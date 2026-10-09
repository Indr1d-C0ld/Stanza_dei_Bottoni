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
    /** @var list<array<string,mixed>> Il giornale narrativo: lo scrivono
     *  tutte le fasi, e la fase 09 decide cosa ne diventa pubblico. Le fasi
     *  10 e 11 vengono DOPO la stampa: quel che annotano di pubblico per
     *  costruzione (elezioni, dimissioni, denunce, fine d'epoca) lo mettono
     *  in cronaca da se', o non ci arriverebbe mai. */
    private array $giornale = [];

    public function __construct(
        public readonly int $tick,
        public readonly int $seme,
        public readonly Caso $caso,
        public readonly Calibrazione $calibrazione,
        public readonly ?Basedati $db = null,
        public readonly bool $aVuoto = false,
        public readonly ?Mondo $mondo = null,
        /**
         * Il caso del MONDO, col seme radice e non con quello del tick: per
         * le grandezze che devono restare le stesse da una settimana
         * all'altra — il ciclo economico, la tregua dopo un cambio di governo.
         * Col caso del tick un indice come «tick/26» non tiene niente fermo:
         * cambia il seme, e il tiro e' nuovo ogni settimana (docs/30).
         */
        public readonly ?Caso $casoDelMondo = null,
    ) {}

    /** Il caso che non cambia col tick (vedi casoDelMondo). */
    public function delMondo(): Caso
    {
        return $this->casoDelMondo ?? $this->caso;
    }

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
