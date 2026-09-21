<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * Le agende private: assegnazione e verifica.
 *
 * Il principio è che il modello deve poter dire da solo se un'agenda è stata
 * raggiunta. Un obiettivo che solo un arbitro umano può giudicare non è un
 * obiettivo: è un suggerimento narrativo, e non regge a lungo.
 */
final class Agende
{
    public function __construct(
        private readonly Basedati $db,
        /** @var array<string,array<string,mixed>> */
        private readonly array $catalogo,
    ) {}

    /** @return list<array<string,mixed>> le agende di un giocatore, con il testo composto */
    public function di(int $giocatore): array
    {
        $righe = $this->db->esegui(
            'SELECT a.*, n.nome AS paese FROM sdb_agenda a
             LEFT JOIN sdb_nazione n ON n.id = a.bersaglio_id
             WHERE a.giocatore_id = ? ORDER BY a.id', [$giocatore])->fetchAll();

        foreach ($righe as &$r) {
            $d = $this->catalogo[$r['codice']] ?? null;
            $r['titolo'] = $d['titolo'] ?? $r['codice'];
            $r['peso']   = $d['peso'] ?? 1;
            $r['segreta'] = (bool) ($d['segreta'] ?? false);
            $r['testo']  = strtr((string) ($d['testo'] ?? ''), [
                '{paese}'  => (string) ($r['paese'] ?? '—'),
                '{soglia}' => rtrim(rtrim(number_format((float) ($r['soglia'] ?? 0), 1, ',', ''), '0'), ','),
            ]);
        }
        return $righe;
    }

    /**
     * Due agende all'ingresso, pescate fra quelle compatibili col ruolo.
     *
     * @param array<string,mixed> $poltrona
     */
    public function assegna(array $poltrona, int $tick): void
    {
        $giocatore = (int) $poltrona['giocatore_id'];
        $gia = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_agenda WHERE giocatore_id = ? AND poltrona_id = ?',
            [$giocatore, (int) $poltrona['id']])->fetchColumn();
        if ($gia > 0) {
            return;
        }

        $ruolo = (string) $poltrona['ruolo'];
        $ammesse = [];
        foreach ($this->catalogo as $codice => $d) {
            if ($d['ruoli'] === [] || in_array($ruolo, $d['ruoli'], true)) {
                $ammesse[] = $codice;
            }
        }
        shuffle($ammesse);

        // Paesi plausibili come bersaglio: quelli con cui il nostro ha un
        // rapporto intenso, in un senso o nell'altro.
        $candidati = $this->db->esegui(
            'SELECT b.id, r.affinita FROM sdb_relazione r
             JOIN sdb_nazione b ON b.id = r.a_nazione_id
             WHERE r.da_nazione_id = ? ORDER BY ABS(r.affinita) DESC LIMIT 12',
            [(int) $poltrona['nazione_id']])->fetchAll();

        foreach (array_slice($ammesse, 0, 2) as $codice) {
            $d = $this->catalogo[$codice];
            $bersaglio = null;
            $soglia = null;

            if (str_contains((string) $d['testo'], '{paese}')) {
                $ostile = str_contains($codice, 'caduta') || str_contains($codice, 'contenimento')
                    || str_contains($codice, 'discredito');
                foreach ($candidati as $c) {
                    $adatto = $ostile ? ((float) $c['affinita'] < 0) : ((float) $c['affinita'] > 0);
                    if ($adatto) {
                        $bersaglio = (int) $c['id'];
                        break;
                    }
                }
                $bersaglio ??= (int) ($candidati[0]['id'] ?? 0);
                if ($bersaglio === 0) {
                    continue;
                }
            }
            if (str_contains((string) $d['testo'], '{soglia}')) {
                $soglia = $this->sogliaPer($codice, (int) $poltrona['nazione_id']);
            }

            $this->db->esegui(
                'INSERT INTO sdb_agenda (giocatore_id, poltrona_id, codice, bersaglio_id, soglia, assegnata_tick)
                 VALUES (?,?,?,?,?,?)',
                [$giocatore, (int) $poltrona['id'], $codice, $bersaglio, $soglia, $tick],
            );
        }
    }

    /** Una soglia ambiziosa ma non assurda: si parte da dove si è. */
    private function sogliaPer(string $codice, int $nazione): float
    {
        $s = $this->db->esegui(
            'SELECT influenza_totale, qualita_vita, quota_militare FROM sdb_nazione_stato
             WHERE nazione_id = ? ORDER BY tick DESC LIMIT 1', [$nazione])->fetch();
        return match ($codice) {
            'influenza' => round(max(0.5, (float) ($s['influenza_totale'] ?? 1) * 1.25), 2),
            'benessere' => min(10, (int) ($s['qualita_vita'] ?? 5) + 1),
            'riarmo'    => round(max(2.0, (float) ($s['quota_militare'] ?? 0.02) * 100 * 1.4), 1),
            default     => 0,
        };
    }

    /**
     * La verifica, a ogni tick. Ogni condizione è una domanda che il modello
     * sa porsi da solo.
     */
    public function verifica(int $tick): int
    {
        $aperte = $this->db->esegui(
            'SELECT a.*, p.nazione_id, p.ruolo, p.potere FROM sdb_agenda a
             JOIN sdb_poltrona p ON p.id = a.poltrona_id
             WHERE a.stato = "aperta"')->fetchAll();
        if ($aperte === []) {
            return 0;
        }

        $chiuse = 0;
        foreach ($aperte as $a) {
            $s = $this->db->esegui(
                'SELECT * FROM sdb_nazione_stato WHERE nazione_id = ? ORDER BY tick DESC LIMIT 1',
                [(int) $a['nazione_id']])->fetch();
            if ($s === false) {
                continue;
            }
            $esito = $this->valuta((string) $a['codice'], $a, $s, $tick);
            if ($esito === null) {
                continue;
            }
            $this->db->esegui('UPDATE sdb_agenda SET stato = ?, chiusa_tick = ? WHERE id = ?',
                [$esito ? 'riuscita' : 'fallita', $tick, (int) $a['id']]);
            $chiuse++;
        }
        return $chiuse;
    }

    /**
     * @param array<string,mixed> $a l'agenda
     * @param array<string,mixed> $s lo stato della nazione
     * @return bool|null null = ancora aperta
     */
    private function valuta(string $codice, array $a, array $s, int $tick): ?bool
    {
        $bersaglio = $a['bersaglio_id'] !== null ? (int) $a['bersaglio_id'] : null;
        $statoBersaglio = $bersaglio !== null
            ? $this->db->esegui('SELECT * FROM sdb_nazione_stato WHERE nazione_id = ? ORDER BY tick DESC LIMIT 1',
                [$bersaglio])->fetch()
            : false;

        return match ($codice) {
            'influenza' => (float) $s['influenza_totale'] >= (float) $a['soglia'] ? true : null,
            'benessere' => (int) $s['qualita_vita'] >= (int) $a['soglia'] ? true : null,
            'riarmo'    => (float) $s['quota_militare'] * 100 >= (float) $a['soglia'] ? true : null,
            'pace'      => (int) $s['net_peace'] >= 6 ? false : null,
            'stabilita_interna' => (int) $s['cambi_irregolari'] > 0 ? false : null,
            'nessuno_scandalo'  => (int) $s['scandali_subiti'] > 0 ? false : null,
            'prima_poltrona'    => $this->primaPoltrona($a),
            'governo_caduto'    => $statoBersaglio !== false
                && (int) $statoBersaglio['cambi_esecutivo'] > 0 ? true : null,
            'governo_in_piedi'  => $statoBersaglio !== false
                && (int) $statoBersaglio['cambi_irregolari'] > 0 ? false : null,
            'influenza_altrui_sotto' => $statoBersaglio !== false
                && (float) $statoBersaglio['influenza_totale'] >= 3.0 ? false : null,
            'amicizia'  => $this->affinita((int) $a['nazione_id'], $bersaglio) > 80.0 ? true : null,
            'discredito' => $this->attribuzioneOttenuta((int) $a['nazione_id'], $bersaglio) ? true : null,
            default     => null,
        };
    }

    /** @param array<string,mixed> $a */
    private function primaPoltrona(array $a): ?bool
    {
        $migliore = $this->db->esegui(
            'SELECT id FROM sdb_poltrona WHERE nazione_id = ? AND ruolo <> "capo"
             ORDER BY potere DESC LIMIT 1', [(int) $a['nazione_id']])->fetchColumn();
        return (int) $migliore === (int) $a['poltrona_id'] ? true : null;
    }

    private function affinita(int $da, ?int $a): float
    {
        if ($a === null) {
            return 0.0;
        }
        return (float) $this->db->esegui(
            'SELECT affinita FROM sdb_relazione WHERE da_nazione_id = ? AND a_nazione_id = ?',
            [$da, $a])->fetchColumn();
    }

    private function attribuzioneOttenuta(int $osservatore, ?int $mandante): bool
    {
        if ($mandante === null) {
            return false;
        }
        return (bool) $this->db->esegui(
            'SELECT 1 FROM sdb_conoscenza c JOIN sdb_evento e ON e.id = c.evento_id
             WHERE c.osservatore_id = ? AND c.livello >= 4 AND e.mandante_id = ?
             AND e.dominio IN ("int","info") LIMIT 1',
            [$osservatore, $mandante])->fetchColumn();
    }
}
