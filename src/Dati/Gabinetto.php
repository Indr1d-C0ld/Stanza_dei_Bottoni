<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Il gabinetto di una potenza giocabile: sette poltrone, i personaggi che le
 * occupano, e le fazioni che li hanno messi lì.
 *
 * È lo strato di CyberJudas, dove i consiglieri sono modellati con le stesse
 * statistiche delle nazioni — etica, ambizione, potere, rango — e non sono un
 * menu di pareri ma attori politici a pieno titolo.
 *
 * Senza giocatori lo muove l'IA. Con i giocatori, ogni poltrona è una persona
 * vera e questo oggetto è il tavolo attorno a cui si siedono.
 */
final class Gabinetto
{
    public const RUOLI = [
        'capo'           => 'Capo',
        'staff'          => 'Capo di Gabinetto',
        'esteri'         => 'Esteri',
        'difesa'         => 'Difesa',
        'intelligence'   => 'Intelligence',
        'interni'        => 'Sicurezza interna',
        'economia'       => 'Economia',
        'informazione'   => 'Informazione',
    ];

    /** Quale poltrona risponde di quale dominio d'azione. */
    public const DOMINIO_DI = [
        'soc' => 'esteri', 'eco' => 'economia', 'info' => 'informazione',
        'int' => 'intelligence', 'mil' => 'difesa', 'nuc' => 'difesa',
    ];

    /** @var array<string,Poltrona> */
    public array $poltrone = [];

    /** @var list<Fazione> */
    public array $fazioni = [];

    public float $coesione = 60.0;
    public int   $ultimoRimpasto = 0;

    public function __construct(public readonly string $iso3) {}

    public function competenza(string $ruolo): float
    {
        return $this->poltrone[$ruolo]?->titolare->competenza ?? 0.5;
    }

    /** La competenza che conta per un dominio d'azione. */
    public function competenzaDominio(string $dominio): float
    {
        return $this->competenza(self::DOMINIO_DI[$dominio] ?? 'staff');
    }

    /**
     * Quanto le fazioni spingono per liberarsi del capo. Alimenta il rischio
     * di cambio irregolare della fase 05: un governo può essere popolare nel
     * paese e finito dentro il palazzo.
     */
    public function pressioneInterna(): float
    {
        $ostili = 0.0;
        foreach ($this->fazioni as $f) {
            if ($f->favore < 0) {
                $ostili += $f->forza * (-$f->favore / 100.0);
            }
        }
        // La scarsa coesione moltiplica: un gabinetto diviso è una porta aperta.
        return $ostili * (1.0 + (60.0 - $this->coesione) / 100.0);
    }

    public function rankingOrdinato(): array
    {
        $p = array_values(array_filter($this->poltrone, static fn($x) => $x->ruolo !== 'capo'));
        usort($p, static fn(Poltrona $a, Poltrona $b): int => $b->potere <=> $a->potere);
        return $p;
    }
}
