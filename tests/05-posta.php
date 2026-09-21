<?php

declare(strict_types=1);

/**
 * La coda della posta.
 *
 * Tutte queste prove girano su un trasporto finto: la configurazione vera
 * punta a un relay che manda posta davvero, e una prova non deve poter
 * svegliare la casella di nessuno. E girano dentro una transazione annullata,
 * cosi' il mondo vivo non se ne accorge.
 */

use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;
use App\Nucleo\Posta;

$db = new Basedati((array) Configurazione::leggi('db', []));
$pdo = $db->pdo();

$pdo->beginTransaction();
try {
    $posta = new Posta($db);

    Prove::gruppo('Posta: quel che parte, parte');

    $mandati = [];
    Posta::trasportoDiProva(function (string $a, string $o, string $c) use (&$mandati): array {
        $mandati[] = [$a, $o];
        return ['ok' => true];
    });

    $r = $posta->invia('tizio@example.invalid', 'Oggetto di prova', 'Corpo', 'prova', 1);
    Prove::che('l\'invio riesce', $r['ok'] === true);
    Prove::uguale('il trasporto ha visto un messaggio', 1, count($mandati));
    Prove::uguale('con il destinatario giusto', 'tizio@example.invalid', $mandati[0][0]);

    $riga = $db->esegui('SELECT * FROM sdb_posta WHERE id = ?', [$r['id']])->fetch();
    Prove::che('resta segnato come inviato', $riga['inviato_il'] !== null);
    Prove::uguale('con un tentativo solo', 1, (int) $riga['tentativi']);

    Prove::gruppo('Posta: quel che non parte resta, e si riprova');

    Posta::trasportoDiProva(static fn(): array => ['ok' => false, 'errore' => 'relay muto']);

    $r2 = $posta->invia('caio@example.invalid', 'Non partira\'', 'Corpo', 'prova', 1);
    Prove::che('l\'invio fallisce', $r2['ok'] === false);

    $riga2 = $db->esegui('SELECT * FROM sdb_posta WHERE id = ?', [$r2['id']])->fetch();
    Prove::che('il messaggio NON e\' perduto', $riga2 !== false);
    Prove::che('non risulta inviato', $riga2['inviato_il'] === null);
    Prove::che('non risulta ancora abbandonato', $riga2['rinunciato_il'] === null);
    Prove::uguale('l\'errore e\' annotato', 'relay muto', $riga2['ultimo_errore']);
    Prove::che('il prossimo tentativo e\' nel futuro',
        strtotime((string) $riga2['prossimo_il']) > time());

    Prove::gruppo('Posta: dopo abbastanza tentativi si rinuncia, e lo si dice');

    $id = $posta->accoda('sempronio@example.invalid', 'Ostinato', 'Corpo', 'prova', 1);
    $massimo = max(1, (int) Configurazione::leggi('posta.massimo_tentativi', 6));
    $rinunciato = false;
    for ($i = 0; $i < $massimo + 2; $i++) {
        // Si sposta indietro il prossimo tentativo, se no la coda aspetta.
        $db->esegui('UPDATE sdb_posta SET prossimo_il = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = ?', [$id]);
        $x = $posta->tenta($id);
        if (!empty($x['rinunciato'])) {
            $rinunciato = true;
            break;
        }
    }
    Prove::che('prima o poi rinuncia', $rinunciato);
    $riga3 = $db->esegui('SELECT * FROM sdb_posta WHERE id = ?', [$id])->fetch();
    Prove::che('e resta agli atti con il motivo',
        $riga3['rinunciato_il'] !== null && (string) $riga3['ultimo_errore'] !== '');

    Prove::gruppo('Posta: lo smistamento non tocca quel che non tocca');

    Posta::trasportoDiProva(static fn(): array => ['ok' => true]);
    $futuro = $posta->accoda('futuro@example.invalid', 'Fra un\'ora', 'Corpo', 'prova', 9);
    $db->esegui('UPDATE sdb_posta SET prossimo_il = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?', [$futuro]);
    $pronto = $posta->accoda('pronto@example.invalid', 'Adesso', 'Corpo', 'prova', 1);

    $esito = $posta->smista(10);
    $rigaF = $db->esegui('SELECT inviato_il FROM sdb_posta WHERE id = ?', [$futuro])->fetch();
    $rigaP = $db->esegui('SELECT inviato_il FROM sdb_posta WHERE id = ?', [$pronto])->fetch();

    Prove::che('il messaggio pronto e\' partito', $rigaP['inviato_il'] !== null);
    Prove::che('quello rimandato e\' rimasto fermo', $rigaF['inviato_il'] === null);
    Prove::che('lo smistamento riferisce quel che ha fatto', $esito['inviati'] >= 1);

    Prove::gruppo('Posta: il tetto del fornitore si rispetta');

    $stato = $posta->stato();
    Prove::che('il tetto e\' un numero sensato', $stato['tetto'] > 0 && $stato['tetto'] <= 10000);
    Prove::che('la coda sa dire quanti ne restano', $stato['in_coda'] >= 0);
} finally {
    Posta::trasportoDiProva(null);
    $pdo->rollBack();
}

Prove::gruppo('Posta: la prova non ha lasciato tracce nel mondo vivo');

$rimasti = (int) $db->esegui(
    'SELECT COUNT(*) FROM sdb_posta WHERE destinatario LIKE "%@example.invalid"')->fetchColumn();
Prove::uguale('nessun messaggio di prova sopravvive', 0, $rimasti);
