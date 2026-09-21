<?php

declare(strict_types=1);

/**
 * Trasforma i confini di Natural Earth in tracciati SVG, uno per nazione.
 *
 * Il seme del gioco non porta coordinate: porta regioni e confini, che bastano
 * al modello ma non a disegnare niente. Questo programma colma quella lacuna
 * una volta sola, a tavolino, e scrive un file di tracciati gia' proiettati e
 * gia' arrotondati. A runtime non si fa nessun calcolo geografico: si colora e
 * basta.
 *
 * I dati vengono da Natural Earth (naturalearthdata.com), che e' di **dominio
 * pubblico**. La risoluzione a 50 milionesimi e non a 110: quella grossolana
 * perde ventitre' paesi, fra cui Singapore, che nel modello commerciale e' una
 * piazza finanziaria di primo piano — sparire dalla mappa perche' si e' piccoli
 * non va bene in un gioco dove i piccoli contano.
 *
 * La proiezione e' Robinson: e' un compromesso senza pretese di esattezza —
 * non conserva ne' le aree ne' gli angoli — ma e' quella che «somiglia a un
 * planisfero» e non gonfia la Groenlandia come Mercatore. In un gioco dove il
 * peso dei paesi si legge dai numeri e non dalla mappa, la leggibilita' vale
 * piu' della correttezza metrica.
 *
 *   php bin/costruisci_mappa.php                  scarica la fonte e costruisce
 *   php bin/costruisci_mappa.php <file.geojson>   usa un file gia' scaricato
 *
 * Il GeoJSON di partenza pesa tre megabyte e non sta in git: quel che sta in
 * git e' il risultato, centotrenta kilobyte di tracciati. Chi vuole
 * ricostruirlo lancia il programma senza argomenti e se lo riscarica.
 */

require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

const FONTE = 'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/'
    . 'master/geojson/ne_50m_admin_0_countries.geojson';

$sorgente = $argv[1] ?? null;
if ($sorgente === null) {
    $sorgente = sys_get_temp_dir() . '/ne_50m_admin_0_countries.geojson';
    if (!is_file($sorgente) || filesize($sorgente) < 1_000_000) {
        printf("  scarico i confini da Natural Earth…\n");
        $dati = @file_get_contents(FONTE);
        if ($dati === false || strlen($dati) < 1_000_000) {
            fwrite(STDERR, "  non riesco a scaricare la fonte. Scaricala a mano da:\n    "
                . FONTE . "\n  e passala come argomento.\n");
            exit(1);
        }
        file_put_contents($sorgente, $dati);
        printf("  scaricati %s KB\n", number_format(strlen($dati) / 1024, 0));
    } else {
        printf("  uso la copia gia' scaricata in %s\n", $sorgente);
    }
}
if (!is_file($sorgente)) {
    fwrite(STDERR, "File non trovato: $sorgente\n");
    exit(1);
}

$radice = dirname(__DIR__);
$LARGO  = 1000.0;   // larghezza del disegno, in unita' SVG
$PRECISIONE = 1;    // decimali tenuti: oltre, si accumulano byte e non dettaglio

// Quanto si puo' sgrossare un contorno prima che si veda. Una unita' SVG qui
// e' un trecentosessantesimo di mondo, circa quaranta chilometri all'equatore.
//
// La tolleranza e' PROPORZIONATA alla taglia del paese, e non per eleganza:
// con un valore fisso che va bene per la Russia, Cipro e il Lussemburgo si
// riducevano a un punto. Un paese piccolo ha bisogno di una mano leggera —
// e costa poco, perche' e' piccolo.
$TOLLERANZA = 0.6;
$TOLLERANZA_MINIMA = 0.05;

// Le isole sotto questa dimensione non si vedono comunque, ma si portano
// dietro migliaia di punti. Si buttano — tranne quando sono tutto quel che un
// paese ha, perche' un microstato che sparisce dalla mappa e' un errore.
$ISOLA_MINIMA = 1.2;

/**
 * La tabella di Robinson: per ogni cinque gradi di latitudine, quanto si
 * stringe il parallelo e a che altezza sta. Fra un valore e l'altro si
 * interpola: e' come la proiezione e' definita, non un'approssimazione nostra.
 */
const ROBINSON = [
    [1.0000, 0.0000], [0.9986, 0.0620], [0.9954, 0.1240], [0.9900, 0.1860],
    [0.9822, 0.2480], [0.9730, 0.3100], [0.9600, 0.3720], [0.9427, 0.4340],
    [0.9216, 0.4958], [0.8962, 0.5571], [0.8679, 0.6176], [0.8350, 0.6769],
    [0.7986, 0.7346], [0.7597, 0.7903], [0.7186, 0.8435], [0.6732, 0.8936],
    [0.6213, 0.9394], [0.5722, 0.9761], [0.5322, 1.0000],
];

/** @return array{0:float,1:float} x, y in unita' di proiezione */
function robinson(float $lon, float $lat): array
{
    $segno = $lat < 0 ? -1.0 : 1.0;
    $a = min(abs($lat), 90.0) / 5.0;
    $i = (int) floor($a);
    $f = $a - $i;
    if ($i >= count(ROBINSON) - 1) {
        $i = count(ROBINSON) - 2;
        $f = 1.0;
    }
    [$x0, $y0] = ROBINSON[$i];
    [$x1, $y1] = ROBINSON[$i + 1];
    $lunghezza = $x0 + ($x1 - $x0) * $f;
    $altezza   = $y0 + ($y1 - $y0) * $f;
    return [$lon * $lunghezza, $segno * $altezza];
}

$geo = json_decode((string) file_get_contents($sorgente), true);
if (!is_array($geo) || !isset($geo['features'])) {
    fwrite(STDERR, "Il file non e' un GeoJSON che sappia leggere.\n");
    exit(1);
}

// Le scale del disegno: Robinson va da -180·1 a +180·1 in larghezza e da
// -1 a +1 in altezza, moltiplicato per il fattore 0,5072 della proiezione.
$scala = $LARGO / 360.0;
$alto  = 2.0 * 0.5072 * 180.0 * $scala;
$mezzoX = $LARGO / 2.0;
$mezzoY = $alto / 2.0;

$punto = static function (array $c) use ($scala, $mezzoX, $mezzoY, $PRECISIONE): ?array {
    if (!isset($c[0], $c[1]) || !is_numeric($c[0]) || !is_numeric($c[1])) {
        return null;
    }
    [$x, $y] = robinson((float) $c[0], (float) $c[1]);
    return [
        round($mezzoX + $x * $scala, $PRECISIONE),
        round($mezzoY - $y * 0.5072 * 180.0 * $scala, $PRECISIONE),
    ];
};

/**
 * Douglas-Peucker: butta i punti che stanno gia' sulla linea fra i vicini.
 *
 * E' l'algoritmo classico e fa esattamente quel che serve qui: tiene i vertici
 * che danno forma alla costa e butta quelli che la descrivono.
 *
 * @param list<array{0:float,1:float}> $punti
 * @return list<array{0:float,1:float}>
 */
function sgrossa(array $punti, float $tolleranza): array
{
    $n = count($punti);
    if ($n < 3) {
        return $punti;
    }
    [$ax, $ay] = $punti[0];
    [$bx, $by] = $punti[$n - 1];

    $peggiore = 0.0;
    $dove = 0;
    $dx = $bx - $ax;
    $dy = $by - $ay;
    $lunghezza = sqrt($dx * $dx + $dy * $dy);

    for ($i = 1; $i < $n - 1; $i++) {
        [$px, $py] = $punti[$i];
        $d = $lunghezza < 1e-9
            ? sqrt(($px - $ax) ** 2 + ($py - $ay) ** 2)
            : abs($dy * $px - $dx * $py + $bx * $ay - $by * $ax) / $lunghezza;
        if ($d > $peggiore) {
            $peggiore = $d;
            $dove = $i;
        }
    }

    if ($peggiore <= $tolleranza) {
        return [$punti[0], $punti[$n - 1]];
    }
    $sinistra = sgrossa(array_slice($punti, 0, $dove + 1), $tolleranza);
    $destra   = sgrossa(array_slice($punti, $dove), $tolleranza);
    array_pop($sinistra);
    return array_merge($sinistra, $destra);
}

/** Quanto e' grande il rettangolo che contiene un anello. */
function estensione(array $punti): float
{
    if ($punti === []) {
        return 0.0;
    }
    $xs = array_column($punti, 0);
    $ys = array_column($punti, 1);
    return max(max($xs) - min($xs), max($ys) - min($ys));
}

/** Un anello di coordinate diventa un pezzo di tracciato. */
/** @return array{0:string,1:float,2:?array{0:float,1:float}} tracciato, grandezza, centro */
$anello = static function (array $punti) use ($punto, $TOLLERANZA, $TOLLERANZA_MINIMA): array {
    $proiettati = [];
    $ultimo = null;
    foreach ($punti as $c) {
        $p = $punto($c);
        if ($p === null) {
            continue;
        }
        // Punti che cadono sullo stesso decimo di unita' non aggiungono nulla
        // al disegno e raddoppiano il peso del file.
        if ($ultimo !== null && $p[0] === $ultimo[0] && $p[1] === $ultimo[1]) {
            continue;
        }
        $proiettati[] = $p;
        $ultimo = $p;
    }
    if (count($proiettati) < 3) {
        return ['', 0.0, $proiettati[0] ?? null];
    }
    $grande = estensione($proiettati);
    $centro = [
        round(array_sum(array_column($proiettati, 0)) / count($proiettati), 1),
        round(array_sum(array_column($proiettati, 1)) / count($proiettati), 1),
    ];
    $mano = max($TOLLERANZA_MINIMA, min($TOLLERANZA, $grande / 25.0));
    $proiettati = sgrossa($proiettati, $mano);
    if (count($proiettati) < 3) {
        return ['', $grande, $centro];
    }

    $d = '';
    foreach ($proiettati as $i => $p) {
        $d .= ($i === 0 ? 'M' : 'L') . $p[0] . ' ' . $p[1];
    }
    return [$d . 'Z', $grande, $centro];
};

/**
 * Un rombo al posto di un'isola.
 *
 * A scala mondiale le Maldive, le Seychelles e Antigua sono punti: sgrossando
 * il contorno spariscono del tutto. Sparire pero' non va bene — sono nazioni
 * del gioco come le altre, si colorano e si cliccano — quindi al loro posto si
 * mette un segno grande abbastanza da esistere.
 */
function segnaposto(array $centro): string
{
    [$x, $y] = $centro;
    $r = 1.6;
    return sprintf('M%.1f %.1fL%.1f %.1fL%.1f %.1fL%.1f %.1fZ',
        $x, $y - $r, $x + $r, $y, $x, $y + $r, $x - $r, $y);
}

$tracciati = [];
$scartati  = 0;
$segnati   = [];

foreach ($geo['features'] as $f) {
    $p = $f['properties'] ?? [];
    $iso = (string) ($p['ISO_A3'] ?? '-99');
    if ($iso === '-99' || $iso === '') {
        $iso = (string) ($p['ISO_A3_EH'] ?? '-99');
    }
    if ($iso === '-99' || $iso === '') {
        // Il Kosovo non ha un ISO 3166 riconosciuto e Natural Earth lo chiama
        // KOS; il seme del gioco usa XKX, che e' il codice d'uso comune.
        $adm = (string) ($p['ADM0_A3'] ?? '');
        $iso = $adm === 'KOS' ? 'XKX' : $adm;
    }
    if ($iso === '' || $iso === '-99') {
        $scartati++;
        continue;
    }

    $g = $f['geometry'] ?? null;
    if ($g === null) {
        continue;
    }
    $poligoni = $g['type'] === 'Polygon' ? [$g['coordinates']] : ($g['coordinates'] ?? []);

    // Si raccolgono tutti i pezzi con la loro grandezza, poi si decide: le
    // isole minuscole si buttano, ma se un paese e' fatto solo di quelle si
    // tiene la piu' grande, o sparirebbe dalla mappa.
    $pezzi = [];
    $sparite = [];
    foreach ($poligoni as $poly) {
        foreach ($poly as $i => $ring) {
            // Solo il contorno esterno: i buchi (laghi, enclavi) costano byte e
            // a questa scala non si vedono.
            if ($i > 0) {
                continue;
            }
            [$d, $grande, $centro] = $anello($ring);
            if ($d !== '') {
                $pezzi[] = [$d, $grande];
            } elseif ($centro !== null && $grande > 0.0) {
                $sparite[] = [$grande, $centro];
            }
        }
    }
    if ($pezzi === []) {
        // Niente e' sopravvissuto alla sgrossatura: un segno, e almeno c'e'.
        if ($sparite !== []) {
            usort($sparite, static fn($a, $b) => $b[0] <=> $a[0]);
            $tracciati[$iso] = ($tracciati[$iso] ?? '') . segnaposto($sparite[0][1]);
            $segnati[] = $iso;
        }
        continue;
    }
    usort($pezzi, static fn($a, $b) => $b[1] <=> $a[1]);
    $tenuti = array_filter($pezzi, static fn($p) => $p[1] >= $ISOLA_MINIMA);
    if ($tenuti === []) {
        $tenuti = [$pezzi[0]];   // un microstato resta visibile
    }
    $tracciati[$iso] = ($tracciati[$iso] ?? '')
        . implode('', array_column($tenuti, 0));
}

// Solo i paesi che il gioco conosce: il resto e' peso inutile.
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$noti = [];
foreach ($mondo->nazioni as $n) {
    $noti[$n->iso3] = true;
}
$tenuti = array_intersect_key($tracciati, $noti);
ksort($tenuti);

$mancanti = array_diff_key($noti, $tenuti);

$destinazione = $radice . '/db/seed/confini-svg.json';
file_put_contents($destinazione, json_encode([
    'fonte'      => 'Natural Earth 1:50m admin 0 countries — dominio pubblico',
    'proiezione' => 'Robinson',
    'larghezza'  => $LARGO,
    'altezza'    => round($alto, 1),
    'tracciati'  => $tenuti,
], JSON_UNESCAPED_SLASHES));

printf("  %d tracciati scritti in %s (%s)\n",
    count($tenuti), basename($destinazione),
    number_format(filesize($destinazione) / 1024, 0) . ' KB');
printf("  riquadro: %.0f × %.0f\n", $LARGO, $alto);
if ($scartati > 0) {
    printf("  %d geometrie senza codice riconoscibile, scartate\n", $scartati);
}
// Si riferisce solo di quel che riguarda il gioco, e solo di chi e' rimasto
// davvero senza contorno: un paese puo' avere un territorio minuscolo ridotto a
// un segno e la madrepatria disegnata per bene, e dirlo confonderebbe.
$soloSegno = [];
foreach ($tenuti as $iso => $d) {
    if (strlen($d) < 60) {
        $soloSegno[] = $iso;
    }
}
if ($soloSegno !== []) {
    printf("  troppo piccole per un contorno, disegnate come rombo (%d): %s\n",
        count($soloSegno), implode(' ', $soloSegno));
}
if ($mancanti !== []) {
    printf("  SENZA GEOMETRIA (%d): %s\n", count($mancanti), implode(' ', array_keys($mancanti)));
} else {
    printf("  tutte le %d nazioni del gioco hanno un tracciato\n", count($noti));
}
