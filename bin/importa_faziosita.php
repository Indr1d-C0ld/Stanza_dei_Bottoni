<?php

declare(strict_types=1);

/**
 * Importa quali paesi hanno una politica faziosa e produce db/seed/faziosita.php.
 *
 * PERCHE' ESISTE. La faziosita' e' il quarto ingrediente del modello PITF
 * (Goldstone et al. 2010), ma il motore la ricavava dal gabinetto, che esiste
 * solo per le quattordici potenze giocabili: per gli altri 175 paesi valeva
 * zero (docs/26 §13). E' invece una proprieta' del regime, e Polity la misura.
 *
 * Fonte: Polity5 (Marshall e Gurr, Center for Systemic Peace, 2020), versione
 * aggiornata al 2018, l'ultimo anno del dataset:
 *
 *   https://www.systemicpeace.org/inscr/p5v2018.xls
 *
 * convertito in storage/fonti/polity/p5v2018.csv. Si legge PARCOMP, la
 * competitivita' della partecipazione: il valore 3, «factional», e' quello che
 * Goldstone et al. chiamano faziosita' — competizione organizzata in blocchi
 * parrocchiali che si contendono lo Stato. Faziosita' 1 se l'ultimo anno
 * disponibile e' «factional», 0 altrimenti. I paesi che Polity non copre (sotto
 * i 500.000 abitanti) restano a zero.
 *
 *   php bin/importa_faziosita.php
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$file = $radice . '/storage/fonti/polity/p5v2018.csv';
if (!is_file($file)) {
    fwrite(STDERR, "Manca $file: si scarica p5v2018.xls (vedi l'intestazione) e si converte in CSV.\n");
    exit(1);
}

/** Nomi Polity che non coincidono col nome inglese del nostro seme. */
const NOMI = [
    'United States' => 'USA', 'UK' => 'GBR', 'Korea South' => 'KOR', 'Korea North' => 'PRK',
    'Congo Kinshasa' => 'COD', 'Congo Brazzaville' => 'COG', 'Ivory Coast' => 'CIV',
    "Cote D'Ivoire" => 'CIV', 'Myanmar (Burma)' => 'MMR', 'Russia' => 'RUS', 'Bosnia' => 'BIH',
    'Macedonia' => 'MKD', 'Timor Leste' => 'TLS', 'Gambia' => 'GMB', 'Cape Verde' => 'CPV',
    'Slovak Republic' => 'SVK', 'UAE' => 'ARE', 'Kyrgyzstan' => 'KGZ', 'Sudan-North' => 'SDN',
    'Swaziland' => 'SWZ', 'Czech Republic' => 'CZE', 'Vietnam' => 'VNM', 'Laos' => 'LAO',
    'Syria' => 'SYR', 'Iran' => 'IRN', 'Taiwan' => 'TWN', 'Serbia' => 'SRB', 'Kosovo' => 'XKX',
    'Tanzania' => 'TZA', 'Bolivia' => 'BOL', 'Venezuela' => 'VEN', 'Moldova' => 'MDA',
    'Belarus' => 'BLR', 'South Sudan' => 'SSD', 'East Timor' => 'TLS', 'Egypt' => 'EGY',
    'Yemen' => 'YEM', 'Central African Republic' => 'CAF', 'Dominican Republic' => 'DOM',
    'Solomon Islands' => 'SLB', 'Trinidad and Tobago' => 'TTO', 'Guinea-Bissau' => 'GNB',
    'Papua New Guinea' => 'PNG', 'Equatorial Guinea' => 'GNQ', 'Sri Lanka' => 'LKA',
    'Saudi Arabia' => 'SAU', 'Costa Rica' => 'CRI', 'El Salvador' => 'SLV', 'New Zealand' => 'NZL',
    'South Africa' => 'ZAF', 'Sierra Leone' => 'SLE', 'Burkina Faso' => 'BFA', 'Montenegro' => 'MNE',
];

$perNome = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $r);
    $perNome[mb_strtolower(trim((string) $d['nome_fonte']))] = (string) $d['iso3'];
}
fclose($fh);

$ultimo = [];   // iso => [anno, parcomp]
$f = fopen($file, 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    $nome = trim((string) $d['country']);
    $iso = NOMI[$nome] ?? $perNome[mb_strtolower($nome)] ?? null;
    $anno = (int) (float) $d['year'];
    $pc = $d['parcomp'] === '' ? null : (int) (float) $d['parcomp'];
    // -66, -77, -88: interruzione, interregno, transizione. Non dicono niente
    // sulla faziosita': si tiene l'ultimo anno con un valore vero.
    if ($iso === null || $pc === null || $pc < 0 || $anno < 2010) {
        continue;
    }
    if (!isset($ultimo[$iso]) || $anno > $ultimo[$iso][0]) {
        $ultimo[$iso] = [$anno, $pc];
    }
}
fclose($f);

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$righe = [];
$faziosi = [];
$mancano = [];
foreach ($mondo->elenco() as $n) {
    if (!isset($ultimo[$n->iso3])) {
        $mancano[] = $n->iso3;
        continue;
    }
    if ($ultimo[$n->iso3][1] === 3) {
        $faziosi[] = $n->iso3;
        $righe[] = sprintf("    '%s' => 1.0,   // %d", $n->iso3, $ultimo[$n->iso3][0]);
    }
}
sort($righe);
sort($mancano);
$testa = <<<PHP
<?php

// Generato da bin/importa_faziosita.php — non modificare a mano.
//
// Polity5 (Marshall e Gurr), PARCOMP = 3 «factional» nell'ultimo anno
// disponibile (l'anno a fianco). Gli altri paesi valgono zero. Polity non copre:
// %s.

return [

PHP;
file_put_contents($radice . '/db/seed/faziosita.php',
    sprintf($testa, wordwrap(implode(', ', $mancano), 70, "\n// ")) . implode("\n", $righe) . "\n];\n");
printf("%d paesi faziosi su %d coperti da Polity; senza dati: %s\n",
    count($faziosi), count($ultimo), implode(' ', $mancano));
