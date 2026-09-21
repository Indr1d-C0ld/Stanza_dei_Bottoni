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

    'intelligence' => [
        // Scoperta più generosa: la paranoia totale non è divertente.
        'difficolta_livello' => [1 => 8.0, 2 => 6.5, 3 => 4.5, 4 => 8.0],
    ],
];
