<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Dati\Evento;
use App\Dati\Nazione;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 00 — Chiusura degli ordini.
 *
 * LEGGE:      gli ordini impartiti fra un tick e l'altro; a vuoto, la dottrina
 * SCRIVE:     nuovi eventi in volo
 * INVARIANTE: ogni ordine produce UNO e un solo evento, con la sua lista di
 *             accesso — che sarà l'insieme dei sospetti se trapela
 *
 * Con i giocatori, qui si raccolgono i loro ordini. Senza giocatori, decide la
 * dottrina: una macchina semplice che guarda con chi ha a che fare e sceglie il
 * verbo. Non deve essere astuta — deve essere *leggibile*, perché quando il
 * mondo farà qualcosa di strano dovremo poter risalire al perché.
 */
final class Fase00Chiusura implements Fase
{
    public function codice(): string { return '00'; }
    public function nome(): string   { return 'Chiusura degli ordini'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $verbi   = $c->calibrazione->leggi('verbi', []);
        if (!is_array($verbi) || $verbi === []) {
            return new EsitoFase(nota: 'catalogo dei verbi assente', saltata: true);
        }
        $perTick = 1.0 / $c->calibrazione->numero('tempo.tick_per_anno', 52.0);
        $attivita = $c->calibrazione->numero('dottrina.attivita', 0.5);
        $inVoloMax = (int) $c->calibrazione->numero('dottrina.azioni_in_volo_max', 4);

        $nuovi = 0;
        $perDominio = [];

        // --- le poltrone in mano all'apparato decidono se firmare ------------
        $this->valutaControfirme($c);

        // --- poi gli ordini dei giocatori -----------------------------------
        // Qui la volonta' di una persona entra nel modello. Dove c'e' un ordine
        // umano la dottrina tace: e' il gabinetto a decidere, non l'apparato.
        $presidiate = [];
        foreach ($this->ordiniDeiGiocatori($c) as $ordine) {
            $n = $mondo->nazioni[$ordine['iso']] ?? null;
            $b = $mondo->nazioni[$ordine['bersaglio']] ?? null;
            $d = $verbi[$ordine['verbo']] ?? null;
            if ($n === null || $b === null || $d === null) {
                continue;
            }
            $evento = $this->confeziona($n, $b->iso3, $ordine['verbo'], $d,
                $ordine['intensita'], $ordine['copertura'], $mondo, $c);
            $mondo->eventi[] = $evento;
            $n->azioniInVolo++;
            $presidiate[$n->iso3] = true;
            $nuovi++;
            $perDominio[$evento->dominio] = ($perDominio[$evento->dominio] ?? 0) + 1;
            $c->annota('ordine_eseguito', [
                'nazione' => $n->nome, 'verbo' => $ordine['verbo'], 'bersaglio' => $b->nome,
            ]);
        }

        // Chi siede davvero, e dove. La dottrina governa «solo le nazioni non
        // presidiate» (calibrazione, blocco `dottrina`), ma finora una nazione
        // con un giocatore che QUESTA settimana non aveva dato ordini veniva
        // governata dalla macchina — invasioni e colpi di Stato compresi. Il
        // Capo copre ogni dominio; gli altri ministri il proprio. Si usa la
        // stessa definizione di «presidiata» del resto del motore.
        $umani = [];
        if ($c->db !== null) {
            foreach ($c->db->esegui(
                'SELECT n.codice, p.ruolo FROM sdb_poltrona p JOIN sdb_nazione n ON n.id = p.nazione_id
                 WHERE ' . \App\Gioco\Delega::sqlPresidiata('p', $c->tick))->fetchAll() as $r) {
                $umani[(string) $r['codice']][(string) $r['ruolo']] = true;
            }
        }

        foreach ($mondo->elenco() as $n) {
            if (isset($presidiate[$n->iso3]) || isset($umani[$n->iso3]['capo'])) {
                continue;   // la governa una persona
            }
            if ($n->azioniInVolo >= $inVoloMax) {
                continue;
            }
            // Chi conta agisce spesso, chi non conta quasi mai. L'ambizione
            // moltiplica: un paese piccolo e spregiudicato si muove piu' di un
            // paese grande e quieto.
            $frequenzaAnnua = $attivita * (0.25 + $n->influenzaTotale * 0.9) * (0.5 + $n->ambizione * 0.25);
            if (!$c->caso->prova('00_agisce', crc32($n->iso3), $c->tick, $frequenzaAnnua * $perTick)) {
                continue;
            }

            $scelta = $this->decidi($n, $c, $verbi);
            if ($scelta === null) {
                continue;
            }
            [$verbo, $bersaglio, $intensita] = $scelta;
            $d = $verbi[$verbo];
            // Il dominio di quella mossa ha un ministro in carne e ossa: tocca
            // a lui, non alla macchina.
            $ruoloDelDominio = \App\Dati\Gabinetto::DOMINIO_DI[(string) ($d['dominio'] ?? '')] ?? null;
            if ($ruoloDelDominio !== null && isset($umani[$n->iso3][$ruoloDelDominio])) {
                continue;
            }

            // Ripetere la stessa mossa sullo stesso bersaglio richiede tempo:
            // nessuno emette una condanna solenne ogni settimana.
            $memoria = $n->iso3 . '|' . $verbo . '|' . $bersaglio;
            $attesa  = (int) ($d['attesa'] ?? 26);
            if (($c->tick - ($mondo->azioniRecenti[$memoria] ?? -9999)) < $attesa) {
                continue;
            }
            $mondo->azioniRecenti[$memoria] = $c->tick;

            $durata = $c->caso->intero('00_durata', crc32($n->iso3 . $verbo), $c->tick,
                (int) $d['maturazione'][0], (int) $d['maturazione'][1]);

            // La copertura vale solo dove c'e' davvero qualcosa da coprire.
            // Nessuno "occulta" un emissario — ne' un investimento estero, che
            // per definizione deve vedersi: la soglia va sul valore di
            // catalogo, non su quello gia' ridotto.
            $occultabile = (float) $d['impronta'] <= 0.60;
            $copertura = $occultabile ? min(0.9, 0.15 * $n->etica) : 0.0;

            // Il gabinetto non e' decorazione: chi ha in mano quel dominio
            // decide quanto l'operazione riesce, e un gabinetto diviso perde
            // pezzi per strada prima ancora di cominciare.
            $gab = $mondo->gabinetti[$n->iso3] ?? null;
            if ($gab !== null) {
                $competenza = $gab->competenzaDominio((string) $d['dominio']);
                $intensita  = min(1.0, $intensita * (0.55 + 0.9 * $competenza));
                // Un gabinetto poco coeso fa rumore: le operazioni coperte
                // trapelano prima ancora di maturare.
                $perdite = 1.0 + max(0.0, (60.0 - $gab->coesione) / 160.0);
            } else {
                $perdite = 1.0;
            }

            // --- la falsa bandiera -------------------------------------
            // Si tenta solo su operazioni davvero coperte, e solo se si ha il
            // mestiere per confezionare il falso. Il capro espiatorio dev'essere
            // credibile: qualcuno che il bersaglio ha gia' ragione di temere.
            $falsaBandiera = null;
            $qualitaFalso = 0.0;
            if ($occultabile && $n->etica >= 4) {
                $abilita = max(
                    $mondo->intelligence->capacita[$n->iso3]['cyber'] ?? 0.0,
                    $mondo->intelligence->capacita[$n->iso3]['humint'] ?? 0.0,
                );
                if ($abilita > 0.45
                    && $c->caso->prova('00_falsabandiera', crc32($n->iso3), $c->tick, 0.30 * $abilita)) {
                    $falsaBandiera = $this->caproEspiatorio($n, $bersaglio, $mondo, $c);
                    if ($falsaBandiera !== null) {
                        $qualitaFalso = min(0.95, 0.35 + 0.6 * $abilita);
                    }
                }
            }

            $evento = new Evento(
                id:              $mondo->prossimoIdEvento++,
                dominio:         (string) $d['dominio'],
                verbo:           $verbo,
                mandante:        $n->iso3,
                esecutore:       null,
                bersaglio:       $bersaglio,
                intensita:       self::centesimi($intensita),
                copertura:       self::centesimi($copertura),
                impronta:        self::centesimi(min(1.0, max(0.05, (float) $d['impronta'] * (1.0 - $copertura * 0.6) * $perdite))),
                creatoTick:      $c->tick,
                maturazioneTick: $c->tick + max(1, $durata),
                dannoBase:       (float) (int) $d['danno'],
                attribuzioneVera: self::centesimi((float) $d['attribuzione'] * (1.0 - $copertura * 0.5)),
                falsaBandiera:   $falsaBandiera,
                qualitaFalso:    round($qualitaFalso, 3),
            );

            $mondo->eventi[] = $evento;
            $n->azioniInVolo++;
            $nuovi++;
            $perDominio[$evento->dominio] = ($perDominio[$evento->dominio] ?? 0) + 1;
        }

        return new EsitoFase(['nuovi' => $nuovi] + $perDominio);
    }

    /**
     * Le controfirme delle poltrone non presidiate.
     *
     * Con un gabinetto pieno di giocatori questa funzione non serve: firmano
     * loro. Ma quasi mai un gabinetto sara' pieno, e senza questo un Ministro
     * dell'Intelligence umano non potrebbe fare assolutamente nulla, perche'
     * ogni sua operazione richiede la firma del Capo.
     *
     * L'apparato non firma a caso: guarda se la mossa ha senso per il paese e
     * quanto e' disposto a sporcarsi le mani. Un Capo scrupoloso rifiutera' le
     * operazioni coperte anche quando converrebbero, ed e' esattamente il
     * genere di attrito che rende interessante avere dei colleghi.
     */
    private function valutaControfirme(ContestoTick $c): void
    {
        if ($c->db === null || $c->aVuoto) {
            return;
        }
        $mondo = $c->mondo;
        $righe = $c->db->esegui(
            'SELECT o.id, o.verbo, o.intensita, o.richiede_controfirma,
                    n.codice AS iso, b.codice AS bersaglio,
                    ' . \App\Gioco\Delega::sqlPresidiata('p', $c->tick) . ' AS firmatario_umano
             FROM sdb_ordine o
             JOIN sdb_nazione n ON n.id = o.nazione_id
             JOIN sdb_nazione b ON b.id = o.bersaglio_id
             LEFT JOIN sdb_poltrona p ON p.nazione_id = o.nazione_id AND p.ruolo = o.richiede_controfirma
             WHERE o.stato = "in_attesa"',
        )->fetchAll();

        $verbi = $c->calibrazione->leggi('verbi', []);

        foreach ($righe as $r) {
            // La firma spetta a una persona solo se quella persona c'e'. Prima
            // bastava che la poltrona avesse un titolare: un controfirmatario
            // sparito da settimane bloccava ogni ordine fino alla scadenza, che
            // e' esattamente il problema che docs/20 dice risolto.
            if ((int) $r['firmatario_umano'] === 1) {
                continue;   // la firma spetta a una persona presente: aspetta lei
            }
            $n = $mondo->nazioni[$r['iso']] ?? null;
            $b = $mondo->nazioni[$r['bersaglio']] ?? null;
            $d = $verbi[$r['verbo']] ?? null;
            $gab = $mondo->gabinetti[$r['iso']] ?? null;
            if ($n === null || $b === null || $d === null || $gab === null) {
                continue;
            }
            $poltrona = $gab->poltrone[(string) $r['richiede_controfirma']] ?? null;
            if ($poltrona === null) {
                continue;
            }

            $rel = $mondo->relazioni->fra($n->iso3, $b->iso3);
            $affinita = $rel?->affinita ?? 0.0;
            $ostile = (float) $d['danno'] > 0;

            // Ha senso? Si colpisce chi non si ama, si aiuta chi si ama.
            $coerenza = $ostile ? (-$affinita / 127.0) : ($affinita / 127.0);
            // Quanto e' disposto a sporcarsi le mani chi deve firmare.
            $scrupoli = ((float) $d['impronta'] < 0.6)
                ? ($poltrona->titolare->etica - 1) / 5.0
                : 1.0;
            $probabilita = max(0.03, min(0.95,
                0.35 + 0.45 * $coerenza + 0.25 * ($scrupoli - 0.5)
                     + ($poltrona->lealta - 60.0) / 300.0));

            $firma = $c->caso->prova('00_controfirma', (int) $r['id'], $c->tick, $probabilita);
            $c->db->esegui('UPDATE sdb_ordine SET stato = ?, controfirmato_il = NOW() WHERE id = ?',
                [$firma ? 'firmato' : 'annullato', (int) $r['id']]);
            $c->annota($firma ? 'controfirma' : 'firma_negata', [
                'nazione' => $n->nome,
                'chi'     => $poltrona->titolare->nome,
                'verbo'   => (string) $r['verbo'],
                'contro'  => $b->nome,
            ]);
        }
    }

    /**
     * Gli ordini firmati che aspettano di partire.
     *
     * @return list<array{iso:string,bersaglio:string,verbo:string,intensita:float,copertura:float}>
     */
    private function ordiniDeiGiocatori(ContestoTick $c): array
    {
        if ($c->db === null || $c->aVuoto) {
            return [];
        }
        $righe = $c->db->esegui(
            'SELECT o.id, o.verbo, o.intensita, o.copertura, n.codice AS iso, b.codice AS bersaglio
             FROM sdb_ordine o
             JOIN sdb_nazione n ON n.id = o.nazione_id
             JOIN sdb_nazione b ON b.id = o.bersaglio_id
             WHERE o.stato = "firmato" ORDER BY o.id',
        )->fetchAll();

        $ordini = [];
        foreach ($righe as $r) {
            $ordini[] = [
                'iso'       => (string) $r['iso'],
                'bersaglio' => (string) $r['bersaglio'],
                'verbo'     => (string) $r['verbo'],
                'intensita' => ((int) $r['intensita']) / 100,
                'copertura' => ((int) $r['copertura']) / 100,
            ];
            $c->db->esegui('UPDATE sdb_ordine SET stato = "eseguito" WHERE id = ?', [(int) $r['id']]);
        }
        // Gli ordini non controfirmati in tempo decadono: una decisione non
        // presa e' comunque una decisione.
        $c->db->esegui(
            'UPDATE sdb_ordine SET stato = "scaduto" WHERE stato = "in_attesa" AND scade_tick < ?',
            [$c->tick]);

        return $ordini;
    }

    /**
     * Un evento vive su piu' tick, e fra un tick e l'altro sta nella base dati
     * in centesimi (sdb_evento). Nasce gia' in centesimi, allora: altrimenti il
     * tick in cui nasce lo usa con tutte le cifre e i successivi arrotondato,
     * e il mondo vivo si separa da quello misurato (tests/13). Gli ordini dei
     * giocatori sono in centesimi per costruzione: e' la dottrina che non lo era.
     */
    private static function centesimi(float $x): float
    {
        return round($x * 100.0) / 100.0;
    }

    /**
     * Confeziona un evento a partire da una scelta gia' fatta — che venga da un
     * giocatore o dalla dottrina, il procedimento e' lo stesso.
     *
     * @param array<string,mixed> $d
     */
    private function confeziona($n, string $bersaglio, string $verbo, array $d,
        float $intensita, float $copertura, $mondo, ContestoTick $c): \App\Dati\Evento
    {
        $durata = $c->caso->intero('00_durata', crc32($n->iso3 . $verbo), $c->tick,
            (int) $d['maturazione'][0], (int) $d['maturazione'][1]);

        $gab = $mondo->gabinetti[$n->iso3] ?? null;
        $perdite = 1.0;
        if ($gab !== null) {
            $intensita = min(1.0, $intensita * (0.55 + 0.9 * $gab->competenzaDominio((string) $d['dominio'])));
            $perdite = 1.0 + max(0.0, (60.0 - $gab->coesione) / 160.0);
        }
        $occultabile = (float) $d['impronta'] <= 0.60;
        $copertura = $occultabile ? $copertura : 0.0;

        return new \App\Dati\Evento(
            id:              $mondo->prossimoIdEvento++,
            dominio:         (string) $d['dominio'],
            verbo:           $verbo,
            mandante:        $n->iso3,
            esecutore:       null,
            bersaglio:       $bersaglio,
            intensita:       self::centesimi($intensita),
            copertura:       self::centesimi($copertura),
            impronta:        self::centesimi(min(1.0, max(0.05, (float) $d['impronta'] * (1.0 - $copertura * 0.6) * $perdite))),
            creatoTick:      $c->tick,
            maturazioneTick: $c->tick + max(1, $durata),
            dannoBase:       (float) (int) $d['danno'],
            attribuzioneVera: self::centesimi((float) $d['attribuzione'] * (1.0 - $copertura * 0.5)),
        );
    }

    /**
     * La dottrina: dato uno Stato, cosa fa e a chi.
     *
     * @param array<string,array<string,mixed>> $verbi
     * @return array{0:string,1:string,2:float}|null
     */
    private function decidi(Nazione $n, ContestoTick $c, array $verbi): ?array
    {
        $mondo = $c->mondo;
        $candidati = [];
        $pesoGuerraVicini = $c->calibrazione->numero('dottrina.peso_guerra_vicini', 2.4);

        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            [$da, $verso] = explode('|', $chiave);
            if ($da !== $n->iso3) {
                continue;
            }
            $b = $mondo->nazioni[$verso] ?? null;
            if ($b === null) {
                continue;
            }

            $ostile  = $r->affinita < -35.0;
            $amico   = $r->affinita > 45.0;
            // «Fragile» comprende anche chi ha un'insurrezione in casa: e'
            // esattamente il momento in cui si manda aiuto e si vendono armi a
            // un amico, e senza quel segno la condizione restava quasi vuota —
            // aiuto_economico e vendita_armi non uscivano mai in quindici anni.
            $fragile = $b->legittimita < 45.0 || $b->netPeace >= 4 || $b->haInsorti();
            // L'etica non vieta: prezza. Uno Stato scrupoloso ricorre al
            // lavoro sporco di rado e solo quando la posta e' alta; uno
            // spregiudicato lo tratta come uno strumento fra gli altri.
            $sporco = (0.15 + 0.30 * ($n->etica - 1)) * (0.5 + $n->ambizione / 4.0);

            // Contro un rivale in difficolta': si spinge dove gia' cede.
            if ($ostile && $fragile) {
                $candidati['disinformazione'] = 3.0 * $sporco;
                $candidati['finanziamento_opposizione'] = 2.5 * $sporco;
                $candidati['destabilizzare'] = 2.2 * $sporco;
                $candidati['armare_insorti'] = ($b->netPeace >= 3 ? 3.5 : 1.0) * $sporco;
                $candidati['sabotaggio'] = 1.5 * $sporco;
                if ($n->ambizione >= 4) {
                    $candidati['colpo_di_stato'] = 1.0 * $sporco;
                }
            }
            // Anche contro un rivale solido si lavora nell'ombra, se si e'
            // abbastanza grandi da poterselo permettere.
            if ($ostile && !$fragile && $n->influenzaTotale > 1.5) {
                $candidati['disinformazione'] = 1.6 * $sporco;
                $candidati['sabotaggio'] = 0.8 * $sporco;
            }
            // L'ultimo gradino. Serve odio dichiarato, una superiorita'
            // militare netta, la possibilita' di arrivarci — e un governo
            // abbastanza spregiudicato da provarci. Resta una mossa rara.
            // Due vincoli che il modello non aveva e senza i quali produceva
            // gli Stati Uniti che conquistano la Russia:
            //
            //   la DETERRENZA — chi ha l'arma non si invade, punto;
            //   la PROIEZIONE — per invadere bisogna poterci arrivare, cioe'
            //   confinare o avere un vicino del bersaglio che ti apra la porta
            //   (e' la regola logistica di Balance of Power).
            $invadibile = $b->posturaNucleare < (int) $c->calibrazione->numero('nucleare.soglia_armato', 4);
            // Una grande potenza puo' proiettare forza anche nella propria
            // regione senza confinare: e' il caso delle flotte.
            $raggiungibile = $r->confinanti
                || $this->haUnaTestaDiPonte($n, $b, $mondo)
                || ($n->influenzaTotale > 8.0 && $n->regione === $b->regione);

            $giaInGuerra = false;
            foreach ($mondo->guerre as $gg) {
                if (in_array($n->iso3, [$gg['aggressore'], $gg['difensore']], true)
                    && in_array($b->iso3, [$gg['aggressore'], $gg['difensore']], true)) {
                    $giaInGuerra = true;
                }
            }

            // La DETERRENZA ESTESA: non si invade nemmeno chi sta sotto
            // l'ombrello nucleare di un garante (obbligo 128, che Mondo
            // assegna ai patti di difesa con uno Stato armato). Prima contava
            // solo l'arsenale del bersaglio, e i baltici erano invadibili.
            // (Le due verifiche costano: si fanno solo quando tutto il resto
            // dice gia' di si'.)
            if ($invadibile && $raggiungibile && !$giaInGuerra
                && $r->affinita < -70.0
                && $n->potenzaGoverno() > $b->potenzaGoverno() * 1.5
                && !$this->sottoOmbrello($b->iso3, $mondo, $c->tick)
                && !$this->haUnGaranteForte($n, $b, $mondo)) {
                // Piu' il bersaglio e' debole in casa propria, piu' la
                // tentazione cresce: si invade chi sembra gia' mezzo caduto.
                $tentazione = $fragile ? 2.0 : 1.0;
                if ($r->confinanti) {
                    // LA GUERRA FRA VICINI RIVALI. E' la forma piu' comune di
                    // guerra fra Stati: la maggior parte nasce da una disputa
                    // territoriale fra confinanti (Vasquez, «The War Puzzle»,
                    // 1993; Senese e Vasquez 2008) dentro una rivalita' di lunga
                    // durata (Diehl e Goertz, «War and Peace in International
                    // Rivalry», 2000), e la iniziano piu' spesso le autocrazie.
                    // Prima serviva l'ambizione di una grande potenza e
                    // un'etica estratta da un numero a caso: l'Azerbaigian, che
                    // ha attaccato l'Armenia nel 2020 e nel 2023, non poteva.
                    // Il peso: tarato perche' il mondo faccia qualche guerra
                    // fra vicini in quindici anni, come il 2010-25 vero
                    // (Russia-Ucraina, Azerbaigian-Armenia due volte,
                    // India-Pakistan, Thailandia-Cambogia, Israele-Iran).
                    $candidati['invasione'] = $pesoGuerraVicini * (0.25 + 0.75 * (1.0 - $n->democrazia)) * $tentazione;
                    $candidati['dimostrazione_forza'] = 1.5;
                } elseif ($n->ambizione >= 4 && $n->etica >= 4) {
                    // Oltre i confini: la proiezione di una grande potenza.
                    $candidati['invasione'] = 0.8 * $tentazione;
                    $candidati['dimostrazione_forza'] = 1.5;
                }
            }

            // Contro un rivale solido: strumenti dichiarati.
            if ($ostile && !$fragile) {
                $candidati['condanna_pubblica'] = 2.0;
                $candidati['restrizioni_commerciali'] = 1.8;
                if ($n->influenzaTotale > 3.0) {
                    $candidati['embargo'] = 0.8;
                    // max: chi prepara un'invasione (sopra, 1,5) non deve
                    // vedersi ridurre la dimostrazione di forza a 0,3.
                    $candidati['dimostrazione_forza'] = max($candidati['dimostrazione_forza'] ?? 0.0,
                        $n->ambizione >= 5 ? 1.0 : 0.3);
                }
                // Il colpo mirato: era nel catalogo, aveva il suo effetto nella
                // fase 02, e nessuna riga della dottrina lo proponeva — restava
                // inarrivabile per l'apparato.
                //
                // Il primo tentativo lo legava all'ansia militare, e non
                // usciva lo stesso: l'ansia alta ce l'hanno i paesi in guerra,
                // che sono piccoli e deboli, non quelli con eserciti forti e
                // nemici profondi. Il movente di un colpo mirato non e' la
                // paura: e' l'ostilita' piu' la possibilita' di permetterselo.
                if ($r->affinita < -55.0 && $n->ambizione >= 4
                    && $n->equipaggiamento > $b->equipaggiamento * 1.5) {
                    $candidati['strike'] = $n->ambizione >= 5 ? 0.9 : 0.35;
                }
            }
            // Un amico nei guai si tiene in piedi: e' l'unico modo di salvare
            // un cliente, ed e' anche cio' che protegge la propria integrita'.
            if ($amico && $fragile) {
                $candidati['aiuto_economico'] = 4.0;
                $candidati['vendita_armi'] = $b->netPeace >= 4 ? 3.0 : 1.2;
            }
            // Un rapporto piu' caldo del trattato che lo regge chiede di essere
            // formalizzato meglio.
            //
            // La condizione era «affinita' alta E obbligo sotto 64», e reggeva
            // finche' gli obblighi si deducevano dall'affinita' stessa: c'era
            // sempre qualche coppia calda e slegata. Adesso i trattati vengono
            // dal Correlates of War, e le coppie calde un patto ce l'hanno
            // gia' — cosi' il verbo «trattato» non usciva PIU' MAI, e la
            // diplomazia perdeva il suo atto piu' semplice.
            //
            // Quel che conta non e' se un trattato esiste: e' se e' all'altezza
            // del rapporto. Ci si lega di piu' con chi si e' avvicinati.
            $gradinoMeritato = match (true) {
                $r->affinita > 100.0 => 96,
                $r->affinita >  70.0 => 64,
                default              => 0,
            };
            if ($gradinoMeritato > $r->obbligo) {
                $candidati['trattato'] = 2.0;
            }
            // Guerra altrui, e noi non siamo schierati: c'e' prestigio da fare.
            if ($b->netPeace >= 5 && abs($r->affinita) < 40.0 && $n->influenzaTotale > 2.0) {
                $candidati['mediazione'] = 1.5;
            }
            // Il programma d'arma si fa in casa propria, non «verso» qualcuno: si
            // propone una volta sola, quando c'e' un vicino armato e ostile e i mezzi
            // per provarci. E' l'unica azione coperta del dominio nucleare, e l'unica
            // ragione per cui le immagini dall'alto servono a qualcosa.
            $armato = (int) $c->calibrazione->numero('nucleare.soglia_armato', 4);
            if ($n->posturaNucleare < $armato && $r->affinita < -45.0
                && ($r->confinanti || $b->posturaNucleare >= $armato)
                && $n->pilProCapite > 9000.0) {   // `maturita` era qui accanto:
                // e' il reddito travestito, e il reddito c'e' gia'.
                $candidati['programma_nucleare'] = 0.6;
            }

            // Terreno neutro: si coltiva.
            if (!$ostile && !$amico) {
                $candidati['emissario'] = 0.8;
                $candidati['investimenti'] = $b->pilProCapite < 20000 ? 1.0 : 0.4;
            }

            if ($candidati === []) {
                continue;
            }

            // Una sola coppia (verbo, bersaglio) per volta, estratta col peso.
            $verbo = $this->estrai($candidati, $c, crc32($n->iso3 . $verso));
            $candidati = [];
            if ($verbo === null || !isset($verbi[$verbo])) {
                continue;
            }
            // Il bersaglio piu' interessante e' quello che conta di piu' e con
            // cui il rapporto e' piu' intenso, in un senso o nell'altro.
            $peso = (abs($r->affinita) / 127.0 + 0.2) * (0.5 + $b->valorePrestigio / 600.0);

            // La mediazione chiede indifferenza — |affinita| sotto quaranta —
            // e il peso qui sopra premia l'intensita' del rapporto: due regole
            // scritte in momenti diversi che si combattevano. Su 483 occasioni
            // la mediazione ne sopravviveva 6, e in quindici anni non e' mai
            // uscita. Ma una guerra altrui che si puo' mediare E' interessante,
            // a prescindere da come guardiamo i belligeranti: e' prestigio da
            // fare. Il peso deve dirlo.
            if ($verbo === 'mediazione') {
                $peso = max($peso, 0.55 + $b->valorePrestigio / 400.0);
            }

            $scelte[] = [$verbo, $verso, $peso];
        }

        if (empty($scelte)) {
            return null;
        }
        usort($scelte, static fn(array $x, array $y): int => $y[2] <=> $x[2]);
        // Non sempre la mossa piu' ovvia: si pesca fra le prime.
        $quante = min(5, count($scelte));
        $i = $c->caso->intero('00_bersaglio', crc32($n->iso3), $c->tick, 0, $quante - 1);
        [$verbo, $bersaglio] = $scelte[$i];

        $intensita = 0.3 + 0.7 * $c->caso->frazione('00_intensita', crc32($n->iso3), $c->tick)
            * ($n->ambizione / 6.0);

        return [$verbo, $bersaglio, min(1.0, $intensita)];
    }

    /**
     * Un capro espiatorio credibile: qualcuno che il bersaglio già teme, e che
     * non è amico nostro — incolpare un proprio alleato sarebbe autolesionista.
     */
    private function caproEspiatorio($n, string $bersaglio, $mondo, ContestoTick $c): ?string
    {
        $candidati = [];
        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            [$da, $verso] = explode('|', $chiave);
            if ($da !== $bersaglio || $verso === $n->iso3) {
                continue;
            }
            if ($r->affinita > -30.0) {
                continue;   // il bersaglio non ci crederebbe mai
            }
            $mio = $mondo->relazioni->fra($n->iso3, $verso);
            if ($mio !== null && $mio->affinita > 40.0) {
                continue;   // non si incolpa un amico
            }
            $candidati[] = $verso;
        }
        if ($candidati === []) {
            return null;
        }
        sort($candidati);
        return $candidati[$c->caso->intero('00_capro', crc32($n->iso3 . $bersaglio), $c->tick, 0, count($candidati) - 1)];
    }

    /**
     * C'è un vicino del bersaglio disposto a farci passare? È la condizione
     * logistica di Crawford: senza truppe già basate in un paese confinante,
     * una potenza può mandare soltanto una forza simbolica.
     */
    /** @var array<string,true> chi e' sotto un ombrello nucleare, per il tick in $ombrelliDelTick */
    private array $ombrelli = [];
    private int $ombrelliDelTick = PHP_INT_MIN;

    /** Qualcuno di armato ha promesso di difenderlo con l'atomica? */
    private function sottoOmbrello(string $iso, $mondo, int $tick): bool
    {
        if ($this->ombrelliDelTick !== $tick) {
            $this->ombrelli = [];
            foreach ($mondo->relazioni->tutte() as $chiave => $r) {
                if ($r->obbligo >= 128) {
                    $this->ombrelli[explode('|', $chiave)[1]] = true;
                }
            }
            $this->ombrelliDelTick = $tick;
        }
        return isset($this->ombrelli[$iso]);
    }

    /**
     * La deterrenza convenzionale estesa (Huth, «Extended Deterrence and the
     * Prevention of War», 1988): si evita di attaccare chi ha un garante
     * impegnato — basi o difesa, obbligo >= 64 — e piu' forte di chi attacca.
     * Il garante che c'e' davvero, sul posto, e' cio' che trattiene.
     */
    private function haUnGaranteForte($n, $b, $mondo): bool
    {
        $forza = $n->potenzaGoverno();
        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            if ($r->obbligo < 64) {
                continue;
            }
            [$garante, $protetto] = explode('|', $chiave);
            if ($protetto !== $b->iso3 || $garante === $n->iso3) {
                continue;
            }
            $g = $mondo->nazioni[$garante] ?? null;
            if ($g !== null && $g->potenzaGoverno() >= $forza) {
                return true;
            }
        }
        return false;
    }

    private function haUnaTestaDiPonte($n, $b, $mondo): bool
    {
        foreach ($mondo->relazioni->vicinato[$b->iso3] ?? [] as $vicino) {
            if ($vicino === $n->iso3) {
                return true;
            }
            $r = $mondo->relazioni->fra($n->iso3, $vicino);
            if ($r !== null && $r->affinita > 65.0 && $r->obbligo >= 64) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string,float> $pesi */
    private function estrai(array $pesi, ContestoTick $c, int $seme): ?string
    {
        $totale = array_sum($pesi);
        if ($totale <= 0.0) {
            return null;
        }
        $tiro = $c->caso->frazione('00_verbo', $seme, $c->tick) * $totale;
        foreach ($pesi as $verbo => $peso) {
            $tiro -= $peso;
            if ($tiro <= 0.0) {
                return $verbo;
            }
        }
        return array_key_first($pesi);
    }
}
