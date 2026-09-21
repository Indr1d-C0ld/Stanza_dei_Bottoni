<?php

declare(strict_types=1);

/**
 * Gli avvisi di gioco e il recupero della parola d'ordine.
 *
 * Tutto dentro una transazione annullata e con un trasporto di posta finto: si
 * creano giocatori, poltrone e crisi finte, si guarda cosa succede, e alla fine
 * non resta niente.
 */

use App\Gioco\Avvisi;
use App\Gioco\Delega;
use App\Gioco\Sessione;
use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;
use App\Nucleo\Posta;

$db  = new Basedati((array) Configurazione::leggi('db', []));
$pdo = $db->pdo();
$pdo->beginTransaction();

try {
    Posta::trasportoDiProva(static fn(): array => ['ok' => true]);

    // Gli avvisi si ACCODANO, non si spediscono subito: partono col prossimo
    // giro di bin/posta.php. Quindi si guarda la coda, non il trasporto — il
    // trasporto finto non verrebbe mai chiamato, e la prima stesura di queste
    // prove falliva tutta per questo.
    $inCoda = static function () use ($db): array {
        return $db->esegui(
            'SELECT oggetto, corpo FROM sdb_posta
             WHERE destinatario = "avvisi@example.invalid" AND genere = "avviso_gioco"
             ORDER BY id DESC')->fetchAll();
    };
    $svuota = static function () use ($db): void {
        $db->esegui('DELETE FROM sdb_posta WHERE destinatario = "avvisi@example.invalid"');
    };

    $tick = (int) $db->esegui('SELECT COALESCE(MAX(tick), 0) FROM sdb_mondo_stato')->fetchColumn();

    // Un giocatore finto su una poltrona libera.
    $db->esegui(
        'INSERT INTO sdb_giocatore (nome, email, hash_password, creato, email_verificata,
                                    verificata_il, avvisi)
         VALUES ("ProvaAvvisi", "avvisi@example.invalid", "x", NOW(), 1, NOW(), 1)');
    $giocatore = $db->ultimoId();

    $poltrona = (int) $db->esegui(
        'SELECT p.id FROM sdb_poltrona p JOIN sdb_nazione n ON n.id = p.nazione_id
         WHERE p.giocatore_id IS NULL AND n.codice = "IND" AND p.ruolo = "esteri"')->fetchColumn();
    $nazione = (int) $db->esegui('SELECT nazione_id FROM sdb_poltrona WHERE id = ?',
        [$poltrona])->fetchColumn();
    $db->esegui('UPDATE sdb_poltrona SET giocatore_id = ?, ultimo_tick_attivo = ? WHERE id = ?',
        [$giocatore, $tick, $poltrona]);

    $avvisi = new Avvisi($db);

    Prove::gruppo('Avvisi: se non c\'e\' niente da dire, non si scrive');

    $svuota();
    $avvisi->manda($tick);
    Prove::uguale('nessun messaggio a chi non ha niente in sospeso', 0, count($inCoda()));

    Prove::gruppo('Avvisi: una crisi che aspetta si dice, e si dice quanto manca');

    // Una crisi in cui tocca a noi.
    $altro = (int) $db->esegui('SELECT id FROM sdb_nazione WHERE codice = "PAK"')->fetchColumn();
    $db->esegui(
        'INSERT INTO sdb_crisi (sfidante_id, sfidato_id, evento_id, oggetto_id, livello,
                                posta_sfidante, posta_sfidato, tocca_a, stato, aperta_tick,
                                ultimo_tick, scade_tick)
         VALUES (?,?,NULL,NULL,4,10,12,"sfidato","aperta",?,?,?)',
        [$altro, $nazione, $tick, $tick, $tick + 2]);

    $db->esegui('UPDATE sdb_giocatore SET ultimo_avviso_tick = 0 WHERE id = ?', [$giocatore]);
    $svuota();
    $avvisi->manda($tick);
    $miei = $inCoda();

    Prove::uguale('arriva un messaggio', 1, count($miei));
    Prove::che('che nomina la crisi',
        $miei !== [] && str_contains($miei[0]['corpo'], 'crisi'));
    Prove::che('e dice con chi',
        $miei !== [] && str_contains($miei[0]['corpo'], 'Pakistan'),
        mb_substr($miei[0]['corpo'] ?? '', 0, 120));
    Prove::che('e quanti giri restano',
        $miei !== [] && str_contains($miei[0]['corpo'], 'Restano 2 giri'));
    Prove::che('con il collegamento alla scrivania',
        $miei !== [] && str_contains($miei[0]['corpo'], '/scrivania'));

    Prove::gruppo('Avvisi: non si scrive due volte di seguito');

    $svuota();
    $avvisi->manda($tick);
    Prove::uguale('il secondo giro non manda niente', 0, count($inCoda()));

    $ogni = max(1, (int) Configurazione::leggi('posta.avviso_ogni_tick', 12));
    $svuota();
    $avvisi->manda($tick + $ogni);
    Prove::uguale('ma dopo la pausa si', 1, count($inCoda()));

    Prove::gruppo('Avvisi: la scadenza urgente cambia il tono');

    $db->esegui('UPDATE sdb_crisi SET scade_tick = ? WHERE sfidato_id = ? AND stato = "aperta"',
        [$tick - 1, $nazione]);
    $db->esegui('UPDATE sdb_giocatore SET ultimo_avviso_tick = 0 WHERE id = ?', [$giocatore]);
    $svuota();
    $avvisi->manda($tick);
    $miei = $inCoda();
    Prove::che('l\'oggetto dice che non puo\' aspettare',
        $miei !== [] && str_contains($miei[0]['oggetto'], 'non puo'),
        $miei[0]['oggetto'] ?? 'nessun messaggio');
    Prove::che('e il corpo dice che varra\' come una resa',
        $miei !== [] && str_contains($miei[0]['corpo'], 'resa'));

    Prove::gruppo('Avvisi: la poltrona che sta per sfuggire');

    $db->esegui('DELETE FROM sdb_crisi WHERE sfidato_id = ? AND stato = "aperta"', [$nazione]);
    $db->esegui('UPDATE sdb_poltrona SET ultimo_tick_attivo = ? WHERE id = ?',
        [$tick - Delega::TICK_PRIMA_DELL_APPARATO + 1, $poltrona]);
    $db->esegui('UPDATE sdb_giocatore SET ultimo_avviso_tick = 0 WHERE id = ?', [$giocatore]);
    $svuota();
    $avvisi->manda($tick);
    $miei = $inCoda();
    Prove::che('si avverte prima che sia troppo tardi',
        $miei !== [] && str_contains($miei[0]['corpo'], 'apparato'),
        $miei !== [] ? mb_substr($miei[0]['corpo'], 0, 120) : 'nessun messaggio');

    Prove::gruppo('Avvisi: chi li spegne non li riceve');

    $db->esegui('UPDATE sdb_giocatore SET avvisi = 0, ultimo_avviso_tick = 0 WHERE id = ?',
        [$giocatore]);
    $svuota();
    $avvisi->manda($tick);
    Prove::uguale('spenti vuol dire spenti', 0, count($inCoda()));

    Prove::gruppo('Recupero: il collegamento vale una volta sola');

    $s = new Sessione($db);
    $db->esegui('UPDATE sdb_giocatore SET hash_password = ? WHERE id = ?',
        [password_hash('vecchiaparola', PASSWORD_DEFAULT), $giocatore]);

    [$ok] = $s->dimenticata('avvisi@example.invalid');
    Prove::che('la richiesta non dice mai se l\'indirizzo esiste', $ok);
    [$okFinto, $dettoFinto] = $s->dimenticata('nessuno@example.invalid');
    [, $dettoVero] = $s->dimenticata('avvisi@example.invalid');
    Prove::uguale('e la risposta e\' identica nei due casi', $dettoVero, $dettoFinto);

    $gettone = (string) $db->esegui('SELECT gettone_reimposta FROM sdb_giocatore WHERE id = ?',
        [$giocatore])->fetchColumn();
    Prove::che('un gettone c\'e\'', $gettone !== '' && strlen($gettone) === 64);
    Prove::che('e riconosce il suo proprietario', $s->gettoneReimposta($gettone) !== null);
    Prove::che('un gettone inventato non vale', $s->gettoneReimposta(str_repeat('a', 64)) === null);

    [$no1] = $s->reimposta($gettone, 'abcdefghij', 'diversa123');
    Prove::che('due parole diverse vengono rifiutate', !$no1);
    [$no2] = $s->reimposta($gettone, 'corta', 'corta');
    Prove::che('una parola troppo corta viene rifiutata', !$no2);

    [$si] = $s->reimposta($gettone, 'parolanuova1', 'parolanuova1');
    Prove::che('una buona viene accettata', $si);

    $hash = (string) $db->esegui('SELECT hash_password FROM sdb_giocatore WHERE id = ?',
        [$giocatore])->fetchColumn();
    Prove::che('la parola nuova funziona', password_verify('parolanuova1', $hash));
    Prove::che('la vecchia non piu\'', !password_verify('vecchiaparola', $hash));
    Prove::che('e il gettone e\' bruciato', $s->gettoneReimposta($gettone) === null);

    Prove::gruppo('Recupero: un collegamento scaduto non vale');

    [$x] = $s->dimenticata('avvisi@example.invalid');
    $db->esegui('UPDATE sdb_giocatore SET reimposta_scade = DATE_SUB(NOW(), INTERVAL 1 MINUTE),
                        reimposta_chiesta = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = ?',
        [$giocatore]);
    $scaduto = (string) $db->esegui('SELECT gettone_reimposta FROM sdb_giocatore WHERE id = ?',
        [$giocatore])->fetchColumn();
    Prove::che('scaduto non vale', $s->gettoneReimposta($scaduto) === null);
} finally {
    Posta::trasportoDiProva(null);
    $pdo->rollBack();
}

Prove::gruppo('Avvisi: la prova non ha lasciato niente');

Prove::uguale('nessun giocatore di prova sopravvive', 0, (int) $db->esegui(
    'SELECT COUNT(*) FROM sdb_giocatore WHERE nome = "ProvaAvvisi"')->fetchColumn());
Prove::uguale('nessuna poltrona resta occupata dalla prova', 0, (int) $db->esegui(
    'SELECT COUNT(*) FROM sdb_poltrona p LEFT JOIN sdb_giocatore g ON g.id = p.giocatore_id
     WHERE p.giocatore_id IS NOT NULL AND g.id IS NULL')->fetchColumn());
