<?php

declare(strict_types=1);

/**
 * La taratura, le leve dell'arbitro, e il mondo che gira a vuoto.
 *
 * La prova sul mondo a vuoto e' lenta — dodici anni di simulazione — ma e'
 * l'unica che dice se una modifica ha spostato il carattere del mondo invece
 * che solo il pezzo che si stava toccando. E' successo: collegare il saldo
 * commerciale alla crescita ha portato i colpi di Stato da 11,9 a 18,1 l'anno,
 * e nessuna prova sul commercio se ne sarebbe accorta.
 */

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Simulazione\EsecutoreTick;

$radice = dirname(__DIR__);

Prove::gruppo('Taratura: i due profili si caricano e si sovrappongono');

foreach (['gioco', 'osservazione'] as $p) {
    $cal = Calibrazione::carica($radice, $p);
    Prove::che("il profilo $p si carica", $cal->profilo !== '');
    Prove::che("il profilo $p ha i tick per anno", $cal->numero('tempo.tick_per_anno', 0) > 0);
}
Prove::esplode('un profilo inventato viene rifiutato',
    static fn() => Calibrazione::carica($radice, 'inesistente'));

Prove::gruppo('Taratura: le leve dell\'arbitro si impongono e si tolgono');

Calibrazione::imponiLeve([]);
$prima = Calibrazione::carica($radice, 'gioco')->numero('commercio.morso', 0);

Calibrazione::imponiLeve(['commercio.morso' => 0.77]);
$dopo = Calibrazione::carica($radice, 'gioco')->numero('commercio.morso', 0);
Prove::vicino('la leva mossa vale', 0.77, $dopo, 0.0001);

Calibrazione::imponiLeve(['crisi.gradini.3' => 'gradino inventato']);
$g = (array) Calibrazione::carica($radice, 'gioco')->leggi('crisi.gradini', []);
Prove::uguale('una leva puo\' scendere dentro una struttura', 'gradino inventato', $g[3] ?? null);
Prove::che('e non distrugge quel che le sta accanto', ($g[4] ?? '') !== '');

Calibrazione::imponiLeve([]);
$tornata = Calibrazione::carica($radice, 'gioco')->numero('commercio.morso', 0);
Prove::vicino('togliendo la leva si torna al file', $prima, $tornata, 0.0001);

Prove::gruppo('Il tick a vuoto: dodici fasi su dodici, sempre');

$cal = Calibrazione::carica($radice, 'osservazione');
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$esecutore = new EsecutoreTick($cal, null, true, $mondo);

$fasiOk = true;
for ($t = 1; $t <= 5; $t++) {
    $esito = $esecutore->esegui($t, 20260921);
    if (count($esito) < 12) {
        $fasiOk = false;
    }
}
Prove::che('ogni tick esegue tutte e dodici le fasi', $fasiOk);
Prove::che('il mondo ha ancora tutte le nazioni', count($mondo->nazioni) === 189,
    (string) count($mondo->nazioni));

Prove::gruppo('Il mondo a vuoto: i tassi restano nella banda storica');

$pilIniziale = $mondo->pilTotale();
$anni = 12;
$perAnno = (int) $cal->numero('tempo.tick_per_anno', 52);
for ($t = 6; $t <= $anni * $perAnno; $t++) {
    $esecutore->esegui($t, 20260921);
}

$irregolari = 0;
$legittimita = 0.0;
foreach ($mondo->nazioni as $n) {
    $irregolari += $n->cambiIrregolari;
    $legittimita += $n->legittimita;
}
$perAnnoIrregolari = $irregolari / $anni;
$crescitaMondiale = ($mondo->pilTotale() / $pilIniziale) ** (1.0 / $anni) - 1.0;
$legittimitaMedia = $legittimita / count($mondo->nazioni);

// La banda e' quella di Realismo::FASCE, datata: 2,2-3,8 colpi riusciti
// l'anno (Cline Center, Powell & Thyne, 2000-2020 e anni Venti) piu' le
// rivoluzioni. Qui c'era 5-20, dai ~10 di Crawford — che erano giusti per
// il 1948-77 e che questo progetto ha smesso di usare in docs/26: la prova
// era rimasta indietro rispetto al metro.
[$minIrr, $maxIrr] = App\Simulazione\Realismo::FASCE['cambi_irregolari'];
Prove::fra('cambi irregolari per anno', (float) $minIrr, (float) $maxIrr, $perAnnoIrregolari);
Prove::fra('crescita mondiale annua', 0.01, 0.06, $crescitaMondiale);
Prove::fra('legittimita\' media', 35.0, 70.0, $legittimitaMedia);
Prove::che('nessuna nazione e\' sparita', count($mondo->nazioni) === 189);

$vivi = 0;
foreach ($mondo->nazioni as $n) {
    if ($n->pil > 0 && $n->popolazione > 0) {
        $vivi++;
    }
}
Prove::uguale('tutte le economie sono ancora sopra zero', 189, $vivi);
