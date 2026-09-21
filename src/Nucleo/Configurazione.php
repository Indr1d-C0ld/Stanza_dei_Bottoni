<?php

declare(strict_types=1);

namespace App\Nucleo;

use RuntimeException;

/**
 * Configurazione applicativa.
 *
 * Ordine di ricerca del file:
 *   1. variabile d'ambiente SDB_CONFIG
 *   2. /etc/stanzadeibottoni/config.php
 *   3. <progetto>/config/config.php          (sviluppo)
 *   4. <progetto>/config/config.example.php  (ripiego: solo segnaposto)
 *
 * In esercizio si usano le prime due: il file coi segreti sta FUORI dal
 * DocumentRoot e il web server non può servirlo nemmeno per sbaglio.
 */
final class Configurazione
{
    /** @var array<string,mixed> */
    private static array $dati = [];
    private static bool $caricata = false;

    public static function carica(string $radiceProgetto): void
    {
        $candidati = array_filter([
            getenv('SDB_CONFIG') ?: null,
            '/etc/stanzadeibottoni/config.php',
            $radiceProgetto . '/config/config.php',
            $radiceProgetto . '/config/config.example.php',
        ]);

        foreach ($candidati as $percorso) {
            if (is_file($percorso)) {
                $dati = require $percorso;
                if (!is_array($dati)) {
                    throw new RuntimeException("Configurazione non valida: $percorso");
                }
                self::$dati = $dati;
                self::$caricata = true;
                return;
            }
        }
        throw new RuntimeException('Nessun file di configurazione trovato.');
    }

    public static function leggi(string $chiave, mixed $ripiego = null): mixed
    {
        if (!self::$caricata) {
            throw new RuntimeException('Configurazione non caricata.');
        }
        $nodo = self::$dati;
        foreach (explode('.', $chiave) as $pezzo) {
            if (!is_array($nodo) || !array_key_exists($pezzo, $nodo)) {
                return $ripiego;
            }
            $nodo = $nodo[$pezzo];
        }
        return $nodo;
    }
}
