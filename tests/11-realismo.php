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

Prove::gruppo('Anche il profilo che si gioca davvero sta nei tassi del mondo vero');

// Lo strumento bin/realismo.php misura «osservazione», che e' il profilo di
// validazione. Ma il mondo vivo gira su «gioco», e per anni nessuno dei due ha
// toccato il rischio di colpo di Stato: entrambi ereditavano 1,6 da base.php e
// facevano tredici colpi l'anno, cioe' il tasso degli anni Sessanta in un mondo
// seminato con dati del 2024. Auditare solo il profilo che nessuno gioca e' un
// audit a meta'.
$calGioco = Calibrazione::carica($radice, 'gioco');
$mGioco   = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$rGioco   = Realismo::misura(
    $mGioco, new EsecutoreTick($calGioco, null, true, $mGioco), $anni, $tickAnno, 1);

[$minIrr, $maxIrr] = Realismo::FASCE['cambi_irregolari'];
Prove::fra("anche in «gioco» i cambi irregolari stanno fra $minIrr e $maxIrr l'anno",
    (float) $minIrr, (float) $maxIrr, $rGioco['misure']['cambi_irregolari']);

// E il tetto al rischio vive in base.php, non duplicato nei profili: due copie
// dello stesso numero divergono, e il modo in cui divergono e' che una si
// dimentica.
foreach (['gioco', 'osservazione'] as $p) {
    Prove::che("il profilo $p non duplica il rischio di colpo di Stato",
        !str_contains((string) file_get_contents($radice . "/calibrazione/$p.php"),
            "'rischio_massimo_anno'"));
}

Prove::gruppo('Nessun riferimento datato torna di soppiatto');

// Il World Handbook of Political and Social Indicators copre il 1948-77. E' la
// fonte da cui Crawford ricava i «~10 cambi irregolari l'anno» — giusti, per il
// 1968 — e i quattro tassi del blocco 'validazione', che nessuno leggeva e che
// pure si presentavano come la definizione di «corretto». Puo' essere CITATO
// (la sua storia e' istruttiva) ma non puo' tornare a fissare un numero.
$chiaviMorte = ['tasso_successo_insurrezioni', 'tasso_successo_cambi_irreg',
                'tasso_successo_cambi_regolari', 'tasso_rivolte_efficaci'];
foreach (['base', 'gioco', 'osservazione'] as $p) {
    $testo = (string) file_get_contents($radice . "/calibrazione/$p.php");
    foreach ($chiaviMorte as $k) {
        // La chiave puo' comparire dentro un commento che ne racconta la
        // rimozione; quel che non deve tornare e' la chiave ATTIVA.
        Prove::che("in $p.php la chiave $k non e' attiva",
            !preg_match("/^\s*'" . preg_quote($k, '/') . "'\s*=>/m", $testo));
    }
}

// Ogni fascia dichiara la propria fonte, e le fonti empiriche devono dire di
// quale mondo parlano: una fonte non basta che sia seria.
foreach (Realismo::FASCE as $chiave => [$min, $max, $unita, $quando, $fonte]) {
    Prove::che("la fascia $chiave cita una fonte", trim($fonte) !== '');
}

Prove::gruppo('Il seme dichiara la propria data');

// Un riferimento che non si puo' datare non si puo' dichiarare scaduto: e' la
// ragione per cui il seme porta la propria provenienza accanto.
$prov = $radice . '/db/seed/PROVENIENZA.md';
Prove::che('il seme ha un file di provenienza', is_file($prov));
Prove::che('e la provenienza porta una data in formato italiano',
    (bool) preg_match('#\b\d{2}/\d{2}/\d{4}\b#', (string) @file_get_contents($prov)));
Prove::che('e l\'importatore la riscrive da solo',
    str_contains((string) file_get_contents($radice . '/bin/importa_factbook.php'),
        'PROVENIENZA.md'));

Prove::gruppo('I trattati vengono dal Correlates of War, non dalle simpatie');

$mondoT = Mondo::daSeme($radice . '/db/seed/nazioni.csv');

// Fatti verificabili, non numeri tondi: sono il controllo che l'importazione
// ha preso i trattati VERI e non una funzione dell'affinita'.
$obb = static fn (string $a, string $b): int => $mondoT->relazioni->fra($a, $b)?->obbligo ?? -1;

Prove::che('gli Stati Uniti garantiscono la Germania al gradino nucleare',
    $obb('USA', 'DEU') === 128, 'obbligo ' . $obb('USA', 'DEU'));
Prove::che('ma la Germania garantisce gli Stati Uniti solo al convenzionale',
    $obb('DEU', 'USA') === 96, 'l\'impegno e\' asimmetrico: conta chi ha l\'atomica');
Prove::che('gli Stati Uniti NON hanno un patto di difesa con Israele',
    $obb('USA', 'ISR') <= 0,
    'e\' il caso che la vecchia formula sull\'affinita\' sbagliava di sicuro');
Prove::che('la Cina ha un patto con la Corea del Nord',
    $obb('CHN', 'PRK') >= 96, 'trattato del 1961, tuttora in vigore');
Prove::che('la Finlandia e\' entrata nella NATO dopo il dataset',
    $obb('USA', 'FIN') >= 96, 'COW arriva al 2012, la Finlandia e\' del 2023');
Prove::che('e nessuno garantisce Taiwan',
    $obb('USA', 'TWN') <= 0, 'il trattato fu denunciato nel 1980');

Prove::gruppo('Un trattato regge al raffreddamento, e le garanzie scattano');

// La fase 06 ricalcola l'obbligo dall'affinita' e lo abbassa col 2% per tick:
// su quindici anni la denuncia era certa, e la struttura di alleanze passava
// da 2.763 patti di difesa a DICIASSETTE. Nessuna garanzia veniva mai messa
// alla prova, perche' quando arrivava una guerra non c'erano piu' trattati.
$calT = Calibrazione::carica($radice, 'gioco');
$mT   = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$eT   = new EsecutoreTick($calT, null, true, $mT);
for ($t = 1; $t <= 15 * 52; $t++) { $mT->tick = $t; $eT->esegui($t, 1); }

$difesa = 0;
foreach ($mT->relazioni->tutte() as $r) {
    if ($r->obbligo >= 96) { $difesa++; }
}
Prove::che('dopo quindici anni le alleanze di difesa esistono ancora',
    $difesa > 500, sprintf('%d patti (prima della correzione ne restavano 17)', $difesa));

// E il meccanismo deve poter scattare: si mette una guerra contro un difensore
// che i trattati proteggono davvero, e si guarda se qualcuno viene chiamato.
$mG = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$garanti = 0;
foreach ($mG->elenco() as $x) {
    if ($x->iso3 === 'EST' || $x->iso3 === 'RUS') { continue; }
    $r = $mG->relazioni->fra($x->iso3, 'EST');
    if ($r !== null && $r->obbligo >= 64) { $garanti++; }
}
Prove::che('un membro della NATO ha molti garanti a cui rispondere',
    $garanti > 10, sprintf('l\'Estonia ne ha %d', $garanti));

Prove::gruppo('La disuguaglianza: quel che sente il cittadino mediano');

$mondoD = Mondo::daSeme($radice . '/db/seed/nazioni.csv');

// Il rapporto fra mediana e media NON e' un coefficiente scelto: si deriva dal
// Gini assumendo redditi lognormali, e si verifica contro un dato reale.
// Per gli Stati Uniti il conto da' 0,74; il rapporto vero fra reddito familiare
// mediano (~75 mila) e medio (~106 mila) e' 0,71.
Prove::vicino('il conto sugli Stati Uniti torna col dato vero',
    0.74, $mondoD->nazioni['USA']->quotaMediana(), 0.03);

// E l'inversa della normale, su cui poggia tutto, deve dare il numero che sta
// su ogni tavola statistica.
$riflessa = new ReflectionMethod(App\Dati\Nazione::class, 'phiInversa');
$riflessa->setAccessible(true);
Prove::vicino('l\'inversa della normale e\' quella giusta',
    1.9600, $riflessa->invoke(null, 0.975), 0.0005);

// L'ordinamento deve essere quello del mondo vero.
$q = static fn (string $i): float => $mondoD->nazioni[$i]->quotaMediana();
Prove::che('il cittadino mediano sudafricano sta molto sotto la media',
    $q('ZAF') < 0.65, sprintf('%.2f', $q('ZAF')));
Prove::che('quello norvegese quasi in pari',
    $q('NOR') > 0.85, sprintf('%.2f', $q('NOR')));

// Il caso che spiega perche' serve: due paesi con media simile e vite diverse.
$bra = $mondoD->nazioni['BRA'];
$tha = $mondoD->nazioni['THA'];
Prove::che('a medie vicine, il thailandese mediano sta meglio del brasiliano',
    $tha->pilProCapite * $tha->quotaMediana() > $bra->pilProCapite * $bra->quotaMediana() * 1.3,
    sprintf('THA %s contro BRA %s, con medie %s e %s',
        number_format($tha->pilProCapite * $tha->quotaMediana()),
        number_format($bra->pilProCapite * $bra->quotaMediana()),
        number_format($tha->pilProCapite), number_format($bra->pilProCapite)));

// E deve CONTARE: la qualita' della vita era una variabile scritta, salvata,
// mostrata in pagina e non letta da nessun meccanismo. Adesso e' il quarto
// predittore di PITF, ed e' la via per cui la disuguaglianza entra nel modello.
$fase05 = (string) file_get_contents($radice . '/src/Simulazione/Fasi/Fase05SicurezzaInterna.php');
Prove::che('la qualita\' della vita pesa sull\'instabilita\'',
    str_contains($fase05, 'pesoQualitaVita'),
    'senza, la disuguaglianza cambierebbe solo un numero mostrato');
$fase04 = (string) file_get_contents($radice . '/src/Simulazione/Fasi/Fase04Societa.php');
Prove::che('e si calcola sul consumo mediano, non su quello medio',
    str_contains($fase04, 'consumoMediano()'));

Prove::gruppo('La disuguaglianza orizzontale: i gruppi, non gli individui');

$mondoE = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$esc = static fn (string $i): float => $mondoE->nazioni[$i]->esclusioneEtnica;

// Il dato deve dire quel che il mondo vero dice. La Siria e' il caso di
// scuola: una minoranza al potere sopra una maggioranza esclusa.
Prove::che('in Siria la popolazione esclusa dal potere e\' la grande maggioranza',
    $esc('SYR') > 0.8, sprintf('%.0f%%', $esc('SYR') * 100));
Prove::che('in Ruanda pure', $esc('RWA') > 0.7, sprintf('%.0f%%', $esc('RWA') * 100));
Prove::che('mentre in Giappone l\'esclusione etnica e\' trascurabile',
    $esc('JPN') < 0.05, sprintf('%.1f%%', $esc('JPN') * 100));

// E la frammentazione conta oltre alla taglia: il Myanmar ha meno esclusi
// della Siria ma in molti piu' gruppi, e undici fronti sono peggio di quattro.
Prove::che('il Myanmar ha molti gruppi esclusi',
    $mondoE->nazioni['MMR']->gruppiEsclusi >= 8,
    sprintf('%d gruppi', $mondoE->nazioni['MMR']->gruppiEsclusi));

// L'esclusione deve CONTARE nel reclutamento, che e' il canale per cui
// l'evidenza esiste — non nei colpi di Stato, dove non e' stata misurata.
$f05 = (string) file_get_contents($radice . '/src/Simulazione/Fasi/Fase05SicurezzaInterna.php');
Prove::che('l\'esclusione pesa sul reclutamento insurrezionale',
    str_contains($f05, 'pesoEsclusione'));

// E la distribuzione dev'essere CONCENTRATA: l'esclusione e' un fatto di
// pochi paesi, non una proprieta' diffusa. Se la mediana fosse alta il dato
// avrebbe perso il proprio potere discriminante.
$tutti = [];
foreach ($mondoE->elenco() as $n) { $tutti[] = $n->esclusioneEtnica; }
sort($tutti);
Prove::fra('la mediana dell\'esclusione resta bassa', 0.0, 0.15, $tutti[intdiv(count($tutti), 2)]);
Prove::che('ma la coda alta esiste', end($tutti) > 0.7);
