<?php

declare(strict_types=1);

/**
 * Profilo GIOCO — tarato perché succedano cose e nessuno si annoi.
 *
 * Qui si accetta deliberatamente di essere meno fedeli: è la stessa scelta che
 * fece Crawford quando prese il 3% di aspettativa di crescita perché "il 2% e
 * non c'erano mai colpi di stato, il 4% e nessun governo sopravviveva".
 * Ottimo game design, pessima modellistica: per quello esiste l'altro profilo.
 */

return [
    'profilo' => 'gioco',

    'societa' => [
        // Aspettative più alte = governi più fragili = più movimento.
        'aspettativa_minima' => 0.020,
        'inerzia_legittimita' => 0.80,
    ],

    'insurrezione' => [
        // Soglie compresse: le insurrezioni salgono di grado più in fretta.
        'soglia_terrorismo' => 48.0,
        'soglia_guerriglia' => 3.0,
    ],

    // IL PREMIO DI VIVACITA' CHE NON ABBIAMO PRESO.
    //
    // Questo profilo esiste per far succedere le cose, e la tentazione era di
    // tenersi i colpi di Stato piu' frequenti del mondo vero. Il conto dice di
    // no, e vale la pena scriverlo perche' non si rifaccia la domanda ogni sei
    // mesi.
    //
    // Un tick e' due ore vere e una settimana di gioco: un anno di gioco dura
    // 103,8 ore reali. Su centottantanove paesi:
    //
    //   rischio 1,60 -> ~14,6 cambi irregolari l'anno -> uno ogni  ~7 ore vere
    //   rischio 0,50 -> ~ 8,5                         -> uno ogni ~12 ore vere
    //   rischio 0,25 -> ~ 6,6                         -> uno ogni ~16 ore vere
    //
    // Fra il mondo piu' turbolento e quello realistico ballano NOVE ORE nel
    // ritmo con cui il giocatore vede cadere un governo da qualche parte. La
    // verosimiglianza costa quasi niente in movimento, perche' il mondo e'
    // grande e il tempo e' compresso ottantaquattro volte: il compromesso che
    // giustificava i tredici colpi l'anno, in pratica, non esisteva.
    //
    // Quindi nessuna deviazione: vale colpo_di_stato.rischio_massimo_anno di
    // base.php (i valori qui sopra sono quelli di allora, per il confronto). Questo profilo
    // resta piu' mosso dell'altro dove il premio serve davvero — aspettative,
    // soglie d'insurrezione, generosita' della scoperta — e non dove servirebbe
    // solo a raccontare un'epoca che non e' quella del seme.

    'intelligence' => [
        // Scoperta più generosa: la paranoia totale non è divertente.
        'difficolta_livello' => [1 => 8.0, 2 => 6.5, 3 => 4.5, 4 => 4.0],   // il quarto resta il muro
    ],
];
