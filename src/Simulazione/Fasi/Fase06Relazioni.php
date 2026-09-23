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
 * SCRIVE:     influenza totale, affinità, umore, sfere, integrità, postura nucleare
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
        /** @var array<string,int> $tavolaObblighi */
        $tavolaObblighi = (array) $cal->leggi('relazioni.obbligo', []);
        $rotturaTrattato = $c->calibrazione->numero('relazioni.rottura_trattato', -35.0);
        $sogliaArmata = (int) $c->calibrazione->numero('nucleare.soglia_armato', 4);
        /** @var array<string,int> $soglie */
        $soglie = (array) $cal->leggi('relazioni.soglie_obbligo', []);
        $consumoSpinta = $cal->numero('relazioni.consumo_spinta_anno', 0.12) * $perTick;
        // Quanto un rapporto si muove all'anno in assenza di eventi. Deve
        // essere piccolo: i rapporti fra Stati cambiano per quel che accade,
        // non per il passare del tempo.
        $deriva   = 0.03;
        // Peso della memoria rispetto alla compatibilita' strutturale.
        // Crawford: la storia vale otto volte l'ideologia. Il rapporto sta in
        // calibrazione e da li' si ricava la quota — prima era un 0,80 inciso
        // qui e la chiave non la leggeva nessuno.
        $volte = max(0.0, $cal->numero('relazioni.peso_storia_su_ideologia', 8.0));
        $pesoAncora = $volte / ($volte + 1.0);

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
        $irregolare = static fn(string $iso): bool =>
            in_array($codiciCaduti[$iso] ?? '', ['rivoluzione', 'colpo_di_stato'], true);

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
            //
            // Di REGIME, appunto: un governo che cade per sfiducia in una
            // democrazia ('cambio_governo') non cambia ideologia ne' alleanze,
            // e prima spostava l'ancora come una rivoluzione — ogni crisi di
            // governo a Roma riscriveva mezzo secolo di rapporti dell'Italia.
            $scossa = $irregolare($isoA) || $irregolare($isoB);
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
            //
            // E solo chi aveva PROMESSO protezione: basi o patto di difesa
            // (obbligo >= 64). Con la soglia a 32 contava anche il trattato
            // commerciale, e ogni colpo di Stato colpiva decine di partner che
            // non avevano garantito niente: nel mondo vivo la mediana
            // dell'integrita' era scesa a 19 su 128, e l'integrita' non
            // distingueva piu' chi mantiene la parola da chi no.
            if ($irregolare($isoB) && $r->obbligo >= 64) {
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
            // La tavola di Crawford, letta dalla calibrazione invece che
            // incisa qui. Prima si fermava a 96 e il gradino piu' alto — la
            // garanzia di difesa nucleare — non era raggiungibile da nessuno:
            // tradire un patto non poteva mai costare tutta la credibilita',
            // perche' il denominatore della formula e' 128 e il numeratore non
            // ci arrivava mai.
            $obbligoNaturale = match (true) {
                $r->affinita >= ($soglie['difesa_nuc']   ?? 100) => $tavolaObblighi['difesa_nuc']   ?? 128,
                $r->affinita >= ($soglie['difesa_conv']  ??  88) => $tavolaObblighi['difesa_conv']  ??  96,
                $r->affinita >= ($soglie['basi']         ??  72) => $tavolaObblighi['basi']         ??  64,
                $r->affinita >= ($soglie['commerciali']  ??  50) => $tavolaObblighi['commerciali']  ??  32,
                $r->affinita >= ($soglie['diplomatiche'] ??  22) => $tavolaObblighi['diplomatiche'] ??  16,
                default                                          => $tavolaObblighi['nessuna']      ??   0,
            };
            // Un trattato si firma in fretta e si denuncia con fatica: il
            // rialzo e' immediato, il ribasso e' una probabilita' per tick.
            //
            // MA NON SI SCENDE SOTTO QUEL CHE E' SCRITTO. Prima si scendeva, e
            // siccome il due per cento per tick su quindici anni e' una
            // certezza, la struttura di alleanze del mondo si sfaldava: da
            // 2.763 patti di difesa a diciassette, e ZERO garanzie messe alla
            // prova in quindici anni. L'integrita' — il meccanismo con cui
            // Crawford rende costose le promesse — non aveva su cosa mordere.
            //
            // Le alleanze vere non si sciolgono perche' due governi si
            // raffreddano. La Grecia e la Turchia stanno nella NATO da
            // settant'anni senza volersi bene, e la Francia usci' dal comando
            // integrato senza uscire dal patto. Il trattato cede solo quando
            // il rapporto si ROMPE davvero.
            // Il gradino nucleare non si concede per simpatia: e' una proprieta'
            // dell'ARSENALE del garante. Senza questo tetto la Germania, la
            // Spagna e la Nuova Zelanda risultavano garanti nucleari — erano
            // quattordici, contro nove Stati che l'atomica ce l'hanno davvero.
            $garanteIso = explode('|', (string) $chiave)[0];
            $chiGarantisce = $mondo->nazioni[$garanteIso] ?? null;
            // Il tetto vale anche per la firma e per l'obbligo in corso: un
            // garante che posa l'arsenale smette SUBITO di offrire l'ombrello,
            // non con la probabilita' del due per cento con cui si denuncia un
            // trattato. Prima il pavimento della firma lo scavalcava.
            $tetto = ($chiGarantisce !== null && $chiGarantisce->posturaNucleare < $sogliaArmata) ? 96 : 255;
            $obbligoNaturale = min($obbligoNaturale, $tetto);
            $r->obbligo = min($r->obbligo, $tetto);

            if ($obbligoNaturale > $r->obbligo) {
                $r->obbligo = $obbligoNaturale;
            } elseif ($obbligoNaturale < $r->obbligo
                && $c->caso->prova('06_trattati', crc32($chiave), $c->tick, 0.02)) {
                $pavimento = $r->affinita <= $rotturaTrattato ? 0 : min($r->obbligoFirmato, $tetto);
                $r->obbligo = max($obbligoNaturale, $pavimento);
            }
            // E se il rapporto si e' rotto, la firma non vale piu' nemmeno
            // come pavimento: e' la denuncia vera, e va detta una volta sola.
            if ($r->affinita <= $rotturaTrattato) {
                $r->obbligoFirmato = 0;
            }
        }


        // --- la bomba -----------------------------------------------------
        $proliferati = $this->laBomba($c);

        return new EsitoFase([
            'grandi_potenze' => $grandiPotenze,
            'scosse'         => $scosse,
            'integrita_pers' => $perditeIntegrita,
            'nucleare'       => $proliferati,
        ]);
    }

    /**
     * Dove tenderebbe un rapporto se contassero solo la compatibilità
     * ideologica, la contiguità e la differenza di taglia.
     */

    /**
     * Chi prende la bomba, e chi la posa.
     *
     * Prima non succedeva: posturaNucleare non veniva scritta da nessuna fase,
     * quindi in quindici anni di gioco nessun paese ne usciva armato e nessuno
     * si disarmava. In un gioco che si chiama Stanza dei Bottoni era la lacuna
     * piu' vistosa — e per giunta quel numero e' quello che, dal documento
     * sulle crisi, tiene ferme le mani in cima alla scala.
     *
     * Tre forze, e servono tutte e tre.
     *
     * **Il movente.** Ci si arma quando si ha paura e si vuole contare. La
     * paura e' l'ansia militare, la guerra in corso, e soprattutto un vicino
     * ostile che ce l'ha gia'.
     *
     * **La capacita'.** Serve un'economia, istituzioni e gente istruita. Il
     * movente da solo non basta: se bastasse, meta' del mondo sarebbe armata.
     *
     * **L'ombrello.** Chi e' protetto da una garanzia forte di una potenza
     * nucleare non ha bisogno della propria, ed e' storicamente il freno piu'
     * efficace che si conosca. Quando l'ombrello si chiude, il movente torna.
     *
     * Il disarmo e' l'operazione inversa e piu' rara: paura passata,
     * istituzioni solide, e qualcuno che garantisce al posto tuo.
     */
    private function laBomba(ContestoTick $c): int
    {
        $cal = $c->calibrazione;
        $perTick = 1.0 / $cal->numero('tempo.tick_per_anno', 52.0);
        $rateo   = $cal->numero('nucleare.rateo_proliferazione_anno', 0.0022) * $perTick;
        $rateoGiu = $cal->numero('nucleare.rateo_disarmo_anno', 0.0016) * $perTick;
        $sogliaArmato = (int) $cal->numero('nucleare.soglia_armato', 4);

        $mondo = $c->mondo;
        $mosse = 0;

        // Si guarda UNA volta sola chi e' armato e che rapporti ha col resto
        // del mondo. La prima stesura rifaceva il giro di tutte le
        // tremilasettanta relazioni per ciascuna delle centottantanove nazioni
        // a ogni tick: mezzo miliardo di passaggi, e il mondo a vuoto non
        // finiva piu'.
        $ombrelli = [];
        $minacce  = [];
        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            [$da, $verso] = explode('|', $chiave);
            $altro = $mondo->nazioni[$da] ?? null;
            if ($altro === null || $altro->posturaNucleare < $sogliaArmato) {
                continue;
            }
            if ($r->obbligo >= 96) {
                $ombrelli[$verso] = max($ombrelli[$verso] ?? 0.0, 1.0);
            } elseif ($r->obbligo >= 64) {
                $ombrelli[$verso] = max($ombrelli[$verso] ?? 0.0, 0.6);
            }
            // Un vicino armato e ostile e' il movente piu' forte che esista —
            // ma «vicino» vuol dire che ci si tocca. Con la sola regione in
            // comune bastava un'inimicizia tiepida a mezzo continente di
            // distanza, e il modello armava la Svizzera, la Slovenia e la
            // Finlandia: paesi che ne avrebbero la capacita' e non il movente.
            $vicino = $mondo->nazioni[$verso] ?? null;
            if ($vicino === null) {
                continue;
            }
            if ($r->confinanti && $r->affinita < -30.0) {
                $minacce[$verso] = max($minacce[$verso] ?? 0.0, $r->affinita < -70.0 ? 1.0 : 0.6);
            } elseif ($altro->regione === $vicino->regione && $r->affinita < -80.0) {
                // Stessa regione ma non confinanti: serve un'ostilita' vera.
                $minacce[$verso] = max($minacce[$verso] ?? 0.0, 0.45);
            }
        }

        foreach ($mondo->elenco() as $n) {
            $ombrello     = $ombrelli[$n->iso3] ?? 0.0;
            $vicinoArmato = $minacce[$n->iso3] ?? 0.0;

            $paura = min(1.0, $n->ansiaMilitare / 100.0
                + ($n->netPeace >= 5 ? 0.5 : 0.0)
                + $vicinoArmato * 0.8);
            $volonta = $paura * (0.5 + ($n->ambizione - 2) * 0.18) * (1.0 - 0.85 * $ombrello);
            // La capacita' di costruire l'atomica e' tecnica e industriale:
            // reddito e istruzione. Qui c'era anche `maturita`, che di quei
            // due e' una funzione (r = 0,989 col logaritmo del reddito): era
            // lo stesso segnale moltiplicato per se' stesso.
            $capacita = min(1.0, $n->pilProCapite / 25000.0)
                * max(0.0, min(1.0, $n->alfabetizzazione))
                * min(1.0, $n->pil / 250000.0);

            if ($n->posturaNucleare < $sogliaArmato) {
                // Sotto una certa volonta' non si prova nemmeno. Senza questa
                // soglia restava una probabilita' minuscola ma non nulla, e su
                // centottantanove paesi per settecentottanta tick produceva la
                // Svizzera atomica: capacita' da vendere, movente nessuno.
                if ($volonta < $cal->numero('nucleare.soglia_volonta', 0.14)) {
                    continue;
                }
                $p = $rateo * $volonta * $capacita * 40.0;
                if ($p > 0.0 && $c->caso->prova('06_bomba', crc32($n->iso3), $c->tick, min(0.02, $p))) {
                    $n->posturaNucleare = min(7, $n->posturaNucleare + 1);
                    $mosse++;
                    if ($n->posturaNucleare >= $sogliaArmato) {
                        $c->annota('bomba_ottenuta', ['paese' => $n->nome]);
                    }
                }
                continue;
            }

            // Chi ce l'ha la tiene, quasi sempre. Si posa solo quando la paura
            // e' passata, le istituzioni reggono, e qualcun altro garantisce.
            $sicuro = $paura < 0.15 && $n->netPeace <= 2;
            // Ci si disarma quando «le istituzioni reggono»: e' una questione
            // di istituzioni, non di reddito. Il Sudafrica smantello' il
            // proprio arsenale nel 1989 uscendo dall'apartheid, non
            // arricchendosi.
            if ($sicuro && $n->democrazia > 0.35 && $ombrello > 0.5) {
                $p = $rateoGiu * $n->democrazia;
                if ($c->caso->prova('06_disarmo', crc32($n->iso3), $c->tick, min(0.01, $p))) {
                    $n->posturaNucleare = max(1, $n->posturaNucleare - 1);
                    $mosse++;
                    if ($n->posturaNucleare < $sogliaArmato) {
                        $c->annota('bomba_posata', ['paese' => $n->nome]);
                    }
                }
            }
        }
        return $mosse;
    }
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
