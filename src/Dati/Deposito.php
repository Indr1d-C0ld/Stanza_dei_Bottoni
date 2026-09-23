<?php

declare(strict_types=1);

namespace App\Dati;

use App\Nucleo\Basedati;

/**
 * La persistenza del mondo.
 *
 * Strategia deliberata: **la topologia si ricostruisce dal seme, lo stato si
 * legge dalla base dati.** Nazioni, confini, coppie di relazione e profili
 * iniziali dei servizi sono deterministici e derivano dai file in db/seed; solo
 * i valori che cambiano nel tempo vengono scritti e riletti.
 *
 * Costa una riga di codice in meno per ogni campo strutturale, e soprattutto
 * rende impossibile che la base dati e il seme divergano in silenzio.
 */
final class Deposito
{
    /** @var array<string,int> ISO3 => id */
    private array $idPerIso = [];
    /** @var array<int,string> id => ISO3 */
    private array $isoPerId = [];
    /** @var array<int,bool> id evento gia' scritti */
    private array $eventiScritti = [];
    private int $notizieScritte = 0;

    public function __construct(private readonly Basedati $db) {}

    // ------------------------------------------------------------ anagrafica

    /**
     * Popola le tabelle immutabili a partire dal seme. Idempotente: si puo'
     * rilanciare a ogni avvio senza danni.
     */
    public function preparaAnagrafica(string $percorsoSeme): void
    {
        $fh = fopen($percorsoSeme, 'r');
        $intestazione = fgetcsv($fh, 0, ',', '"', '\\');
        $righe = [];
        while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            $righe[] = array_combine($intestazione, $r);
        }
        fclose($fh);

        $regioni = array_values(array_unique(array_column($righe, 'regione')));
        foreach ($regioni as $codice) {
            $this->db->esegui(
                'INSERT IGNORE INTO sdb_regione (codice, nome) VALUES (?, ?)',
                [$codice, $codice],
            );
        }
        $ideologie = array_values(array_unique(array_column($righe, 'ideologia_formale')));
        foreach ($ideologie as $codice) {
            $this->db->esegui(
                'INSERT IGNORE INTO sdb_ideologia (codice, nome) VALUES (?, ?)',
                [$codice, str_replace('_', ' ', $codice)],
            );
        }

        $idRegione   = $this->mappa('SELECT codice, id FROM sdb_regione');
        $idIdeologia = $this->mappa('SELECT codice, id FROM sdb_ideologia');

        foreach ($righe as $r) {
            $this->db->esegui(
                'INSERT INTO sdb_nazione
                    (codice, nome, regione_id, ideologia_id, area_km2, giocabile,
                     valore_prestigio, valore_strategico, maturita)
                 VALUES (?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                    nome = VALUES(nome), regione_id = VALUES(regione_id),
                    ideologia_id = VALUES(ideologia_id), giocabile = VALUES(giocabile),
                    valore_prestigio = VALUES(valore_prestigio),
                    valore_strategico = VALUES(valore_strategico), maturita = VALUES(maturita)',
                [
                    $r['iso3'], $r['nome'], $idRegione[$r['regione']],
                    $idIdeologia[$r['ideologia_formale']], (int) $r['area_km2'],
                    (int) $r['giocabile'], (int) $r['valore_prestigio'],
                    (int) $r['valore_strategico'], (int) $r['maturita'],
                ],
            );
        }
        $this->caricaIdentificatori();
    }

    public function caricaIdentificatori(): void
    {
        $this->idPerIso = array_map('intval', $this->mappa('SELECT codice, id FROM sdb_nazione'));
        $this->isoPerId = array_flip($this->idPerIso);
    }

    public function ultimoTick(): int
    {
        $s = $this->db->esegui('SELECT COALESCE(MAX(tick), 0) FROM sdb_mondo_stato');
        return (int) $s->fetchColumn();
    }

    // ---------------------------------------------------------------- scrive

    /**
     * Cancella il mondo vissuto, lasciando l'anagrafica e i giocatori.
     *
     * Lo usano bin/avvia_mondo.php --ricomincia e la prova di fedelta' della
     * persistenza (tests/13), che deve partire da un mondo pulito come il
     * cron: una lista sola, cosi' le due non possono divergere.
     *
     * L'ORDINE CONTA, e prima non contava: sdb_conoscenza ha una chiave
     * esterna su sdb_evento, e cancellare gli eventi per primi faceva fallire
     * l'intero riavvio con una violazione di vincolo. Lo strumento non aveva
     * mai funzionato su un mondo che avesse prodotto anche un solo evento —
     * cioe' su qualunque mondo vissuto. Trovato riavviando il mondo vero.
     *
     * Si cancella dai FIGLI verso i PADRI. Le dipendenze vere, lette dallo
     * schema:
     *
     *   sdb_evento   ← sdb_conoscenza
     *   sdb_nazione  ← sdb_capacita_intel, sdb_gabinetto, sdb_nazione_stato,
     *                  sdb_relazione
     *
     * L'anagrafica (sdb_nazione, sdb_regione, sdb_ideologia) NON si tocca: la
     * ricostruisce preparaAnagrafica() subito dopo, e le poltrone vi si
     * appoggiano.
     */
    public function azzeraMondo(): void
    {
        $ordine = [
            // prima i figli
            'sdb_conoscenza',
            // poi il resto dello stato del mondo
            'sdb_evento',
            'sdb_nazione_stato',
            'sdb_mondo_stato',
            'sdb_relazione',
            'sdb_notizia',
            'sdb_guerra',
            'sdb_poltrona',
            'sdb_fazione',
            'sdb_gabinetto',
            'sdb_tick_log',
            'sdb_capacita_intel',
            'sdb_presenza_intel',
            'sdb_assenza_fatto',
            'sdb_ordine',
            'sdb_punteggio',
            // Tutto cio' che il GIOCO accumula sopra il mondo. Mancava, e dopo il
            // riavvio del 22/09/2026 il mondo nuovo si portava dietro quello
            // vecchio: un'epoca «in corso» cominciata al tick 118 di un mondo al
            // tick 0, messaggi e offerte fra poltrone che non esistevano piu',
            // intercettazioni e rivelazioni. E siccome gli eventi ripartono da 1,
            // una crisi vecchia poteva puntare a un evento nuovo che non c'entrava.
            'sdb_crisi_passo',
            'sdb_crisi',
            'sdb_intercettazione',
            'sdb_manipolazione',
            'sdb_messaggio',
            'sdb_offerta',
            'sdb_linea',
            'sdb_agenda',
            'sdb_rivelazione',
            'sdb_epoca',
            'sdb_strozzatura',
            'sdb_memoria_azioni',
            // Restano di proposito: i giocatori e i loro inviti, la posta, le
            // leve dell'arbitro (sono configurazione, non storia) e il registro
            // degli atti dell'arbitro, che e' la traccia di chi ha fatto cosa.
        ];
        foreach ($ordine as $t) {
            try {
                $this->db->esegui("DELETE FROM $t");
            } catch (\Throwable $e) {
                // Una tabella che non c'e' piu' non e' un motivo per non
                // ripartire: si dice e si tira avanti.
                fwrite(STDERR, "  (salto $t: " . $e->getMessage() . ")\n");
            }
        }
        // I giocatori restano, ma la loro reputazione e il segno dell'ultimo
        // avviso appartenevano al mondo vecchio: un avviso «gia' mandato al
        // tick 118» zittirebbe gli avvisi fino al tick 118 del mondo nuovo.
        $this->db->esegui('UPDATE sdb_giocatore SET integrita = 128, ultimo_avviso_tick = 0');
        $this->idPerIso = [];
    }

    /**
     * Scrive il mondo. TUTTO O NIENTE.
     *
     * Prima erano nove scritture separate senza transazione, e `sdb_mondo_stato`
     * era la seconda: se la connessione cadeva dopo, ultimoTick() diceva gia'
     * «N» e il giro successivo girava su nazioni del tick N e relazioni,
     * eventi, gabinetti, guerre del tick N-1 — un mondo cucito male che niente
     * avrebbe mai riparato. `salvaStrozzature` cancella e reinserisce, e in
     * mezzo il sito vedeva le tabelle vuote. Adesso si scrive tutto insieme, o
     * niente; e se il tick intero e' gia' dentro una transazione — come fa
     * bin/tick.php — ci si unisce a quella.
     */
    public function salva(Mondo $mondo, int $tick, string $dataGioco): void
    {
        if ($this->idPerIso === []) {
            $this->caricaIdentificatori();
        }
        $this->db->inTransazione(function () use ($mondo, $tick, $dataGioco): void {
            $this->salvaNazioni($mondo, $tick);
            $this->salvaMondo($mondo, $tick, $dataGioco);
            $this->salvaRelazioni($mondo);
            $this->salvaEventi($mondo);
            $this->salvaPalazzo($mondo);
            $this->salvaGuerre($mondo, $tick);
            $this->salvaStrozzature($mondo);
            $this->salvaNotizie($mondo);
            $this->salvaServizi($mondo, $tick);
            $this->salvaMemoria($mondo, $tick);
        });
    }

    /**
     * La memoria della dottrina: quando ciascun paese ha fatto l'ultima volta
     * una certa mossa contro un certo bersaglio. Prima non si salvava, e
     * l'attesa fra due mosse uguali si azzerava a ogni tick del mondo vivo.
     * Si pota oltre l'attesa piu' lunga del catalogo (l'invasione, 260 tick):
     * piu' in la' non puo' piu' fermare niente.
     */
    private function salvaMemoria(Mondo $mondo, int $tick): void
    {
        $this->db->esegui('DELETE FROM sdb_memoria_azioni WHERE tick < ?', [$tick - 300]);
        $blocchi = [];
        $valori = [];
        foreach ($mondo->azioniRecenti as $chiave => $quando) {
            if ((int) $quando < $tick - 300) {
                continue;
            }
            $blocchi[] = '(?,?)';
            array_push($valori, mb_substr((string) $chiave, 0, 96), (int) $quando);
            if (count($blocchi) >= 500) {
                $this->db->esegui('REPLACE INTO sdb_memoria_azioni (chiave, tick) VALUES '
                    . implode(',', $blocchi), $valori);
                $blocchi = [];
                $valori = [];
            }
        }
        if ($blocchi !== []) {
            $this->db->esegui('REPLACE INTO sdb_memoria_azioni (chiave, tick) VALUES '
                . implode(',', $blocchi), $valori);
        }
    }

    private function salvaNazioni(Mondo $mondo, int $tick): void
    {
        $campi = [
            'popolazione', 'pil', 'crescita_pil', 'pil_pro_capite', 'consumo_pro_capite',
            'quota_consumi', 'quota_investimenti', 'quota_militare', 'democrazia',
            'influenza_totale', 'etica', 'ambizione', 'qualita_vita', 'stato_polizia',
            'net_peace', 'legittimita', 'aspettativa', 'clamore_sociale', 'orientamento',
            'ansia_militare', 'controllo_info', 'cyber_difesa',
            'soldati', 'equipaggiamento', 'potenza_militare', 'postura_nucleare',
            'forza_insorti',             // La memoria: senza queste, a ogni tick il mondo dimentica chi e'.
            'deriva_politica', 'integrita', 'crescita_strutturale', 'pressione_esterna',
            'reputazione_sporca', 'consumo_pro_capite_prec', 'azioni_in_volo',
            'cambi_esecutivo', 'cambi_irregolari', 'vittorie_insorti', 'scandali_subiti',
            'anno_ultimo_cambio', 'prossima_elezione', 'mandato_tick',
        ];
        $segnaposti = '(?, ?, ' . implode(', ', array_fill(0, count($campi), '?')) . ')';
        $blocchi = [];
        $valori = [];
        foreach ($mondo->elenco() as $n) {
            $id = $this->idPerIso[$n->iso3] ?? null;
            if ($id === null) {
                continue;
            }
            $blocchi[] = $segnaposti;
            array_push($valori, $id, $tick,
                $n->popolazione, $n->pil, $n->crescitaPil, $n->pilProCapite,
                $n->consumoProCapite, $n->quotaConsumi, $n->quotaInvestimenti, $n->quotaMilitare,
                $n->democrazia,
                $n->influenzaTotale, $n->etica, $n->ambizione, $n->qualitaVita, $n->statoPolizia,
                $n->netPeace, $n->legittimita, $n->aspettativa, $n->clamoreSociale, $n->orientamento,
                $n->ansiaMilitare, $n->controlloInfo, $n->cyberDifesa,
                $n->soldati, $n->equipaggiamento, $n->potenzaGoverno(), $n->posturaNucleare,
                $n->forzaInsorti,
                $n->derivaPolitica, $n->integrita, $n->crescitaStrutturale, $n->pressioneEsterna,
                $n->reputazioneSporca, $n->consumoProCapitePrec, $n->azioniInVolo,
                $n->cambiEsecutivo, $n->cambiIrregolari, $n->vittorieInsorti, $n->scandaliSubiti,
                $n->annoUltimoCambio, $n->prossimaElezione, $n->mandatoTick);
        }
        if ($blocchi === []) {
            return;
        }
        $this->db->esegui(
            'REPLACE INTO sdb_nazione_stato (nazione_id, tick, ' . implode(', ', $campi) . ') VALUES '
            . implode(', ', $blocchi),
            $valori,
        );
    }

    private function salvaMondo(Mondo $mondo, int $tick, string $dataGioco): void
    {
        $this->db->esegui(
            'REPLACE INTO sdb_mondo_stato (tick, data_gioco, livello_pace, nastiness) VALUES (?,?,?,?)',
            [$tick, $dataGioco, $mondo->livelloPace, $mondo->nastiness],
        );
    }

    private function salvaRelazioni(Mondo $mondo): void
    {
        $blocchi = [];
        $valori = [];
        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            [$a, $b] = explode('|', $chiave);
            $ia = $this->idPerIso[$a] ?? null;
            $ib = $this->idPerIso[$b] ?? null;
            if ($ia === null || $ib === null) {
                continue;
            }
            $blocchi[] = '(?,?,?,?,?,?,?,?,?,?)';
            array_push($valori, $ia, $ib, $r->umore, $r->affinita, $r->obbligo,
                $r->sfera, $r->spintaSfera, $mondo->tick, $r->ancora ?? $r->affinita, $r->obbligoFirmato);
            if (count($blocchi) >= 800) {
                $this->scaricaRelazioni($blocchi, $valori);
            }
        }
        $this->scaricaRelazioni($blocchi, $valori);
    }

    /** @param list<string> $blocchi @param list<mixed> $valori */
    private function scaricaRelazioni(array &$blocchi, array &$valori): void
    {
        if ($blocchi === []) {
            return;
        }
        $this->db->esegui(
            'REPLACE INTO sdb_relazione
                (da_nazione_id, a_nazione_id, umore, affinita, obbligo, sfera, spinta_sfera, aggiornata_tick,
                 ancora, obbligo_firmato)
             VALUES ' . implode(', ', $blocchi),
            $valori,
        );
        $blocchi = [];
        $valori = [];
    }

    private function salvaEventi(Mondo $mondo): void
    {
        foreach ($mondo->eventi as $e) {
            if (isset($this->eventiScritti[$e->id])) {
                $this->db->esegui('UPDATE sdb_evento SET stato = ? WHERE id = ?', [$e->stato, $e->id]);
                continue;
            }
            $this->db->esegui(
                'INSERT INTO sdb_evento
                    (id, dominio, verbo, mandante_id, bersaglio_id, intensita, copertura,
                     impronta, creato_tick, maturazione_tick, danno_base, attribuzione_vera,
                     stato, falsa_bandiera_id, qualita_falso)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE stato = VALUES(stato)',
                [
                    $e->id, $e->dominio, $e->verbo,
                    $this->idPerIso[$e->mandante] ?? 0, $this->idPerIso[$e->bersaglio] ?? null,
                    (int) round($e->intensita * 100), (int) round($e->copertura * 100),
                    (int) round($e->impronta * 100), $e->creatoTick, $e->maturazioneTick,
                    (int) $e->dannoBase, (int) round($e->attribuzioneVera * 100), $e->stato,
                    $e->falsaBandiera !== null ? ($this->idPerIso[$e->falsaBandiera] ?? null) : null,
                    $e->qualitaFalso,
                ],
            );
            $this->eventiScritti[$e->id] = true;
        }
    }

    private function salvaPalazzo(Mondo $mondo): void
    {
        foreach ($mondo->gabinetti as $iso => $g) {
            $id = $this->idPerIso[$iso] ?? null;
            if ($id === null) {
                continue;
            }
            $this->db->esegui(
                'REPLACE INTO sdb_gabinetto (nazione_id, coesione, ultimo_rimpasto) VALUES (?,?,?)',
                [$id, $g->coesione, $g->ultimoRimpasto],
            );
            foreach ($g->poltrone as $ruolo => $p) {
                // Quando al posto arriva un altro ministro (insediato_tick
                // cambia), la talpa e il sospetto restano col vecchio: prima
                // il nuovo li ereditava, e un ministro appena nominato era una
                // spia straniera senza aver mai accettato niente. ATTENZIONE
                // all'ordine: MariaDB assegna da sinistra a destra, e i tre IF
                // devono vedere il VECCHIO insediato_tick — che quindi si
                // aggiorna per ultimo.
                $this->db->esegui(
                    'INSERT INTO sdb_poltrona
                        (nazione_id, ruolo, nome, eta, etica, ambizione, competenza,
                         vulnerabilita, potere, lealta, insediato_tick)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE
                        reclutata_da   = IF(VALUES(insediato_tick) <> insediato_tick, NULL, reclutata_da),
                        reclutata_tick = IF(VALUES(insediato_tick) <> insediato_tick, NULL, reclutata_tick),
                        sospettata     = IF(VALUES(insediato_tick) <> insediato_tick, 0, sospettata),
                        nome = VALUES(nome), eta = VALUES(eta), etica = VALUES(etica),
                        ambizione = VALUES(ambizione), competenza = VALUES(competenza),
                        vulnerabilita = VALUES(vulnerabilita), potere = VALUES(potere),
                        lealta = VALUES(lealta), insediato_tick = VALUES(insediato_tick)',
                    [
                        $id, $ruolo, $p->titolare->nome, $p->titolare->eta, $p->titolare->etica,
                        $p->titolare->ambizione, $p->titolare->competenza,
                        implode('; ', $p->titolare->vulnerabilita), $p->potere, $p->lealta,
                        $p->insediatoTick,
                    ],
                );
            }
            $this->db->esegui('DELETE FROM sdb_fazione WHERE nazione_id = ?', [$id]);
            foreach ($g->fazioni as $f) {
                $this->db->esegui(
                    'INSERT INTO sdb_fazione (nazione_id, nome, forza, favore, agenda) VALUES (?,?,?,?,?)',
                    [$id, $f->nome, $f->forza, $f->favore, $f->agenda],
                );
            }
        }
    }

    /**
     * I rubinetti chiusi. Si riscrivono per intero a ogni giro: sono pochi, e
     * l'elenco in memoria e' l'unica verita' — la fase 03 ci ha gia' tolto
     * quelli scaduti.
     */
    private function salvaStrozzature(Mondo $mondo): void
    {
        $this->db->esegui('DELETE FROM sdb_strozzatura');
        foreach ($mondo->strozzature as $st) {
            $f = $this->idPerIso[$st['fornitore']] ?? null;
            $k = $this->idPerIso[$st['cliente']] ?? null;
            if ($f === null || $k === null) {
                continue;
            }
            $this->db->esegui(
                'INSERT INTO sdb_strozzatura (fornitore_id, cliente_id, quota, dal_tick, al_tick)
                 VALUES (?,?,?,?,?)',
                [$f, $k, (int) round(100.0 * (float) $st['quota']),
                 (int) ($st['dal'] ?? 0), (int) $st['fine']]);
        }
    }

    private function ripristinaStrozzature(Mondo $mondo): void
    {
        $mondo->strozzature = [];
        foreach ($this->db->esegui('SELECT * FROM sdb_strozzatura')->fetchAll() as $r) {
            $f = $this->isoPerId[(int) $r['fornitore_id']] ?? null;
            $k = $this->isoPerId[(int) $r['cliente_id']] ?? null;
            if ($f === null || $k === null) {
                continue;
            }
            $mondo->strozzature[] = [
                'fornitore' => $f,
                'cliente'   => $k,
                'quota'     => ((int) $r['quota']) / 100.0,
                'dal'       => (int) $r['dal_tick'],
                'fine'      => (int) $r['al_tick'],
            ];
        }
    }

    private function salvaGuerre(Mondo $mondo, int $tick): void
    {
        foreach ($mondo->guerre as $g) {
            $ia = $this->idPerIso[$g['aggressore']] ?? null;
            $id = $this->idPerIso[$g['difensore']] ?? null;
            if ($ia === null || $id === null) {
                continue;
            }
            $this->db->esegui(
                'INSERT INTO sdb_guerra (aggressore_id, difensore_id, inizio_tick, morti)
                 SELECT ?,?,?,? FROM DUAL WHERE NOT EXISTS (
                    SELECT 1 FROM sdb_guerra WHERE aggressore_id = ? AND difensore_id = ?
                       AND inizio_tick = ? )',
                [$ia, $id, $g['inizio'], (float) $g['morti'], $ia, $id, $g['inizio']],
            );
        }
        // Le guerre non piu' in elenco sono finite. Quelle nate sul sito
        // dopo l'ultimo tick (una crisi portata al nono gradino) hanno
        // inizio_tick = tick - 1 e non si toccano: le prende il tick dopo.
        $this->db->esegui(
            'UPDATE sdb_guerra SET fine_tick = ? WHERE fine_tick IS NULL AND inizio_tick < ?',
            [$tick, $tick - 1],
        );
        foreach ($mondo->guerre as $g) {
            $this->db->esegui(
                'UPDATE sdb_guerra SET fine_tick = NULL, morti = ?, aiuti_difensore = ?, aiuti_aggressore = ?
                 WHERE aggressore_id = ? AND difensore_id = ? AND inizio_tick = ?',
                [(float) $g['morti'], (float) ($g['aiuti_difensore'] ?? 0.0), (float) ($g['aiuti_aggressore'] ?? 0.0),
                 $this->idPerIso[$g['aggressore']] ?? 0, $this->idPerIso[$g['difensore']] ?? 0, $g['inizio']],
            );
        }
        // E come sono finite quelle chiuse in questo tick.
        foreach ($mondo->guerreConcluse as $g) {
            $this->db->esegui(
                'UPDATE sdb_guerra SET esito = ?
                 WHERE aggressore_id = ? AND difensore_id = ? AND inizio_tick = ?',
                [(string) $g['esito'], $this->idPerIso[$g['aggressore']] ?? 0,
                 $this->idPerIso[$g['difensore']] ?? 0, $g['inizio']],
            );
        }
    }

    private function salvaNotizie(Mondo $mondo): void
    {
        $nuove = array_slice($mondo->notizie, $this->notizieScritte);
        foreach ($nuove as $n) {
            $this->db->esegui(
                'INSERT INTO sdb_notizia (tick, genere, dati) VALUES (?,?,?)',
                [$n['tick'], $n['genere'], json_encode($n['dati'], JSON_UNESCAPED_UNICODE)],
            );
        }
        $this->notizieScritte = count($mondo->notizie);
    }

    private function salvaServizi(Mondo $mondo, int $tick): void
    {
        foreach ($mondo->intelligence->capacita as $iso => $discipline) {
            $id = $this->idPerIso[$iso] ?? null;
            if ($id === null) {
                continue;
            }
            foreach ($discipline as $d => $v) {
                $this->db->esegui(
                    'REPLACE INTO sdb_capacita_intel (nazione_id, disciplina, livello) VALUES (?,?,?)',
                    [$id, $d, $v],
                );
            }
        }

        // La presenza decade nel tempo: se non la si salva, a ogni ripartenza
        // torna ai valori del seme e il decadimento non esiste.
        foreach ($mondo->intelligence->presenza as $iso => $bersagli) {
            $id = $this->idPerIso[$iso] ?? null;
            if ($id === null) {
                continue;
            }
            $blocchi = [];
            $valori = [];
            foreach ($bersagli as $b => $v) {
                $ib = $this->idPerIso[$b] ?? null;
                if ($ib === null) {
                    continue;
                }
                $blocchi[] = '(?,?,?)';
                array_push($valori, $id, $ib, $v);
            }
            if ($blocchi !== []) {
                $this->db->esegui(
                    'REPLACE INTO sdb_presenza_intel (nazione_id, bersaglio_id, livello) VALUES '
                    . implode(', ', $blocchi), $valori);
            }
        }

        // La conoscenza: solo quella sugli eventi ancora in volo, che e' l'unica
        // che serve al modello. Il resto e' archivio e puo' restare com'e'.
        $idInVolo = [];
        foreach ($mondo->eventi as $e) {
            if ($e->inVolo()) {
                $idInVolo[$e->id] = true;
            }
        }
        foreach ($mondo->intelligence->conoscenza as $chiave => $livello) {
            [$iso, $idEvento] = explode('|', $chiave);
            if (!isset($idInVolo[(int) $idEvento])) {
                continue;
            }
            $osservatore = $this->idPerIso[$iso] ?? null;
            if ($osservatore === null) {
                continue;
            }
            $accusato = $mondo->intelligence->accusa[(int) $idEvento][$iso] ?? null;
            $this->db->esegui(
                'REPLACE INTO sdb_conoscenza
                    (osservatore_id, evento_id, livello, primo_tick, confidenza, accusato_id, aggiornata_tick)
                 VALUES (?,?,?,?,?,?,?)',
                [$osservatore, (int) $idEvento, $livello, $tick, 60,
                 $accusato !== null ? ($this->idPerIso[$accusato] ?? null) : null, $tick],
            );
        }
    }

    // --------------------------------------------------------------- rilegge

    /**
     * Riporta un mondo appena costruito dal seme allo stato salvato al tick
     * indicato. La topologia resta quella del seme: qui si sovrascrive solo
     * cio' che cambia nel tempo.
     */
    public function ripristina(Mondo $mondo, int $tick): bool
    {
        if ($this->idPerIso === []) {
            $this->caricaIdentificatori();
        }
        $stmt = $this->db->esegui('SELECT * FROM sdb_nazione_stato WHERE tick = ?', [$tick]);
        $righe = $stmt->fetchAll();
        if ($righe === []) {
            return false;
        }
        foreach ($righe as $r) {
            $iso = $this->isoPerId[(int) $r['nazione_id']] ?? null;
            $n = $iso !== null ? ($mondo->nazioni[$iso] ?? null) : null;
            if ($n === null) {
                continue;
            }
            $n->popolazione       = (float) $r['popolazione'];
            $n->pil               = (float) $r['pil'];
            $n->crescitaPil       = (float) $r['crescita_pil'];
            $n->pilProCapite      = (float) $r['pil_pro_capite'];
            $n->consumoProCapite  = (float) $r['consumo_pro_capite'];
            $n->quotaConsumi      = (float) $r['quota_consumi'];
            $n->quotaInvestimenti = (float) $r['quota_investimenti'];
            $n->quotaMilitare     = (float) $r['quota_militare'];
            // Le istituzioni si muovono e vanno rilette: se restassero al
            // valore del seme, ogni colpo di Stato verrebbe dimenticato.
            $n->democrazia        = (float) $r['democrazia'];
            $n->influenzaTotale   = (float) $r['influenza_totale'];
            $n->etica             = (int) $r['etica'];
            $n->ambizione         = (int) $r['ambizione'];
            $n->qualitaVita       = (int) $r['qualita_vita'];
            $n->statoPolizia      = (float) $r['stato_polizia'];
            $n->netPeace          = (int) $r['net_peace'];
            $n->legittimita       = (float) $r['legittimita'];
            $n->aspettativa       = (float) $r['aspettativa'];
            $n->clamoreSociale    = (float) $r['clamore_sociale'];
            $n->orientamento      = (int) $r['orientamento'];
            $n->ansiaMilitare     = (float) $r['ansia_militare'];
            $n->controlloInfo     = (float) $r['controllo_info'];
            $n->cyberDifesa       = (float) $r['cyber_difesa'];
            $n->soldati           = (float) $r['soldati'];
            $n->equipaggiamento   = (float) $r['equipaggiamento'];
            $n->posturaNucleare   = (int) $r['postura_nucleare'];
            $n->forzaInsorti      = (float) $r['forza_insorti'];
            // La memoria.
            $n->derivaPolitica      = (float) $r['deriva_politica'];
            $n->integrita           = (float) $r['integrita'];
            $n->crescitaStrutturale = (float) $r['crescita_strutturale'];
            $n->pressioneEsterna    = (float) $r['pressione_esterna'];
            $n->reputazioneSporca   = (float) $r['reputazione_sporca'];
            $n->consumoProCapitePrec = (float) $r['consumo_pro_capite_prec'];
            $n->azioniInVolo        = (int) $r['azioni_in_volo'];
            $n->cambiEsecutivo      = (int) $r['cambi_esecutivo'];
            $n->cambiIrregolari     = (int) $r['cambi_irregolari'];
            $n->vittorieInsorti     = (int) $r['vittorie_insorti'];
            $n->scandaliSubiti      = (int) $r['scandali_subiti'];
            $n->annoUltimoCambio    = (int) $r['anno_ultimo_cambio'];
            $n->prossimaElezione    = (int) $r['prossima_elezione'];
            $n->mandatoTick         = (int) $r['mandato_tick'];
        }

        $stmt = $this->db->esegui('SELECT * FROM sdb_relazione');
        foreach ($stmt->fetchAll() as $r) {
            $a = $this->isoPerId[(int) $r['da_nazione_id']] ?? null;
            $b = $this->isoPerId[(int) $r['a_nazione_id']] ?? null;
            if ($a === null || $b === null) {
                continue;
            }
            $rel = $mondo->relazioni->fra($a, $b);
            if ($rel === null) {
                continue;
            }
            $rel->umore    = (int) $r['umore'];
            $rel->affinita = (float) $r['affinita'];
            $rel->obbligo  = (int) $r['obbligo'];
            // L'ancora e il trattato sulla carta si rileggono. Prima c'era
            // `$rel->ancora ??= ...`, che non scattava mai perche' il seme
            // l'ancora la mette sempre: tornava al valore del seme a ogni tick.
            // Una riga con l'ancora a NULL non e' mai stata scritta dal codice
            // che la salva (e' anteriore alla migrazione 0029): li' valgono
            // ancora i valori del seme, obbligo firmato compreso — altrimenti
            // lo zero di una colonna appena nata passerebbe per una denuncia.
            if ($r['ancora'] !== null) {
                $rel->ancora         = (float) $r['ancora'];
                $rel->obbligoFirmato = (int) $r['obbligo_firmato'];
            }
            $rel->sfera       = (int) $r['sfera'];
            $rel->spintaSfera = (float) ($r['spinta_sfera'] ?? 0.0);
        }

        $this->ripristinaEventi($mondo);
        $this->ripristinaPalazzo($mondo);
        $this->ripristinaGuerre($mondo);
        $this->ripristinaStrozzature($mondo);
        $this->ripristinaServizi($mondo);

        $mondo->tick = $tick;
        $stmt = $this->db->esegui('SELECT * FROM sdb_mondo_stato WHERE tick = ?', [$tick]);
        $m = $stmt->fetch();
        if ($m !== false) {
            $mondo->livelloPace = (int) $m['livello_pace'];
            $mondo->nastiness   = (float) $m['nastiness'];
        }
        $mondo->azioniRecenti = [];
        foreach ($this->db->esegui('SELECT chiave, tick FROM sdb_memoria_azioni WHERE tick <= ?', [$tick])
                     ->fetchAll() as $x) {
            $mondo->azioniRecenti[(string) $x['chiave']] = (int) $x['tick'];
        }
        return true;
    }

    /**
     * Gli eventi ancora in volo, con la conoscenza che ciascuno ne ha.
     *
     * Senza questo, le operazioni decise restavano scritte in tabella e non
     * maturavano mai: il mondo le dimenticava a ogni tick e ne ordinava di
     * nuove, accumulando lavoro che nessuno avrebbe mai svolto.
     */
    private function ripristinaEventi(Mondo $mondo): void
    {
        $mondo->eventi = [];
        $righe = $this->db->esegui(
            "SELECT * FROM sdb_evento WHERE stato IN ('in_volo','scoperto') ORDER BY id",
        )->fetchAll();

        foreach ($righe as $r) {
            $mandante  = $this->isoPerId[(int) $r['mandante_id']] ?? null;
            $bersaglio = $this->isoPerId[(int) $r['bersaglio_id']] ?? null;
            if ($mandante === null || $bersaglio === null) {
                continue;
            }
            $mondo->eventi[] = new Evento(
                id:               (int) $r['id'],
                dominio:          (string) $r['dominio'],
                verbo:            (string) $r['verbo'],
                mandante:         $mandante,
                esecutore:        null,
                bersaglio:        $bersaglio,
                intensita:        ((int) $r['intensita']) / 100,
                copertura:        ((int) $r['copertura']) / 100,
                impronta:         ((int) $r['impronta']) / 100,
                creatoTick:       (int) $r['creato_tick'],
                maturazioneTick:  (int) $r['maturazione_tick'],
                dannoBase:        (float) $r['danno_base'],
                attribuzioneVera: ((int) $r['attribuzione_vera']) / 100,
                falsaBandiera:    $r['falsa_bandiera_id'] !== null
                                    ? ($this->isoPerId[(int) $r['falsa_bandiera_id']] ?? null) : null,
                qualitaFalso:     (float) $r['qualita_falso'],
                stato:            (string) $r['stato'],
            );
            $this->eventiScritti[(int) $r['id']] = true;
        }

        // Gli identificatori non devono ricominciare da uno, o si scontrano
        // con quelli gia' scritti.
        $massimo = (int) $this->db->esegui('SELECT COALESCE(MAX(id),0) FROM sdb_evento')->fetchColumn();
        $mondo->prossimoIdEvento = $massimo + 1;

        if ($mondo->eventi === []) {
            return;
        }
        $ids = implode(',', array_map(static fn(Evento $e): int => $e->id, $mondo->eventi));
        foreach ($this->db->esegui("SELECT * FROM sdb_conoscenza WHERE evento_id IN ($ids)")->fetchAll() as $r) {
            $iso = $this->isoPerId[(int) $r['osservatore_id']] ?? null;
            if ($iso === null) {
                continue;
            }
            $mondo->intelligence->imponiLivello($iso, (int) $r['evento_id'], (int) $r['livello']);
            if ($r['accusato_id'] !== null) {
                $accusato = $this->isoPerId[(int) $r['accusato_id']] ?? null;
                if ($accusato !== null) {
                    $mondo->intelligence->accusa[(int) $r['evento_id']][$iso] = $accusato;
                }
                if ((int) $r['livello'] >= 4) {
                    $mondo->intelligence->attribuito[(int) $r['evento_id']][] = $iso;
                }
            }
        }
    }

    /**
     * I gabinetti con le persone che li compongono.
     *
     * Senza questo il Capo di Francia cambiava identita' a ogni tick, perche'
     * la fase 10 ricreava da zero un gabinetto che non trovava.
     */
    private function ripristinaPalazzo(Mondo $mondo): void
    {
        $gabinetti = $this->db->esegui('SELECT * FROM sdb_gabinetto')->fetchAll();
        if ($gabinetti === []) {
            return;
        }
        foreach ($gabinetti as $r) {
            $iso = $this->isoPerId[(int) $r['nazione_id']] ?? null;
            if ($iso === null || !isset($mondo->nazioni[$iso])) {
                continue;
            }
            $g = new Gabinetto($iso);
            $g->coesione = (float) $r['coesione'];
            $g->ultimoRimpasto = (int) $r['ultimo_rimpasto'];
            $mondo->gabinetti[$iso] = $g;
        }

        foreach ($this->db->esegui('SELECT * FROM sdb_poltrona')->fetchAll() as $r) {
            $iso = $this->isoPerId[(int) $r['nazione_id']] ?? null;
            $g = $iso !== null ? ($mondo->gabinetti[$iso] ?? null) : null;
            if ($g === null) {
                continue;
            }
            $g->poltrone[(string) $r['ruolo']] = new Poltrona(
                ruolo: (string) $r['ruolo'],
                titolare: new Personaggio(
                    nome: (string) $r['nome'],
                    etica: (int) $r['etica'],
                    ambizione: (int) $r['ambizione'],
                    competenza: (float) $r['competenza'],
                    vulnerabilita: array_values(array_filter(array_map(
                        'trim', explode(';', (string) $r['vulnerabilita'])))),
                    eta: (int) $r['eta'],
                ),
                potere: (float) $r['potere'],
                lealta: (float) $r['lealta'],
                insediatoTick: (int) $r['insediato_tick'],
            );
        }

        foreach ($this->db->esegui('SELECT * FROM sdb_fazione ORDER BY id')->fetchAll() as $r) {
            $iso = $this->isoPerId[(int) $r['nazione_id']] ?? null;
            $g = $iso !== null ? ($mondo->gabinetti[$iso] ?? null) : null;
            if ($g === null) {
                continue;
            }
            $g->fazioni[] = new Fazione(
                nome: (string) $r['nome'],
                forza: (float) $r['forza'],
                favore: (float) $r['favore'],
                agenda: (string) $r['agenda'],
            );
        }
    }

    private function ripristinaGuerre(Mondo $mondo): void
    {
        $mondo->guerre = [];
        foreach ($this->db->esegui('SELECT * FROM sdb_guerra WHERE fine_tick IS NULL')->fetchAll() as $r) {
            $a = $this->isoPerId[(int) $r['aggressore_id']] ?? null;
            $d = $this->isoPerId[(int) $r['difensore_id']] ?? null;
            if ($a === null || $d === null) {
                continue;
            }
            $mondo->guerre[] = [
                'aggressore' => $a, 'difensore' => $d,
                'inizio' => (int) $r['inizio_tick'], 'morti' => (float) $r['morti'],
                'aiuti_difensore'  => (float) $r['aiuti_difensore'],
                'aiuti_aggressore' => (float) $r['aiuti_aggressore'],
            ];
        }
    }

    private function ripristinaServizi(Mondo $mondo): void
    {
        foreach ($this->db->esegui('SELECT * FROM sdb_capacita_intel')->fetchAll() as $r) {
            $iso = $this->isoPerId[(int) $r['nazione_id']] ?? null;
            if ($iso !== null) {
                $mondo->intelligence->capacita[$iso][(string) $r['disciplina']] = (float) $r['livello'];
            }
        }
        foreach ($this->db->esegui('SELECT * FROM sdb_presenza_intel')->fetchAll() as $r) {
            $a = $this->isoPerId[(int) $r['nazione_id']] ?? null;
            $b = $this->isoPerId[(int) $r['bersaglio_id']] ?? null;
            if ($a !== null && $b !== null) {
                $mondo->intelligence->presenza[$a][$b] = (float) $r['livello'];
            }
        }
    }

    /** @return array<string,string> */
    private function mappa(string $sql): array
    {
        $out = [];
        foreach ($this->db->esegui($sql)->fetchAll() as $r) {
            $v = array_values($r);
            $out[(string) $v[0]] = (string) $v[1];
        }
        return $out;
    }
}
