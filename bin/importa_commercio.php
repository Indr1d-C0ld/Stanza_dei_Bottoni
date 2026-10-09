<?php

declare(strict_types=1);

/**
 * Importa quanto ogni paese esporta e importa, settore per settore, e produce
 * db/seed/commercio-dati.php.
 *
 * PERCHE' ESISTE. Il grafo del commercio (docs/21) ricavava chi vende cosa da
 * terra, ricchezza, istruzione e taglia, piu' moltiplicatori dichiarati
 * [FABBRICATO] per chi ha il petrolio sottoterra; e quanto ognuno compra fuori
 * da una formula sul suo peso nel mondo. Confrontate coi dati, le esportazioni
 * in rapporto al PIL avevano una correlazione di 0,06: il Belgio esportava lo
 * 0% contro l'87% vero, l'Irlanda il 16% contro il 139%, l'Arabia Saudita il 91%
 * contro il 31% (docs/35).
 *
 * Fonte: Banca Mondiale, World Development Indicators (API aggiornata
 * l'08/10/2026), media 2021-2024 per paese (il 2020 della pandemia escluso):
 *
 *   BX.GSR.MRCH.CD, BM.GSR.MRCH.CD   beni esportati e importati, dollari correnti
 *   BX.GSR.NFSV.CD, BM.GSR.NFSV.CD   servizi esportati e importati
 *   NY.GDP.MKTP.CD                   PIL, dollari correnti
 *   T?.VAL.FUEL.ZS.UN                combustibili, % dei beni
 *   T?.VAL.FOOD.ZS.UN, T?.VAL.AGRI.ZS.UN   cibo e materie prime agricole
 *   T?.VAL.MANF.ZS.UN, T?.VAL.MMTL.ZS.UN   manufatti, minerali e metalli
 *   T?.VAL.ICTG.ZS.UN                beni dell'informatica e delle telecomunicazioni
 *   B?.GSR.INSF.ZS                   servizi assicurativi e finanziari, % dei servizi
 *   BX.GSR.CCIS.ZS                   servizi informatici, % dei servizi esportati
 *
 * I cinque settori del modello:
 *
 *   energia      combustibili
 *   cibo         cibo + materie prime agricole
 *   tecnologia   beni informatici + servizi informatici (per le importazioni
 *                solo i beni: l'API non ha i servizi informatici importati)
 *   manifattura  manufatti meno i beni informatici, + minerali e metalli
 *   finanza      servizi assicurativi e finanziari
 *
 * Ciascuno in frazione del PIL del paese: il modello la applica al proprio PIL,
 * che e' a parita' di potere d'acquisto. Chi la Banca Mondiale non copre resta
 * col modello di prima (docs/21).
 *
 *   php bin/importa_commercio.php
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$cartella = $radice . '/storage/fonti/commercio';
$serie = static function (string $codice) use ($cartella): array {
    $f = "$cartella/$codice.json";
    $d = json_decode((string) @file_get_contents($f), true);
    if (!is_array($d[1] ?? null)) {
        fwrite(STDERR, "Manca o e' vuoto $f: https://api.worldbank.org/v2/country/all/indicator/$codice"
            . "?format=json&date=2019:2024&per_page=20000\n");
        exit(1);
    }
    $somme = [];
    foreach ($d[1] as $r) {
        $anno = (int) $r['date'];
        if ($r['value'] !== null && $anno >= 2021 && $anno <= 2024) {
            $somme[(string) $r['countryiso3code']][] = (float) $r['value'];
        }
    }
    return array_map(static fn(array $v): float => array_sum($v) / count($v), $somme);
};

$pil = $serie('NY.GDP.MKTP.CD');
$beni = ['x' => $serie('BX.GSR.MRCH.CD'), 'm' => $serie('BM.GSR.MRCH.CD')];
$servizi = ['x' => $serie('BX.GSR.NFSV.CD'), 'm' => $serie('BM.GSR.NFSV.CD')];
$quota = [];
foreach (['x' => 'TX', 'm' => 'TM'] as $verso => $p) {
    foreach (['FUEL', 'FOOD', 'AGRI', 'MANF', 'MMTL', 'ICTG'] as $k) {
        $quota[$verso][$k] = $serie("$p.VAL.$k.ZS.UN");
    }
}
$finanza = ['x' => $serie('BX.GSR.INSF.ZS'), 'm' => $serie('BM.GSR.INSF.ZS')];
$informatica = $serie('BX.GSR.CCIS.ZS');

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');

// Chi ha i totali ma non la composizione delle merci (l'Iran, il Bangladesh, la
// Bielorussia...) prende la composizione mediana della sua regione: i totali
// restano i suoi.
$perRegione = [];
foreach ($mondo->elenco() as $n) {
    foreach (['x', 'm'] as $verso) {
        foreach ($quota[$verso] as $k => $valori) {
            if (isset($valori[$n->iso3])) {
                $perRegione[$n->regione][$verso][$k][] = $valori[$n->iso3];
            }
        }
    }
}
$mediana = static function (array $v): float {
    sort($v);
    return $v === [] ? 0.0 : $v[intdiv(count($v), 2)];
};
$prestati = [];

// Chi la Banca Mondiale non copre — Taiwan, l'Iran, la Birmania, il
// Turkmenistan... — prende i beni scambiati dai flussi bilaterali del FMI
// (IMTS, gli stessi di bin/importa_imts.php: quel che il paese esporta, e
// quel che gli altri gli vendono, che c'e' anche quando il paese non dichiara
// niente), il PIL in dollari dal FMI (World Economic Outlook, NGDPD, media
// 2023-2024) e la composizione mediana della sua regione. Niente servizi: non
// ci sono.
$imts = [];
$alias = ['UVK' => 'XKX', 'KOS' => 'XKX'];
foreach (['imts-esportazioni-2023-2024.xml' => 1.0, 'imts-importazioni-da-taiwan-2023-2024.xml' => 1.0 / 1.06]
         as $nome => $fattore) {
    $xml = new XMLReader();
    if (!@$xml->open("$cartella/$nome")) {
        continue;
    }
    $coppia = null;
    $anni = [];
    while ($xml->read()) {
        if ($xml->nodeType !== XMLReader::ELEMENT) {
            continue;
        }
        if ($xml->localName === 'Series') {
            $da = (string) $xml->getAttribute('COUNTRY');
            $a  = (string) $xml->getAttribute('COUNTERPART_COUNTRY');
            // Nel secondo file chi dichiara e' l'importatore: il flusso va da Taiwan a lui.
            $coppia = $nome === 'imts-esportazioni-2023-2024.xml'
                ? [($alias[$da] ?? $da), ($alias[$a] ?? $a)]
                : ['TWN', ($alias[$da] ?? $da)];
            // Solo coppie di paesi del mondo: il FMI mette fra le controparti
            // anche il mondo intero e gli aggregati regionali (G001, G200...).
            if (!isset($mondo->nazioni[$coppia[0]], $mondo->nazioni[$coppia[1]]) || $coppia[0] === $coppia[1]) {
                $coppia = null;
            }
        } elseif ($xml->localName === 'Obs' && $coppia !== null) {
            $v = $xml->getAttribute('OBS_VALUE');
            if (is_numeric($v) && (float) $v > 0.0) {
                $anni[$coppia[0] . '|' . $coppia[1]][] = (float) $v * $fattore;
            }
        }
    }
    $xml->close();
    foreach ($anni as $k => $v) {
        [$da, $a] = explode('|', $k);
        $media = array_sum($v) / count($v);
        $imts['x'][$da] = ($imts['x'][$da] ?? 0.0) + $media;
        $imts['m'][$a]  = ($imts['m'][$a] ?? 0.0) + $media;
    }
}
$pilFmi = [];
$weo = json_decode((string) @file_get_contents($radice . '/storage/fonti/fmi-weo-ngdpd.json'), true);
foreach ((array) ($weo['values']['NGDPD'] ?? []) as $iso => $s) {
    $v = array_values(array_filter([$s['2023'] ?? null, $s['2024'] ?? null], 'is_numeric'));
    if ($v !== []) {
        $pilFmi[$iso] = array_sum($v) / count($v) * 1.0e9;
    }
}
$daFmi = [];
// L'Iran no: il petrolio che vende alla Cina sotto sanzioni le dogane cinesi lo
// registrano come malese, e nei flussi del FMI l'Iran esporterebbe il 3% del PIL
// contro il 20% circa delle stime (EIA, Kpler). Meglio il modello di docs/21.
unset($pilFmi['IRN']);

$esito = [];
$mancano = [];
foreach ($mondo->elenco() as $n) {
    $iso = $n->iso3;
    $p = $pil[$iso] ?? 0.0;
    if (($p <= 0.0 || !isset($beni['x'][$iso], $beni['m'][$iso]))
        && isset($pilFmi[$iso], $imts['x'][$iso], $imts['m'][$iso])) {
        $p = $pilFmi[$iso];
        $beni['x'][$iso] = $imts['x'][$iso];
        $beni['m'][$iso] = $imts['m'][$iso];
        $servizi['x'][$iso] = 0.0;
        $servizi['m'][$iso] = 0.0;
        unset($quota['x']['FUEL'][$iso], $quota['m']['FUEL'][$iso]);
        $daFmi[] = $iso;
    }
    if ($p <= 0.0 || !isset($beni['x'][$iso], $beni['m'][$iso])) {
        $mancano[] = $iso;
        continue;
    }
    foreach (['x', 'm'] as $verso) {
        if (!isset($quota[$verso]['FUEL'][$iso])) {
            foreach ($quota[$verso] as $k => $valori) {
                $quota[$verso][$k][$iso] = $mediana($perRegione[$n->regione][$verso][$k] ?? []);
            }
            $prestati[$iso] = true;
        }
    }
    foreach (['x' => 'esporta', 'm' => 'importa'] as $verso => $nome) {
        $b = $beni[$verso][$iso];
        $s = $servizi[$verso][$iso] ?? 0.0;
        $q = static fn(string $k): float => ($quota[$verso][$k][$iso] ?? 0.0) / 100.0;
        $tecnologia = $b * $q('ICTG') + ($verso === 'x' ? $s * ($informatica[$iso] ?? 0.0) / 100.0 : 0.0);
        $esito[$iso][$nome] = [
            'energia'     => $b * $q('FUEL') / $p,
            'cibo'        => $b * ($q('FOOD') + $q('AGRI')) / $p,
            'tecnologia'  => $tecnologia / $p,
            'finanza'     => $s * ($finanza[$verso][$iso] ?? 0.0) / 100.0 / $p,
            'manifattura' => $b * (max(0.0, $q('MANF') - $q('ICTG')) + $q('MMTL')) / $p,
        ];
    }
}
ksort($esito);

$righe = [];
foreach ($esito as $iso => $v) {
    $fmt = static fn(array $s): string => implode(', ', array_map(
        static fn(string $k, float $x): string => sprintf("'%s' => %.4f", $k, $x), array_keys($s), $s));
    $righe[] = sprintf("    '%s' => ['esporta' => [%s],\n              'importa' => [%s]],", $iso,
        $fmt($v['esporta']), $fmt($v['importa']));
}
$testa = <<<PHP
<?php

// Generato da bin/importa_commercio.php — non modificare a mano.
//
// Esportazioni e importazioni per settore, in frazione del PIL a prezzi
// correnti. Banca Mondiale, World Development Indicators, media 2021-2024.
// Composizione delle merci presa dalla mediana della regione: %s.
// Beni dai flussi bilaterali del FMI e PIL dal FMI (la Banca Mondiale non li
// copre), senza servizi: %s.
// Senza dati, e restano al modello di docs/21: %s.

return [

PHP;
file_put_contents($radice . '/db/seed/commercio-dati.php',
    sprintf($testa, $prestati === [] ? 'nessuno' : implode(', ', array_keys($prestati)),
        $daFmi === [] ? 'nessuno' : implode(', ', $daFmi),
        $mancano === [] ? 'nessuno' : implode(', ', $mancano)) . implode("\n", $righe) . "\n];\n");
printf("%d paesi coi dati, %d senza: %s\n", count($esito), count($mancano), implode(' ', $mancano));
