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

    /**
     * Faziosita', da 0 a 1 — il quarto ingrediente del modello PITF.
     *
     * NON e' pressioneInterna(). Quella misura quanto le fazioni sono ostili
     * al capo; questa misura quanto la politica e' SPACCATA, che e' un'altra
     * cosa. Goldstone et al. la riprendono dalla componente «factional» di
     * Polity: competizione politica organizzata in blocchi contrapposti e
     * parrocchiali, dove chi vince prende tutto e chi perde perde tutto.
     *
     * E' il predittore piu' forte che abbiano trovato. Una democrazia parziale
     * fazionalizzata ha piu' di TRENTA VOLTE le probabilita' d'instabilita' di
     * un'autocrazia piena — piu' della mortalita' infantile, piu' del
     * vicinato, piu' di qualunque grandezza economica.
     *
     * Si misura come lo spacco: massima quando le forze schierate ai due poli
     * si equivalgono, nulla quando tirano tutte dalla stessa parte. Due campi
     * tiepidi non sono una frattura, quindi pesa anche l'intensita'.
     */
    public function faziosita(): float
    {
        $pro = 0.0;
        $contro = 0.0;
        foreach ($this->fazioni as $f) {
            if ($f->favore >= 0.0) {
                $pro += $f->forza * ($f->favore / 100.0);
            } else {
                $contro += $f->forza * (-$f->favore / 100.0);
            }
        }
        $totale = $pro + $contro;
        if ($totale <= 0.0) {
            return 0.0;
        }

        // Le due normalizzazioni si MISURANO sul mondo, non si scelgono a
        // occhio. Al primo tentativo avevo diviso l'intensita' per 90 e la
        // coesione per 120: il totale pesato ha mediana 30 e la coesione sta
        // fra 50 e 75, quindi la faziosita' non superava mai 0,35 e il
        // moltiplicatore piu' importante del modello PITF non si accendeva
        // mai. Una grandezza che non arriva mai in fondo alla propria scala e'
        // una grandezza che non esiste.
        $spacco      = 1.0 - abs($pro - $contro) / $totale;
        $intensita   = min(1.0, $totale / 50.0);
        // Un gabinetto che non tiene insieme aggrava lo spacco: e' la stessa
        // logica per cui pressioneInterna() moltiplica per la coesione.
        $sfaldamento = max(0.5, min(1.5, 1.0 + (65.0 - $this->coesione) / 40.0));

        return max(0.0, min(1.0, $spacco * $intensita * $sfaldamento));
    }

    public function rankingOrdinato(): array
    {
        $p = array_values(array_filter($this->poltrone, static fn($x) => $x->ruolo !== 'capo'));
        usort($p, static fn(Poltrona $a, Poltrona $b): int => $b->potere <=> $a->potere);
        return $p;
    }
}
