<?php

declare(strict_types=1);

/**
 * La guerra e la pace (docs/33, docs/34, docs/35): i garanti che combattono, i
 * ribelli col tetto, l'orientamento e la radicalita', i civili, il rimbalzo, la
 * faziosita'. Tutto in memoria.
 */

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Nucleo\Caso;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsecutoreTick;
use App\Simulazione\Fasi\Fase03Economia;
use App\Simulazione\Fasi\Fase07Conflitto;

$radiceGP = dirname(__DIR__);
$semeGP = $radiceGP . '/db/seed/nazioni.csv';
$calGP = Calibrazione::carica($radiceGP, 'osservazione');

// ------------------------------------------------- orientamento e radicalita'
Prove::gruppo('L\'orientamento e\' l\'allineamento, la radicalita\' e\' la repressione');

$m = Mondo::daSeme($semeGP);
Prove::uguale('gli Stati Uniti sono il polo dell\'asse dei voti all\'ONU', 128, $m->nazioni['USA']->orientamento);
Prove::che('la Russia vota col paese mediano, l\'Iran contro l\'Occidente',
    abs($m->nazioni['RUS']->orientamento) < 20 && $m->nazioni['IRN']->orientamento < -30);
Prove::che('ma il regime piu\' radicale non sono gli Stati Uniti: e\' la Corea del Nord',
    $m->nazioni['PRK']->radicalita() > 0.9 && $m->nazioni['USA']->radicalita() < 0.3);

// ------------------------------------------------------------ cobelligeranti
Prove::gruppo('I garanti che onorano la parola combattono');

$m = Mondo::daSeme($semeGP);
$m->guerre[] = ['aggressore' => 'CHN', 'difensore' => 'TWN', 'inizio' => 0, 'morti' => 0.0,
    'aiuti_difensore' => 0.0, 'aiuti_aggressore' => 0.0];
$c = new ContestoTick(tick: 1, seme: 1, caso: new Caso(1), calibrazione: $calGP, mondo: $m);
(new Fase07Conflitto())->esegui($c);
$g = array_values(array_filter($m->guerre, static fn(array $g): bool => $g['difensore'] === 'TWN'))[0] ?? [];
Prove::che('gli Stati Uniti entrano in guerra per Taiwan (Taiwan Relations Act, filo d\'inciampo)',
    in_array('USA', (array) ($g['cobelligeranti'] ?? []), true), implode(',', (array) ($g['cobelligeranti'] ?? [])));
Prove::che('e combattono: sono in conflitto', $m->nazioni['USA']->netPeace >= 4);

// -------------------------------------------------------------------- ribelli
Prove::gruppo('I ribelli hanno un tetto');

$m = Mondo::daSeme($semeGP);
$es = new EsecutoreTick($calGP, null, true, $m);
$peggio = 0.0;
for ($t = 1; $t <= 4 * 52; $t++) {
    $m->tick = $t;
    $es->esegui($t, 4);
}
foreach ($m->elenco() as $n) {
    $peggio = max($peggio, $n->forzaInsorti / max(1.0, $n->potenzaIniziale));
}
Prove::che('in quattro anni nessuna insurrezione supera di molto il tetto (1,5 volte lo Stato al seme)',
    $peggio < 1.7, (string) round($peggio, 2));

// ------------------------------------------------------------ civili e pace
Prove::gruppo('I civili e il rimbalzo vengono dai dati');

Prove::vicino('0,09 civili per combattente, come nel GED dell\'UCDP', 0.09,
    $calGP->numero('conflitto.civili_per_militare'), 0.001);

$m = Mondo::daSeme($semeGP);
$n = $m->nazioni['GHA'];
$n->costoGuerra = 0.023;   // una guerra civile piena, appena finita
$n->netPeace = 2;
$c = new ContestoTick(tick: 1, seme: 1, caso: new Caso(1), calibrazione: $calGP, mondo: $m,
    casoDelMondo: new Caso(1));
(new Fase03Economia())->esegui($c);
Prove::fra('una guerra civile che finisce da\' un rimbalzo di quasi due punti', 0.015, 0.020, $n->rimbalzo);
Prove::uguale('e il costo della guerra non c\'e\' piu\'', 0.0, $n->costoGuerra);
$m2 = Mondo::daSeme($semeGP);
$c2 = new ContestoTick(tick: 1, seme: 1, caso: new Caso(1), calibrazione: $calGP, mondo: $m2,
    casoDelMondo: new Caso(1));
(new Fase03Economia())->esegui($c2);
Prove::che('chi non ha avuto guerre non rimbalza', $m2->nazioni['GHA']->rimbalzo === 0.0);

// ------------------------------------------------------------------ faziosita'
Prove::gruppo('La faziosita\' e\' del regime, e c\'e\' per tutti');

$m = Mondo::daSeme($semeGP);
$faziosi = array_filter($m->elenco(), static fn($n): bool => $n->faziosita > 0.0);
Prove::che('la faziosita\' di Polity non riguarda piu\' solo le quattordici potenze giocabili',
    count($faziosi) >= 25, (string) count($faziosi));
Prove::che('il Libano, l\'Iraq e il Pakistan sono faziosi; la Norvegia e la Cina no',
    $m->nazioni['LBN']->faziosita === 1.0 && $m->nazioni['IRQ']->faziosita === 1.0
    && $m->nazioni['PAK']->faziosita === 1.0
    && $m->nazioni['NOR']->faziosita === 0.0 && $m->nazioni['CHN']->faziosita === 0.0);
Prove::vicino('e pesa quanto nei colpi veri: un quinto in piu\' fra i regimi parziali (Powell e Thyne)',
    0.2, $calGP->numero('instabilita.peso_faziosita'), 0.001);
