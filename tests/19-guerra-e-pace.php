<?php

declare(strict_types=1);

/**
 * La guerra e la pace (docs/33-docs/37): i garanti che combattono, i
 * ribelli col tetto, l'orientamento e la radicalita', i civili, il rimbalzo, la
 * faziosita', la trappola del colpo. Tutto in memoria.
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

// ------------------------------------------------------------ colpi e rivalita'
Prove::gruppo('Si fa un colpo di Stato anche dove si vota, e si odia anche senza sparare');

Prove::vicino('sotto 0,55 di democrazia liberale una caduta e\' un colpo di Stato (19 colpi su 36 fra 0,25 e 0,55)',
    0.55, $calGP->numero('colpo_di_stato.democrazia_regolare'), 0.001);
Prove::che('la curva del colpo e\' quella misurata, non quella ripida di prima (pendenza 18, tetto sotto il 3,5% l\'anno)',
    $calGP->numero('colpo_di_stato.pendenza') >= 14.0 && $calGP->numero('colpo_di_stato.rischio_massimo_anno') <= 0.035);

$m = Mondo::daSeme($semeGP);
$aff = static fn(string $a, string $b): float => (float) ($m->relazioni->fra($a, $b)?->affinita ?? 0.0);
Prove::che('le rivalita\' strategiche senza guerre pesano: l\'Argentina e il Regno Unito partono ostili',
    $aff('ARG', 'GBR') < -20.0, (string) round($aff('ARG', 'GBR')));
Prove::che('ma quelle finite non piu\': l\'Iran e l\'Iraq (Thompson, Sakuwa e Suhas, aggiornate al 2020)',
    $aff('IRN', 'IRQ') > 0.0, (string) round($aff('IRN', 'IRQ')));
Prove::che('i paesi che la Russia dichiara ostili lo sono anche qui: il Portogallo, lontano e senza dispute',
    $aff('RUS', 'PRT') < 0.0, (string) round($aff('RUS', 'PRT')));
Prove::che('e chi non c\'entra resta indifferente o amico: la Russia e il Brasile',
    $aff('RUS', 'BRA') > 20.0, (string) round($aff('RUS', 'BRA')));

// ------------------------------------------------------- la trappola del colpo
Prove::gruppo('Chi ha appena avuto un colpo di Stato ne ha altri');

$m = Mondo::daSeme($semeGP);
Prove::che('il seme sa dei colpi recenti: il Niger nel 2023, il Mali nel 2021 (Powell e Thyne)',
    $m->nazioni['NER']->ultimoColpo < 0 && $m->nazioni['NER']->ultimoColpo > -3 * 52
    && $m->nazioni['MLI']->ultimoColpo > -5 * 52,
    $m->nazioni['NER']->ultimoColpo . ' ' . $m->nazioni['MLI']->ultimoColpo);
Prove::che('e di chi non ne ha avuti: l\'Iraq e il Pakistan',
    $m->nazioni['IRQ']->ultimoColpo === \App\Dati\Nazione::MAI_COLPO
    && $m->nazioni['PAK']->ultimoColpo === \App\Dati\Nazione::MAI_COLPO);
Prove::vicino('un colpo nei dieci anni prima triplica il rischio (3,1: Powell e Thyne, a parita\' di stabilita\')',
    3.1, $calGP->numero('colpo_di_stato.trappola'), 0.001);

// ------------------------------------------------------------ eventi recenti
Prove::gruppo('Le ostilita\' nate dopo il 2020 senza sparare ci sono, ma non insegnano niente ai pesi');

$m = Mondo::daSeme($semeGP);
$aff = static fn(string $a, string $b): float => (float) ($m->relazioni->fra($a, $b)?->affinita ?? 0.0);
Prove::che('la Francia e le giunte del Sahel, la Turchia e Israele, la Cina e le Filippine sono ostili',
    $aff('NER', 'FRA') < -30.0 && $aff('TUR', 'ISR') < -30.0 && $aff('CHN', 'PHL') < -30.0,
    sprintf('%d %d %d', $aff('NER', 'FRA'), $aff('TUR', 'ISR'), $aff('CHN', 'PHL')));
$politicaGP = require $radiceGP . '/db/seed/politica-nota.php';
Prove::che('e i pesi delle affinita\' si stimano sui soli rapporti strutturali, non sugli eventi',
    !str_contains((string) file_get_contents($radiceGP . '/bin/importa_onu.php'), 'eventi_recenti')
    && count($politicaGP['eventi_recenti'] ?? []) >= 10);
