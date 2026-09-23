<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Nucleo\Basedati;

/**
 * Le crisi.
 *
 * "Balance of Power si perde quasi sempre in una crisi, o facendo saltare il
 * mondo o cedendo." È il pezzo che Crawford si ritrovò per caso a metà
 * sviluppo, e che trasformò un simulatore di geopolitica in un gioco.
 *
 * Il meccanismo è semplice e non perdona. Uno contesta un'azione dell'altro e
 * gli chiede di revocarla. L'altro cede — e perde la faccia davanti al mondo —
 * oppure sale di un gradino, e la palla torna al primo. Si va avanti finché
 * qualcuno molla o finché si arriva in fondo alla scala, dove non c'è più
 * niente da dire.
 *
 * Due cose rendono la decisione atroce, ed entrambe vengono dall'originale:
 * **la posta cresce a ogni gradino** — più si è saliti, più costa scendere — e
 * **da metà scala in su ogni passo può sfuggire di mano**.
 */
final class Crisi
{
    /**
     * Chi siede al tavolo di una crisi: il Capo e gli Esteri. Una crisi e' una
     * contestazione fra governi, e la conduce chi parla per il governo. Prima
     * contava QUALUNQUE poltrona occupata della nazione: il ministro
     * dell'Economia poteva portare il paese al nono gradino, e bastava un
     * giocatore seduto all'Informazione per togliere la crisi all'apparato.
     * Se nessuno dei due e' presidiato, decide l'apparato.
     */
    public const TAVOLO = ['capo', 'esteri'];

    public static function siedeAlTavolo(string $ruolo): bool
    {
        return in_array($ruolo, self::TAVOLO, true);
    }

    public function __construct(
        private readonly Basedati $db,
        /** @var array<int,string> */
        private readonly array $gradini,
        private readonly float $crescitaPosta = 0.38,
        private readonly int $pazienza = 3,
    ) {}

    /**
     * Quando la crisi si muove dentro un tick, le conseguenze non vanno
     * scritte nel database: il tick tiene il mondo in memoria e a fine giro lo
     * riscrive sopra, cancellandole. Collegando il mondo, la penale finisce
     * dove sopravvive. Fuori dal tick — quando a muovere e' un giocatore — il
     * mondo non c'e' e si scrive nel database, che il tick seguente rilegge.
     */
    public function collega(\App\Dati\Mondo $mondo): void
    {
        $this->mondo = $mondo;
    }

    private ?\App\Dati\Mondo $mondo = null;

    private function iso(int $nazione): ?string
    {
        $v = $this->db->esegui('SELECT codice FROM sdb_nazione WHERE id = ?', [$nazione])->fetchColumn();
        return $v === false ? null : (string) $v;
    }

    public function gradino(int $livello): string
    {
        return $this->gradini[$livello] ?? '—';
    }

    /**
     * Apre una crisi contestando un'operazione altrui.
     *
     * Si può contestare solo ciò che si è potuto ATTRIBUIRE: senza il quarto
     * livello di conoscenza non si ha niente in mano, e una contestazione senza
     * prove è soltanto un'accusa.
     *
     * @param array<string,mixed> $poltrona
     * @return array{0:bool,1:string}
     */
    public function apri(array $poltrona, int $evento, int $tick): array
    {
        if (!self::siedeAlTavolo((string) ($poltrona['ruolo'] ?? ''))) {
            return [false, 'Una contestazione la aprono il Capo o gli Esteri.'];
        }
        $e = $this->db->esegui(
            'SELECT e.*, c.livello AS conoscenza, m.nome AS mandante, m.id AS mandante_id,
                    b.nome AS bersaglio
             FROM sdb_evento e
             JOIN sdb_conoscenza c ON c.evento_id = e.id AND c.osservatore_id = ?
             JOIN sdb_nazione m ON m.id = e.mandante_id
             LEFT JOIN sdb_nazione b ON b.id = e.bersaglio_id
             WHERE e.id = ?', [(int) $poltrona['nazione_id'], $evento])->fetch();

        if ($e === false) {
            return [false, 'Non risulta nulla del genere nei nostri archivi.'];
        }
        if ((int) $e['conoscenza'] < 4) {
            return [false, 'Sappiamo che è successo, non possiamo dimostrare chi è stato. '
                . 'Contestare senza prove significa solo farsi smentire.'];
        }
        if ((int) $e['mandante_id'] === (int) $poltrona['nazione_id']) {
            return [false, 'È roba nostra.'];
        }
        $aperta = $this->db->esegui(
            'SELECT 1 FROM sdb_crisi WHERE evento_id = ? AND stato = "aperta" LIMIT 1', [$evento])->fetchColumn();
        if ($aperta) {
            return [false, 'C\'è già una crisi aperta su quel fatto.'];
        }

        [$postaSfidante, $postaSfidato] = $this->poste(
            (int) $poltrona['nazione_id'], (int) $e['mandante_id'], $e, 1);

        $this->db->esegui(
            'INSERT INTO sdb_crisi
                (evento_id, sfidante_id, sfidato_id, oggetto_id, livello, tocca_a, stato,
                 posta_sfidante, posta_sfidato, aperta_tick, ultimo_tick, scade_tick)
             VALUES (?,?,?,?,?, "sfidato", "aperta", ?,?,?,?,?)',
            [$evento, (int) $poltrona['nazione_id'], (int) $e['mandante_id'],
             $e['bersaglio_id'] !== null ? (int) $e['bersaglio_id'] : null, 1,
             $postaSfidante, $postaSfidato, $tick, $tick, $this->scadenza($tick)],
        );
        $id = (int) $this->db->pdo()->lastInsertId();
        $this->passo($id, $tick, 'sfidante', 'apre', 1, null);

        return [true, sprintf(
            'Crisi aperta con %s. Primo gradino: %s. Ora tocca a loro.',
            $e['mandante'], $this->gradino(1))];
    }

    /**
     * La posta: quanto perde chi cede, per l'uno e per l'altro.
     *
     * È l'Outrage di Crawford — quanto danno ha fatto l'azione, quanto ci
     * tengono i due al paese coinvolto, quanto quel paese conta nell'ordine
     * mondiale — moltiplicato per il gradino a cui si è arrivati.
     *
     * @param array<string,mixed> $e
     * @return array{0:float,1:float}
     */
    private function poste(int $sfidante, int $sfidato, array $e, int $livello): array
    {
        $oggetto = $e['bersaglio_id'] !== null ? (int) $e['bersaglio_id'] : null;
        $danno = abs((float) $e['danno_base']) / 127.0;

        $peso = static function (int $chi) use ($oggetto): array {
            return [$chi, $oggetto];
        };
        $affinita = function (int $da, ?int $a): float {
            if ($a === null) {
                return 0.0;
            }
            return (float) $this->db->esegui(
                'SELECT COALESCE(affinita,0) FROM sdb_relazione WHERE da_nazione_id = ? AND a_nazione_id = ?',
                [$da, $a])->fetchColumn();
        };
        $prestigio = $oggetto !== null
            ? (float) $this->db->esegui('SELECT valore_prestigio FROM sdb_nazione WHERE id = ?',
                [$oggetto])->fetchColumn()
            : 50.0;

        $scala = (1.0 + $livello * $this->crescitaPosta) * (0.4 + min(2.0, $prestigio / 300.0));

        // Chi ci tiene di più al paese coinvolto ha più da perdere a mollare.
        // La scala è calibrata sull'integrità, che va da 0 a 128: una crisi
        // portata in fondo deve poter costare decine di punti, o la scelta di
        // cedere non fa paura e allora non è una scelta.
        $a = 26.0 * $danno * (0.5 + abs($affinita($sfidante, $oggetto)) / 127.0) * $scala;
        $b = 26.0 * $danno * (0.5 + abs($affinita($sfidato, $oggetto)) / 127.0) * $scala;

        return [round($a, 2), round($b, 2)];
    }

    /**
     * Cedere o salire.
     *
     * @return array{0:bool,1:string}
     */
    /**
     * Quale parte tiene, in questa crisi, la nazione data: «sfidante»,
     * «sfidato», o null se la crisi non la riguarda.
     *
     * ESISTE PER UN DIFETTO TROVATO DALL'AUDIT. La parte arrivava dal modulo
     * web come campo nascosto, e rispondi() controllava soltanto che fosse il
     * suo turno: qualunque giocatore seduto, di qualunque paese, poteva
     * rispondere a una crisi fra due ALTRE nazioni — cedere per conto loro, o
     * salire alternando la parte fino al nono gradino, che apre una guerra
     * vera. La parte non si dichiara: si ricava da chi si e'.
     */
    public function parteDi(int $crisi, int $nazione): ?string
    {
        $c = $this->db->esegui('SELECT sfidante_id, sfidato_id FROM sdb_crisi WHERE id = ?', [$crisi])->fetch();
        if ($c === false) {
            return null;
        }
        return match ($nazione) {
            (int) $c['sfidante_id'] => 'sfidante',
            (int) $c['sfidato_id']  => 'sfidato',
            default                 => null,
        };
    }

    /**
     * L'ultimo tick in cui si puo' ancora rispondere. La scadenza e'
     * INCLUSIVA come ogni altra del gioco («entro il…», e la fase 01 fa
     * cedere solo quando scade_tick < tick): con tick + pazienza le finestre
     * per rispondere erano quattro, non le tre di crisi.pazienza_tick.
     */
    private function scadenza(int $tick): int
    {
        return $tick + max(1, $this->pazienza) - 1;
    }

    public function rispondi(int $crisi, string $parte, string $azione, int $tick): array
    {
        // Due mosse e basta. Prima tutto cio' che non era «cede» saliva — anche
        // un campo vuoto.
        if ($azione !== 'scala' && $azione !== 'cede') {
            return [false, 'Due mosse sole: si sale, o si cede.'];
        }
        $c = $this->db->esegui('SELECT * FROM sdb_crisi WHERE id = ? AND stato = "aperta"', [$crisi])->fetch();
        if ($c === false) {
            return [false, 'Quella crisi è chiusa.'];
        }
        if ((string) $c['tocca_a'] !== $parte) {
            return [false, 'Non tocca a te: aspetta la loro mossa.'];
        }

        if ($azione === 'cede') {
            return $this->cede($c, $parte, $tick);
        }

        // --- si sale ------------------------------------------------------
        $livello = (int) $c['livello'] + 1;
        if ($livello >= 9) {
            return $this->fondoScala($c, $tick, $parte);
        }

        $e = $this->db->esegui('SELECT * FROM sdb_evento WHERE id = ?', [(int) $c['evento_id']])->fetch()
            ?: ['danno_base' => 40, 'bersaglio_id' => $c['oggetto_id']];
        [$pa, $pb] = $this->poste((int) $c['sfidante_id'], (int) $c['sfidato_id'], $e, $livello);

        // Dal sito il dado dell'incidente non si tira: lo tira la fase 01 al
        // tick dopo, con le regole dell'apparato (migrazione 0030). Il motore,
        // che ha il mondo collegato, l'ha gia' tirato prima di salire.
        $daProvare = ($this->mondo === null && $livello >= 6) ? $livello : 0;
        $this->db->esegui(
            'UPDATE sdb_crisi SET livello = ?, tocca_a = ?, posta_sfidante = ?, posta_sfidato = ?,
                    ultimo_tick = ?, scade_tick = ?, da_provare = ? WHERE id = ?',
            [$livello, $parte === 'sfidante' ? 'sfidato' : 'sfidante', $pa, $pb,
             $tick, $this->scadenza($tick), $daProvare, $crisi]);
        $this->passo($crisi, $tick, $parte, 'scala', $livello, null);

        return [true, sprintf('Si sale: %s. Ora la palla è dall\'altra parte.', $this->gradino($livello))];
    }

    /** @param array<string,mixed> $c */
    private function cede(array $c, string $parte, int $tick): array
    {
        $chiCede = $parte === 'sfidante' ? (int) $c['sfidante_id'] : (int) $c['sfidato_id'];
        $chiVince = $parte === 'sfidante' ? (int) $c['sfidato_id'] : (int) $c['sfidante_id'];
        $posta = (float) ($parte === 'sfidante' ? $c['posta_sfidante'] : $c['posta_sfidato']);

        $this->db->esegui(
            'UPDATE sdb_crisi SET stato = ?, ultimo_tick = ? WHERE id = ?',
            [$parte === 'sfidante' ? 'ceduto_sfidante' : 'ceduto_sfidato', $tick, (int) $c['id']]);
        $this->passo((int) $c['id'], $tick, $parte, 'cede', (int) $c['livello'], null);

        // Chi molla dopo essere salito perde la faccia, e la faccia e' una cosa
        // che il mondo misura: l'integrita' di chi cede scende, la sfera di
        // influenza di chi ha tenuto duro sul paese conteso cresce.
        $isoCede    = $this->iso($chiCede);
        $isoVince   = $this->iso($chiVince);
        $isoOggetto = $c['oggetto_id'] !== null ? $this->iso((int) $c['oggetto_id']) : null;

        if ($this->mondo !== null) {
            $n = $isoCede !== null ? ($this->mondo->nazioni[$isoCede] ?? null) : null;
            if ($n !== null) {
                $n->integrita = max(0.0, $n->integrita - $posta);
            }
            if ($isoOggetto !== null) {
                // Non si tocca la sfera, che la fase 06 ricalcola: si sposta la
                // spinta, che e' la parte che il mondo si ricorda.
                $r = $isoVince !== null ? $this->mondo->relazioni->fra($isoVince, $isoOggetto) : null;
                if ($r !== null) {
                    $r->spintaSfera = min(6.0, $r->spintaSfera + 1.0);
                }
                $r = $isoCede !== null ? $this->mondo->relazioni->fra($isoCede, $isoOggetto) : null;
                if ($r !== null) {
                    $r->spintaSfera = max(-6.0, $r->spintaSfera - 1.0);
                }
            }
        } else {
            $this->db->esegui(
                'UPDATE sdb_nazione_stato SET integrita = GREATEST(0, integrita - ?)
                 WHERE nazione_id = ? AND tick = (SELECT MAX(tick) FROM sdb_mondo_stato)',
                [$posta, $chiCede]);
            if ($c['oggetto_id'] !== null) {
                $this->db->esegui(
                    'UPDATE sdb_relazione SET spinta_sfera = LEAST(6, spinta_sfera + 1)
                     WHERE da_nazione_id = ? AND a_nazione_id = ?', [$chiVince, (int) $c['oggetto_id']]);
                $this->db->esegui(
                    'UPDATE sdb_relazione SET spinta_sfera = GREATEST(-6, spinta_sfera - 1)
                     WHERE da_nazione_id = ? AND a_nazione_id = ?', [$chiCede, (int) $c['oggetto_id']]);
            }
        }
        // Se cede chi era stato contestato, l'operazione si ferma davvero.
        if ($parte === 'sfidato' && $c['evento_id'] !== null) {
            $this->db->esegui(
                'UPDATE sdb_evento SET stato = "fermato" WHERE id = ? AND stato IN ("in_volo","scoperto")',
                [(int) $c['evento_id']]);
            // E ANCHE IN MEMORIA, se si cede dentro un tick (l'apparato, o una
            // scadenza nella fase 01). Scriverlo solo nella base dati non
            // bastava: a fine tick Deposito::salvaEventi riscrive lo stato di
            // ogni evento dalla memoria, dove era ancora «in volo» — e la fase
            // 02, nello stesso tick, poteva perfino farlo maturare. Una crisi
            // vinta non fermava l'operazione per cui era stata aperta.
            if ($this->mondo !== null) {
                foreach ($this->mondo->eventi as $e) {
                    if ($e->id === (int) $c['evento_id']
                        && in_array($e->stato, [\App\Dati\Evento::IN_VOLO, \App\Dati\Evento::SCOPERTO], true)) {
                        $e->stato = \App\Dati\Evento::FERMATO;
                    }
                }
            }
        }

        $this->annuncia($c, $tick, 'crisi_chiusa', [
            'chi_cede'  => $this->nome($chiCede),
            'chi_tiene' => $this->nome($chiVince),
            'gradino'   => $this->gradino((int) $c['livello']),
        ]);

        return [true, sprintf('Hai ceduto al gradino «%s». Costa %.0f punti di faccia, '
            . 'e il mondo se lo ricorderà.', $this->gradino((int) $c['livello']), $posta)];
    }

    /** @param array<string,mixed> $c */
    private function fondoScala(array $c, int $tick, string $parte = 'sfidato'): array
    {
        $this->db->esegui('UPDATE sdb_crisi SET stato = "guerra", livello = 9, ultimo_tick = ? WHERE id = ?',
            [$tick, (int) $c['id']]);
        $this->passo((int) $c['id'], $tick, $parte, 'scala', 9, 'fondo scala');

        // Il nono gradino non e' una parola: e' una guerra vera, che da qui in
        // poi la fase 07 combatte con le sue regole. Chi ha fatto l'ultimo
        // passo e' l'aggressore, perche' e' lui che ha scelto di non fermarsi.
        $aggressore = $parte === 'sfidante' ? (int) $c['sfidante_id'] : (int) $c['sfidato_id'];
        $difensore  = $parte === 'sfidante' ? (int) $c['sfidato_id']  : (int) $c['sfidante_id'];
        $this->db->esegui(
            'INSERT INTO sdb_guerra (aggressore_id, difensore_id, inizio_tick, morti)
             SELECT ?, ?, ?, 0 FROM DUAL WHERE NOT EXISTS (
                 SELECT 1 FROM sdb_guerra WHERE fine_tick IS NULL
                   AND ((aggressore_id = ? AND difensore_id = ?)
                     OR (aggressore_id = ? AND difensore_id = ?)))',
            [$aggressore, $difensore, $tick,
             $aggressore, $difensore, $difensore, $aggressore]);
        $this->annuncia($c, $tick, 'crisi_degenerata', [
            'fra'     => $this->nome((int) $c['sfidante_id']),
            'e'       => $this->nome((int) $c['sfidato_id']),
        ]);
        return [true, 'Siete arrivati in fondo alla scala. Non c\'era altro da dire.'];
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $dati */
    private function annuncia(array $c, int $tick, string $genere, array $dati): void
    {
        $this->db->esegui('INSERT INTO sdb_notizia (tick, genere, dati) VALUES (?,?,?)',
            [$tick, $genere, json_encode($dati, JSON_UNESCAPED_UNICODE)]);
    }

    private function passo(int $crisi, int $tick, string $attore, string $azione, int $livello, ?string $nota): void
    {
        $this->db->esegui(
            'INSERT INTO sdb_crisi_passo (crisi_id, tick, attore, azione, livello_dopo, nota)
             VALUES (?,?,?,?,?,?)', [$crisi, $tick, $attore, $azione, $livello, $nota]);
    }

    private function nome(int $id): string
    {
        return (string) $this->db->esegui('SELECT nome FROM sdb_nazione WHERE id = ?', [$id])->fetchColumn();
    }

    /** @return list<array<string,mixed>> le crisi che riguardano questa nazione */
    public function aperte(int $nazione): array
    {
        return $this->db->esegui(
            'SELECT c.*, a.nome AS sfidante, b.nome AS sfidato, o.nome AS oggetto, e.verbo
             FROM sdb_crisi c
             JOIN sdb_nazione a ON a.id = c.sfidante_id
             JOIN sdb_nazione b ON b.id = c.sfidato_id
             LEFT JOIN sdb_nazione o ON o.id = c.oggetto_id
             LEFT JOIN sdb_evento e ON e.id = c.evento_id
             WHERE c.stato = "aperta" AND (c.sfidante_id = ? OR c.sfidato_id = ?)
             ORDER BY c.id DESC', [$nazione, $nazione])->fetchAll();
    }

    /** @return list<array<string,mixed>> le operazioni che possiamo contestare */
    public function contestabili(int $nazione): array
    {
        return $this->db->esegui(
            'SELECT e.id, e.verbo, e.dominio, m.nome AS mandante, b.nome AS bersaglio
             FROM sdb_conoscenza c
             JOIN sdb_evento e ON e.id = c.evento_id
             JOIN sdb_nazione m ON m.id = e.mandante_id
             LEFT JOIN sdb_nazione b ON b.id = e.bersaglio_id
             WHERE c.osservatore_id = ? AND c.livello >= 4 AND e.mandante_id <> ?
               AND NOT EXISTS (SELECT 1 FROM sdb_crisi k WHERE k.evento_id = e.id AND k.stato = "aperta")
             ORDER BY e.id DESC LIMIT 10', [$nazione, $nazione])->fetchAll();
    }
}
