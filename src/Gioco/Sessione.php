<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;

/**
 * Chi sta guardando, e se ha diritto di toccare qualcosa.
 *
 * Nessuna dipendenza esterna: sessione nativa di PHP, cookie con le protezioni
 * ragionevoli, e un gettone anti-falsificazione su ogni modulo. Le cose semplici
 * fatte bene reggono meglio di quelle complicate fatte a metà.
 */
final class Sessione
{
    /** Quanto dev'essere lunga, come minimo, una parola d'ordine. */
    public const LUNGHEZZA_MINIMA = 9;

    private ?array $giocatore = null;

    public function __construct(private readonly Basedati $db)
    {
        // Da riga di comando una sessione non ha senso e non si puo' nemmeno
        // aprire: il tick, le prove e i programmi di servizio usano questa
        // classe per leggere i giocatori, non per sapere chi sta navigando.
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_name((string) Configurazione::leggi('security.nome_sessione', 'sdb_sess'));
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']),
            ]);
            // Un identificativo di sessione che il server non ha emesso non si
            // accetta: sullo stesso dominio girano altre applicazioni, e senza
            // la modalita' rigorosa una di loro potrebbe imporne uno.
            ini_set('session.use_strict_mode', '1');
            session_start();
        }
        $id = (int) ($_SESSION['giocatore'] ?? 0);
        if ($id > 0) {
            // La sospensione vale anche per chi e' gia' dentro. Prima si
            // controllava solo all'ingresso, e un giocatore sospeso restava
            // attivo finche' non usciva da solo.
            $r = $this->db->esegui(
                'SELECT * FROM sdb_giocatore
                 WHERE id = ? AND attivo = 1 AND (sospeso_fino IS NULL OR sospeso_fino <= NOW())',
                [$id])->fetch();
            // E la sessione vale finche' vale la parola d'ordine con cui e'
            // nata: chi reimposta la password perche' teme di averla persa
            // deve chiudere fuori chiunque sia entrato con quella vecchia.
            // Prima la reimpostazione rinnovava solo la sessione di chi la
            // faceva, e le altre restavano aperte.
            if ($r !== false && !hash_equals(self::impronta((string) $r['hash_password']),
                    (string) ($_SESSION['impronta'] ?? ''))) {
                $r = false;
                unset($_SESSION['giocatore'], $_SESSION['impronta']);
            }
            $this->giocatore = $r === false ? null : $r;
        }
    }

    /** Un'impronta della parola d'ordine, da tenere in sessione al suo posto. */
    private static function impronta(string $hash): string
    {
        return hash('sha256', 'sessione|' . $hash);
    }

    public function autenticato(): bool
    {
        return $this->giocatore !== null;
    }

    /** @return array<string,mixed>|null */
    public function giocatore(): ?array
    {
        return $this->giocatore;
    }

    public function id(): int
    {
        return (int) ($this->giocatore['id'] ?? 0);
    }

    /**
     * Accendere o spegnere gli avvisi per posta.
     *
     * @return array{0:bool,1:string}
     */
    public function avvisi(bool $acceso): array
    {
        if (!$this->autenticato()) {
            return [false, 'Devi essere entrato.'];
        }
        $this->db->esegui('UPDATE sdb_giocatore SET avvisi = ? WHERE id = ?',
            [$acceso ? 1 : 0, $this->id()]);
        if ($this->giocatore !== null) {
            $this->giocatore['avvisi'] = $acceso ? 1 : 0;
        }
        return [true, $acceso
            ? 'Avvisi accesi: ti scriviamo solo quando serve.'
            : 'Avvisi spenti. Il mondo va avanti lo stesso, e non te lo dira\' nessuno.'];
    }

    public function arbitro(): bool
    {
        return ($this->giocatore['ruolo'] ?? '') === 'arbitro';
    }

    /** @return array{0:bool,1:string} esito e messaggio */
    public function registra(string $nome, string $email, string $password,
        string $invito = ''): array
    {
        // Prima di tutto: si puo' entrare? Il controllo sta in testa perche'
        // non ha senso validare un nome per poi dire che le porte sono chiuse.
        $inviti = new Inviti($this->db);
        [$puo, $perche] = $inviti->ammesso($invito);
        if (!$puo) {
            return [false, $perche];
        }

        $nome  = trim($nome);
        $email = trim(mb_strtolower($email));

        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 48) {
            return [false, 'Il nome in gioco deve stare fra 3 e 48 caratteri.'];
        }
        // Si entra col nome O con l'indirizzo, e la ricerca e' «email = ? OR
        // nome = ?»: chi si registrava col nome uguale all'indirizzo di un
        // altro gli dirottava l'accesso e il recupero della parola d'ordine.
        if (str_contains($nome, '@')) {
            return [false, 'Il nome in gioco non può contenere la chiocciola.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Indirizzo di posta non valido.'];
        }
        // Nove e non dieci: scelta dell'arbitro. Nove caratteri non sono
        // molti, e l'unica cosa che li rende accettabili e' che qui non c'e'
        // niente da rubare che valga un attacco a forza bruta — ma se un
        // giorno ci fosse, questo numero e' il primo da rialzare.
        if (mb_strlen($password) < self::LUNGHEZZA_MINIMA) {
            return [false, sprintf('La parola d\'ordine deve essere di almeno %d caratteri.',
                self::LUNGHEZZA_MINIMA)];
        }
        $esiste = $this->db->esegui(
            'SELECT 1 FROM sdb_giocatore WHERE email = ? OR nome = ? LIMIT 1', [$email, $nome])->fetchColumn();
        if ($esiste) {
            return [false, 'Nome o indirizzo già in uso.'];
        }

        // L'indirizzo si conferma prima di entrare. In un gioco dove le
        // poltrone sono poche e si tengono per mesi, un posto occupato da una
        // casella inventata e' un posto perso per tutti — ed e' un problema
        // pratico prima che di sicurezza.
        $gettone = bin2hex(random_bytes(32));
        $ore = max(1, (int) Configurazione::leggi('posta.scadenza_gettone_ore', 48));

        $id = 0;
        try {
            $id = (int) $this->db->inTransazione(function () use ($nome, $email, $password, $gettone,
                $ore, $inviti, $invito): int {
                $this->db->esegui(
                    'INSERT INTO sdb_giocatore
                        (nome, email, hash_password, creato, email_verificata, gettone_verifica,
                         gettone_scade, ip_registrazione)
                     VALUES (?,?,?,NOW(),0,?,DATE_ADD(NOW(), INTERVAL ? HOUR),?)',
                    [$nome, $email, password_hash($password, PASSWORD_DEFAULT), $gettone, $ore,
                     mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)],
                );
                $nuovo = $this->db->ultimoId();
                if (!$inviti->consuma($invito, $nuovo)) {
                    throw new \RuntimeException('invito gia\' usato');
                }
                return $nuovo;
            });
        } catch (\RuntimeException $e) {
            return [false, 'Quell\'invito è appena stato usato da qualcun altro.'];
        }

        $this->mandaVerifica($id, $nome, $email, $gettone, $ore);
        $this->avvisaArbitri($nome, $email);

        return [true, sprintf(
            'Registrazione ricevuta. Ti abbiamo scritto a %s: apri il collegamento che trovi '
            . 'nel messaggio e potrai entrare. Il collegamento vale %d ore.',
            $email, $ore)];
    }

    /** Il messaggio con il collegamento di conferma. */
    private function mandaVerifica(int $id, string $nome, string $email, string $gettone, int $ore, bool $subito = true): void
    {
        $url = rtrim((string) Configurazione::leggi('app.url_pubblico', ''), '/')
            . '/verifica?g=' . $gettone;

        $corpo = <<<TESTO
            $nome,

            qualcuno — speriamo tu — ha chiesto un accesso a Stanza dei Bottoni con questo
            indirizzo. Per confermarlo apri questo collegamento:

            $url

            Vale $ore ore. Dopo, scade e va chiesto di nuovo.

            Se non sei stato tu, non devi fare niente: senza conferma l'accesso non si apre
            e la richiesta si cancella da sola.

            --
            Stanza dei Bottoni
            TESTO;

        $posta = new \App\Nucleo\Posta($this->db);
        $testo = preg_replace('/^ {12}/m', '', $corpo) ?? $corpo;
        // Alla registrazione si spedisce subito: l'esistenza dell'account non
        // e' un segreto, e chi si e' appena iscritto aspetta la lettera. Al
        // reinvio si ACCODA: spedire solo quando l'indirizzo esiste rivelava,
        // nel tempo di risposta, chi e' iscritto; e teneva un processo di
        // Apache fermo fino a quindici secondi sul server di posta.
        $subito
            ? $posta->invia($email, 'Stanza dei Bottoni — conferma il tuo indirizzo', $testo, 'verifica', 1)
            : $posta->accoda($email, 'Stanza dei Bottoni — conferma il tuo indirizzo', $testo, 'verifica', 1);
    }

    /** Gli arbitri devono sapere chi bussa. */
    private function avvisaArbitri(string $nome, string $email): void
    {
        if (!Configurazione::leggi('arbitro.avvisa_registrazioni', true)) {
            return;
        }
        $arbitri = $this->db->esegui(
            'SELECT email FROM sdb_giocatore WHERE ruolo = "arbitro" AND attivo = 1')
            ->fetchAll(\PDO::FETCH_COLUMN);
        if ($arbitri === []) {
            return;
        }
        $posta = new \App\Nucleo\Posta($this->db);
        $quadro = rtrim((string) Configurazione::leggi('app.url_pubblico', ''), '/') . '/arbitrio';
        foreach ($arbitri as $a) {
            $posta->accoda((string) $a, 'Stanza dei Bottoni — nuova richiesta di accesso',
                "Si e' registrato:\n\n  $nome <$email>\n\n"
                . "Deve ancora confermare l'indirizzo. Lo trovi nella sezione arbitrio:\n\n  $quadro\n",
                'avviso_arbitro', 6);
        }
    }

    /**
     * La conferma dell'indirizzo.
     *
     * @return array{0:bool,1:string}
     */
    public function verifica(string $gettone): array
    {
        $gettone = trim($gettone);
        if ($gettone === '' || !preg_match('/^[0-9a-f]{64}$/', $gettone)) {
            return [false, 'Collegamento non valido.'];
        }
        $g = $this->db->esegui(
            'SELECT id, nome, gettone_scade FROM sdb_giocatore WHERE gettone_verifica = ?',
            [$gettone])->fetch();
        if ($g === false) {
            return [false, 'Questo collegamento non vale piu\': forse l\'indirizzo e\' gia\' confermato.'];
        }
        if ($g['gettone_scade'] !== null && strtotime((string) $g['gettone_scade']) < time()) {
            return [false, 'Il collegamento e\' scaduto. Chiedine un altro dalla pagina di accesso.'];
        }
        $this->db->esegui(
            'UPDATE sdb_giocatore SET email_verificata = 1, verificata_il = NOW(),
                    gettone_verifica = NULL, gettone_scade = NULL WHERE id = ?',
            [(int) $g['id']]);
        return [true, sprintf('Indirizzo confermato, %s. Adesso puoi entrare.', (string) $g['nome'])];
    }

    /**
     * «Ho dimenticato la parola d'ordine».
     *
     * Non si dice mai se l'indirizzo esiste: la risposta e' identica in tutti
     * i casi, perche' altrimenti questo modulo diventerebbe un modo comodo per
     * scoprire chi gioca. E non si puo' chiedere in continuazione — un minuto
     * fra una richiesta e l'altra basta a non trasformarlo in un modo di
     * riempire la casella di qualcuno.
     *
     * @return array{0:bool,1:string}
     */
    public function dimenticata(string $chi): array
    {
        $risposta = [true, 'Se quell\'indirizzo corrisponde a un accesso, gli abbiamo scritto. '
            . 'Il collegamento vale un\'ora.'];

        $chi = trim(mb_strtolower($chi));
        if ($chi === '') {
            return $risposta;
        }
        $g = $this->db->esegui(
            'SELECT id, nome, email, reimposta_chiesta FROM sdb_giocatore
             WHERE (email = ? OR LOWER(nome) = ?) AND attivo = 1 LIMIT 1',
            [$chi, $chi])->fetch();
        if ($g === false) {
            return $risposta;
        }
        if ($g['reimposta_chiesta'] !== null
            && strtotime((string) $g['reimposta_chiesta']) > time() - 60) {
            return $risposta;   // ha appena chiesto: si tace e non si rimanda
        }

        $gettone = bin2hex(random_bytes(32));
        $this->db->esegui(
            'UPDATE sdb_giocatore SET gettone_reimposta = ?,
                    reimposta_scade = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                    reimposta_chiesta = NOW() WHERE id = ?',
            [$gettone, (int) $g['id']]);

        $url = rtrim((string) Configurazione::leggi('app.url_pubblico', ''), '/')
            . '/reimposta?g=' . $gettone;
        $nome = (string) $g['nome'];

        $corpo = <<<TESTO
            $nome,

            qualcuno ha chiesto di reimpostare la parola d'ordine di questo accesso a
            Stanza dei Bottoni. Se sei stato tu, apri questo collegamento:

            $url

            Vale un'ora sola, e una volta usato non vale piu'.

            Se non sei stato tu, non devi fare niente: la tua parola d'ordine resta quella
            che era, e questo collegamento scade da solo.

            --
            Stanza dei Bottoni
            TESTO;

        // In coda, non subito: spedire solo quando l'account esiste rivelava
        // nel tempo di risposta chi e' iscritto. Parte al prossimo giro di
        // bin/posta.php, entro cinque minuti, con la precedenza piu' alta.
        (new \App\Nucleo\Posta($this->db))->accoda(
            (string) $g['email'], 'Stanza dei Bottoni — reimpostare la parola d\'ordine',
            preg_replace('/^ {12}/m', '', $corpo) ?? $corpo, 'reimposta', 1);

        return $risposta;
    }

    /** Il gettone e' buono? Serve a decidere se mostrare il modulo. */
    public function gettoneReimposta(string $gettone): ?array
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $gettone)) {
            return null;
        }
        $g = $this->db->esegui(
            'SELECT id, nome FROM sdb_giocatore
             WHERE gettone_reimposta = ? AND reimposta_scade > NOW() AND attivo = 1',
            [$gettone])->fetch();
        return $g === false ? null : $g;
    }

    /** @return array{0:bool,1:string} */
    public function reimposta(string $gettone, string $nuova, string $conferma): array
    {
        $g = $this->gettoneReimposta($gettone);
        if ($g === null) {
            return [false, 'Il collegamento non vale piu\': ne serve uno nuovo.'];
        }
        if ($nuova !== $conferma) {
            return [false, 'Le due parole d\'ordine non coincidono.'];
        }
        if (mb_strlen($nuova) < self::LUNGHEZZA_MINIMA) {
            return [false, sprintf('La parola d\'ordine deve essere di almeno %d caratteri.',
                self::LUNGHEZZA_MINIMA)];
        }

        $this->db->esegui(
            'UPDATE sdb_giocatore SET hash_password = ?, gettone_reimposta = NULL,
                    reimposta_scade = NULL WHERE id = ?',
            [password_hash($nuova, PASSWORD_DEFAULT), (int) $g['id']]);

        // Chi entra da qui aveva perso l'accesso: se la sessione era di
        // qualcun altro, che non lo resti. Da riga di comando — le prove — una
        // sessione non c'e', e chiederne il rinnovo emette un avvertimento.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        return [true, sprintf('Fatto, %s. Adesso entra con la parola nuova.', (string) $g['nome'])];
    }

    /** Un altro collegamento, per chi ha perso il primo. */
    public function rimandaVerifica(string $email): array
    {
        $email = trim(mb_strtolower($email));
        $g = $this->db->esegui(
            'SELECT id, nome, email_verificata FROM sdb_giocatore WHERE email = ?', [$email])->fetch();
        // Non si dice se l'indirizzo esiste: sarebbe un modo per scoprirlo.
        if ($g === false || (int) $g['email_verificata'] === 1) {
            return [true, 'Se quell\'indirizzo e\' in attesa di conferma, gli abbiamo appena riscritto.'];
        }
        $ore = max(1, (int) Configurazione::leggi('posta.scadenza_gettone_ore', 48));
        // Un freno, con la stessa risposta: al massimo una lettera ogni dieci
        // minuti per indirizzo. Senza, chiunque conoscesse un indirizzo in
        // attesa poteva farlo riscrivere all'infinito e consumare il tetto
        // giornaliero di posta — bloccando verifiche, recuperi e avvisi di
        // tutti per ventiquattr'ore. Il gettone scade a «emissione + $ore»,
        // quindi e' fresco se scade oltre «adesso + $ore - 10 minuti».
        $fresco = (bool) $this->db->esegui(
            'SELECT 1 FROM sdb_giocatore
             WHERE id = ? AND gettone_scade > DATE_ADD(NOW(), INTERVAL ? MINUTE)',
            [(int) $g['id'], $ore * 60 - 10])->fetchColumn();
        if ($fresco) {
            return [true, 'Se quell\'indirizzo e\' in attesa di conferma, gli abbiamo appena riscritto.'];
        }
        $gettone = bin2hex(random_bytes(32));
        $this->db->esegui(
            'UPDATE sdb_giocatore SET gettone_verifica = ?, gettone_scade = DATE_ADD(NOW(), INTERVAL ? HOUR)
             WHERE id = ?', [$gettone, $ore, (int) $g['id']]);
        $this->mandaVerifica((int) $g['id'], (string) $g['nome'], $email, $gettone, $ore, false);
        return [true, 'Se quell\'indirizzo e\' in attesa di conferma, gli abbiamo appena riscritto.'];
    }

    /**
     * L'accesso.
     *
     * Torna una coppia e non un booleano perche' i modi di non entrare non
     * sono equivalenti: chi ha sbagliato la parola d'ordine deve riprovare,
     * chi non ha confermato l'indirizzo deve guardare la posta, e chi e'
     * sospeso deve sapere fino a quando. Dirgli tutti e tre «credenziali
     * errate» e' il modo piu' sicuro di far andare via qualcuno che aveva
     * diritto di entrare.
     *
     * @return array{0:bool,1:string}
     */
    public function entra(string $chi, string $password): array
    {
        // Si entra con l'indirizzo o con il nome in gioco, indifferentemente:
        // chiedere di ricordare quale dei due si era usato e' una piccola
        // crudelta' gratuita, e l'indirizzo e' comunque unico.
        $chi = trim(mb_strtolower($chi));
        $r = $this->db->esegui(
            'SELECT * FROM sdb_giocatore WHERE email = ? OR LOWER(nome) = ? LIMIT 1',
            [$chi, $chi],
        )->fetch();

        // Si verifica comunque un hash fittizio, anche quando l'utente non
        // esiste: altrimenti il tempo di risposta dice se l'indirizzo e' noto.
        $hash = $r === false
            ? '$2y$12$abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTU'
            : (string) $r['hash_password'];

        if (!password_verify($password, $hash) || $r === false) {
            return [false, 'Indirizzo o parola d\'ordine non corrispondono.'];
        }

        // Da qui in poi sappiamo che e' lui: possiamo dirgli la verita'.
        if ((int) $r['attivo'] !== 1) {
            return [false, 'Questo accesso e\' stato chiuso da un arbitro.'];
        }
        if ($r['sospeso_fino'] !== null && strtotime((string) $r['sospeso_fino']) > time()) {
            return [false, sprintf('Accesso sospeso fino al %s.',
                \App\Nucleo\Calendario::dataOra((string) $r['sospeso_fino']))];
        }
        if ((int) $r['email_verificata'] !== 1) {
            return [false, 'Devi prima confermare il tuo indirizzo: il collegamento e\' nel messaggio '
                . 'che ti abbiamo mandato. Se non lo trovi, puoi chiederne un altro qui sotto.'];
        }

        session_regenerate_id(true);
        $_SESSION['giocatore'] = (int) $r['id'];
        $_SESSION['impronta']  = self::impronta((string) $r['hash_password']);
        $this->giocatore = $r;
        $this->db->esegui('UPDATE sdb_giocatore SET ultimo_accesso = NOW() WHERE id = ?', [(int) $r['id']]);
        return [true, 'Bentornato.'];
    }

    public function esci(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->giocatore = null;
    }

    // ------------------------------------------------- gettone anti-falsificazione

    public function gettone(): string
    {
        if (empty($_SESSION['gettone'])) {
            $_SESSION['gettone'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['gettone'];
    }

    public function gettoneValido(?string $dato): bool
    {
        return $dato !== null && !empty($_SESSION['gettone'])
            && hash_equals((string) $_SESSION['gettone'], $dato);
    }
}
