<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 04 — Società e legittimità.
 *
 * LEGGE:      il blocco economico appena calcolato
 * SCRIVE:     qualità della vita, ansie, clamore sociale, legittimità, aspettativa
 * INVARIANTE: la legittimità resta in [0,100]
 *
 * Il cuore è l'equazione di Crawford: la popolarità di un governo dipende dal
 * MIGLIORAMENTO del consumo pro capite rispetto a quanto la gente si aspettava,
 * non dal livello assoluto della ricchezza. Un paese ricco che rallenta è più
 * instabile di un paese povero che cresce.
 *
 * Con una correzione nostra: Crawford usava un'aspettativa costante per tutto
 * il mondo (il famoso -3, tarato a playtest). Qui l'aspettativa è per paese e
 * mobile — la gente si aspetta quello che ha avuto di recente. Chi è cresciuto
 * al 7% per tre anni si arrabbia al 4%; chi è fermo da un decennio festeggia
 * all'1%.
 */
final class Fase04Societa implements Fase
{
    public function codice(): string { return '04'; }
    public function nome(): string   { return 'Società e legittimità'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $cal        = $c->calibrazione;
        $tickAnno   = $cal->numero('tempo.tick_per_anno', 52.0);
        $perTick    = 1.0 / $tickAnno;
        $finestra   = max(1.0, $cal->numero('societa.finestra_aspettativa', 5.0));
        $aspMin     = $cal->numero('societa.aspettativa_minima', 0.005);
        $aspMax     = $cal->numero('societa.aspettativa_massima', 0.060);
        $inerzia    = $cal->numero('societa.inerzia_legittimita', 0.85);
        $bonusRad   = $cal->numero('societa.bonus_radicalita', 1.0);
        $rientro    = $cal->numero('societa.rientro_ansie', 0.10);
        $ritornoDeriva  = $cal->numero('societa.ritorno_deriva', 0.15);
        $ampiezzaDeriva = $cal->numero('societa.ampiezza_deriva', 1.1);

        $fragili = 0;

        foreach ($mondo->elenco() as $n) {
            // --- miglioramento e aspettativa -----------------------------
            $precedente = $n->consumoProCapitePrec > 0 ? $n->consumoProCapitePrec : $n->consumoProCapite;
            $variazione = $precedente > 0 ? ($n->consumoProCapite - $precedente) / $precedente : 0.0;
            $annualizzata = $variazione * $tickAnno;

            // media mobile esponenziale sulla finestra dichiarata
            $alfa = $perTick / $finestra;
            $n->aspettativa = max($aspMin, min($aspMax,
                $n->aspettativa * (1.0 - $alfa) + $annualizzata * $alfa));

            $miglioramento = $annualizzata - $n->aspettativa;

            // --- legittimità ---------------------------------------------
            // Inerzia: la gente non si volta contro il governo da un giorno
            // all'altro. Poi il termine economico, poi un piccolo premio ai
            // governi radicali (reprimono il dissenso e non si dividono).
            $radicalita = $bonusRad * (abs($n->orientamento) / 128.0);
            $spinta = ($miglioramento * 180.0 + $radicalita) * $perTick;

            // La deriva politica è un processo a ritorno alla media: si allontana
            // per caso e viene ricondotta verso lo zero. Senza di essa il livello
            // di riposo della legittimità è ESATTAMENTE 50 per ogni paese del
            // mondo e per sempre, e questo basta a rendere l'esito di ogni
            // paese identico in ogni corsa: la casualità sposta solo QUANDO
            // succede, mai SE succede.
            $n->derivaPolitica += (
                -$ritornoDeriva * $n->derivaPolitica
                + $c->caso->rumore('04_deriva', crc32($n->iso3), $c->tick, $ampiezzaDeriva)
            ) * $perTick * $tickAnno * $perTick;

            // ATTENZIONE alle unità: l'inerzia di Crawford è ANNUA. Applicarla
            // per tick significa riportare ogni governo alla media in due mesi,
            // e allora non cade mai nessuno. È l'errore che ha reso inerte la
            // prima versione di questo mondo.
            $n->legittimita += (50.0 + $n->derivaPolitica - $n->legittimita)
                * (1.0 - $inerzia) * $perTick + $spinta;

            // Una guerra civile in corso erode la legittimità da sola.
            if ($n->netPeace >= 5) {
                $n->legittimita -= 6.0 * $perTick;
            }

            $n->legittimita = max(0.0, min(100.0, $n->legittimita));

            // --- qualità della vita --------------------------------------
            // Dieci livelli, come il "parco" del quadrante frontale della città
            // di Shadow President: non solo reddito, anche libertà e paura.
            // Il consumo MEDIANO, non quello medio: la qualita' della vita e'
            // quel che sente la maggior parte della gente, e la media non lo
            // dice. In Sudafrica il cittadino mediano vive con il 58% di quel
            // che la media promette, negli Stati Uniti col 74%, in Norvegia
            // con l'89% — e il rapporto non e' inventato, si deriva dal Gini
            // della Banca Mondiale assumendo redditi lognormali (vedi
            // Nazione::consumoMediano()).
            $reddito = $n->consumoMediano();
            $livello = match (true) {
                $reddito >= 40000 => 10, $reddito >= 28000 => 9,
                $reddito >= 18000 => 8,  $reddito >= 11000 => 7,
                $reddito >=  7000 => 6,  $reddito >=  4500 => 5,
                $reddito >=  2800 => 4,  $reddito >=  1600 => 3,
                $reddito >=   800 => 2,  default           => 1,
            };
            // Uno stato di polizia e una guerra civile tolgono qualità della
            // vita anche a reddito invariato.
            // Lo stato di polizia e' continuo: qui serve un gradino intero,
            // e si prende quello raggiunto.
            $livello -= (int) max(0, floor($n->statoPolizia - 2.0));
            if ($n->netPeace >= 4) {
                $livello -= 1;
            }
            $n->qualitaVita = (int) max(1, min(10, $livello));


            // --- le ansie che passano ---------------------------------------
            //
            // L'ansia militare saliva e non scendeva mai: una dimostrazione di
            // forza subita nel 2027 pesava identica quindici anni dopo.
            //
            // Ma non scende a zero: torna al livello di riposo che l'ambiente
            // di sicurezza impone. Chi vive in un mondo in guerra fredda ha una
            // sua ansia di fondo anche quando non gli sta succedendo niente, e
            // chi e' in guerra ce l'ha alta per definizione. Alla prima
            // stesura la facevo decadere verso lo zero e l'ansia militare
            // diventava zero per tutti, guerre comprese: l'errore opposto.
            $riposo = 5.0 + 5.0 * ($mondo->livelloPace - 1)
                + match (true) {
                    $n->netPeace >= 5 => 40.0,
                    $n->netPeace >= 4 => 20.0,
                    default           => 0.0,
                };
            $n->ansiaMilitare = max(0.0, min(100.0, $n->ansiaMilitare
                + ($riposo - $n->ansiaMilitare)
                  * $cal->numero('societa.sfogo_ansia_anno', 14.0) / 100.0 * $perTick * 4.0));

            // --- clamore sociale ------------------------------------------
            // Più la legittimità è bassa, più il governo è esposto: dal
            // capannello di dissidenti fino all'attentato.
            $obiettivoClamore = max(0.0, (45.0 - $n->legittimita) * 1.6);
            $n->clamoreSociale += ($obiettivoClamore - $n->clamoreSociale) * $rientro;

            if ($n->legittimita < 25.0) {
                $fragili++;
            }
        }

        return new EsitoFase(['fragili' => $fragili]);
    }
}
