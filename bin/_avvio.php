<?php

declare(strict_types=1);

/** Avvio comune degli script CLI di bin/. */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('STANZA_DEI_BOTTONI', true);

$radiceProgetto = dirname(__DIR__);

require $radiceProgetto . '/src/autoload.php';
require $radiceProgetto . '/src/Supporto/aiutanti.php';

use App\Nucleo\Configurazione;

try {
    Configurazione::carica($radiceProgetto);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Errore di configurazione: ' . $e->getMessage() . "\n");
    exit(1);
}

date_default_timezone_set((string) Configurazione::leggi('app.timezone', 'Europe/Rome'));
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

// Un'eccezione che nessuno raccoglie diventa UNA riga, datata, col punto
// d'origine — invece di una traccia di pila stampata due volte nel registro
// del cron. Il caso vero che l'ha fatto nascere: il 22/09/2026 MariaDB ha
// rifiutato una connessione per esaurimento («Too many connections», il server
// ospita molti progetti), e bin/posta.php e' morto riempiendo il registro. Non
// era un guasto — il giro successivo e' andato — ma un registro rumoroso nasconde
// i guasti veri.
set_exception_handler(static function (\Throwable $e): void {
    $radice = dirname(__DIR__) . '/';
    fwrite(STDERR, sprintf("%s  %s: %s: %s (%s:%d)\n",
        date('Y-m-d H:i:s'), basename((string) ($_SERVER['argv'][0] ?? 'bin')),
        (new \ReflectionClass($e))->getShortName(), $e->getMessage(),
        str_replace($radice, '', $e->getFile()), $e->getLine()));
    exit(1);
});

return $radiceProgetto;
