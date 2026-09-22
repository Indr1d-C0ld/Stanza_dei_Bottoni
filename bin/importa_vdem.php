<?php

declare(strict_types=1);

/**
 * Importa l'indice di democrazia liberale di V-Dem e produce
 * db/seed/democrazia.php.
 *
 * PERCHE' ESISTE. Il motore non aveva nessun asse democrazia-autocrazia.
 * `maturita` sembrava esserlo e non lo e': e' marcata [SEGNAPOSTO] e si
 * ricava da reddito e alfabetizzazione, quindi mette Singapore e l'Arabia
 * Saudita accanto alla Norvegia. `ideologia_formale` e' peggio: e' la
 * descrizione giuridica che ogni Stato da' di se stesso, e centoquarantasei
 * paesi su centottantanove si dichiarano democrazie liberali.
 *
 * Senza quell'asse il modello di Goldstone et al. (2010) non si puo'
 * implementare, perche' il suo predittore piu' forte e' proprio il TIPO DI
 * REGIME — e in particolare la democrazia PARZIALE, che sta in mezzo.
 *
 * La sostituzione era gia' scritta nel codice: «DA SOSTITUIRE con V-Dem».
 *
 * Fonte: V-Dem Institute (Universita' di Goteborg), Liberal Democracy Index,
 * via Our World in Data. Scala 0..1. Copre 202 paesi dal 1789.
 *
 *   php bin/importa_vdem.php [--csv=percorso]
 *
 * Senza --csv lo scarica da Our World in Data.
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$opz = getopt('', ['csv::']);
$csv = (string) ($opz['csv'] ?? '');
$fonte = 'https://ourworldindata.org/grapher/liberal-democracy-index.csv'
       . '?v=1&csvType=full&useColumnShortNames=true';

if ($csv === '') {
    echo "Scarico da Our World in Data...\n";
    $csv = sys_get_temp_dir() . '/vdem-libdem.csv';
    $dati = @file_get_contents($fonte);
    if ($dati === false || strlen($dati) < 1000) {
        fwrite(STDERR, "Scaricamento fallito. Usa --csv=<file> con una copia locale.\n");
        exit(1);
    }
    file_put_contents($csv, $dati);
}

$f = fopen($csv, 'r');
if ($f === false) {
    fwrite(STDERR, "Non riesco ad aprire $csv\n");
    exit(1);
}
fgetcsv($f);   // intestazione

/** @var array<string,array{0:int,1:float}> $ultimo */
$ultimo = [];
while (($r = fgetcsv($f)) !== false) {
    if (count($r) < 4) {
        continue;
    }
    [, $iso, $anno, $val] = $r;
    if ($iso === '' || $val === '') {
        continue;
    }
    if (!isset($ultimo[$iso]) || (int) $anno > $ultimo[$iso][0]) {
        $ultimo[$iso] = [(int) $anno, (float) $val];
    }
}
fclose($f);

/**
 * I micro-Stati che V-Dem non copre.
 *
 * Non si inventano e non si lasciano a un valore di comodo: vengono dai
 * punteggi «Freedom in the World» di Freedom House, convertiti sulla scala
 * di V-Dem con la relazione approssimata che le due misure mostrano dove si
 * sovrappongono (Norvegia FH 100 / V-Dem 0,85; Italia FH 90 / V-Dem 0,64):
 *
 *     vdem ~ (FH/100)^2 * 0,85
 *
 * E' una stima dichiarata, non una misura. Quasi tutte sono piccole
 * democrazie parlamentari del Commonwealth caraibico e del Pacifico; le
 * eccezioni vere sono il Brunei, monarchia assoluta, e il Kosovo.
 */
const FREEDOM_HOUSE = [
    'AND' => 93, 'ATG' => 85, 'BHS' => 91, 'BLZ' => 87, 'BRN' => 29,
    'DMA' => 93, 'FSM' => 92, 'GRD' => 89, 'KIR' => 93, 'KNA' => 89,
    'LCA' => 92, 'MHL' => 93, 'TON' => 79, 'VCT' => 91, 'WSM' => 82,
    'XKX' => 60,
];

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$righe = [];
$daVDem = 0;
$daFH   = 0;
$senza  = [];

foreach ($mondo->elenco() as $n) {
    if (isset($ultimo[$n->iso3])) {
        $righe[$n->iso3] = round($ultimo[$n->iso3][1], 3);
        $daVDem++;
    } elseif (isset(FREEDOM_HOUSE[$n->iso3])) {
        $righe[$n->iso3] = round((FREEDOM_HOUSE[$n->iso3] / 100.0) ** 2 * 0.85, 3);
        $daFH++;
    } else {
        // Ultima spiaggia: la mediana mondiale. Si dichiara, non si nasconde.
        $righe[$n->iso3] = 0.355;
        $senza[] = $n->iso3;
    }
}
ksort($righe);

$anno = max(array_column($ultimo, 0));
$out  = "<?php\n\ndeclare(strict_types=1);\n\n"
      . "/**\n"
      . " * Indice di democrazia liberale, 0 (autocrazia piena) .. 1 (democrazia piena).\n"
      . " *\n"
      . " * GENERATO DA bin/importa_vdem.php — non si modifica a mano.\n"
      . " *\n"
      . " * Fonte: V-Dem Institute (Universita' di Goteborg), Liberal Democracy Index,\n"
      . " * via Our World in Data. Ultimo anno disponibile: $anno.\n"
      . " *\n"
      . " * Copre $daVDem delle nostre nazioni. Per $daFH micro-Stati che V-Dem non\n"
      . " * segue il valore e' stimato dai punteggi Freedom House (vedi l'importatore:\n"
      . " * e' una stima dichiarata, non una misura).\n"
      . " *\n"
      . " * Serve al modello di Goldstone et al. (2010): il tipo di regime e' il suo\n"
      . " * predittore piu' forte, e la democrazia PARZIALE — quella in mezzo — e'\n"
      . " * quella che salta.\n"
      . " */\n\nreturn [\n";
foreach ($righe as $iso => $v) {
    $out .= sprintf("    '%s' => %.3f,\n", $iso, $v);
}
$out .= "];\n";

file_put_contents($radice . '/db/seed/democrazia.php', $out);

printf("Scritte %d nazioni in db/seed/democrazia.php (V-Dem %d · Freedom House %d%s)\n",
    count($righe), $daVDem, $daFH,
    $senza === [] ? '' : ' · senza fonte ' . implode(',', $senza));
