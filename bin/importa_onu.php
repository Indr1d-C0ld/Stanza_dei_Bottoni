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
 *            + f * rivalita' strategica + g * ostile per la Russia
 *
 * e i pesi NON si scelgono: si stimano qui, ai minimi quadrati, sui rapporti
 * dichiarati di db/seed/politica-nota.php. La scala resta quella dei rapporti
 * dichiarati; il valore di ogni coppia viene dai dati. I rapporti dichiarati
 * restano dove i dati non vedono la politica — il Giappone e la Corea del
 * Nord, Israele e il Libano dopo il 2023, l'Ungheria di Orban — ciascuno con
 * la sua ragione in db/seed/politica-nota.php.
 *
 * Fonte: Voeten, «United Nations General Assembly Ideal Point Estimates,
 * 1946-2025», Harvard Dataverse doi:10.7910/DVN/LEJUQZ, versione 39
 * (30/07/2026), file IdealpointestimatesFP_2026FP.csv. Media del 2023-2025;
 * chi non ha votato in quegli anni prende l'ultimo anno in cui ha votato, se
 * e' dal 2015 in poi.
 *
 *   php bin/importa_onu.php
 *
 * Due ostilita' che le dispute non vedono (docs/36), perche' non si spara:
 *
 *   - le RIVALITA' STRATEGICHE di THOMPSON, SAKUWA e SUHAS (2021),
 *     «Analyzing Strategic Rivalries in World Politics», Springer — chi si
 *     considera nemico o concorrente, aggiornamento al 2020 dell'inventario
 *     di Thompson e Dreyer (2012): le 56 ancora in corso nel 2020, l'Iran e
 *     l'Arabia Saudita, l'Algeria e il Marocco, la Russia e l'Ucraina. File
 *     di Kentaro Sakuwa, storage/fonti/rivalita-strategiche/
 *     tss-rivalita-1816-2020.csv, da
 *     https://www.kentarosakuwa.info/uploads/9/4/6/6/94666922/strategic_rivalry_data_list_of_rivalries_by_type.csv
 *     Prima avevo usato l'edizione ferma al 2010, che teneva aperte rivalita'
 *     finite dopo: l'Iraq con l'Arabia Saudita (2018), l'Etiopia con
 *     l'Eritrea (la pace del 2018).
 *   - i paesi «ostili» per la Russia, ordinanza del governo russo n. 430-r del
 *     5 marzo 2022 e n. 2018 del 23 luglio 2022 (le Bahamas): le sanzioni dopo
 *     l'invasione dell'Ucraina, che le dispute COW (ferme al 2014) non
 *     contano. Prima di questa variabile la distanza dei voti pesava -33 per
 *     spiegare da sola l'ostilita' fra la Russia e l'Europa; adesso -17.
 *
 * Con le due variabili l'R² sui rapporti dichiarati passa da 0,62 a 0,81.
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

// --- le ostilita' senza spari -------------------------------------------------
/** Ordinanze del governo russo 430-r (5/3/2022) e 2018 (23/7/2022). */
const OSTILI_ALLA_RUSSIA = [
    // i 27 dell'Unione Europea
    'AUT', 'BEL', 'BGR', 'HRV', 'CYP', 'CZE', 'DNK', 'EST', 'FIN', 'FRA', 'DEU', 'GRC', 'HUN', 'IRL',
    'ITA', 'LVA', 'LTU', 'LUX', 'MLT', 'NLD', 'POL', 'PRT', 'ROU', 'SVK', 'SVN', 'ESP', 'SWE',
    // gli altri del 5 marzo 2022
    'USA', 'GBR', 'CAN', 'JPN', 'KOR', 'AUS', 'NZL', 'CHE', 'NOR', 'ISL', 'SGP', 'TWN', 'UKR',
    'ALB', 'AND', 'LIE', 'FSM', 'MCO', 'MNE', 'MKD', 'SMR',
    // 23 luglio 2022
    'BHS',
];
$fileTd = $radice . '/storage/fonti/rivalita-strategiche/tss-rivalita-1816-2020.csv';
if (!is_file($fileTd)) {
    fwrite(STDERR, "Manca $fileTd (vedi l'intestazione).\n");
    exit(1);
}
// I codici COW, tradotti come in bin/importa_rivalita.php.
$nomeCow = [];
foreach (file($radice . '/storage/fonti/cow-codici.csv', FILE_IGNORE_NEW_LINES) ?: [] as $riga) {
    $p = str_getcsv($riga, ',', '"', '\\');
    if (count($p) >= 3 && ctype_digit(trim((string) $p[1]))) {
        $nomeCow[(int) $p[1]] = trim((string) $p[2]);
    }
}
$perNome = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($riga = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $riga);
    $perNome[mb_strtolower(trim((string) $d['nome_fonte']))] = (string) $d['iso3'];
}
fclose($fh);
$nomiCow = [
    'United States of America' => 'USA', 'United Arab Emirates' => 'ARE', 'Taiwan' => 'TWN',
    'Democratic Republic of the Congo' => 'COD', 'Central African Republic' => 'CAF', 'Kosovo' => 'XKX',
    'Yugoslavia' => 'SRB', 'Germany' => 'DEU', 'German Federal Republic' => 'DEU', 'Myanmar' => 'MMR',
];
$isoCow = static fn(int $c): ?string => isset($nomeCow[$c])
    ? ($nomiCow[$nomeCow[$c]] ?? $perNome[mb_strtolower($nomeCow[$c])] ?? null) : null;
$strategiche = [];
$fh = fopen($fileTd, 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($riga = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $riga);
    if ((string) $d['ongoing2020'] !== '1') {
        continue;
    }
    $a = $isoCow((int) $d['ccode1']);
    $b = $isoCow((int) $d['ccode2']);
    if ($a === null || $b === null) {
        fwrite(STDERR, "Rivalita' senza paese nel seme: {$d['abbrev1']}-{$d['abbrev2']}\n");
        continue;
    }
    $strategiche[strcmp($a, $b) < 0 ? "$a|$b" : "$b|$a"] = true;
}
fclose($fh);
ksort($strategiche);
$ostili = array_flip(OSTILI_ALLA_RUSSIA);

// --- la stima dei pesi --------------------------------------------------------
$patti = require $radice . '/db/seed/patti-difesa.php';
$rivalita = require $radice . '/db/seed/rivalita.php';
$politica = require $radice . '/db/seed/politica-nota.php';

$variabili = static function (string $a, string $b) use ($punti, $patti, $rivalita, $strategiche, $ostili): ?array {
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
        isset($strategiche[$k]) ? 1.0 : 0.0,
        ($a === 'RUS' && isset($ostili[$b])) || ($b === 'RUS' && isset($ostili[$a])) ? 1.0 : 0.0,
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
//              %+.1f * dispute (fino a dieci) %+.1f * rivalita' strategica
//              %+.1f * ostile per la Russia

return [
    'pesi' => [
        'costante'      => %.3f,
        'distanza'      => %.3f,
        'bilaterale'    => %.3f,
        'integrato'     => %.3f,
        'multilaterale' => %.3f,
        'dispute'       => %.3f,
        'strategica'    => %.3f,
        'ostile_russia' => %.3f,
        'r2'            => %.3f,
    ],
    // Thompson, Sakuwa e Suhas (2021), rivalita' strategiche in corso nel 2020.
    'strategiche' => [%s],
    // Ordinanze 430-r e 2018 del governo russo (2022).
    'ostili_russia' => [%s],
    'punti' => [

PHP,
    $vecchi === [] ? 'nessuno' : implode(', ', $vecchi), $mancano === [] ? 'nessuno' : implode(', ', $mancano),
    count($y), $r2, $coef[0], $coef[1], $coef[2], $coef[3], $coef[4], $coef[5], $coef[6], $coef[7],
    $coef[0], $coef[1], $coef[2], $coef[3], $coef[4], $coef[5], $coef[6], $coef[7], $r2,
    "\n        '" . implode("',\n        '", array_keys($strategiche)) . "',\n    ",
    "'" . implode("', '", OSTILI_ALLA_RUSSIA) . "'")
    . implode("\n", $righe) . "\n    ],\n];\n";
file_put_contents($radice . '/db/seed/onu.php', $out);

printf("%d punti ideali; %d senza. Pesi su %d rapporti (R2 %.2f): %.1f %+.1f*d %+.1f*bil %+.1f*integr %+.1f*carta %+.1f*disp %+.1f*strat %+.1f*ostili\n",
    count($punti), count($mancano), count($y), $r2, ...$coef);
