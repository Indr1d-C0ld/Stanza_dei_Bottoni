<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Dati\Evento;
use App\Dati\Nazione;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 02 — Maturazione degli eventi.
 *
 * LEGGE:      gli eventi con maturazione <= tick
 * SCRIVE:     lo stato delle nazioni colpite, le relazioni, il giornale
 * INVARIANTE: un evento passa a "realizzato" una volta sola
 *
 * L'ordine dei domini non è casuale: **l'informazione matura per prima**,
 * perché cambia il contesto in cui tutto il resto viene giudicato, e il
 * nucleare per ultimo, perché ridefinisce tutto.
 *
 * Qui accade anche la cosa più importante del modello relazionale: il
 * contraccolpo diplomatico è **proporzionale all'attribuzione**. Un'azione che
 * nessuno può ricondurre a te non ti costa nulla in rapporti, per quanto danno
 * faccia. È questa singola moltiplicazione a rendere la guerra ibrida
 * conveniente e la guerra aperta l'ultimo gradino.
 */
final class Fase02Maturazione implements Fase
{
    private const ORDINE = ['info' => 0, 'eco' => 1, 'soc' => 2, 'int' => 3, 'mil' => 4, 'nuc' => 5];

    public function codice(): string { return '02'; }
    public function nome(): string   { return 'Maturazione degli eventi'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $maturi = [];
        foreach ($mondo->eventi as $e) {
            if ($e->inVolo() && $e->maturazioneTick <= $c->tick) {
                $maturi[] = $e;
            }
        }
        usort($maturi, static fn(Evento $a, Evento $b): int
            => [self::ORDINE[$a->dominio] ?? 9, $a->id] <=> [self::ORDINE[$b->dominio] ?? 9, $b->id]);

        $conteggio = [];
        foreach ($maturi as $e) {
            $mandante  = $mondo->nazioni[$e->mandante]  ?? null;
            $bersaglio = $mondo->nazioni[$e->bersaglio] ?? null;
            if ($mandante === null || $bersaglio === null) {
                $e->stato = Evento::REALIZZATO;
                continue;
            }

            $this->applica($e, $mandante, $bersaglio, $c);
            $this->contraccolpo($e, $mondo, $c);

            // "L'etica riflette il modo in cui il mondo giudica i moventi delle
            // azioni che compi" (Shadow President). Chi lavora nell'ombra se ne
            // fa una fama, e la fama e' lenta a cambiare in meglio.
            if ($e->dominio === 'int' && $e->dannoBase > 40) {
                $mandante->reputazioneSporca += $e->intensita * $e->attribuzioneVera;
            }

            $e->stato = Evento::REALIZZATO;
            $mandante->azioniInVolo = max(0, $mandante->azioniInVolo - 1);
            $conteggio[$e->verbo] = ($conteggio[$e->verbo] ?? 0) + 1;
        }

        // Gli eventi conclusi non servono più: si tiene solo una coda recente,
        // altrimenti trentacinque anni di storia diventano un milione di righe.
        if (count($mondo->eventi) > 4000) {
            $mondo->eventi = array_values(array_filter(
                $mondo->eventi,
                static fn(Evento $e): bool => $e->inVolo() || $e->maturazioneTick > $c->tick - 52,
            ));
        }

        return new EsitoFase(['maturati' => count($maturi)]);
    }

    /** Gli effetti, verbo per verbo. Espliciti: sono la sostanza del gioco. */
    private function applica(Evento $e, Nazione $a, Nazione $b, ContestoTick $c): void
    {
        $i = $e->intensita;
        $r = $c->mondo->relazioni->fra($e->mandante, $e->bersaglio);
        $mondo = $c->mondo;
        $cal   = $c->calibrazione;

        switch ($e->verbo) {
            // --- diplomazia ------------------------------------------------
            case 'emissario':
                break;   // conta solo per il contraccolpo, che qui è positivo
            case 'trattato':
                // Un trattato e' un obbligo MESSO PER ISCRITTO: va anche nella
                // firma, o la fase 06 lo riporta all'obbligo che l'affinita'
                // giustifica con la probabilita' del due per cento a tick, e
                // un patto appena concluso evaporava in un anno.
                foreach ([$r, $c->mondo->relazioni->fra($e->bersaglio, $e->mandante)] as $lato) {
                    if ($lato !== null) {
                        $lato->obbligo        = max($lato->obbligo, 64);
                        $lato->obbligoFirmato = max($lato->obbligoFirmato, 64);
                    }
                }
                break;
            case 'condanna_pubblica':
                $b->clamoreSociale += 2.0 * $i;
                break;
            case 'mediazione':
                if ($b->netPeace >= 4) {
                    $b->forzaInsorti *= 1.0 - 0.18 * $i;
                    $c->annota('mediazione', ['mediatore' => $a->nome, 'paese' => $b->nome]);
                }
                break;

            // --- economia ---------------------------------------------------
            case 'aiuto_economico':
                $b->pil *= 1.0 + 0.006 * $i;
                $b->legittimita = min(100.0, $b->legittimita + 2.2 * $i);
                break;
            case 'investimenti':
                // Un investimento sposta la tendenza, non il livello: e' la
                // differenza fra regalare pesce e costruire un porto.
                $b->crescitaStrutturale = min(0.09, $b->crescitaStrutturale + 0.004 * $i);
                break;
            case 'restrizioni_commerciali':
            case 'embargo':
                // Non si toglie un numero alla crescita del bersaglio: si
                // chiude un rubinetto, e il danno lo calcola il grafo
                // commerciale — per tutti e due. Un embargo verso chi non ci
                // compra niente e' teatro; verso chi dipende da noi per il gas
                // e' un'altra cosa; e in entrambi i casi noi perdiamo il
                // mercato. E' questo che lo rende una decisione.
                $pieno  = $e->verbo === 'embargo';
                $durata = (int) $cal->numero($pieno ? 'commercio.durata_embargo'
                                                    : 'commercio.durata_restrizioni', 52);
                $mondo->strozzature[] = [
                    'fornitore' => $a->iso3,
                    'cliente'   => $b->iso3,
                    // in centesimi, come la salva sdb_strozzatura (tests/13)
                    'quota'     => $pieno ? 1.0 : round(min(1.0, 0.30 * $i), 2),
                    'dal'       => $c->tick,
                    'fine'      => $c->tick + (int) round($durata * (0.6 + 0.4 * $i)),
                ];
                $c->annota($pieno ? 'embargo' : 'restrizioni', [
                    'da'      => $a->nome,
                    'contro'  => $b->nome,
                    'costa_a_noi' => round(100.0 * $mondo->commercio->dannoAlFornitore(
                        $a->iso3, $b->iso3, $pieno ? 1.0 : 0.30 * $i), 2),
                    'costa_a_loro' => round(100.0 * $mondo->commercio->dannoAlCliente(
                        $a->iso3, $b->iso3, $pieno ? 1.0 : 0.30 * $i), 2),
                ]);
                break;

            // --- nucleare ---------------------------------------------------
            case 'programma_nucleare':
                // Il bersaglio di un programma nucleare e' se stessi: si
                // costruisce in casa propria. Chi arriva in fondo sale di un
                // gradino, e superata la soglia entra nel club.
                $a->posturaNucleare = min(7, $a->posturaNucleare + 1);
                $sogliaClub = (int) $cal->numero('nucleare.soglia_armato', 4);
                if ($a->posturaNucleare === $sogliaClub) {
                    $c->annota('bomba_ottenuta', ['paese' => $a->nome]);
                }
                $a->ansiaMilitare = max(0.0, $a->ansiaMilitare - 12.0);
                break;

            // --- informazione -----------------------------------------------
            case 'disinformazione':
                $b->clamoreSociale += 9.0 * $i;
                $b->controlloInfo = max(0.0, $b->controlloInfo - 4.0 * $i);
                break;
            case 'finanziamento_opposizione':
                $b->legittimita = max(0.0, $b->legittimita - 2.6 * $i);
                $b->clamoreSociale += 5.0 * $i;
                break;

            // --- operazioni coperte -------------------------------------------
            case 'sabotaggio':
                $b->pil *= 1.0 - 0.004 * $i;
                $b->equipaggiamento *= 1.0 - 0.025 * $i;
                break;
            case 'armare_insorti':
                // Le armi consegnate agli insorti valgono piu' del loro peso:
                // le usano con piu' cura perche' ne hanno poche (Crawford). Il
                // commento lo diceva da sempre e il codice non lo faceva — il
                // moltiplicatore stava in calibrazione e non lo leggeva nessuno.
                $b->forzaInsorti += $b->potenzaGoverno() * 0.055 * $i
                    * $cal->numero('insurrezione.moltiplicatore_armi_insorti', 2.0);
                $c->annota('armi_ai_ribelli', ['da' => $a->nome, 'in' => $b->nome]);
                break;
            case 'destabilizzare':
                // Si somma alla pressione, non sostituisce la realta': non puoi
                // far cadere un governo che reggerebbe comunque.
                $b->legittimita = max(0.0, $b->legittimita - 5.5 * $i);
                $b->clamoreSociale += 10.0 * $i;
                break;
            case 'colpo_di_stato':
                // Il verbo non scavalca il modello: lo carica. Sara' la fase 05,
                // piu' avanti in questo stesso tick, a decidere se cade davvero.
                $b->legittimita = max(0.0, $b->legittimita - 17.0 * $i);
                $b->clamoreSociale += 18.0 * $i;
                $c->annota('trama', ['da' => $a->nome, 'contro' => $b->nome]);
                break;

            // --- militare -----------------------------------------------------
            case 'vendita_armi':
                $b->equipaggiamento += $b->pil * 0.012 * $i;
                break;
            case 'dimostrazione_forza':
                $b->ansiaMilitare = min(100.0, $b->ansiaMilitare + 18.0 * $i);
                break;
            case 'invasione':
                // Una guerra alla volta fra due paesi. Prima una seconda
                // invasione a guerra gia' aperta ne apriva un'altra accanto,
                // e i due conflitti si sommavano: morti contati due volte,
                // due attriti, due esiti.
                foreach ($c->mondo->guerre as $gg) {
                    if (($gg['aggressore'] === $a->iso3 && $gg['difensore'] === $b->iso3)
                        || ($gg['aggressore'] === $b->iso3 && $gg['difensore'] === $a->iso3)) {
                        break 2;
                    }
                }
                $c->mondo->guerre[] = [
                    'aggressore' => $a->iso3, 'difensore' => $b->iso3,
                    'inizio' => $c->tick, 'morti' => 0.0,
                ];
                $a->netPeace = 6;
                $b->netPeace = 6;
                $b->ansiaMilitare = 100.0;
                $c->annota('guerra', ['aggressore' => $a->nome, 'difensore' => $b->nome]);
                break;
            case 'strike':
                $b->equipaggiamento *= 1.0 - 0.07 * $i;
                $b->legittimita = max(0.0, $b->legittimita - 4.0 * $i);
                $b->netPeace = max($b->netPeace, 4);
                $b->scossaEsterna = max($b->scossaEsterna, 4);
                $b->ansiaMilitare = min(100.0, $b->ansiaMilitare + 30.0 * $i);
                $c->annota('attacco', ['da' => $a->nome, 'contro' => $b->nome]);
                break;
        }
    }

    /**
     * Il contraccolpo diplomatico, moltiplicato per l'attribuzione.
     *
     * È il perno dell'intero progetto: un'azione che nessuno può ricondurre a
     * te non ti costa nulla in rapporti, per quanto danno faccia.
     */
    private function contraccolpo(Evento $e, $mondo, ContestoTick $c): void
    {
        $intel = $mondo->intelligence ?? null;

        // ECCO IL PUNTO. Il bersaglio reagisce a cio' che e' riuscito a
        // DIMOSTRARE, non a cio' che e' vero. Un'operazione coperta e mai
        // attribuita non costa nulla in rapporti, per quanto danno faccia.
        $livello = $intel?->livello($e->bersaglio, $e->id) ?? 4;
        $attribuzione = match (true) {
            $e->impronta >= 0.90 => 1.0,    // un atto dichiarato si attribuisce da se'
            $livello >= 4        => 1.0,
            $livello >= 3        => 0.35,   // sospetti fondati, nessuna prova
            default              => 0.0,
        };

        // Si paga chi il bersaglio CREDE colpevole, non chi lo e'.
        $accusato = $intel?->accusato($e->bersaglio, $e->id, $e->mandante) ?? $e->mandante;
        $rb = $mondo->relazioni->fra($e->bersaglio, $accusato);
        if ($rb === null) {
            return;
        }
        $delta = -($e->dannoBase / 127.0) * 30.0 * $e->intensita * $attribuzione;
        if ($accusato !== $e->mandante && $attribuzione > 0.0) {
            $c->annota('accusa_sbagliata', [
                'bersaglio' => $mondo->nazioni[$e->bersaglio]->nome,
                'incolpa'   => $mondo->nazioni[$accusato]->nome,
                'vero'      => $mondo->nazioni[$e->mandante]->nome,
            ]);
        }
        if (abs($delta) < 0.1) {
            return;
        }
        // Gli eventi muovono l'ANCORA: e' cosi' che la storia entra nel modello
        // e non se ne va con il passare del tempo.
        $rb->ancora = max(-127.0, min(127.0, ($rb->ancora ?? $rb->affinita) + $delta));
        $rb->affinita = max(-127.0, min(127.0, $rb->affinita + $delta * 0.8));
        $rb->aggiornaUmore();

        // E i terzi che hanno dimostrato la stessa cosa reagiscono anche loro,
        // con meno intensita': non e' successo a casa loro, ma hanno visto.
        foreach ($intel?->attribuito[$e->id] ?? [] as $terzo) {
            if ($terzo === $e->bersaglio || $terzo === $e->mandante) {
                continue;
            }
            $rt = $mondo->relazioni->fra($terzo, $intel?->accusato($terzo, $e->id, $e->mandante) ?? $e->mandante);
            if ($rt === null) {
                continue;
            }
            $dt = $delta * 0.30;
            $rt->ancora = max(-127.0, min(127.0, ($rt->ancora ?? $rt->affinita) + $dt));
            $rt->affinita = max(-127.0, min(127.0, $rt->affinita + $dt * 0.8));
            $rt->aggiornaUmore();
        }
    }
}
