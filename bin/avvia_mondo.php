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

    // Cosa si cancella e in che ordine sta in Deposito::azzeraMondo(), che
    // usa anche la prova di fedelta' della persistenza.
    $dep->azzeraMondo();
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
