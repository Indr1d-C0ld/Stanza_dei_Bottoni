<?php

declare(strict_types=1);

namespace App\Dati;

/** Una poltrona di gabinetto e chi la occupa. */
final class Poltrona
{
    public function __construct(
        public readonly string $ruolo,
        public Personaggio $titolare,
        /** Potere di gabinetto 0..100: chi prevale quando due ordini si contraddicono. */
        public float $potere = 50.0,
        /** Lealtà verso il capo. Scenderà sotto zero quando qualcuno la comprerà. */
        public float $lealta = 70.0,
        public int   $insediatoTick = 0,
    ) {}
}
