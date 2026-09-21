<?php

declare(strict_types=1);

/**
 * Esegue un tick del mondo.
 *
 *   php bin/tick.php                 esegue il tick successivo
 *   php bin/tick.php --tick=42       esegue un tick preciso
 *   php bin/tick.php --a-vuoto       non tocca la base dati (collaudo)
 *   php bin/tick.php --profilo=osservazione
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Deposito;
use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;
use App\Nucleo\Calendario;
use App\Nucleo\Configurazione;
use App\Simulazione\EsecutoreTick;

$opzioni = getopt('', ['tick::', 'a-vuoto', 'profilo::', 'silenzioso']);

$aVuoto     = array_key_exists('a-vuoto', $opzioni);
$silenzioso = array_key_exists('silenzioso', $opzioni);
$profilo    = (string) ($opzioni['profilo'] ?? Configurazione::leggi('mondo.profilo', 'gioco'));
$tick       = isset($opzioni['tick']) ? (int) $opzioni['tick'] : 1;
$semeRadice = (int) Configurazione::leggi('mondo.seme', 1);

// Il database prima della taratura, e non per capriccio: le leve che l'arbitro
// ha mosso a mondo acceso stanno li' dentro, e vanno imposte PRIMA che i file
// di calibrazione vengano letti. Altrimenti il tick girerebbe con numeri
// diversi da quelli che il banco dell'arbitro mostra.
$db = null;
$deposito = null;
if (!$aVuoto) {
    /** @var array<string,mixed> $impostazioni */
    $impostazioni = (array) Configurazione::leggi('db', []);
    $db = new Basedati($impostazioni);
    $deposito = new Deposito($db);
    // Senza --tick si prosegue da dove si era arrivati: e' il caso del cron.
    if (!isset($opzioni['tick'])) {
        $tick = $deposito->ultimoTick() + 1;
    }
    Calibrazione::imponiLeve((new \App\Gioco\Arbitrio($db))->leveImposte());
}

try {
    $calibrazione = Calibrazione::carica($radice, $profilo);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Errore di calibrazione: ' . $e->getMessage() . "\n");
    exit(1);
}

if (!$silenzioso) {
    printf(
        "Stanza dei Bottoni — tick %d, profilo %s@%s%s\n\n",
        $tick,
        $calibrazione->profilo,
        $calibrazione->versione,
        $aVuoto ? ', A VUOTO (nessuna scrittura)' : '',
    );
}

// Anche il tick a vuoto ha bisogno di un mondo su cui girare: senza, ogni
// fase si limita a dichiararsi non implementata.
$mondo = null;
$seme = $radice . '/db/seed/nazioni.csv';
if (is_file($seme)) {
    $mondo = App\Dati\Mondo::daSeme($seme);
    // La topologia viene dal seme, lo stato dalla base dati: cosi' le due non
    // possono divergere in silenzio.
    if ($deposito !== null && !$deposito->ripristina($mondo, $tick - 1)) {
        fwrite(STDERR, "Nessuno stato al tick " . ($tick - 1) . ". Lancia prima bin/avvia_mondo.php\n");
        exit(1);
    }
    $mondo->tick = $tick;
}

$avvio = hrtime(true);
$esecutore = new EsecutoreTick($calibrazione, $db, $aVuoto, $mondo);

try {
    $resoconto = $esecutore->esegui(
        $tick,
        $semeRadice,
        $silenzioso ? null : static function (array $riga): void {
            printf(
                "  %s  %s %7.2f ms  %s\n",
                $riga['codice'],
                mb_str_pad((string) $riga['nome'], 34),
                $riga['durata'],
                $riga['saltata'] ? '· ' . $riga['esito'] : $riga['esito'],
            );
        },
    );
} catch (\Throwable $e) {
    fwrite(STDERR, "\nTick interrotto: " . $e->getMessage() . "\n");
    exit(2);
}

if ($deposito !== null && $mondo !== null) {
    $deposito->salva($mondo, $tick, Calendario::tickIso($tick));
}

$eseguite = count(array_filter($resoconto, static fn(array $r): bool => !$r['saltata']));

// Gli avvisi ai giocatori stanno FUORI dalle fasi, e apposta: le dodici fasi
// sono il mondo, e il mondo non sa che esiste la posta elettronica. Questo e'
// il gioco che parla ai suoi giocatori, ed e' un'altra cosa.
$avvisati = null;
if ($db !== null && !$aVuoto) {
    try {
        $avvisati = (new \App\Gioco\Avvisi($db))->manda($tick);
    } catch (\Throwable $e) {
        // Un avviso che non parte non deve far fallire un tick: il mondo e'
        // gia' stato scritto, e perderlo per un problema di posta sarebbe
        // sproporzionato.
        fwrite(STDERR, 'avvisi: ' . $e->getMessage() . "\n");
    }
}

if ($silenzioso) {
    // Una riga sola, ma UNA RIGA C'E'. Un registro che resta vuoto non dice
    // "e' andato tutto bene": dice la stessa identica cosa di un lavoro che non
    // e' mai partito, e quei due casi devono potersi distinguere a colpo d'occhio.
    $riassunto = [];
    foreach ($resoconto as $r) {
        if (!$r['saltata'] && $r['esito'] !== '—') {
            $riassunto[] = $r['esito'];
        }
    }
    printf("%s  tick %d (%s)  %d/%d fasi  %.1fs  %s\n",
        date('Y-m-d H:i:s'),
        $tick,
        $mondo !== null ? Calendario::tick($tick) : '?',
        $eseguite, count($resoconto),
        // Il tempo vero, non la somma delle fasi: il grosso se ne va nella
        // scrittura, che avviene dopo.
        (hrtime(true) - $avvio) / 1_000_000_000,
        implode(' · ', array_slice($riassunto, 0, 5))
            . ($avvisati !== null && $avvisati['scritti'] > 0
                ? sprintf(' · avvisati %d giocatori', $avvisati['scritti']) : ''),
    );
} else {
    printf("\n%d fasi su %d eseguite.\n", $eseguite, count($resoconto));
}
