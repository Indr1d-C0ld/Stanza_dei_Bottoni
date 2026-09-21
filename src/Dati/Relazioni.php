<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * La matrice delle relazioni, sparsa.
 *
 * Non teniamo 189x189 coppie: la stragrande maggioranza degli Stati del mondo
 * non ha alcun rapporto reciproco degno di essere simulato, e Crawford dovette
 * buttare via la multipolarità proprio perché la matrice piena non gli stava in
 * memoria. Noi la memoria ce l'abbiamo, ma il problema vero non era quello: era
 * che simulare rapporti inesistenti è rumore.
 *
 * Esistono qui solo le coppie che hanno una ragione di esistere:
 *   - fra le potenze (chiunque conti si interessa di chiunque altro conti)
 *   - fra vicini di confine
 *   - fra ogni Stato e le grandi potenze
 */
final class Relazioni
{
    /** @var array<string,Relazione> chiave "AAA|BBB", da A verso B */
    private array $coppie = [];

    /** @var array<string,list<string>> indice dei vicini per ISO3 */
    public array $vicinato = [];

    public static function chiave(string $a, string $b): string
    {
        return $a . '|' . $b;
    }

    public function fra(string $a, string $b): ?Relazione
    {
        return $this->coppie[self::chiave($a, $b)] ?? null;
    }

    public function imposta(string $a, string $b, Relazione $r): void
    {
        $this->coppie[self::chiave($a, $b)] = $r;
    }

    /** @return array<string,Relazione> */
    public function tutte(): array
    {
        return $this->coppie;
    }

    public function quante(): int
    {
        return count($this->coppie);
    }

    /** Carica il grafo di contiguità terrestre. */
    public function caricaConfini(string $percorsoCsv): void
    {
        if (!is_file($percorsoCsv)) {
            return;
        }
        $fh = fopen($percorsoCsv, 'r');
        fgetcsv($fh, 0, ',', '"', '\\');
        while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            [$a, $b] = [$r[0], $r[1]];
            $this->vicinato[$a][] = $b;
            $this->vicinato[$b][] = $a;
        }
        fclose($fh);
    }

    public function confinanti(string $a, string $b): bool
    {
        return in_array($b, $this->vicinato[$a] ?? [], true);
    }
}
