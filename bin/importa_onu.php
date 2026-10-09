<?php

declare(strict_types=1);

/**
 * Importa i punti ideali dei voti all'Assemblea generale dell'ONU e stima come
 * diventano affinita'; produce db/seed/onu.php.
 *
 * PERCHE' ESISTE. L'affinita' di partenza fra due Stati era una formula
 * sull'orientamento politico — 25 meno la distanza ideologica — piu' 137
 * rapporti scritti a mano e dichiarati [FABBRICATO]. Fuori da quei 137, quasi
 * tutte le coppie del mondo valevano 25: la mediana, il primo e il terzo
 * quartile erano lo stesso numero (docs/32).
 *
 * La politica estera di uno Stato si misura da come vota e con chi si allea:
 *
 *   BAILEY, STREZHNEV, VOETEN (2017), «Estimating Dynamic State Preferences
 *   from United Nations Voting Data», Journal of Conflict Resolution 61(2) —
 *   un punto ideale per Stato e per anno, su un asse che va dal consenso dei
 *   paesi in via di sviluppo all'allineamento con gli Stati Uniti;
 *   SIGNORINO, RITTER (1999), «Tau-b or Not Tau-b: Measuring the Similarity of
 *   Foreign Policy Positions», International Studies Quarterly 43(1); HAGE
 *   (2011), Political Analysis 19(3) — voti e portafogli di alleanze insieme.
 *
 * Ma votare uguale non vuol dire volersi bene: l'India e il Pakistan votano
 * quasi allo stesso modo. L'ostilita' la dicono le dispute militarizzate
 * (db/seed/rivalita.php). Quindi l'affinita' strutturale di una coppia e'
 *
 *   costante + a * distanza dei punti ideali + b * difesa bilaterale
 *            + c * difesa in un patto integrato (NATO, CSTO)
 *            + d * difesa in un patto fra molti sulla carta / radice(membri - 1)
 *            + e * dispute
 *
 * e i pesi NON si scelgono: si stimano qui, ai minimi quadrati, sui rapporti
 * dichiarati di db/seed/politica-nota.php. La scala resta quella dei rapporti
 * dichiarati; il valore di ogni coppia viene dai dati. I rapporti dichiarati
 * restano dove i dati non vedono la rivalita' — l'Arabia Saudita e l'Iran, il
 * Giappone e la Corea del Nord non si sono mai sparati addosso direttamente.
 *
 * Fonte: Voeten, «United Nations General Assembly Ideal Point Estimates,
 * 1946-2025», Harvard Dataverse doi:10.7910/DVN/LEJUQZ, versione 39
 * (30/07/2026), file IdealpointestimatesFP_2026FP.csv. Media del 2023-2025;
 * chi non ha votato in quegli anni prende l'ultimo anno in cui ha votato, se
 * e' dal 2015 in poi.
 *
 *   php bin/importa_onu.php
 *
 * Va lanciato DOPO bin/importa_alleanze.php e bin/importa_rivalita.php.
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$file = $radice . '/storage/fonti/unga/ideal-points-fp-2026.csv';
if (!is_file($file)) {
    fwrite(STDERR, "Manca $file.\nSi scarica da https://dataverse.harvard.edu/api/access/datafile/14098429\n");
    exit(1);
}

// --- i punti ideali -----------------------------------------------------------
$f = fopen($file, 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
$perAnno = [];
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    $perAnno[(string) $d['iso3c']][(int) $d['year']] = (float) $d['IdealPointFP'];
}
fclose($f);

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$punti = [];
$vecchi = [];
foreach ($mondo->elenco() as $n) {
    $anni = $perAnno[$n->iso3] ?? [];
    $recenti = array_filter($anni, static fn(int $a): bool => $a >= 2023, ARRAY_FILTER_USE_KEY);
    if ($recenti !== []) {
        $punti[$n->iso3] = array_sum($recenti) / count($recenti);
    } elseif ($anni !== [] && max(array_keys($anni)) >= 2015) {
        // Il Venezuela ha perso il voto per gli arretrati: vale il suo ultimo
        // anno. Ma non Taiwan, il cui ultimo voto e' della Repubblica di Cina
        // nel 1971.
        ksort($anni);
        $punti[$n->iso3] = end($anni);
        $vecchi[] = $n->iso3 . ' ' . array_key_last($anni);
    }
}
$mancano = array_values(array_diff(array_keys($mondo->nazioni), array_keys($punti)));

// --- la stima dei pesi --------------------------------------------------------
$patti = require $radice . '/db/seed/patti-difesa.php';
$rivalita = require $radice . '/db/seed/rivalita.php';
$politica = require $radice . '/db/seed/politica-nota.php';

$variabili = static function (string $a, string $b) use ($punti, $patti, $rivalita): ?array {
    if (!isset($punti[$a], $punti[$b])) {
        return null;
    }
    $tipo = $patti["$a|$b"] ?? $patti["$b|$a"] ?? '';
    $k = strcmp($a, $b) < 0 ? "$a|$b" : "$b|$a";
    return [
        1.0,
        abs($punti[$a] - $punti[$b]),
        $tipo === 'bilaterale' ? 1.0 : 0.0,
        $tipo === 'integrato' ? 1.0 : 0.0,
        // Sulla carta, la promessa a ciascuno si diluisce coi membri: si
        // stima il peso di 1/radice(membri - 1).
        str_starts_with($tipo, 'multilaterale:') ? 1.0 / sqrt(max(1, (int) explode(':', $tipo)[1] - 1)) : 0.0,
        (float) min(10, (int) ($rivalita[$k]['dispute'] ?? 0)),
    ];
};
$X = [];
$y = [];
foreach ($politica['rapporti'] as [$a, $b, $valore]) {
    $v = $variabili((string) $a, (string) $b);
    if ($v !== null) {
        $X[] = $v;
        $y[] = (float) $valore;
    }
}

/** Minimi quadrati: (X'X)^-1 X'y, con l'eliminazione di Gauss. */
$k = count($X[0]);
$A = array_fill(0, $k, array_fill(0, $k + 1, 0.0));
foreach ($X as $i => $riga) {
    for ($p = 0; $p < $k; $p++) {
        for ($q = 0; $q < $k; $q++) {
            $A[$p][$q] += $riga[$p] * $riga[$q];
        }
        $A[$p][$k] += $riga[$p] * $y[$i];
    }
}
for ($p = 0; $p < $k; $p++) {
    $perno = $p;
    for ($q = $p + 1; $q < $k; $q++) {
        if (abs($A[$q][$p]) > abs($A[$perno][$p])) {
            $perno = $q;
        }
    }
    [$A[$p], $A[$perno]] = [$A[$perno], $A[$p]];
    for ($q = 0; $q < $k; $q++) {
        if ($q !== $p && $A[$p][$p] != 0.0) {
            $fattore = $A[$q][$p] / $A[$p][$p];
            for ($c = $p; $c <= $k; $c++) {
                $A[$q][$c] -= $fattore * $A[$p][$c];
            }
        }
    }
}
$coef = [];
for ($p = 0; $p < $k; $p++) {
    $coef[] = $A[$p][$k] / $A[$p][$p];
}
$media = array_sum($y) / count($y);
$ssTot = 0.0;
$ssRes = 0.0;
foreach ($X as $i => $riga) {
    $stima = 0.0;
    foreach ($riga as $p => $x) {
        $stima += $coef[$p] * $x;
    }
    $ssRes += ($y[$i] - $stima) ** 2;
    $ssTot += ($y[$i] - $media) ** 2;
}
$r2 = 1.0 - $ssRes / max(1e-9, $ssTot);

// --- uscita -------------------------------------------------------------------
ksort($punti);
$righe = [];
foreach ($punti as $iso => $v) {
    $righe[] = sprintf("        '%s' => %.4f,", $iso, $v);
}
$out = sprintf(<<<PHP
<?php

// Generato da bin/importa_onu.php — non modificare a mano.
//
// Punti ideali dei voti all'Assemblea generale dell'ONU (Bailey, Strezhnev,
// Voeten 2017; Voeten, Harvard Dataverse, v39 del 30/07/2026), media 2023-2025.
// Ultimo anno votato, per chi non ha votato dopo il 2022: %s. Senza punto
// ideale (non membri dell'ONU, o senza voti dal 2015), e prendono la formula
// ideologica: %s.
//
// I pesi dell'affinita' strutturale, stimati ai minimi quadrati su %d rapporti
// dichiarati (R² = %.2f):
//
//   affinita' = %.1f %+.1f * distanza %+.1f * difesa bilaterale
//              %+.1f * patto integrato %+.1f * patto sulla carta / radice(membri-1)
//              %+.1f * dispute (fino a dieci)

return [
    'pesi' => [
        'costante'      => %.3f,
        'distanza'      => %.3f,
        'bilaterale'    => %.3f,
        'integrato'     => %.3f,
        'multilaterale' => %.3f,
        'dispute'       => %.3f,
        'r2'            => %.3f,
    ],
    'punti' => [

PHP,
    $vecchi === [] ? 'nessuno' : implode(', ', $vecchi), $mancano === [] ? 'nessuno' : implode(', ', $mancano),
    count($y), $r2, $coef[0], $coef[1], $coef[2], $coef[3], $coef[4], $coef[5],
    $coef[0], $coef[1], $coef[2], $coef[3], $coef[4], $coef[5], $r2)
    . implode("\n", $righe) . "\n    ],\n];\n";
file_put_contents($radice . '/db/seed/onu.php', $out);

printf("%d punti ideali; %d senza. Pesi su %d rapporti (R2 %.2f): %.1f %+.1f*d %+.1f*bil %+.1f*integr %+.1f*carta %+.1f*disp\n",
    count($punti), count($mancano), count($y), $r2, ...$coef);
