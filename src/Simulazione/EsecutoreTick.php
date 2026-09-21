<?php

declare(strict_types=1);

namespace App\Simulazione;

use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;
use App\Nucleo\Caso;
use App\Dati\Mondo;
use App\Simulazione\Fasi as F;

/**
 * L'orchestratore del tick.
 *
 * Regole che valgono per tutte e dodici le fasi:
 *   - UNA TRANSAZIONE PER FASE, non per tick: se la 07 fallisce, le fasi
 *     00-06 restano scritte e si riprende da li'.
 *   - IDEMPOTENZA: una fase già registrata per quel tick non si riesegue.
 *   - Il giornale narrativo si accumula nelle fasi 02-07 e viene scritto solo
 *     dalla 09.
 */
final class EsecutoreTick
{
    /** @return list<Fase> Le dodici fasi, nell'ordine che conta più delle formule. */
    public static function fasi(): array
    {
        return [
            new F\Fase00Chiusura(),
            new F\Fase01ControAzioni(),
            new F\Fase02Maturazione(),
            new F\Fase03Economia(),
            new F\Fase04Societa(),
            new F\Fase05SicurezzaInterna(),
            new F\Fase06Relazioni(),
            new F\Fase07Conflitto(),
            new F\Fase08Intelligence(),
            new F\Fase09Stampa(),
            new F\Fase10Gabinetto(),
            new F\Fase11GlobaliScadenze(),
        ];
    }

    public function __construct(
        private readonly Calibrazione $calibrazione,
        private readonly ?Basedati $db = null,
        private readonly bool $aVuoto = false,
        private readonly ?Mondo $mondo = null,
    ) {}

    /** @var list<array<string,mixed>> le annotazioni dell'ultimo tick eseguito */
    private array $ultimoGiornale = [];

    /** @return list<array<string,mixed>> le annotazioni dell'ultimo tick */
    public function giornale(): array
    {
        return $this->ultimoGiornale;
    }

    /**
     * @return list<array<string,mixed>> il resoconto, una riga per fase
     */
    public function esegui(int $tick, int $semeRadice, ?callable $suProgresso = null): array
    {
        $seme = Caso::semeDelTick($semeRadice, $tick);
        $contesto = new ContestoTick(
            tick:         $tick,
            seme:         $seme,
            caso:         new Caso($seme),
            calibrazione: $this->calibrazione,
            db:           $this->db,
            aVuoto:       $this->aVuoto,
            mondo:        $this->mondo,
        );

        $resoconto = [];
        foreach (self::fasi() as $fase) {
            if ($this->giaEseguita($tick, $fase)) {
                $resoconto[] = $this->riga($fase, 0.0, new EsitoFase(nota: 'già eseguita', saltata: true));
                continue;
            }

            $avvio = hrtime(true);
            $esito = $this->db instanceof Basedati && !$this->aVuoto
                ? $this->db->inTransazione(static fn() => $fase->esegui($contesto))
                : $fase->esegui($contesto);
            $durata = (hrtime(true) - $avvio) / 1_000_000;

            $this->registra($tick, $seme, $fase, $durata, $esito);
            $riga = $this->riga($fase, $durata, $esito);
            $resoconto[] = $riga;

            if ($suProgresso !== null) {
                $suProgresso($riga);
            }
        }

        $this->ultimoGiornale = $contesto->giornale();

        return $resoconto;
    }

    /** @return array<string,mixed> */
    private function riga(Fase $fase, float $durata, EsitoFase $esito): array
    {
        return [
            'codice'  => $fase->codice(),
            'nome'    => $fase->nome(),
            'durata'  => round($durata, 2),
            'saltata' => $esito->saltata,
            'esito'   => $esito->riassunto(),
        ];
    }

    private function giaEseguita(int $tick, Fase $fase): bool
    {
        if (!$this->db instanceof Basedati || $this->aVuoto) {
            return false;
        }
        $stmt = $this->db->esegui(
            'SELECT 1 FROM sdb_tick_log WHERE tick = ? AND fase = ? LIMIT 1',
            [$tick, $fase->codice()],
        );
        return (bool) $stmt->fetchColumn();
    }

    private function registra(int $tick, int $seme, Fase $fase, float $durata, EsitoFase $esito): void
    {
        if (!$this->db instanceof Basedati || $this->aVuoto) {
            return;
        }
        $this->db->esegui(
            'INSERT INTO sdb_tick_log (tick, fase, iniziata, durata_ms, seme, calibrazione, esito, note)
             VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)',
            [
                $tick,
                $fase->codice(),
                (int) round($durata),
                $seme,
                $this->calibrazione->profilo . '@' . $this->calibrazione->versione,
                $esito->saltata ? 'saltata' : 'ok',
                $esito->riassunto(),
            ],
        );
    }
}
