<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * Le linee dirette fra potenze — il telefono rosso.
 *
 * Un canale dedicato fra due capitali: i segnali di terzi non lo toccano, e
 * arriva in un giro solo. E' il canale migliore del gioco, e proprio per
 * questo ha tre costi che nessun altro ha.
 *
 * **Si stabilisce in due.** Una parte propone, l'altra accetta. Finche' non
 * accetta, la linea non esiste.
 *
 * **E' pubblica.** Chiunque puo' vedere quali capitali hanno una linea aperta
 * fra loro. Aprirne una e' una dichiarazione di allineamento, e chiuderla lo e'
 * ancora di piu'.
 *
 * **E' al sicuro dai segnali, non dalle persone.** Chi ha reclutato un uomo in
 * uno dei due gabinetti legge tutto quello che ci passa — meglio di come
 * leggerebbe qualunque cifrato, perche' non deve decifrare niente: gli viene
 * consegnato. Il canale piu' sicuro e' anche quello che ripaga di piu' chi ha
 * saputo mettere qualcuno dentro, ed e' la ragione per cui esiste.
 */
final class Linea
{
    /** Le poltrone che possono impegnare il paese su una linea diretta. */
    public const RUOLI_AMMESSI = ['capo', 'esteri'];

    public function __construct(private readonly Basedati $db) {}

    /** @return array{0:bool,1:string} */
    public function proponi(array $poltrona, int $controparte, int $tick): array
    {
        if (!in_array((string) $poltrona['ruolo'], self::RUOLI_AMMESSI, true)) {
            return [false, 'Una linea diretta la si apre dal vertice: il Capo o gli Esteri.'];
        }
        $nostra = (int) $poltrona['nazione_id'];
        if ($controparte === $nostra || $controparte <= 0) {
            return [false, 'Con chi, di preciso?'];
        }
        $haGabinetto = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_poltrona WHERE nazione_id = ?', [$controparte])->fetchColumn();
        if ($haGabinetto === 0) {
            return [false, 'Quel paese non ha un gabinetto con cui parlare.'];
        }

        [$a, $b] = $this->ordina($nostra, $controparte);
        $gia = $this->db->esegui(
            'SELECT stato FROM sdb_linea WHERE a_nazione_id = ? AND b_nazione_id = ?', [$a, $b])->fetchColumn();
        if ($gia === 'attiva') {
            return [false, 'La linea con loro è già aperta.'];
        }
        if ($gia === 'proposta') {
            return [false, 'C\'è già una proposta in piedi: tocca a loro rispondere.'];
        }

        $this->db->esegui(
            'REPLACE INTO sdb_linea (a_nazione_id, b_nazione_id, proposta_da, stato, proposta_tick)
             VALUES (?,?,?,"proposta",?)', [$a, $b, (int) $poltrona['id'], $tick]);

        return [true, 'Proposta trasmessa. Una linea diretta esiste solo se la vogliono in due: '
            . 'adesso la palla è dall\'altra parte.'];
    }

    /** @return array{0:bool,1:string} */
    public function rispondi(array $poltrona, int $linea, bool $accetta, int $tick): array
    {
        if (!in_array((string) $poltrona['ruolo'], self::RUOLI_AMMESSI, true)) {
            return [false, 'Non è una risposta che puoi dare tu.'];
        }
        $nostra = (int) $poltrona['nazione_id'];
        $l = $this->db->esegui(
            'SELECT * FROM sdb_linea WHERE id = ? AND stato = "proposta"', [$linea])->fetch();
        if ($l === false) {
            return [false, 'Quella proposta non è più in piedi.'];
        }
        if ((int) $l['a_nazione_id'] !== $nostra && (int) $l['b_nazione_id'] !== $nostra) {
            return [false, 'Quella proposta non riguarda noi.'];
        }
        // Chi ha proposto non puo' anche accettare.
        $proponente = (int) $this->db->esegui(
            'SELECT nazione_id FROM sdb_poltrona WHERE id = ?', [(int) $l['proposta_da']])->fetchColumn();
        if ($proponente === $nostra) {
            return [false, 'L\'abbiamo proposta noi: aspettiamo la loro risposta.'];
        }

        if (!$accetta) {
            $this->db->esegui('UPDATE sdb_linea SET stato = "rifiutata", chiusa_tick = ? WHERE id = ?',
                [$tick, $linea]);
            return [true, 'Proposta respinta. Continueranno a scriverci come tutti gli altri.'];
        }

        $this->db->esegui(
            'UPDATE sdb_linea SET stato = "attiva", accettata_da = ?, attivata_tick = ? WHERE id = ?',
            [(int) $poltrona['id'], $tick, $linea]);

        return [true, 'Linea aperta. Da adesso i segnali altrui non ci toccano più — '
            . 'ma chiunque abbia un uomo in uno dei due palazzi legge tutto, e senza fatica.'];
    }

    /** @return array{0:bool,1:string} */
    public function chiudi(array $poltrona, int $linea, int $tick): array
    {
        if (!in_array((string) $poltrona['ruolo'], self::RUOLI_AMMESSI, true)) {
            return [false, 'Non è una decisione che puoi prendere tu.'];
        }
        $n = $this->db->esegui(
            'UPDATE sdb_linea SET stato = "revocata", chiusa_tick = ?
             WHERE id = ? AND stato = "attiva" AND (a_nazione_id = ? OR b_nazione_id = ?)',
            [$tick, $linea, (int) $poltrona['nazione_id'], (int) $poltrona['nazione_id']])->rowCount();
        return $n > 0
            ? [true, 'Linea chiusa. Il mondo lo vedrà, e ne trarrà le sue conclusioni.']
            : [false, 'Quella linea non è nostra da chiudere.'];
    }

    /**
     * Le linee che ci riguardano: attive, proposte da noi, e proposte a noi.
     *
     * @return list<array<string,mixed>>
     */
    public function nostre(int $nazione): array
    {
        return $this->db->esegui(
            'SELECT l.*, a.nome AS nome_a, b.nome AS nome_b,
                    p.nazione_id AS proponente_id
             FROM sdb_linea l
             JOIN sdb_nazione a ON a.id = l.a_nazione_id
             JOIN sdb_nazione b ON b.id = l.b_nazione_id
             JOIN sdb_poltrona p ON p.id = l.proposta_da
             WHERE (l.a_nazione_id = ? OR l.b_nazione_id = ?) AND l.stato IN ("proposta","attiva")
             ORDER BY l.stato, l.id', [$nazione, $nazione])->fetchAll();
    }

    /** Tutte le linee aperte del mondo: è un fatto pubblico. @return list<array<string,mixed>> */
    public function pubbliche(): array
    {
        return $this->db->esegui(
            'SELECT l.attivata_tick, a.nome AS nome_a, b.nome AS nome_b
             FROM sdb_linea l
             JOIN sdb_nazione a ON a.id = l.a_nazione_id
             JOIN sdb_nazione b ON b.id = l.b_nazione_id
             WHERE l.stato = "attiva" ORDER BY l.attivata_tick DESC')->fetchAll();
    }

    /** Gli id delle nazioni con cui abbiamo una linea aperta. @return list<int> */
    public function apertePer(int $nazione): array
    {
        return array_map('intval', $this->db->esegui(
            'SELECT IF(a_nazione_id = ?, b_nazione_id, a_nazione_id) FROM sdb_linea
             WHERE stato = "attiva" AND (a_nazione_id = ? OR b_nazione_id = ?)',
            [$nazione, $nazione, $nazione])->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function esiste(int $a, int $b): bool
    {
        [$x, $y] = $this->ordina($a, $b);
        return (bool) $this->db->esegui(
            'SELECT 1 FROM sdb_linea WHERE a_nazione_id = ? AND b_nazione_id = ? AND stato = "attiva"',
            [$x, $y])->fetchColumn();
    }

    /** @return array{0:int,1:int} */
    private function ordina(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }
}
