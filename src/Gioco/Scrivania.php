<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Dati\Gabinetto;
use App\Nucleo\Basedati;

/**
 * La Scrivania: che poltrona occupi, che cosa puoi ordinare, che cosa ti si
 * chiede oggi.
 *
 * Due principi di progetto passano da qui.
 *
 * Il primo: **ti arriva solo il tuo dominio**. Il gabinetto esiste perché il
 * mondo è troppo grande per una persona sola, e se ognuno vedesse tutto non
 * servirebbe a niente essere in otto.
 *
 * Il secondo: **le controfirme**. Le cose che contano non le decide una
 * poltrona sola, ed è da lì che nasce la politica interna: chi ha bisogno della
 * firma di chi, e a che prezzo.
 */
final class Scrivania
{
    /** Chi deve firmare insieme a chi, per dominio. */
    private const CONTROFIRME = [
        'int'  => 'capo',    'info' => 'capo',
        'mil'  => 'capo',    'nuc'  => 'capo',
    ];

    /** I verbi che, pur nel loro dominio, restano alla sola firma di chi li propone. */
    private const SENZA_CONTROFIRMA = ['emissario', 'condanna_pubblica', 'mediazione', 'investimenti'];

    public function __construct(
        private readonly Basedati $db,
        private readonly int $ordiniPerTick = 2,
    ) {}

    // ------------------------------------------------------------- poltrone

    /**
     * La poltrona a cui questo giocatore è seduto adesso.
     *
     * Di norma è la sua. Se però qualcuno gli ha affidato la propria mentre non
     * c'è, può scegliere di sedersi lì: la chiave 'per_delega' dice se sta
     * agendo in casa propria o in casa d'altri, e da quella dipende come
     * verranno firmate le sue decisioni.
     *
     * @return array<string,mixed>|null
     */
    public function poltronaDi(int $giocatore, ?int $scelta = null): ?array
    {
        if ($scelta !== null && $scelta > 0) {
            $r = $this->db->esegui(
                'SELECT p.*, n.codice, n.nome AS nazione, g.nome AS titolare_vero
                 FROM sdb_poltrona p
                 JOIN sdb_nazione n ON n.id = p.nazione_id
                 LEFT JOIN sdb_giocatore g ON g.id = p.giocatore_id
                 WHERE p.id = ? AND (p.giocatore_id = ? OR p.delega_a = ?)',
                [$scelta, $giocatore, $giocatore])->fetch();
            if ($r !== false) {
                $r['per_delega'] = (int) $r['giocatore_id'] !== $giocatore;
                // Chi siede davvero: e' il titolare, o il delegato. Serve per
                // lasciare la SUA firma negli atti, che e' quel che docs/20
                // promette («la firma resta sua accanto alla tua»).
                $r['agente_id'] = $giocatore;
                return $r;
            }
        }
        $r = $this->db->esegui(
            'SELECT p.*, n.codice, n.nome AS nazione FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id
             WHERE p.giocatore_id = ?', [$giocatore])->fetch();
        if ($r === false) {
            return null;
        }
        $r['per_delega'] = false;
        $r['agente_id'] = $giocatore;
        return $r;
    }

    /** @return list<array<string,mixed>> */
    public function poltroneLibere(): array
    {
        return $this->db->esegui(
            'SELECT p.id, p.ruolo, p.nome AS titolare, p.potere, n.codice, n.nome AS nazione,
                    s.legittimita, s.influenza_totale
             FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id
             LEFT JOIN sdb_nazione_stato s
                    ON s.nazione_id = n.id AND s.tick = (SELECT MAX(tick) FROM sdb_mondo_stato)
             WHERE p.giocatore_id IS NULL
             ORDER BY s.influenza_totale DESC, n.nome, p.ruolo')->fetchAll();
    }

    /** @return array{0:bool,1:string} */
    public function occupa(int $giocatore, int $poltrona, int $tick = 0): array
    {
        if ($this->poltronaDi($giocatore) !== null) {
            return [false, 'Occupi già una poltrona: lasciala prima di prenderne un\'altra.'];
        }
        $r = $this->db->esegui(
            'SELECT p.*, n.nome AS nazione FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id WHERE p.id = ?', [$poltrona])->fetch();
        if ($r === false) {
            return [false, 'Quella poltrona non esiste.'];
        }
        if ($r['giocatore_id'] !== null) {
            return [false, 'Qualcuno è arrivato prima.'];
        }
        $preso = $this->db->esegui(
            'UPDATE sdb_poltrona SET giocatore_id = ?, delega_a = NULL, delega_dal_tick = NULL,
                    reclutata_da = NULL, reclutata_tick = NULL, sospettata = 0, ultimo_tick_attivo = ?
             WHERE id = ? AND giocatore_id IS NULL',
            [$giocatore, $tick, $poltrona])->rowCount();
        // Due richieste che arrivano insieme sulla stessa poltrona: prima la
        // seconda si sentiva dire «hai preso posto» e riceveva perfino le
        // agende, anche se l'UPDATE non aveva toccato niente.
        if ($preso === 0) {
            return [false, 'Qualcuno è arrivato prima.'];
        }

        // Due agende private, che nessuno conosce tranne chi le riceve.
        $catalogo = @include dirname(__DIR__, 2) . '/calibrazione/agende.php';
        if (is_array($catalogo)) {
            $fresca = $this->poltronaDi($giocatore);
            if ($fresca !== null) {
                (new Agende($this->db, $catalogo))->assegna($fresca, $tick);
            }
        }

        return [true, sprintf('Hai preso posto: %s, %s. Il titolare uscente era %s.',
            Gabinetto::RUOLI[$r['ruolo']] ?? $r['ruolo'], $r['nazione'], $r['nome'])];
    }

    /**
     * Chi lascia la poltrona chiude i propri conti.
     *
     * Prima si azzerava soltanto il titolare, e tre cose restavano appese.
     * La DELEGA: il delegato continuava a sedere su una poltrona senza
     * titolare, e il giocatore successivo se lo trovava in casa senza averlo
     * mai scelto. La TALPA: `reclutata_da` restava sulla poltrona, e il nuovo
     * arrivato era una spia straniera senza aver mai accettato niente. Gli
     * ORDINI in volo, che partivano al giro successivo a nome di chi non c'era
     * piu'. Le agende aperte si chiudono come fallite: sono sue, e se ne va.
     */
    public function lascia(int $giocatore): void
    {
        $mie = $this->db->esegui('SELECT id FROM sdb_poltrona WHERE giocatore_id = ?', [$giocatore])->fetchAll();
        foreach ($mie as $p) {
            $id = (int) $p['id'];
            $this->db->esegui(
                'UPDATE sdb_ordine SET stato = "annullato"
                 WHERE poltrona_id = ? AND giocatore_id = ? AND stato IN ("in_attesa","firmato")',
                [$id, $giocatore]);
            $this->db->esegui(
                'UPDATE sdb_agenda SET stato = "fallita", chiusa_tick = COALESCE(
                    (SELECT MAX(tick) FROM sdb_mondo_stato), 0)
                 WHERE poltrona_id = ? AND giocatore_id = ? AND stato = "aperta"',
                [$id, $giocatore]);
        }
        $this->db->esegui(
            'UPDATE sdb_poltrona SET giocatore_id = NULL, delega_a = NULL, delega_dal_tick = NULL,
                    reclutata_da = NULL, reclutata_tick = NULL, sospettata = 0
             WHERE giocatore_id = ?', [$giocatore]);
    }

    /** @return list<array<string,mixed>> i colleghi di gabinetto */
    public function colleghi(int $nazione): array
    {
        return $this->db->esegui(
            'SELECT p.*, g.nome AS giocatore FROM sdb_poltrona p
             LEFT JOIN sdb_giocatore g ON g.id = p.giocatore_id
             WHERE p.nazione_id = ? ORDER BY p.potere DESC', [$nazione])->fetchAll();
    }

    // -------------------------------------------------------------- ordini

    /**
     * I verbi che questa poltrona può proporre.
     *
     * @param array<string,array<string,mixed>> $catalogo
     * @return array<string,array<string,mixed>>
     */
    public function verbiPossibili(string $ruolo, array $catalogo): array
    {
        $possibili = [];
        foreach ($catalogo as $verbo => $d) {
            $competente = Gabinetto::DOMINIO_DI[$d['dominio']] ?? 'staff';
            // Il Capo può proporre in qualunque dominio; gli altri solo nel proprio.
            if ($ruolo === 'capo' || $ruolo === $competente) {
                $possibili[$verbo] = $d + ['controfirma' => $this->controfirmaRichiesta($ruolo, $verbo, $d)];
            }
        }
        return $possibili;
    }

    /** @param array<string,mixed> $d */
    public function controfirmaRichiesta(string $ruolo, string $verbo, array $d): ?string
    {
        if (in_array($verbo, self::SENZA_CONTROFIRMA, true)) {
            return null;
        }
        $dominio = (string) $d['dominio'];
        $competente = Gabinetto::DOMINIO_DI[$dominio] ?? 'staff';
        $secondo = self::CONTROFIRME[$dominio] ?? null;
        if ($secondo === null) {
            return null;
        }
        // Chi propone non può controfirmarsi da solo: se è il Capo a proporre,
        // la seconda firma spetta alla poltrona competente, e viceversa.
        return $ruolo === $secondo ? $competente : $secondo;
    }

    /**
     * @param array<string,mixed> $poltrona
     * @param array<string,mixed> $definizione
     * @return array{0:bool,1:string}
     */
    public function ordina(array $poltrona, array $definizione, string $verbo,
        int $bersaglio, int $intensita, int $copertura, int $tick): array
    {
        $giaDati = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_ordine
             WHERE poltrona_id = ? AND creato_tick = ? AND stato <> "annullato"',
            [(int) $poltrona['id'], $tick])->fetchColumn();
        if ($giaDati >= $this->ordiniPerTick) {
            return [false, sprintf('Hai già impartito %d ordini in questo giro: il prossimo al giro d\'orologio.',
                $giaDati)];
        }

        $controfirma = $this->controfirmaRichiesta((string) $poltrona['ruolo'], $verbo, $definizione);
        $this->db->esegui(
            'INSERT INTO sdb_ordine
                (giocatore_id, poltrona_id, nazione_id, verbo, bersaglio_id, intensita, copertura,
                 richiede_controfirma, stato, creato_tick, scade_tick, creato, firmato_per_delega_da)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?)',
            [
                (int) $poltrona['giocatore_id'], (int) $poltrona['id'], (int) $poltrona['nazione_id'],
                // Il modulo arriva fino a 90: il servizio si allinea, invece di
                // lasciar passare un 100 che il gioco non prevede.
                $verbo, $bersaglio, max(1, min(100, $intensita)), max(0, min(90, $copertura)),
                $controfirma, $controfirma === null ? 'firmato' : 'in_attesa',
                $tick, $tick + 4,
                // La colonna esiste dalla migrazione 0013 e nessuno la
                // scriveva: il delegato agiva e negli atti restava solo il
                // titolare, contro quel che docs/20 promette.
                ($poltrona['per_delega'] ?? false) ? (int) ($poltrona['agente_id'] ?? 0) : null,
            ],
        );
        return [true, $controfirma === null
            ? 'Ordine impartito: partirà al prossimo giro d\'orologio.'
            : 'Ordine predisposto: manca la firma di ' . (Gabinetto::RUOLI[$controfirma] ?? $controfirma) . '.'];
    }

    /** @return list<array<string,mixed>> gli ordini che aspettano la MIA firma */
    public function daControfirmare(array $poltrona): array
    {
        return $this->db->esegui(
            'SELECT o.*, b.nome AS bersaglio, g.nome AS proponente, p.ruolo AS ruolo_proponente
             FROM sdb_ordine o
             JOIN sdb_nazione b ON b.id = o.bersaglio_id
             JOIN sdb_giocatore g ON g.id = o.giocatore_id
             JOIN sdb_poltrona p ON p.id = o.poltrona_id
             WHERE o.nazione_id = ? AND o.stato = "in_attesa" AND o.richiede_controfirma = ?
             ORDER BY o.id', [(int) $poltrona['nazione_id'], (string) $poltrona['ruolo']])->fetchAll();
    }

    /** @return list<array<string,mixed>> i miei ordini in corso */
    public function mieiOrdini(int $giocatore): array
    {
        return $this->db->esegui(
            'SELECT o.*, b.nome AS bersaglio FROM sdb_ordine o
             JOIN sdb_nazione b ON b.id = o.bersaglio_id
             WHERE (o.giocatore_id = ? OR o.firmato_per_delega_da = ?)
               AND o.stato IN ("in_attesa","firmato")
             ORDER BY o.id DESC LIMIT 12', [$giocatore, $giocatore])->fetchAll();
    }

    /**
     * Controfirma. «Nessuno puo' controfirmarsi da solo» (docs/15) — e con la
     * delega si poteva: chi aveva in mano il Capo per delega e sedeva lui
     * stesso all'Informazione proponeva da una poltrona e firmava dall'altra.
     * Adesso chi firma davvero non puo' essere chi ha proposto, in nessuna
     * delle due vesti; e negli atti resta chi ha firmato davvero.
     */
    public function firma(int $ordine, array $poltrona): bool
    {
        $chi = (int) ($poltrona['agente_id'] ?? $poltrona['giocatore_id']);
        $s = $this->db->esegui(
            'UPDATE sdb_ordine SET stato = "firmato", controfirmato_da = ?, controfirmato_il = NOW()
             WHERE id = ? AND stato = "in_attesa" AND nazione_id = ? AND richiede_controfirma = ?
               AND giocatore_id <> ? AND COALESCE(firmato_per_delega_da, 0) <> ?',
            [$chi, $ordine, (int) $poltrona['nazione_id'], (string) $poltrona['ruolo'], $chi, $chi],
        );
        return $s->rowCount() > 0;
    }

    public function annulla(int $ordine, int $giocatore): bool
    {
        $s = $this->db->esegui(
            'UPDATE sdb_ordine SET stato = "annullato"
             WHERE id = ? AND (giocatore_id = ? OR firmato_per_delega_da = ?)
               AND stato IN ("in_attesa","firmato")',
            [$ordine, $giocatore, $giocatore]);
        return $s->rowCount() > 0;
    }
}
