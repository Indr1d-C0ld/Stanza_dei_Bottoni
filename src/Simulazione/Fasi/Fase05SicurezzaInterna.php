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
        $kReclutamento = $cal->numero('insurrezione.reclutamento', 1.0);
        $pesoEsclusione     = $cal->numero('insurrezione.peso_esclusione', 3.0);
        $pesoFrammentazione = $cal->numero('insurrezione.peso_frammentazione', 0.5);
        $sogliaColpo = $cal->numero('colpo_di_stato.soglia_legittimita', 34.0);
        $rischioMax  = $cal->numero('colpo_di_stato.rischio_massimo_anno', 0.024);
        $pendenza    = $cal->numero('colpo_di_stato.pendenza', 18.0);
        $resistenzaEstremi = $cal->numero('colpo_di_stato.resistenza_estremisti', 2.0);
        $blindatura        = $cal->numero('colpo_di_stato.blindatura', 3.0);
        $trappola          = $cal->numero('colpo_di_stato.trappola', 3.1);
        $trappolaTick      = (int) round($cal->numero('colpo_di_stato.trappola_anni', 10.0) * $tickAnno);
        $vittoriaInsorti   = $cal->numero('insurrezione.vittoria_insorti_anno', 0.18);
        $rispostaGoverno   = $cal->numero('insurrezione.risposta_governo', 6.0);
        $innescoBase       = $cal->numero('insurrezione.innesco_base_anno', 0.010);
        $innescoMassimo    = $cal->numero('insurrezione.innesco_massimo_anno', 0.10);
        $spostamentoRegime = $cal->numero('instabilita.spostamento_regime', 24.0);
        $pesoFaziosita     = $cal->numero('instabilita.peso_faziosita', 0.2);
        $protezioneChiusura = $cal->numero('instabilita.protezione_chiusura', 10.0);
        $pesoQualitaVita = $cal->numero('instabilita.peso_qualita_vita', 2.7);
        $pesoVicinato  = $cal->numero('instabilita.peso_vicinato', 1.2);

        $soglie = [
            'pace'         => $cal->numero('insurrezione.soglia_pace', 512.0),
            'terrorismo'   => $cal->numero('insurrezione.soglia_terrorismo', 32.0),
            'guerriglia'   => $cal->numero('insurrezione.soglia_guerriglia', 2.0),
            'guerraCivile' => $cal->numero('insurrezione.soglia_guerra_civile', 1.0),
        ];

        // --- i vicini in conflitto, contati UNA volta -------------------
        // Il contagio di PITF ha bisogno di sapere quanti confinanti sono in
        // guerra. Contarlo dentro il ciclo delle nazioni sarebbe O(n^2) a ogni
        // tick su centottantanove paesi: si conta qui, una volta, sullo stato
        // con cui la fase e' entrata.
        //
        // ATTENZIONE: si legge il netPeace di INIZIO fase, non quello che il
        // ciclo qui sotto sta riscrivendo. Altrimenti il contagio dipenderebbe
        // dall'ordine alfabetico in cui le nazioni vengono visitate, e il
        // mondo avrebbe una freccia del tempo che punta da 'AFG' a 'ZWE'.
        $viciniInConflitto = [];
        foreach ($mondo->elenco() as $x) {
            $viciniInConflitto[$x->iso3] = 0;
        }
        // La Relazione non porta i due codici: stanno nella chiave «A|B», e
        // ogni coppia compare in entrambi i versi perche' i rapporti sono
        // asimmetrici. Un giro solo basta quindi a contare i due lati.
        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            if (!$r->confinanti) {
                continue;
            }
            $pezzi = explode('|', $chiave);
            if (count($pezzi) !== 2) {
                continue;
            }
            [$io, $lui] = $pezzi;
            $altro = $mondo->nazioni[$lui] ?? null;
            if ($altro !== null && $altro->netPeace >= 4 && isset($viciniInConflitto[$io])) {
                $viciniInConflitto[$io]++;
            }
        }

        // Chi e' in guerra con un altro Stato, adesso.
        $belligeranti = [];
        foreach ($mondo->guerre as $g) {
            $belligeranti[(string) $g['aggressore']] = true;
            $belligeranti[(string) $g['difensore']] = true;
        }

        $colpi = 0;
        $rivoluzioni = 0;
        $inConflitto = 0;

        $rispostaPolizia = $cal->numero('sicurezza.risposta_polizia_anno', 0.55) * $perTick;
        $sogliaRepressione = $cal->numero('sicurezza.soglia_repressione', 28.0);

        foreach ($mondo->elenco() as $n) {
            $seme = crc32($n->iso3);

            // --- lo stato di polizia ---------------------------------------
            //
            // Era un numero fermo: valeva 2 in tutti e centottantanove i paesi,
            // per sempre, e le tre fasi che lo leggono — liberta' di stampa,
            // malcontento, ammissibilita' delle elezioni — calcolavano tutte
            // una costante. La Corea del Nord e la Norvegia avevano la stessa
            // polizia.
            //
            // Ora si muove, e segue due forze contrarie. Un governo che si
            // sente minacciato stringe, e stringe tanto piu' quanto meno ha
            // istituzioni che facciano il lavoro al posto della forza. Un
            // governo che sta bene e ha istituzioni solide allenta, perche' la
            // repressione costa e non serve.
            $minaccia = $n->clamoreSociale
                + max(0.0, 45.0 - $n->legittimita)
                + ($n->haInsorti() ? 25.0 : 0.0);
            $freno = $n->maturita / 255.0;   // istituzioni: chi ne ha, reprime meno

            // Si converge verso un livello, non si deriva: senza un obiettivo
            // il valore scivolava fino al pavimento e centosettanta paesi su
            // centottantanove finivano esattamente a 1.
            //
            // E il livello di partenza e' quello del REGIME, non 1 per tutti:
            // senza minaccia la Corea del Nord resta la Corea del Nord e la
            // Norvegia la Norvegia (V-Dem, Nazione::basePolizia, docs/31). La
            // minaccia spinge da li' verso la morsa piena.
            $base = $n->basePolizia();
            $obiettivo = $base + (5.0 - $base) * 0.9 * min(1.0, $minaccia / (2.2 * $sogliaRepressione))
                * (1.0 - 0.55 * $freno);
            $obiettivo = max(1.0, min(5.0, $obiettivo));

            // Stringere e' rapido, allentare e' lento: una polizia costruita
            // non si smonta alla prima stagione tranquilla.
            $velocita = $obiettivo > $n->statoPolizia
                ? $rispostaPolizia
                : $rispostaPolizia * 0.35;
            $n->statoPolizia = max(1.0, min(5.0,
                $n->statoPolizia + ($obiettivo - $n->statoPolizia) * $velocita));

            // Chi stringe la presa sulla piazza la stringe anche sul racconto.
            // Prima il controllo dell'informazione poteva solo SCENDERE — lo
            // abbassava la disinformazione altrui e non lo alzava niente — e
            // dopo quindici anni centosettantacinque paesi su centottantanove
            // erano ancora esattamente al valore di partenza.
            // Quanto un regime stringe sul racconto e' una questione di
            // istituzioni, non di reddito: gli Emirati e Singapore sono
            // ricchissimi e controllano moltissimo. Qui c'era `maturita`.
            //
            // Anche qui il punto di partenza e' la censura del regime (V-Dem):
            // la formula di prima — 18 + 17 per la polizia - 22 per la
            // democrazia — metteva Singapore e la Norvegia allo stesso
            // livello, e la stretta si aggiunge sopra (docs/31).
            $obiettivoInfo = 100.0 * $n->censura + 17.0 * ($n->statoPolizia - $n->basePolizia());
            $n->controlloInfo = max(0.0, min(100.0, $n->controlloInfo
                + (max(0.0, $obiettivoInfo) - $n->controlloInfo) * $rispostaPolizia * 0.7));

            // --- reclutamento ---------------------------------------------
            // Quattro fattori: quanto terreno il paese offre a una guerra
            // (Fearon 2010, sotto), il motivo (l'esclusione dal potere), il
            // malcontento, e quanto l'insurrezione sta già andando bene —
            // nessuno vuole salire su un carro che perde.
            // ATTENZIONE alle unità. La forza del governo è una media
            // geometrica di uomini ed equipaggiamento; contare gli insorti a
            // testa le rende incommensurabili, e il governo li annienta sempre.
            // Anche il reclutamento va quindi in unità di POTENZA.
            // Nella formula di Crawford il reclutamento insurrezionale dipende
            // da popolazione, DEBOLEZZA ISTITUZIONALE e successo accumulato —
            // la popolarita' del governo non vi compare affatto: quella decide
            // i colpi di stato, non le guerriglie. Legare tutto al malcontento
            // significava che uno Stato fragile con un governo momentaneamente
            // amato non aveva alcuna insurrezione, il che non somiglia a
            // nessun posto reale.
            $malcontento = max(0.0, (55.0 - $n->legittimita) / 55.0);
            // Le democrazie piene non hanno insurrezioni che durino: li' il
            // dissenso armato si sfalda da se'. Quanto un'insurrezione cresce
            // altrove lo dice il terreno di guerra qui sotto, non questo.
            $debolezza   = 1.0 - $n->democrazia;
            $spinta      = 0.30 + 0.70 * $malcontento;
            if ($debolezza > 0.15) {
                $successo = $n->forzaInsorti > 0.0
                    ? min(1.0, $n->forzaInsorti / max(1.0, $n->potenzaGoverno()))
                    : 0.0;
                // QUANTO TERRENO HA UN'INSURREZIONE, una volta accesa.
                //
                // Era una meccanica: reclute in proporzione alla popolazione
                // e alla poverta', contro un governo la cui forza cresce con
                // la radice di popolazione per PIL. Il rapporto fra le due
                // aveva cosi' un'elasticita' di -1,5 al reddito e NESSUNA
                // alla popolazione: il Madagascar risultava ventiquattro volte
                // piu' esposto dell'India, e ogni paese povero oltre la soglia
                // a cui il governo smette di reggere — Togo, Burundi, Ruanda,
                // Uganda, Kenya, Tanzania — finiva in guerra civile piena
                // appena qualcosa si accendeva (docs/30).
                //
                // Adesso il terreno e' il rischio di GUERRA MAGGIORE stimato,
                // non dedotto: FEARON (2010), «Governance and Civil War
                // Onset», WDR 2011, tabella 2 modello 1 (guerre oltre i mille
                // morti l'anno, 1946-2008) e tabella 20 per la qualita' del
                // governo — vedi terrenoDiGuerra(). Il reddito pesa -0,20 e
                // non -1,5; la popolazione +0,20 e non zero; contano il
                // terreno, il petrolio e soprattutto lo Stato.
                //
                // Si misura in unita' della forza che il governo aveva al
                // seme: un governo che si arma resta piu' forte dei ribelli,
                // uno che si logora piu' debole.
                $terreno = $this->terrenoDiGuerra($n, $c);
                // CEDERMAN, WIMMER, MIN (2010), «Why Do Ethnic Groups
                // Rebel?», World Politics 62(1); CEDERMAN, WEIDMANN, GLEDITSCH
                // (2011), APSR 105(3).
                //
                // Fin qui il modello aveva solo le OPPORTUNITA': poverta',
                // popolazione, debolezza dello Stato. E' il consenso costruito
                // da Fearon & Laitin e da Collier & Hoeffler, per cui i MOTIVI
                // — le ingiustizie — non predicono le guerre civili.
                //
                // Cederman e colleghi mostrano che quel consenso reggeva
                // perche' si era misurata la disuguaglianza sbagliata: fra
                // individui (il Gini) invece che fra GRUPPI politicamente
                // rilevanti. Misurata come si deve — quanta popolazione sta
                // fuori dal potere esecutivo — la disuguaglianza orizzontale
                // predice, e bene.
                //
                // La Siria basta a capire: 86% della popolazione senza accesso
                // al potere, in quattro gruppi, con una minoranza del 13%
                // dominante. Il Myanmar ha meno esclusi (29%) ma in UNDICI
                // gruppi, e la frammentazione conta oltre alla taglia.
                $motivo = 1.0 + $pesoEsclusione * $n->esclusioneEtnica
                    * (1.0 + $pesoFrammentazione * min(1.0, $n->gruppiEsclusi / 6.0));

                $reclute = $kReclutamento * $terreno * $motivo * $spinta
                    * $n->potenzaIniziale * $attrito
                    * (1.0 + $carrozzone * $successo);
                // E un tetto: un paese non esprime ribelli all'infinito. Il
                // reclutamento rallenta man mano che i ribelli si avvicinano a
                // una volta e mezza la forza che lo Stato aveva al seme. Senza,
                // l'Iran in guerra con l'Afghanistan arrivava a quattordici
                // volte il proprio esercito (docs/33). Nei dati sugli attori
                // non statali — CUNNINGHAM, GLEDITSCH, SALEHYAN (2013), «Non-State
                // Actors in Civil Wars: A New Dataset», Conflict Management and
                // Peace Science 30(5) — i ribelli alla pari dello Stato o piu'
                // forti sono una piccola minoranza.
                $reclute *= max(0.0, 1.0 - $n->forzaInsorti
                    / max(1.0, $cal->numero('insurrezione.tetto_insorti', 1.5) * $n->potenzaIniziale));

                // L'INNESCO. Fearon e Laitin non stimano quanti ribelli ci sono:
                // stimano la PROBABILITA' ANNUA che una guerra civile cominci.
                // Qui prima un'insurrezione nasceva da sola appena il
                // reclutamento superava l'attrito del governo, e nasceva
                // dovunque nello stesso momento.
                //
                // Poi la probabilita' cresceva col rapporto fra il reclutamento
                // possibile e la forza del governo, fino a un tetto. Ma nei
                // paesi piccoli e poveri l'esercito pesa pochissimo e il
                // rapporto esplodeva: sedici paesi stavano al tetto del 10%
                // l'anno, compreso il Ruanda, e l'India al 2,7% (docs/30).
                //
                // Adesso il rischio RELATIVO e' quello stimato, con i suoi
                // coefficienti: vedi rischioDiGuerraCivile(). La base e' la
                // probabilita' del paese mediano, ed e' tarata.
                if (!$n->haInsorti()) {
                    $pInnesco = min($innescoMassimo,
                        $innescoBase * $this->rischioDiGuerraCivile($n, $c, $tickAnno));
                    if ($c->caso->prova('05_innesco', crc32($n->iso3), $c->tick, $pInnesco * $perTick)) {
                        $n->forzaInsorti = max(1.0, $n->forzaInsorti) + $reclute;
                        $c->annota('innesco', ['nazione' => $n->nome]);
                    }
                } else {
                    $n->forzaInsorti += $reclute;
                }
            } else {
                // Senza malcontento l'insurrezione si sfalda da sola.
                $n->forzaInsorti *= (1.0 - 0.5 * $perTick);
            }

            // --- attrito ---------------------------------------------------
            // Ciascuno toglie all'altro un quarto della PROPRIA forza all'anno:
            // se sono entrambi forti muoiono in molti.
            if ($n->forzaInsorti > 0.5) {
                $potenzaGoverno = $n->potenzaGoverno();
                // La controinsurrezione cresce con la minaccia. Con un attrito
                // fisso un'insurrezione poteva solo spegnersi o crescere senza
                // freni fino a pareggiare l'esercito: non esisteva la guerriglia
                // cronica a bassa intensita', che e' la forma piu' comune di
                // conflitto armato nel mondo vero (UCDP 2024: 61 conflitti
                // statali, 11 soli arrivati al livello di guerra). Un governo
                // che vede crescere i ribelli sposta su di loro truppe,
                // bilancio e polizia: e' la curva di risposta che crea un
                // equilibrio stabile sotto la guerra civile, e lascia arrivarci
                // solo chi ha un reclutamento molte volte superiore.
                $risposta = 1.0 + $rispostaGoverno
                    * min(1.0, $n->forzaInsorti / max(1.0, $potenzaGoverno));
                $dannoAgliInsorti = $potenzaGoverno * $attrito * $risposta;
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
            $interno = match (true) {
                !$n->haInsorti()                   => 2,
                $rapporto > $soglie['pace']        => 2,
                $rapporto > $soglie['terrorismo']  => 3,
                $rapporto > $soglie['guerriglia']  => 4,
                $rapporto > $soglie['guerraCivile']=> 5,
                default                            => 6,
            };
            // Il livello di conflitto non e' solo interno. Prima questa fase
            // lo ricalcolava dalle sole insurrezioni e cancellava il resto: un
            // paese in guerra con un altro Stato risultava «in pace» per tutta
            // la fase 06 — i suoi controlli su guerra e disarmo lo vedevano
            // cosi' — finche' la 07 non lo rimetteva a 6; e gli shock di un
            // incidente o di un attacco mirato sparivano nello stesso tick in
            // cui erano nati.
            $esterno = isset($belligeranti[$n->iso3]) ? 6 : 0;
            $n->netPeace = max($interno, $esterno, $n->scossaEsterna);
            $n->scossaEsterna = 0;
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
            // La vittoria degli insorti era AUTOMATICA: appena il rapporto di
            // forze si ribaltava, il governo cadeva quel tick stesso. Nel mondo
            // vero non funziona cosi'. Il Myanmar, il Congo, la Somalia hanno
            // guerriglie piu' forti dell'esercito in mezzo paese da decenni e
            // la capitale non cade: prevalere sul campo non e' prendere il
            // potere, e fra i conflitti che finiscono la vittoria dei ribelli
            // e' l'esito piu' raro. Adesso e' una probabilita' annua, e la
            // guerra civile puo' durare.
            if ($rapporto < $soglie['guerraCivile'] && $n->forzaInsorti > 1.0 && !$tregua
                && $c->caso->prova('05_rivoluzione', $seme, $c->tick, $vittoriaInsorti * $perTick)) {
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
            $centro = $sogliaColpo + $resistenzaEstremi * $n->radicalita();

            // Il tipo di regime sposta il CENTRO della logistica, non la
            // moltiplica — ed e' una differenza di forma, non di taratura.
            // Moltiplicandola il termine istituzionale restava schiacciato:
            // misurato, sestuplicare il peso da 4 a 25 muoveva il rapporto fra
            // regimi parziali e autocrazie piene da 1,1 a 1,6 soltanto, perche'
            // la logistica sulla legittimita' spazia su ordini di grandezza e
            // un fattore lineare non la tocca.
            //
            // Spostando il centro si dice invece la cosa giusta, che e' anche
            // quella di Goldstone: un regime parziale cade con una
            // legittimita' con cui un'autocrazia piena reggerebbe. Con la
            // pendenza a 18, ventiquattro punti di spostamento valgono circa
            // quattro volte le probabilita' (docs/36). La faziosita'
            // raddoppiava lo spostamento (fino a trenta volte, il numero di
            // PITF): non piu', vedi sotto.
            $gab = $mondo->gabinetti[$n->iso3] ?? null;
            $centro += $spostamentoRegime * $n->regimeParziale();

            // E LA CHIUSURA PROTEGGE. Era il pezzo mancante, ed e' il ramo
            // sinistro della U di Goldstone: le autocrazie piene non cadono
            // spesso: reprimono e tengono.
            //
            // Nel modello la repressione costava legittimita' — la fase 04 la
            // scala — e non comprava NIENTE: `statoPolizia` non compariva in
            // nessun punto del rischio di colpo di Stato. Un'autocrazia pagava
            // il prezzo del pugno di ferro senza averne il beneficio, e
            // risultava piu' fragile di una democrazia (legittimita' media
            // 43,1 contro 53,6). E' empiricamente falso, ed e' il motivo per
            // cui i regimi parziali finivano SOTTO le autocrazie invece che
            // sopra.
            $centro -= $protezioneChiusura * (1.0 - $n->aperturaIstituzionale());

            // E IL QUARTO PREDITTORE DI PITF, che finora mancava: la qualita'
            // della vita. Goldstone et al. usano la mortalita' infantile —
            // «i paesi al 75esimo percentile hanno SETTE VOLTE le probabilita'
            // di quelli al 25esimo» — come misura insieme di benessere e di
            // capacita' dello Stato di provvedere ai propri cittadini.
            //
            // Da noi e' `qualitaVita`, che era una variabile SCRITTA, salvata,
            // mostrata in pagina — e non letta da nessun meccanismo. Adesso
            // pesa, ed e' anche la via per cui entra la disuguaglianza: quel
            // livello si calcola sul consumo MEDIANO, non su quello medio.
            //
            // La taratura viene dal numero di Goldstone, non dal gusto: i
            // quartili di qualitaVita stanno a 3 e a 8, cinque livelli di
            // scarto, e con la pendenza a 7 servono 7*ln(7) = 13,6 punti per
            // fare sette volte le probabilita'. Cioe' 2,7 punti per livello.
            // Con la pendenza a 18 (docs/36) valgono circa due volte: sui
            // colpi di Stato il vincolo e' la pendenza misurata per stabilita'
            // politica, e la taratura la rispetta con questo peso dentro.
            $centro += $pesoQualitaVita * (6.0 - $n->qualitaVita);

            $rischioAnnuo = $rischioMax / (1.0 + exp(($n->legittimita - $centro) / $pendenza));

            // LA BLINDATURA. Le autocrazie piene non subiscono colpi di Stato:
            // nel 2000-2025 zero colpi riusciti in 265 anni-paese con la
            // democrazia liberale di V-Dem sotto 0,05 l'anno prima, contro 12,9
            // ogni mille nei regimi parziali (Powell e Thyne, versione del
            // 29/08/2026; docs/35). E' il «coup-proofing» — QUINLIVAN (1999),
            // «Coup-Proofing: Its Practice and Consequences in the Middle
            // East», International Security 24(2); POWELL (2012), «Determinants
            // of the Attempting and Outcome of Coups d'etat», Journal of
            // Conflict Resolution 56(6): unita' d'elite fedeli, forze parallele
            // che si bilanciano, sorveglianza sui militari. Lo spostamento del
            // centro qui sopra non bastava piu' da quando la legittimita' ha
            // un'ancora per paese: i regimi chiusi partono bassi, e il modello
            // faceva cadere la Corea del Nord, l'Eritrea e il Turkmenistan tre
            // o quattro volte piu' spesso dei regimi parziali.
            //
            // Sulla democrazia liberale di V-Dem, come il dato: non
            // sull'apertura istituzionale, che sottrae la stretta di polizia e
            // censura e metteva fra le autocrazie piene meta' dei regimi
            // parziali.
            $chiusuraPiena = max(0.0, min(1.0, (0.10 - $n->democrazia) / 0.05));
            $rischioAnnuo *= exp(-$blindatura * $chiusuraPiena);

            // LA FAZIOSITA', per tutti e col peso che ha davvero. Veniva dal
            // gabinetto, che c'e' solo per le quattordici potenze giocabili, e
            // raddoppiava lo spostamento dei regimi parziali: le «trenta volte»
            // di Goldstone. Ma quel numero e' sull'instabilita' in generale
            // (guerre civili, genocidi, crolli) del 1955-2003, e confronta un
            // parziale fazioso con un'autocrazia piena: e' quasi tutto tipo di
            // regime, che il modello ha gia'. Fra regimi parziali, coi colpi di
            // Powell e Thyne e la PARCOMP di Polity5, i faziosi cadono 21,9
            // volte ogni mille anni-paese contro 18,8 nel 2000-2018 (24,9 contro
            // 20,7 dal 1990): un quinto in piu' (docs/35). La faziosita' e'
            // quella di Polity, del regime; dove c'e' un gabinetto conta per
            // meta' anche la sua, che e' del momento.
            $faziosita = $gab !== null ? ($n->faziosita + $gab->faziosita()) / 2.0 : $n->faziosita;
            $rischioAnnuo *= 1.0 + $pesoFaziosita * $faziosita * $n->regimeParziale();

            // LA TRAPPOLA DEL COLPO. Chi ha appena avuto un colpo di Stato ne
            // ha altri: LONDREGAN e POOLE (1990), «Poverty, the Coup Trap, and
            // the Seizure of Executive Power», World Politics 42(2). Fra i
            // regimi parziali del 2000-2025 un colpo riuscito nei dieci anni
            // prima moltiplica per 3,1 quello dell'anno dopo, a parita' di
            // stabilita' politica (Powell e Thyne; 38,7 ogni mille anni-paese
            // contro 10,4). Senza, il modello dava all'Iraq e al Pakistan piu'
            // rischio che al Mali e al Niger (docs/37).
            if ($c->tick - $n->ultimoColpo < $trappolaTick) {
                $rischioAnnuo *= $trappola;
            }
            // Il clamore accelera, senza essere lui a decidere.
            $rischioAnnuo *= 1.0 + $n->clamoreSociale / 120.0;

            // --- il modello PITF ------------------------------------------
            // GOLDSTONE, BATES, EPSTEIN, GURR, LUSTIK, MARSHALL, ULFELDER,
            // WOODWARD (2010), «A Global Model for Forecasting Political
            // Instability», American Journal of Political Science 54(1).
            //
            // Quattro predittori, 81,7% di accuratezza a due anni su tutte le
            // instabilita' del mondo dal 1955 al 2003. La loro conclusione e'
            // netta e va contro l'intuito: sono le ISTITUZIONI, «properly
            // specified», a predire — non l'economia, non la demografia, non
            // la geografia.
            //
            // Qui entrano i due che il nostro motore non aveva.
            //
            // IL PRIMO, la U rovesciata. Il rischio non cresce ne' cala con
            // l'apertura: ha un massimo in mezzo. Un'autocrazia piena
            // reprime il dissenso, una democrazia piena lo incanala; e' il
            // regime PARZIALE che salta — aperto abbastanza da far competere,
            // non abbastanza da far perdere senza perdere tutto. E se quella
            // competizione e' FAZIOSA, organizzata in blocchi dove chi vince
            // prende tutto, le probabilita' superano di oltre TRENTA VOLTE
            // quelle di un'autocrazia piena. E' il predittore piu' forte che
            // abbiano trovato.
            //
            // La nostra logistica sulla legittimita' e' monotona e non puo'
            // vedere niente di tutto questo: per lei un paese chiuso e uno
            // aperto con la stessa legittimita' rischiano uguale.
            // IL SECONDO, il vicinato. Quattro o piu' confinanti in conflitto
            // armato e l'instabilita' passa il confine: armi, profughi,
            // santuari, e l'esempio che si puo' fare. Da noi i conflitti non
            // contagiavano nessuno.
            $rischioAnnuo *= 1.0 + $pesoVicinato
                * min(1.0, ($viciniInConflitto[$n->iso3] ?? 0) / 4.0);

            // E il palazzo pesa quanto la piazza: un capo puo' essere amato nel
            // paese e finito dentro le mura, se le fazioni che lo hanno messo
            // li' hanno smesso di volerlo. E' il Panel di CyberJudas — chi ti
            // ha dato il potere e' anche chi te lo toglie.
            if ($gab !== null) {
                $rischioAnnuo *= 1.0 + $gab->pressioneInterna() / 55.0;
            }

            // La tregua e' una per governo: si tira col caso del MONDO. Col
            // caso del tick si ritirava ogni settimana, e il primo tiro basso
            // la chiudeva — durava quasi sempre il minimo (docs/30).
            $tregua = (int) ($tickAnno * (0.8 + 1.8 * $c->delMondo()->frazione('05_tregua', $seme, $n->cambiEsecutivo)));
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

    /**
     * Il rischio di guerra civile di un paese, in multipli di quello del
     * paese mediano del seme.
     *
     *   FEARON (2010), «Governance and Civil War Onset», documento di base
     *   del World Development Report 2011, Banca Mondiale — tabella 2,
     *   modello 3: logit sull'inizio di tutti i conflitti UCDP/PRIO (anche
     *   minori, oltre i 25 morti) nel 1946-2008. E' la ristima, con dati
     *   piu' completi e il reddito in logaritmo, del modello di FEARON e
     *   LAITIN (2003), «Ethnicity, Insurgency, and Civil War», APSR 97(1).
     *
     * I coefficienti stanno in insurrezione.rischio, ciascuno con la sua
     * riga. Due adattamenti, dichiarati:
     *
     *   - la qualita' del governo (WGI) e' nella tabella 21 dello stesso
     *     lavoro: la stabilita' politica vale -0,93, e quando entra il peso
     *     del reddito scende di circa un quarto (da -0,55 a -0,40). Qui il
     *     reddito vale quindi -0,26 invece di -0,351;
     *   - l'«instabilita'» di Fearon e' un qualunque cambio del punteggio
     *     Polity nell'anno prima. Qui e' un cambio al vertice nell'ultimo anno
     *     in un paese che non vota: e' il caso in cui cambia il regime, non
     *     solo il governo.
     */
    private function rischioDiGuerraCivile(Nazione $n, ContestoTick $c, float $tickAnno): float
    {
        $cal = $c->calibrazione;
        $med = $c->mondo->mediano;
        $instabile = $c->tick - $n->annoUltimoCambio < $tickAnno
            && $n->democrazia < $cal->numero('elezioni.democrazia_minima', 0.25);
        $eta = $cal->numero('insurrezione.rischio.reddito', -0.26)
                * log(max(300.0, $n->pilProCapite) / max(300.0, $med['reddito']))
            + $cal->numero('insurrezione.rischio.popolazione', 0.238)
                * log(max(1.0e4, $n->popolazione) / max(1.0e4, $med['popolazione']))
            + $cal->numero('insurrezione.rischio.montuoso', 0.151)
                * log((1.0 + $n->montuoso) / (1.0 + $med['montuoso']))
            + $cal->numero('insurrezione.rischio.petrolio', 0.715) * ($n->petrolio ? 1.0 : 0.0)
            + $cal->numero('insurrezione.rischio.regime_parziale', 0.355) * $n->regimeParziale()
            + $cal->numero('insurrezione.rischio.instabilita', 0.466) * ($instabile ? 1.0 : 0.0)
            + $cal->numero('insurrezione.rischio.governo', -0.93) * $n->stabilitaPolitica;
        return exp($eta);
    }

    /**
     * Quanto terreno ha un'insurrezione accesa, in multipli di quello del
     * paese mediano: il rischio relativo di GUERRA MAGGIORE (oltre i mille
     * morti l'anno) di FEARON (2010), tabella 2 modello 1, con la qualita'
     * del governo della tabella 20 — la stabilita' politica vale -0,97 e
     * dimezza il peso del reddito (da -0,42 a -0,20). Coefficienti in
     * insurrezione.terreno.
     *
     * L'esclusione etnica non sta qui: entra a parte come motivo (Cederman,
     * Wimmer e Min), ed e' anche il risultato di Fearon (2010, p. 18).
     */
    private function terrenoDiGuerra(Nazione $n, ContestoTick $c): float
    {
        $cal = $c->calibrazione;
        $med = $c->mondo->mediano;
        $eta = $cal->numero('insurrezione.terreno.reddito', -0.20)
                * log(max(300.0, $n->pilProCapite) / max(300.0, $med['reddito']))
            + $cal->numero('insurrezione.terreno.popolazione', 0.0)
                * log(max(1.0e4, $n->popolazione) / max(1.0e4, $med['popolazione']))
            + $cal->numero('insurrezione.terreno.montuoso', 0.360)
                * log((1.0 + $n->montuoso) / (1.0 + $med['montuoso']))
            + $cal->numero('insurrezione.terreno.petrolio', 1.095) * ($n->petrolio ? 1.0 : 0.0)
            + $cal->numero('insurrezione.terreno.regime_parziale', 0.258) * $n->regimeParziale()
            + $cal->numero('insurrezione.terreno.governo', -0.97) * $n->stabilitaPolitica;
        return exp($eta);
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
        // Ma non piu' forte dello Stato che hanno abbattuto, com'era al seme:
        // altrimenti l'Iran rivoluzionato valeva piu' della Cina (docs/33).
        $potenzaVincitori = min($n->forzaInsorti, max(1.0, $n->potenzaIniziale));
        $n->soldati = max(1000.0, $n->soldati * 0.5 + $potenzaVincitori * 0.5);
        $n->equipaggiamento = max(
            1.0,
            $n->soldati > 0 ? ($n->equipaggiamento * 0.4 + $potenzaVincitori ** 2 / max(1.0, $n->soldati)) : 1.0,
        );
        $n->forzaInsorti = 0.0;

        // E il credito che la gente concede sempre a chi arriva: alto abbastanza
        // da azzerare il malcontento per qualche stagione.
        $n->derivaPolitica = $c->caso->rumore('05_dopo_rivoluzione', crc32($n->iso3), $c->tick, 16.0);
        // Chi vince con le armi governa con le armi: dopo le prese del potere
        // armate la repressione sale (V-Dem, Afghanistan 2021, Yemen 2015,
        // Libia 2011: +0,14 in media, ma con la Libia che la abbassa; docs/33).
        $cal = $c->calibrazione;
        $n->repressione = min(1.0, $n->repressione + $cal->numero('regime.repressione_dopo_rivoluzione', 0.14));
        $n->censura     = min(1.0, $n->censura + $cal->numero('regime.censura_dopo_rivoluzione', 0.09));
        $n->legittimita  = ($n->ancoraLegittimita() + $n->derivaPolitica) + 7.0 - 12.8 * $n->radicalita();
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
        // «Irregolare» vuol dire che il paese non ha un modo ordinario di
        // cambiare chi comanda. E' una proprieta' delle ISTITUZIONI, e qui
        // c'era `maturita`, che correla 0,989 col logaritmo del reddito: un
        // paese ricco aveva ricambi «regolari» anche se non votava nessuno.
        //
        // Era la soglia con cui la fase 10 decide chi va alle urne (0,25), e
        // non e' la stessa domanda. Un paese puo' votare e subire un colpo di
        // Stato: dei 36 colpi riusciti del 2000-2025 (Powell e Thyne) 19 sono
        // caduti fra 0,25 e 0,55 di democrazia liberale V-Dem — il Mali, il
        // Niger, la Birmania, la Thailandia due volte, il Burkina Faso, l'Honduras
        // —, 16,2 e 8,2 ogni mille anni-paese, quanto sotto. Sopra 0,55 nessuno,
        // in 1.488 anni-paese. Il modello quei rischi li aveva, e li chiamava
        // crisi di governo (docs/36).
        $irregolare = $n->democrazia < $c->calibrazione->numero('colpo_di_stato.democrazia_regolare', 0.55);
        // Un nuovo governo non è il precedente con la legittimità ricaricata:
        // è gente diversa, con fortuna diversa. Alcuni consolidano per un
        // decennio, altri cadono in sei mesi, e questo NON è deducibile.
        $n->derivaPolitica = $c->caso->rumore('05_nuovogoverno', crc32($n->iso3), $c->tick, 14.0);
        $n->legittimita = ($n->ancoraLegittimita() + $n->derivaPolitica)
            + ($irregolare ? 3.0 : 5.0)
            + $c->caso->rumore('05_luna_di_miele', crc32($n->iso3), $c->tick, 5.0);
        $n->clamoreSociale *= 0.4;
        // Un cambio irregolare erode la fiducia nelle istituzioni: se è potuto
        // accadere una volta, può riaccadere.
        if ($irregolare) {
            // Un colpo di Stato non impoverisce il paese da un giorno
            // all'altro: gli rompe le istituzioni. L'erosione va li'.
            $n->democrazia = max(0.0, $n->democrazia - 0.02);
            $n->orientamento = max(-128, min(128, -$n->orientamento + ($n->orientamento === 0 ? 24 : 0)));
            // E stringe: tre anni dopo i quindici colpi di Stato riusciti del
            // 2010-2023 (Powell e Thyne), la repressione di V-Dem era salita
            // in media di 0,12 e la censura di 0,13 — l'Egitto, la Thailandia,
            // la Birmania e il Burkina Faso molto, altri quasi niente (docs/33).
            $cal = $c->calibrazione;
            $n->repressione = min(1.0, $n->repressione + $cal->numero('regime.repressione_dopo_colpo', 0.12));
            $n->censura     = min(1.0, $n->censura + $cal->numero('regime.censura_dopo_colpo', 0.13));
        }
        $n->cambiEsecutivo++;
        if ($irregolare) {
            $n->cambiIrregolari++;
            $n->ultimoColpo = $c->tick;
        }
        $n->annoUltimoCambio = $c->tick;
        $c->annota($irregolare ? 'colpo_di_stato' : 'cambio_governo', [
            'nazione' => $n->nome,
            'tick'    => $c->tick,
        ]);
    }
}
