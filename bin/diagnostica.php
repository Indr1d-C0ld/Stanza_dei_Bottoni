<?php

declare(strict_types=1);

/**
 * Perché il mondo è sempre lo stesso?
 *
 * Fa girare N semi e misura dove muore la varianza. Un insieme che non si apre
 * non è un insieme, e tutta la validazione statistica del simulatore autonomo
 * si regge sugli insiemi.
 *
 *   php bin/diagnostica.php --semi=8 --anni=15
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Simulazione\EsecutoreTick;

$opz     = getopt('', ['semi::', 'anni::', 'profilo::']);
$nSemi   = (int) ($opz['semi'] ?? 8);
$anni    = (int) ($opz['anni'] ?? 15);
$profilo = (string) ($opz['profilo'] ?? 'osservazione');

$cal      = Calibrazione::carica($radice, $profilo);
$tickAnno = (int) $cal->numero('tempo.tick_per_anno', 52.0);
$totale   = $anni * $tickAnno;

// Alcuni paesi da tenere d'occhio: due fragili, due mediani, due solidi.
$osservati = ['SDN', 'HTI', 'VEN', 'ECU', 'ITA', 'BWA'];

$corse = [];
echo "corse: ";
for ($i = 0; $i < $nSemi; $i++) {
    $seme  = 1000 + $i * 137;
    $mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
    $es    = new EsecutoreTick($cal, null, true, $mondo);

    $traiettorie = [];
    for ($t = 1; $t <= $totale; $t++) {
        $mondo->tick = $t;
        $es->esegui($t, $seme);
        if ($t % $tickAnno === 0) {
            foreach ($osservati as $iso) {
                $traiettorie[$iso][] = round($mondo->nazioni[$iso]->legittimita, 1);
            }
        }
    }

    $cambi = [];
    $fragili = [];
    $irregolari = 0;
    $rivoluzioni = 0;
    $sottoSoglia = 0;
    foreach ($mondo->nazioni as $n) {
        if ($n->cambiEsecutivo > 0) {
            $cambi[$n->iso3] = $n->cambiEsecutivo;
        }
        // L'insieme dei paesi si misura sui soli cambi irregolari: con le
        // elezioni a calendario quasi ogni paese cambia governo almeno una
        // volta in quindici anni, e l'indice di Jaccard smetterebbe di
        // misurare quel che deve — cioe' se la FRAGILITA' e' la stessa in
        // ogni storia possibile.
        if ($n->cambiIrregolari > 0) {
            $fragili[$n->iso3] = $n->cambiIrregolari;
        }
        $irregolari += $n->cambiIrregolari;
        $rivoluzioni += $n->vittorieInsorti;
        if ($n->legittimita < 30.0) {
            $sottoSoglia++;
        }
    }
    $corse[] = [
        'seme'        => $seme,
        'totale'      => array_sum($cambi),
        'irregolari'  => $irregolari,
        'rivoluzioni' => $rivoluzioni,
        'fragili'     => $fragili,
        'paesi'       => $cambi,
        'traiettorie' => $traiettorie,
        'sotto_soglia' => $sottoSoglia,
    ];
    echo '.';
}
echo "\n\n";

// --- 1. il totale si muove? --------------------------------------------------
$totali = array_column($corse, 'totale');
$media  = array_sum($totali) / count($totali);
$varianza = 0.0;
foreach ($totali as $x) { $varianza += ($x - $media) ** 2; }
$sd = sqrt($varianza / max(1, count($totali) - 1));
$sdPoisson = sqrt($media);

$irr = array_column($corse, 'irregolari');
$mediaIrr = array_sum($irr) / count($irr);
printf("CAMBI IRREGOLARI per corsa: %s  (media %.1f = %.1f l'anno; riferimento ~10)\n",
    implode(' ', $irr), $mediaIrr, $mediaIrr / $anni);
printf("CAMBI DI ESECUTIVO per corsa: %s\n", implode(' ', $totali));
printf("  media %.1f · scarto osservato %.2f · scarto di Poisson atteso %.2f\n", $media, $sd, $sdPoisson);
printf("  rapporto osservato/atteso: %.2f  %s\n\n", $sd / max(0.001, $sdPoisson),
    $sd / max(0.001, $sdPoisson) < 0.5 ? '<< il mondo e\' troppo prevedibile' : 'plausibile');

// --- 2. sono sempre gli stessi paesi? ----------------------------------------
$insiemi = array_map(static fn($c) => array_keys($c['fragili']), $corse);
$intersezione = $insiemi[0];
$unione = [];
foreach ($insiemi as $ins) {
    $intersezione = array_intersect($intersezione, $ins);
    $unione = array_unique(array_merge($unione, $ins));
}
printf("PAESI CON CAMBI IRREGOLARI: unione %d · intersezione %d · Jaccard %.2f\n",
    count($unione), count($intersezione), count($intersezione) / max(1, count($unione)));
printf("  (1,00 = sempre esattamente gli stessi paesi, qualunque sia il seme)\n\n");

// --- 3. quanto varia il NUMERO di cambi per ciascun paese? -------------------
$riv = array_column($corse, 'rivoluzioni');
printf("RIVOLUZIONI per corsa: %s  (media %.1f in %d anni; riferimento ~%d)\n\n",
    implode(' ', $riv), array_sum($riv) / count($riv), $anni, (int) round($anni));

$perPaese = [];
foreach ($unione as $iso) {
    $valori = array_map(static fn($c) => $c['fragili'][$iso] ?? 0, $corse);
    $m = array_sum($valori) / count($valori);
    $v = 0.0;
    foreach ($valori as $x) { $v += ($x - $m) ** 2; }
    $perPaese[$iso] = ['media' => $m, 'sd' => sqrt($v / max(1, count($valori) - 1)), 'valori' => $valori];
}
uasort($perPaese, static fn($a, $b) => $b['sd'] <=> $a['sd']);
echo "PAESI CON PIU' VARIANZA (quanti cambi, per seme):\n";
$i = 0;
foreach ($perPaese as $iso => $d) {
    if ($i++ >= 5) { break; }
    printf("  %-4s media %.1f sd %.2f   %s\n", $iso, $d['media'], $d['sd'], implode(' ', $d['valori']));
}
$deterministici = count(array_filter($perPaese, static fn($d) => $d['sd'] < 0.01));
printf("  paesi con esito IDENTICO in tutte le corse: %d su %d\n\n", $deterministici, count($perPaese));

// --- 4. le traiettorie di legittimità divergono? -----------------------------
echo "LEGITTIMITA' a fine corsa, per paese osservato (una colonna per seme):\n";
foreach ($osservati as $iso) {
    $finali = array_map(static fn($c) => end($c['traiettorie'][$iso]), $corse);
    $m = array_sum($finali) / count($finali);
    $v = 0.0;
    foreach ($finali as $x) { $v += ($x - $m) ** 2; }
    printf("  %-4s media %5.1f  sd %5.2f   %s\n", $iso, $m, sqrt($v / max(1, count($finali) - 1)),
        implode(' ', array_map(static fn($x) => sprintf('%5.1f', $x), $finali)));
}

// --- 5. dove si apre la forbice nel tempo? -----------------------------------
echo "\nAPERTURA DELLA FORBICE nel tempo (sd della legittimità fra semi):\n";
printf("  %-4s %s\n", 'anno', implode(' ', array_map(static fn($i) => sprintf('%5s', $i), $osservati)));
for ($a = 1; $a <= $anni; $a += max(1, (int) ($anni / 5))) {
    printf("  %-4d ", $a);
    foreach ($osservati as $iso) {
        $valori = array_map(static fn($c) => $c['traiettorie'][$iso][$a - 1] ?? 0, $corse);
        $m = array_sum($valori) / count($valori);
        $v = 0.0;
        foreach ($valori as $x) { $v += ($x - $m) ** 2; }
        printf('%5.2f ', sqrt($v / max(1, count($valori) - 1)));
    }
    echo "\n";
}
