<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Un'azione decisa ma non ancora realizzata.
 *
 * È l'entità centrale del gioco. Il divario fra la decisione e la sua
 * maturazione è lo spazio in cui vive tutto il resto: più a lungo un'azione
 * resta in volo, più è potente e più è vulnerabile.
 */
final class Evento
{
    public const IN_VOLO     = 'in_volo';
    public const SCOPERTO    = 'scoperto';
    public const FERMATO     = 'fermato';
    public const REALIZZATO  = 'realizzato';
    public const DEPISTAGGIO = 'depistaggio';

    public function __construct(
        public readonly int    $id,
        public readonly string $dominio,
        public readonly string $verbo,
        public readonly string $mandante,
        public readonly ?string $esecutore,     // il proxy, se c'è
        public readonly string $bersaglio,
        public readonly float  $intensita,      // 0..1, il "rocker" di Shadow President
        public readonly float  $copertura,      // 0..1, investimento in OPSEC
        public readonly float  $impronta,       // 0..1, quanto rumore fa mentre è in volo
        public readonly int    $creatoTick,
        public readonly int    $maturazioneTick,
        public readonly float  $dannoBase,      // Hurt: -127..+127, negativo = aiuto
        public readonly float  $attribuzioneVera,
        /**
         * Chi si vuole far accusare al posto proprio. La falsificazione non
         * serve a nascondere cio' che hai fatto: serve a far attribuire a un
         * terzo cio' che hai fatto.
         */
        public readonly ?string $falsaBandiera = null,
        /** Quanto e' ben confezionato il falso: 0..1 */
        public readonly float $qualitaFalso = 0.0,
        public string  $stato = self::IN_VOLO,
    ) {}

    /**
     * Quanto è ancora fermabile. Decade con l'avanzare della maturazione:
     * "gli eventi sono molto più facili da fermare quando individuati presto"
     * (manuale di CyberJudas).
     */
    public function reversibilita(int $tick): float
    {
        $durata = max(1, $this->maturazioneTick - $this->creatoTick);
        $fatto  = ($tick - $this->creatoTick) / $durata;
        return max(0.0, 1.0 - $fatto ** 0.7);
    }

    public function inVolo(): bool
    {
        return $this->stato === self::IN_VOLO || $this->stato === self::SCOPERTO;
    }
}
