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
use App\Dati\Mondo;
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

if (array_key_exists('ricomincia', $opz)) {
    echo "Azzero il mondo esistente...\n";

    // L'ORDINE CONTA, e prima non contava: sdb_conoscenza ha una chiave
    // esterna su sdb_evento, e cancellare gli eventi per primi faceva fallire
    // l'intero riavvio con una violazione di vincolo. Lo strumento non aveva
    // mai funzionato su un mondo che avesse prodotto anche un solo evento —
    // cioe' su qualunque mondo vissuto. Trovato riavviando il mondo vero.
    //
    // Si cancella dai FIGLI verso i PADRI. Le dipendenze vere, lette dallo
    // schema:
    //
    //   sdb_evento   ← sdb_conoscenza
    //   sdb_nazione  ← sdb_capacita_intel, sdb_gabinetto, sdb_nazione_stato,
    //                  sdb_relazione
    //
    // L'anagrafica (sdb_nazione, sdb_regione, sdb_ideologia) NON si tocca: la
    // ricostruisce preparaAnagrafica() qui sotto, e le poltrone vi si
    // appoggiano.
    $ordine = [
        // prima i figli
        'sdb_conoscenza',
        'sdb_rapporto',
        // poi il resto dello stato del mondo
        'sdb_evento',
        'sdb_nazione_stato',
        'sdb_mondo_stato',
        'sdb_relazione',
        'sdb_notizia',
        'sdb_guerra',
        'sdb_poltrona',
        'sdb_fazione',
        'sdb_gabinetto',
        'sdb_tick_log',
        'sdb_capacita_intel',
        'sdb_presenza_intel',
        'sdb_assenza_fatto',
        'sdb_ordine',
        'sdb_punteggio',
    ];
    foreach ($ordine as $t) {
        try {
            $db->esegui("DELETE FROM $t");
        } catch (\Throwable $e) {
            // Una tabella che non c'e' piu' non e' un motivo per non
            // ripartire: si dice e si tira avanti.
            fwrite(STDERR, "  (salto $t: " . $e->getMessage() . ")\n");
        }
    }
}

echo "Anagrafica...\n";
$dep->preparaAnagrafica($radice . '/db/seed/nazioni.csv');

echo "Costruisco il mondo dal seme...\n";
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$mondo->tick = 0;

$scenario = (string) Configurazione::leggi('mondo.scenario', 'base');
$dep->salva($mondo, 0, dataDiGioco(0));

printf("Mondo avviato: %d nazioni, %d coppie di relazione, %d gabinetti. Scenario «%s».\n",
    count($mondo->nazioni), $mondo->relazioni->quante(), count($mondo->gabinetti), $scenario);
echo "\n    Ora si puo' far girare il tempo:  php bin/tick.php\n\n";

/** Un tick vale una settimana di gioco, a partire dalla data di divergenza. */
function dataDiGioco(int $tick): string
{
    $inizio = new DateTimeImmutable('2026-01-05');
    return $inizio->modify('+' . ($tick * 7) . ' days')->format('Y-m-d');
}
