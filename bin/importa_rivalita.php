<?php

declare(strict_types=1);

/**
 * Importa le rivalita' fra Stati e produce db/seed/rivalita.php.
 *
 * PERCHE' ESISTE. Le guerre fra Stati nascevano da una lotteria: ogni volta che
 * una nazione agiva pescava fra le cinque coppie col rapporto piu' intenso, e
 * le rivalita' vere — Cina e Taiwan, Russia e Ucraina — stavano fra il sesto e
 * l'undicesimo posto. In sei mondi da quindici anni ne usciva un'invasione,
 * contro le quattro-sei del 2010-2025 (docs/30, docs/31).
 *
 * Le guerre fra Stati nascono quasi tutte dentro rivalita' durature:
 *
 *   KLEIN, GOERTZ, DIEHL (2006), «The New Rivalry Dataset: Procedures and
 *   Patterns», Journal of Peace Research 43(3) — una coppia e' rivale se ha
 *   dispute militarizzate ripetute; con sei o piu' in vent'anni la rivalita' e'
 *   «duratura»;
 *   DIEHL, GOERTZ (2000), «War and Peace in International Rivalry».
 *
 * La finestra e' il 2006-2025, i vent'anni prima della divergenza:
 *
 *   - CORRELATES OF WAR, Dyadic Militarized Interstate Disputes v4.03
 *     (1816-2014): ogni disputa conta una volta per coppia, nell'anno in cui
 *     comincia;
 *   - UCDP/PRIO Armed Conflict Dataset v26.1 (1946-2025): per il 2015-2025, che
 *     le dispute COW non coprono, ogni anno di conflitto armato fra due governi
 *     (oltre i 25 morti) conta come una disputa — e' un uso della forza, cioe'
 *     il gradino piu' alto di una disputa. E anche un anno di conflitto interno
 *     in cui un altro Stato combatte coi ribelli con le sue truppe: l'Armenia
 *     nel Karabakh, il Ruanda con l'M23.
 *
 * E dalle stesse dispute il tasso: fra il 1946 e il 2014, in una coppia con
 * almeno tre dispute nei vent'anni prima una guerra (oltre mille morti)
 * comincia nello 0,53% degli anni; con almeno sei nell'1,26%. L'importatore
 * lo ricalcola e lo scrive in testa al file.
 *
 *   php bin/importa_rivalita.php
 *
 * Legge storage/fonti/mid/, storage/fonti/ucdp/ e storage/fonti/cow-codici.csv;
 * ciascuno dice da dove si scarica.
 */

$radice = require __DIR__ . '/_avvio.php';

$fonti = $radice . '/storage/fonti';
$fileMid  = $fonti . '/mid/dyadic_mid_4.03_update/dyadic_mid_4.03.csv';
$fileAcd  = $fonti . '/ucdp/UcdpPrioConflict_v26_1.csv';
$fileCow  = $fonti . '/cow-codici.csv';
foreach ([
    $fileMid => 'https://correlatesofwar.org/wp-content/uploads/dyadic_mid_4.03_update.zip',
    $fileAcd => 'https://ucdp.uu.se/downloads/ucdpprio/ucdp-prio-acd-261-csv.zip',
    $fileCow => 'https://correlatesofwar.org/wp-content/uploads/COW-country-codes.csv (ritorni a capo da convertire)',
] as $file => $dove) {
    if (!is_file($file)) {
        fwrite(STDERR, "Manca $file.\nSi scarica da $dove\n");
        exit(1);
    }
}

const DA = 2006;
const A  = 2025;

/** Nomi COW che non coincidono col nome inglese del nostro seme (come bin/importa_alleanze.php). */
const NOMI = [
    'United Arab Emirates' => 'ARE', 'United States of America' => 'USA',
    'Bahamas' => 'BHS', 'Dominican Republic' => 'DOM', 'St. Lucia' => 'LCA',
    'St. Vincent and the Grenadines' => 'VCT', 'Antigua & Barbuda' => 'ATG',
    'St. Kitts and Nevis' => 'KNA', 'German Federal Republic' => 'DEU', 'Germany' => 'DEU',
    'Czech Republic' => 'CZE', 'Cape Verde' => 'CPV', 'Gambia' => 'GMB',
    'Ivory Coast' => 'CIV', 'Swaziland' => 'SWZ', 'Yugoslavia' => 'SRB',
    'Central African Republic' => 'CAF', 'Congo' => 'COG',
    'Democratic Republic of the Congo' => 'COD', 'Taiwan' => 'TWN', 'Kosovo' => 'XKX',
    'Yemen' => 'YEM', 'Yemen Arab Republic' => 'YEM', 'East Timor' => 'TLS', 'Myanmar' => 'MMR',
];
/** Dove i codici Gleditsch-Ward di UCDP differiscono da quelli COW. */
const GW_IN_COW = [678 => 679, 340 => 345, 260 => 255, 817 => 816];

$nomeDi = [];
foreach (file($fileCow, FILE_IGNORE_NEW_LINES) ?: [] as $r) {
    $p = str_getcsv($r, ',', '"', '\\');
    if (count($p) >= 3 && ctype_digit(trim((string) $p[1]))) {
        $nomeDi[(int) $p[1]] = trim((string) $p[2]);
    }
}
$perNome = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $r);
    $perNome[mb_strtolower(trim((string) $d['nome_fonte']))] = (string) $d['iso3'];
}
fclose($fh);
$iso = static function (int $ccode) use ($nomeDi, $perNome): ?string {
    $nome = $nomeDi[$ccode] ?? '';
    return $nome === '' ? null : (NOMI[$nome] ?? $perNome[mb_strtolower($nome)] ?? null);
};
$coppia = static function (string $a, string $b): string {
    return strcmp($a, $b) < 0 ? "$a|$b" : "$b|$a";
};

// --- le dispute COW, una per coppia, nell'anno in cui cominciano -------------
$f = fopen($fileMid, 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
$dispute = [];   // disno|a|b => [anno, guerra]
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    $a = (int) $d['statea'];
    $b = (int) $d['stateb'];
    [$a, $b] = $a < $b ? [$a, $b] : [$b, $a];
    $k = $d['disno'] . "|$a|$b";
    $anno = (int) $d['strtyr'];
    $guerra = (int) $d['war'] === 1 || (int) $d['hihost'] === 5;
    $dispute[$k] = isset($dispute[$k])
        ? [min($dispute[$k][0], $anno), $dispute[$k][1] || $guerra]
        : [$anno, $guerra];
}
fclose($f);

$perCoppiaCow = [];   // "a|b" (codici COW) => list<[anno, guerra]>
foreach ($dispute as $k => $v) {
    [, $a, $b] = explode('|', $k);
    $perCoppiaCow["$a|$b"][] = $v;
}

// --- il tasso di guerra fra rivali, 1946-2014 --------------------------------
$tasso = static function (int $soglia) use ($perCoppiaCow): array {
    $anni = 0;
    $guerre = 0;
    for ($t = 1946; $t <= 2014; $t++) {
        foreach ($perCoppiaCow as $v) {
            $prima = 0;
            $ora = 0;
            foreach ($v as [$anno, $guerra]) {
                if ($anno >= $t - 20 && $anno < $t) {
                    $prima++;
                } elseif ($anno === $t && $guerra) {
                    $ora++;
                }
            }
            if ($prima >= $soglia) {
                $anni++;
                $guerre += $ora;
            }
        }
    }
    return [$anni, $guerre, $anni > 0 ? $guerre / $anni : 0.0];
};
[$anniR, $guerreR, $tassoR] = $tasso(3);
[$anniD, $guerreD, $tassoD] = $tasso(6);

// --- la finestra 2006-2025 ----------------------------------------------------
$conta = [];   // "ISO|ISO" => [dispute, ultimo anno]
$segna = static function (string $k, int $anno) use (&$conta): void {
    $conta[$k] = [($conta[$k][0] ?? 0) + 1, max($conta[$k][1] ?? 0, $anno)];
};
$senzaNome = [];
foreach ($perCoppiaCow as $k => $v) {
    [$a, $b] = array_map('intval', explode('|', $k));
    foreach ($v as [$anno]) {
        if ($anno < DA || $anno > 2014) {
            continue;
        }
        $ia = $iso($a);
        $ib = $iso($b);
        if ($ia === null || $ib === null) {
            $senzaNome[$ia === null ? $a : $b] = $nomeDi[$ia === null ? $a : $b] ?? '?';
            continue;
        }
        $segna($coppia($ia, $ib), $anno);
    }
}
$f = fopen($fileAcd, 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    $anno = (int) $d['year'];
    $tipo = (int) $d['type_of_conflict'];
    if (!in_array($tipo, [2, 4], true) || $anno < 2015 || $anno > A) {
        continue;
    }
    $lato = static fn(string $s): array => array_values(array_filter(array_map(
        static fn(string $x): int => GW_IN_COW[(int) trim($x)] ?? (int) trim($x), explode(',', $s))));
    // Tipo 2: due governi. Tipo 4: un governo contro ribelli, e conta solo se
    // un altro Stato combatte coi ribelli con le sue truppe (side_b_2nd): e'
    // cosi' che UCDP registra l'Armenia nel Karabakh e il Ruanda con l'M23.
    $contro = $tipo === 2 ? (string) $d['gwno_b'] : (string) $d['gwno_b_2nd'];
    foreach ($lato((string) $d['gwno_a']) as $a) {
        foreach ($lato($contro) as $b) {
            $ia = $iso($a);
            $ib = $iso($b);
            if ($ia !== null && $ib !== null && $ia !== $ib) {
                $segna($coppia($ia, $ib), $anno);
            }
        }
    }
}
fclose($f);

$rivali = array_filter($conta, static fn(array $v): bool => $v[0] >= 3);
uasort($rivali, static fn(array $x, array $y): int => $y[0] <=> $x[0]);

$righe = [];
foreach ($rivali as $k => [$n, $ultimo]) {
    $righe[] = sprintf("    '%s' => ['dispute' => %d, 'ultima' => %d],", $k, $n, $ultimo);
}
$testa = <<<PHP
<?php

// Generato da bin/importa_rivalita.php — non modificare a mano.
//
// Le coppie di Stati con almeno tre dispute militarizzate nel %d-%d:
// Correlates of War, Dyadic MID v4.03, fino al 2014; UCDP/PRIO Armed Conflict
// Dataset v26.1, conflitti fra governi, dal 2015 (un anno di conflitto = una
// disputa). Con sei o piu' la rivalita' e' «duratura» (Klein, Goertz, Diehl).
//
// Il tasso di guerra misurato sulle stesse dispute, 1946-2014: con almeno tre
// dispute nei vent'anni prima, %d guerre in %d anni-coppia (%.2f%%); con almeno
// sei, %d in %d (%.2f%%).

return [

PHP;
file_put_contents($radice . '/db/seed/rivalita.php',
    sprintf($testa, DA, A, $guerreR, $anniR, $tassoR * 100, $guerreD, $anniD, $tassoD * 100)
    . implode("\n", $righe) . "\n];\n");

printf("%d rivalita' (%d durature). Tasso di guerra: %.2f%% e %.2f%%. Codici senza nome nel seme: %s\n",
    count($rivali), count(array_filter($rivali, static fn(array $v): bool => $v[0] >= 6)),
    $tassoR * 100, $tassoD * 100, implode(', ', array_unique($senzaNome)) ?: 'nessuno');
