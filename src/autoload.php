<?php

declare(strict_types=1);

/** Autoloader artigianale, PSR-4 su App\ -> src/. Nessun composer. */
spl_autoload_register(static function (string $classe): void {
    $prefisso = 'App\\';
    if (!str_starts_with($classe, $prefisso)) {
        return;
    }
    $relativo = substr($classe, strlen($prefisso));
    $percorso = __DIR__ . '/' . str_replace('\\', '/', $relativo) . '.php';
    if (is_file($percorso)) {
        require $percorso;
    }
});
