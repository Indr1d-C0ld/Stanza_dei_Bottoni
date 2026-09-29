<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * Che cosa fa un servizio di un'operazione altrui che ha scoperto.
 *
 * PERCHE' ESISTE. Il gioco promette che «chi scopre in tempo puo' fermarlo,
 * negarlo, farlo trapelare o usarlo», e fino a qui le contromosse erano tutte
 * automatiche: la fase 01 decideva da sola se il servizio sventava, e il
 * giocatore seduto all'Intelligence non aveva niente da scegliere (docs/29).
 *
 * Quattro scelte, dal terzo gradino in su — quando si sa almeno contro chi:
 *
 *   sventa     concentrare i mezzi per fermarla: piu' probabile del lavoro
 *              ordinario dell'apparato, e si tenta a ogni giro finche' vola;
 *   sorveglia  lasciarla correre e seguirla: non la si ferma, ma si arriva
 *              prima al quarto gradino, il nome di chi l'ha ordinata;
 *   trapela    darla alla stampa: al quarto gradino e' uno scandalo per chi
 *              il servizio accusa (anche quando accusa quello sbagliato); al
 *              terzo e' la notizia di un'operazione in corso di mano ignota,
 *              e chi l'ha avviata spesso la abbandona;
 *   avvisa     dirlo al paese colpito: il suo servizio sa quel che sappiamo noi,
 *              e se ne ricorda.
 *
 * Quando la sicurezza di un paese ha una persona al tavolo — l'Intelligence o
 * il Capo — la macchina non sventa piu' da sola le operazioni contro di lui:
 * decide la persona, e il silenzio e' una scelta come le altre.
 */
final class Contromosse
{
    public const SCELTE = [
        'sventa'    => 'Sventarla',
        'sorveglia' => 'Lasciarla correre e seguirla',
        'trapela'   => 'Farla trapelare',
        'avvisa'    => 'Avvisare il paese colpito',
    ];

    public const SPIEGAZIONI = [
        'sventa'    => 'Concentriamo i mezzi per fermarla prima che arrivi a segno. Si tenta a ogni giro finché è in volo.',
        'sorveglia' => 'Non la fermiamo: la seguiamo per arrivare al nome di chi l\'ha ordinata.',
        'trapela'   => 'La diamo alla stampa. Se sappiamo chi è stato è uno scandalo per lui; se no, chi l\'ha avviata spesso la abbandona.',
        'avvisa'    => 'Lo diciamo al paese colpito: il suo servizio saprà quel che sappiamo noi, e se ne ricorderà.',
    ];

    public function __construct(private readonly Basedati $db) {}

    /**
     * Le scelte possibili su un'operazione del quadro del servizio.
     *
     * @param array<string,mixed> $k una riga di Operazioni::quadro()
     * @return list<string>
     */
    public static function possibili(array $k, int $nostra): array
    {
        $livello = (int) $k['livello'];
        if ($livello < 3) {
            return [];   // finche' non si sa contro chi, non c'e' niente da fare
        }
        $inVolo = $k['stato'] === 'in_volo';
        $controNoi = (int) $k['bersaglio_id'] === $nostra;
        $scelte = [];
        // Un'azione dichiarata (impronta alta) non si sventa: si subisce.
        if ($inVolo && $controNoi && (int) $k['impronta'] < 60) {
            $scelte[] = 'sventa';
            $scelte[] = 'sorveglia';
        }
        if ($inVolo && !$controNoi) {
            $scelte[] = 'avvisa';
        }
        if ($inVolo || $livello >= 4) {
            $scelte[] = 'trapela';
        }
        return $scelte;
    }

    /** @return array{0:bool,1:string} */
    public function scegli(array $poltrona, int $evento, string $scelta, int $tick): array
    {
        if (!Operazioni::vedeIlQuadro((string) $poltrona['ruolo'])) {
            return [false, 'Le contromosse le decide chi dirige il servizio, o il Capo.'];
        }
        if (!isset(self::SCELTE[$scelta])) {
            return [false, 'Quella non è una scelta.'];
        }
        $nazione = (int) $poltrona['nazione_id'];
        $k = $this->db->esegui(
            'SELECT c.livello, e.stato, e.bersaglio_id, e.impronta,
                    k.stato AS contromossa_stato
             FROM sdb_conoscenza c
             JOIN sdb_evento e ON e.id = c.evento_id
             LEFT JOIN sdb_contromossa k ON k.nazione_id = c.osservatore_id AND k.evento_id = e.id
             WHERE c.osservatore_id = ? AND c.evento_id = ?', [$nazione, $evento])->fetch();
        if ($k === false) {
            return [false, 'Il nostro servizio non ne sa niente.'];
        }
        if (($k['contromossa_stato'] ?? '') === 'conclusa') {
            return [false, 'Su quell\'operazione abbiamo già fatto la nostra mossa.'];
        }
        if (!in_array($scelta, self::possibili($k, $nazione), true)) {
            return [false, (int) $k['livello'] < 3
                ? 'Non sappiamo ancora abbastanza: serve almeno sapere contro chi è diretta.'
                : 'Su quell\'operazione, adesso, quella mossa non si può fare.'];
        }
        $this->db->esegui(
            'INSERT INTO sdb_contromossa (nazione_id, evento_id, scelta, giocatore_id, tick)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE scelta = VALUES(scelta), giocatore_id = VALUES(giocatore_id),
                                     tick = VALUES(tick), esito = NULL',
            [$nazione, $evento, $scelta, (int) ($poltrona['agente_id'] ?? $poltrona['giocatore_id']), $tick]);

        return [true, match ($scelta) {
            'sventa'    => 'Il servizio si concentra su quell\'operazione: da questo giro prova a fermarla.',
            'sorveglia' => 'La lasciamo correre, e la seguiamo fino a chi l\'ha ordinata.',
            'trapela'   => 'Al prossimo giro la notizia sarà sui giornali.',
            'avvisa'    => 'Al prossimo giro il paese colpito saprà quel che sappiamo noi.',
        }];
    }

    /**
     * Le contromosse ancora attive, per il motore.
     *
     * @return array<string,array<int,array<string,mixed>>> iso => evento => riga
     */
    public static function attive(Basedati $db): array
    {
        $esito = [];
        foreach ($db->esegui(
            'SELECT k.*, n.codice AS iso FROM sdb_contromossa k
             JOIN sdb_nazione n ON n.id = k.nazione_id
             WHERE k.stato = "attiva"')->fetchAll() as $r) {
            $esito[(string) $r['iso']][(int) $r['evento_id']] = $r;
        }
        return $esito;
    }

    public static function concludi(Basedati $db, int $id, string $esito, int $tick): void
    {
        $db->esegui('UPDATE sdb_contromossa SET stato = "conclusa", esito = ?, esito_tick = ? WHERE id = ?',
            [mb_substr($esito, 0, 64), $tick, $id]);
    }

    public static function annotaEsito(Basedati $db, int $id, string $esito): void
    {
        $db->esegui('UPDATE sdb_contromossa SET esito = ? WHERE id = ?', [mb_substr($esito, 0, 64), $id]);
    }
}
