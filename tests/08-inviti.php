<?php

declare(strict_types=1);

/**
 * Gli inviti.
 *
 * La prova che conta e' che la leva funzioni: l'arbitro cambia il modo delle
 * registrazioni e il modo cambia davvero. Alla prima stesura non succedeva —
 * la leva finiva nella calibrazione e Inviti::modo() leggeva la configurazione,
 * due posti diversi che non si parlavano. L'arbitro muoveva la leva e non
 * accadeva niente, senza nemmeno un errore.
 */

use App\Gioco\Arbitrio;
use App\Gioco\Inviti;
use App\Gioco\Sessione;
use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;
use App\Nucleo\Configurazione;
use App\Nucleo\Posta;

$db  = new Basedati((array) Configurazione::leggi('db', []));
$pdo = $db->pdo();
$leveDiPrima = (new Arbitrio($db))->leveImposte();

$pdo->beginTransaction();
try {
    Posta::trasportoDiProva(static fn(): array => ['ok' => true]);
    $inviti = new Inviti($db);
    $sessione = new Sessione($db);

    $arbitro = (int) $db->esegui('SELECT id FROM sdb_giocatore WHERE ruolo = "arbitro" LIMIT 1')
        ->fetchColumn();

    $imponi = static function (string $modo) use ($db): void {
        $db->esegui(
            'INSERT INTO sdb_leva (chiave, valore, valore_prima, cambiata_da, nota)
             VALUES ("gioco.registrazioni", ?, NULL, 1, "prova")
             ON DUPLICATE KEY UPDATE valore = VALUES(valore)', [json_encode($modo)]);
        Calibrazione::imponiLeve((new Arbitrio($db))->leveImposte());
    };

    Prove::gruppo('Inviti: la leva dell\'arbitro comanda davvero');

    foreach (['aperte', 'invito', 'chiuse'] as $modo) {
        $imponi($modo);
        Prove::uguale("con la leva su «$modo» il modo e\' quello", $modo, $inviti->modo());
    }

    Prove::gruppo('Inviti: a porte aperte entra chiunque');

    $imponi('aperte');
    [$puo] = $inviti->ammesso('');
    Prove::che('senza codice si passa lo stesso', $puo);

    Prove::gruppo('Inviti: a porte chiuse non entra nessuno');

    $imponi('chiuse');
    [$puo, $perche] = $inviti->ammesso('QUALSIASI-COSA');
    Prove::che('nemmeno con un codice', !$puo);
    Prove::che('e si dice perche\'', str_contains($perche, 'chiuse'));

    Prove::gruppo('Inviti: a invito serve il codice, e vale una volta');

    $imponi('invito');
    [$puo, $perche] = $inviti->ammesso('');
    Prove::che('senza codice non si entra', !$puo);
    [$puo] = $inviti->ammesso('NONESISTE1');
    Prove::che('con un codice inventato nemmeno', !$puo);

    $inviti->crea($arbitro, 'prova automatica', 1);
    $codice = (string) $db->esegui(
        'SELECT codice FROM sdb_invito WHERE nota = "prova automatica" AND usato_da IS NULL
         ORDER BY creato_il DESC LIMIT 1')->fetchColumn();

    Prove::che('il codice si puo\' dettare: niente 0, O, 1, I, L',
        preg_match('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789-]+$/', $codice) === 1, $codice);
    Prove::che('ha una forma riconoscibile', str_contains($codice, '-'), $codice);

    [$puo] = $inviti->ammesso($codice);
    Prove::che('col codice buono si entra', $puo);
    [$puo] = $inviti->ammesso(strtolower($codice));
    Prove::che('e non importa come lo scrivi', $puo);

    // Lo si usa davvero: una registrazione vera lo consuma.
    [$fatto] = $sessione->registra('ProvaInvitoAuto', 'invito-auto@example.invalid',
        'parolalunghissima', $codice);
    Prove::che('la registrazione col codice riesce', $fatto);

    [$puo, $perche] = $inviti->ammesso($codice);
    Prove::che('ma il codice e\' bruciato', !$puo);
    Prove::che('e lo dice chiaramente', str_contains($perche, 'una volta sola'));

    $usato = $db->esegui('SELECT usato_da, usato_il FROM sdb_invito WHERE codice = ?',
        [$codice])->fetch();
    Prove::che('resta scritto chi l\'ha usato', $usato['usato_da'] !== null);

    Prove::gruppo('Inviti: uno scaduto non vale');

    $inviti->crea($arbitro, 'prova scaduta', 1);
    $vecchio = (string) $db->esegui(
        'SELECT codice FROM sdb_invito WHERE nota = "prova scaduta" ORDER BY creato_il DESC LIMIT 1')
        ->fetchColumn();
    $db->esegui('UPDATE sdb_invito SET scade_il = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE codice = ?',
        [$vecchio]);
    [$puo, $perche] = $inviti->ammesso($vecchio);
    Prove::che('scaduto non vale', !$puo);
    Prove::che('e si dice che e\' scaduto', str_contains($perche, 'scaduto'));

    Prove::gruppo('Inviti: la revoca');

    $inviti->crea($arbitro, 'prova revoca', 1);
    $daRevocare = (string) $db->esegui(
        'SELECT codice FROM sdb_invito WHERE nota = "prova revoca" ORDER BY creato_il DESC LIMIT 1')
        ->fetchColumn();
    [$tolto] = $inviti->revoca($daRevocare);
    Prove::che('un codice libero si revoca', $tolto);
    [$dinuovo] = $inviti->revoca($daRevocare);
    Prove::che('ma non due volte', !$dinuovo);
    [$usatoNo] = $inviti->revoca($codice);
    Prove::che('e uno gia\' usato non si revoca', !$usatoNo);

    Prove::gruppo('Inviti: la leva rifiuta le parole che non esistono');

    $arb = new Arbitrio($db);
    $cal = Calibrazione::carica(dirname(__DIR__), 'gioco');
    [$no, $detto] = $arb->muoviLeva($arbitro, 'gioco.registrazioni', 'spalancate', $cal, 0);
    Prove::che('«spalancate» non e\' un modo', !$no);
    Prove::che('e si elencano quelli buoni', str_contains($detto, 'aperte'));
} finally {
    Posta::trasportoDiProva(null);
    $pdo->rollBack();
    // La calibrazione e' statica e non torna indietro da sola con la
    // transazione: va rimessa a mano, o le prove dopo questa vedrebbero un
    // mondo tarato male.
    Calibrazione::imponiLeve($leveDiPrima);
}

Prove::gruppo('Inviti: la prova non ha lasciato niente');

Prove::uguale('nessun invito di prova', 0, (int) $db->esegui(
    'SELECT COUNT(*) FROM sdb_invito WHERE nota LIKE "prova %"')->fetchColumn());
Prove::uguale('nessun giocatore di prova', 0, (int) $db->esegui(
    'SELECT COUNT(*) FROM sdb_giocatore WHERE nome = "ProvaInvitoAuto"')->fetchColumn());
