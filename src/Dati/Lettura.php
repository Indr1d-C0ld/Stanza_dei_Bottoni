<?php

declare(strict_types=1);

namespace App\Dati;

use App\Nucleo\Basedati;

/**
 * Le interrogazioni in sola lettura per il web.
 *
 * Tenuta separata dal Deposito, che scrive: chi legge non deve nemmeno avere
 * a disposizione i metodi per sbagliare.
 */
final class Lettura
{
    public function __construct(private readonly Basedati $db) {}

    public function mondoAvviato(): bool
    {
        try {
            $s = $this->db->esegui('SELECT COALESCE(MAX(tick), -1) FROM sdb_mondo_stato');
            return (int) $s->fetchColumn() >= 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<string,mixed>|null */
    public function statoMondo(): ?array
    {
        $s = $this->db->esegui('SELECT * FROM sdb_mondo_stato ORDER BY tick DESC LIMIT 1');
        $r = $s->fetch();
        return $r === false ? null : $r;
    }

    /** @return list<array<string,mixed>> */
    public function nazioni(int $tick, string $ordine = 'influenza_totale', int $quante = 0): array
    {
        $consentiti = ['influenza_totale', 'pil', 'legittimita', 'qualita_vita', 'net_peace', 'nome'];
        $ordine = in_array($ordine, $consentiti, true) ? $ordine : 'influenza_totale';
        $verso = $ordine === 'nome' ? 'ASC' : 'DESC';
        $limite = $quante > 0 ? ' LIMIT ' . $quante : '';
        return $this->db->esegui(
            "SELECT n.codice, n.nome, n.giocabile, n.valore_prestigio, n.maturita,
                    r.codice AS regione, s.*
             FROM sdb_nazione_stato s
             JOIN sdb_nazione n ON n.id = s.nazione_id
             JOIN sdb_regione r ON r.id = n.regione_id
             WHERE s.tick = ?
             ORDER BY $ordine $verso, n.nome ASC$limite",
            [$tick],
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function nazione(string $codice, int $tick): ?array
    {
        $r = $this->db->esegui(
            'SELECT n.*, r.codice AS regione, i.nome AS ideologia, s.*
             FROM sdb_nazione n
             JOIN sdb_regione r ON r.id = n.regione_id
             JOIN sdb_ideologia i ON i.id = n.ideologia_id
             LEFT JOIN sdb_nazione_stato s ON s.nazione_id = n.id AND s.tick = ?
             WHERE n.codice = ?',
            [$tick, $codice],
        )->fetch();
        return $r === false ? null : $r;
    }

    /** @return list<array<string,mixed>> */
    public function relazioni(string $codice, int $quante = 14): array
    {
        return $this->db->esegui(
            'SELECT b.codice, b.nome, rel.umore, rel.affinita, rel.obbligo, rel.sfera
             FROM sdb_relazione rel
             JOIN sdb_nazione a ON a.id = rel.da_nazione_id
             JOIN sdb_nazione b ON b.id = rel.a_nazione_id
             WHERE a.codice = ?
             ORDER BY ABS(rel.affinita) DESC, b.nome ASC
             LIMIT ' . (int) $quante,
            [$codice],
        )->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function gabinetto(string $codice): array
    {
        return $this->db->esegui(
            'SELECT p.* FROM sdb_poltrona p
             JOIN sdb_nazione n ON n.id = p.nazione_id
             WHERE n.codice = ? ORDER BY p.potere DESC',
            [$codice],
        )->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function fazioni(string $codice): array
    {
        return $this->db->esegui(
            'SELECT f.* FROM sdb_fazione f
             JOIN sdb_nazione n ON n.id = f.nazione_id
             WHERE n.codice = ? ORDER BY f.forza DESC',
            [$codice],
        )->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function cronaca(int $quante = 40, ?string $genere = null): array
    {
        $dove = $genere !== null ? ' WHERE genere = ?' : '';
        $par  = $genere !== null ? [$genere] : [];
        return $this->db->esegui(
            'SELECT * FROM sdb_notizia' . $dove . ' ORDER BY tick DESC, id DESC LIMIT ' . (int) $quante,
            $par,
        )->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function guerre(): array
    {
        return $this->db->esegui(
            'SELECT g.*, a.nome AS aggressore, d.nome AS difensore, a.codice AS cod_a, d.codice AS cod_d
             FROM sdb_guerra g
             JOIN sdb_nazione a ON a.id = g.aggressore_id
             JOIN sdb_nazione d ON d.id = g.difensore_id
             ORDER BY g.fine_tick IS NULL DESC, g.inizio_tick DESC LIMIT 12',
        )->fetchAll();
    }
}
