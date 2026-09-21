<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Un funzionario. Fittizio: gli Stati sono reali, le persone no.
 *
 * Le vulnerabilità non servono a niente finché non c'è un servizio straniero
 * che le cerchi — ma senza di esse i dossier non avrebbero niente dentro, e
 * vanno generate adesso perché la biografia sia coerente fin dall'inizio.
 */
final class Personaggio
{
    public function __construct(
        public readonly string $nome,
        public int   $etica,        // 1 senza macchia .. 6 spregiudicato
        public int   $ambizione,    // 1 quieto .. 6 famelico
        public float $competenza,   // 0..1
        /** @var list<string> */
        public readonly array $vulnerabilita,
        public int   $eta = 55,
    ) {}
}
