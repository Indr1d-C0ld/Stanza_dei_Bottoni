<?php

declare(strict_types=1);

/**
 * Il catalogo dei verbi — la versione ridotta.
 *
 * Il bacino completo sta in docs/02-verbi.md e conta una sessantina di voci.
 * Qui ce n'è una ventina, scelti perché ognuno apra una strategia diversa e
 * nessuno sia il "sempre giusto". Crawford ne pubblicò otto su quattordici
 * progettati e dedica pagine a spiegare che i verbi sono la cosa più importante
 * del design: "un buon insieme permette al giocatore di fare tutto ciò di cui
 * ha bisogno; uno cattivo lo confonde con l'arbitrarietà o lo imprigiona in una
 * camicia di forza".
 *
 * Per ogni verbo:
 *   dominio        soc | eco | info | int | mil | nuc
 *   maturazione    [min, max] in tick da una settimana
 *   impronta       0..1 quanto rumore fa mentre e' in volo
 *   attribuzione   0..1 quanto e' facile risalire al mandante DOPO il fatto
 *   danno          Hurt di base a intensita' piena; NEGATIVO = aiuto
 *   gradino        posizione sulla scala di escalation (0 = negabile, 9 = guerra)
 */

return [
    // --- SOC: diplomazia e societa' -----------------------------------------
    'emissario' => [
        'dominio' => 'soc', 'maturazione' => [1, 2], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => -10, 'gradino' => 1,
        'attesa' => 26,
    ],
    'trattato' => [
        'dominio' => 'soc', 'maturazione' => [4, 12], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => -45, 'gradino' => 1,
        'attesa' => 104,
    ],
    'condanna_pubblica' => [
        'dominio' => 'soc', 'maturazione' => [1, 1], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => 10, 'gradino' => 2,
        'attesa' => 39,
    ],
    'mediazione' => [
        'dominio' => 'soc', 'maturazione' => [3, 6], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => -35, 'gradino' => 1,
        'attesa' => 52,
    ],

    // --- ECO: economia ------------------------------------------------------
    'aiuto_economico' => [
        'dominio' => 'eco', 'maturazione' => [2, 5], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => -40, 'gradino' => 1,
        'attesa' => 13,
    ],
    'investimenti' => [
        'dominio' => 'eco', 'maturazione' => [6, 14], 'impronta' => 0.9,
        'attribuzione' => 1.0, 'danno' => -30, 'gradino' => 0,
        'attesa' => 52,
    ],
    'restrizioni_commerciali' => [
        'dominio' => 'eco', 'maturazione' => [2, 4], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => 30, 'gradino' => 3,
        'attesa' => 78,
    ],
    'embargo' => [
        'dominio' => 'eco', 'maturazione' => [3, 6], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => 70, 'gradino' => 5,
        'attesa' => 156,
    ],

    // --- INFO: informazione e influenza -------------------------------------
    'disinformazione' => [
        'dominio' => 'info', 'maturazione' => [2, 4], 'impronta' => 0.25,
        'attribuzione' => 0.30, 'danno' => 30, 'gradino' => 0,
        'attesa' => 13,
    ],
    'finanziamento_opposizione' => [
        'dominio' => 'info', 'maturazione' => [6, 12], 'impronta' => 0.20,
        'attribuzione' => 0.35, 'danno' => 35, 'gradino' => 0,
        'attesa' => 52,
    ],

    // --- INT: operazioni coperte --------------------------------------------
    'sabotaggio' => [
        'dominio' => 'int', 'maturazione' => [3, 6], 'impronta' => 0.45,
        'attribuzione' => 0.40, 'danno' => 45, 'gradino' => 0,
        'attesa' => 26,
    ],
    'armare_insorti' => [
        'dominio' => 'int', 'maturazione' => [2, 5], 'impronta' => 0.50,
        'attribuzione' => 0.55, 'danno' => 60, 'gradino' => 0,
        'attesa' => 13,
    ],
    'destabilizzare' => [
        'dominio' => 'int', 'maturazione' => [6, 14], 'impronta' => 0.40,
        'attribuzione' => 0.45, 'danno' => 80, 'gradino' => 0,
        'attesa' => 52,
    ],
    'colpo_di_stato' => [
        'dominio' => 'int', 'maturazione' => [10, 20], 'impronta' => 0.55,
        'attribuzione' => 0.60, 'danno' => 100, 'gradino' => 0,
        'attesa' => 156,
    ],

    // --- MIL: militare ------------------------------------------------------
    'vendita_armi' => [
        'dominio' => 'mil', 'maturazione' => [3, 8], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => -40, 'gradino' => 1,
        'attesa' => 26,
    ],
    'dimostrazione_forza' => [
        'dominio' => 'mil', 'maturazione' => [1, 3], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => 30, 'gradino' => 7,
        'attesa' => 39,
    ],
    'invasione' => [
        'dominio' => 'mil', 'maturazione' => [6, 12], 'impronta' => 1.0,
        'attribuzione' => 1.0, 'danno' => 125, 'gradino' => 9, 'attesa' => 260,
    ],
    'strike' => [
        'dominio' => 'mil', 'maturazione' => [1, 1], 'impronta' => 1.0,
        'attribuzione' => 0.95, 'danno' => 85, 'gradino' => 8,
        'attesa' => 8,
    ],
];
