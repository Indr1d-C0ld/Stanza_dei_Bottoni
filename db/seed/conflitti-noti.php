<?php

declare(strict_types=1);

/**
 * I conflitti armati in corso al momento della divergenza (gennaio 2026).
 *
 * PERCHE' ESISTE. Le insurrezioni nascevano da ZERO per tutti, e siccome il
 * reclutamento e' rapido si formavano tutte insieme: il mondo apriva con
 * cinquantanove paesi in conflitto al primo anno — piu' che a regime — e ci
 * metteva otto anni a scendere ai trentasei di equilibrio. Chi guardava si
 * vedeva quasi un decennio di mondo sbagliato prima che diventasse giusto.
 *
 * Un mondo che comincia oggi deve cominciare con i conflitti di oggi.
 *
 * Fonte: UCDP/PRIO Armed Conflict Dataset v26.1 e UCDP Battle-Related Deaths
 * v26.1, conflitti statali interni (anche internazionalizzati) attivi nel
 * 2025, l'anno prima della divergenza: fino a ottobre 2026 qui c'era il 2024
 * (docs/31). Fra parentesi i morti in battaglia del 2025, stima centrale.
 *
 * Il livello e' sulla nostra scala di netPeace, che e' un RAPPORTO DI FORZE e
 * non un conto dei morti:
 *
 *   4  guerriglia          lo Stato tiene il paese
 *   5  insurrezione grave  i ribelli tengono territorio
 *   6  guerra civile       i ribelli valgono lo Stato, almeno in parte del paese
 *
 * Per questo il livello non si traduce in automatico dai morti: il Pakistan
 * perde piu' di tremila persone l'anno, ma il TTP non vale l'esercito
 * pachistano; Israele ne conta quindicimila, ma nessuno minaccia il controllo
 * dello Stato israeliano. I morti dicono quali conflitti ci sono e quali sono
 * cresciuti o calati; il rapporto di forze resta un giudizio, dichiarato riga
 * per riga.
 *
 * I paesi che NON stanno in questo elenco partono a zero, ed e' la risposta
 * giusta: nel mondo vero, in questo momento, non hanno un conflitto armato
 * statale. Rispetto al 2024 escono Ciad, Libia, Mauritania, Senegal, Congo,
 * Egitto e Burundi, che UCDP nel 2025 non registra piu'.
 */

return [
    // --- guerra civile ------------------------------------------------------
    'SDN' => 6,   // Sudan, esercito contro RSF (12.269)
    'MMR' => 6,   // Birmania, dopo il colpo di Stato del 2021 (2.083)
    'COD' => 6,   // Congo orientale, M23 col Ruanda (4.464)
    'SOM' => 6,   // Somalia, al-Shabaab (3.095)
    'NGA' => 6,   // Nigeria, jihadisti e banditismo del nord-ovest (1.905)
    'ETH' => 6,   // Etiopia, Amhara e Oromia (4.312)
    'MLI' => 6,   // Mali, Sahel jihadista (1.357)
    'BFA' => 6,   // Burkina Faso, il piu' colpito del Sahel (3.031)
    'HTI' => 6,   // Haiti: le bande tengono quasi tutta la capitale (1.211). Era 5

    // --- insurrezione grave -------------------------------------------------
    'SYR' => 5,   // Siria dopo la caduta di Assad: resti del regime, SDF, drusi (700). Era 6
    'YEM' => 5,   // Yemen (469)
    'NER' => 5,   // Niger (702)
    'SSD' => 5,   // Sud Sudan (282)
    'CAF' => 5,   // Centrafrica (197)
    'MOZ' => 5,   // Mozambico, Cabo Delgado (296)
    'CMR' => 5,   // Camerun, anglofoni e Boko Haram (240)
    'PAK' => 5,   // Pakistan, TTP e Baluchistan (3.091): sopra i mille, ma lo Stato regge
    'COL' => 5,   // Colombia, ELN e dissidenze FARC (298)
    // Il Messico non e' nei conflitti STATALI di UCDP: i cartelli non hanno
    // un'incompatibilita' politica col governo, e UCDP li conta fra i
    // conflitti non statali. Resta qui per scelta dichiarata: lo Stato
    // combatte, e i cartelli tengono territorio.
    'MEX' => 5,

    // --- guerriglia ---------------------------------------------------------
    'ISR' => 4,   // guerra con Hamas e Hezbollah (14.771): nessuna minaccia al controllo dello Stato
    'AFG' => 4,   // Afghanistan, resistenza e ISKP (81). Era 6
    'IND' => 4,   // naxaliti e nord-est (537)
    'PHL' => 4,   // Mindanao e NPA (141)
    'IDN' => 4,   // Papua (104)
    'IRQ' => 4,   // ISIS (77)
    'TUR' => 4,   // PKK (55)
    'IRN' => 4,   // Kurdi e Baluci (52)
    'KEN' => 4,   // al-Shabaab al confine (44)
    'RWA' => 4,   // (60)
    'BEN' => 4,   // il Sahel che scende verso il golfo (149)
    'TGO' => 4,   // (69)
    'THA' => 4,   // profondo sud (36)
    'AGO' => 4,   // Cabinda (25)
];
