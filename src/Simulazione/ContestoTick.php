<?php

declare(strict_types=1);

namespace App\Simulazione;

use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;
use App\Nucleo\Caso;
use App\Dati\Mondo;

/**
 * Il contesto condiviso da tutte e dodici le fasi di un tick.
 *
 * La calibrazione è immutabile dentro il tick: nessuna fase può cambiare le
 * regole a metà partita.
 */
final class ContestoTick
{
    /** @var list<array<string,mixed>> Il giornale narrativo: si accumula nelle
     *  fasi 02-07 e viene SCRITTO solo dalla fase 09. Così la stampa e'
     *  l'unico punto che decide cosa diventa pubblico. */
    private array $giornale = [];

    public function __construct(
        public readonly int $tick,
        public readonly int $seme,
        public readonly Caso $caso,
        public readonly Calibrazione $calibrazione,
        public readonly ?Basedati $db = null,
        public readonly bool $aVuoto = false,
        public readonly ?Mondo $mondo = null,
    ) {}

    /** @param array<string,mixed> $dati */
    public function annota(string $genere, array $dati): void
    {
        $this->giornale[] = ['genere' => $genere, 'dati' => $dati];
    }

    /** @return list<array<string,mixed>> */
    public function giornale(): array
    {
        return $this->giornale;
    }
}
