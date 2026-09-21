<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Dati\Nazione;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 06 — Relazioni, alleanze, integrità.
 *
 * LEGGE:      gli esiti della fase 05, le ideologie, lo stato del mondo
 * SCRIVE:     influenza totale, affinità, umore, sfere, integrità
 * INVARIANTE: la storia pesa più dell'ideologia; l'integrità cala in
 *             proporzione all'obbligo quando cade un cliente
 *
 * È la fase in cui il mondo smette di essere 189 paesi indipendenti e diventa
 * un sistema. Tre cose accadono qui:
 *
 *   1. si ricalcola chi conta — e il peso relativo di economia e forze armate
 *      cambia col clima: "man mano che il mondo si allontana dalla guerra, il
 *      potere economico pesa di più" (Shadow President, glossario);
 *   2. le affinità derivano lentamente verso la compatibilità strutturale, ma
 *      un cambio di regime le scuote di colpo;
 *   3. chi aveva garantito un governo che cade ne paga il prezzo in integrità,
 *      e l'integrità è ciò che rende le promesse costose.
 */
final class Fase06Relazioni implements Fase
{
    public function codice(): string { return '06'; }
    public function nome(): string   { return 'Relazioni, alleanze, integrità'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $cal      = $c->calibrazione;
        $perTick  = 1.0 / $cal->numero('tempo.tick_per_anno', 52.0);
        $recupero = $cal->numero('relazioni.recupero_integrita_anno', 5.0);
        $consumoSpinta = $cal->numero('relazioni.consumo_spinta_anno', 0.12) * $perTick;
        // Quanto un rapporto si muove all'anno in assenza di eventi. Deve
        // essere piccolo: i rapporti fra Stati cambiano per quel che accade,
        // non per il passare del tempo.
        $deriva   = 0.03;
        // Peso della memoria rispetto alla compatibilita' strutturale.
        // Crawford: la storia vale otto volte l'ideologia.
        $pesoAncora = 0.80;

        // --- 1. chi conta ---------------------------------------------------
        $pilMondiale = 0.0;
        $milMondiale = 0.0;
        foreach ($mondo->nazioni as $n) {
            $pilMondiale += $n->pil;
            $milMondiale += $n->potenzaGoverno();
        }
        $pilMondiale = max(1.0, $pilMondiale);
        $milMondiale = max(1.0, $milMondiale);

        // Livello di pace 1 (pace globale) .. 6 (anarchia): in pace conta
        // l'economia, in guerra contano le divisioni.
        $pesoEconomia = 0.86 - 0.07 * ($mondo->livelloPace - 1);

        $grandiPotenze = 0;
        foreach ($mondo->elenco() as $n) {
            $n->influenzaTotale = 100.0 * (
                $pesoEconomia * ($n->pil / $pilMondiale)
                + (1.0 - $pesoEconomia) * ($n->potenzaGoverno() / $milMondiale)
            );
            if ($n->influenzaTotale >= 5.0) {
                $grandiPotenze++;   // la soglia di Shadow President
            }
            // L'integrità si riguadagna piano, e solo tenendo il naso pulito.
            $n->integrita = min(128.0, $n->integrita + $recupero * $perTick);

            // La fama di scorrettezza si accumula in fretta e si smaltisce piano.
            $n->reputazioneSporca = max(0.0, $n->reputazioneSporca - 0.3 * $perTick);
            $n->etica = (int) max(1, min(6, $n->etica + ($n->reputazioneSporca > 4.0 ? 1 : 0)));
            if ($n->reputazioneSporca > 4.0) {
                $n->reputazioneSporca = 0.0;
            }

            // L'ambizione e' la fame di potere: pesa, e si nutre di radicalita'.
            // L'etica NON si deriva dalle istituzioni — legarla alla maturita'
            // rendeva le democrazie mature incapaci di azione coperta, il che
            // e' storicamente falso e cancella meta' del gioco. E' invece,
            // come nel glossario di Shadow President, "il modo in cui il mondo
            // giudica i moventi delle azioni che compi": sta scritta nello
            // stato della nazione e la spostano gli eventi.
            $n->ambizione = (int) max(1, min(6,
                2 + round(min(3.0, $n->influenzaTotale / 4.0))
                  + (abs($n->orientamento) >= 55 ? 1 : 0)));
        }

        // --- 2. chi è caduto in questo tick ----------------------------------
        $caduti = [];
        foreach ($c->giornale() as $voce) {
            if (in_array($voce['genere'], ['rivoluzione', 'colpo_di_stato', 'cambio_governo'], true)) {
                $caduti[(string) $voce['dati']['nazione']] = $voce['genere'];
            }
        }
        // Il giornale porta i nomi; qui servono i codici.
        $codiciCaduti = [];
        foreach ($mondo->nazioni as $n) {
            if (isset($caduti[$n->nome])) {
                $codiciCaduti[$n->iso3] = $caduti[$n->nome];
            }
        }

        // --- 3. le relazioni --------------------------------------------------
        $scosse = 0;
        $perditeIntegrita = 0;

        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            [$isoA, $isoB] = explode('|', $chiave);
            $a = $mondo->nazioni[$isoA] ?? null;
            $b = $mondo->nazioni[$isoB] ?? null;
            if ($a === null || $b === null) {
                continue;
            }

            $strutturale = $this->attrattore($a, $b, $r->confinanti);
            $r->ancora ??= $r->affinita;

            // Un cambio di regime da una parte o dall'altra riscrive il
            // rapporto di colpo — ed è la sola cosa che sposta l'ancora: è il
            // momento in cui la storia entra nel modello.
            $scossa = isset($codiciCaduti[$isoA]) || isset($codiciCaduti[$isoB]);
            if ($scossa) {
                $r->ancora += ($strutturale - $r->ancora) * 0.45;
                $r->affinita += ($r->ancora - $r->affinita) * 0.45;
            }

            $bersaglio = $pesoAncora * $r->ancora + (1.0 - $pesoAncora) * $strutturale;
            $r->affinita += ($bersaglio - $r->affinita) * $deriva * $perTick;
            $r->affinita = max(-127.0, min(127.0, $r->affinita));
            $r->aggiornaUmore();

            if ($scossa) {
                $scosse++;
            }

            // --- integrità: A aveva garantito B, e B è caduto ---------------
            // Solo le cadute IRREGOLARI mettono alla prova una garanzia: se il
            // tuo cliente perde un'elezione non hai tradito nessuno.
            $cadutaIrregolare = ($codiciCaduti[$isoB] ?? '') !== ''
                && ($codiciCaduti[$isoB] === 'rivoluzione' || $codiciCaduti[$isoB] === 'colpo_di_stato');
            if ($cadutaIrregolare && $r->obbligo >= 32) {
                $prima = $a->integrita;
                // La formula di Crawford: un trattato di difesa nucleare (128)
                // azzera l'integrità del garante. Le promesse grosse costano.
                $a->integrita *= 1.0 - ($r->obbligo / 128.0);
                if ($prima - $a->integrita > 1.0) {
                    $perditeIntegrita++;
                    if ($r->obbligo >= 64) {
                        $c->annota('integrita_perduta', [
                            'garante' => $a->nome,
                            'cliente' => $b->nome,
                            'obbligo' => $r->obbligo,
                            'residuo' => round($a->integrita),
                        ]);
                    }
                }
            }

            // --- sfera di influenza ------------------------------------------
            // La parte strutturale — peso, vicinanza, regione — piu' la memoria
            // delle crisi decise su questo paese, che si consuma piano: una
            // vittoria vale anni, non un'eternita'.
            $sfera = 1.0 + 0.9 * min(12.0, $a->influenzaTotale)
                + ($r->confinanti ? 3.0 : 0.0)
                + ($a->regione === $b->regione ? 2.0 : 0.0)
                + $r->spintaSfera;
            $r->sfera = (int) max(1, min(15, round($sfera)));
            $r->spintaSfera *= 1.0 - $consumoSpinta;

            // L'obbligo segue l'affinità, ma con isteresi: un trattato si firma
            // in fretta e si denuncia con fatica.
            $obbligoNaturale = match (true) {
                $r->affinita >= 100 => 96,
                $r->affinita >=  80 => 64,
                $r->affinita >=  55 => 32,
                $r->affinita >=  25 => 16,
                default             =>  0,
            };
            if ($obbligoNaturale > $r->obbligo) {
                $r->obbligo = $obbligoNaturale;
            } elseif ($obbligoNaturale < $r->obbligo && $c->caso->prova('06_trattati', crc32($chiave), $c->tick, 0.02)) {
                $r->obbligo = $obbligoNaturale;
            }
        }

        return new EsitoFase([
            'grandi_potenze' => $grandiPotenze,
            'scosse'         => $scosse,
            'integrita_pers' => $perditeIntegrita,
        ]);
    }

    /**
     * Dove tenderebbe un rapporto se contassero solo la compatibilità
     * ideologica, la contiguità e la differenza di taglia.
     */
    private function attrattore(Nazione $a, Nazione $b, bool $confinanti): float
    {
        $valore = 25.0 - abs($a->orientamento - $b->orientamento) * 0.55;
        if ($confinanti) {
            $valore += abs($a->orientamento - $b->orientamento) < 30 ? 8.0 : -18.0;
        }
        if ($confinanti && $b->pil > $a->pil * 8.0) {
            $valore -= 12.0;
        }
        return max(-127.0, min(127.0, $valore));
    }
}
