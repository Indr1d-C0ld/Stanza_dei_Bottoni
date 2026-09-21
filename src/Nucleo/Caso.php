<?php

declare(strict_types=1);

namespace App\Nucleo;

/**
 * Il caso, deterministico.
 *
 * Non esiste stato interno: ogni tiro è una funzione pura del seme del tick,
 * della fase, dell'entità e di un indice. Conseguenza: un tick si può
 * rieseguire e produce lo stesso identico mondo.
 *
 * Serve per il collaudo, ma serve soprattutto il giorno in cui un giocatore
 * scrive "il mio colpo di stato non poteva fallire": si riapre il tick e si
 * guarda.
 */
final class Caso
{
    public function __construct(private readonly int $seme) {}

    /** Frazione in [0,1). */
    public function frazione(string $fase, int $entita = 0, int $indice = 0): float
    {
        $impronta = hash('xxh128', $this->seme . '|' . $fase . '|' . $entita . '|' . $indice);
        // 13 cifre esadecimali = 52 bit, entro la precisione esatta di un float.
        // ATTENZIONE al divisore: 13 cifre arrivano a 2^52-1, non a 2^53-1.
        // Dividendo per 2^53 questa funzione ha restituito per settimane valori
        // in [0; 0,5) invece che in [0; 1) — il che significa che rumore() era
        // SEMPRE NEGATIVO, con media -0,5 anziche' zero. Ogni scossa casuale
        // del mondo era una spinta verso il basso, e la "deriva della
        // legittimita' da indagare" annotata in docs/08 era questo.
        $grezzo = hexdec(substr($impronta, 0, 13));
        return $grezzo / 0x10000000000000;
    }

    public function intero(string $fase, int $entita, int $indice, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }
        return $min + (int) floor($this->frazione($fase, $entita, $indice) * ($max - $min + 1));
    }

    /** Vero con la probabilità data (0..1). */
    public function prova(string $fase, int $entita, int $indice, float $probabilita): bool
    {
        return $this->frazione($fase, $entita, $indice) < $probabilita;
    }

    /** Rumore simmetrico in [-ampiezza, +ampiezza]. */
    public function rumore(string $fase, int $entita, int $indice, float $ampiezza): float
    {
        return ($this->frazione($fase, $entita, $indice) * 2.0 - 1.0) * $ampiezza;
    }

    /** Il seme di un tick deriva dal seme radice del mondo. */
    public static function semeDelTick(int $semeRadice, int $tick): int
    {
        return (int) hexdec(substr(hash('xxh128', $semeRadice . ':' . $tick), 0, 12));
    }
}
