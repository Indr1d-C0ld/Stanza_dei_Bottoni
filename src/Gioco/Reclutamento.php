<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Dati\Gabinetto;
use App\Nucleo\Basedati;

/**
 * Il Giuda.
 *
 * Nel gioco da cui veniamo il traditore è assegnato dal sistema. Con giocatori
 * veri sarebbe brutto: o lo si sorteggia, e chi lo pesca gioca un altro gioco,
 * oppure non c'è, e manca la tensione che dà il nome a tutto.
 *
 * Qui il tradimento **non si estrae: si matura**. Un servizio straniero fa
 * un'offerta a una poltrona che ha ragione di essere scontenta, e quella
 * accetta o rifiuta. Rifiutare non è gratis nemmeno per chi rifiuta — resta in
 * mano una prova, e una prova si può usare in molti modi.
 */
final class Reclutamento
{
    /** Chi può tentare di comprarsi qualcuno. */
    public const RUOLI_AMMESSI = ['intelligence', 'capo'];

    public function __construct(private readonly Basedati $db) {}

    /**
     * @param array<string,mixed> $poltrona
     * @return array{0:bool,1:string}
     */
    public function offri(array $poltrona, int $destinatario, string $testo,
        bool $denaro, bool $dossier, bool $appoggio, int $tick): array
    {
        if (!in_array((string) $poltrona['ruolo'], self::RUOLI_AMMESSI, true)) {
            return [false, 'Non è mestiere della tua poltrona.'];
        }
        $d = $this->db->esegui(
            'SELECT p.*, n.id AS nazione, n.nome AS paese FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id WHERE p.id = ?', [$destinatario])->fetch();
        if ($d === false) {
            return [false, 'Poltrona sconosciuta.'];
        }
        if ((int) $d['nazione_id'] === (int) $poltrona['nazione_id']) {
            return [false, 'Quello è un collega: se vuoi comprarlo, non serve un servizio segreto.'];
        }
        if ($d['reclutata_da'] !== null) {
            return [false, 'Qualcuno è arrivato prima di te.'];
        }
        if (!$denaro && !$dossier && !$appoggio) {
            return [false, 'Un\'offerta senza niente sul piatto non è un\'offerta.'];
        }
        $pendente = $this->db->esegui(
            'SELECT 1 FROM sdb_offerta WHERE a_poltrona = ? AND stato = "aperta" LIMIT 1',
            [$destinatario])->fetchColumn();
        if ($pendente) {
            return [false, 'Ha già un\'altra proposta sul tavolo: aspetta che risponda.'];
        }

        $this->db->esegui(
            'INSERT INTO sdb_offerta
                (da_nazione_id, da_poltrona, a_poltrona, testo, offre_denaro, offre_dossier,
                 offre_appoggio, tick, scade_tick)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [(int) $poltrona['nazione_id'], (int) $poltrona['id'], $destinatario, trim($testo),
             (int) $denaro, (int) $dossier, (int) $appoggio, $tick, $tick + 6],
        );
        return [true, sprintf('Proposta recapitata a %s di %s. Ora tocca a lui.',
            Gabinetto::RUOLI[$d['ruolo']] ?? $d['ruolo'], $d['paese'])];
    }

    /** @return list<array<string,mixed>> */
    public function offerteRicevute(int $poltrona): array
    {
        return $this->db->esegui(
            'SELECT o.*, n.nome AS da_paese, p.ruolo AS da_ruolo
             FROM sdb_offerta o
             JOIN sdb_nazione n ON n.id = o.da_nazione_id
             JOIN sdb_poltrona p ON p.id = o.da_poltrona
             WHERE o.a_poltrona = ? AND o.stato = "aperta" ORDER BY o.id', [$poltrona])->fetchAll();
    }

    /** @return array{0:bool,1:string} */
    public function rispondi(int $offerta, array $poltrona, string $risposta, int $tick): array
    {
        $o = $this->db->esegui(
            'SELECT o.*, n.nome AS da_paese FROM sdb_offerta o
             JOIN sdb_nazione n ON n.id = o.da_nazione_id
             WHERE o.id = ? AND o.a_poltrona = ? AND o.stato = "aperta"',
            [$offerta, (int) $poltrona['id']])->fetch();
        if ($o === false) {
            return [false, 'Quella proposta non è più sul tavolo.'];
        }

        switch ($risposta) {
            case 'accetta':
                $this->db->esegui('UPDATE sdb_offerta SET stato = "accettata", risposta_tick = ? WHERE id = ?',
                    [$tick, $offerta]);
                $this->db->esegui(
                    'UPDATE sdb_poltrona SET reclutata_da = ?, reclutata_tick = ?, lealta = lealta * 0.4
                     WHERE id = ?',
                    [(int) $o['da_nazione_id'], $tick, (int) $poltrona['id']]);
                return [true, sprintf(
                    'Hai accettato. Da adesso quel che passa dalla tua scrivania passa anche per %s. '
                    . 'Se te ne accorgono, non sarà un rimprovero.', $o['da_paese'])];

            case 'rifiuta':
                $this->db->esegui('UPDATE sdb_offerta SET stato = "rifiutata", risposta_tick = ? WHERE id = ?',
                    [$tick, $offerta]);
                return [true, 'Hai rifiutato. La proposta resta agli atti: è una prova, e le prove si usano.'];

            case 'denuncia':
                $this->db->esegui('UPDATE sdb_offerta SET stato = "denunciata", risposta_tick = ? WHERE id = ?',
                    [$tick, $offerta]);
                // Denunciare costa al proponente in rapporti, e paga in
                // reputazione a chi denuncia.
                $this->db->esegui(
                    'UPDATE sdb_relazione SET affinita = GREATEST(-127, affinita - 25)
                     WHERE da_nazione_id = ? AND a_nazione_id = ?',
                    [(int) $poltrona['nazione_id'], (int) $o['da_nazione_id']]);
                $this->db->esegui(
                    'UPDATE sdb_giocatore SET integrita = LEAST(128, integrita + 6) WHERE id = ?',
                    [(int) $poltrona['giocatore_id']]);
                $this->db->esegui(
                    'INSERT INTO sdb_notizia (tick, genere, dati) VALUES (?,?,?)',
                    [$tick, 'reclutamento_denunciato', json_encode([
                        'paese'     => $this->nomeNazione((int) $poltrona['nazione_id']),
                        'accusa'    => (string) $o['da_paese'],
                        'poltrona'  => Gabinetto::RUOLI[$poltrona['ruolo']] ?? $poltrona['ruolo'],
                    ], JSON_UNESCAPED_UNICODE)]);
                return [true, 'Hai reso pubblica la proposta. Il mondo lo saprà, e il tuo nome ci guadagna.'];
        }
        return [false, 'Risposta non prevista.'];
    }

    /** @return list<array<string,mixed>> chi lavora per noi, dentro casa d'altri */
    public function nostriUomini(int $nazione): array
    {
        return $this->db->esegui(
            'SELECT p.id, p.ruolo, p.nome, p.sospettata, p.reclutata_tick, n.nome AS paese, n.codice
             FROM sdb_poltrona p JOIN sdb_nazione n ON n.id = p.nazione_id
             WHERE p.reclutata_da = ? ORDER BY p.reclutata_tick DESC', [$nazione])->fetchAll();
    }

    private function nomeNazione(int $id): string
    {
        return (string) $this->db->esegui('SELECT nome FROM sdb_nazione WHERE id = ?', [$id])->fetchColumn();
    }
}
