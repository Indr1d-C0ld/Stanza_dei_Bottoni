<?php

declare(strict_types=1);

namespace App\Simulazione;

/** Cio' che una fase riporta: contatori e note, che finiscono in sdb_tick_log. */
final class EsitoFase
{
    /** @param array<string,int|float|string> $contatori */
    public function __construct(
        public readonly array $contatori = [],
        public readonly ?string $nota = null,
        public readonly bool $saltata = false,
    ) {}

    public static function nonImplementata(): self
    {
        return new self(nota: 'fase non ancora implementata', saltata: true);
    }

    public function riassunto(): string
    {
        if ($this->contatori === []) {
            return $this->nota ?? '—';
        }
        $pezzi = [];
        foreach ($this->contatori as $chiave => $valore) {
            $pezzi[] = $chiave . '=' . $valore;
        }
        return implode(' ', $pezzi);
    }
}
