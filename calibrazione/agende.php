<?php

declare(strict_types=1);

/**
 * Le agende private.
 *
 * Ogni giocatore ne riceve due quando prende posto, e nessuno le conosce
 * tranne lui. Non coincidono con l'interesse nazionale — a volte lo
 * contraddicono — ed è questo il punto: **sono il motivo per cui un giocatore
 * prenderà in considerazione il tradimento**, senza che nessuno debba pescare
 * una carta che dice "tu sei il traditore".
 *
 * Ogni agenda ha una condizione verificabile dalla macchina: il modello deve
 * poter dire da solo se è stata raggiunta, altrimenti sarebbe soltanto un
 * suggerimento narrativo.
 *
 *   ruoli      a chi può toccare (vuoto = chiunque)
 *   segreta    se il fallimento è visibile agli altri a fine era
 *   peso       quanto conta nel bilancio finale
 *   verifica   il nome della condizione, valutata dalla fase 10
 */

return [
    'influenza' => [
        'titolo'   => 'Far contare il tuo paese',
        'testo'    => 'Porta l\'influenza totale del tuo paese sopra il {soglia}%.',
        'ruoli'    => [], 'peso' => 3, 'segreta' => false,
        'verifica' => 'influenza_sopra',
    ],
    'benessere' => [
        'titolo'   => 'La gente prima di tutto',
        'testo'    => 'Porta la qualità della vita del tuo paese ad almeno {soglia}/10.',
        'ruoli'    => ['economia', 'capo', 'staff'], 'peso' => 3, 'segreta' => false,
        'verifica' => 'qualita_vita_sopra',
    ],
    'pace' => [
        'titolo'   => 'Nessuna guerra sotto di me',
        'testo'    => 'Arriva alla fine dell\'era senza che il tuo paese entri in guerra.',
        'ruoli'    => ['esteri', 'capo', 'difesa'], 'peso' => 3, 'segreta' => false,
        'verifica' => 'mai_in_guerra',
    ],
    'riarmo' => [
        'titolo'   => 'Un esercito che si veda',
        'testo'    => 'Porta la spesa militare sopra il {soglia}% del prodotto interno.',
        'ruoli'    => ['difesa'], 'peso' => 2, 'segreta' => false,
        'verifica' => 'quota_militare_sopra',
    ],
    'mani_pulite' => [
        'titolo'   => 'Mai un\'ombra',
        'testo'    => 'Fa\' in modo che al tuo paese non venga attribuita alcuna operazione coperta.',
        'ruoli'    => ['intelligence', 'capo', 'informazione'], 'peso' => 4, 'segreta' => true,
        'verifica' => 'nessuno_scandalo',
    ],
    'caduta' => [
        'titolo'   => 'Quel governo deve cadere',
        'testo'    => 'Il governo di {paese} deve cambiare, in un modo o nell\'altro.',
        'ruoli'    => ['intelligence', 'esteri'], 'peso' => 4, 'segreta' => true,
        'verifica' => 'governo_caduto',
    ],
    'protezione' => [
        'titolo'   => 'Quel governo deve reggere',
        'testo'    => 'Il governo di {paese} deve arrivare in piedi alla fine dell\'era.',
        'ruoli'    => ['esteri', 'capo'], 'peso' => 3, 'segreta' => true,
        'verifica' => 'governo_in_piedi',
    ],
    'contenimento' => [
        'titolo'   => 'Tenerli fuori dal club',
        'testo'    => 'Impedisci che {paese} superi la soglia di potenza maggiore (3% di influenza).',
        'ruoli'    => ['intelligence', 'difesa', 'esteri'], 'peso' => 4, 'segreta' => true,
        'verifica' => 'influenza_altrui_sotto',
    ],
    'scalata' => [
        'titolo'   => 'Sedere a capotavola',
        'testo'    => 'Diventa la poltrona con più potere del gabinetto, il Capo escluso.',
        'ruoli'    => [], 'peso' => 4, 'segreta' => true,
        'verifica' => 'prima_poltrona',
    ],
    'stabilita_interna' => [
        'titolo'   => 'Nessuno tocchi il governo',
        'testo'    => 'Arriva alla fine dell\'era senza cambi irregolari nel tuo paese.',
        'ruoli'    => ['interni', 'capo'], 'peso' => 3, 'segreta' => false,
        'verifica' => 'nessun_cambio_irregolare',
    ],
    'amicizia' => [
        'titolo'   => 'Un legame che tenga',
        'testo'    => 'Porta l\'affinità con {paese} sopra 80, e mantienila.',
        'ruoli'    => ['esteri', 'capo'], 'peso' => 3, 'segreta' => false,
        'verifica' => 'affinita_sopra',
    ],
    'discredito' => [
        'titolo'   => 'Smascherarli',
        'testo'    => 'Fa\' in modo che al tuo paese risulti attribuita almeno una operazione coperta di {paese}.',
        'ruoli'    => ['intelligence', 'informazione'], 'peso' => 4, 'segreta' => true,
        'verifica' => 'attribuzione_ottenuta',
    ],
];
