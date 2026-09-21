<?php

declare(strict_types=1);

/**
 * MODELLO di configurazione — solo segnaposto, nessun segreto.
 *
 * Ordine di ricerca: $SDB_CONFIG, /etc/stanzadeibottoni/config.php,
 * <progetto>/config/config.php.
 */

return [
    'app' => [
        'name'     => 'Stanza dei Bottoni',
        'env'      => 'development',
        'debug'    => true,
        'timezone' => 'Europe/Rome',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'sdb_stanza',
        'user'    => 'sdb_stanza',
        'pass'    => 'CAMBIAMI',
        'charset' => 'utf8mb4',
        'prefix'  => 'sdb_',
    ],

    'mondo' => [
        // Profilo di calibrazione: 'gioco' oppure 'osservazione'.
        'profilo'     => 'gioco',
        // Seme radice del mondo. Ogni tick ne deriva il proprio.
        'seme'        => 20260920,
        // Durata del tick in secondi reali (7200 = 2 ore = 1 settimana di gioco).
        'tick_reale'  => 7200,
        'scenario'    => 'base',
    ],
];
