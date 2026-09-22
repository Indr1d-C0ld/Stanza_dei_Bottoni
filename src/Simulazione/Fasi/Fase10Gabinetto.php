<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Dati\Gabinetti;
use App\Dati\Gabinetto;
use App\Dati\Nazione;
use App\Dati\Poltrona;
use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 10 — Politica di gabinetto.
 *
 * LEGGE:      le poltrone, le fazioni, gli esiti del tick
 * SCRIVE:     scadenze elettorali, cambi regolari di esecutivo
 * INVARIANTE: il potere di gabinetto è a somma limitata dentro un governo
 *
 * Con i giocatori qui vivranno ranking, rimpasti, sfiducie e il Panel dei
 * mandanti. Senza giocatori, la parte che conta già adesso sono **le elezioni a
 * calendario** — il meccanismo che mancava e che teneva i cambi regolari a un
 * terzo del riferimento storico.
 *
 * La distinzione è quella di Crawford: il *cambio regolare* di esecutivo usa le
 * procedure riconosciute, quello *irregolare* una pallottola. Il primo è di
 * gran lunga il più comune, e ha un tasso di riuscita dell'80% contro il 44%.
 */
final class Fase10Gabinetto implements Fase
{
    public function codice(): string { return '10'; }
    public function nome(): string   { return 'Politica di gabinetto'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $tickAnno = (int) $c->calibrazione->numero('tempo.tick_per_anno', 52.0);
        $sogliaElettorale = $c->calibrazione->numero('elezioni.democrazia_minima', 0.15);
        $pendenza = $c->calibrazione->numero('elezioni.pendenza', 9.0);
        $centro   = $c->calibrazione->numero('elezioni.centro', 52.0);
        $rischioCrisi = $c->calibrazione->numero('elezioni.rischio_crisi_anno', 0.9);

        $consultazioni = 0;
        $ricambi = 0;

        // --- i gabinetti delle potenze giocabili --------------------------
        $bacini = $this->bacini();
        $caduti = [];
        foreach ($c->giornale() as $voce) {
            if (in_array($voce['genere'], ['rivoluzione', 'colpo_di_stato', 'cambio_governo'], true)) {
                $caduti[(string) $voce['dati']['nazione']] = true;
            }
        }

        $dimissioni = 0;
        foreach ($mondo->elenco() as $n) {
            if (!$n->giocabile) {
                continue;
            }
            if (!isset($mondo->gabinetti[$n->iso3])) {
                $mondo->gabinetti[$n->iso3] = Gabinetti::perNazione($n, $bacini, $c->caso, $c->tick);
                continue;
            }
            $dimissioni += $this->muoviGabinetto($mondo->gabinetti[$n->iso3], $n, $c, $bacini,
                isset($caduti[$n->nome]));
        }

        foreach ($mondo->elenco() as $n) {
            // Chi ha istituzioni elettorali vere vota; gli altri no, e per
            // loro resta soltanto la via irregolare della fase 05.
            //
            // QUI C'ERA `maturita`, che e' marcata SEGNAPOSTO e si ricava da
            // reddito e alfabetizzazione. Il risultato era che in questo mondo
            // andavano alle urne la Cina, Cuba, la Bielorussia, il Brunei e
            // gli Emirati — ricchi e alfabetizzati — mentre non ci andavano il
            // Ghana, Capo Verde, la Giamaica e lo Sri Lanka, che sono
            // democrazie vere e meno ricche. Quaranta paesi sul lato sbagliato.
            //
            // Adesso decide l'indice di democrazia liberale di V-Dem, che e'
            // la misura di questa cosa esatta.
            $elettorale = $n->democrazia >= $sogliaElettorale && $n->statoPolizia <= 3;

            if (!$elettorale) {
                $n->prossimaElezione = 0;
                continue;
            }

            if ($n->prossimaElezione === 0) {
                // Prima assegnazione: mandati fra quattro e cinque anni, sfasati
                // fra loro — il mondo non vota tutto lo stesso giorno.
                $n->mandatoTick = (int) round($tickAnno * (4.0 + $c->caso->frazione('10_mandato', crc32($n->iso3), 0)));
                $n->prossimaElezione = $c->tick
                    + (int) round($n->mandatoTick * $c->caso->frazione('10_sfasamento', crc32($n->iso3), 1));
                continue;
            }

            if ($c->tick < $n->prossimaElezione) {
                // Fra un voto e l'altro un esecutivo puo' cadere lo stesso, per
                // via ordinaria: sfiducia, crisi di coalizione, dimissioni. E'
                // questo che il riferimento storico conta come "cambio
                // regolare", ed e' piu' frequente delle elezioni stesse.
                //
                // QUI C'ERA UN ANCORAGGIO SBAGLIATO DUE VOLTE. Diceva: «nei
                // trent'anni del World Handbook la Francia ne registra 61 e
                // l'Italia 41 di soli tentativi falliti». Datato, perche' il
                // World Handbook copre il 1948-77; e non rappresentativo,
                // perche' quella Francia e' la Quarta Repubblica e quell'Italia
                // e' la Prima — le due democrazie piu' instabili del dopoguerra
                // europeo, prese come metro per tutti e per sempre. Il mondo
                // che ne usciva aveva governi da 3,3 anni di media.
                //
                // Il metro giusto e' la forchetta contemporanea: 4-8 anni nelle
                // democrazie competitive, decenni nei sistemi autoritari.
                $rischio = $rischioCrisi / (1.0 + exp(($n->legittimita - 42.0) / 7.0));
                if (($c->tick - $n->annoUltimoCambio) > ($tickAnno / 2)
                    && $c->caso->prova('10_sfiducia', crc32($n->iso3), $c->tick, $rischio / $tickAnno)) {
                    $this->ricambio($n, $c, 'sfiducia');
                    $ricambi++;
                }
                continue;
            }

            $consultazioni++;
            $n->prossimaElezione = $c->tick + $n->mandatoTick;

            // Chi governa male perde. La curva è centrata poco sopra la media:
            // a legittimità 40 l'uscente cade quasi sempre, a 65 quasi mai.
            $probabilitaRicambio = 1.0 / (1.0 + exp(($n->legittimita - $centro) / $pendenza));
            if (!$c->caso->prova('10_urne', crc32($n->iso3), $c->tick, $probabilitaRicambio)) {
                // Riconfermato: un mandato fresco vale qualche punto di credito.
                $n->legittimita = min(100.0, $n->legittimita + 3.0);
                continue;
            }

            $this->ricambio($n, $c, 'alternanza');
            $ricambi++;
        }

        $agende = $this->verificaAgende($c);
        $risposte = $this->valutaOfferte($c);

        return new EsitoFase([
            'agende_chiuse' => $agende,
            'offerte'       => $risposte,
            'consultazioni' => $consultazioni,
            'ricambi'       => $ricambi,
            'dimissioni'    => $dimissioni,
        ]);
    }

    /** Le agende private si verificano da sole: e' il loro requisito. */
    private function verificaAgende(ContestoTick $c): int
    {
        if ($c->db === null || $c->aVuoto) {
            return 0;
        }
        $catalogo = @include dirname(__DIR__, 3) . '/calibrazione/agende.php';
        if (!is_array($catalogo)) {
            return 0;
        }
        return (new \App\Gioco\Agende($c->db, $catalogo))->verifica($c->tick);
    }

    /**
     * Le poltrone in mano all'apparato rispondono alle offerte di reclutamento.
     *
     * Non basta essere corruttibili: bisogna avere un motivo. Un funzionario
     * ambizioso che conta poco, in un governo che detesta chi gli sta offrendo,
     * e' molto piu' comprabile di uno soddisfatto — e uno scrupoloso denuncia.
     */
    private function valutaOfferte(ContestoTick $c): int
    {
        if ($c->db === null || $c->aVuoto) {
            return 0;
        }
        $offerte = $c->db->esegui(
            'SELECT o.*, p.ruolo, p.giocatore_id, p.lealta, p.potere,
                    p.etica, p.ambizione, ospite.codice AS ospite, chi.codice AS proponente
             FROM sdb_offerta o
             JOIN sdb_poltrona p ON p.id = o.a_poltrona
             JOIN sdb_nazione ospite ON ospite.id = p.nazione_id
             JOIN sdb_nazione chi ON chi.id = o.da_nazione_id
             WHERE o.stato = "aperta"')->fetchAll();

        $risposte = 0;
        foreach ($offerte as $o) {
            if ($o['giocatore_id'] !== null) {
                // Decide una persona: si aspetta. Ma non per sempre.
                if ((int) $o['scade_tick'] < $c->tick) {
                    $c->db->esegui('UPDATE sdb_offerta SET stato = "scaduta" WHERE id = ?', [(int) $o['id']]);
                    $risposte++;
                }
                continue;
            }

            $gab = $c->mondo->gabinetti[$o['ospite']] ?? null;
            $poltrona = $gab?->poltrone[(string) $o['ruolo']] ?? null;
            if ($poltrona === null) {
                continue;
            }
            $rel = $c->mondo->relazioni->fra((string) $o['ospite'], (string) $o['proponente']);
            $affinita = $rel?->affinita ?? 0.0;

            $sulPiatto = 0.10 * ((int) $o['offre_denaro'] + (int) $o['offre_dossier'] + (int) $o['offre_appoggio']);
            $propensione = 0.02
                + 0.10 * ($poltrona->titolare->etica - 1) / 5.0
                + 0.10 * ($poltrona->titolare->ambizione - 3) / 3.0
                + max(0.0, (55.0 - $poltrona->lealta) / 180.0)
                + max(0.0, (40.0 - $poltrona->potere) / 200.0)
                + $sulPiatto
                + max(0.0, $affinita / 400.0);   // si tradisce piu' volentieri per un amico

            $seme = (int) $o['id'];
            if ($c->caso->prova('10_reclutato', $seme, $c->tick, min(0.6, $propensione))) {
                $c->db->esegui('UPDATE sdb_offerta SET stato = "accettata", risposta_tick = ? WHERE id = ?',
                    [$c->tick, (int) $o['id']]);
                $c->db->esegui(
                    'UPDATE sdb_poltrona SET reclutata_da = ?, reclutata_tick = ?, lealta = lealta * 0.4
                     WHERE id = ?', [(int) $o['da_nazione_id'], $c->tick, (int) $o['a_poltrona']]);
                $c->annota('reclutamento_riuscito', [
                    'paese' => $c->mondo->nazioni[$o['ospite']]->nome ?? $o['ospite'],
                    'ruolo' => Gabinetto::RUOLI[$o['ruolo']] ?? (string) $o['ruolo'],
                ]);
            } elseif ($poltrona->titolare->etica <= 2
                && $c->caso->prova('10_denuncia', $seme, $c->tick, 0.45)) {
                $c->db->esegui('UPDATE sdb_offerta SET stato = "denunciata", risposta_tick = ? WHERE id = ?',
                    [$c->tick, (int) $o['id']]);
                $r = $c->mondo->relazioni->fra((string) $o['ospite'], (string) $o['proponente']);
                if ($r !== null) {
                    $r->ancora = max(-127.0, ($r->ancora ?? $r->affinita) - 25.0);
                    $r->affinita = max(-127.0, $r->affinita - 25.0);
                    $r->aggiornaUmore();
                }
                $c->annota('reclutamento_denunciato', [
                    'paese'    => $c->mondo->nazioni[$o['ospite']]->nome ?? $o['ospite'],
                    'accusa'   => $c->mondo->nazioni[$o['proponente']]->nome ?? $o['proponente'],
                    'poltrona' => Gabinetto::RUOLI[$o['ruolo']] ?? (string) $o['ruolo'],
                ]);
            } else {
                $c->db->esegui('UPDATE sdb_offerta SET stato = "rifiutata", risposta_tick = ? WHERE id = ?',
                    [$c->tick, (int) $o['id']]);
            }
            $risposte++;
        }
        return $risposte;
    }

    /**
     * La vita del gabinetto in un tick: chi sale, chi scende, chi se ne va.
     *
     * @param array<string,array{nomi:list<string>,cognomi:list<string>}> $bacini
     */
    private function muoviGabinetto(Gabinetto $g, Nazione $n, ContestoTick $c,
        array $bacini, bool $caduto): int
    {
        $perTick = 1.0 / $c->calibrazione->numero('tempo.tick_per_anno', 52.0);

        // Se il capo e' caduto, cade con lui buona parte della squadra: si
        // salva chi era abbastanza forte o abbastanza utile da restare.
        if ($caduto) {
            $nuovo = Gabinetti::perNazione($n, $bacini, $c->caso, $c->tick);
            $i = 0;
            foreach ($g->poltrone as $ruolo => $p) {
                $i++;
                if ($ruolo !== 'capo' && $p->potere > 70.0
                    && $c->caso->prova('gab_continuita', crc32($n->iso3), $c->tick + $i, 0.45)) {
                    $nuovo->poltrone[$ruolo] = $p;   // la continuita' dell'apparato
                }
            }
            $g->poltrone = $nuovo->poltrone;
            $g->fazioni  = $nuovo->fazioni;
            $g->coesione = $nuovo->coesione;
            $g->ultimoRimpasto = $c->tick;
            return 0;
        }

        $dimissioni = 0;
        $capo = $g->poltrone['capo'];

        foreach ($g->poltrone as $ruolo => $p) {
            if ($ruolo === 'capo') {
                continue;
            }
            // Il potere di una poltrona segue la competenza di chi la occupa e
            // il favore delle fazioni, e non puo' superare quello del capo
            // senza che il capo se ne accorga.
            $obiettivo = 25.0 + 55.0 * $p->titolare->competenza
                + 0.2 * $this->favoreMedio($g)
                + ($p->titolare->ambizione - 3) * 4.0;
            $p->potere += ($obiettivo - $p->potere) * 0.5 * $perTick;
            $p->potere = max(0.0, min(100.0, $p->potere));

            // La lealta' si consuma quando si e' ambiziosi e si conta poco.
            $p->lealta += ((70.0 - ($p->titolare->ambizione - 3) * 12.0 + $p->potere * 0.2) - $p->lealta)
                * 0.4 * $perTick;

            // Chi e' ambizioso e conta niente se ne va sbattendo la porta.
            // La soglia e' RELATIVA al capo, non assoluta. Era «potere < 18»,
            // ma il potere di una poltrona non scende mai sotto il trentanove:
            // la condizione non esisteva nell'intervallo che la variabile
            // occupa davvero, e nessuno si e' mai dimesso in quindici anni.
            if ($p->potere < $capo->potere * 0.62 && $p->titolare->ambizione >= 5
                && $c->caso->prova('gab_dimissioni', crc32($n->iso3 . $ruolo), $c->tick, 0.9 * $perTick)) {
                $c->annota('dimissioni', [
                    'nazione' => $n->nome,
                    'chi'     => $p->titolare->nome,
                    'ruolo'   => Gabinetto::RUOLI[$ruolo],
                ]);
                $g->poltrone[$ruolo] = new Poltrona(
                    ruolo: $ruolo,
                    titolare: Gabinetti::personaggio($n, $bacini, $c->caso, crc32($n->iso3), $c->tick % 97, $c->tick),
                    potere: 30.0,
                    lealta: 75.0,
                    insediatoTick: $c->tick,
                );
                $g->coesione = max(0.0, $g->coesione - 7.0);
                $dimissioni++;
            }
        }

        // Il potere del capo segue la sua legittimita' nel paese.
        $capo->potere += (($n->legittimita * 0.8 + 25.0) - $capo->potere) * 0.5 * $perTick;

        // --- le fazioni -------------------------------------------------
        // Una fazione non giudica il capo dai sondaggi: lo giudica da quanto
        // ottiene. Far dipendere il favore solo dalla legittimita' le rendeva
        // tutte contente quando il paese andava bene, e allora non servivano a
        // niente. Metà del giudizio viene dall'agenda.
        foreach ($g->fazioni as $f) {
            $generale = ($n->legittimita - 48.0) * 0.9 + $n->crescitaPil * 250.0
                - ($n->netPeace >= 5 ? 20.0 : 0.0);
            $obiettivo = $generale * 0.5 + 60.0 * $this->agendaSoddisfatta($f, $n, $g);
            $f->favore += ($obiettivo - $f->favore) * 0.35 * $perTick;
            $f->favore = max(-100.0, min(100.0, $f->favore));
        }

        // La coesione soffre quando le poltrone sono squilibrate.
        $poteri = array_map(static fn(Poltrona $p): float => $p->potere, $g->poltrone);
        $scarto = max($poteri) - min($poteri);
        $g->coesione += ((85.0 - $scarto * 0.6) - $g->coesione) * 0.25 * $perTick;
        $g->coesione = max(0.0, min(100.0, $g->coesione));

        return $dimissioni;
    }

    /**
     * Quanto la fazione sta ottenendo quel che vuole, da -1 a +1.
     *
     * È qui che il Panel smette di essere una decorazione: chi ti ha messo al
     * potere ha un'idea di cosa dovresti fare, e se non la fai smette di
     * proteggerti. Una fazione la cui agenda è «sostituire il capo» è ostile
     * per definizione, e prima o poi trova il modo.
     */
    private function agendaSoddisfatta(\App\Dati\Fazione $f, Nazione $n, Gabinetto $g): float
    {
        return match ($f->agenda) {
            'contenere la spesa militare'          => 1.0 - $n->quotaMilitare * 14.0,
            'riarmare a qualunque costo'           => $n->quotaMilitare * 14.0 - 1.0,
            'chiudere la stampa critica'           => ($n->statoPolizia - 2) * 0.8,
            'liberalizzare l\'economia'            => ($n->quotaInvestimenti - 0.18) * 8.0,
            'proteggere i propri interessi economici' => $n->crescitaPil * 40.0 - 0.3,
            'restare fuori da ogni guerra'         => $n->netPeace >= 5 ? -1.0 : 0.6,
            'riprendersi il territorio perduto'    => $n->netPeace >= 5 ? 0.5 : -0.5,
            'aprire al blocco avverso'             => -abs($n->orientamento) / 90.0 + 0.4,
            'sostituire il capo'                   => -1.0,
            'conservare le cose come stanno'       => ($c = $n->cambiEsecutivo) > 2 ? -0.6 : 0.5,
            default                                => 0.0,
        };
    }

    private function favoreMedio(Gabinetto $g): float
    {
        $t = 0.0;
        foreach ($g->fazioni as $f) {
            $t += $f->favore;
        }
        return $g->fazioni === [] ? 0.0 : $t / count($g->fazioni);
    }

    /** @return array<string,array{nomi:list<string>,cognomi:list<string>}> */
    private function bacini(): array
    {
        static $bacini = null;
        if ($bacini === null) {
            $bacini = require dirname(__DIR__, 3) . '/db/seed/nomi-personaggi.php';
        }
        return $bacini;
    }

    /** Alternanza per via ordinaria: cambia chi governa, non come si governa. */
    private function ricambio(Nazione $n, ContestoTick $c, string $esito): void
    {
        // Anche qui una nuova squadra è gente diversa con fortuna diversa, ma
        // l'alternanza ordinaria è meno traumatica di un colpo di stato: la
        // deriva si riscrive con ampiezza minore.
        $n->derivaPolitica = $c->caso->rumore('10_nuovogoverno', crc32($n->iso3), $c->tick, 9.0);
        // La luna di miele esiste ma e' corta: se ogni ricambio regalasse
        // dieci punti, con tre ricambi per paese in quindici anni il mondo
        // diventerebbe lentamente sempre piu' contento di se'. E cosi' era.
        $n->legittimita = (50.0 + $n->derivaPolitica) + 4.0
            + $c->caso->rumore('10_luna_di_miele', crc32($n->iso3), $c->tick, 4.0);
        $n->clamoreSociale *= 0.55;
        $n->cambiEsecutivo++;
        $n->annoUltimoCambio = $c->tick;
        // L'alternanza pacifica non erode le istituzioni: le conferma.
        $n->maturita = min(255, $n->maturita + 1);
        $c->annota('elezione', ['nazione' => $n->nome, 'esito' => $esito]);
        // Un voto e' pubblico per costruzione: non passa dal vaglio
        // dell'attribuzione della fase 09, che del resto ha gia' chiuso il
        // giornale quando questa fase gira.
        $c->mondo->notizie[] = ['tick' => $c->tick, 'genere' => 'elezione',
            'dati' => ['nazione' => $n->nome, 'esito' => $esito]];
    }
}
