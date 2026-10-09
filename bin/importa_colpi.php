<?php

declare(strict_types=1);

/**
 * Importa l'ultimo colpo di Stato riuscito di ogni paese e produce
 * db/seed/colpi.php.
 *
 * PERCHE' ESISTE. Chi ha appena avuto un colpo di Stato ne ha altri: e' la
 * «trappola del colpo» di LONDREGAN e POOLE (1990), «Poverty, the Coup Trap,
 * and the Seizure of Executive Power», World Politics 42(2). Sul dato di oggi:
 * fra i regimi parziali del 2000-2025, un colpo riuscito nei dieci anni prima
 * moltiplica per 3,1 quello dell'anno dopo, a parita' di stabilita' politica
 * (38,7 ogni mille anni-paese contro 10,4; docs/37). Il modello non lo sapeva:
 * dava all'Iraq e al Pakistan, che non hanno colpi dal 2003 e dal 1999, piu'
 * rischio che al Mali e al Niger, che ne hanno avuti tre e due dal 2020.
 *
 * Fonte: Powell e Thyne, «Global Instances of Coups from 1950 to Present»,
 * Journal of Peace Research 48(2), 2011, versione del 29/08/2026,
 * storage/fonti/powell-thyne-colpi-20260829.csv (coup = 2 e' riuscito).
 *
 *   php bin/importa_colpi.php
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$file = $radice . '/storage/fonti/powell-thyne-colpi-20260829.csv';
if (!is_file($file)) {
    fwrite(STDERR, "Manca $file: https://www.uky.edu/~clthyn2/coup_data/powell_thyne_coups_final.txt\n");
    exit(1);
}

/** Nomi di Powell e Thyne che non coincidono col nome inglese del nostro seme. */
const NOMI = [
    'ivory coast' => 'CIV', "cote d'ivoire" => 'CIV', 'democratic republic of the congo' => 'COD',
    'republic of congo' => 'COG', 'guinea-bissau' => 'GNB', 'guinea bissau' => 'GNB', 'the gambia' => 'GMB',
    'gambia' => 'GMB', 'burma' => 'MMR', 'myanmar' => 'MMR', 'central african republic' => 'CAF',
    'burkina faso' => 'BFA', 'sao tome and principe' => 'STP', 'egypt' => 'EGY', 'sudan' => 'SDN',
    'zimbabwe' => 'ZWE', 'bangladesh' => 'BGD', 'madagascar' => 'MDG', 'thailand' => 'THA',
    'congo' => 'COG', 'swaziland' => 'SWZ', 'dominican republic' => 'DOM',
    // Stati che non esistono piu': i loro colpi sono comunque anteriori al 2000.
    'yemen arab republic; n. yemen' => 'YEM', "yemen people's republic; s. yemen" => 'YEM',
    'republic of vietnam' => 'VNM',
];

$perNome = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $r);
    $perNome[mb_strtolower(trim((string) $d['nome_fonte']))] = (string) $d['iso3'];
}
fclose($fh);

$ultimo = [];   // iso => [anno, mese]
$senza = [];
$f = fopen($file, 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    if ((int) $d['coup'] !== 2) {
        continue;
    }
    $nome = mb_strtolower(trim((string) $d['country']));
    $iso = NOMI[$nome] ?? $perNome[$nome] ?? null;
    if ($iso === null) {
        $senza[$nome] = true;
        continue;
    }
    $quando = [(int) $d['year'], max(1, (int) $d['month'])];
    if (!isset($ultimo[$iso]) || $quando > $ultimo[$iso]) {
        $ultimo[$iso] = $quando;
    }
}
fclose($f);

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$righe = [];
foreach ($ultimo as $iso => [$anno, $mese]) {
    if (isset($mondo->nazioni[$iso]) && $anno >= 2000) {
        $righe[$iso] = sprintf("    '%s' => [%d, %d],", $iso, $anno, $mese);
    }
}
ksort($righe);
$testa = <<<PHP
<?php

// Generato da bin/importa_colpi.php — non modificare a mano.
//
// L'ultimo colpo di Stato riuscito di ogni paese dal 2000, [anno, mese].
// Powell e Thyne, versione del 29/08/2026. Chi non c'e' non ne ha avuti.

return [

PHP;
file_put_contents($radice . '/db/seed/colpi.php', $testa . implode("\n", $righe) . "\n];\n");
printf("%d paesi con un colpo riuscito dal 2000; nomi della fonte senza paese nel seme: %s\n",
    count($righe), $senza === [] ? 'nessuno' : implode(', ', array_keys($senza)));
