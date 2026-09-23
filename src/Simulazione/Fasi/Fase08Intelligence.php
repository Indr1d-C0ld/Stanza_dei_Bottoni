<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Dati\Evento;
use App\Dati\Intelligence;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 08 — Raccolta, scoperta, intercettazione.
 *
 * LEGGE:      gli eventi in volo, la copertura di intelligence
 * SCRIVE:     la conoscenza per osservatore, i rapporti
 * INVARIANTE: la conoscenza non regredisce MAI — i quattro livelli sono monotoni
 *
 * I quattro livelli sono quelli di CyberJudas, generalizzati da un solo
 * traditore a tutti gli eventi del mondo: prima gli indizi sul *dove*, poi
 * quelli sul *chi*.
 *
 *   1  esistenza          "sta per succedere qualcosa"
 *   2  dominio e regione  "un'operazione di intelligence, in Africa occidentale"
 *   3  bersaglio          "il paese è il Mali"
 *   4  ATTRIBUZIONE       "il mandante sono loro, e posso dimostrarlo"
 *
 * Il quarto livello è di un ordine di grandezza più difficile degli altri tre,
 * e questa asimmetria è deliberata: sapere che stai subendo qualcosa è
 * relativamente facile, poterlo *dimostrare* quasi mai. È da lì che discende
 * tutto il resto del progetto.
 */
final class Fase08Intelligence implements Fase
{
    public function codice(): string { return '08'; }
    public function nome(): string   { return 'Raccolta, scoperta, intercettazione'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null || !isset($mondo->intelligence)) {
            return EsitoFase::nonImplementata();
        }

        $intel = $mondo->intelligence;
        $cal   = $c->calibrazione;
        /** @var array<int,float> $difficolta */
        $difficolta = $cal->leggi('intelligence.difficolta_livello', [1 => 6.0, 2 => 5.0, 3 => 3.5, 4 => 3.0]);
        $decadimento = $cal->numero('intelligence.decadimento_copertura', 0.01);

        // Le grandi potenze guardano ovunque: sono osservatori per default.
        $potenze = $mondo->elenco();
        usort($potenze, static fn($a, $b) => $b->influenzaTotale <=> $a->influenzaTotale);
        $osservatoriGlobali = array_map(static fn($n) => $n->iso3, array_slice($potenze, 0, 8));

        $scoperte = 0;
        $attribuzioni = 0;
        $smascherati = 0;
        $falsiRiusciti = 0;
        $rapportiNuovi = 0;

        foreach ($mondo->eventi as $e) {
            if (!$e->inVolo()) {
                continue;
            }

            // Un'azione dichiarata e' pubblica per costruzione: non si
            // "scopre", si legge sul giornale. Nessun tiro.
            if ($e->impronta >= 0.90) {
                $intel->imponiLivello($e->bersaglio, $e->id, 4);
                continue;
            }

            foreach ($this->osservatori($e, $mondo, $osservatoriGlobali) as $iso) {
                if ($iso === $e->mandante) {
                    continue;   // il mandante sa già
                }
                $liv = $intel->livello($iso, $e->id);
                if ($liv >= 4) {
                    continue;
                }
                // Si possono guadagnare fino a due livelli in un tick: una sola
                // buona intercettazione puo' rivelare parecchio in un colpo, e
                // senza questo le operazioni brevi non si scoprono mai in tempo.
                $avanzamenti = 0;
                while ($liv < 4 && $avanzamenti < 2) {
                $prossimo = $liv + 1;

                // Per i primi tre livelli si guarda dove l'operazione ATTERRA;
                // per l'attribuzione si deve guardare da dove PARTE, ed è
                // tutt'altro mestiere.
                $doveGuardare = $prossimo === 4 ? $e->mandante : $e->bersaglio;

                $inCasa = $iso === $doveGuardare;
                $solidita = ($mondo->nazioni[$iso]->maturita ?? 100) / 255.0;

                $migliore = 0.0;
                foreach (Intelligence::PERTINENTI[$e->dominio] ?? ['osint'] as $disciplina) {
                    $migliore = max($migliore, $inCasa
                        ? $intel->coperturaInterna($iso, $disciplina, $solidita)
                        : $intel->copertura($iso, $doveGuardare, $disciplina));
                }
                if ($migliore <= 0.0) {
                    break;   // dentro il ciclo dei livelli: uscire, non riprovare
                }

                $probabilita = min(0.9, $e->impronta * $migliore
                    * (float) ($difficolta[$prossimo] ?? 0.05)
                    / (1.0 + 2.0 * $e->copertura));

                if (!$c->caso->prova('08_scoperta', crc32($e->id . '|' . $iso),
                        $c->tick * 4 + $avanzamenti, $probabilita)) {
                    break;
                }

                // Per il BERSAGLIO i livelli due e tre sono impliciti: i gradini
                // "che dominio" e "quale paese" servono a chi guarda da fuori,
                // non a chi si ritrova l'operazione in casa. Accorgersene
                // significa gia' sapere che tocca a te.
                if ($iso === $e->bersaglio && $prossimo < 3) {
                    $prossimo = 3;
                }
                $intel->imponiLivello($iso, $e->id, $prossimo);
                $liv = $prossimo;
                $avanzamenti++;
                $scoperte++;

                if ($prossimo >= 2) {
                    // Un rapporto ha sempre una provenienza e un'accuratezza:
                    // non è la verità, è ciò che un servizio crede di sapere.
                    $intel->rapporti[] = [
                        'tick'        => $c->tick,
                        'proprietario' => $iso,
                        'evento'      => $e->id,
                        'livello'     => $prossimo,
                        'disciplina'  => $this->disciplinaMigliore($e, $iso, $intel, $doveGuardare),
                        'accuratezza' => (int) round(100 * min(1.0, 0.45 + $migliore * 0.5)),
                    ];
                    $rapportiNuovi++;
                }

                if ($prossimo === 4) {
                    $attribuzioni++;
                    $intel->attribuito[$e->id][] = $iso;

                    // Arrivare al quarto livello significa poter fare un nome.
                    // Se c'e' un falso ben confezionato, il nome puo' essere
                    // quello sbagliato — e allora il vero mandante l'ha fatta
                    // franca due volte: non paga lui, e paga qualcun altro.
                    $accusato = $e->mandante;
                    if ($e->falsaBandiera !== null) {
                        $analisi = 0.25 + ($intel->capacita[$iso]['finint'] ?? 0.0) * 0.5
                            + ($intel->capacita[$iso]['humint'] ?? 0.0) * 0.35;
                        $smaschera = $c->caso->prova('08_smaschera', crc32($e->id . '|' . $iso),
                            $c->tick, max(0.05, $analisi - $e->qualitaFalso));
                        if (!$smaschera) {
                            $accusato = $e->falsaBandiera;
                        } else {
                            $smascherati++;
                            $c->annota('falso_smascherato', [
                                'chi'      => $mondo->nazioni[$iso]->nome,
                                'mandante' => $mondo->nazioni[$e->mandante]->nome,
                                'incolpato' => $mondo->nazioni[$e->falsaBandiera]->nome,
                            ]);
                        }
                    }
                    // Lo scandalo scoppia una volta. Se un altro servizio ha
                    // gia' fatto lo STESSO nome per la stessa operazione, la
                    // notizia c'e' gia': prima ogni conferma era uno scandalo
                    // nuovo, con la sua legittimita' persa, e un'operazione
                    // vista da sei servizi costava sei volte.
                    $giaDetto = in_array($accusato, $intel->accusa[$e->id] ?? [], true);
                    $intel->accusa[$e->id][$iso] = $accusato;
                    if (!$giaDetto && ($e->dominio === 'int' || $e->dominio === 'info')) {
                        $c->annota('attribuzione', [
                            'chi'      => $mondo->nazioni[$iso]->nome,
                            'mandante' => $mondo->nazioni[$accusato]->nome,
                            'verbo'    => $e->verbo,
                            'contro'   => $mondo->nazioni[$e->bersaglio]->nome,
                        ]);
                    }
                }
                }
            }
        }

        $this->manutieneLeDifese($c);
        $intercettati = $this->intercettaMessaggi($c, $intel, $osservatoriGlobali);
        $talpe = $this->uominiDentro($c, $intel);

        // La copertura segue l'interesse: una rete di informatori che non si
        // coltiva smette di esistere, e nessuno se ne accorge finché non serve;
        // dove l'interesse cresce — un rapporto che si incrina, un vicino che
        // pesa di piu' — la rete si allarga con la stessa lentezza.
        foreach ($intel->presenza as $iso => $bersagli) {
            $a = $mondo->nazioni[$iso] ?? null;
            foreach ($bersagli as $b => $v) {
                $nb = $mondo->nazioni[$b] ?? null;
                $r  = $mondo->relazioni->fra($iso, $b);
                $obiettivo = ($a !== null && $nb !== null && $r !== null)
                    ? Intelligence::presenzaColtivata($a, $nb, $r)
                    : 0.0;
                $intel->presenza[$iso][$b] = max(0.02, $v + ($obiettivo - $v) * $decadimento);
            }
        }

        if (count($intel->rapporti) > 3000) {
            $intel->rapporti = array_slice($intel->rapporti, -1500);
        }

        return new EsitoFase([
            'talpe'        => $talpe,
            'intercettati' => $intercettati,
            'scoperte'     => $scoperte,
            'attribuzioni' => $attribuzioni,
            'rapporti'     => $rapportiNuovi,
            'falsi_smascherati' => $smascherati,
        ]);
    }

    /**
     * Le talpe: quel che vedono, e quanto reggono.
     *
     * Una poltrona comprata non e' una fonte come le altre. Non deve scoprire
     * niente: sta dentro. Tutto cio' che il suo governo ha in volo arriva al
     * servizio che l'ha reclutata gia' al quarto livello — con nome e cognome
     * del mandante, che e' la cosa che nessun'altra disciplina regala quasi mai.
     *
     * In cambio, ogni settimana che passa e' una settimana in cui il
     * controspionaggio di casa puo' accorgersene.
     */
    private function uominiDentro(ContestoTick $c, Intelligence $intel): int
    {
        if ($c->db === null || $c->aVuoto) {
            return 0;
        }
        $talpe = $c->db->esegui(
            'SELECT p.id, p.ruolo, p.nome, p.sospettata, p.reclutata_tick, p.giocatore_id,
                    ospite.codice AS ospite, recluta.codice AS padrone
             FROM sdb_poltrona p
             JOIN sdb_nazione ospite  ON ospite.id = p.nazione_id
             JOIN sdb_nazione recluta ON recluta.id = p.reclutata_da
             WHERE p.reclutata_da IS NOT NULL')->fetchAll();

        foreach ($talpe as $t) {
            $ospite  = (string) $t['ospite'];
            $padrone = (string) $t['padrone'];

            // Cio' che passa dalla scrivania della talpa passa al suo padrone.
            foreach ($c->mondo->eventi as $e) {
                if (!$e->inVolo() || $e->mandante !== $ospite) {
                    continue;
                }
                if ($intel->livello($padrone, $e->id) < 4) {
                    $intel->imponiLivello($padrone, $e->id, 4);
                    $intel->attribuito[$e->id][] = $padrone;
                    $intel->accusa[$e->id][$padrone] = $e->mandante;
                }
            }

            // E il controspionaggio di casa cerca. Piu' a lungo dura, piu' e'
            // probabile che qualcosa non torni.
            $nazioneOspite = $c->mondo->nazioni[$ospite] ?? null;
            if ($nazioneOspite === null) {
                continue;
            }
            $anzianita = min(1.0, ($c->tick - (int) $t['reclutata_tick']) / 52.0);
            $fiuto = 0.004 + 0.02 * $anzianita
                * (0.4 + $nazioneOspite->maturita / 400.0 + $nazioneOspite->cyberDifesaNormalizzata());

            if (!$c->caso->prova('08_talpa', (int) $t['id'], $c->tick, $fiuto)) {
                continue;
            }

            if (!(int) $t['sospettata']) {
                // Primo indizio: qualcosa non torna, ma non basta per un arresto.
                $c->db->esegui('UPDATE sdb_poltrona SET sospettata = 1 WHERE id = ?', [(int) $t['id']]);
                $c->annota('sospetto_interno', [
                    'paese' => $nazioneOspite->nome,
                    'ruolo' => \App\Dati\Gabinetto::RUOLI[$t['ruolo']] ?? (string) $t['ruolo'],
                ]);
                continue;
            }

            // Seconda volta: si chiude il cerchio.
            $c->db->esegui(
                'UPDATE sdb_poltrona SET reclutata_da = NULL, reclutata_tick = NULL,
                        sospettata = 0, giocatore_id = NULL, lealta = 70 WHERE id = ?',
                [(int) $t['id']]);
            // Anche in memoria: salvaPalazzo() a fine tick riscrive la lealta'
            // dall'oggetto, e senza questa riga il 70 si perdeva.
            $inMemoria = $c->mondo->gabinetti[$ospite]->poltrone[(string) $t['ruolo']] ?? null;
            if ($inMemoria !== null) {
                $inMemoria->lealta = 70.0;
            }
            $c->annota('talpa_scoperta', [
                'paese'    => $nazioneOspite->nome,
                'ruolo'    => \App\Dati\Gabinetto::RUOLI[$t['ruolo']] ?? (string) $t['ruolo'],
                'chi'      => (string) $t['nome'],
                'per_conto' => $c->mondo->nazioni[$padrone]->nome ?? $padrone,
            ]);
            // Il rapporto fra i due paesi ne esce a pezzi.
            $r = $c->mondo->relazioni->fra($ospite, $padrone);
            if ($r !== null) {
                $r->ancora = max(-127.0, ($r->ancora ?? $r->affinita) - 35.0);
                $r->affinita = max(-127.0, $r->affinita - 35.0);
                $r->aggiornaUmore();
            }
            $nazioneOspite->clamoreSociale += 8.0;
        }
        return count($talpe);
    }

    /**
     * Le conversazioni altrui.
     *
     * "I tuoi messaggi privati non sono sicuri" e' la regola che, da sola,
     * produce buona parte del gioco politico. Qui viene applicata: per ogni
     * messaggio partito in questo tick, chi ha segnali e presenza tenta di
     * leggerlo — prima i metadati, che spesso bastano, poi il contenuto.
     *
     * Il corriere e' l'unico canale che i segnali non toccano. Costa due tick
     * di ritardo, ed e' un prezzo che a volte vale la pena.
     *
     * @param list<string> $globali le grandi potenze, che leggono comunque
     */
    private function intercettaMessaggi(ContestoTick $c, Intelligence $intel, array $globali): int
    {
        if ($c->db === null || $c->aVuoto) {
            return 0;
        }

        // Chi puo' provarci. Non e' il rango a decidere — un paese medio che ha
        // investito nei segnali legge piu' di una grande potenza distratta — ma
        // l'avere davvero una capacita' di ascolto. Le grandi potenze restano
        // nella lista perche' ascoltano comunque; accanto a loro ci va chiunque
        // abbia costruito un servizio.
        $ascoltatori = $globali;
        foreach ($intel->capacita as $iso => $discipline) {
            if (($discipline['sigint'] ?? 0.0) > 0.0 && !in_array($iso, $ascoltatori, true)) {
                $ascoltatori[] = $iso;
            }
        }
        // Si guarda la finestra fra il tick precedente e questo: e' il tempo in
        // cui le persone hanno scritto. Il corriere non compare: e' marcato
        // come non intercettabile alla partenza.
        $messaggi = $c->db->esegui(
            'SELECT m.id, m.sicurezza, nd.codice AS mittente, na.codice AS destinatario,
                    nd.id AS mittente_id, na.id AS destinatario_id
             FROM sdb_messaggio m
             JOIN sdb_poltrona pd ON pd.id = m.da_poltrona
             JOIN sdb_nazione  nd ON nd.id = pd.nazione_id
             JOIN sdb_poltrona pa ON pa.id = m.a_poltrona
             JOIN sdb_nazione  na ON na.id = pa.nazione_id
             WHERE m.intercettabile = 1 AND m.tick_invio >= ? AND m.tick_invio < ?',
            [max(0, $c->tick - 1), $c->tick])->fetchAll();

        // Chi ha una squadra appostata su un corrispondente preciso lo lavora
        // molto piu' a fondo della raccolta di routine: non ascolta per sapere,
        // ascolta per poter riscrivere, e quindi insiste finche' il testo non
        // e' chiaro. Senza questo vantaggio la manomissione non scatterebbe
        // quasi mai, perche' leggere per intero e' raro.
        $mirati = [];
        foreach ($c->db->esegui(
            'SELECT o.nazione_id, n.codice AS spia, d.codice AS bersaglio
             FROM sdb_manipolazione o
             JOIN sdb_nazione n ON n.id = o.nazione_id
             JOIN sdb_nazione d ON d.id = o.da_nazione_id
             WHERE o.stato = "attiva" AND o.scade_tick >= ?', [$c->tick])->fetchAll() as $o) {
            $mirati[$o['spia'] . '|' . $o['bersaglio']] = true;
        }
        $concentrazione = $c->calibrazione->numero('intelligence.concentrazione_mirata', 2.2);

        // Prima dei segnali, le persone. Chi ha reclutato il titolare di una
        // poltrona legge la corrispondenza di quella poltrona — tutta, sempre,
        // e senza decifrare niente: gliela consegnano. E' la ragione per cui
        // reclutare qualcuno vale piu' di qualunque antenna, ed e' anche
        // l'unica crepa della linea diretta, che i segnali non toccano ma le
        // persone si'.
        $quanti = $this->leggonoLeTalpe($c);

        foreach ($messaggi as $m) {
            $sicurezza = (int) $m['sicurezza'];

            $mittente = (string) $m['mittente'];
            $destinatario = (string) $m['destinatario'];
            $nm = $c->mondo->nazioni[$mittente] ?? null;
            if ($nm === null) {
                continue;
            }
            // Un canale aperto e' pubblico per definizione: lo leggono tutti.
            if ($sicurezza === 1) {
                foreach ($ascoltatori as $iso) {
                    if ($iso !== $mittente && $iso !== $destinatario) {
                        $this->registraIntercettazione($c, (int) $m['id'], $iso, 'integrale');
                        $quanti++;
                    }
                }
                continue;
            }

            // La difesa: quanto e' solido chi parla, e quanto e' chiuso il canale.
            $difesa = (1.0 + 0.9 * $sicurezza) * (0.7 + 0.8 * $nm->cyberDifesaNormalizzata() * 3.0);

            foreach ($ascoltatori as $iso) {
                if ($iso === $mittente || $iso === $destinatario) {
                    continue;
                }
                $capacita = $intel->copertura($iso, $mittente, 'sigint')
                    + 0.4 * $intel->copertura($iso, $destinatario, 'sigint');
                if ($capacita <= 0.0) {
                    continue;
                }
                $base = min(0.85, $capacita / $difesa);
                $seme = crc32($m['id'] . '|' . $iso);   // una chiave per coppia: col modulo due osservatori su tre condividevano la sorte

                $livello = null;
                if (isset($mirati[$iso . '|' . $mittente])) {
                    // Una squadra tarata su un corrispondente preciso non
                    // affronta tre ostacoli separati come la raccolta di
                    // routine: concentra i mezzi su un filo solo, e o entra nel
                    // testo o non ci entra. E' questo che rende possibile
                    // riscrivere le parole di qualcuno — senza, la lettura
                    // integrale resterebbe un caso su mille e la manomissione
                    // sarebbe una voce di menu che non scatta mai.
                    $mirata = min(0.85, $capacita * $concentrazione / $difesa);
                    if ($c->caso->prova('08_metadati', $seme, $c->tick, $mirata)) {
                        $livello = $c->caso->prova('08_integrale', $seme, $c->tick, $mirata * 0.75)
                            ? 'integrale' : 'parziale';
                    }
                } elseif ($c->caso->prova('08_metadati', $seme, $c->tick, $base)) {
                    $livello = 'metadati';
                    if ($c->caso->prova('08_parziale', $seme, $c->tick, $base * 0.45)) {
                        $livello = 'parziale';
                        if ($c->caso->prova('08_integrale', $seme, $c->tick, $base * 0.35)) {
                            $livello = 'integrale';
                        }
                    }
                }
                if ($livello !== null) {
                    $spia = $this->registraIntercettazione($c, (int) $m['id'], $iso, $livello);
                    $quanti++;

                    // Essere letti per intero vuol dire che una strada dentro
                    // c'e', e finche' nessuno se ne accorge si allarga.
                    if ($livello === 'integrale' && $nm !== null) {
                        $nm->cyberDifesa = max(0.0, $nm->cyberDifesa
                            - $c->calibrazione->numero('intelligence.costo_violazione', 0.9));
                    }

                    // Letto per intero: e' l'unico istante in cui si puo' anche
                    // riscrivere. Il messaggio e' in transito e non e' ancora
                    // arrivato a nessuno.
                    if ($livello === 'integrale' && $spia !== null) {
                        $this->manometti($c, $m, $spia);
                    }
                }
            }
        }
        return $quanti;
    }

    /**
     * Le difese informatiche, che prima non si muovevano mai.
     *
     * Erano il valore del seme, per sempre — e sono il numero che decide se i
     * messaggi di un paese si possono leggere e riscrivere. Adesso seguono un
     * obiettivo: chi ha istituzioni solide e soldi ci arriva, chi non li ha no.
     *
     * (L'offesa cibernetica non sta qui: e' gia' una delle sei discipline di
     * Intelligence, e un secondo numero che dicesse la stessa cosa sarebbe
     * solo un'altra manopola da tenere allineata.)
     *
     * E chi viene letto paga. Un'intercettazione riuscita non e' un evento
     * neutro: vuol dire che qualcuno ha trovato una strada dentro, e finche'
     * non se ne accorge quella strada resta aperta e si allarga. Chi invece
     * si accorge di una manomissione tappa il buco, e ne esce piu' forte di
     * prima: e' l'unica cosa buona che capita a chi scopre di essere stato
     * violato.
     */
    private function manutieneLeDifese(ContestoTick $c): void
    {
        $cal     = $c->calibrazione;
        $perTick = 1.0 / $cal->numero('tempo.tick_per_anno', 52.0);
        $passo   = $cal->numero('intelligence.manutenzione_difese_anno', 0.35) * $perTick;

        foreach ($c->mondo->nazioni as $n) {
            $ricchezza = min(1.0, $n->pilProCapite / 45000.0);
            $obiettivo = 18.0 + 52.0 * ($n->maturita / 255.0) + 22.0 * $ricchezza;
            $n->cyberDifesa += ($obiettivo - $n->cyberDifesa) * $passo;
            $n->cyberDifesa = max(0.0, min(100.0, $n->cyberDifesa));
        }
    }

    /**
     * Quel che le talpe consegnano.
     *
     * Una poltrona reclutata non ha bisogno di essere intercettata: il suo
     * titolare porta fuori le carte. Vale per ogni canale, corriere e linea
     * diretta compresi, perche' nessuna cifratura protegge da chi ha
     * legittimamente la chiave.
     */
    private function leggonoLeTalpe(ContestoTick $c): int
    {
        if ($c->db === null) {
            return 0;
        }
        $righe = $c->db->esegui(
            'SELECT m.id, COALESCE(pd.reclutata_da, pa.reclutata_da) AS padrone
             FROM sdb_messaggio m
             JOIN sdb_poltrona pd ON pd.id = m.da_poltrona
             JOIN sdb_poltrona pa ON pa.id = m.a_poltrona
             WHERE m.tick_invio >= ? AND m.tick_invio < ?
               AND (pd.reclutata_da IS NOT NULL OR pa.reclutata_da IS NOT NULL)',
            [max(0, $c->tick - 1), $c->tick])->fetchAll();

        $quanti = 0;
        foreach ($righe as $r) {
            $c->db->esegui(
                'INSERT INTO sdb_intercettazione (messaggio_id, nazione_id, livello, tick)
                 VALUES (?,?,"integrale",?)
                 ON DUPLICATE KEY UPDATE livello = "integrale"',
                [(int) $r['id'], (int) $r['padrone'], $c->tick]);
            $quanti++;
        }
        return $quanti;
    }

    /**
     * La manomissione, se qualcuno l'aveva ordinata, e il controspionaggio di
     * chi la subisce.
     *
     * Chi riceve non scopre *chi* ha riscritto — per quello servirebbe il
     * lavoro di attribuzione, che e' un altro mestiere — ma puo' accorgersi
     * che qualcosa non torna. E' abbastanza per non fidarsi, che e' gia' il
     * danno peggiore.
     *
     * @param array<string,mixed> $m
     */
    private function manometti(ContestoTick $c, array $m, int $spia): void
    {
        $falso = new \App\Gioco\Falsificazione($c->db);
        $modo = $falso->applica((int) $m['id'], $spia, (int) $m['mittente_id'],
            (int) $m['destinatario_id'], $c->tick);
        if ($modo === null) {
            return;
        }

        $c->annota('manomissione', [
            'fra' => $c->mondo->nazioni[$m['mittente']]->nome ?? $m['mittente'],
            'e'   => $c->mondo->nazioni[$m['destinatario']]->nome ?? $m['destinatario'],
            'modo' => $modo,
        ]);

        // Il controspionaggio di chi riceve. Un messaggio sostituito di sana
        // pianta stona piu' di una frase infilata dentro il vero; uno sparito
        // non lascia niente da esaminare, e infatti e' il piu' difficile da
        // accorgersene.
        $vittima = $c->mondo->nazioni[$m['destinatario']] ?? null;
        if ($vittima === null) {
            return;
        }
        $sospetto = match ($modo) {
            'sostituisci' => 0.40,
            'inserisci'   => 0.22,
            default       => 0.06,
        };
        $sospetto *= 0.5 + $vittima->cyberDifesaNormalizzata() * 2.0;
        if ($c->caso->prova('08_manomissione', (int) $m['id'], $c->tick, min(0.9, $sospetto))) {
            $c->db->esegui('UPDATE sdb_messaggio SET manomissione_sospetta = 1 WHERE id = ?',
                [(int) $m['id']]);
            // Chi scopre di essere stato manomesso tappa il buco: e' l'unica
            // cosa buona che capita a chi scopre di essere stato violato.
            $vittima->cyberDifesa = min(100.0, $vittima->cyberDifesa
                + $c->calibrazione->numero('intelligence.premio_scoperta', 2.5));
        }
    }

    private function registraIntercettazione(ContestoTick $c, int $messaggio, string $iso, string $livello): ?int
    {
        $id = $c->db->esegui('SELECT id FROM sdb_nazione WHERE codice = ?', [$iso])->fetchColumn();
        if ($id === false) {
            return null;
        }
        $c->db->esegui(
            'INSERT INTO sdb_intercettazione (messaggio_id, nazione_id, livello, tick) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE livello = VALUES(livello)',
            [$messaggio, (int) $id, $livello, $c->tick]);
        return (int) $id;
    }

    /**
     * Chi ha ragione di guardare questo evento: il bersaglio, i suoi vicini,
     * i vicini del mandante, e le grandi potenze — che guardano tutto.
     *
     * @param list<string> $globali
     * @return list<string>
     */
    private function osservatori(Evento $e, $mondo, array $globali): array
    {
        $lista = array_merge(
            [$e->bersaglio],
            $globali,
            array_slice($mondo->relazioni->vicinato[$e->bersaglio] ?? [], 0, 6),
            array_slice($mondo->relazioni->vicinato[$e->mandante] ?? [], 0, 4),
        );
        return array_values(array_unique($lista));
    }

    private function disciplinaMigliore(Evento $e, string $iso, Intelligence $intel, string $dove): string
    {
        $migliore = 'osint';
        $valore = -1.0;
        foreach (Intelligence::PERTINENTI[$e->dominio] ?? ['osint'] as $d) {
            $v = $intel->copertura($iso, $dove, $d);
            if ($v > $valore) {
                $valore = $v;
                $migliore = $d;
            }
        }
        return $migliore;
    }
}
