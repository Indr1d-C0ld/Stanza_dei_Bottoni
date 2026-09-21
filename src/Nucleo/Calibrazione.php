<?php

declare(strict_types=1);

namespace App\Nucleo;

use RuntimeException;

/**
 * I parametri di taratura, letti da calibrazione/.
 *
 * Un gioco si tara per essere interessante, un simulatore per avere ragione:
 * per questo esistono due profili sopra la stessa base, e per questo ogni tick
 * registra con quale versione è girato.
 */
final class Calibrazione
{
    /** @param array<string,mixed> $valori */
    private function __construct(
        public readonly string $profilo,
        public readonly string $versione,
        private readonly array $valori,
    ) {}

    public static function carica(string $radiceProgetto, string $profilo): self
    {
        $base = $radiceProgetto . '/calibrazione/base.php';
        $sopra = $radiceProgetto . '/calibrazione/' . $profilo . '.php';

        if (!is_file($base) || !is_file($sopra)) {
            throw new RuntimeException("Profilo di calibrazione sconosciuto: $profilo");
        }

        $valoriBase  = require $base;
        $valoriSopra = require $sopra;

        // Il catalogo dei verbi e' calibrazione a tutti gli effetti: se lo
        // ritocca un progettista, sta in git.
        $percorsoVerbi = $radiceProgetto . '/calibrazione/verbi.php';
        if (is_file($percorsoVerbi)) {
            $valoriBase['verbi'] = require $percorsoVerbi;
        }

        $valori = self::fondi($valoriBase, $valoriSopra);

        // E infine, sopra tutto, le leve che l'arbitro ha mosso a mondo acceso.
        // I file restano la verita' di partenza e stanno in git; queste sono
        // scostamenti dichiarati, revocabili uno per uno.
        foreach (self::$leve as $chiave => $valore) {
            self::infila($valori, $chiave, $valore);
        }

        return new self(
            profilo:  (string) ($valoriSopra['profilo'] ?? $profilo),
            versione: (string) ($valoriBase['versione'] ?? '0'),
            valori:   $valori,
        );
    }

    /**
     * Le sovrascritture dell'arbitro, da caricare PRIMA di ogni carica().
     *
     * Sta qui e non nel costruttore perche' la calibrazione si carica da mezza
     * dozzina di posti — il tick, il web, i programmi da riga di comando — e
     * far passare il database a tutti significherebbe legare la taratura alla
     * persistenza, che e' l'opposto di quel che serve. Chi ha il database lo
     * dice una volta; chi non ce l'ha (le prove, il mondo a vuoto) lavora sui
     * file e basta.
     *
     * @var array<string,mixed>
     */
    private static array $leve = [];

    /** @param array<string,mixed> $leve chiave puntata => valore */
    public static function imponiLeve(array $leve): void
    {
        self::$leve = $leve;
    }

    public static function leveImposte(): array
    {
        return self::$leve;
    }

    /** Scrive un valore in fondo a una chiave puntata, creando quel che manca. */
    private static function infila(array &$dove, string $chiave, mixed $valore): void
    {
        $pezzi = explode('.', $chiave);
        $p = &$dove;
        foreach ($pezzi as $i => $pezzo) {
            if ($i === count($pezzi) - 1) {
                $p[$pezzo] = $valore;
                return;
            }
            if (!isset($p[$pezzo]) || !is_array($p[$pezzo])) {
                $p[$pezzo] = [];
            }
            $p = &$p[$pezzo];
        }
    }

    /**
     * Fusione ricorsiva: il profilo sovrascrive la base solo dove si esprime.
     *
     * @param array<string,mixed> $base
     * @param array<string,mixed> $sopra
     * @return array<string,mixed>
     */
    private static function fondi(array $base, array $sopra): array
    {
        foreach ($sopra as $chiave => $valore) {
            if (is_array($valore) && isset($base[$chiave]) && is_array($base[$chiave])) {
                $base[$chiave] = self::fondi($base[$chiave], $valore);
            } else {
                $base[$chiave] = $valore;
            }
        }
        return $base;
    }

    public function leggi(string $chiave, mixed $ripiego = null): mixed
    {
        $nodo = $this->valori;
        foreach (explode('.', $chiave) as $pezzo) {
            if (!is_array($nodo) || !array_key_exists($pezzo, $nodo)) {
                return $ripiego;
            }
            $nodo = $nodo[$pezzo];
        }
        return $nodo;
    }

    public function numero(string $chiave, float $ripiego = 0.0): float
    {
        return (float) $this->leggi($chiave, $ripiego);
    }
}
