<?php

declare(strict_types=1);

/**
 * Crea il mondo nella base dati a partire dal seme, al tick zero.
 *
 * Da lanciare UNA VOLTA dopo deploy/00-avvio.sh. Idempotente sull'anagrafica,
 * ma rifiuta di sovrascrivere un mondo gia' avviato senza --ricomincia.
 *
 *   php bin/avvia_mondo.php
 *   php bin/avvia_mondo.php --ricomincia
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Deposito;
use App\Dati\Gabinetti;
use App\Dati\Mondo;
use App\Gioco\Servizi;
use App\Nucleo\Calibrazione;
use App\Nucleo\Caso;
use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;

$opz = getopt('', ['ricomincia']);
$db  = new Basedati((array) Configurazione::leggi('db', []));
$dep = new Deposito($db);

$esistente = $dep->ultimoTick();
if ($esistente > 0 && !array_key_exists('ricomincia', $opz)) {
    fwrite(STDERR, "Esiste gia' un mondo al tick $esistente. Usa --ricomincia per azzerarlo.\n");
    exit(1);
}

// Chi sedeva dove: dopo il riavvio torna al suo posto (docs/33). Prima il
// riavvio cancellava le poltrone e i giocatori si ritrovavano in piedi.
$seduti = [];
if (array_key_exists('ricomincia', $opz)) {
    $seduti = $db->esegui(
        'SELECT n.codice, p.ruolo, p.giocatore_id FROM sdb_poltrona p
           JOIN sdb_nazione n ON n.id = p.nazione_id WHERE p.giocatore_id IS NOT NULL')->fetchAll();
}

if (array_key_exists('ricomincia', $opz)) {
    echo "Azzero il mondo esistente...\n";

    // Cosa si cancella e in che ordine sta in Deposito::azzeraMondo(), che
    // usa anche la prova di fedelta' della persistenza.
    $dep->azzeraMondo();
}

echo "Anagrafica...\n";
$dep->preparaAnagrafica($radice . '/db/seed/nazioni.csv');

echo "Costruisco il mondo dal seme...\n";
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$mondo->tick = 0;

// I gabinetti delle potenze giocabili, subito. Li costruiva la fase 10 al
// primo tick, e un mondo appena riavviato restava fino all'ora pari dopo senza
// una poltrona su cui sedersi (docs/33). Si costruiscono col caso del primo
// tick, che e' quello con cui li avrebbe costruiti la fase 10.
$casoPrimo = new Caso(Caso::semeDelTick((int) Configurazione::leggi('mondo.seme', 1), 1));
$bacini = require $radice . '/db/seed/nomi-personaggi.php';
foreach ($mondo->elenco() as $n) {
    if ($n->giocabile && !isset($mondo->gabinetti[$n->iso3])) {
        $mondo->gabinetti[$n->iso3] = Gabinetti::perNazione($n, $bacini, $casoPrimo, 0);
    }
}

$scenario = (string) Configurazione::leggi('mondo.scenario', 'base');
$dep->salva($mondo, 0, dataDiGioco(0));

// E chi sedeva torna al suo posto, con le sue agende private nuove.
if ($seduti !== []) {
    $servizi = new Servizi($db, Calibrazione::carica($radice,
        (string) Configurazione::leggi('mondo.profilo', 'osservazione')), $radice);
    foreach ($seduti as $s) {
        $id = (int) $db->esegui(
            'SELECT p.id FROM sdb_poltrona p JOIN sdb_nazione n ON n.id = p.nazione_id
              WHERE n.codice = ? AND p.ruolo = ?', [$s['codice'], $s['ruolo']])->fetchColumn();
        $esito = $id > 0 ? $servizi->scrivania->occupa((int) $s['giocatore_id'], $id, 0) : [false, 'poltrona sparita'];
        printf("  %s, %s: %s\n", $s['codice'], $s['ruolo'], $esito[0] ? 'di nuovo al suo posto' : $esito[1]);
    }
}

printf("Mondo avviato: %d nazioni, %d coppie di relazione, %d gabinetti. Scenario «%s».\n",
    count($mondo->nazioni), $mondo->relazioni->quante(), count($mondo->gabinetti), $scenario);
echo "\n    Ora si puo' far girare il tempo:  php bin/tick.php\n\n";

/** Un tick vale una settimana di gioco, a partire dalla data di divergenza. */
function dataDiGioco(int $tick): string
{
    $inizio = new DateTimeImmutable('2026-01-05');
    return $inizio->modify('+' . ($tick * 7) . ' days')->format('Y-m-d');
}
