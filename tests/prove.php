<?php

declare(strict_types=1);

/**
 * L'impalcatura delle prove: minima, e senza dipendenze.
 *
 * Non c'e' PHPUnit e non serve. Servono tre cose — dire cosa si prova,
 * confrontare due valori, e contare quel che non torna — e stanno in
 * cinquanta righe. Tirarsi dietro un framework per farlo sarebbe la stessa
 * sproporzione del client SMTP.
 */
final class Prove
{
    private static int $passate = 0;
    private static int $fallite = 0;
    private static string $gruppo = '';
    /** @var list<string> */
    private static array $guai = [];

    public static function gruppo(string $nome): void
    {
        self::$gruppo = $nome;
        printf("\n  %s\n", $nome);
    }

    public static function che(string $cosa, bool $vero, string $dettaglio = ''): void
    {
        if ($vero) {
            self::$passate++;
            printf("    \033[32m✓\033[0m %s\n", $cosa);
            return;
        }
        self::$fallite++;
        self::$guai[] = self::$gruppo . ' — ' . $cosa . ($dettaglio !== '' ? ": $dettaglio" : '');
        printf("    \033[31m✗\033[0m %s%s\n", $cosa, $dettaglio !== '' ? "  ($dettaglio)" : '');
    }

    public static function uguale(string $cosa, mixed $atteso, mixed $avuto): void
    {
        self::che($cosa, $atteso === $avuto,
            sprintf('atteso %s, avuto %s', var_export($atteso, true), var_export($avuto, true)));
    }

    public static function vicino(string $cosa, float $atteso, float $avuto, float $tolleranza): void
    {
        self::che($cosa, abs($atteso - $avuto) <= $tolleranza,
            sprintf('atteso %.4f ± %.4f, avuto %.4f', $atteso, $tolleranza, $avuto));
    }

    public static function fra(string $cosa, float $min, float $max, float $avuto): void
    {
        self::che($cosa, $avuto >= $min && $avuto <= $max,
            sprintf('atteso fra %.4f e %.4f, avuto %.4f', $min, $max, $avuto));
    }

    public static function esplode(string $cosa, callable $f): void
    {
        try {
            $f();
            self::che($cosa, false, 'non ha sollevato niente');
        } catch (\Throwable $e) {
            self::che($cosa, true);
        }
    }

    public static function esito(): int
    {
        printf("\n  %d passate, %d fallite\n", self::$passate, self::$fallite);
        if (self::$guai !== []) {
            printf("\n  Quel che non torna:\n");
            foreach (self::$guai as $g) {
                printf("    · %s\n", $g);
            }
        }
        printf("\n");
        return self::$fallite === 0 ? 0 : 1;
    }
}
