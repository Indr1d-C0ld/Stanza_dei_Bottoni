<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 11 — Globali e scadenze.
 *
 * LEGGE:      tutto lo stato del tick
 * SCRIVE:     sdb_mondo_stato, i default d'ufficio sulle scadenze scadute
 * INVARIANTE: Il livello di pace mondiale è funzione PURA dei net_peace. Nessuna scadenza resta pendente: se nessuno decide, decide l'ufficio competente.
 */
final class Fase11GlobaliScadenze implements Fase
{
    public function codice(): string
    {
        return '11';
    }

    public function nome(): string
    {
        return 'Globali e scadenze';
    }

    public function esegui(ContestoTick $contesto): EsitoFase
    {
        $mondo = $contesto->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        // Il livello di pace mondiale e' la somma dei net peace: una funzione
        // PURA dello stato delle nazioni, non una variabile con vita propria.
        $somma = 0.0;
        foreach ($mondo->nazioni as $n) {
            $somma += $n->netPeace;
        }
        $media = $somma / max(1, count($mondo->nazioni));
        // 1 pace globale, 2 pace stabile, 3 pace fredda, 4 guerra fredda,
        // 5 guerra calda, 6 pura anarchia.
        $mondo->livelloPace = (int) max(1, min(6, round($media)));

        // La cattiveria del mondo sale con gli interventi e le crisi, e scende
        // solo col tempo (Crawford: "e' diminuita soltanto dal balsamo del
        // tempo"). Qui, per ora, scende e basta.
        $decadimento = $contesto->calibrazione->numero('crisi.decadimento_nastiness', 0.02);
        $mondo->nastiness = max(0.0, $mondo->nastiness * (1.0 - $decadimento));

        // Le squadre appostate che non hanno trovato niente non restano
        // appostate per sempre: prima o poi si richiamano, e il posto si
        // libera per un'altra operazione.
        $ritirate = 0;
        $epocaChiusa = null;
        if ($contesto->db !== null && !$contesto->aVuoto) {
            $ritirate = (new \App\Gioco\Falsificazione($contesto->db))->scadenze($contesto->tick);

            // E quando un'epoca ha finito il suo tempo, si chiude da sola. Non
            // serve un arbitro sveglio: il momento in cui si conta e si scopre
            // deve arrivare comunque, o non arriverebbe mai.
            $epoca = new \App\Gioco\Epoca($contesto->db);
            if ($epoca->restano($contesto->tick) === 0) {
                [, $epocaChiusa] = $epoca->chiudi($contesto->tick);
                $contesto->annota('epoca_chiusa', ['racconto' => $epocaChiusa]);
            }
        }

        return new EsitoFase([
            'livello_pace' => $mondo->livelloPace,
            'manipolazioni_ritirate' => $ritirate,
            'epoca_chiusa' => $epocaChiusa,
        ]);
    }
}
