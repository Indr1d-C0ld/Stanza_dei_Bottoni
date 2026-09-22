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
 * Fonte: UCDP/PRIO Armed Conflict Dataset, conflitti statali attivi nel 2024
 * (61 conflitti in 36 paesi, di cui 11 al livello di guerra). Il livello qui
 * sotto e' sulla nostra scala di netPeace:
 *
 *   4  guerriglia          conflitto minore, sotto i mille morti l'anno
 *   5  insurrezione grave
 *   6  guerra civile       oltre i mille morti l'anno: la «guerra» di UCDP
 *
 * I paesi che NON stanno in questo elenco partono a zero, ed e' la risposta
 * giusta: nel mondo vero, in questo momento, non hanno un conflitto armato.
 */

return [
    // --- guerra civile aperta (UCDP: oltre mille morti in battaglia) --------
    'SDN' => 6,   // Sudan, guerra fra esercito e RSF dal 2023
    'MMR' => 6,   // Birmania, dopo il colpo di Stato del 2021
    'COD' => 6,   // Congo orientale, M23 e altri
    'SOM' => 6,   // Somalia, al-Shabaab
    'SYR' => 6,   // Siria
    'NGA' => 6,   // Nigeria, Boko Haram e banditismo del nord-ovest
    'ETH' => 6,   // Etiopia, Amhara e Oromia dopo il Tigray
    'MLI' => 6,   // Mali, Sahel jihadista
    'BFA' => 6,   // Burkina Faso, il piu' colpito del Sahel
    'AFG' => 6,   // Afghanistan, resistenza e ISKP

    // --- insurrezione grave -------------------------------------------------
    'YEM' => 5,   // Yemen
    'NER' => 5,   // Niger
    'SSD' => 5,   // Sud Sudan
    'CAF' => 5,   // Centrafrica
    'MOZ' => 5,   // Mozambico, Cabo Delgado
    'CMR' => 5,   // Camerun anglofono
    'PAK' => 5,   // Pakistan, TTP e Baluchistan
    'COL' => 5,   // Colombia, ELN e dissidenze FARC
    'MEX' => 5,   // Messico, conflitto coi cartelli
    'HTI' => 5,   // Haiti, controllo delle bande

    // --- guerriglia ---------------------------------------------------------
    'IRQ' => 4,
    'TCD' => 4,
    'LBY' => 4,
    'IND' => 4,   // naxaliti e nord-est
    'PHL' => 4,   // Mindanao e NPA
    'TUR' => 4,   // PKK
    'BEN' => 4,   // il Sahel che scende verso il golfo
    'TGO' => 4,
    'BDI' => 4,
    'MRT' => 4,
    'SEN' => 4,   // Casamance
    'THA' => 4,   // profondo sud
    'COG' => 4,
    'EGY' => 4,   // Sinai
];
