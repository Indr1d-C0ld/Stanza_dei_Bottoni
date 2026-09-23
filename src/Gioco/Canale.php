<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Dati\Gabinetto;
use App\Nucleo\Basedati;

/**
 * La messaggistica fra poltrone, e la sua fragilità.
 *
 * Il principio, che vale più di tutta l'implementazione: **i tuoi messaggi
 * privati non sono sicuri**. Questa singola regola produce da sola buona parte
 * del gioco politico — perché cambia cosa scrivi, a chi, e su quale canale; e
 * perché prima o poi qualcuno userà un canale che *sa* compromesso per far
 * leggere all'avversario esattamente ciò che vuole.
 *
 * La sicurezza è una proprietà del CANALE, non della cifratura a riposo: il
 * testo sta in chiaro nel database perché l'arbitro deve poterlo leggere, e
 * perché fingere altrimenti sarebbe teatro.
 */
final class Canale
{
    public const LIVELLI = [
        1 => ['nome' => 'aperto',       'nota' => 'dichiarazione pubblica: la leggono tutti'],
        2 => ['nome' => 'diplomatico',  'nota' => 'valigia diplomatica: difesa ordinaria'],
        3 => ['nome' => 'cifrato',      'nota' => 'costoso e lento da violare, non impossibile'],
        4 => ['nome' => 'corriere',     'nota' => 'a mano, intoccabile dai segnali — arriva fra due tick'],
        5 => ['nome' => 'linea diretta', 'nota' => 'il canale dedicato con una potenza: veloce e fuori dai segnali — ma non dalle persone'],
    ];

    /** Quanti messaggi ad alta sicurezza può spedire una poltrona per tick. */
    public const TETTO_RISERVATI = 3;

    public function __construct(private readonly Basedati $db) {}

    /** @return array{0:bool,1:string} */
    public function invia(array $poltrona, int $destinatario, string $testo, int $sicurezza, int $tick): array
    {
        $testo = trim($testo);
        if ($testo === '' || mb_strlen($testo) > 4000) {
            return [false, 'Il messaggio è vuoto o troppo lungo.'];
        }
        $sicurezza = max(1, min(5, $sicurezza));

        $esiste = $this->db->esegui('SELECT 1 FROM sdb_poltrona WHERE id = ?', [$destinatario])->fetchColumn();
        if (!$esiste) {
            return [false, 'Destinatario sconosciuto.'];
        }
        if ($destinatario === (int) $poltrona['id']) {
            return [false, 'Scrivere a sé stessi non serve a nulla.'];
        }

        if ($sicurezza === 5) {
            $loro = (int) $this->db->esegui(
                'SELECT nazione_id FROM sdb_poltrona WHERE id = ?', [$destinatario])->fetchColumn();
            if (!(new Linea($this->db))->esiste((int) $poltrona['nazione_id'], $loro)) {
                return [false, 'Con loro non abbiamo una linea diretta: va aperta prima, e in due.'];
            }
        }

        // La linea diretta non pesa sul contingente dei canali riservati: è
        // infrastruttura costruita apposta, ed è tutto il suo senso. Il
        // contingente copre il cifrato E il corriere: prima valeva solo per il
        // cifrato — il corriere, piu' sicuro ancora, era illimitato — e
        // contava contro il cifrato anche i messaggi sulla linea diretta.
        if ($sicurezza === 3 || $sicurezza === 4) {
            $usati = (int) $this->db->esegui(
                'SELECT COUNT(*) FROM sdb_messaggio WHERE da_poltrona = ? AND sicurezza IN (3,4) AND tick_invio = ?',
                [(int) $poltrona['id'], $tick])->fetchColumn();
            if ($usati >= self::TETTO_RISERVATI) {
                return [false, 'Hai esaurito i canali riservati di questo giro: ne restano di ordinari.'];
            }
        }

        // Niente arriva nell'istante in cui parte, tranne una dichiarazione
        // pubblica, che è pubblica proprio perché è già arrivata. Tutto il
        // resto passa per un transito — ed è in quel transito che un servizio
        // straniero può metterci le mani. Il corriere ci mette di più: è il
        // prezzo dell'unico canale che i segnali non toccano.
        $arrivo = match ($sicurezza) {
            1       => $tick,          // una dichiarazione pubblica è già arrivata
            4       => $tick + 2,      // il corriere viaggia a piedi
            default => $tick + 1,
        };

        // Due momenti distinti: quando parte (ed e' intercettabile) e quando
        // arriva. Per il corriere non coincidono, ed e' tutto il suo senso.
        $this->db->esegui(
            'INSERT INTO sdb_messaggio
                (da_poltrona, a_poltrona, testo, sicurezza, tick, tick_invio, intercettabile, inviato)
             VALUES (?,?,?,?,?,?,?,NOW())',
            [(int) $poltrona['id'], $destinatario, $testo, $sicurezza, $arrivo, $tick,
             $sicurezza >= 4 ? 0 : 1],
        );
        return [true, match ($sicurezza) {
            4 => 'Affidato a un corriere: arriverà fra due giri d\'orologio.',
            5 => 'Passato sulla linea diretta. Nessun servizio straniero lo ascolterà — '
               . 'ma se hanno un uomo in uno dei due palazzi, lo leggeranno lo stesso.',
            default => 'Inviato sul canale ' . self::LIVELLI[$sicurezza]['nome'] . '.',
        }];
    }

    /** @return list<array<string,mixed>> */
    public function ricevuti(int $poltrona, int $tick, int $quanti = 30): array
    {
        return $this->db->esegui(
            'SELECT m.*, p.ruolo AS ruolo_mittente, n.nome AS nazione_mittente, n.codice,
                    g.nome AS giocatore_mittente, p.nome AS titolare_mittente
             FROM sdb_messaggio m
             JOIN sdb_poltrona p ON p.id = m.da_poltrona
             JOIN sdb_nazione n ON n.id = p.nazione_id
             LEFT JOIN sdb_giocatore g ON g.id = p.giocatore_id
             WHERE m.a_poltrona = ? AND m.tick <= ? AND m.soppresso = 0
             ORDER BY m.id DESC LIMIT ' . (int) $quanti,
            [$poltrona, $tick])->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function inviati(int $poltrona, int $tick, int $quanti = 20): array
    {
        return $this->db->esegui(
            'SELECT m.*, COALESCE(m.testo_originale, m.testo) AS testo,
                    (m.tick > ?) AS in_transito,
                    p.ruolo AS ruolo_destinatario, n.nome AS nazione_destinataria,
                    g.nome AS giocatore_destinatario, p.nome AS titolare_destinatario
             FROM sdb_messaggio m
             JOIN sdb_poltrona p ON p.id = m.a_poltrona
             JOIN sdb_nazione n ON n.id = p.nazione_id
             LEFT JOIN sdb_giocatore g ON g.id = p.giocatore_id
             WHERE m.da_poltrona = ? ORDER BY m.id DESC LIMIT ' . (int) $quanti,
            [$tick, $poltrona])->fetchAll();
    }

    /**
     * Quel che i nostri servizi hanno letto delle conversazioni altrui.
     *
     * Lo vede solo chi ha l'Intelligence o la Sicurezza interna: è il prodotto
     * di quel mestiere, non un privilegio di tutto il gabinetto.
     *
     * Qui si mostra sempre il testo **originale**: chi era sul filo ha sentito
     * quel che è stato detto davvero. Se poi qualcuno lo ha riscritto prima
     * della consegna, quella è una bugia che riguarda il destinatario, non chi
     * ascoltava.
     *
     * @return list<array<string,mixed>>
     */
    public function intercettati(int $nazione, int $quanti = 30): array
    {
        return $this->db->esegui(
            'SELECT i.livello, i.tick, m.sicurezza,
                    COALESCE(m.testo_originale, m.testo) AS testo,
                    pd.ruolo AS ruolo_mittente, nd.nome AS nazione_mittente,
                    pa.ruolo AS ruolo_destinatario, na.nome AS nazione_destinataria
             FROM sdb_intercettazione i
             JOIN sdb_messaggio m ON m.id = i.messaggio_id
             JOIN sdb_poltrona pd ON pd.id = m.da_poltrona
             JOIN sdb_nazione  nd ON nd.id = pd.nazione_id
             JOIN sdb_poltrona pa ON pa.id = m.a_poltrona
             JOIN sdb_nazione  na ON na.id = pa.nazione_id
             WHERE i.nazione_id = ? ORDER BY i.tick DESC, i.messaggio_id DESC LIMIT ' . (int) $quanti,
            [$nazione])->fetchAll();
    }

    /** I corrispondenti possibili: le poltrone delle potenze giocabili. */
    public function rubrica(int $escludi): array
    {
        $righe = $this->db->esegui(
            'SELECT p.id, p.ruolo, p.nome AS titolare, n.nome AS nazione, n.id AS nazione_id,
                    g.nome AS giocatore
             FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id
             LEFT JOIN sdb_giocatore g ON g.id = p.giocatore_id
             WHERE p.id <> ? ORDER BY n.nome, p.ruolo', [$escludi])->fetchAll();
        $per = [];
        foreach ($righe as $r) {
            $per[(string) $r['nazione']][] = $r;
        }
        return $per;
    }

    public static function etichettaRuolo(string $ruolo): string
    {
        return Gabinetto::RUOLI[$ruolo] ?? $ruolo;
    }
}
