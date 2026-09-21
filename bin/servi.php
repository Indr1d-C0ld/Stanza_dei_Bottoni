<?php

declare(strict_types=1);

/**
 * Router per il server incorporato di PHP, SOLO per lo sviluppo locale.
 *
 *   php -S 127.0.0.1:8080 bin/servi.php
 *
 * In esercizio ci pensa Apache con deploy/apache-stanzadeibottoni.conf.
 */

$file = __DIR__ . '/../' . ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
if ($file !== '' && is_file($file) && !str_ends_with($file, '.php')) {
    return false;   // le risorse statiche le serve il server
}
require __DIR__ . '/../index.php';
