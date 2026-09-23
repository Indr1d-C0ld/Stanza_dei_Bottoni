<?php

declare(strict_types=1);

/**
 * La guerra fra Stati, la guerriglia, il governo che cade alle urne.
 *
 * Le meccaniche entrate col secondo giro dell'audit (docs/28): la guerra
 * russo-ucraina nel seme, gli aiuti militari, l'armistizio, la deterrenza,
 * l'innesco delle insurrezioni, il gabinetto che cambia con chi perde le
 * elezioni. Tutto in memoria, sul profilo di osservazione.
 */

use App\Dati\Mondo;
use App\Nucleo\Calendario;
use App\Nucleo\Calibrazione;
use App\Simulazione\EsecutoreTick;

$radiceGuerra = dirname(__DIR__);
$semeGuerra   = $radiceGuerra . '/db/seed/nazioni.csv';

Prove::gruppo('La guerra fra Stati: il seme sa che la Russia e l\'Ucraina combattono');

$m = Mondo::daSeme($semeGuerra);
$ru = array_values(array_filter($m->guerre,
    static fn(array $g): bool => $g['aggressore'] === 'RUS' && $g['difensore'] === 'UKR'));
Prove::uguale('la guerra c\'e\', una volta sola', 1, count($ru));
Prove::uguale('e comincia la settimana del 24/02/2022', '21/02/2022',
    Calendario::tick((int) ($ru[0]['inizio'] ?? 0)));
Prove::uguale('una data prima della divergenza si scrive', '05/01/2026', Calendario::tick(0));
Prove::che('i due eserciti sono in guerra dal primo giorno',
    $m->nazioni['RUS']->netPeace === 6 && $m->nazioni['UKR']->netPeace === 6);

$alleanze = require $radiceGuerra . '/db/seed/alleanze.php';
Prove::che('la Russia non e\' piu\' garante della difesa dell\'Ucraina', !isset($alleanze['RUS|UKR']));
Prove::che('ne\' la Georgia alleata della Russia', !isset($alleanze['GEO|RUS']));
Prove::che('l\'Armenia non sta piu\' sotto l\'ombrello della CSTO', !isset($alleanze['RUS|ARM']));
Prove::uguale('la KFOR e\' una base americana in Kosovo', 64, $alleanze['USA|XKX'] ?? 0);

$quota = $m->nazioni['UKR']->quotaMilitare;
Prove::fra('la spesa militare ucraina e\' quella del SIPRI 2024', 0.30, 0.38, $quota);
Prove::che('e la crescita ucraina non porta il crollo del 2022 come tendenza',
    $m->nazioni['UKR']->crescitaBase > 0.0);

Prove::gruppo('La guerra fra Stati: gli aiuti, e una guerra sola per coppia');

$cal = Calibrazione::carica($radiceGuerra, 'osservazione');
$mondoG = Mondo::daSeme($semeGuerra);
$motore = new EsecutoreTick($cal, null, true, $mondoG);
$giornale = new ReflectionProperty($motore, 'ultimoGiornale');
$doppie = 0;
$primoAnno = [];
$aperta = null;
$alternanze = 0;
$gabinettiNuovi = 0;
// Il seme 2: la guerra russo-ucraina vi dura oltre il primo semestre, e
// gli aiuti si possono misurare.
for ($t = 1; $t <= 104; $t++) {
    $mondoG->tick = $t;
    $motore->esegui($t, 2, null);
    $coppie = [];
    foreach ($mondoG->guerre as $g) {
        $k = min($g['aggressore'], $g['difensore']) . '|' . max($g['aggressore'], $g['difensore']);
        $doppie += isset($coppie[$k]) ? 1 : 0;
        $coppie[$k] = true;
    }
    if ($t <= 52) {
        $primoAnno[] = count(array_filter($mondoG->nazioni, static fn($n) => $n->netPeace >= 4));
    }
    if ($t === 26) {
        $aperta = array_values(array_filter($mondoG->guerre,
            static fn(array $g): bool => $g['aggressore'] === 'RUS' && $g['difensore'] === 'UKR'))[0] ?? null;
    }
    // Chi perde alle urne perde il gabinetto: il Capo e' nuovo.
    foreach ($giornale->getValue($motore) as $voce) {
        if ($voce['genere'] !== 'elezione' || ($voce['dati']['esito'] ?? '') !== 'alternanza') {
            continue;
        }
        foreach ($mondoG->nazioni as $n) {
            if ($n->nome === $voce['dati']['nazione'] && isset($mondoG->gabinetti[$n->iso3])) {
                $alternanze++;
                $gabinettiNuovi += $mondoG->gabinetti[$n->iso3]->poltrone['capo']->insediatoTick === $t ? 1 : 0;
            }
        }
    }
}
Prove::uguale('mai due guerre fra gli stessi due paesi', 0, $doppie);
if ($aperta !== null) {
    // Kiel Institute: ~45 miliardi di euro l'anno; nelle unita' del modello,
    // a parita' di potere d'acquisto, fra 25 e 50 mila in mezzo anno.
    Prove::fra('l\'Ucraina riceve aiuti dell\'ordine del Kiel Institute, in mezzo anno',
        25000.0, 50000.0, (float) $aperta['aiuti_difensore']);
    Prove::che('molto piu\' di quanti ne riceva la Russia',
        $aperta['aiuti_difensore'] > 5.0 * $aperta['aiuti_aggressore']);
    Prove::fra('i morti, in mezzo anno, stanno fra i riferimenti', 20000.0, 120000.0, (float) $aperta['morti']);
} else {
    Prove::che('sul seme 2 la guerra dura oltre il primo semestre', false, 'e\' finita prima: la prova va rifatta');
}
Prove::che('nessun picco di conflitti nel primo anno (innesco di Fearon e Laitin)',
    max($primoAnno) <= 40, 'massimo ' . max($primoAnno));
Prove::che('in due anni qualche governo perde le elezioni', $alternanze > 0, (string) $alternanze);
Prove::uguale('e chi perde cambia il Capo del gabinetto', $alternanze, $gabinettiNuovi);
