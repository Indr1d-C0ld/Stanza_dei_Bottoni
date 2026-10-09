<?php

declare(strict_types=1);

/**
 * Importa due indici di V-Dem sui diritti e produce db/seed/repressione.php.
 *
 * PERCHE' ESISTE. Lo stato di polizia partiva da 2 per tutti e il controllo
 * dell'informazione da 50, e poi si muovevano solo con la minaccia interna: una
 * Corea del Nord tranquilla scivolava verso la polizia di una democrazia, e
 * la Norvegia partiva col racconto controllato a meta' (docs/31).
 *
 * Quanto un regime reprime e censura e' una proprieta' del regime, e V-Dem la
 * misura:
 *
 *   - Physical Integrity Rights Index (v2x_clphy): quanto la gente e' libera
 *     da uccisioni politiche e tortura da parte del governo, 0..1;
 *   - Freedom of Expression and Alternative Sources of Information Index
 *     (v2x_freexp_altinf): stampa, opinione, accademia, 0..1.
 *
 * Fonte: V-Dem Institute (Universita' di Goteborg), dataset v16 (2026), via Our
 * World in Data (aggiornato al 17/03/2026), anno 2025:
 *
 *   https://ourworldindata.org/grapher/physical-integrity-rights-index-vdem.csv
 *   https://ourworldindata.org/grapher/freedom-of-expression-index.csv
 *
 *   php bin/importa_repressione.php
 *
 * Legge storage/fonti/vdem-*.csv. I paesi che V-Dem non copre prendono la
 * mediana della loro regione.
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$file = [
    'integrita'   => $radice . '/storage/fonti/vdem-physical-integrity-rights-index-vdem.csv',
    'espressione' => $radice . '/storage/fonti/vdem-freedom-of-expression-index.csv',
];
$ultimo = [];   // iso => indice => [anno, valore]
foreach ($file as $indice => $percorso) {
    $f = @fopen($percorso, 'r');
    if ($f === false) {
        fwrite(STDERR, "Manca $percorso: si scarica da Our World in Data (vedi l'intestazione).\n");
        exit(1);
    }
    fgetcsv($f, 0, ',', '"', '\\');
    while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
        if (count($r) < 4 || $r[1] === '' || $r[3] === '') {
            continue;
        }
        [, $iso, $anno, $valore] = $r;
        if (!isset($ultimo[$iso][$indice]) || (int) $anno > $ultimo[$iso][$indice][0]) {
            $ultimo[$iso][$indice] = [(int) $anno, (float) $valore];
        }
    }
    fclose($f);
}
// OWID chiama il Kosovo come la Banca Mondiale non fa.
if (isset($ultimo['OWID_KOS']) && !isset($ultimo['XKX'])) {
    $ultimo['XKX'] = $ultimo['OWID_KOS'];
}

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$esito = [];
$perRegione = [];
foreach ($mondo->elenco() as $n) {
    $v = $ultimo[$n->iso3] ?? [];
    if (isset($v['integrita'], $v['espressione']) && $v['integrita'][0] >= 2020) {
        $esito[$n->iso3] = ['integrita' => $v['integrita'][1], 'espressione' => $v['espressione'][1]];
        $perRegione[$n->regione][] = $esito[$n->iso3];
    }
}
$mancano = [];
foreach ($mondo->elenco() as $n) {
    if (isset($esito[$n->iso3])) {
        continue;
    }
    $mancano[] = $n->iso3;
    $r = $perRegione[$n->regione] ?? [['integrita' => 0.5, 'espressione' => 0.5]];
    $mediana = static function (array $v): float {
        sort($v);
        return $v[intdiv(count($v), 2)];
    };
    $esito[$n->iso3] = [
        'integrita'   => $mediana(array_column($r, 'integrita')),
        'espressione' => $mediana(array_column($r, 'espressione')),
    ];
}
ksort($esito);

$righe = [];
foreach ($esito as $iso => $v) {
    $righe[] = sprintf("    '%s' => ['integrita' => %.3f, 'espressione' => %.3f],", $iso, $v['integrita'], $v['espressione']);
}
$testa = <<<PHP
<?php

// Generato da bin/importa_repressione.php — non modificare a mano.
//
// V-Dem v16 (2026) via Our World in Data, anno 2025: integrita = Physical
// Integrity Rights Index, espressione = Freedom of Expression and Alternative
// Sources of Information Index, entrambi 0..1 (1 = piu' liberi). Prendono la
// mediana della regione: %s.

return [

PHP;
file_put_contents($radice . '/db/seed/repressione.php',
    sprintf($testa, $mancano === [] ? 'nessuno' : implode(', ', $mancano)) . implode("\n", $righe) . "\n];\n");
printf("%d paesi da V-Dem, %d alla mediana regionale: %s\n",
    count($esito) - count($mancano), count($mancano), implode(' ', $mancano));
