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

return $radiceProgetto;
