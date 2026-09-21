<?php

declare(strict_types=1);

/**
 * Quel che il Factbook non dice, e che noi dobbiamo dire lo stesso.
 *
 * Il tipo di governo del World Factbook è una descrizione giuridica: la Russia
 * vi compare come "semi-presidential federation" e l'Ucraina pure. Derivandone
 * l'orientamento politico, i due paesi risultano amici all'83%. Non va bene.
 *
 * Questa tabella è FABBRICATA e dichiarata tale, esattamente come l'indice di
 * maturità istituzionale che Chris Crawford confessò di aver inventato ("mi
 * sono affidato alla mia vasta conoscenza degli affari mondiali — ehm! — e ho
 * compiuto un gioco di prestigio"). È un atto d'autore, è discutibile, ed è
 * versionata apposta perché la si possa discutere.
 *
 * Va sostituita, quando avremo integrato le fonti, con V-Dem per gli
 * orientamenti e Correlates of War per i rapporti bilaterali.
 */

return [

    /**
     * Posizione sull'asse politico, -128 .. +128, dove la formula automatica
     * prende un abbaglio. Non è "buoni e cattivi": è quanto un governo si
     * riconosce nel blocco liberale o in quello autoritario, perché è questo
     * che determina con chi tende ad allinearsi.
     */
    'orientamenti' => [
        'RUS' => -60, 'BLR' => -75, 'PRK' => -110, 'CHN' => -85, 'IRN' => -70,
        'SYR' => -65, 'VEN' => -60, 'CUB' => -80, 'NIC' => -60, 'ERI' => -75,
        'TKM' => -70, 'TJK' => -55, 'UZB' => -45, 'AZE' => -50, 'KAZ' => -35,
        'EGY' => -35, 'SAU' => -40, 'ARE' => -30, 'QAT' => -20, 'TUR' => -15,
        'HUN' =>  10, 'SRB' => -15, 'MMR' => -70, 'LAO' => -70, 'VNM' => -55,
        'AFG' => -70, 'ZWE' => -50, 'SDN' => -55, 'MLI' => -45, 'BFA' => -45,
        'NER' => -40, 'GNQ' => -60, 'RWA' => -30, 'ETH' => -25, 'PAK' => -10,
        'IND' =>  20, 'BRA' =>  25, 'ZAF' =>  15, 'IDN' =>  20, 'MEX' =>  30,
        'ISR' =>  50, 'UKR' =>  45, 'GEO' =>  25, 'MDA' =>  35, 'ARM' =>  -5,
    ],

    /**
     * Postura nucleare, sulla scala a sette livelli del "quadrante destro"
     * della città di Shadow President: 1 nessun nucleare · 2 centrale civile ·
     * 3 programma militare avviato · 4 ordigno provato · 5 gittata regionale ·
     * 6 macro-regionale · 7 globale.
     *
     * Questa non è fabbricata, è pubblica: sono gli Stati dotati e quelli con
     * un ciclo civile significativo.
     */
    'postura_nucleare' => [
        'USA' => 7, 'RUS' => 7, 'CHN' => 7,
        'FRA' => 6, 'GBR' => 6, 'IND' => 6,
        'PAK' => 5, 'ISR' => 5, 'PRK' => 5,
        'IRN' => 3,
        'JPN' => 2, 'DEU' => 2, 'KOR' => 2, 'CAN' => 2, 'BRA' => 2, 'ARG' => 2,
        'ZAF' => 2, 'UKR' => 2, 'ESP' => 2, 'BEL' => 2, 'CZE' => 2, 'FIN' => 2,
        'SWE' => 2, 'CHE' => 2, 'NLD' => 2, 'HUN' => 2, 'MEX' => 2, 'ROU' => 2,
        'SVK' => 2, 'SVN' => 2, 'BGR' => 2, 'ARE' => 2, 'BLR' => 2, 'TUR' => 2,
        'EGY' => 2, 'BGD' => 2,
    ],

    /**
     * Moltiplicatori della capacità di intelligence, dove il peso economico è
     * una misura ingannevole. Israele e il Regno Unito pesano poco e vedono
     * molto; il contrario vale per parecchi paesi grandi e distratti.
     *
     * [FABBRICATO] come tutto il resto di questo file.
     */
    'capacita_intelligence' => [
        'ISR' => 2.6, 'GBR' => 1.8, 'FRA' => 1.4, 'RUS' => 1.6, 'PRK' => 1.5,
        'IRN' => 1.4, 'PAK' => 1.3, 'CHE' => 1.3, 'SGP' => 1.4, 'NLD' => 1.2,
        'SWE' => 1.2, 'AUS' => 1.3, 'KOR' => 1.3, 'TUR' => 1.2, 'IND' => 0.9,
        'BRA' => 0.7, 'IDN' => 0.7, 'NGA' => 0.7, 'DEU' => 0.9, 'JPN' => 0.9,
    ],

    /**
     * Rapporti bilaterali notevoli: alleanze e inimicizie che nessuna formula
     * strutturale può indovinare. Valore di affinità -127..+127, applicato in
     * entrambe le direzioni salvo diversa indicazione.
     *
     * Non è un elenco esaustivo e non pretende di esserlo: copre i rapporti che
     * cambiano il comportamento del modello.
     */
    'rapporti' => [
        // Blocco atlantico
        ['USA', 'GBR',  110], ['USA', 'CAN',  115], ['USA', 'AUS',  110],
        ['USA', 'JPN',  105], ['USA', 'KOR',  100], ['USA', 'DEU',   95],
        ['USA', 'FRA',   90], ['USA', 'ITA',   95], ['USA', 'POL',  100],
        ['USA', 'ISR',  110], ['GBR', 'FRA',   95], ['DEU', 'FRA',  115],
        ['DEU', 'POL',   85], ['FRA', 'ITA',   95], ['NLD', 'DEU',  110],
        ['ESP', 'PRT',  110], ['SWE', 'FIN',  115], ['NOR', 'SWE',  110],
        ['USA', 'TUR',   45], ['TUR', 'GRC',  -35], ['GRC', 'CYP',  110],

        // Blocco orientale e partner
        ['RUS', 'BLR',  110], ['RUS', 'CHN',   70], ['RUS', 'IRN',   65],
        ['RUS', 'PRK',   55], ['RUS', 'SYR',   80], ['RUS', 'KAZ',   60],
        ['CHN', 'PRK',   75], ['CHN', 'PAK',   90], ['CHN', 'IRN',   60],
        ['CHN', 'RUS',   70], ['VEN', 'CUB',   95], ['CUB', 'RUS',   60],

        // Inimicizie storiche
        ['RUS', 'UKR', -110], ['UKR', 'RUS', -120],
        ['IND', 'PAK', -100], ['PAK', 'IND',  -95],
        ['PRK', 'KOR',  -95], ['KOR', 'PRK',  -85],
        ['ISR', 'IRN', -110], ['IRN', 'ISR', -115],
        ['ISR', 'SYR',  -90], ['ISR', 'LBN',  -70],
        ['SAU', 'IRN',  -85], ['IRN', 'SAU',  -85],
        ['USA', 'IRN', -100], ['USA', 'PRK', -105], ['USA', 'CUB',  -60],
        ['USA', 'RUS',  -85], ['RUS', 'USA',  -85],
        ['USA', 'CHN',  -45], ['CHN', 'USA',  -45],
        ['CHN', 'TWN',  -80], ['TWN', 'CHN',  -75], ['USA', 'TWN',   70],
        ['CHN', 'IND',  -45], ['IND', 'CHN',  -45],
        ['ARM', 'AZE',  -95], ['AZE', 'ARM',  -95],
        ['SRB', 'XKX',  -90], ['XKX', 'SRB',  -85],
        ['DZA', 'MAR',  -60], ['MAR', 'DZA',  -60],
        ['ETH', 'ERI',  -55], ['SDN', 'SSD',  -45],
        ['GRC', 'TUR',  -35], ['JPN', 'PRK',  -80], ['JPN', 'CHN',  -35],

        // Partner regionali
        ['BRA', 'ARG',   75], ['IND', 'BGD',   60], ['IND', 'NPL',   65],
        ['ZAF', 'NAM',   75], ['ZAF', 'BWA',   80], ['NGA', 'GHA',   70],
        ['SAU', 'ARE',   95], ['SAU', 'BHR',   95], ['ARE', 'EGY',   80],
        ['IDN', 'MYS',   70], ['AUS', 'NZL',  115], ['MEX', 'CAN',   75],
    ],
];
