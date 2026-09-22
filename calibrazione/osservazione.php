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
 *
 * ATTENZIONE A UNA COSA CHE NON E' OVVIA: quei bersagli non sono «storici»,
 * sono DATATI. Il World Handbook copre il 1948-1977, e Crawford scriveva nel
 * 1985. I suoi ~10 cambi irregolari l'anno coincidono con gli anni Sessanta e
 * Settanta quasi alla cifra — 103 colpi di Stato riusciti negli anni '60, 95
 * negli anni '70, secondo il Cline Center Coup d'Etat Project e il dataset
 * Powell & Thyne. Ma dal 2000 il mondo ne fa 2,2 l'anno, e negli anni Venti
 * circa 3,8.
 *
 * Il nostro mondo parte da dati 2024-2025 e il calendario comincia il 5
 * gennaio 2026: un mondo del 2026 che fa dodici colpi di Stato l'anno non e'
 * il 2026, e' il 1968. Dove i due riferimenti divergono vince quello
 * contemporaneo, perche' e' il mondo che stiamo seminando.
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

    // NOTA: il rischio di colpo di Stato NON si sovrascrive qui. Per anni
    // nessuno dei due profili toccava quel blocco: entrambi ereditavano 1,6 da
    // base.php e facevano quasi lo stesso numero di colpi (13,2 contro 11,8).
    // La distinzione fra i profili, su questa dimensione, era solo nominale —
    // e il profilo «tarato sui tassi storici» non era mai stato tarato su
    // nessun tasso. Adesso base.php porta 0,25, cioe' il tasso contemporaneo,
    // e qui non serve deviare da niente.

    'validazione' => [
        'tasso_successo_insurrezioni'   => 0.20,
        'tasso_successo_cambi_irreg'    => 0.44,
        'tasso_successo_cambi_regolari' => 0.80,
        'tasso_rivolte_efficaci'        => 0.01,
        'tolleranza'                    => 0.40,   // fattore, non punto percentuale
    ],
];
