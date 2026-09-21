<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * La manomissione delle comunicazioni altrui.
 *
 * Intercettare e' leggere; questo e' scrivere. E' il salto che separa un
 * servizio che osserva da uno che interviene, ed e' anche il piu' pericoloso,
 * perche' una manomissione scoperta vale molto piu' danno di quello che il
 * messaggio falso poteva fare.
 *
 * Tre cose la governano, e sono tutte e tre vincoli di realta':
 *
 * 1. **Non si improvvisa.** La manipolazione si ordina prima, contro un
 *    corrispondente preciso, e resta appostata finche' i segnali non le portano
 *    qualcosa da manomettere. Non c'e' modo di reagire al singolo messaggio: i
 *    giocatori non sono collegati nello stesso momento, e i servizi veri non
 *    lavorano cosi' comunque.
 * 2. **Serve leggere prima di poter scrivere.** L'operazione scatta solo su un
 *    messaggio intercettato a livello INTEGRALE. Metadati e frammenti non
 *    bastano: non si riscrive quel che non si e' capito.
 * 3. **Chi ha scritto sa cosa ha scritto.** Il mittente vede sempre il proprio
 *    testo originale. Basta che le due parti si parlino su un altro canale —
 *    o si incontrino di persona — perche' la frode venga fuori. E' la
 *    debolezza strutturale di ogni manomissione, e qui c'e'.
 */
final class Falsificazione
{
    /** Quante operazioni di manomissione un paese puo' tenere aperte insieme. */
    public const TETTO_ATTIVE = 2;

    /** Per quanti tick resta appostata un'operazione prima di sfiorire. */
    public const DURATA = 12;

    public const MODI = [
        'inserisci'   => 'Infilare una frase nel testo autentico — il resto resta vero, ed è quel che la rende credibile',
        'sostituisci' => 'Sostituire l\'intero testo con uno preparato da noi',
        'sopprimi'    => 'Fare sparire il messaggio: non arriverà mai, e nessuno saprà perché',
    ];

    public function __construct(private readonly Basedati $db) {}

    // -- il lato del giocatore --------------------------------------------

    /** @return array{0:bool,1:string} */
    public function ordina(
        array $poltrona,
        int $bersaglioNazione,
        ?int $versoNazione,
        string $modo,
        string $testo,
        int $tick,
    ): array {
        if ((string) $poltrona['ruolo'] !== 'intelligence') {
            return [false, 'Solo l\'Intelligence può ordinare una manomissione.'];
        }
        if (!isset(self::MODI[$modo])) {
            return [false, 'Non so che cosa vorresti far fare ai nostri.'];
        }
        $nostra = (int) $poltrona['nazione_id'];
        if ($bersaglioNazione === $nostra || $versoNazione === $nostra) {
            return [false, 'Non si manomettono le proprie comunicazioni: per quelle basta non scriverle.'];
        }
        if ($bersaglioNazione <= 0) {
            return [false, 'Serve sapere di chi vogliamo riscrivere le parole.'];
        }

        $testo = trim($testo);
        if ($modo !== 'sopprimi') {
            if ($testo === '') {
                return [false, 'Un falso va scritto prima: qui non c\'è niente da far leggere.'];
            }
            if (mb_strlen($testo) > 2000) {
                return [false, 'Troppo lungo perché regga.'];
            }
        }

        $aperte = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_manipolazione WHERE nazione_id = ? AND stato = "attiva"',
            [$nostra])->fetchColumn();
        if ($aperte >= self::TETTO_ATTIVE) {
            return [false, sprintf(
                'Abbiamo già %d operazioni di questo tipo in corso. Di più non se ne reggono: '
                . 'ogni squadra appostata è una squadra che può essere presa.', $aperte)];
        }

        // Non si manomette quello che non si riesce nemmeno ad ascoltare.
        $presenza = (float) $this->db->esegui(
            'SELECT COALESCE(SUM(livello), 0) FROM sdb_presenza_intel
             WHERE nazione_id = ? AND bersaglio_id = ?', [$nostra, $bersaglioNazione])->fetchColumn();
        if ($presenza <= 0.0) {
            return [false, 'Non abbiamo niente di piantato lì dentro: prima bisogna arrivarci.'];
        }

        $this->db->esegui(
            'INSERT INTO sdb_manipolazione
                (nazione_id, ordinata_da, da_nazione_id, a_nazione_id, modo, testo,
                 usi_max, aperta_tick, scade_tick)
             VALUES (?,?,?,?,?,?,1,?,?)',
            [$nostra, (int) $poltrona['id'], $bersaglioNazione, $versoNazione,
             $modo, $modo === 'sopprimi' ? null : $testo, $tick, $tick + self::DURATA]);

        return [true, sprintf(
            'Squadra appostata sulle comunicazioni. Resterà in ascolto per %d giri: agirà sul primo '
            . 'messaggio che riusciremo a leggere per intero, e poi si ritirerà.', self::DURATA)];
    }

    /** @return list<array<string,mixed>> */
    public function nostre(int $nazione): array
    {
        return $this->db->esegui(
            'SELECT o.*, d.nome AS bersaglio, a.nome AS verso
             FROM sdb_manipolazione o
             JOIN sdb_nazione d ON d.id = o.da_nazione_id
             LEFT JOIN sdb_nazione a ON a.id = o.a_nazione_id
             WHERE o.nazione_id = ? ORDER BY o.id DESC LIMIT 12', [$nazione])->fetchAll();
    }

    /** @return array{0:bool,1:string} */
    public function revoca(int $operazione, array $poltrona): array
    {
        $n = $this->db->esegui(
            'UPDATE sdb_manipolazione SET stato = "scaduta"
             WHERE id = ? AND nazione_id = ? AND stato = "attiva"',
            [$operazione, (int) $poltrona['nazione_id']])->rowCount();
        return $n > 0
            ? [true, 'Squadra richiamata.']
            : [false, 'Quell\'operazione non è più nostra da richiamare.'];
    }

    // -- il lato del motore -----------------------------------------------

    /**
     * Prova ad applicare una manomissione a un messaggio appena letto per
     * intero. Torna il modo applicato, o null se non c'era niente in agguato.
     *
     * Chiamata dalla fase 08, subito dopo l'intercettazione integrale e prima
     * che il messaggio arrivi: e' l'unico istante in cui la cosa e' possibile.
     */
    public function applica(int $messaggio, int $spia, int $mittente, int $destinatario, int $tick): ?string
    {
        $op = $this->db->esegui(
            'SELECT * FROM sdb_manipolazione
             WHERE nazione_id = ? AND stato = "attiva" AND da_nazione_id = ?
               AND (a_nazione_id IS NULL OR a_nazione_id = ?)
               AND scade_tick >= ?
             ORDER BY a_nazione_id IS NULL, id LIMIT 1',
            [$spia, $mittente, $destinatario, $tick])->fetch();
        if ($op === false) {
            return null;
        }

        $m = $this->db->esegui('SELECT testo, testo_originale FROM sdb_messaggio WHERE id = ?',
            [$messaggio])->fetch();
        if ($m === false || $m['testo_originale'] !== null) {
            return null;   // gia' manomesso da qualcun altro: non si riscrive due volte
        }
        $originale = (string) $m['testo'];

        $modo = (string) $op['modo'];
        if ($modo === 'sopprimi') {
            $this->db->esegui(
                'UPDATE sdb_messaggio SET soppresso = 1, testo_originale = ?, falsificato_da = ?
                 WHERE id = ?', [$originale, $spia, $messaggio]);
        } else {
            $nuovo = $modo === 'sostituisci'
                ? (string) $op['testo']
                : $this->infila($originale, (string) $op['testo']);
            $this->db->esegui(
                'UPDATE sdb_messaggio SET testo = ?, testo_originale = ?, falsificato_da = ?
                 WHERE id = ?', [$nuovo, $originale, $spia, $messaggio]);
        }

        $usi = (int) $op['usi'] + 1;
        $this->db->esegui(
            'UPDATE sdb_manipolazione SET usi = ?, stato = ? WHERE id = ?',
            [$usi, $usi >= (int) $op['usi_max'] ? 'esaurita' : 'attiva', (int) $op['id']]);

        return $modo;
    }

    /**
     * La frase infilata va messa dove fa meno rumore: in fondo a un periodo
     * esistente, non in testa al testo e non attaccata all'ultima parola.
     */
    private function infila(string $originale, string $aggiunta): string
    {
        $aggiunta = rtrim(trim($aggiunta), '.') . '.';
        $pezzi = preg_split('/(?<=[.!?])\s+/u', trim($originale)) ?: [];
        if (count($pezzi) < 2) {
            return trim($originale) . ' ' . $aggiunta;
        }
        $dove = (int) floor(count($pezzi) / 2);
        array_splice($pezzi, $dove, 0, [$aggiunta]);
        return implode(' ', $pezzi);
    }

    /** Chiude le operazioni che hanno finito il loro tempo senza trovare nulla. */
    public function scadenze(int $tick): int
    {
        return $this->db->esegui(
            'UPDATE sdb_manipolazione SET stato = "scaduta" WHERE stato = "attiva" AND scade_tick < ?',
            [$tick])->rowCount();
    }
}
