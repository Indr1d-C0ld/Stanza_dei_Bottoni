<?php

declare(strict_types=1);

namespace App\Nucleo;

use PDO;
use PDOException;
use RuntimeException;

/** Accesso a MariaDB. Connessione pigra: in esecuzione a vuoto non si apre. */
final class Basedati
{
    private ?PDO $pdo = null;

    /** @param array<string,mixed> $impostazioni */
    public function __construct(private readonly array $impostazioni) {}

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) ($this->impostazioni['host'] ?? '127.0.0.1'),
            (int)    ($this->impostazioni['port'] ?? 3306),
            (string) ($this->impostazioni['name'] ?? ''),
            (string) ($this->impostazioni['charset'] ?? 'utf8mb4'),
        );
        try {
            $this->pdo = new PDO(
                $dsn,
                (string) ($this->impostazioni['user'] ?? ''),
                (string) ($this->impostazioni['pass'] ?? ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ],
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Connessione alla base dati fallita: ' . $e->getMessage(), 0, $e);
        }
        return $this->pdo;
    }

    /** L'identificativo dell'ultima riga inserita. */
    public function ultimoId(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    /** @param array<string|int,mixed> $parametri */
    public function esegui(string $sql, array $parametri = []): \PDOStatement
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($parametri);
        return $stmt;
    }

    /**
     * Esegue il blocco in una transazione — o dentro quella gia' aperta.
     *
     * PDO non annida le transazioni: aprirne una mentre un'altra e' in corso
     * lancia un'eccezione. Serve che si possano comporre — il tick intero sta
     * in una transazione sola, e le fasi che dentro ne chiedono una devono
     * semplicemente farne parte; le prove girano dentro una transazione che si
     * annulla, e i servizi che ne aprono una devono unirsi a quella. Chi ha
     * aperto la transazione esterna decide se confermare o annullare.
     */
    public function inTransazione(callable $blocco): mixed
    {
        $pdo = $this->pdo();
        if ($pdo->inTransaction()) {
            return $blocco($this);
        }
        $pdo->beginTransaction();
        try {
            $esito = $blocco($this);
            $pdo->commit();
            return $esito;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
