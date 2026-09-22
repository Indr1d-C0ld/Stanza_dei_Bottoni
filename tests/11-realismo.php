<?php

declare(strict_types=1);

/**
 * Le grandezze che il mondo dichiara devono somigliare a quelle del mondo vero.
 *
 * Questa prova nasce da un numero visto in faccia sul planisfero: «Francia
 * contro Cina, 64.710.856 morti» dopo un anno di guerra. Il modello toglieva
 * dai ruoli centocinquantamila uomini e poi ne dichiarava morti duecentoventi
 * volte tanto, perche' i caduti si ottenevano moltiplicando l'attrito per un
 * sessanta che non aveva unita' di misura dietro.
 *
 * Nessuna prova sul conflitto se ne sarebbe accorta: tutte guardavano se la
 * guerra cominciava, se finiva, se le garanzie scattavano. Nessuna guardava se
 * i numeri erano credibili. Adesso qualcuna lo fa.
 */

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Simulazione\EsecutoreTick;
use App\Simulazione\Realismo;

$radice = dirname(__DIR__);

Prove::gruppo('Le grandezze del mondo stanno nelle fasce del mondo vero');

$cal      = Calibrazione::carica($radice, 'osservazione');
$tickAnno = (int) $cal->numero('tempo.tick_per_anno', 52.0);
$anni     = 12;

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$r     = Realismo::misura($mondo, new EsecutoreTick($cal, null, true, $mondo), $anni, $tickAnno, 1);

foreach (Realismo::FASCE as $chiave => [$min, $max, $unita, $quando, $fonte]) {
    Prove::fra("$chiave sta fra $min e $max $unita", (float) $min, (float) $max, $r['misure'][$chiave]);
}

Prove::gruppo('Una guerra fa i morti di una guerra, non quelli di un secolo');

// Cina contro Francia per un anno: la coppia che aveva prodotto i 64 milioni.
$cal2   = Calibrazione::carica($radice, 'osservazione');
$attrito = $cal2->numero('insurrezione.attrito_anno', 0.25) / $tickAnno;
$quota   = $cal2->numero('conflitto.quota_caduti', 0.33);
$civili  = $cal2->numero('conflitto.civili_per_militare', 1.0);

$m = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$a = $m->nazioni['CHN'];
$d = $m->nazioni['FRA'];

$morti = 0.0;
$persiTotali = 0.0;
for ($t = 1; $t <= $tickAnno; $t++) {
    $mob = 1.35 + 0.9 * min(1.0, $t / $tickAnno);
    $fA  = $a->potenzaGoverno();
    $fD  = $d->potenzaGoverno() * $mob;
    $persi = 0.0;
    foreach ([[$a, $fD * $attrito, $fA], [$d, $fA * $attrito, $fD]] as [$n, $danno, $forza]) {
        $q = $forza > 0 ? min(0.4, $danno / $forza) : 0.0;
        $n->equipaggiamento *= 1.0 - $q * 0.9;
        $prima = $n->soldati;
        $n->soldati = max(500.0, $n->soldati * (1.0 - $q * 0.45));
        $persi += $prima - $n->soldati;
    }
    $persiTotali += $persi;
    $morti += $persi * $quota * (1.0 + $civili);
}

// Corea 1950-53 fece ~400 mila morti l'anno, la guerra Iran-Iraq ~100 mila,
// Russia-Ucraina ~100 mila: mezzo milione l'anno e' gia' il limite alto per
// UNA guerra bilaterale, e cinquemila e' il limite basso sotto cui non e' piu'
// una guerra ma un incidente di frontiera.
Prove::fra('un anno di guerra Cina-Francia fa morti da guerra', 5_000.0, 500_000.0, $morti);

// E la relazione fra le due grandezze deve reggere: i morti non possono essere
// piu' degli uomini tolti dai ruoli. E' l'invariante che mancava.
Prove::che('i morti non superano gli uomini persi dal fronte',
    $morti <= $persiTotali,
    sprintf('morti %s, persi %s', number_format($morti), number_format($persiTotali)));

Prove::gruppo('Il planisfero non spaccia per aperte le guerre finite');

$mappa = file_get_contents($radice . '/views/mappa.php');
Prove::che('la mappa separa le guerre in corso da quelle concluse',
    str_contains((string) $mappa, "fine_tick'] === null")
    && str_contains((string) $mappa, 'Guerre concluse'));

Prove::gruppo('I numeri si scrivono all\'italiana, come le date');

$viste = array_merge(glob($radice . '/views/*.php') ?: [], glob($radice . '/views/parti/*.php') ?: []);
$colpevoli = [];
foreach ($viste as $v) {
    $testo = (string) file_get_contents($v);
    // number_format() senza separatori espliciti stampa «25,680»: un occhio
    // italiano ci legge venticinque virgola sei. Le viste usano n().
    if (preg_match('/number_format\s*\((?![^()]*\',\'\s*,\s*\'\.\')/', $testo)) {
        $colpevoli[] = basename($v);
    }
}
Prove::che('nessuna vista stampa numeri all\'anglosassone',
    $colpevoli === [], implode(', ', $colpevoli));

Prove::che('il formattatore n() esiste in index.php',
    str_contains((string) file_get_contents($radice . '/index.php'), 'function n(float $x'));
