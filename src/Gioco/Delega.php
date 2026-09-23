<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * Chi siede davvero a una poltrona, in questo momento.
 *
 * In un gioco che va avanti a tick anche di notte, la domanda non ha una
 * risposta ovvia. Una poltrona puo' avere un titolare che non si collega da
 * tre giorni: sulla carta e' presidiata, nei fatti e' vuota. E se una poltrona
 * vuota basta a bloccare una crisi o una controfirma, il mondo si ferma per
 * tutti gli altri — il modo piu' sicuro di uccidere una partita persistente.
 *
 * Qui stanno le due risposte a quel problema.
 *
 * La **delega** e' volontaria e si sceglie: affidi la poltrona a un altro
 * giocatore mentre non ci sei. Agisce in tuo nome, e la sua firma resta agli
 * atti accanto alla tua. Puo' fare quello che avresti fatto tu, e anche quello
 * che non avresti mai fatto: e' esattamente il tipo di rischio che questo gioco
 * vuole far correre.
 *
 * L'**assenza** e' automatica e non si sceglie: dopo un certo numero di tick
 * senza che nessuno tocchi la poltrona, l'apparato riprende il paese in mano.
 * Non persegue le tue agende private — quelle sono tue — e fa solo quello che
 * farebbe in un gabinetto senza titolare.
 */
final class Delega
{
    /** Dopo quanti tick di silenzio l'apparato riprende in mano la poltrona. */
    public const TICK_PRIMA_DELL_APPARATO = 6;

    public function __construct(private readonly Basedati $db) {}

    /**
     * Il pezzo di SQL che dice se una poltrona ha davvero qualcuno seduto.
     *
     * Serve identico in piu' fasi del tick, e averlo in un posto solo e' quel
     * che impedisce che il motore e l'interfaccia finiscano per non essere
     * d'accordo su chi comanda.
     */
    public static function sqlPresidiata(string $alias, int $tick): string
    {
        $soglia = $tick - self::TICK_PRIMA_DELL_APPARATO;
        // «Dopo sei tick senza che NESSUNO tocchi la poltrona» (docs/20):
        // nessuno vuol dire ne' il titolare ne' il delegato, e ciascuno dei
        // due la «tocca» quando agisce (index.php chiama tocca() sulla
        // poltrona in uso). Prima c'era anche «OR delega_a IS NOT NULL», e una
        // poltrona delegata restava presidiata PER SEMPRE: se titolare e
        // delegato sparivano, l'apparato non subentrava mai e le crisi
        // scadevano cedute.
        return "($alias.giocatore_id IS NOT NULL AND $alias.ultimo_tick_attivo >= $soglia)";
    }

    /** Segna che qualcuno ha davvero toccato questa poltrona, adesso. */
    public function tocca(int $poltrona, int $tick): void
    {
        $this->db->esegui('UPDATE sdb_poltrona SET ultimo_tick_attivo = ? WHERE id = ?',
            [$tick, $poltrona]);
    }

    /** @return array{0:bool,1:string} */
    public function affida(array $poltrona, int $giocatore, int $tick): array
    {
        if ($giocatore === (int) $poltrona['giocatore_id']) {
            return [false, 'Delegare a sé stessi non è delegare.'];
        }
        $esiste = $this->db->esegui(
            'SELECT nome FROM sdb_giocatore WHERE id = ? AND attivo = 1', [$giocatore])->fetchColumn();
        if ($esiste === false) {
            return [false, 'Quel giocatore non c\'è.'];
        }
        $this->db->esegui(
            'UPDATE sdb_poltrona SET delega_a = ?, delega_dal_tick = ? WHERE id = ?',
            [$giocatore, $tick, (int) $poltrona['id']]);

        return [true, sprintf(
            'La poltrona è affidata a %s. Firmerà in tuo nome e resterà agli atti che è stato lui. '
            . 'Potrà fare quello che avresti fatto tu — e quello che non avresti mai fatto.',
            (string) $esiste)];
    }

    /** @return array{0:bool,1:string} */
    public function revoca(array $poltrona, int $tick): array
    {
        if ($poltrona['delega_a'] === null) {
            return [false, 'Non c\'è nessuna delega da revocare.'];
        }
        $this->db->esegui(
            'UPDATE sdb_poltrona SET delega_a = NULL, delega_dal_tick = NULL, ultimo_tick_attivo = ?
             WHERE id = ?', [$tick, (int) $poltrona['id']]);
        return [true, 'Delega revocata: la poltrona torna tua.'];
    }

    /**
     * Le poltrone su cui questo giocatore puo' agire: la sua, e quelle che
     * altri gli hanno affidato.
     *
     * @return list<array<string,mixed>>
     */
    public function affidateA(int $giocatore): array
    {
        return $this->db->esegui(
            'SELECT p.*, n.nome AS nazione, n.codice, g.nome AS titolare_vero
             FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id
             JOIN sdb_giocatore g ON g.id = p.giocatore_id
             WHERE p.delega_a = ?', [$giocatore])->fetchAll();
    }

    /** Annota una cosa fatta mentre il titolare non c'era. */
    public function annota(int $poltrona, int $tick, string $chi, string $cosa, string $dettaglio = ''): void
    {
        $this->db->esegui(
            'INSERT INTO sdb_assenza_fatto (poltrona_id, tick, chi, cosa, dettaglio)
             VALUES (?,?,?,?,?)', [$poltrona, $tick, $chi, $cosa, mb_substr($dettaglio, 0, 255)]);
    }

    /**
     * Il conto di quel che è successo mentre non c'eri, e che non hai ancora
     * letto. Leggerlo lo consuma: si dice una volta sola.
     *
     * @return list<array<string,mixed>>
     */
    public function mentreNonCEri(int $poltrona): array
    {
        $righe = $this->db->esegui(
            'SELECT * FROM sdb_assenza_fatto WHERE poltrona_id = ? AND visto = 0 ORDER BY tick, id',
            [$poltrona])->fetchAll();
        if ($righe !== []) {
            $this->db->esegui('UPDATE sdb_assenza_fatto SET visto = 1 WHERE poltrona_id = ?', [$poltrona]);
        }
        return $righe;
    }

    /** Da quanti tick nessuno tocca questa poltrona. */
    public function silenzio(array $poltrona, int $tick): int
    {
        return max(0, $tick - (int) $poltrona['ultimo_tick_attivo']);
    }

    /** @return list<array<string,mixed>> gli altri giocatori, per il modulo di delega */
    public function altriGiocatori(int $escludi): array
    {
        return $this->db->esegui(
            'SELECT g.id, g.nome, n.nome AS nazione
             FROM sdb_giocatore g
             LEFT JOIN sdb_poltrona p ON p.giocatore_id = g.id
             LEFT JOIN sdb_nazione n ON n.id = p.nazione_id
             WHERE g.id <> ? AND g.attivo = 1 ORDER BY g.nome', [$escludi])->fetchAll();
    }
}
