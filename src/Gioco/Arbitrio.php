<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;
use App\Nucleo\Configurazione;
use App\Nucleo\Posta;

/**
 * Il banco dell'arbitro.
 *
 * Tre mestieri diversi sotto lo stesso tetto, e vale la pena tenerli distinti
 * perche' pesano in modo molto diverso.
 *
 * **Gli arrivi** — chi si e' registrato, chi non ha ancora confermato
 * l'indirizzo, chi e' rimasto in sospeso. Lavoro leggero, ma va fatto in
 * fretta o la gente se ne va.
 *
 * **Gli account** — sospendere, riattivare, chiudere, promuovere, liberare una
 * poltrona, confermare un indirizzo a mano quando la posta non arriva. Sono
 * atti su persone.
 *
 * **Le leve** — cambiare la taratura del mondo mentre il mondo gira. E' il
 * potere piu' grosso che esista in questo programma, ed e' per questo che ogni
 * atto finisce nel registro con il valore di prima accanto: in un gioco dove
 * l'arbitro puo' cambiare le regole a partita in corso, un atto non tracciato
 * e' indistinguibile da un favore.
 */
final class Arbitrio
{
    /**
     * Le leve che ha senso esporre, raggruppate.
     *
     * Non tutte: la calibrazione ha centinaia di voci e metterle tutte in una
     * pagina non e' potere, e' rumore. Queste sono quelle che cambiano il
     * carattere del mondo, e ciascuna dice a che cosa serve.
     *
     * @var array<string,array<string,string>>
     */
    public const LEVE = [
        'Il ritmo del mondo' => [
            'tempo.tick_per_anno'        => 'Quanti tick fanno un anno di gioco.',
            'crisi.pazienza_tick'        => 'Dopo quanti tick senza risposta una crisi si intende ceduta.',
        ],
        'Quanto è cattivo il mondo' => [
            'crisi.peso_paura'           => 'Quanto spaventa una guerra convenzionale in cima alla scala.',
            'crisi.peso_nucleare'        => 'Quanto spaventa la bomba. Alzandolo, le potenze nucleari non arrivano mai in fondo.',
            'crisi.peso_impegno'         => 'Quanto è caro tornare indietro dopo essere saliti.',
            'crisi.reluttanza'           => 'Quanto costa alzare il primo gradino.',
            'crisi.decadimento_nastiness' => 'Quanto in fretta il mondo dimentica la propria cattiveria.',
        ],
        'L\'economia' => [
            'economia.crescita_max_anno' => 'Tetto alla crescita annua di un paese.',
            'economia.crescita_min_anno' => 'Pavimento: quanto può crollare in un anno.',
            'economia.resa_investimento' => 'Quanto rende investire, oltre la soglia.',
            'economia.soglia_investimento' => 'Sotto questa quota il capitale si consuma più in fretta di quanto si rinnovi.',
        ],
        'Il commercio' => [
            'commercio.morso'            => 'La scala del danno di un embargo. È il numero che rende le sanzioni un\'arma o un gesto.',
            'commercio.quota_fornitore'  => 'Quanto ne paga chi l\'embargo lo impone.',
            'commercio.tetto_pressione'  => 'Si può strangolare un paese, non annientarlo: questo è il limite.',
            'commercio.durata_embargo'   => 'Quanti tick regge un embargo prima di sciogliersi.',
        ],
        'I servizi segreti' => [
            'intelligence.concentrazione_mirata' => 'Quanto rende concentrare i mezzi su un solo corrispondente. È il numero che rende possibile falsificare un messaggio.',
            'intelligence.decadimento_copertura' => 'Quanto in fretta si perde una presenza se non la si alimenta.',
        ],
        'Chi puo\' entrare' => [
            'gioco.registrazioni' => 'aperte, invito o chiuse. Non e\' un numero: si scrive la parola.',
        ],
        'Le relazioni' => [
            'relazioni.recupero_integrita_anno' => 'Quanti punti di faccia si riguadagnano in un anno.',
            'relazioni.consumo_spinta_anno'     => 'Quanto in fretta il mondo dimentica chi ha vinto una crisi.',
            'linee.soglia_affinita'             => 'Quanta affinità serve perché l\'apparato accetti una linea diretta.',
        ],
    ];

    public function __construct(private readonly Basedati $db) {}

    // ------------------------------------------------------------- gli arrivi

    /** @return list<array<string,mixed>> */
    public function arrivi(int $quanti = 40): array
    {
        return $this->db->esegui(
            'SELECT g.id, g.nome, g.email, g.ruolo, g.attivo, g.email_verificata, g.creato,
                    g.verificata_il, g.ultimo_accesso, g.ip_registrazione, g.sospeso_fino,
                    g.nota_arbitro,
                    p.id AS poltrona_id, p.ruolo AS poltrona_ruolo, n.nome AS nazione
             FROM sdb_giocatore g
             LEFT JOIN sdb_poltrona p ON p.giocatore_id = g.id
             LEFT JOIN sdb_nazione  n ON n.id = p.nazione_id
             ORDER BY g.creato DESC LIMIT ' . (int) $quanti)->fetchAll();
    }

    /** @return array{in_attesa:int,attivi:int,sospesi:int,chiusi:int} */
    public function conteggi(): array
    {
        $r = $this->db->esegui(
            'SELECT
                SUM(email_verificata = 0 AND attivo = 1) AS in_attesa,
                SUM(email_verificata = 1 AND attivo = 1
                    AND (sospeso_fino IS NULL OR sospeso_fino < NOW())) AS attivi,
                SUM(sospeso_fino IS NOT NULL AND sospeso_fino >= NOW()) AS sospesi,
                SUM(attivo = 0) AS chiusi
             FROM sdb_giocatore')->fetch();
        return array_map(static fn($v) => (int) $v, (array) $r);
    }

    // ------------------------------------------------------------ gli account

    /** @return array{0:bool,1:string} */
    public function suGiocatore(int $arbitro, int $giocatore, string $atto, string $argomento, int $tick): array
    {
        if ($giocatore === $arbitro && in_array($atto, ['chiudi', 'sospendi', 'degrada'], true)) {
            return [false, 'Non puoi farlo a te stesso: se sbagli, resti fuori e non c\'è nessuno a riaprirti.'];
        }
        $g = $this->db->esegui('SELECT * FROM sdb_giocatore WHERE id = ?', [$giocatore])->fetch();
        if ($g === false) {
            return [false, 'Quel giocatore non c\'è.'];
        }

        [$fatto, $detto] = match ($atto) {
            'verifica'  => $this->verificaAMano($giocatore),
            'rimanda'   => $this->rimandaVerifica($g),
            'sospendi'  => $this->sospendi($giocatore, max(1, (int) $argomento)),
            'riattiva'  => $this->riattiva($giocatore),
            'chiudi'    => $this->chiudi($giocatore),
            'riapri'    => $this->riapri($giocatore),
            'promuovi'  => $this->cambiaRuolo($giocatore, 'arbitro'),
            'degrada'   => $this->cambiaRuolo($giocatore, 'giocatore'),
            'libera'    => $this->liberaPoltrona($giocatore),
            'nota'      => $this->annota($giocatore, $argomento),
            'email'     => $this->cambiaEmail($giocatore, $argomento),
            default     => [false, 'Atto sconosciuto.'],
        };

        if ($fatto) {
            $this->registra($arbitro, 'account', (string) $g['nome'],
                sprintf('%s%s', $atto, $argomento !== '' ? " ($argomento)" : ''), $tick);
        }
        return [$fatto, $detto];
    }

    private function verificaAMano(int $id): array
    {
        $this->db->esegui(
            'UPDATE sdb_giocatore SET email_verificata = 1, verificata_il = NOW(),
                    gettone_verifica = NULL, gettone_scade = NULL WHERE id = ?', [$id]);
        return [true, 'Indirizzo dato per buono. Adesso può entrare.'];
    }

    /** @param array<string,mixed> $g */
    private function rimandaVerifica(array $g): array
    {
        if ((int) $g['email_verificata'] === 1) {
            return [false, 'Ha già confermato.'];
        }
        (new Sessione($this->db))->rimandaVerifica((string) $g['email']);
        return [true, 'Gli abbiamo riscritto.'];
    }

    private function sospendi(int $id, int $giorni): array
    {
        $this->db->esegui(
            'UPDATE sdb_giocatore SET sospeso_fino = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?',
            [$giorni, $id]);
        return [true, "Sospeso per $giorni giorni. Glielo diremo quando prova a entrare."];
    }

    private function riattiva(int $id): array
    {
        $this->db->esegui('UPDATE sdb_giocatore SET sospeso_fino = NULL WHERE id = ?', [$id]);
        return [true, 'Sospensione tolta.'];
    }

    private function chiudi(int $id): array
    {
        $this->db->esegui('UPDATE sdb_giocatore SET attivo = 0 WHERE id = ?', [$id]);
        return [true, 'Accesso chiuso. La poltrona resta sua finché non la liberi.'];
    }

    private function riapri(int $id): array
    {
        $this->db->esegui('UPDATE sdb_giocatore SET attivo = 1 WHERE id = ?', [$id]);
        return [true, 'Accesso riaperto.'];
    }

    private function cambiaRuolo(int $id, string $ruolo): array
    {
        $this->db->esegui('UPDATE sdb_giocatore SET ruolo = ? WHERE id = ?', [$ruolo, $id]);
        return [true, $ruolo === 'arbitro' ? 'Ora siede al banco.' : 'Torna fra i giocatori.'];
    }

    private function liberaPoltrona(int $id): array
    {
        $n = $this->db->esegui(
            'UPDATE sdb_poltrona SET giocatore_id = NULL, delega_a = NULL, delega_dal_tick = NULL
             WHERE giocatore_id = ?', [$id])->rowCount();
        return $n > 0
            ? [true, 'Poltrona liberata: l\'apparato la riprende in mano e chiunque può occuparla.']
            : [false, 'Non occupava nessuna poltrona.'];
    }

    private function annota(int $id, string $nota): array
    {
        $this->db->esegui('UPDATE sdb_giocatore SET nota_arbitro = ? WHERE id = ?',
            [mb_substr($nota, 0, 255), $id]);
        return [true, 'Annotato.'];
    }

    private function cambiaEmail(int $id, string $email): array
    {
        $email = trim(mb_strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Indirizzo non valido.'];
        }
        $preso = $this->db->esegui('SELECT 1 FROM sdb_giocatore WHERE email = ? AND id <> ?',
            [$email, $id])->fetchColumn();
        if ($preso) {
            return [false, 'Quell\'indirizzo è già di qualcun altro.'];
        }
        $this->db->esegui('UPDATE sdb_giocatore SET email = ? WHERE id = ?', [$email, $id]);
        return [true, 'Indirizzo cambiato.'];
    }

    // --------------------------------------------------------------- le leve

    /** @return array<string,mixed> le sovrascritture in essere */
    public function leve(): array
    {
        $per = [];
        foreach ($this->db->esegui('SELECT * FROM sdb_leva')->fetchAll() as $r) {
            $per[(string) $r['chiave']] = [
                'valore'  => json_decode((string) $r['valore'], true),
                'prima'   => $r['valore_prima'] === null ? null : json_decode((string) $r['valore_prima'], true),
                'quando'  => $r['cambiata_il'],
                'nota'    => $r['nota'],
            ];
        }
        return $per;
    }

    /** Le leve pronte da passare a Calibrazione::imponiLeve(). @return array<string,mixed> */
    public function leveImposte(): array
    {
        $per = [];
        foreach ($this->db->esegui('SELECT chiave, valore FROM sdb_leva')->fetchAll() as $r) {
            $per[(string) $r['chiave']] = json_decode((string) $r['valore'], true);
        }
        return $per;
    }

    /** @return array{0:bool,1:string} */
    public function muoviLeva(int $arbitro, string $chiave, string $valore, Calibrazione $cal, int $tick): array
    {
        if (!$this->levaNota($chiave)) {
            return [false, 'Quella leva non è fra quelle che si possono muovere da qui.'];
        }
        $valore = trim($valore);
        if ($valore === '') {
            return $this->rimettiLeva($arbitro, $chiave, $tick);
        }
        // Quasi tutte le leve sono numeri, ma non tutte: le registrazioni sono
        // una parola fra tre, e costringerla in un numero sarebbe una cifratura
        // inutile che l'arbitro dovrebbe tenere a mente.
        if ($chiave === 'gioco.registrazioni') {
            if (!isset(Inviti::MODI[$valore])) {
                return [false, 'Le registrazioni possono essere: '
                    . implode(', ', array_keys(Inviti::MODI)) . '.'];
            }
            $prima = (string) Configurazione::leggi('gioco.registrazioni', 'invito');
            $this->db->esegui(
                'INSERT INTO sdb_leva (chiave, valore, valore_prima, cambiata_da, nota)
                 VALUES (?,?,?,?,"")
                 ON DUPLICATE KEY UPDATE valore = VALUES(valore), cambiata_da = VALUES(cambiata_da),
                                         cambiata_il = NOW()',
                [$chiave, json_encode($valore), json_encode($prima), $arbitro]);
            $this->registra($arbitro, 'leva', $chiave, "da $prima a $valore", $tick);
            return [true, sprintf('Registrazioni: %s. %s', $valore, Inviti::MODI[$valore])];
        }

        if (!is_numeric($valore)) {
            return [false, 'Per ora da qui si muovono solo numeri.'];
        }
        $nuovo = str_contains($valore, '.') ? (float) $valore : (int) $valore;
        $prima = $cal->leggi($chiave, null);

        if ($prima !== null && is_numeric($prima)) {
            // Una leva mossa di dieci volte non e' una taratura, e' un'altra
            // partita: si puo' fare, ma non per sbaglio con uno zero di troppo.
            $rapporto = abs((float) $prima) > 1e-9 ? abs((float) $nuovo / (float) $prima) : 1.0;
            if ($rapporto > 10.0 || ($rapporto < 0.1 && abs((float) $nuovo) > 1e-9)) {
                return [false, sprintf(
                    'Da %s a %s è un salto di oltre dieci volte. Se è voluto, arrivaci in due passi: '
                    . 'così non ci si arriva per uno zero di troppo.',
                    (string) $prima, (string) $nuovo)];
            }
        }

        $this->db->esegui(
            'INSERT INTO sdb_leva (chiave, valore, valore_prima, cambiata_da, nota)
             VALUES (?,?,?,?,"")
             ON DUPLICATE KEY UPDATE valore = VALUES(valore), cambiata_da = VALUES(cambiata_da),
                                     cambiata_il = NOW()',
            [$chiave, json_encode($nuovo), json_encode($prima), $arbitro]);

        $this->registra($arbitro, 'leva', $chiave,
            sprintf('da %s a %s', var_export($prima, true), var_export($nuovo, true)), $tick);

        return [true, sprintf('%s: da %s a %s. Vale dal prossimo tick.',
            $chiave, var_export($prima, true), var_export($nuovo, true))];
    }

    /** @return array{0:bool,1:string} */
    public function rimettiLeva(int $arbitro, string $chiave, int $tick): array
    {
        $n = $this->db->esegui('DELETE FROM sdb_leva WHERE chiave = ?', [$chiave])->rowCount();
        if ($n === 0) {
            return [false, 'Quella leva non era stata mossa.'];
        }
        $this->registra($arbitro, 'leva', $chiave, 'rimessa al valore del file', $tick);
        return [true, "$chiave torna al valore che ha nei file."];
    }

    private function levaNota(string $chiave): bool
    {
        foreach (self::LEVE as $gruppo) {
            if (isset($gruppo[$chiave])) {
                return true;
            }
        }
        return false;
    }

    // ------------------------------------------------------------ il registro

    public function registra(int $arbitro, string $genere, string $bersaglio,
        string $dettaglio, int $tick): void
    {
        $this->db->esegui(
            'INSERT INTO sdb_atto_arbitro (arbitro_id, genere, bersaglio, dettaglio, tick)
             VALUES (?,?,?,?,?)',
            [$arbitro, $genere, mb_substr($bersaglio, 0, 190), $dettaglio, $tick]);
    }

    /** @return list<array<string,mixed>> */
    public function atti(int $quanti = 40): array
    {
        return $this->db->esegui(
            'SELECT a.*, g.nome AS arbitro FROM sdb_atto_arbitro a
             JOIN sdb_giocatore g ON g.id = a.arbitro_id
             ORDER BY a.id DESC LIMIT ' . (int) $quanti)->fetchAll();
    }

    // ------------------------------------------------------------ il sistema

    /** @return array<string,mixed> */
    public function salute(int $tick): array
    {
        $posta = new Posta($this->db);
        $ultimo = $this->db->esegui(
            'SELECT MAX(tick) AS t FROM sdb_mondo_stato')->fetchColumn();
        return [
            'tick'        => (int) $ultimo,
            'posta'       => $posta->stato(),
            'messaggi'    => (int) $this->db->esegui('SELECT COUNT(*) FROM sdb_messaggio')->fetchColumn(),
            'eventi'      => (int) $this->db->esegui('SELECT COUNT(*) FROM sdb_evento')->fetchColumn(),
            'crisi_aperte' => (int) $this->db->esegui(
                'SELECT COUNT(*) FROM sdb_crisi WHERE stato = "aperta"')->fetchColumn(),
            'guerre'      => (int) $this->db->esegui(
                'SELECT COUNT(*) FROM sdb_guerra WHERE fine_tick IS NULL')->fetchColumn(),
            'strozzature' => (int) $this->db->esegui('SELECT COUNT(*) FROM sdb_strozzatura')->fetchColumn(),
            'salvataggio' => $this->salvataggio(),
        ];
    }

    /**
     * Quando e' stato fatto l'ultimo salvataggio, e quanto e' vecchio.
     *
     * Sta qui perche' e' la cosa che si guarda meno e che si rimpiange di
     * piu'. Un salvataggio vecchio di tre giorni si nota solo se qualcuno lo
     * mette davanti agli occhi.
     *
     * @return array{quando:?string,ore:?int,quanti:int,peso:string}
     */
    private function salvataggio(): array
    {
        // Dove stanno i salvataggi: la variabile d'ambiente vince, poi la
        // configurazione, poi la casa dell'utente. Un percorso inciso nel
        // codice renderebbe il programma non installabile altrove.
        $dove = getenv('SDB_BACKUP')
            ?: (string) Configurazione::leggi('salvataggi.cartella',
                (getenv('HOME') ?: sys_get_temp_dir()) . '/backup-stanzadeibottoni');
        $file = glob($dove . '/db-*.sql.gz') ?: [];
        if ($file === []) {
            return ['quando' => null, 'ore' => null, 'quanti' => 0, 'peso' => '—'];
        }
        usort($file, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        $quando = filemtime($file[0]);
        $peso = 0;
        foreach ($file as $f) {
            $peso += (int) filesize($f);
        }
        return [
            'quando' => \App\Nucleo\Calendario::daIstante($quando, true),
            'ore'    => (int) floor((time() - $quando) / 3600),
            'quanti' => count($file),
            'peso'   => number_format($peso / 1048576, 1, ',', '.') . ' MB',
        ];
    }

    /**
     * La moderazione: le ultime conversazioni, in chiaro.
     *
     * L'arbitro le puo' leggere, e il documento sul Canale lo dice a chiare
     * lettere fin dall'inizio: il testo sta in chiaro nel database proprio
     * perche' qualcuno deve poter intervenire. Chi gioca lo sa.
     *
     * @return list<array<string,mixed>>
     */
    public function conversazioni(int $quante = 40): array
    {
        return $this->db->esegui(
            'SELECT m.id, m.tick, m.sicurezza, m.testo, m.testo_originale, m.soppresso,
                    gd.nome AS da_giocatore, nd.nome AS da_paese, pd.ruolo AS da_ruolo,
                    ga.nome AS a_giocatore, na.nome AS a_paese, pa.ruolo AS a_ruolo
             FROM sdb_messaggio m
             JOIN sdb_poltrona pd ON pd.id = m.da_poltrona
             JOIN sdb_nazione  nd ON nd.id = pd.nazione_id
             LEFT JOIN sdb_giocatore gd ON gd.id = pd.giocatore_id
             JOIN sdb_poltrona pa ON pa.id = m.a_poltrona
             JOIN sdb_nazione  na ON na.id = pa.nazione_id
             LEFT JOIN sdb_giocatore ga ON ga.id = pa.giocatore_id
             WHERE pd.giocatore_id IS NOT NULL OR pa.giocatore_id IS NOT NULL
             ORDER BY m.id DESC LIMIT ' . (int) $quante)->fetchAll();
    }
}
