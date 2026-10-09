<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * Quel che un giocatore puo' sapere delle operazioni: le proprie, e quelle
 * altrui che il suo servizio ha scoperto.
 *
 * PERCHE' ESISTE. Un giocatore da solo all'Intelligence iraniana ha dato due
 * ordini di sabotaggio contro Israele: l'apparato li ha controfirmati al posto
 * del Capo, sono partiti, il servizio israeliano li ha scoperti e fermati — e
 * lui non l'ha mai saputo. La scrivania mostrava solo gli ordini in attesa, e
 * i quattro livelli di conoscenza che il motore tiene per ogni servizio non li
 * mostrava a nessuno. Il motore sapeva tutto; il sito taceva (docs/29).
 *
 * Qui si dice soltanto cio' che chi guarda PUO' sapere. Di un'operazione
 * propria: se e' partita, se e' arrivata a segno o e' stata fermata, e se il
 * mondo l'ha attribuita a qualcuno — non chi l'ha vista, che e' affare dei
 * servizi altrui. Di un'operazione altrui: quello che il proprio servizio ne
 * ha capito, gradino per gradino, compreso un mandante che puo' essere quello
 * sbagliato.
 */
final class Operazioni
{
    /** Le regioni del seme, per il secondo gradino di conoscenza. */
    public const REGIONI = [
        'AFR' => 'Africa',                  'ASC' => 'Russia e Asia centrale',
        'ASE' => 'Asia orientale',          'ASS' => 'Asia meridionale',
        'CAM' => 'America centrale e Caraibi', 'EUR' => 'Europa',
        'MEO' => 'Medio Oriente',           'NAM' => 'America settentrionale',
        'OCE' => 'Oceania',                 'SAM' => 'America meridionale',
    ];

    public const DOMINI = [
        'soc' => 'diplomazia', 'eco' => 'economia', 'info' => 'informazione',
        'int' => 'operazione coperta', 'mil' => 'militare', 'nuc' => 'nucleare',
    ];

    public function __construct(private readonly Basedati $db) {}

    /**
     * Gli ordini di questa poltrona, dal piu' recente, con il loro destino.
     *
     * @return list<array<string,mixed>>
     */
    public function registro(int $poltrona): array
    {
        $righe = $this->db->esegui(
            'SELECT o.id, o.verbo, o.stato, o.creato_tick, o.scade_tick, o.richiede_controfirma,
                    o.firmato_da_apparato, o.intensita, o.copertura, o.evento_id,
                    b.nome AS bersaglio, n.nome AS nazione,
                    e.stato AS esito, e.maturazione_tick, e.chiuso_tick, e.intensita AS intensita_effettiva,
                    g.nome AS firmatario
             FROM sdb_ordine o
             JOIN sdb_nazione b ON b.id = o.bersaglio_id
             JOIN sdb_nazione n ON n.id = o.nazione_id
             LEFT JOIN sdb_evento e ON e.id = o.evento_id
             LEFT JOIN sdb_giocatore g ON g.id = o.controfirmato_da
             WHERE o.poltrona_id = ?
             ORDER BY o.id DESC LIMIT 20', [$poltrona])->fetchAll();

        $attribuzioni = $this->attribuzioni(array_values(array_filter(
            array_map(static fn(array $r): int => (int) $r['evento_id'], $righe))));
        foreach ($righe as &$r) {
            $r['attribuzioni'] = $attribuzioni[(int) $r['evento_id']] ?? [];
        }
        unset($r);
        return $righe;
    }

    /**
     * Chi ha attribuito pubblicamente queste operazioni, e a chi. Lo scandalo
     * e' pubblico, e il mandante vero lo legge come tutti — anche quando il
     * nome fatto e' un altro, perche' la falsa bandiera ha funzionato.
     *
     * @param list<int> $eventi
     * @return array<int,list<array{chi:string,accusato:string}>>
     */
    private function attribuzioni(array $eventi): array
    {
        if ($eventi === []) {
            return [];
        }
        $segnaposto = implode(',', array_fill(0, count($eventi), '?'));
        $esito = [];
        foreach ($this->db->esegui(
            "SELECT tick, dati FROM sdb_notizia
              WHERE genere = 'scandalo' AND JSON_VALUE(dati, '$.evento') IN ($segnaposto)
              ORDER BY tick", $eventi)->fetchAll() as $n) {
            $d = json_decode((string) $n['dati'], true) ?: [];
            $esito[(int) ($d['evento'] ?? 0)][] = [
                'chi'      => (string) ($d['chi'] ?? '—'),
                'accusato' => (string) ($d['mandante'] ?? '—'),
                'tick'     => (int) $n['tick'],
            ];
        }
        return $esito;
    }

    /**
     * Cio' che il servizio di questa nazione sa delle operazioni altrui: quelle
     * in corso, e quelle chiuse o capite nelle ultime venti settimane.
     *
     * @return list<array<string,mixed>>
     */
    public function quadro(int $nazione, int $tick): array
    {
        $da = $tick - 20;
        return $this->db->esegui(
            'SELECT c.livello, c.primo_tick, c.aggiornata_tick,
                    e.id AS evento, e.dominio, e.verbo, e.stato, e.maturazione_tick, e.chiuso_tick,
                    e.impronta, e.bersaglio_id, b.nome AS bersaglio, r.codice AS regione,
                    a.nome AS accusato,
                    k.scelta, k.stato AS contromossa_stato, k.esito AS contromossa_esito
             FROM sdb_conoscenza c
             JOIN sdb_evento e ON e.id = c.evento_id
             LEFT JOIN sdb_nazione b ON b.id = e.bersaglio_id
             LEFT JOIN sdb_regione r ON r.id = b.regione_id
             LEFT JOIN sdb_nazione a ON a.id = c.accusato_id
             LEFT JOIN sdb_contromossa k ON k.nazione_id = c.osservatore_id AND k.evento_id = e.id
             WHERE c.osservatore_id = ?
               AND (e.stato = "in_volo" OR COALESCE(e.chiuso_tick, e.maturazione_tick) >= ?
                    OR c.aggiornata_tick >= ?)
             ORDER BY (e.stato = "in_volo") DESC, c.aggiornata_tick DESC, e.id DESC
             LIMIT 40', [$nazione, $da, $da])->fetchAll();
    }

    /**
     * Le operazioni proprie arrivate in fondo dopo un certo giro: per gli
     * avvisi, che le dicono a chi non era alla scrivania.
     *
     * @return list<array<string,mixed>>
     */
    public function concluseDopo(int $poltrona, int $tick): array
    {
        return $this->db->esegui(
            'SELECT o.verbo, b.nome AS bersaglio, e.stato AS esito, e.chiuso_tick
             FROM sdb_ordine o
             JOIN sdb_evento e ON e.id = o.evento_id
             JOIN sdb_nazione b ON b.id = o.bersaglio_id
             WHERE o.poltrona_id = ? AND e.chiuso_tick IS NOT NULL AND e.chiuso_tick > ?
             ORDER BY e.chiuso_tick', [$poltrona, $tick])->fetchAll();
    }

    /** Chi vede il quadro del servizio: chi lo dirige, e chi dirige il governo. */
    public static function vedeIlQuadro(string $ruolo): bool
    {
        return in_array($ruolo, ['intelligence', 'capo'], true);
    }
}
