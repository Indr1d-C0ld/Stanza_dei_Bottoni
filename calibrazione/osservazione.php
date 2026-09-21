<?php

declare(strict_types=1);

/**
 * Profilo OSSERVAZIONE — tarato contro i tassi storici, anche a costo di
 * decenni in cui non succede niente.
 *
 * Bersagli di validazione (fonte: Crawford, "Balance of Power the Book",
 * che li ricava dal World Handbook of Political and Social Indicators):
 *
 *   insurrezioni significative .......... ~200 in 40 anni, 20% di successo
 *   cambi irregolari di esecutivo ....... 238 riusciti / 304 falliti (44%)
 *   cambi regolari di esecutivo ......... 1645 / 409 (80%)
 *   rivolte che rovesciano un governo ... ~1% di ~10.000
 *   guerra nucleare in 15 anni a vuoto .. mai
 *
 * Si valida su DISTRIBUZIONI, non su traiettorie, e per ensemble di semi.
 */

return [
    'profilo' => 'osservazione',

    'societa' => [
        'aspettativa_minima'  => 0.005,
        'inerzia_legittimita' => 0.90,
    ],

    'insurrezione' => [
        // Soglie originali di BoP, non compresse.
        'soglia_terrorismo' => 32.0,
        'soglia_guerriglia' => 2.0,
    ],

    'validazione' => [
        'tasso_successo_insurrezioni'   => 0.20,
        'tasso_successo_cambi_irreg'    => 0.44,
        'tasso_successo_cambi_regolari' => 0.80,
        'tasso_rivolte_efficaci'        => 0.01,
        'tolleranza'                    => 0.40,   // fattore, non punto percentuale
    ],
];
