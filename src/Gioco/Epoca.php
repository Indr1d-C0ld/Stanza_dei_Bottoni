<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * L'epoca: il tratto di storia che si conta, e alla fine del quale si scopre
 * tutto.
 *
 * Una partita persistente senza fine non e' una partita, e' un acquario. La
 * chiusura d'epoca fa due cose, e la seconda vale piu' della prima.
 *
 * **Conta.** Quanto hai servito l'interesse del tuo paese, quante delle tue
 * agende private hai portato a casa, e quanto e' costato al paese averti
 * seduto li'. Le tre voci non tirano nella stessa direzione: e' voluto.
 *
 * **Rivela.** Ogni operazione coperta col suo vero mandante, ogni talpa col suo
 * padrone, ogni messaggio riscritto accanto al suo originale, ogni crisi con
 * quello che c'era dietro. Per un'epoca intera il gioco e' stato fatto di cose
 * non dette; quando si dicono tutte insieme si capisce finalmente che partita
 * si stava giocando — ed e' questo, non il punteggio, il momento per cui vale
 * la pena arrivare in fondo.
 */
final class Epoca
{
    public function __construct(private readonly Basedati $db) {}

    public function corrente(): ?array
    {
        $r = $this->db->esegui('SELECT * FROM sdb_epoca WHERE stato = "in_corso" ORDER BY numero DESC LIMIT 1')
            ->fetch();
        return $r === false ? null : $r;
    }

    /** @return list<array<string,mixed>> */
    public function chiuse(): array
    {
        return $this->db->esegui('SELECT * FROM sdb_epoca WHERE stato = "chiusa" ORDER BY numero DESC')
            ->fetchAll();
    }

    public function apri(string $titolo, int $inizio, int $durata): array
    {
        if ($this->corrente() !== null) {
            return [false, 'C\'è già un\'epoca in corso: va chiusa prima.'];
        }
        $numero = 1 + (int) $this->db->esegui('SELECT COALESCE(MAX(numero), 0) FROM sdb_epoca')->fetchColumn();
        $this->db->esegui(
            'INSERT INTO sdb_epoca (numero, titolo, inizio_tick, durata_tick) VALUES (?,?,?,?)',
            [$numero, $titolo, $inizio, $durata]);
        return [true, sprintf('Epoca %d aperta: «%s», %d giri da qui.', $numero, $titolo, $durata)];
    }

    /** Quanti tick mancano alla fine, o null se non c'è un'epoca aperta. */
    public function restano(int $tick): ?int
    {
        $e = $this->corrente();
        return $e === null ? null : max(0, (int) $e['inizio_tick'] + (int) $e['durata_tick'] - $tick);
    }

    // ------------------------------------------------------------ la chiusura

    /** @return array{0:bool,1:string} */
    public function chiudi(int $tick): array
    {
        $e = $this->corrente();
        if ($e === null) {
            return [false, 'Non c\'è nessuna epoca da chiudere.'];
        }
        $id = (int) $e['id'];
        $da = (int) $e['inizio_tick'];

        // Prima di contare, si chiudono le agende rimaste aperte: le difensive
        // arrivate fin qui sono riuscite, le altre no. Senza questo passo le
        // agende difensive non potevano mai riuscire.
        $catalogo = @include dirname(__DIR__, 2) . '/calibrazione/agende.php';
        if (is_array($catalogo)) {
            (new Agende($this->db, $catalogo))->chiudiEpoca($tick);
        }

        $quanti = $this->conta($id, $da, $tick);
        $rivelati = $this->rivela($id, $da, $tick);

        $this->db->esegui('UPDATE sdb_epoca SET stato = "chiusa", fine_tick = ? WHERE id = ?', [$tick, $id]);

        return [true, sprintf(
            'Epoca %d chiusa. %d bilanci, e %d cose che nessuno sapeva.',
            (int) $e['numero'], $quanti, $rivelati)];
    }

    /**
     * Il conto, giocatore per giocatore.
     *
     * Le tre voci non tirano nella stessa direzione, ed e' il punto: si puo'
     * servire benissimo il proprio paese e fallire ogni agenda, o portarne a
     * casa tre lasciando il paese a pezzi. Il totale non dice chi ha giocato
     * meglio: dice che partita ha giocato.
     */
    private function conta(int $epoca, int $da, int $a): int
    {
        $poltrone = $this->db->esegui(
            'SELECT p.id, p.giocatore_id, p.nazione_id, p.ruolo, p.reclutata_da,
                    n.codice, n.nome AS nazione, g.nome AS giocatore
             FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id
             JOIN sdb_giocatore g ON g.id = p.giocatore_id
             WHERE p.giocatore_id IS NOT NULL')->fetchAll();

        $quanti = 0;
        foreach ($poltrone as $p) {
            $voci  = $this->interesseNazionale((int) $p['nazione_id'], $da, $a);
            $voci += $this->agende((int) $p['giocatore_id'], $da, $a);
            $voci += $this->ilConto((int) $p['nazione_id'], (int) $p['id'], $da, $a);

            if ($p['reclutata_da'] !== null) {
                // Chi ha lavorato per un altro paese non viene punito qui: la
                // sua partita si giudica su quel che ha ottenuto per chi lo
                // pagava, e lo dira' la rivelazione. Qui resta solo agli atti.
                $voci['ha servito un altro padrone'] = 0.0;
            }

            $totale = array_sum($voci);
            $this->db->esegui(
                'INSERT INTO sdb_punteggio (epoca_id, giocatore_id, poltrona_id, nazione_id, voci, totale)
                 VALUES (?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE voci = VALUES(voci), totale = VALUES(totale)',
                [$epoca, (int) $p['giocatore_id'], (int) $p['id'], (int) $p['nazione_id'],
                 json_encode($voci, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
                 round($totale, 2)]);
            $quanti++;
        }
        return $quanti;
    }

    /** @return array<string,float> */
    private function interesseNazionale(int $nazione, int $da, int $a): array
    {
        $prima = $this->stato($nazione, $da);
        $dopo  = $this->stato($nazione, $a);
        if ($prima === null || $dopo === null) {
            return [];
        }
        // Le scale sono diverse fra loro — l'influenza e' in percentuale di
        // mondo, la legittimita' su 100, l'integrita' su 128 — quindi ogni
        // voce ha il suo moltiplicatore, scelto perche' pesino all'incirca
        // uguale quando si muovono di quanto ci si aspetta in un'epoca.
        return [
            'influenza guadagnata nel mondo' =>
                round(((float) $dopo['influenza_totale'] - (float) $prima['influenza_totale']) * 12.0, 2),
            'benessere di chi ci vive' =>
                round((((float) $dopo['pil_pro_capite'] / max(1.0, (float) $prima['pil_pro_capite'])) - 1.0) * 60.0, 2),
            'legittimità del governo' =>
                round(((float) $dopo['legittimita'] - (float) $prima['legittimita']) * 0.5, 2),
            'parola mantenuta nel mondo' =>
                round(((float) $dopo['integrita'] - (float) $prima['integrita']) * 0.4, 2),
        ];
    }

    /** @return array<string,float> */
    /**
     * Le agende private, pesate come dice il catalogo.
     *
     * Due correzioni dell'audit di settembre 2026. Si contano solo le agende
     * chiuse DENTRO quest'epoca: prima la query non aveva filtri di tempo, e
     * un'agenda vinta nella prima epoca fruttava venticinque punti anche nella
     * seconda, nella terza, e cosi' via. E ogni agenda pesa il suo `peso` —
     * «quanto conta nel bilancio finale», dice il catalogo — che prima veniva
     * letto e ignorato: tutte valevano venticinque.
     *
     * @return array<string,float>
     */
    private function agende(int $giocatore, int $da, int $a): array
    {
        $catalogo = @include dirname(__DIR__, 2) . '/calibrazione/agende.php';
        $catalogo = is_array($catalogo) ? $catalogo : [];
        $righe = $this->db->esegui(
            'SELECT codice, stato FROM sdb_agenda
             WHERE giocatore_id = ? AND stato IN ("riuscita","fallita") AND chiusa_tick BETWEEN ? AND ?',
            [$giocatore, $da, $a])->fetchAll();
        $vinte = 0.0;
        $perse = 0.0;
        foreach ($righe as $r) {
            $peso = (float) ($catalogo[(string) $r['codice']]['peso'] ?? 3);
            if ($r['stato'] === 'riuscita') {
                $vinte += 8.0 * $peso;      // peso 3 -> 24, peso 4 -> 32
            } else {
                $perse -= 2.0 * $peso;      // peso 3 -> -6, peso 4 -> -8
            }
        }
        return [
            'agende private portate a casa' => $vinte,
            'agende private fallite'        => $perse,
        ];
    }

    /** @return array<string,float> */
    private function ilConto(int $nazione, int $poltrona, int $da, int $a): array
    {
        $guerre = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_guerra
             WHERE inizio_tick BETWEEN ? AND ? AND (aggressore_id = ? OR difensore_id = ?)',
            [$da, $a, $nazione, $nazione])->fetchColumn();

        $rese = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_crisi
             WHERE ultimo_tick BETWEEN ? AND ?
               AND ((stato = "ceduto_sfidante" AND sfidante_id = ?)
                 OR (stato = "ceduto_sfidato"  AND sfidato_id  = ?))',
            [$da, $a, $nazione, $nazione])->fetchColumn();

        $tenute = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_crisi
             WHERE ultimo_tick BETWEEN ? AND ?
               AND ((stato = "ceduto_sfidato"  AND sfidante_id = ?)
                 OR (stato = "ceduto_sfidante" AND sfidato_id  = ?))',
            [$da, $a, $nazione, $nazione])->fetchColumn();

        $assenze = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_assenza_fatto WHERE poltrona_id = ? AND tick BETWEEN ? AND ?',
            [$poltrona, $da, $a])->fetchColumn();

        return [
            'guerre in cui ci siamo infilati' => -22.0 * $guerre,
            'crisi in cui abbiamo ceduto'     => -6.0 * $rese,
            'crisi in cui hanno ceduto loro'  =>  9.0 * $tenute,
            'decisioni prese senza di te'     => -3.0 * $assenze,
        ];
    }

    private function stato(int $nazione, int $tick): ?array
    {
        $r = $this->db->esegui(
            'SELECT * FROM sdb_nazione_stato WHERE nazione_id = ? AND tick <= ?
             ORDER BY tick DESC LIMIT 1', [$nazione, $tick])->fetch();
        return $r === false ? null : $r;
    }

    // ----------------------------------------------------------- la rivelazione

    private function rivela(int $epoca, int $da, int $a): int
    {
        $this->db->esegui('DELETE FROM sdb_rivelazione WHERE epoca_id = ?', [$epoca]);
        $n = 0;

        // Le operazioni coperte, col loro vero mandante. Comprese quelle che
        // portavano la bandiera di qualcun altro.
        foreach ($this->db->esegui(
            'SELECT e.*, m.nome AS mandante, b.nome AS bersaglio, f.nome AS bandiera
             FROM sdb_evento e
             JOIN sdb_nazione m ON m.id = e.mandante_id
             LEFT JOIN sdb_nazione b ON b.id = e.bersaglio_id
             LEFT JOIN sdb_nazione f ON f.id = e.falsa_bandiera_id
             WHERE e.dominio = "int" AND e.creato_tick BETWEEN ? AND ?
             ORDER BY e.creato_tick', [$da, $a])->fetchAll() as $e) {
            $this->aggiungi($epoca, 'operazione', (int) $e['creato_tick'],
                sprintf('%s: %s contro %s', $e['verbo'], $e['mandante'], $e['bersaglio'] ?? '—'),
                $e['bandiera'] !== null
                    ? sprintf('Portava la bandiera di %s. Stato finale: %s.', $e['bandiera'], $e['stato'])
                    : sprintf('Stato finale: %s.', $e['stato']));
            $n++;
        }

        // Le talpe, col loro padrone: quelle reclutate IN QUESTA epoca. Una
        // talpa ancora in servizio dall'epoca prima e' gia' stata rivelata
        // allora, e ricomparirebbe a ogni chiusura come una notizia nuova.
        foreach ($this->db->esegui(
            'SELECT p.nome, p.ruolo, p.reclutata_tick, n.nome AS paese, r.nome AS padrone
             FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id
             JOIN sdb_nazione r ON r.id = p.reclutata_da
             WHERE p.reclutata_da IS NOT NULL AND p.reclutata_tick BETWEEN ? AND ?',
            [$da, $a])->fetchAll() as $t) {
            $this->aggiungi($epoca, 'talpa', (int) ($t['reclutata_tick'] ?? $da),
                sprintf('%s, %s di %s, lavorava per %s',
                    $t['nome'], Canale::etichettaRuolo((string) $t['ruolo']), $t['paese'], $t['padrone']),
                'Per tutta l\'epoca ha consegnato la propria corrispondenza.');
            $n++;
        }

        // I messaggi riscritti, con l'originale accanto.
        foreach ($this->db->esegui(
            'SELECT m.tick, m.testo, m.testo_originale, m.soppresso, f.nome AS falsario,
                    nd.nome AS mittente, na.nome AS destinatario
             FROM sdb_messaggio m
             JOIN sdb_poltrona pd ON pd.id = m.da_poltrona
             JOIN sdb_nazione nd ON nd.id = pd.nazione_id
             JOIN sdb_poltrona pa ON pa.id = m.a_poltrona
             JOIN sdb_nazione na ON na.id = pa.nazione_id
             LEFT JOIN sdb_nazione f ON f.id = m.falsificato_da
             WHERE m.testo_originale IS NOT NULL AND m.tick BETWEEN ? AND ?',
            [$da, $a])->fetchAll() as $m) {
            $this->aggiungi($epoca, 'falso', (int) $m['tick'],
                sprintf('%s riscrisse una lettera da %s a %s',
                    $m['falsario'] ?? 'Qualcuno', $m['mittente'], $m['destinatario']),
                (int) $m['soppresso'] === 1
                    ? "Non arrivò mai. Diceva:\n" . $m['testo_originale']
                    : "Arrivò così:\n" . $m['testo'] . "\n\nEra stata scritta così:\n" . $m['testo_originale']);
            $n++;
        }

        // Le crisi, con quel che c'era davvero dietro.
        foreach ($this->db->esegui(
            'SELECT k.*, a.nome AS sfidante, b.nome AS sfidato, o.nome AS oggetto
             FROM sdb_crisi k
             JOIN sdb_nazione a ON a.id = k.sfidante_id
             JOIN sdb_nazione b ON b.id = k.sfidato_id
             LEFT JOIN sdb_nazione o ON o.id = k.oggetto_id
             WHERE k.ultimo_tick BETWEEN ? AND ?', [$da, $a])->fetchAll() as $k) {
            $this->aggiungi($epoca, 'crisi', (int) $k['ultimo_tick'],
                sprintf('%s contro %s per %s', $k['sfidante'], $k['sfidato'], $k['oggetto'] ?? '—'),
                sprintf('Finita %s, al gradino %d. In palio: %.0f punti di faccia per chi contestava, '
                    . '%.0f per chi era contestato.',
                    str_replace('_', ' ', (string) $k['stato']), (int) $k['livello'],
                    (float) $k['posta_sfidante'], (float) $k['posta_sfidato']));
            $n++;
        }

        return $n;
    }

    private function aggiungi(int $epoca, string $genere, int $tick, string $titolo, string $dettaglio): void
    {
        $this->db->esegui(
            'INSERT INTO sdb_rivelazione (epoca_id, genere, tick, titolo, dettaglio) VALUES (?,?,?,?,?)',
            [$epoca, $genere, $tick, mb_substr($titolo, 0, 190), $dettaglio]);
    }

    // -------------------------------------------------------------- la lettura

    /** @return list<array<string,mixed>> */
    public function classifica(int $epoca): array
    {
        return $this->db->esegui(
            'SELECT s.*, g.nome AS giocatore, n.nome AS nazione, p.ruolo
             FROM sdb_punteggio s
             JOIN sdb_giocatore g ON g.id = s.giocatore_id
             JOIN sdb_nazione n ON n.id = s.nazione_id
             JOIN sdb_poltrona p ON p.id = s.poltrona_id
             WHERE s.epoca_id = ? ORDER BY s.totale DESC', [$epoca])->fetchAll();
    }

    /** @return array<string,list<array<string,mixed>>> */
    public function rivelazioni(int $epoca): array
    {
        $per = [];
        foreach ($this->db->esegui(
            'SELECT * FROM sdb_rivelazione WHERE epoca_id = ? ORDER BY genere, tick', [$epoca])->fetchAll() as $r) {
            $per[(string) $r['genere']][] = $r;
        }
        return $per;
    }
}
