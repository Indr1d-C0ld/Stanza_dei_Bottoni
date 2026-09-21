<?php

declare(strict_types=1);

/**
 * Estrae il grafo di contiguità terrestre dal World Factbook.
 *
 * Serve alla proiezione di forza: in Balance of Power una potenza può portare
 * tutto il suo peso contro un paese con cui confina, molto meno altrove. Senza
 * un grafo dei confini, "vicinato" e "sfera di influenza" sono parole vuote.
 *
 * Uso:  php bin/importa_confini.php [--verboso]
 */

$radice = require __DIR__ . '/_avvio.php';

$verboso  = in_array('--verboso', $argv, true);
$sorgente = $radice . '/storage/factbook';
$uscita   = $radice . '/db/seed/confini.csv';

// --- indice nome inglese -> ISO3, dal seme già prodotto ----------------------
$indice = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$intestazione = fgetcsv($fh, 0, ',', '"', '\\');
$righe = [];
while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $righe[] = array_combine($intestazione, $r);
}
fclose($fh);

$normalizza = static function (string $nome): string {
    $n = html_entity_decode($nome, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $n = preg_replace('/\([^)]*\)/', '', $n) ?? $n;          // via le parentesi
    $n = preg_replace('/[^a-z ]/', '', mb_strtolower($n)) ?? $n;
    return trim(preg_replace('/\s+/', ' ', $n) ?? $n);
};

foreach ($righe as $r) {
    $indice[$normalizza($r['nome_fonte'])] = $r['iso3'];
}

// Nomi che il Factbook usa nei confini ma non come forma breve propria.
$alias = [
    'burma' => 'MMR', 'myanmar' => 'MMR',
    'korea south' => 'KOR', 'south korea' => 'KOR',
    'korea north' => 'PRK', 'north korea' => 'PRK',
    'czech republic' => 'CZE', 'czechia' => 'CZE',
    'the gambia' => 'GMB', 'gambia the' => 'GMB',
    'bahamas the' => 'BHS', 'the bahamas' => 'BHS',
    'congo kinshasa' => 'COD', 'congo brazzaville' => 'COG',
    'democratic republic of the congo' => 'COD', 'republic of the congo' => 'COG',
    'drc' => 'COD', 'dr congo' => 'COD',
    'cote divoire' => 'CIV', 'ivory coast' => 'CIV',
    'timorleste' => 'TLS', 'east timor' => 'TLS',
    'russia' => 'RUS', 'turkey turkiye' => 'TUR', 'turkiye' => 'TUR',
    'macedonia' => 'MKD', 'north macedonia' => 'MKD',
    'swaziland' => 'SWZ', 'eswatini' => 'SWZ',
    'cabo verde' => 'CPV', 'cape verde' => 'CPV',
    'the dominican' => 'DOM', 'dominican republic' => 'DOM',
    'united arab emirates' => 'ARE', 'ae' => 'ARE',
    'central african republic' => 'CAF', 'ct' => 'CAF',
    'vatican city' => null, 'holy see' => null,          // microstati senza peso
    'monaco' => null, 'san marino' => null, 'liechtenstein' => null,
    'gibraltar' => null, 'hong kong' => null, 'macau' => null,
    'west bank' => null, 'gaza strip' => null, 'western sahara' => null,
    'french guiana' => 'FRA', 'greenland' => 'DNK',
    'us' => 'USA', 'uk' => 'GBR', 'uae' => 'ARE', 'netherlands' => 'NLD',
    'us naval base at guantanamo bay' => null,
];

$regioni = ['africa', 'australia-oceania', 'central-america-n-caribbean', 'central-asia',
            'east-n-southeast-asia', 'europe', 'middle-east', 'north-america',
            'south-america', 'south-asia'];

$confini = [];
$ignoti  = [];

foreach ($regioni as $cartella) {
    foreach (glob($sorgente . '/' . $cartella . '/*.json') ?: [] as $percorso) {
        $d = json_decode((string) file_get_contents($percorso), true);
        if (!is_array($d)) {
            continue;
        }
        $nomeProprio = $d['Government']['Country name']['conventional short form']['text'] ?? '';
        $mio = $indice[$normalizza($nomeProprio)] ?? null;
        if ($mio === null) {
            continue;   // non è uno Stato del nostro seme
        }

        $testo = $d['Geography']['Land boundaries']['border countries']['text'] ?? '';
        if ($testo === '') {
            continue;   // isola
        }

        foreach (explode(';', $testo) as $pezzo) {
            // "Austria 404 km" -> nome + chilometri
            if (!preg_match('/^\s*([^0-9]+?)\s+([\d.,]+)\s*km/i', $pezzo, $m)) {
                continue;
            }
            $chiave = $normalizza($m[1]);
            $km     = (int) round((float) str_replace(',', '', $m[2]));

            if (array_key_exists($chiave, $alias)) {
                $vicino = $alias[$chiave];
                if ($vicino === null) {
                    continue;   // microstato o territorio: niente riga
                }
            } else {
                $vicino = $indice[$chiave] ?? null;
            }

            if ($vicino === null) {
                $ignoti[$chiave] = ($ignoti[$chiave] ?? 0) + 1;
                continue;
            }
            if ($vicino === $mio) {
                continue;
            }

            // Una riga per coppia non ordinata, con la lunghezza maggiore fra
            // le due dichiarazioni (i due Stati non sempre concordano).
            $a = min($mio, $vicino);
            $b = max($mio, $vicino);
            $confini["$a|$b"] = max($confini["$a|$b"] ?? 0, $km);
        }
    }
}

ksort($confini);
$out = fopen($uscita, 'w');
fputcsv($out, ['iso3_a', 'iso3_b', 'km'], ',', '"', '\\');
foreach ($confini as $coppia => $km) {
    [$a, $b] = explode('|', $coppia);
    fputcsv($out, [$a, $b, $km], ',', '"', '\\');
}
fclose($out);

printf("Confini terrestri: %d coppie in %s\n", count($confini), str_replace($radice . '/', '', $uscita));
if ($ignoti !== []) {
    arsort($ignoti);
    printf("Nomi non riconosciuti (%d): %s\n", count($ignoti),
        implode(', ', array_slice(array_keys($ignoti), 0, $verboso ? 50 : 12)));
}
