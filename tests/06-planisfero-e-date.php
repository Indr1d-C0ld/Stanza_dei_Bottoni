<?php

declare(strict_types=1);

/**
 * Il planisfero.
 *
 * Una mappa sbagliata non da' errore: disegna, e sembra plausibile finche'
 * qualcuno non nota che il Giappone sta in Atlantico. Queste prove controllano
 * la geografia con affermazioni che un bambino saprebbe verificare, ed e'
 * esattamente il livello giusto — se salta una di queste, e' saltata la
 * proiezione.
 *
 * L'errore che hanno gia' colto, la prima volta: il centro di un paese non si
 * calcola sulla media di tutti i suoi punti. La Nuova Zelanda ha le Chatham
 * oltre l'antimeridiano, e quella media la piazzava in mezzo al Pacifico —
 * a ovest dell'Australia. Si guarda il pezzo piu' grande.
 */

use App\Dati\Planisfero;

$percorso = dirname(__DIR__) . '/db/seed/confini-svg.json';
$p = new Planisfero($percorso);

Prove::gruppo('Planisfero: i confini ci sono tutti');

Prove::che('il file dei confini esiste ed e\' leggibile', $p->pronto());
Prove::uguale('un tracciato per ciascuna delle 189 nazioni', 189, count($p->tracciati));
Prove::che('il riquadro ha proporzioni da planisfero',
    $p->larghezza / $p->altezza > 1.6 && $p->larghezza / $p->altezza < 2.4,
    sprintf('%.0f × %.0f', $p->larghezza, $p->altezza));

$peso = filesize($percorso) / 1024;
Prove::che('il file non e\' ingestibile per una pagina web',
    $peso < 400, sprintf('%.0f KB', $peso));

/** Il centro del pezzo piu' grande: e' li' che sta il paese. */
$centro = static function (string $d): ?array {
    $migliore = null;
    $quanti = 0;
    foreach (array_filter(explode('Z', $d)) as $pezzo) {
        preg_match_all('/[ML](-?[\d.]+) (-?[\d.]+)/', $pezzo, $m);
        if (count($m[1]) > $quanti) {
            $quanti = count($m[1]);
            $migliore = [
                array_sum($m[1]) / count($m[1]),
                array_sum($m[2]) / count($m[2]),
            ];
        }
    }
    return $migliore;
};

$c = [];
foreach ($p->tracciati as $iso => $d) {
    $x = $centro($d);
    if ($x !== null) {
        $c[$iso] = $x;
    }
}

Prove::gruppo('Planisfero: la geografia sta al posto giusto');

Prove::che('l\'Islanda e\' a nord dell\'Italia',        $c['ISL'][1] < $c['ITA'][1]);
Prove::che('l\'Italia e\' a nord dell\'Egitto',         $c['ITA'][1] < $c['EGY'][1]);
Prove::che('l\'Egitto e\' a nord del Sudafrica',        $c['EGY'][1] < $c['ZAF'][1]);
Prove::che('l\'Argentina e\' a sud del Brasile',        $c['ARG'][1] > $c['BRA'][1]);
Prove::che('l\'India e\' a sud della Russia',           $c['IND'][1] > $c['RUS'][1]);
Prove::che('l\'Australia e\' a sud della Cina',         $c['AUS'][1] > $c['CHN'][1]);
Prove::che('gli Stati Uniti sono a ovest dell\'Italia', $c['USA'][0] < $c['ITA'][0]);
Prove::che('il Brasile e\' a ovest del Sudafrica',      $c['BRA'][0] < $c['ZAF'][0]);
Prove::che('il Giappone e\' a est della Cina',          $c['JPN'][0] > $c['CHN'][0]);
Prove::che('la Nuova Zelanda e\' a est dell\'Australia', $c['NZL'][0] > $c['AUS'][0]);

$fuori = 0;
foreach ($c as $x) {
    if ($x[0] < 0 || $x[0] > $p->larghezza || $x[1] < 0 || $x[1] > $p->altezza) {
        $fuori++;
    }
}
Prove::uguale('nessun paese finisce fuori dal riquadro', 0, $fuori);

Prove::gruppo('Planisfero: nessun poligono attraversa la mappa');

// Chi ha terre oltre l'antimeridiano — Figi, Russia, Nuova Zelanda — deve
// avere piu' sottotracciati, non uno solo largo mezzo mondo: quello
// disegnerebbe una striscia da un capo all'altro dell'oceano.
$spalmati = [];
foreach ($p->tracciati as $iso => $d) {
    foreach (array_filter(explode('Z', $d)) as $pezzo) {
        preg_match_all('/[ML](-?[\d.]+) (-?[\d.]+)/', $pezzo, $m);
        if (count($m[1]) < 2) {
            continue;
        }
        if (max($m[1]) - min($m[1]) > $p->larghezza * 0.5) {
            $spalmati[] = $iso;
        }
    }
}
Prove::uguale('nessun tracciato spalmato da un capo all\'altro', [], array_unique($spalmati));

Prove::gruppo('Planisfero: le letture producono colori sensati');

$finte = [];
foreach (['AAA' => 0, 'BBB' => 50, 'CCC' => 100] as $iso => $leg) {
    $finte[] = [
        'codice' => $iso, 'legittimita' => $leg, 'influenza_totale' => 1.0,
        'net_peace' => 2, 'qualita_vita' => 5, 'pressione_esterna' => 0.0,
    ];
}
$colori = $p->colori('legittimita', $finte);
Prove::uguale('un colore per ogni paese', 3, count($colori));
Prove::che('chi sta male e chi sta bene hanno colori diversi',
    $colori['AAA']['colore'] !== $colori['CCC']['colore']);
Prove::che('il colore e\' un esadecimale valido',
    preg_match('/^#[0-9a-f]{6}$/', $colori['BBB']['colore']) === 1,
    $colori['BBB']['colore']);

foreach (array_keys(Planisfero::LETTURE) as $l) {
    $x = $p->colori($l, $finte, ['AAA' => -100.0, 'CCC' => 100.0]);
    Prove::uguale("la lettura «{$l}» colora tutti", 3, count($x));
}

Prove::gruppo('Le date si scrivono in italiano');

// Una data in formato americano o ISO in mezzo a una pagina italiana non e'
// un dettaglio estetico: e' ambiguo. «03/04» puo' essere marzo o aprile a
// seconda di chi guarda, e «2028-11-27» non lo scrive nessuno in italiano.
$c = App\Nucleo\Calendario::class;

Prove::uguale('il primo giorno del mondo', '05/01/2026', $c::tick(0));
Prove::uguale('una settimana dopo',        '12/01/2026', $c::tick(1));
Prove::uguale('e il database lo tiene in ISO, che li si ordina', '2026-01-12', $c::tickIso(1));

Prove::uguale('una data dal database',     '21/09/2026', $c::data('2026-09-21 16:55:47'));
Prove::uguale('con l\'ora quando serve',   '21/09/2026 16:55', $c::dataOra('2026-09-21 16:55:47'));
Prove::uguale('e un trattino quando non c\'e\' niente', '—', $c::data(null));
Prove::uguale('anche se la stringa e\' vuota', '—', $c::data(''));
Prove::uguale('e se non e\' una data', '—', $c::data('questo non e\' un giorno'));

// Il giorno viene prima del mese: e' tutto il punto.
$undiciDicembre = $c::data('2028-12-11');
Prove::uguale('il giorno viene per primo', '11/12/2028', $undiciDicembre);

Prove::gruppo('Le viste non mostrano piu\' numeri di tick al giocatore');

$colpevoli = [];
foreach (glob(dirname(__DIR__) . '/views/{*.php,parti/*.php}', GLOB_BRACE) ?: [] as $f) {
    $testo = (string) file_get_contents($f);
    // «t<?= ... tick» oppure «al tick <?=»: sono i modi in cui un numero di
    // tick finiva davanti a chi gioca.
    if (preg_match('/(tick <\?=|>t<\?=)/', $testo) === 1) {
        $colpevoli[] = basename($f);
    }
}
Prove::uguale('nessuna vista stampa un tick nudo', [], $colpevoli);
