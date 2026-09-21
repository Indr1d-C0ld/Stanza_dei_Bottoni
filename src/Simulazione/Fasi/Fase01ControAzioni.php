<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Dati\Evento;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 01 — Risoluzione delle contro-azioni.
 *
 * LEGGE:      gli eventi in volo, le risposte dichiarate, le crisi aperte
 * SCRIVE:     lo stato degli eventi
 * INVARIANTE: una contro-azione agisce solo su eventi ancora reversibili —
 *             la reversibilità decade con l'avanzare della maturazione
 *
 * Con i giocatori, qui si risolvono le loro risposte: fermare, negare
 * l'autorizzazione, insabbiare, avvisare il bersaglio, far trapelare. Senza
 * giocatori, due cose accadono comunque:
 *
 *   1. il mandante annulla ciò che non ha più senso — il bersaglio è caduto da
 *      solo, o il rapporto si è ribaltato mentre l'operazione era in volo;
 *   2. i servizi del bersaglio intercettano qualcosa.
 *
 * Il punto 2 non è più un tiro cieco: agisce sulla conoscenza costruita dalla
 * fase 08 del tick precedente. Serve essere arrivati almeno al terzo livello —
 * sapere che il bersaglio sei tu — e il rapporto diplomatico peggiora soltanto
 * se si è arrivati al quarto, cioè se si può dimostrare chi è stato.
 */
final class Fase01ControAzioni implements Fase
{
    public function codice(): string { return '01'; }
    public function nome(): string   { return 'Risoluzione delle contro-azioni'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $annullati = 0;
        $sventati  = 0;
        $crisi     = $this->gestisciCrisi($c);
        $linee     = $this->rispondiAlleLinee($c);

        foreach ($mondo->eventi as $e) {
            if (!$e->inVolo()) {
                continue;
            }
            $mandante  = $mondo->nazioni[$e->mandante]  ?? null;
            $bersaglio = $mondo->nazioni[$e->bersaglio] ?? null;
            if ($mandante === null || $bersaglio === null) {
                continue;
            }

            $reversibilita = $e->reversibilita($c->tick);
            if ($reversibilita <= 0.02) {
                continue;   // troppo tardi: ormai è in volo e basta
            }

            // --- 1. il mandante ci ripensa ---------------------------------
            $r = $mondo->relazioni->fra($e->mandante, $e->bersaglio);
            $ostileOra = $r !== null && $r->affinita < -20.0;
            $amicoOra  = $r !== null && $r->affinita > 45.0;
            $eraOstile = $e->dannoBase > 0;

            // Un'operazione ostile contro chi nel frattempo è diventato amico
            // (o viceversa) non ha più ragione di esistere.
            $senzaSenso = ($eraOstile && $amicoOra) || (!$eraOstile && $ostileOra);
            if ($senzaSenso && $c->caso->prova('01_ripensamento', $e->id, $c->tick, 0.35 * $reversibilita)) {
                $e->stato = Evento::FERMATO;
                $mandante->azioniInVolo = max(0, $mandante->azioniInVolo - 1);
                $annullati++;
                continue;
            }

            // --- 2. il bersaglio agisce su cio' che sa ---------------------
            // Non piu' un tiro cieco: serve essere arrivati almeno al terzo
            // livello di conoscenza, cioe' sapere che il bersaglio sei tu.
            $intel = $mondo->intelligence ?? null;
            if ($intel === null) {
                continue;
            }
            $livello = $intel->livello($e->bersaglio, $e->id);
            if ($livello < 3) {
                continue;
            }
            if ($e->impronta >= 0.60) {
                continue;   // un'azione dichiarata non si sventa: si subisce
            }

            // Sapere che sta arrivando e' una cosa, arrivare in tempo e'
            // un'altra: conta quanto e' ancora reversibile e quanto si e'
            // attrezzati. Avere anche l'attribuzione aiuta a colpire giusto.
            $capacita = 0.20 + $bersaglio->maturita / 400.0 + $bersaglio->cyberDifesaNormalizzata();
            $probabilita = 0.55 * $reversibilita * $capacita * ($livello >= 4 ? 1.35 : 1.0);

            if ($c->caso->prova('01_intercettazione', $e->id, $c->tick, $probabilita)) {
                $e->stato = Evento::FERMATO;
                $mandante->azioniInVolo = max(0, $mandante->azioniInVolo - 1);
                $sventati++;
                $c->annota('operazione_sventata', [
                    'bersaglio' => $bersaglio->nome,
                    'mandante'  => $livello >= 4 ? $mandante->nome : 'ignoti',
                    'verbo'     => $e->verbo,
                ]);
                // Il rapporto peggiora solo se si puo' DIMOSTRARE chi e' stato.
                if ($livello >= 4) {
                    $rb = $mondo->relazioni->fra($e->bersaglio, $e->mandante);
                    if ($rb !== null) {
                        $colpo = 20.0 * $e->intensita;
                        $rb->ancora = ($rb->ancora ?? $rb->affinita) - $colpo;
                        $rb->affinita = max(-127.0, $rb->affinita - $colpo);
                        $rb->aggiornaUmore();
                    }
                }
            }
        }

        return new EsitoFase(['annullati' => $annullati, 'sventati' => $sventati, 'crisi' => $crisi]);
    }

    /**
     * Le crisi in corso: chi deve rispondere, risponde.
     *
     * Per le nazioni in mano all'apparato decide la formula di Crawford, ed e'
     * la sua idea piu' bella: il calcolatore stima **anche l'oltraggio
     * dell'avversario** e confronta i due. Se il proprio eccede, tiene duro.
     *
     * "Perche' distruggeresti il mondo per una crisi da 22 punti? Il
     * calcolatore e' ancora piu' giustificato a chiedere: perche' sei salito a
     * DEFCON 2 per qualcosa che per te ne valeva 18? Per litigare bisogna
     * essere in due."
     *
     * Ci sono tre correttivi, tutti dall'originale: nei primi gradini il
     * calcolatore **bluffa** (si comporta come se avesse piu' ragione di
     * quanta ne abbia), c'e' un po' di rumore, e una costante sottratta che
     * lascia al giocatore la possibilita' di bluffare a sua volta.
     */
    /**
     * Le proposte di linea diretta che arrivano a un gabinetto retto
     * dall'apparato.
     *
     * Una linea diretta e' un atto di allineamento pubblico: si accetta con chi
     * si sta gia' bene e non si e' in guerra, si rifiuta con gli altri. La
     * soglia non e' alta — aprire un canale non e' un'alleanza — ma non e'
     * nemmeno zero, perche' il mondo vedra' la linea e ne trarra' conclusioni.
     */
    private function rispondiAlleLinee(ContestoTick $c): int
    {
        if ($c->db === null || $c->aVuoto) {
            return 0;
        }
        $aperte = $c->db->esegui(
            'SELECT l.id, l.a_nazione_id, l.b_nazione_id, pr.nazione_id AS proponente,
                    a.codice AS iso_a, b.codice AS iso_b
             FROM sdb_linea l
             JOIN sdb_poltrona pr ON pr.id = l.proposta_da
             JOIN sdb_nazione a ON a.id = l.a_nazione_id
             JOIN sdb_nazione b ON b.id = l.b_nazione_id
             WHERE l.stato = "proposta"')->fetchAll();
        if ($aperte === []) {
            return 0;
        }

        $servizio = new \App\Gioco\Linea($c->db);
        $soglia   = $c->calibrazione->numero('linee.soglia_affinita', 20.0);
        $mosse = 0;

        foreach ($aperte as $l) {
            $destinatario = (int) $l['proponente'] === (int) $l['a_nazione_id']
                ? (int) $l['b_nazione_id'] : (int) $l['a_nazione_id'];

            // Se quel gabinetto ha un titolare in carne e ossa, la risposta e'
            // sua: la macchina non decide al posto di un giocatore.
            $presidiata = (int) $c->db->esegui(
                'SELECT COUNT(*) FROM sdb_poltrona p
                 WHERE p.nazione_id = ? AND p.ruolo IN ("capo","esteri") AND '
                 . \App\Gioco\Delega::sqlPresidiata('p', $c->tick),
                [$destinatario])->fetchColumn();
            if ($presidiata > 0) {
                continue;
            }

            $isoDest = (int) $l['a_nazione_id'] === $destinatario
                ? (string) $l['iso_a'] : (string) $l['iso_b'];
            $isoAltro = (int) $l['a_nazione_id'] === $destinatario
                ? (string) $l['iso_b'] : (string) $l['iso_a'];

            $r = $c->mondo->relazioni->fra($isoDest, $isoAltro);
            $affinita = $r?->affinita ?? 0.0;

            $inGuerra = false;
            foreach ($c->mondo->guerre as $g) {
                if (($g['aggressore'] === $isoDest && $g['difensore'] === $isoAltro)
                    || ($g['aggressore'] === $isoAltro && $g['difensore'] === $isoDest)) {
                    $inGuerra = true;
                    break;
                }
            }

            $accetta = !$inGuerra && $affinita
                + $c->caso->rumore('01_linea', (int) $l['id'], $c->tick, 12.0) >= $soglia;

            // Serve una poltrona vera a firmare, anche quando a decidere e' la
            // macchina: chi ha firmato resta agli atti.
            $firma = (int) $c->db->esegui(
                'SELECT id FROM sdb_poltrona WHERE nazione_id = ? AND ruolo IN ("esteri","capo")
                 ORDER BY FIELD(ruolo, "esteri", "capo") LIMIT 1', [$destinatario])->fetchColumn();
            if ($firma === 0) {
                continue;
            }
            $servizio->rispondi(
                ['id' => $firma, 'ruolo' => 'esteri', 'nazione_id' => $destinatario],
                (int) $l['id'], $accetta, $c->tick);

            $c->annota($accetta ? 'linea_aperta' : 'linea_rifiutata', [
                'fra' => $c->mondo->nazioni[$isoDest]->nome ?? $isoDest,
                'e'   => $c->mondo->nazioni[$isoAltro]->nome ?? $isoAltro,
            ]);
            $mosse++;
        }
        return $mosse;
    }

    private function gestisciCrisi(ContestoTick $c): int
    {
        if ($c->db === null || $c->aVuoto) {
            return 0;
        }
        $cal = $c->calibrazione;
        $servizio = new \App\Gioco\Crisi(
            $c->db,
            (array) $cal->leggi('crisi.gradini', []),
            $cal->numero('crisi.crescita_posta', 0.38),
            (int) $cal->numero('crisi.pazienza_tick', 3),
        );
        $servizio->collega($c->mondo);

        $aperte = $c->db->esegui(
            'SELECT k.*, a.codice AS iso_sfidante, b.codice AS iso_sfidato,
                    (SELECT COUNT(*) FROM sdb_poltrona p
                      WHERE p.nazione_id = IF(k.tocca_a = "sfidante", k.sfidante_id, k.sfidato_id)
                        AND ' . \App\Gioco\Delega::sqlPresidiata('p', $c->tick) . ') AS presidiata,
                    (SELECT MIN(p2.id) FROM sdb_poltrona p2
                      WHERE p2.nazione_id = IF(k.tocca_a = "sfidante", k.sfidante_id, k.sfidato_id)
                        AND p2.giocatore_id IS NOT NULL) AS poltrona_assente
             FROM sdb_crisi k
             JOIN sdb_nazione a ON a.id = k.sfidante_id
             JOIN sdb_nazione b ON b.id = k.sfidato_id
             WHERE k.stato = "aperta"')->fetchAll();

        $mosse = 0;
        foreach ($aperte as $k) {
            $parte = (string) $k['tocca_a'];
            $scaduta = (int) $k['scade_tick'] < $c->tick;

            // Una decisione non presa e' comunque una decisione: chi non
            // risponde entro la scadenza ha ceduto.
            if ((int) $k['presidiata'] > 0) {
                if ($scaduta) {
                    $servizio->rispondi((int) $k['id'], $parte, 'cede', $c->tick);
                    $mosse++;
                }
                continue;
            }

            $miaPosta  = (float) ($parte === 'sfidante' ? $k['posta_sfidante'] : $k['posta_sfidato']);
            $suaPosta  = (float) ($parte === 'sfidante' ? $k['posta_sfidato'] : $k['posta_sfidante']);
            $livello   = (int) $k['livello'];

            // Chi e' seduto al tavolo, e con quali eserciti alle spalle.
            $ioIso  = (string) ($parte === 'sfidante' ? $k['iso_sfidante'] : $k['iso_sfidato']);
            $luiIso = (string) ($parte === 'sfidante' ? $k['iso_sfidato']  : $k['iso_sfidante']);
            $io  = $c->mondo->nazioni[$ioIso]  ?? null;
            $lui = $c->mondo->nazioni[$luiIso] ?? null;

            // Il rapporto di forze convenzionali: sopra 1 il piu' forte siamo noi.
            $tetto = $cal->numero('crisi.tetto_forze', 2.0);
            $forze = ($io !== null && $lui !== null)
                ? max(1.0 / $tetto, min($tetto, $io->equipaggiamento / max(1.0, $lui->equipaggiamento)))
                : 1.0;

            // La paura della guerra cresce col quadrato del gradino, perche' in
            // fondo alla scala c'e' una cosa vera e non una parola. Il piu'
            // debole la vede prima; fra due potenze nucleari la vedono entrambi,
            // ed e' questo che tiene ferme le mani sulla scala.
            $paura = ($livello / 9.0) ** $cal->numero('crisi.esponente_paura', 2.4)
                * $cal->numero('crisi.peso_paura', 28.0) / $forze;

            // E sopra ci sta la paura dell'altra cosa, che non si divide per
            // niente e si sveglia solo in cima. Fra due potenze nucleari il
            // nono gradino e' quasi irraggiungibile, e deve esserlo.
            if ($io !== null && $lui !== null
                && $io->posturaNucleare >= 3 && $lui->posturaNucleare >= 3) {
                $paura += ($livello / 9.0) ** $cal->numero('crisi.esponente_nucleare', 4.0)
                    * $cal->numero('crisi.peso_nucleare', 45.0)
                    * min(7, min($io->posturaNucleare, $lui->posturaNucleare)) / 7.0;
            }

            // L'eccesso di oltraggio; piu' l'impegno gia' speso, che cresce a
            // ogni gradino e rende sempre piu' caro tornare indietro; piu' il
            // bluff che si sgonfia salendo; meno la reluttanza di partenza e
            // meno quello che si rischia davvero continuando a salire.
            $eccesso = ($miaPosta - $suaPosta)
                + $miaPosta * $cal->numero('crisi.peso_impegno', 0.18)
                + max(0.0, (9 - $livello)) * 1.2
                + $c->caso->rumore('01_crisi', (int) $k['id'], $c->tick, 3.0)
                - $cal->numero('crisi.reluttanza', 2.0)
                - $paura;

            $azione = $eccesso > 0 ? 'scala' : 'cede';

            // Da meta' scala in su, ogni passo puo' sfuggire di mano.
            if ($azione === 'scala' && $livello >= 5) {
                $rischio = (array) $cal->leggi('crisi.incidente_base', []);
                $p = (float) ($rischio[$livello + 1] ?? 0.01)
                    * (1.0 + $c->mondo->nastiness / 50.0)
                    * $cal->numero('crisi.peso_nastiness', 1.5);
                if ($c->caso->prova('01_incidente', (int) $k['id'], $c->tick, $p)) {
                    $c->db->esegui('UPDATE sdb_crisi SET stato = "incidente", ultimo_tick = ? WHERE id = ?',
                        [$c->tick, (int) $k['id']]);
                    $c->annota('incidente', [
                        'fra' => $c->mondo->nazioni[$k['iso_sfidante']]->nome ?? $k['iso_sfidante'],
                        'e'   => $c->mondo->nazioni[$k['iso_sfidato']]->nome ?? $k['iso_sfidato'],
                        'gradino' => $servizio->gradino($livello),
                    ]);
                    $this->ricadute($c, (string) $k['iso_sfidante'], (string) $k['iso_sfidato']);
                    $mosse++;
                    continue;
                }
            }

            $servizio->rispondi((int) $k['id'], $parte, $azione, $c->tick);
            $c->mondo->nastiness += $azione === 'scala' ? 0.8 : 0.0;
            $mosse++;

            // Se quella poltrona ha un titolare che semplicemente non c'e',
            // deve trovare il conto al rientro.
            if ($k['poltrona_assente'] !== null) {
                (new \App\Gioco\Delega($c->db))->annota(
                    (int) $k['poltrona_assente'], $c->tick, 'apparato',
                    $azione === 'scala' ? 'ha alzato una crisi' : 'ha ceduto in una crisi',
                    sprintf('gradino %d con %s', (int) $k['livello'],
                        $c->mondo->nazioni[$parte === 'sfidante' ? $k['iso_sfidato'] : $k['iso_sfidante']]->nome
                        ?? '—'));
            }
        }
        return $mosse;
    }

    /** Un incidente non uccide nessuno per conto suo: avvelena tutto il resto. */
    private function ricadute(ContestoTick $c, string $a, string $b): void
    {
        foreach ([[$a, $b], [$b, $a]] as [$da, $verso]) {
            $r = $c->mondo->relazioni->fra($da, $verso);
            if ($r !== null) {
                $r->ancora = max(-127.0, ($r->ancora ?? $r->affinita) - 40.0);
                $r->affinita = max(-127.0, $r->affinita - 40.0);
                $r->aggiornaUmore();
            }
            $n = $c->mondo->nazioni[$da] ?? null;
            if ($n !== null) {
                $n->ansiaMilitare = (int) min(100, $n->ansiaMilitare + 35);
                $n->netPeace = max($n->netPeace, 4);
            }
        }
        $c->mondo->nastiness += 12.0;
    }
}
