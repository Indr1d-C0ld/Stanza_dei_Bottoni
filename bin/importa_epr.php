<?php

declare(strict_types=1);

/**
 * Importa Ethnic Power Relations e produce db/seed/esclusione.php.
 *
 * PERCHE' ESISTE. Il modello aveva la disuguaglianza VERTICALE — il Gini fra
 * individui — e la letteratura e' netta sul fatto che per l'insorgenza di
 * guerra civile non e' un buon predittore. Quella che conta e' la
 * disuguaglianza ORIZZONTALE: fra GRUPPI, e in particolare fra chi sta al
 * potere e chi ne e' escluso.
 *
 *   CEDERMAN, WIMMER, MIN (2010), «Why Do Ethnic Groups Rebel?», World
 *   Politics 62(1); CEDERMAN, WEIDMANN, GLEDITSCH (2011), «Horizontal
 *   Inequalities and Ethnonationalist Civil War», APSR 105(3).
 *
 * La loro tesi va contro il consenso costruito da Fearon & Laitin e da Collier
 * & Hoeffler, per cui contano le OPPORTUNITA' (poverta', terreno, popolazione)
 * e non i MOTIVI. Cederman e colleghi mostrano che il consenso reggeva perche'
 * si era misurata la disuguaglianza sbagliata: fra individui invece che fra
 * gruppi politicamente rilevanti.
 *
 * Il nostro motore aveva gia' le opportunita'. Questo aggiunge i motivi.
 *
 * La Siria basta come prova che il dato dice qualcosa: arabi sunniti 65% senza
 * potere, alawiti 13% dominanti, curdi 8% auto-esclusi. Non serve altro per
 * capire quella guerra.
 *
 * Fonte: EPR Core 2021, Chair of International Conflict Research, ETH Zurigo.
 * Copre tutti gli Stati sopra i 250.000 abitanti dal 1946 al 2021.
 *
 *   php bin/importa_epr.php [--csv=percorso]
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

/** Gli stati di accesso al potere che EPR considera ESCLUSIONE. */
const ESCLUSI = ['POWERLESS', 'DISCRIMINATED', 'SELF-EXCLUSION'];

/** Nomi EPR che non coincidono col nome inglese del nostro seme. */
const NOMI = [
    'United States of America' => 'USA', 'Russia (Soviet Union)' => 'RUS',
    'Congo, Democratic Republic of (Zaire)' => 'COD', 'Congo' => 'COG',
    'Myanmar (Burma)' => 'MMR', 'Cambodia (Kampuchea)' => 'KHM',
    'Sri Lanka (Ceylon)' => 'LKA', 'Zimbabwe (Rhodesia)' => 'ZWE',
    'Burkina Faso (Upper Volta)' => 'BFA', 'Tanzania (Tanganyika)' => 'TZA',
    'Madagascar (Malagasy)' => 'MDG', 'Cote d\'Ivoire' => 'CIV',
    'Ivory Coast' => 'CIV', 'Bosnia-Herzegovina' => 'BIH',
    'Macedonia (Former Yugoslav Republic of)' => 'MKD',
    'Yemen (Arab Republic of Yemen)' => 'YEM', 'Iran (Persia)' => 'IRN',
    'Turkey (Ottoman Empire)' => 'TUR', 'Italy/Sardinia' => 'ITA',
    'German Federal Republic' => 'DEU', 'Germany' => 'DEU',
    'Kyrgyzstan' => 'KGZ', 'Vietnam (Annam/Cochin China/Tonkin)' => 'VNM',
    'Vietnam, Democratic Republic of' => 'VNM', 'Korea, Republic of' => 'KOR',
    "Korea, People's Republic of" => 'PRK', 'Belarus (Byelorussia)' => 'BLR',
    'Czech Republic' => 'CZE', 'Cape Verde' => 'CPV', 'East Timor' => 'TLS',
    'Gambia' => 'GMB', 'Swaziland (Eswatini)' => 'SWZ',
    'Surinam' => 'SUR', 'Bahamas' => 'BHS', 'Dominican Republic' => 'DOM',
    'Central African Republic' => 'CAF', 'Solomon Islands' => 'SLB',
    'United Arab Emirates' => 'ARE', 'Trinidad and Tobago' => 'TTO',
    'Papua New Guinea' => 'PNG', 'Equatorial Guinea' => 'GNQ',
    'Macedonia (FYROM/North Macedonia)' => 'MKD', 'Rumania' => 'ROU',
    "Cote D'Ivoire" => 'CIV', 'Kyrgyz Republic' => 'KGZ',
];

$opz = getopt('', ['csv::']);
$csv = (string) ($opz['csv'] ?? '');

if ($csv === '') {
    echo "Scarico da icr.ethz.ch...\n";
    $csv = sys_get_temp_dir() . '/EPR-2021.csv';
    $dati = @file_get_contents('https://icr.ethz.ch/data/epr/core/EPR-2021.csv');
    if ($dati === false || strlen($dati) < 10000) {
        fwrite(STDERR, "Scaricamento fallito. Usa --csv=<file>.\n");
        exit(1);
    }
    file_put_contents($csv, $dati);
}

$f = fopen($csv, 'r');
if ($f === false) {
    fwrite(STDERR, "Non riesco ad aprire $csv\n");
    exit(1);
}
$intestazione = fgetcsv($f);
$col = array_flip(array_map('trim', (array) $intestazione));
foreach (['statename', 'to', 'group', 'size', 'status'] as $x) {
    if (!isset($col[$x])) {
        fwrite(STDERR, "Colonna mancante nel CSV: $x\n");
        exit(1);
    }
}

// Ogni gruppo compare una volta per periodo: si tiene l'ultimo.
/** @var array<string, array<string, array{0:int,1:float,2:string}>> $perPaese */
$perPaese = [];
$ultimoAnno = 0;
while (($r = fgetcsv($f)) !== false) {
    $paese  = trim((string) ($r[$col['statename']] ?? ''));
    $gruppo = trim((string) ($r[$col['group']] ?? ''));
    $a      = (int) ($r[$col['to']] ?? 0);
    $quota  = (float) ($r[$col['size']] ?? 0);
    $stato  = strtoupper(trim((string) ($r[$col['status']] ?? '')));
    if ($paese === '' || $gruppo === '' || $a === 0) {
        continue;
    }
    $ultimoAnno = max($ultimoAnno, $a);
    if (!isset($perPaese[$paese][$gruppo]) || $a > $perPaese[$paese][$gruppo][0]) {
        $perPaese[$paese][$gruppo] = [$a, $quota, $stato];
    }
}
fclose($f);

// --- aggancio col nostro seme ------------------------------------------------
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$perNome = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $r);
    $perNome[mb_strtolower(trim((string) $d['nome_fonte']))] = $d['iso3'];
}
fclose($fh);

$iso = static function (string $nome) use ($perNome): ?string {
    if (isset(NOMI[$nome])) {
        return NOMI[$nome];
    }

    return $perNome[mb_strtolower($nome)] ?? null;
};

$righe = [];
$agganciati = 0;
$saltati = [];
foreach ($perPaese as $paese => $gruppi) {
    $i = $iso($paese);
    if ($i === null || !isset($mondo->nazioni[$i])) {
        $saltati[] = $paese;
        continue;
    }
    // Si guarda solo la fotografia piu' recente di quel paese: i gruppi
    // scomparsi dai dati anni fa non contano piu'.
    $anno = max(array_column($gruppi, 0));
    $esclusa = 0.0;
    $quanti  = 0;
    $alPotere = 0.0;
    foreach ($gruppi as [$a, $quota, $stato]) {
        if ($a < $anno) {
            continue;
        }
        if (in_array($stato, ESCLUSI, true)) {
            $esclusa += $quota;
            $quanti++;
        } elseif ($stato !== 'IRRELEVANT' && $stato !== 'STATE COLLAPSE') {
            $alPotere += $quota;
        }
    }
    $righe[$i] = [
        'esclusa'  => round(min(1.0, $esclusa), 3),
        'gruppi'   => $quanti,
        'al_potere' => round(min(1.0, $alPotere), 3),
    ];
    $agganciati++;
}

// I paesi che EPR non copre — sotto i 250.000 abitanti, quasi tutti — non hanno
// gruppi politicamente rilevanti codificati. Si dichiarano a zero, che per un
// micro-Stato omogeneo e' anche la risposta giusta.
$senzaDato = [];
foreach ($mondo->elenco() as $n) {
    if (!isset($righe[$n->iso3])) {
        $righe[$n->iso3] = ['esclusa' => 0.0, 'gruppi' => 0, 'al_potere' => 1.0];
        $senzaDato[] = $n->iso3;
    }
}
ksort($righe);

$out = "<?php\n\ndeclare(strict_types=1);\n\n"
     . "/**\n"
     . " * Esclusione etnica dal potere, per paese.\n"
     . " *\n"
     . " * GENERATO DA bin/importa_epr.php — non si modifica a mano.\n"
     . " *\n"
     . " *   esclusa    quota di popolazione in gruppi SENZA accesso al potere\n"
     . " *              (powerless, discriminati, auto-esclusi)\n"
     . " *   gruppi     quanti sono quei gruppi\n"
     . " *   al_potere  quota di popolazione in gruppi che il potere ce l'hanno\n"
     . " *\n"
     . " * Fonte: Ethnic Power Relations (EPR) Core $ultimoAnno, Chair of International\n"
     . " * Conflict Research, ETH Zurigo. Copre gli Stati sopra i 250.000 abitanti.\n"
     . " *\n"
     . " * Coperti $agganciati paesi con dati veri; " . count($senzaDato) . " senza — quasi tutti\n"
     . " * micro-Stati che EPR non segue, dichiarati a zero perche' omogenei.\n"
     . " *\n"
     . " * Serve alla disuguaglianza ORIZZONTALE di Cederman, Wimmer e Min: quella\n"
     . " * che predice la guerra civile, mentre il Gini fra individui non la predice.\n"
     . " */\n\nreturn [\n";
foreach ($righe as $i => $v) {
    $out .= sprintf("    '%s' => ['esclusa' => %.3f, 'gruppi' => %d, 'al_potere' => %.3f],\n",
        $i, $v['esclusa'], $v['gruppi'], $v['al_potere']);
}
$out .= "];\n";

file_put_contents($radice . '/db/seed/esclusione.php', $out);

printf("Scritte %d nazioni in db/seed/esclusione.php (EPR %d)\n", count($righe), $ultimoAnno);
printf("  con dati veri: %d · senza (dichiarati a zero): %d\n", $agganciati, count($senzaDato));
if ($saltati !== []) {
    printf("  paesi EPR non agganciati: %d — %s%s\n", count($saltati),
        implode(', ', array_slice($saltati, 0, 12)), count($saltati) > 12 ? '...' : '');
}
