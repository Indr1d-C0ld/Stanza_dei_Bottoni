<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Dati\Nazione;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 05 — Insurrezioni e cambi di esecutivo.
 *
 * LEGGE:      forze del governo e degli insorti, legittimità, maturità
 * SCRIVE:     forza degli insorti, net peace, cambi di regime
 * INVARIANTE: al massimo UN cambio di esecutivo per nazione per tick
 *
 * Due processi distinti, come li separa Crawford: l'insurrezione è una prova di
 * forza che dura anni, il cambio di esecutivo è un fatto di ore preparato in
 * mesi. Il primo dipende dalla maturità istituzionale, il secondo dall'economia.
 */
final class Fase05SicurezzaInterna implements Fase
{
    public function codice(): string { return '05'; }
    public function nome(): string   { return 'Insurrezioni e cambi di esecutivo'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $cal      = $c->calibrazione;
        $tickAnno = $cal->numero('tempo.tick_per_anno', 52.0);
        $perTick  = 1.0 / $tickAnno;
        $attrito  = $cal->numero('insurrezione.attrito_anno', 0.25) * $perTick;
        $carrozzone = $cal->numero('insurrezione.effetto_carrozzone', 0.20);
        $kReclutamento = $cal->numero('insurrezione.reclutamento_k', 2.5);
        $sogliaColpo = $cal->numero('colpo_di_stato.soglia_legittimita', 22.0);
        $rischioMax  = $cal->numero('colpo_di_stato.rischio_massimo_anno', 0.9);
        $pendenza    = $cal->numero('colpo_di_stato.pendenza', 6.0);
        $resistenzaEstremi = $cal->numero('colpo_di_stato.resistenza_estremisti', 2.0);

        $soglie = [
            'pace'         => $cal->numero('insurrezione.soglia_pace', 512.0),
            'terrorismo'   => $cal->numero('insurrezione.soglia_terrorismo', 32.0),
            'guerriglia'   => $cal->numero('insurrezione.soglia_guerriglia', 2.0),
            'guerraCivile' => $cal->numero('insurrezione.soglia_guerra_civile', 1.0),
        ];

        $colpi = 0;
        $rivoluzioni = 0;
        $inConflitto = 0;

        foreach ($mondo->elenco() as $n) {
            $seme = crc32($n->iso3);

            // --- reclutamento ---------------------------------------------
            // Tre fattori: quanta gente c'è, quanto è debole lo stato di
            // diritto (la "maturity" che Crawford confessa di aver inventato),
            // e quanto l'insurrezione sta già andando bene — nessuno vuole
            // salire su un carro che perde.
            // ATTENZIONE alle unità. La forza del governo è una media
            // geometrica di uomini ed equipaggiamento; contare gli insorti a
            // testa le rende incommensurabili, e il governo li annienta sempre.
            // Anche il reclutamento va quindi in unità di POTENZA, e scala con
            // la radice della popolazione come vi scala la potenza del governo.
            // Nella formula di Crawford il reclutamento insurrezionale dipende
            // da popolazione, DEBOLEZZA ISTITUZIONALE e successo accumulato —
            // la popolarita' del governo non vi compare affatto: quella decide
            // i colpi di stato, non le guerriglie. Legare tutto al malcontento
            // significava che uno Stato fragile con un governo momentaneamente
            // amato non aveva alcuna insurrezione, il che non somiglia a
            // nessun posto reale.
            $malcontento = max(0.0, (55.0 - $n->legittimita) / 55.0);
            $debolezza   = 1.0 - ($n->maturita / 255.0);
            $spinta      = 0.30 + 0.70 * $malcontento;
            if ($debolezza > 0.15) {
                $successo = $n->forzaInsorti > 0.0
                    ? min(1.0, $n->forzaInsorti / max(1.0, $n->potenzaGoverno()))
                    : 0.0;
                $reclute = $kReclutamento * sqrt($n->popolazione) * $spinta * ($debolezza ** 1.6)
                    * (1.0 + $carrozzone * $successo) * $perTick;
                $n->forzaInsorti += $reclute;
            } else {
                // Senza malcontento l'insurrezione si sfalda da sola.
                $n->forzaInsorti *= (1.0 - 0.5 * $perTick);
            }

            // --- attrito ---------------------------------------------------
            // Ciascuno toglie all'altro un quarto della PROPRIA forza all'anno:
            // se sono entrambi forti muoiono in molti.
            if ($n->forzaInsorti > 0.5) {
                $potenzaGoverno = $n->potenzaGoverno();
                $dannoAgliInsorti = $potenzaGoverno * $attrito;
                $dannoAlGoverno   = $n->forzaInsorti * $attrito;

                $n->forzaInsorti = max(0.0, $n->forzaInsorti - $dannoAgliInsorti);
                // Il danno al governo si scarica sull'equipaggiamento e sugli
                // uomini, in proporzione.
                $quota = $potenzaGoverno > 0 ? min(0.5, $dannoAlGoverno / $potenzaGoverno) : 0.0;
                $n->equipaggiamento *= (1.0 - $quota * 0.5);
                $n->soldati         *= (1.0 - $quota * 0.25);
            }

            // --- stato del conflitto ---------------------------------------
            $rapporto = $n->rapportoForze();
            $primaEra = $n->netPeace;
            $n->netPeace = match (true) {
                $n->forzaInsorti < 1.0             => 2,
                $rapporto > $soglie['pace']        => 2,
                $rapporto > $soglie['terrorismo']  => 3,
                $rapporto > $soglie['guerriglia']  => 4,
                $rapporto > $soglie['guerraCivile']=> 5,
                default                            => 6,
            };
            if ($n->netPeace >= 4) {
                $inConflitto++;
            }
            if ($n->netPeace !== $primaEra && $n->netPeace >= 4) {
                $c->annota('conflitto', [
                    'nazione' => $n->nome,
                    'livello' => $n->netPeace,
                    'tick'    => $c->tick,
                ]);
            }

            // --- vittoria degli insorti ------------------------------------
            $tregua = ($c->tick - $n->annoUltimoCambio) < (int) ($tickAnno * 2);
            if ($rapporto < $soglie['guerraCivile'] && $n->forzaInsorti > 1.0 && !$tregua) {
                $this->rivoluzione($n, $c);
                $rivoluzioni++;
                continue;   // l'invariante: un solo cambio per nazione per tick
            }

            // --- cambio di esecutivo ---------------------------------------
            // NON una soglia ma un RISCHIO CONTINUO. Una soglia netta rende
            // l'esito di ogni paese una proprietà della sua struttura: sopra
            // non cade mai, sotto cade sempre, e la casualità sposta soltanto
            // la data. Nel mondo vero i governi cadono anche con consensi
            // discreti, solo più di rado — ed è quel "più di rado" a fare la
            // differenza fra una storia e un orologio.
            $centro = $sogliaColpo + $resistenzaEstremi * (abs($n->orientamento) / 128.0);
            $rischioAnnuo = $rischioMax / (1.0 + exp(($n->legittimita - $centro) / $pendenza));
            // Il clamore accelera, senza essere lui a decidere.
            $rischioAnnuo *= 1.0 + $n->clamoreSociale / 120.0;

            // E il palazzo pesa quanto la piazza: un capo puo' essere amato nel
            // paese e finito dentro le mura, se le fazioni che lo hanno messo
            // li' hanno smesso di volerlo. E' il Panel di CyberJudas — chi ti
            // ha dato il potere e' anche chi te lo toglie.
            $gab = $mondo->gabinetti[$n->iso3] ?? null;
            if ($gab !== null) {
                $rischioAnnuo *= 1.0 + $gab->pressioneInterna() / 55.0;
            }

            $tregua = (int) ($tickAnno * (0.8 + 1.8 * $c->caso->frazione('05_tregua', $seme, $n->cambiEsecutivo)));
            if (($c->tick - $n->annoUltimoCambio) >= $tregua
                && $c->caso->prova('05_colpo', $seme, $c->tick, $rischioAnnuo * $perTick)) {
                $this->cambioEsecutivo($n, $c);
                $colpi++;
            }
        }

        return new EsitoFase([
            'in_conflitto' => $inConflitto,
            'colpi'        => $colpi,
            'rivoluzioni'  => $rivoluzioni,
        ]);
    }

    /** I ribelli vincono: si scambiano i posti, e il pendolo politico si inverte. */
    private function rivoluzione(Nazione $n, ContestoTick $c): void
    {
        $n->orientamento = $n->orientamento !== 0 ? (int) (-$n->orientamento * 1.2) : 64;
        $n->orientamento = max(-128, min(128, $n->orientamento));

        // Il punto che nella prima stesura mi era sfuggito, e che fa la
        // differenza fra una storia e un frullatore: gli insorti non
        // scompaiono, DIVENTANO l'esercito. Se il vincitore eredita uno Stato
        // più debole di quello che ha appena battuto, il paese ricade in
        // rivoluzione ogni pochi mesi all'infinito.
        $potenzaVincitori = $n->forzaInsorti;
        $n->soldati = max(1000.0, $n->soldati * 0.5 + $potenzaVincitori * 0.5);
        $n->equipaggiamento = max(
            1.0,
            $n->soldati > 0 ? ($n->equipaggiamento * 0.4 + $potenzaVincitori ** 2 / max(1.0, $n->soldati)) : 1.0,
        );
        $n->forzaInsorti = 0.0;

        // E il credito che la gente concede sempre a chi arriva: alto abbastanza
        // da azzerare il malcontento per qualche stagione.
        $n->derivaPolitica = $c->caso->rumore('05_dopo_rivoluzione', crc32($n->iso3), $c->tick, 16.0);
        $n->legittimita  = (50.0 + $n->derivaPolitica) + 7.0 - abs($n->orientamento) / 10.0;
        $n->netPeace     = 3;
        $n->vittorieInsorti++;
        $n->cambiEsecutivo++;
        $n->cambiIrregolari++;
        $n->annoUltimoCambio = $c->tick;
        $c->annota('rivoluzione', ['nazione' => $n->nome, 'tick' => $c->tick, 'orientamento' => $n->orientamento]);
    }

    /** Cambio al vertice: cambia chi comanda, non l'apparato. */
    private function cambioEsecutivo(Nazione $n, ContestoTick $c): void
    {
        $irregolare = $n->maturita < 140;
        // Un nuovo governo non è il precedente con la legittimità ricaricata:
        // è gente diversa, con fortuna diversa. Alcuni consolidano per un
        // decennio, altri cadono in sei mesi, e questo NON è deducibile.
        $n->derivaPolitica = $c->caso->rumore('05_nuovogoverno', crc32($n->iso3), $c->tick, 14.0);
        $n->legittimita = (50.0 + $n->derivaPolitica)
            + ($irregolare ? 3.0 : 5.0)
            + $c->caso->rumore('05_luna_di_miele', crc32($n->iso3), $c->tick, 5.0);
        $n->clamoreSociale *= 0.4;
        // Un cambio irregolare erode la fiducia nelle istituzioni: se è potuto
        // accadere una volta, può riaccadere.
        if ($irregolare) {
            $n->maturita = max(10, $n->maturita - 3);
            $n->orientamento = max(-128, min(128, -$n->orientamento + ($n->orientamento === 0 ? 24 : 0)));
        }
        $n->cambiEsecutivo++;
        if ($irregolare) {
            $n->cambiIrregolari++;
        }
        $n->annoUltimoCambio = $c->tick;
        $c->annota($irregolare ? 'colpo_di_stato' : 'cambio_governo', [
            'nazione' => $n->nome,
            'tick'    => $c->tick,
        ]);
    }
}
