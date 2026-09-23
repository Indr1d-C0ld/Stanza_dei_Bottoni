<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;
use App\Nucleo\Posta;

/**
 * Gli avvisi di gioco, per posta.
 *
 * Questo e' un mondo che gira a tick di due ore e i giocatori non stanno
 * collegati ad aspettarlo. Una crisi che aspetta la tua risposta scade in tre
 * tick — sei ore — e se nessuno te lo dice hai ceduto senza saperlo. Una
 * poltrona lasciata sola passa all'apparato dopo sei tick, e al rientro trovi
 * il conto di decisioni che non hai preso.
 *
 * Il rischio opposto e' la molestia: un gioco che scrive a ogni giro smette di
 * essere letto in tre giorni. Quindi valgono tre regole.
 *
 * **Si scrive solo se c'e' qualcosa da dire**, e il messaggio le dice tutte
 * insieme — un riepilogo, non un avviso per evento.
 *
 * **Non piu' di uno ogni tanti tick.** La soglia sta in configurazione; a
 * dodici tick e' uno ogni ventiquattr'ore di orologio vero.
 *
 * **Chi non li vuole li spegne**, e resta spento finche' non li riaccende.
 */
final class Avvisi
{
    public function __construct(private readonly Basedati $db) {}

    /**
     * Guarda chi ha qualcosa in sospeso e, se e' il caso, glielo scrive.
     *
     * @return array{guardati:int,scritti:int}
     */
    public function manda(int $tick): array
    {
        $ogni = max(1, (int) Configurazione::leggi('posta.avviso_ogni_tick', 12));
        $giocatori = $this->db->esegui(
            'SELECT g.id, g.nome, g.email, g.ultimo_avviso_tick,
                    p.id AS poltrona_id, p.ruolo, p.nazione_id, p.ultimo_tick_attivo,
                    p.delega_a, n.nome AS nazione
             FROM sdb_giocatore g
             JOIN sdb_poltrona p ON p.giocatore_id = g.id
             JOIN sdb_nazione  n ON n.id = p.nazione_id
             WHERE g.attivo = 1 AND g.avvisi = 1 AND g.email_verificata = 1
               AND (g.sospeso_fino IS NULL OR g.sospeso_fino < NOW())')->fetchAll();

        $posta = new Posta($this->db);
        $scritti = 0;

        foreach ($giocatori as $g) {
            // Zero vuol dire «mai avvisato», non «avvisato al tick zero»: prima
            // la pausa valeva anche per chi non aveva ricevuto niente, e in un
            // mondo appena riavviato nessuno riceveva avvisi per i primi dodici
            // tick — un giorno vero — nemmeno per una crisi in scadenza.
            $ultimo = (int) $g['ultimo_avviso_tick'];
            if ($ultimo > 0 && $tick - $ultimo < $ogni) {
                continue;
            }
            $cose = $this->sospesi($g, $tick);
            if ($cose === []) {
                continue;
            }

            $posta->accoda(
                (string) $g['email'],
                'Stanza dei Bottoni — ' . $this->titolo($cose),
                $this->corpo((string) $g['nome'], (string) $g['nazione'], $cose),
                'avviso_gioco', 5);

            $this->db->esegui('UPDATE sdb_giocatore SET ultimo_avviso_tick = ? WHERE id = ?',
                [$tick, (int) $g['id']]);
            $scritti++;
        }

        return ['guardati' => count($giocatori), 'scritti' => $scritti];
    }

    /**
     * Che cosa aspetta questo giocatore, adesso.
     *
     * @param array<string,mixed> $g
     * @return list<array{urgente:bool,testo:string}>
     */
    private function sospesi(array $g, int $tick): array
    {
        $cose = [];
        $poltrona = (int) $g['poltrona_id'];
        $nazione  = (int) $g['nazione_id'];

        // Le crisi in cui tocca a noi, con quanto manca.
        foreach ($this->db->esegui(
            'SELECT k.livello, k.scade_tick,
                    IF(k.sfidante_id = ?, b.nome, a.nome) AS altro
             FROM sdb_crisi k
             JOIN sdb_nazione a ON a.id = k.sfidante_id
             JOIN sdb_nazione b ON b.id = k.sfidato_id
             WHERE k.stato = "aperta"
               AND ((k.tocca_a = "sfidante" AND k.sfidante_id = ?)
                 OR (k.tocca_a = "sfidato"  AND k.sfidato_id  = ?))',
            [$nazione, $nazione, $nazione])->fetchAll() as $k) {
            $restano = (int) $k['scade_tick'] - $tick;
            $cose[] = [
                'urgente' => $restano <= 1,
                'testo' => sprintf(
                    'Una crisi con %s aspetta la tua mossa, al gradino %d. %s',
                    (string) $k['altro'], (int) $k['livello'],
                    $restano <= 0
                        ? 'La pazienza e\' finita: al prossimo giro varra\' come una resa.'
                        : sprintf('Restano %d giri, fino al %s: dopo, non rispondere varra\' come cedere.',
                            $restano, \App\Nucleo\Calendario::tick((int) $k['scade_tick']))),
            ];
        }

        // Gli ordini che aspettano la nostra controfirma.
        $firme = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_ordine o
             WHERE o.stato = "in_attesa" AND o.nazione_id = ?
               AND o.richiede_controfirma = ? AND o.poltrona_id <> ?',
            [$nazione, (string) $g['ruolo'], $poltrona])->fetchColumn();
        if ($firme > 0) {
            $cose[] = ['urgente' => false, 'testo' => $firme === 1
                ? 'Un ordine aspetta la tua seconda firma.'
                : sprintf('%d ordini aspettano la tua seconda firma.', $firme)];
        }

        // Le offerte che ci sono state fatte — e che non si possono ignorare
        // senza che qualcuno le interpreti.
        $offerte = (int) $this->db->esegui(
            'SELECT COUNT(*) FROM sdb_offerta WHERE a_poltrona = ? AND stato = "aperta"',
            [$poltrona])->fetchColumn();
        if ($offerte > 0) {
            $cose[] = ['urgente' => false,
                'testo' => 'Qualcuno ti ha fatto una proposta riservata. Ti conviene leggerla da solo.'];
        }

        // E l'avvertimento che conta piu' di tutti: la poltrona sta per
        // sfuggirti di mano.
        $silenzio = $tick - (int) $g['ultimo_tick_attivo'];
        $soglia   = Delega::TICK_PRIMA_DELL_APPARATO;
        if ($g['delega_a'] === null && $silenzio >= $soglia - 2) {
            $cose[] = ['urgente' => true, 'testo' => $silenzio >= $soglia
                ? 'L\'apparato ha ripreso in mano il tuo paese: decide lui finche\' non torni.'
                : sprintf('Fra %d giri l\'apparato riprendera\' in mano il tuo paese. '
                    . 'Se stai via, puoi affidare la poltrona a qualcuno.', $soglia - $silenzio)];
        }

        return $cose;
    }

    /** @param list<array{urgente:bool,testo:string}> $cose */
    private function titolo(array $cose): string
    {
        foreach ($cose as $c) {
            if ($c['urgente']) {
                return 'qualcosa non puo\' aspettare';
            }
        }
        return count($cose) === 1 ? 'c\'e\' una cosa che ti aspetta' : 'ci sono cose che ti aspettano';
    }

    /** @param list<array{urgente:bool,testo:string}> $cose */
    private function corpo(string $nome, string $paese, array $cose): string
    {
        $url = rtrim((string) Configurazione::leggi('app.url_pubblico', ''), '/') . '/scrivania';
        $righe = '';
        foreach ($cose as $c) {
            $righe .= ($c['urgente'] ? '  !  ' : '  ·  ') . $c['testo'] . "\n";
        }
        return "$nome,\n\nsulla scrivania di $paese:\n\n$righe\n$url\n\n"
            . "--\nStanza dei Bottoni\n"
            . "Se non vuoi piu' questi messaggi, li spegni dalla tua scrivania.\n";
    }
}
