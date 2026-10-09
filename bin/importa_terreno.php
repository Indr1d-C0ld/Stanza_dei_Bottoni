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
 * IL PETROLIO. «Produttore di petrolio» se il primo prodotto esportato, nel
 * Factbook, e' greggio o gas naturale — non i raffinati, che metterebbero fra i
 * produttori i Paesi Bassi, il Belgio e le isole dei Caraibi. Fearon e Laitin (2003)
 * usano le esportazioni di combustibili oltre un terzo del totale; Fearon
 * (2010) le rendite naturali oltre un terzo del PIL. Il primo prodotto per
 * valore e' un'approssimazione dichiarata della prima definizione.
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

// Dove il primo prodotto inganna. L'Iran esporta petrolio per vie che le
// statistiche commerciali non vedono (le sanzioni): il Factbook mette prima la
// plastica. Gli Stati Uniti hanno il greggio in testa, ma i combustibili sono
// circa un sesto delle loro esportazioni di merci: sotto il terzo di Fearon e
// Laitin.
$correzioni = ['IRN' => true, 'USA' => false];

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
        'petrolio' => $correzioni[$iso] ?? (bool) preg_match($petrolio, $merci),
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
// Laitin. petrolio: il primo prodotto esportato nel Factbook e' greggio
// o gas (%d paesi). Senza dato, e prendono la mediana: %s.

return [

PHP;
file_put_contents($radice . '/db/seed/terreno.php',
    sprintf($intesta, $quanti, $mancano === [] ? 'nessuno' : implode(', ', $mancano)) . implode("\n", $righe) . "\n];\n");
printf("%d paesi, %d produttori di petrolio, senza terreno: %s\n", count($esito), $quanti, implode(' ', $mancano));
