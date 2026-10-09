<?php

declare(strict_types=1);

/**
 * Quanti civili muoiono per ogni combattente, nelle guerre fra Stati: la
 * misura dietro conflitto.civili_per_militare (docs/34).
 *
 * Il modello metteva un civile per ogni militare caduto, la media storica di
 * Eckhardt su tre secoli. Nelle guerre di oggi non e' cosi': nella guerra
 * russo-ucraina il conto UCDP dei morti in battaglia (che comprende i civili
 * uccisi nei combattimenti) e' di 76.000-102.000 l'anno, e il modello ne
 * contava 150.000 perche' raddoppiava i suoi 75.000 caduti militari.
 *
 * Fonte: UCDP Georeferenced Event Dataset v26.1 (Sundberg e Melander 2013,
 * Journal of Peace Research 50(4)), violenza statale (type_of_violence = 1),
 * solo i conflitti fra due governi secondo l'UCDP/PRIO Armed Conflict Dataset
 * v26.1. Rapporto fra deaths_civilians e deaths_a + deaths_b.
 *
 *   php bin/misura_civili.php
 *
 * Legge storage/fonti/ucdp/ged-261.zip direttamente, senza scompattarlo (274 MB),
 * e storage/fonti/ucdp/UcdpPrioConflict_v26_1.csv.
 */

$radice = require __DIR__ . '/_avvio.php';

$zip = $radice . '/storage/fonti/ucdp/ged-261.zip';
$acd = $radice . '/storage/fonti/ucdp/UcdpPrioConflict_v26_1.csv';
if (!is_file($zip) || !is_file($acd)) {
    fwrite(STDERR, "Servono $zip (https://ucdp.uu.se/downloads/ged/ged261-csv.zip)\n"
        . "e $acd (https://ucdp.uu.se/downloads/ucdpprio/ucdp-prio-acd-261-csv.zip).\n");
    exit(1);
}

$fraStati = [];
$f = fopen($acd, 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    if ((int) $d['type_of_conflict'] === 2) {
        $fraStati[(string) $d['conflict_id']] = true;
    }
}
fclose($f);

$f = fopen('zip://' . $zip . '#GEDEvent_v26_1.csv', 'r');
if ($f === false) {
    fwrite(STDERR, "Non riesco a leggere GEDEvent_v26_1.csv dentro $zip\n");
    exit(1);
}
$t = fgetcsv($f, 0, ',', '"', '\\');
$i = array_flip($t);
$combattenti = 0;
$civili = 0;
$perGuerra = [];
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    if ((int) $r[$i['type_of_violence']] !== 1 || !isset($fraStati[(string) $r[$i['conflict_new_id']]])) {
        continue;
    }
    $c = (int) $r[$i['deaths_a']] + (int) $r[$i['deaths_b']];
    $v = (int) $r[$i['deaths_civilians']];
    $combattenti += $c;
    $civili += $v;
    $k = $r[$i['conflict_name']] . ' ' . $r[$i['year']];
    $perGuerra[$k] = [($perGuerra[$k][0] ?? 0) + $c, ($perGuerra[$k][1] ?? 0) + $v];
}
fclose($f);

ksort($perGuerra);
foreach ($perGuerra as $k => [$c, $v]) {
    if ($c + $v >= 1000) {
        printf("  %-55s combattenti %7d  civili %6d  rapporto %.3f\n", $k, $c, $v, $v / max(1, $c));
    }
}
printf("\nGuerre fra Stati 1989-2025: %d combattenti, %d civili: %.3f civili per combattente.\n",
    $combattenti, $civili, $civili / max(1, $combattenti));
