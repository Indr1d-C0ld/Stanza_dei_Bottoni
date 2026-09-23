<?php

declare(strict_types=1);

/**
 * Il ciclo del giocatore, dall'ingresso alla chiusura d'epoca.
 *
 * PERCHE' ESISTE. Fino all'audit di settembre 2026 nove classi dello strato di
 * gioco — Scrivania, Canale, Falsificazione, Linea, Agende, Reclutamento,
 * Epoca, Mercato e il Deposito — non avevano NESSUNA prova diretta. Erano le
 * funzionalita' che un giocatore usa, e si sapeva che funzionavano solo
 * perche' nessuno si era lamentato. In un mondo dove non gioca nessuno, non
 * lamentarsi non prova niente.
 *
 * Qui si percorre tutto, con giocatori finti su poltrone vere, dentro una
 * transazione che alla fine si annulla: il mondo vivo non se ne accorge.
 *
 * Ogni passo e' chiuso in `$passo()`, che trasforma un'eccezione in una prova
 * fallita col messaggio accanto invece di interrompere il file: un errore SQL
 * nella falsificazione non deve nascondere quello che c'e' nella linea.
 */

use App\Dati\Deposito;
use App\Dati\Mondo;
use App\Gioco\Servizi;
use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;
use App\Nucleo\Configurazione;
use App\Nucleo\Posta;

$radice = dirname(__DIR__);
$db     = new Basedati((array) Configurazione::leggi('db', []));
$pdo    = $db->pdo();
$cal    = Calibrazione::carica($radice, 'gioco');

$passo = static function (string $cosa, callable $f): mixed {
    try {
        return $f();
    } catch (\Throwable $e) {
        Prove::che($cosa . ' — senza eccezioni', false,
            get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 160));
        return null;
    }
};

$pdo->beginTransaction();
try {
    Posta::trasportoDiProva(static fn(): array => ['ok' => true]);
    $s    = new Servizi($db, $cal, $radice);
    $tick = (int) $db->esegui('SELECT COALESCE(MAX(tick), 0) FROM sdb_mondo_stato')->fetchColumn();

    // ------------------------------------------------------------ i giocatori
    $nuovo = static function (string $nome) use ($db): int {
        $db->esegui(
            'INSERT INTO sdb_giocatore (nome, email, hash_password, creato, email_verificata,
                                        verificata_il, avvisi)
             VALUES (?, ?, "x", NOW(), 1, NOW(), 0)',
            [$nome, strtolower($nome) . '@example.invalid']);
        return $db->ultimoId();
    };
    $poltronaLibera = static function (string $iso, string $ruolo) use ($db): int {
        return (int) $db->esegui(
            'SELECT p.id FROM sdb_poltrona p JOIN sdb_nazione n ON n.id = p.nazione_id
             WHERE n.codice = ? AND p.ruolo = ? AND p.giocatore_id IS NULL',
            [$iso, $ruolo])->fetchColumn();
    };

    $A = $nuovo('ProvaCapoUsa');        // Stati Uniti, Capo
    $B = $nuovo('ProvaCapoRus');        // Russia, Capo
    $C = $nuovo('ProvaIntelUsa');       // Stati Uniti, Intelligence
    $D = $nuovo('ProvaEsteriChn');      // Cina, Esteri: il terzo incomodo
    $E = $nuovo('ProvaDelegato');       // senza poltrona: riceve deleghe

    Prove::gruppo('Ciclo del giocatore: la Scrivania');

    $pA = $poltronaLibera('USA', 'capo');
    $pB = $poltronaLibera('RUS', 'capo');
    $pC = $poltronaLibera('USA', 'intelligence');
    $pD = $poltronaLibera('CHN', 'esteri');
    Prove::che('le quattro poltrone di prova esistono e sono libere', $pA && $pB && $pC && $pD);

    foreach ([[$A, $pA], [$B, $pB], [$C, $pC], [$D, $pD]] as [$g, $p]) {
        $esito = $passo('occupa', fn() => $s->scrivania->occupa($g, $p, $tick));
        Prove::che("il giocatore $g prende posto", ($esito[0] ?? false) === true, $esito[1] ?? '');
    }
    $doppio = $passo('occupa due volte', fn() => $s->scrivania->occupa($A, $pD, $tick));
    Prove::che('non si occupano due poltrone', ($doppio[0] ?? true) === false);

    $poltA = $s->scrivania->poltronaDi($A);
    $poltB = $s->scrivania->poltronaDi($B);
    $poltC = $s->scrivania->poltronaDi($C);
    $poltD = $s->scrivania->poltronaDi($D);
    Prove::che('la poltrona torna col paese giusto', ($poltA['codice'] ?? '') === 'USA');
    Prove::che('e non e\' per delega', ($poltA['per_delega'] ?? true) === false);

    $libere = array_column($s->scrivania->poltroneLibere(), 'id');
    Prove::che('una poltrona occupata sparisce dalle libere', !in_array($pA, array_map('intval', $libere), true));

    // Le agende private arrivano con la poltrona.
    $agende = $passo('agende', fn() => $s->agende->di($A));
    Prove::che('chi prende posto riceve le proprie agende private', is_array($agende) && count($agende) >= 1,
        is_array($agende) ? count($agende) . ' agende' : '');
    $passo('verifica delle agende', fn() => $s->agende->verifica($tick));

    // --- un ordine senza controfirma, e il suo ritiro --------------------
    $catalogo  = require $radice . '/calibrazione/verbi.php';
    $possibili = $s->scrivania->verbiPossibili('capo', $catalogo);
    Prove::che('il Capo ha dei verbi', $possibili !== []);
    $liberi = array_filter($possibili,
        fn($def, $v) => $s->scrivania->controfirmaRichiesta('capo', (string) $v, $def) === null,
        ARRAY_FILTER_USE_BOTH);
    $verbo = (string) array_key_first($liberi ?: $possibili);
    $rus   = (int) $poltB['nazione_id'];

    $esito = $passo('ordina', fn() => $s->scrivania->ordina($poltA, $possibili[$verbo], $verbo, $rus, 60, 30, $tick));
    Prove::che("il Capo ordina «{$verbo}» contro la Russia", ($esito[0] ?? false) === true, $esito[1] ?? '');
    $mio = $s->scrivania->mieiOrdini($A);
    Prove::che('l\'ordine compare fra i miei', count($mio) >= 1);
    $idOrdine = (int) ($mio[0]['id'] ?? 0);
    Prove::che('un altro giocatore non lo puo\' ritirare', $s->scrivania->annulla($idOrdine, $B) === false);
    Prove::che('io si\'', $s->scrivania->annulla($idOrdine, $A) === true);
    Prove::che('e una volta sola', $s->scrivania->annulla($idOrdine, $A) === false);

    // --- le controfirme --------------------------------------------------
    Prove::gruppo('Ciclo del giocatore: le controfirme');
    $conFirma = null;
    foreach (['capo', 'esteri', 'difesa', 'intelligence', 'interni', 'economia'] as $ruolo) {
        foreach ($s->scrivania->verbiPossibili($ruolo, $catalogo) as $v => $def) {
            $chi = $s->scrivania->controfirmaRichiesta($ruolo, (string) $v, $def);
            if ($chi !== null && $ruolo === 'capo') {
                $conFirma = [$v, $def, $chi];
                break 2;
            }
        }
    }
    if ($conFirma === null) {
        Prove::che('esiste almeno un verbo del Capo che chiede una controfirma', false,
            'nessuno: il meccanismo non si puo\' provare da questo ruolo');
    } else {
        [$v, $def, $chi] = $conFirma;
        $passo('ordina con controfirma', fn() => $s->scrivania->ordina($poltA, $def, (string) $v, $rus, 50, 50, $tick));
        $idCf = (int) $db->esegui('SELECT MAX(id) FROM sdb_ordine WHERE giocatore_id = ?', [$A])->fetchColumn();
        $stato = (string) $db->esegui('SELECT stato FROM sdb_ordine WHERE id = ?', [$idCf])->fetchColumn();
        Prove::uguale("«{$v}» aspetta la firma di $chi", 'in_attesa', $stato);
        Prove::che('chi non e\' nel gabinetto giusto non puo\' firmare', $s->scrivania->firma($idCf, $poltD) === false);
    }

    // Il tetto degli ordini per giro (gioco.ordini_per_tick). Prima non ce
    // n'era: un ministro poteva lanciare cento operazioni in una settimana.
    $verbiEsteri = $s->scrivania->verbiPossibili((string) $poltD['ruolo'], $catalogo);
    if ($verbiEsteri !== []) {
        $vE = (string) array_key_first($verbiEsteri);
        $tetto = (int) $cal->numero('gioco.ordini_per_tick', 2);
        $esiti = [];
        for ($i = 0; $i <= $tetto; $i++) {
            $esiti[] = $passo('ordina in serie', fn() => $s->scrivania->ordina(
                $poltD, $verbiEsteri[$vE], $vE, $rus, 30, 0, $tick))[0] ?? null;
        }
        Prove::che("i primi $tetto ordini del giro partono",
            array_slice($esiti, 0, $tetto) === array_fill(0, $tetto, true));
        Prove::che('quello dopo no', end($esiti) === false);
    }

    // ------------------------------------------------------------ il Canale
    Prove::gruppo('Ciclo del giocatore: il Canale');
    $esito = $passo('invia', fn() => $s->canale->invia($poltA, (int) $poltB['id'], 'Prova di canale, ignorare.', 2, $tick));
    Prove::che('Washington scrive a Mosca', ($esito[0] ?? false) === true, $esito[1] ?? '');
    $ricevuti = $passo('ricevuti', fn() => $s->canale->ricevuti((int) $poltB['id'], $tick + 5));
    Prove::che('e Mosca lo riceve', is_array($ricevuti) && count(array_filter($ricevuti,
        fn($m) => str_contains((string) ($m['testo'] ?? ''), 'Prova di canale'))) === 1);
    $inviati = $passo('inviati', fn() => $s->canale->inviati((int) $poltA['id'], $tick + 5));
    Prove::che('e resta fra gli inviati di Washington', is_array($inviati) && count($inviati) >= 1);
    $vuoto = $passo('invia vuoto', fn() => $s->canale->invia($poltA, (int) $poltB['id'], '   ', 2, $tick));
    Prove::che('un messaggio vuoto non parte', ($vuoto[0] ?? true) === false);
    $fantasma = $passo('invia a nessuno', fn() => $s->canale->invia($poltA, 999999999, 'x', 2, $tick));
    Prove::che('un messaggio a una poltrona inesistente non parte', ($fantasma[0] ?? true) === false);
    // Il contingente dei canali riservati vale per il cifrato E per il
    // corriere: prima il corriere, il piu' sicuro, era illimitato.
    $corrieri = [];
    for ($i = 0; $i <= \App\Gioco\Canale::TETTO_RISERVATI; $i++) {
        $corrieri[] = $passo('corriere', fn() => $s->canale->invia($poltA, (int) $poltB['id'],
            'Corriere di prova ' . $i, 4, $tick))[0] ?? null;
    }
    Prove::che('i corrieri del contingente partono',
        array_slice($corrieri, 0, \App\Gioco\Canale::TETTO_RISERVATI)
            === array_fill(0, \App\Gioco\Canale::TETTO_RISERVATI, true));
    Prove::che('oltre il contingente il corriere non parte', end($corrieri) === false);
    $passo('rubrica', fn() => Prove::che('la rubrica si legge', count($s->canale->rubrica((int) $poltA['id'])) > 0));
    $passo('intercettati', fn() => $s->canale->intercettati((int) $poltA['nazione_id']));

    // ------------------------------------------------------ la falsificazione
    Prove::gruppo('Ciclo del giocatore: la falsificazione');
    $chn = (int) $poltD['nazione_id'];
    $no = $passo('manometti dal Capo', fn() => $s->falso->ordina($poltA, $rus, $chn, 'inserisci', 'x', $tick));
    Prove::che('solo l\'Intelligence manomette', ($no[0] ?? true) === false);
    $si = $passo('manometti', fn() => $s->falso->ordina($poltC, $rus, $chn, 'inserisci', 'e trattiamo in segreto.', $tick));
    Prove::che('l\'Intelligence americana manomette la posta russa verso la Cina', ($si[0] ?? false) === true, $si[1] ?? '');
    $nostre = $passo('nostre', fn() => $s->falso->nostre((int) $poltC['nazione_id']));
    Prove::che('l\'operazione compare fra le nostre', is_array($nostre) && count($nostre) >= 1);
    if (is_array($nostre) && $nostre !== []) {
        $idOp = (int) $nostre[0]['id'];
        $r = $passo('revoca da fuori', fn() => $s->falso->revoca($idOp, $poltB));
        Prove::che('la Russia non puo\' revocare un\'operazione americana', ($r[0] ?? true) === false);
        $r = $passo('revoca', fn() => $s->falso->revoca($idOp, $poltC));
        Prove::che('chi l\'ha ordinata si\'', ($r[0] ?? false) === true, $r[1] ?? '');
    }
    $passo('scadenze', fn() => $s->falso->scadenze($tick));

    // --------------------------------------------------------- la linea
    Prove::gruppo('Ciclo del giocatore: la linea diretta');
    $esito = $passo('proponi', fn() => $s->linea->proponi($poltA, $rus, $tick));
    Prove::che('Washington propone una linea a Mosca', ($esito[0] ?? false) === true, $esito[1] ?? '');
    $linee = $passo('nostre linee', fn() => $s->linea->nostre($rus));
    $proposta = null;
    foreach ((array) $linee as $l) {
        if (($l['stato'] ?? '') === 'proposta') { $proposta = (int) $l['id']; }
    }
    Prove::che('Mosca la vede', $proposta !== null);
    if ($proposta !== null) {
        $terzo = $passo('risponde un terzo', fn() => $s->linea->rispondi($poltD, $proposta, true, $tick));
        Prove::che('Pechino non puo\' accettarla per Mosca', ($terzo[0] ?? true) === false);
        $ok = $passo('accetta', fn() => $s->linea->rispondi($poltB, $proposta, true, $tick));
        Prove::che('Mosca accetta', ($ok[0] ?? false) === true, $ok[1] ?? '');
        Prove::che('e la linea esiste', $s->linea->esiste((int) $poltA['nazione_id'], $rus));
        $chiusa = $passo('chiudi', fn() => $s->linea->chiudi($poltA, $proposta, $tick));
        Prove::che('e si chiude', ($chiusa[0] ?? false) === true, $chiusa[1] ?? '');
    }

    // ----------------------------------------------------- il reclutamento
    Prove::gruppo('Ciclo del giocatore: il reclutamento');
    $esito = $passo('offri', fn() => $s->reclutamento->offri($poltA, (int) $poltB['id'],
        'Una proposta di prova.', true, false, false, $tick));
    Prove::che('Washington fa un\'offerta al Capo russo', ($esito[0] ?? false) === true, $esito[1] ?? '');
    $offerte = $passo('offerte', fn() => $s->reclutamento->offerteRicevute((int) $poltB['id']));
    Prove::che('il Capo russo la vede', is_array($offerte) && count($offerte) >= 1);
    if (is_array($offerte) && $offerte !== []) {
        $idOf = (int) $offerte[0]['id'];
        $altro = $passo('risponde un altro', fn() => $s->reclutamento->rispondi($idOf, $poltD, 'accetta', $tick));
        Prove::che('nessun altro puo\' rispondere al suo posto', ($altro[0] ?? true) === false);
        $r = $passo('rifiuta', fn() => $s->reclutamento->rispondi($idOf, $poltB, 'rifiuta', $tick));
        Prove::che('il Capo russo rifiuta', ($r[0] ?? false) === true, $r[1] ?? '');
    }
    $passo('nostri uomini', fn() => $s->reclutamento->nostriUomini((int) $poltA['nazione_id']));

    // ------------------------------------------------------------ la crisi
    Prove::gruppo('Ciclo del giocatore: la crisi');
    // Un'operazione russa contro gli Stati Uniti, e Washington che la conosce
    // fino all'attribuzione: il minimo per poter contestare.
    $usa = (int) $poltA['nazione_id'];
    $evento = (int) $passo('prepara l\'evento contestato', function () use ($db, $rus, $usa, $tick): int {
        $db->esegui(
            'INSERT INTO sdb_evento (dominio, verbo, mandante_id, bersaglio_id, intensita, copertura,
                                     creato_tick, maturazione_tick, danno_base, attribuzione_vera, stato)
             VALUES ("int", "sabotaggio", ?, ?, 0.6, 0.3, ?, ?, 40, 1, "realizzato")',
            [$rus, $usa, max(0, $tick - 3), max(0, $tick - 1)]);   // anche su un mondo appena nato
        $id = $db->ultimoId();
        $db->esegui('INSERT INTO sdb_conoscenza (osservatore_id, evento_id, livello, primo_tick) VALUES (?,?,4,?)',
            [$usa, $id, max(0, $tick - 1)]);
        return $id;
    });

    $aperta = $passo('apri', fn() => $s->crisi->apri($poltA, $evento, $tick));
    Prove::che('Washington apre una crisi con Mosca', ($aperta[0] ?? false) === true, $aperta[1] ?? '');
    $idCrisi = (int) $db->esegui('SELECT id FROM sdb_crisi WHERE evento_id = ?', [$evento])->fetchColumn();
    Prove::che('la crisi esiste', $idCrisi > 0);

    // IL DIFETTO TROVATO DALL'AUDIT. La parte arrivava dal modulo, e il
    // servizio controllava solo che fosse il suo turno: qualunque giocatore
    // seduto poteva rispondere a una crisi fra due ALTRE nazioni, cedere per
    // conto loro, o spingerla al nono gradino — che apre una guerra vera.
    Prove::uguale('la parte di Mosca si ricava dalla sua poltrona', 'sfidato',
        $passo('parteDi', fn() => $s->crisi->parteDi($idCrisi, $rus)));
    Prove::uguale('quella di Washington pure', 'sfidante',
        $passo('parteDi', fn() => $s->crisi->parteDi($idCrisi, $usa)));
    Prove::uguale('e Pechino non ha nessuna parte in questa crisi', null,
        $passo('parteDi', fn() => $s->crisi->parteDi($idCrisi, $chn)));
    $indice = (string) file_get_contents($radice . '/index.php');
    Prove::che('e index.php non si fida piu\' della parte dichiarata dal modulo',
        !str_contains($indice, "\$post['parte']"),
        'la parte va ricavata dalla nazione della poltrona, non chiesta al giocatore');

    $mossa = $passo('rispondi', fn() => $s->crisi->rispondi($idCrisi, 'sfidato', 'scala', $tick));
    Prove::che('Mosca sale di un gradino', ($mossa[0] ?? false) === true, $mossa[1] ?? '');
    $livello = (int) $db->esegui('SELECT livello FROM sdb_crisi WHERE id = ?', [$idCrisi])->fetchColumn();
    Prove::uguale('e la crisi e\' al secondo gradino', 2, $livello);
    $fuoriTurno = $passo('fuori turno', fn() => $s->crisi->rispondi($idCrisi, 'sfidato', 'scala', $tick));
    Prove::che('Mosca non puo\' giocare due volte di fila', ($fuoriTurno[0] ?? true) === false);
    $pazienza = (int) $cal->numero('crisi.pazienza_tick', 3);
    Prove::uguale("la pazienza e' di $pazienza giri, non uno di piu'", $tick + $pazienza - 1,
        (int) $db->esegui('SELECT scade_tick FROM sdb_crisi WHERE id = ?', [$idCrisi])->fetchColumn());
    // Chi sale oltre il quinto gradino dalla scrivania corre il rischio
    // d'incidente che corre l'apparato: lo tira la fase 01 al tick dopo.
    $db->esegui('UPDATE sdb_crisi SET livello = 5, tocca_a = "sfidante" WHERE id = ?', [$idCrisi]);
    $passo('sale al sesto', fn() => $s->crisi->rispondi($idCrisi, 'sfidante', 'scala', $tick));
    Prove::uguale('salire al sesto dal sito lascia il dado da tirare', 6,
        (int) $db->esegui('SELECT da_provare FROM sdb_crisi WHERE id = ?', [$idCrisi])->fetchColumn());
    $db->esegui('UPDATE sdb_crisi SET tocca_a = "sfidante" WHERE id = ?', [$idCrisi]);
    $cede = $passo('cede', fn() => $s->crisi->rispondi($idCrisi, 'sfidante', 'cede', $tick));
    Prove::che('Washington cede', ($cede[0] ?? false) === true, $cede[1] ?? '');
    $stato = (string) $db->esegui('SELECT stato FROM sdb_crisi WHERE id = ?', [$idCrisi])->fetchColumn();
    Prove::che('e la crisi e\' chiusa', $stato !== 'aperta', $stato);

    // ------------------------------------------------------------ la delega
    Prove::gruppo('Ciclo del giocatore: la delega');
    $esito = $passo('affida', fn() => $s->delega->affida($poltA, $E, $tick));
    Prove::che('il Capo americano affida la poltrona', ($esito[0] ?? false) === true, $esito[1] ?? '');
    $perDelega = $s->scrivania->poltronaDi($E, (int) $poltA['id']);
    Prove::che('il delegato ci si puo\' sedere', $perDelega !== null && ($perDelega['per_delega'] ?? false) === true);
    Prove::che('un estraneo no', $s->scrivania->poltronaDi($D, (int) $poltA['id'])['codice'] !== 'USA');

    // «Agisce in tuo nome, e la firma resta SUA accanto alla tua negli atti»
    // (docs/20). La colonna sdb_ordine.firmato_per_delega_da esiste dalla
    // migrazione 0013, e fino all'audit nessuna riga di codice la scriveva.
    if ($perDelega !== null) {
        $passo('ordina per delega', fn() => $s->scrivania->ordina($perDelega, $possibili[$verbo], $verbo, $rus, 40, 40, $tick));
        $firma = $db->esegui('SELECT giocatore_id, firmato_per_delega_da FROM sdb_ordine
                              WHERE poltrona_id = ? ORDER BY id DESC LIMIT 1', [(int) $poltA['id']])->fetch();
        Prove::uguale('l\'ordine resta del titolare', $A, (int) ($firma['giocatore_id'] ?? 0));
        Prove::uguale('ma porta la firma del delegato', $E, (int) ($firma['firmato_per_delega_da'] ?? 0));
    }

    // Chi lascia la poltrona non puo' lasciarla in mano al delegato: il
    // delegato agiva per conto del titolare, e un titolare che se ne va non
    // ha piu' un conto per cui far agire qualcuno.
    $s->scrivania->lascia($A);
    Prove::che('lasciata la poltrona, la delega finisce',
        $s->scrivania->poltronaDi($E, (int) $poltA['id']) === null);
    $orfani = (int) $db->esegui('SELECT COUNT(*) FROM sdb_ordine WHERE giocatore_id = ? AND stato IN ("in_attesa","firmato")',
        [$A])->fetchColumn();
    Prove::uguale('e gli ordini ancora in volo di chi se ne va si ritirano', 0, $orfani);

    // ------------------------------------------------ posta e sessione
    // Un messaggio gia' preso da un altro giro della posta non si rispedisce.
    $idPosta = $s->posta->accoda('prova@example.invalid', 'Prova', 'Corpo', 'prova');
    $db->esegui('UPDATE sdb_posta SET prossimo_il = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?', [$idPosta]);
    Prove::che('un messaggio preso da un altro processo non riparte',
        ($s->posta->tenta($idPosta)['ok'] ?? true) === false);

    // La sessione vale finche' vale la parola d'ordine con cui e' nata.
    $improntaDi = static fn(string $h): string =>
        (new \ReflectionMethod(\App\Gioco\Sessione::class, 'impronta'))->invoke(null, $h);
    $hashB = (string) $db->esegui('SELECT hash_password FROM sdb_giocatore WHERE id = ?', [$B])->fetchColumn();
    $_SESSION = ['giocatore' => $B, 'impronta' => $improntaDi($hashB)];
    Prove::che('una sessione con la parola giusta resta aperta', (new \App\Gioco\Sessione($db))->id() === $B);
    $db->esegui('UPDATE sdb_giocatore SET hash_password = "cambiata" WHERE id = ?', [$B]);
    Prove::che('cambiata la parola d\'ordine, le altre sessioni si chiudono',
        (new \App\Gioco\Sessione($db))->id() === 0);
    $_SESSION = [];

    // ------------------------------------------------------------ il mercato
    Prove::gruppo('Ciclo del giocatore: il mercato');
    $f = $passo('fornitori', fn() => $s->mercato->fornitori('USA'));
    Prove::che('gli Stati Uniti hanno fornitori', is_array($f) && $f !== []);
    $cl = $passo('clienti', fn() => $s->mercato->clienti('USA'));
    Prove::che('e clienti', is_array($cl) && $cl !== []);
    $passo('armi', fn() => $s->mercato->armiDisponibili('USA'));
    $passo('strozzature', fn() => $s->mercato->strozzature($tick));

    // ------------------------------------------------------------ l'epoca
    Prove::gruppo('Ciclo del giocatore: l\'epoca');
    $corrente = $s->epoca->corrente();
    if ($corrente !== null) {
        $passo('chiudi l\'epoca in corso', fn() => $s->epoca->chiudi($tick));
    }
    $ap = $passo('apri', fn() => $s->epoca->apri('Epoca di prova', $tick, 4));
    Prove::che('si apre un\'epoca', ($ap[0] ?? false) === true, $ap[1] ?? '');
    Prove::uguale('e le restano quattro giri', 4, $s->epoca->restano($tick));
    $doppia = $passo('apri due', fn() => $s->epoca->apri('Seconda', $tick, 4));
    Prove::che('non se ne aprono due insieme', ($doppia[0] ?? true) === false);
    $ch = $passo('chiudi', fn() => $s->epoca->chiudi($tick + 4));
    Prove::che('e si chiude', ($ch[0] ?? false) === true, $ch[1] ?? '');
    $ultima = $s->epoca->chiuse()[0] ?? null;
    if ($ultima !== null) {
        $cl = $passo('classifica', fn() => $s->epoca->classifica((int) $ultima['id']));
        Prove::che('con una classifica', is_array($cl));
        $passo('rivelazioni', fn() => $s->epoca->rivelazioni((int) $ultima['id']));
    }
} finally {
    $pdo->rollBack();
}

// ============================================================ la persistenza
Prove::gruppo('Il Deposito: quel che si salva torna uguale');

// Il mondo si ricostruisce dal seme a ogni tick e poi si sovrascrive con lo
// stato salvato. Un campo che cambia in gioco e non fa il giro completo —
// salva(), poi ripristina() — torna al valore del seme senza che nessuno se ne
// accorga: e' successo alla democrazia, ai quattro interi arrotondati. Qui si
// fa il giro su un tick fittizio, dentro una transazione annullata, e si
// confronta campo per campo.
$pdo->beginTransaction();
try {
    $dep   = new Deposito($db);
    $tickV = (int) $db->esegui('SELECT COALESCE(MAX(tick), 0) FROM sdb_mondo_stato')->fetchColumn();
    $prima = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
    $ok    = $passo('ripristina il mondo vivo', fn() => $dep->ripristina($prima, $tickV));
    Prove::che('il mondo vivo si ripristina', $ok === true);

    $fittizio = $tickV + 50000;
    $passo('salva su un tick fittizio', fn() => $dep->salva($prima, $fittizio, '2099-01-01'));
    $dopo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
    $ok2  = $passo('ripristina il tick fittizio', fn() => $dep->ripristina($dopo, $fittizio));
    Prove::che('e il tick fittizio pure', $ok2 === true);

    $diversi = [];
    foreach ($prima->nazioni as $iso => $n) {
        $m = $dopo->nazioni[$iso] ?? null;
        if ($m === null) { continue; }
        foreach (get_object_vars($n) as $campo => $v) {
            $w = get_object_vars($m)[$campo] ?? null;
            if (is_float($v) || is_int($v)) {
                $tolleranza = max(1e-3, abs((float) $v) * 1e-5);
                if (abs((float) $v - (float) $w) > $tolleranza) {
                    $diversi[$campo] = ($diversi[$campo] ?? 0) + 1;
                }
            }
        }
    }
    arsort($diversi);
    Prove::che('ogni campo numerico delle nazioni fa il giro intero',
        $diversi === [],
        implode(', ', array_map(fn($c, $k) => "$c ($k paesi)", array_keys($diversi), $diversi)));

    $relDiverse = 0;
    foreach ($prima->relazioni->tutte() as $k => $r) {
        [$a, $b] = explode('|', $k);
        $r2 = $dopo->relazioni->fra($a, $b);
        if ($r2 === null || abs($r->affinita - $r2->affinita) > 0.01 || $r->obbligo !== $r2->obbligo
            || $r->obbligoFirmato !== $r2->obbligoFirmato) {
            $relDiverse++;
        }
    }
    Prove::uguale('e ogni relazione pure, obbligo firmato compreso', 0, $relDiverse);
} finally {
    $pdo->rollBack();
}
