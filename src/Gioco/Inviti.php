<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;
use App\Nucleo\Configurazione;

/**
 * Gli inviti, e chi puo' entrare.
 *
 * Per una partita fra qualche decina di persone che si tengono il posto per
 * mesi, la registrazione aperta non e' il modello giusto: non e' un forum dove
 * chi arriva legge e se ne va, e' un tavolo dove un posto occupato male rovina
 * la partita a tutti gli altri.
 *
 * Un codice vale **una volta sola**. Non esiste il codice buono per tutti:
 * quella sarebbe una registrazione aperta scritta peggio.
 */
final class Inviti
{
    public const MODI = [
        'aperte' => 'Chiunque puo\' registrarsi.',
        'invito' => 'Serve un codice, che consegni tu a chi vuoi.',
        'chiuse' => 'Nessuno puo\' registrarsi, e la pagina lo dice.',
    ];

    /** Quanto dura un invito prima di scadere, in giorni. */
    public const DURATA_GIORNI = 30;

    public function __construct(private readonly Basedati $db) {}

    /**
     * Come sono le registrazioni adesso.
     *
     * Si legge dalla CALIBRAZIONE e non dalla configurazione, ed e' una
     * distinzione che mi e' costata un difetto: l'arbitro muoveva la leva, la
     * leva finiva in sdb_leva e da li' nella calibrazione, e questo metodo
     * continuava a leggere il file in /etc — che non era cambiato. La leva si
     * muoveva e non succedeva niente.
     *
     * Il file resta il valore di partenza, come per ogni altra leva.
     */
    public function modo(): string
    {
        $cal = Calibrazione::carica(dirname(__DIR__, 2),
            (string) Configurazione::leggi('mondo.profilo', 'gioco'));
        $m = (string) ($cal->leggi('gioco.registrazioni',
            Configurazione::leggi('gioco.registrazioni', 'invito')) ?? 'invito');
        return isset(self::MODI[$m]) ? $m : 'invito';
    }

    /**
     * Si puo' registrare, questo qui?
     *
     * @return array{0:bool,1:string} il secondo e' il motivo del no
     */
    public function ammesso(string $codice): array
    {
        return match ($this->modo()) {
            'aperte' => [true, ''],
            'chiuse' => [false, 'Le registrazioni sono chiuse. Non e\' una cosa che passa '
                . 'aspettando: se pensi di doverci essere, scrivi a chi ti ha parlato del gioco.'],
            default  => $this->codiceBuono($codice),
        };
    }

    /** @return array{0:bool,1:string} */
    private function codiceBuono(string $codice): array
    {
        $codice = strtoupper(trim($codice));
        if ($codice === '') {
            return [false, 'Serve un codice d\'invito.'];
        }
        $r = $this->db->esegui(
            'SELECT codice, usato_da, scade_il FROM sdb_invito WHERE codice = ?', [$codice])->fetch();
        if ($r === false) {
            return [false, 'Quel codice non esiste.'];
        }
        if ($r['usato_da'] !== null) {
            return [false, 'Quel codice e\' gia\' stato usato. Ognuno vale una volta sola.'];
        }
        if ($r['scade_il'] !== null && strtotime((string) $r['scade_il']) < time()) {
            return [false, 'Quel codice e\' scaduto: chiedine un altro a chi te l\'ha dato.'];
        }
        return [true, ''];
    }

    /** Un codice si consuma quando qualcuno lo usa, e resta scritto chi. */
    /**
     * Consuma l'invito, e dice se ci e' riuscito. Prima non lo diceva: due
     * iscrizioni simultanee con lo stesso codice passavano entrambe, perche'
     * la seconda non toccava nessuna riga e nessuno se ne accorgeva.
     */
    public function consuma(string $codice, int $giocatore): bool
    {
        if ($this->modo() !== 'invito') {
            return true;
        }
        return $this->db->esegui(
            'UPDATE sdb_invito SET usato_da = ?, usato_il = NOW()
             WHERE codice = ? AND usato_da IS NULL',
            [$giocatore, strtoupper(trim($codice))])->rowCount() === 1;
    }

    /** @return array{0:bool,1:string} */
    public function crea(int $arbitro, string $nota, int $quanti = 1): array
    {
        $quanti = max(1, min(20, $quanti));
        $fatti = [];
        for ($i = 0; $i < $quanti; $i++) {
            $codice = $this->codiceNuovo();
            $this->db->esegui(
                'INSERT INTO sdb_invito (codice, creato_da, scade_il, nota)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), ?)',
                [$codice, $arbitro, self::DURATA_GIORNI, mb_substr(trim($nota), 0, 120)]);
            $fatti[] = $codice;
        }
        return [true, $quanti === 1
            ? sprintf('Codice: %s — vale %d giorni, e una volta sola.', $fatti[0], self::DURATA_GIORNI)
            : sprintf('%d codici: %s', $quanti, implode(' · ', $fatti))];
    }

    /**
     * Un codice che si possa dettare al telefono.
     *
     * Niente 0, O, 1, I, L: chi lo ricopia sbaglia, e sbagliare un codice
     * d'invito vuol dire scrivere a qualcuno per chiederne un altro.
     */
    private function codiceNuovo(): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $c = '';
            for ($i = 0; $i < 10; $i++) {
                if ($i === 5) {
                    $c .= '-';
                }
                $c .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
            $preso = $this->db->esegui('SELECT 1 FROM sdb_invito WHERE codice = ?', [$c])->fetchColumn();
        } while ($preso);
        return $c;
    }

    public function revoca(string $codice): array
    {
        $n = $this->db->esegui(
            'DELETE FROM sdb_invito WHERE codice = ? AND usato_da IS NULL',
            [strtoupper(trim($codice))])->rowCount();
        return $n > 0
            ? [true, 'Codice revocato.']
            : [false, 'Quel codice non c\'e\', o e\' gia\' stato usato.'];
    }

    /** @return list<array<string,mixed>> */
    public function elenco(int $quanti = 30): array
    {
        return $this->db->esegui(
            'SELECT i.*, a.nome AS arbitro, u.nome AS usato_dal
             FROM sdb_invito i
             JOIN sdb_giocatore a ON a.id = i.creato_da
             LEFT JOIN sdb_giocatore u ON u.id = i.usato_da
             ORDER BY i.creato_il DESC LIMIT ' . (int) $quanti)->fetchAll();
    }

    /** @return array{liberi:int,usati:int,scaduti:int} */
    public function conto(): array
    {
        $r = $this->db->esegui(
            'SELECT
                SUM(usato_da IS NULL AND (scade_il IS NULL OR scade_il >= NOW())) AS liberi,
                SUM(usato_da IS NOT NULL) AS usati,
                SUM(usato_da IS NULL AND scade_il < NOW()) AS scaduti
             FROM sdb_invito')->fetch();
        return array_map(static fn($v) => (int) $v, (array) $r);
    }
}
