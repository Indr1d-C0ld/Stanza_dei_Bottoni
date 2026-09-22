<?php

declare(strict_types=1);

/**
 * Importa l'indice di Gini della Banca Mondiale e produce
 * db/seed/disuguaglianza.php.
 *
 * PERCHE' ESISTE. Il modello non aveva nessuna misura della disuguaglianza, e
 * l'equazione della legittimita' di Crawford guarda il consumo PRO CAPITE —
 * cioe' la media. Ma la media non e' quel che sente il cittadino tipico: in
 * Sudafrica il cinquanta per cento piu' povero vive con poco piu' della meta'
 * di quel che la media promette, e un governo che festeggia la crescita mentre
 * la gente non la vede e' una delle storie piu' comuni del mondo vero.
 *
 * ATTENZIONE A COSA MISURA. Questo e' il Gini VERTICALE, fra individui. La
 * letteratura e' netta: per l'insorgenza di guerra civile NON e' un buon
 * predittore — Fearon & Laitin e Collier & Hoeffler lo trovano non
 * significativo, ed e' la disuguaglianza ORIZZONTALE fra gruppi etnici a
 * contare (Cederman, Weidmann, Gleditsch 2011, APSR 105(3)). Il nostro seme non
 * ha gruppi etnici, quindi la disuguaglianza qui NON tocca le guerre: tocca il
 * malcontento, che e' il canale per cui l'evidenza c'e'.
 *
 * Fonte: Banca Mondiale (PIP/WDI), via Our World in Data. Scala 0..1.
 *
 *   php bin/importa_gini.php [--csv=percorso]
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$opz = getopt('', ['csv::']);
$csv = (string) ($opz['csv'] ?? '');
$fonte = 'https://ourworldindata.org/grapher/economic-inequality-gini-index.csv'
       . '?v=1&csvType=full&useColumnShortNames=true';

if ($csv === '') {
    echo "Scarico da Our World in Data...\n";
    $csv = sys_get_temp_dir() . '/gini.csv';
    $dati = @file_get_contents($fonte);
    if ($dati === false || strlen($dati) < 1000) {
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
fgetcsv($f);

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

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');

// I paesi senza rilevazione prendono la mediana della PROPRIA regione: e' meno
// sbagliato della mediana mondiale, perche' la disuguaglianza e' un fatto
// regionale prima che nazionale — l'America Latina sta in alto, l'Europa in
// basso, e chi manca somiglia piu' ai vicini che al pianeta.
$perRegione = [];
foreach ($mondo->elenco() as $n) {
    if (isset($ultimo[$n->iso3])) {
        $perRegione[$n->regione][] = $ultimo[$n->iso3][1];
    }
}
$mediana = static function (array $v): float {
    if ($v === []) {
        return 0.352;   // mediana mondiale
    }
    sort($v);

    return $v[intdiv(count($v), 2)];
};

$righe = [];
$daDato = 0;
$daRegione = [];
foreach ($mondo->elenco() as $n) {
    if (isset($ultimo[$n->iso3])) {
        $righe[$n->iso3] = round($ultimo[$n->iso3][1], 3);
        $daDato++;
    } else {
        $righe[$n->iso3] = round($mediana($perRegione[$n->regione] ?? []), 3);
        $daRegione[] = $n->iso3;
    }
}
ksort($righe);

$anni = array_column(array_intersect_key($ultimo, $righe), 0);
sort($anni);
$annoMediano = $anni === [] ? 0 : $anni[intdiv(count($anni), 2)];

$out = "<?php\n\ndeclare(strict_types=1);\n\n"
     . "/**\n"
     . " * Indice di Gini, 0 (uguaglianza perfetta) .. 1 (un solo percettore).\n"
     . " *\n"
     . " * GENERATO DA bin/importa_gini.php — non si modifica a mano.\n"
     . " *\n"
     . " * Fonte: Banca Mondiale (PIP/WDI), via Our World in Data. Anno mediano\n"
     . " * delle rilevazioni: $annoMediano — le indagini sui redditi sono rade e non\n"
     . " * escono tutte lo stesso anno, quindi questi numeri NON sono sincroni.\n"
     . " *\n"
     . " * Coperti $daDato paesi con rilevazione vera; gli altri " . count($daRegione) . " prendono la\n"
     . " * mediana della propria regione, che e' meno sbagliata di quella mondiale.\n"
     . " *\n"
     . " * E una cautela sul confronto: alcune rilevazioni misurano il REDDITO e\n"
     . " * altre il CONSUMO, che da' numeri sistematicamente piu' bassi. L'India a\n"
     . " * 0,255 e' una misura sul consumo; sul reddito starebbe piu' in alto.\n"
     . " */\n\nreturn [\n";
foreach ($righe as $iso => $v) {
    $out .= sprintf("    '%s' => %.3f,\n", $iso, $v);
}
$out .= "];\n";

file_put_contents($radice . '/db/seed/disuguaglianza.php', $out);

printf("Scritte %d nazioni in db/seed/disuguaglianza.php (anno mediano %d)\n",
    count($righe), $annoMediano);
printf("  con rilevazione vera: %d · per mediana regionale: %d\n", $daDato, count($daRegione));
