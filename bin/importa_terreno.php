<?php

declare(strict_types=1);

/**
 * Importa il terreno e il petrolio, i due predittori «di opportunita'» delle
 * guerre civili che il seme non aveva, e produce db/seed/terreno.php.
 *
 * PERCHE' ESISTE. L'innesco delle insurrezioni cresceva col rapporto fra il
 * reclutamento possibile e la forza del governo, fino a un tetto. Nei paesi
 * piccoli e poveri l'esercito pesa pochissimo, e il rapporto esplodeva: sedici
 * paesi stavano al tetto del 10% l'anno, compreso il Ruanda, mentre l'India ne
 * aveva il 2,7%. La letteratura dice il contrario — la popolazione grande e'
 * fra i predittori piu' forti — e dice che conta il terreno, che noi non
 * avevamo (docs/30).
 *
 * IL TERRENO. Fearon e Laitin usano la quota di territorio montuoso secondo il
 * geografo John Gerrard. Qui si usa la quota di territorio «molto accidentato»
 * (rugged_pc) di
 *
 *   NUNN, PUGA (2012), «Ruggedness: The Blessing of Bad Geography in Africa»,
 *   Review of Economics and Statistics 94(1), dati su diegopuga.org/data/rugged
 *
 * che e' una misura diversa ma con la stessa distribuzione: mediana 11,8%,
 * quartili 0,8 e 33, contro il 9%, 1,7 e 27,2 di Gerrard nel campione di
 * Fearon (2010, tabella 3). Il Sud Sudan prende il Sudan, la Serbia, il
 * Montenegro e il Kosovo la vecchia Serbia e Montenegro: i dati sono del 2010.
 *
 * IL PETROLIO. Fearon e Laitin (2003) chiamano «esportatore di petrolio» chi
 * ricava dai combustibili piu' di un terzo delle esportazioni. Si legge cosi'
 * com'e' dalla Banca Mondiale, World Development Indicators, TX.VAL.FUEL.ZS.UN
 * («Fuel exports, % of merchandise exports»), media del 2019-2024 (API
 * aggiornata l'08/10/2026). Dove la Banca Mondiale non ha il dato (Venezuela,
 * Guinea Equatoriale, Sud Sudan...) si ripiega sul Factbook: «produttore» se il
 * primo prodotto esportato e' greggio o gas.
 *
 *   php bin/importa_terreno.php [--csv=storage/fonti/rugged/rugged_data.csv]
 */

$radice = require __DIR__ . '/_avvio.php';

$opz = getopt('', ['csv::']);
$csv = (string) ($opz['csv'] ?? $radice . '/storage/fonti/rugged/rugged_data.csv');
$f = @fopen($csv, 'r');
if ($f === false) {
    fwrite(STDERR, "Non riesco ad aprire $csv.\nScarica e scompatta https://diegopuga.org/data/rugged/rugged_data.zip\n");
    exit(1);
}
$testa = fgetcsv($f, 0, ',', '"', '\\');
$asperita = [];
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($testa, $r);
    $asperita[(string) $d['isocode']] = (float) $d['rugged_pc'];
}
fclose($f);
$eredi = ['SSD' => 'SDN', 'SRB' => 'SCG', 'MNE' => 'SCG', 'XKX' => 'SCG'];

$petrolio = '/^\s*(crude petroleum|natural gas|petroleum gas|liquefied natural gas)\b/i';

// La quota dei combustibili sulle esportazioni di merci, Banca Mondiale.
$quotaCombustibili = [];
$wb = json_decode((string) @file_get_contents($radice . '/storage/fonti/wb-fuel-exports.json'), true);
if (!is_array($wb[1] ?? null)) {
    fwrite(STDERR, "Manca storage/fonti/wb-fuel-exports.json: https://api.worldbank.org/v2/country/all/"
        . "indicator/TX.VAL.FUEL.ZS.UN?format=json&date=2015:2024&per_page=20000\n");
    exit(1);
}
$somme = [];
foreach ($wb[1] as $r) {
    if ($r['value'] !== null && (int) $r['date'] >= 2019) {
        $somme[(string) $r['countryiso3code']][] = (float) $r['value'];
    }
}
foreach ($somme as $iso => $v) {
    $quotaCombustibili[$iso] = array_sum($v) / count($v);
}
$daFactbook = [];

$esito = [];
$mancano = [];
$h = fopen($radice . '/db/seed/nazioni.csv', 'r');
$intestazione = fgetcsv($h, 0, ',', '"', '\\');
while (($r = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($intestazione, $r);
    $iso = (string) $d['iso3'];
    $fonte = $asperita[$iso] ?? $asperita[$eredi[$iso] ?? ''] ?? null;
    if ($fonte === null) {
        $mancano[] = $iso;
    }
    $file = glob($radice . '/storage/factbook/*/' . $d['gec'] . '.json')[0] ?? null;
    $merci = '';
    if ($file !== null) {
        $j = json_decode((string) file_get_contents($file), true);
        $merci = (string) ($j['Economy']['Exports - commodities']['text'] ?? '');
    }
    $esito[$iso] = [
        'montuoso' => $fonte,
        'petrolio' => isset($quotaCombustibili[$iso])
            ? $quotaCombustibili[$iso] >= 33.3
            : ($daFactbook[] = $iso) && (bool) preg_match($petrolio, $merci),
    ];
}
fclose($h);
ksort($esito);

$righe = [];
foreach ($esito as $iso => $v) {
    $righe[] = sprintf("    '%s' => ['montuoso' => %s, 'petrolio' => %s],", $iso,
        $v['montuoso'] === null ? 'null' : sprintf('%.2f', $v['montuoso']), $v['petrolio'] ? 'true' : 'false');
}
$quanti = count(array_filter($esito, static fn(array $v): bool => $v['petrolio']));
$intesta = <<<PHP
<?php

// Generato da bin/importa_terreno.php — non modificare a mano.
//
// montuoso: quota %% di territorio molto accidentato (Nunn e Puga 2012,
// rugged_pc), al posto della quota montuosa di Gerrard usata da Fearon e
// Laitin. petrolio: combustibili oltre un terzo delle esportazioni di merci,
// Banca Mondiale 2019-2024 (%d paesi); dove manca, primo prodotto esportato
// nel Factbook (%s). Senza terreno, e prendono la mediana: %s.

return [

PHP;
file_put_contents($radice . '/db/seed/terreno.php',
    sprintf($intesta, $quanti, implode(', ', $daFactbook), $mancano === [] ? 'nessuno' : implode(', ', $mancano)) . implode("\n", $righe) . "\n];\n");
printf("%d paesi, %d produttori di petrolio, senza terreno: %s\n", count($esito), $quanti, implode(' ', $mancano));
