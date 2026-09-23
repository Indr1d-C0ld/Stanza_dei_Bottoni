<?php

declare(strict_types=1);

namespace App\Nucleo;

/**
 * La coda della posta in uscita.
 *
 * Il Postino sa parlare SMTP; questa sa cosa fare quando l'SMTP non risponde.
 * La differenza conta: senza coda, un messaggio di verifica perduto lascia
 * l'utente fuori dal gioco senza che nessuno se ne accorga, perche' senza
 * indirizzo confermato non si entra.
 *
 * Tre regole:
 *
 * - ogni messaggio entra in coda **prima** di essere tentato: se il processo
 *   muore a meta', il messaggio resta;
 * - se non riesce si riprova con attesa crescente — 1, 5, 15, 60, 180, 360
 *   minuti — fino al massimo dei tentativi, e poi si rinuncia dicendolo;
 * - il tetto giornaliero del fornitore si rispetta contando gli invii riusciti
 *   nelle ultime ventiquattr'ore, non sperando che bastino.
 */
final class Posta
{
    /** Attese fra un tentativo e l'altro, in minuti. */
    private const ATTESE = [1, 5, 15, 60, 180, 360];

    /**
     * Trasporto alternativo, SOLO per le prove automatiche.
     *
     * La configurazione vera punta a un relay che manda posta davvero: una
     * prova non deve poter svegliare la casella di nessuno.
     *
     * @var null|callable(string,string,string):array{ok:bool,errore?:string}
     */
    private static $finzione = null;

    public function __construct(private readonly Basedati $db) {}

    public static function trasportoDiProva(?callable $f): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new \RuntimeException('Il trasporto di prova esiste solo da riga di comando.');
        }
        self::$finzione = $f;
    }

    /** @return array{ok:bool, id:int, errore?:string} */
    public function invia(string $a, string $oggetto, string $corpo,
        string $genere = 'generico', int $priorita = 5): array
    {
        $id = $this->accoda($a, $oggetto, $corpo, $genere, $priorita);
        $r  = $this->tenta($id);
        return ['ok' => $r['ok'], 'id' => $id]
            + ($r['ok'] ? [] : ['errore' => $r['errore'] ?? 'invio rimandato']);
    }

    public function accoda(string $a, string $oggetto, string $corpo,
        string $genere = 'generico', int $priorita = 5): int
    {
        $this->db->esegui(
            'INSERT INTO sdb_posta (destinatario, oggetto, corpo, genere, priorita)
             VALUES (?,?,?,?,?)',
            [$a, mb_substr($oggetto, 0, 190), $corpo, $genere, max(1, min(9, $priorita))]);
        return (int) $this->db->ultimoId();
    }

    /** @return array{ok:bool, errore?:string, rinunciato?:bool} */
    public function tenta(int $id): array
    {
        $m = $this->db->esegui('SELECT * FROM sdb_posta WHERE id = ?', [$id])->fetch();
        if ($m === false || $m['inviato_il'] !== null || $m['rinunciato_il'] !== null) {
            return ['ok' => false, 'errore' => 'niente da fare'];
        }

        if ($this->tettoRaggiunto()) {
            // Non e' colpa del messaggio: si rimanda senza bruciare un tentativo.
            $this->db->esegui(
                'UPDATE sdb_posta SET prossimo_il = DATE_ADD(NOW(), INTERVAL 30 MINUTE),
                        ultimo_errore = ? WHERE id = ?',
                ['tetto giornaliero del fornitore raggiunto', $id]);
            return ['ok' => false, 'errore' => 'tetto giornaliero raggiunto'];
        }

        // Si PRENDE il messaggio prima di spedirlo: un aggiornamento solo, che
        // riesce a uno solo. Senza, un giro di bin/posta.php che si
        // sovrapponeva al precedente (o all'invio immediato dal sito) leggeva
        // la stessa riga ancora da spedire, e la stessa mail partiva due volte.
        // La presa scade da sola fra dieci minuti, se il processo muore.
        $preso = $this->db->esegui(
            'UPDATE sdb_posta SET prossimo_il = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
              WHERE id = ? AND inviato_il IS NULL AND rinunciato_il IS NULL AND prossimo_il <= NOW()',
            [$id])->rowCount();
        if ($preso !== 1) {
            return ['ok' => false, 'errore' => 'già in lavorazione'];
        }

        $r = self::$finzione !== null
            ? (self::$finzione)((string) $m['destinatario'], (string) $m['oggetto'], (string) $m['corpo'])
            : Postino::manda((string) $m['destinatario'], (string) $m['oggetto'], (string) $m['corpo']);

        if ($r['ok']) {
            $this->db->esegui(
                'UPDATE sdb_posta SET inviato_il = NOW(), tentativi = tentativi + 1,
                        ultimo_errore = NULL WHERE id = ?', [$id]);
            return ['ok' => true];
        }

        $tentativi = (int) $m['tentativi'] + 1;
        $massimo   = max(1, (int) Configurazione::leggi('posta.massimo_tentativi', 6));
        $errore    = mb_substr((string) ($r['errore'] ?? 'errore sconosciuto'), 0, 255);

        if ($tentativi >= $massimo) {
            $this->db->esegui(
                'UPDATE sdb_posta SET tentativi = ?, rinunciato_il = NOW(), ultimo_errore = ? WHERE id = ?',
                [$tentativi, $errore, $id]);
            return ['ok' => false, 'errore' => $errore, 'rinunciato' => true];
        }

        $attesa = self::ATTESE[min($tentativi - 1, count(self::ATTESE) - 1)];
        $this->db->esegui(
            'UPDATE sdb_posta SET tentativi = ?, prossimo_il = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                    ultimo_errore = ? WHERE id = ?',
            [$tentativi, $attesa, $errore, $id]);
        return ['ok' => false, 'errore' => $errore];
    }

    /** @return array{tentati:int,inviati:int,rinunciati:int} */
    public function smista(?int $quanti = null): array
    {
        $quanti ??= max(1, (int) Configurazione::leggi('posta.per_giro', 8));
        $righe = $this->db->esegui(
            'SELECT id FROM sdb_posta
              WHERE inviato_il IS NULL AND rinunciato_il IS NULL AND prossimo_il <= NOW()
              ORDER BY priorita, id LIMIT ' . (int) $quanti)->fetchAll();

        $esito = ['tentati' => 0, 'inviati' => 0, 'rinunciati' => 0];
        foreach ($righe as $r) {
            $x = $this->tenta((int) $r['id']);
            $esito['tentati']++;
            if ($x['ok']) {
                $esito['inviati']++;
            } elseif (!empty($x['rinunciato'])) {
                $esito['rinunciati']++;
            }
        }
        return $esito;
    }

    public function inviate24h(): int
    {
        return (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_posta WHERE inviato_il >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')
            ->fetchColumn();
    }

    public function tettoRaggiunto(): bool
    {
        $tetto = (int) Configurazione::leggi('posta.tetto_24h', 280);
        return $tetto > 0 && $this->inviate24h() >= $tetto;
    }

    /** @return array{in_coda:int,inviate_24h:int,rinunciate:int,tetto:int} */
    public function stato(): array
    {
        return [
            'in_coda' => (int) $this->db->esegui(
                'SELECT COUNT(*) FROM sdb_posta WHERE inviato_il IS NULL AND rinunciato_il IS NULL')
                ->fetchColumn(),
            'inviate_24h' => $this->inviate24h(),
            'rinunciate'  => (int) $this->db->esegui(
                'SELECT COUNT(*) FROM sdb_posta WHERE rinunciato_il IS NOT NULL')->fetchColumn(),
            'tetto' => (int) Configurazione::leggi('posta.tetto_24h', 280),
        ];
    }

    /** I messaggi vecchi non servono piu' a nessuno. */
    public function pota(int $giorni = 30): int
    {
        return $this->db->esegui(
            'DELETE FROM sdb_posta
              WHERE (inviato_il IS NOT NULL OR rinunciato_il IS NOT NULL)
                AND creato_il < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT 1000',
            [max(1, $giorni)])->rowCount();
    }

    /** @return list<array<string,mixed>> per l'arbitro */
    public function ultime(int $quante = 30): array
    {
        return $this->db->esegui(
            'SELECT id, destinatario, oggetto, genere, tentativi, inviato_il, rinunciato_il,
                    ultimo_errore, creato_il
             FROM sdb_posta ORDER BY id DESC LIMIT ' . (int) $quante)->fetchAll();
    }
}
