<?php

declare(strict_types=1);

/**
 * Il registro delle operazioni, il quadro del servizio, le contromosse.
 *
 * PERCHE' ESISTE. Un giocatore da solo all'Intelligence iraniana ha dato due
 * ordini: l'apparato li ha firmati, sono partiti, Israele li ha fermati — e lui
 * non ne ha saputo niente (docs/29). Qui si verifica che adesso lo sappia, e
 * che le scelte sulle operazioni altrui facciano quel che promettono.
 *
 * Tutto dentro una transazione annullata, con un giocatore finto alla
 * Francia e un tick vero fatto girare su una copia del mondo vivo.
 */

use App\Dati\Deposito;
use App\Dati\Mondo;
use App\Gioco\Contromosse;
use App\Gioco\Operazioni;
use App\Gioco\Servizi;
use App\Nucleo\Basedati;
use App\Nucleo\Calendario;
use App\Nucleo\Calibrazione;
use App\Nucleo\Configurazione;
use App\Simulazione\EsecutoreTick;

$radiceReg = dirname(__DIR__);
$dbReg     = new Basedati((array) Configurazione::leggi('db', []));
$calReg    = Calibrazione::carica($radiceReg, 'gioco');
$pdoReg    = $dbReg->pdo();

$passoReg = static function (string $cosa, callable $f): mixed {
    try {
        return $f();
    } catch (\Throwable $e) {
        Prove::che($cosa . ' — senza eccezioni', false, get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 160));
        return null;
    }
};

$pdoReg->beginTransaction();
try {
    $sr   = new Servizi($dbReg, $calReg, $radiceReg);
    $tick = (int) $dbReg->esegui('SELECT COALESCE(MAX(tick), 0) FROM sdb_mondo_stato')->fetchColumn();
    $id   = static fn(string $iso): int => (int) $dbReg->esegui('SELECT id FROM sdb_nazione WHERE codice = ?', [$iso])->fetchColumn();
    $fra = $id('FRA'); $rus = $id('RUS'); $deu = $id('DEU');

    // Un giocatore finto all'Intelligence francese; il Capo resta alla macchina.
    $dbReg->esegui(
        'INSERT INTO sdb_giocatore (nome, email, hash_password, creato, email_verificata, verificata_il, avvisi)
         VALUES ("ProvaRegistro", "registro@example.invalid", "x", NOW(), 1, NOW(), 0)');
    $G = $dbReg->ultimoId();
    $dbReg->esegui('UPDATE sdb_poltrona SET giocatore_id = NULL WHERE nazione_id = ?', [$fra]);
    $seggio = (int) $dbReg->esegui('SELECT id FROM sdb_poltrona WHERE nazione_id = ? AND ruolo = "intelligence"',
        [$fra])->fetchColumn();
    $passoReg('occupa', fn() => $sr->scrivania->occupa($G, $seggio, $tick));
    $intel = $sr->scrivania->poltronaDi($G);

    // ------------------------------------------------------------ il registro
    Prove::gruppo('Il registro: l\'ordine dell\'Intelligence e la firma dell\'apparato');
    $catalogo = require $radiceReg . '/calibrazione/verbi.php';
    $esito = $passoReg('ordina', fn() => $sr->scrivania->ordina($intel, $catalogo['sabotaggio'], 'sabotaggio',
        $rus, 60, 40, $tick));
    Prove::che('l\'ordine dice che la firma la decide l\'apparato',
        ($esito[0] ?? false) && str_contains((string) $esito[1], 'apparato'), (string) ($esito[1] ?? ''));
    $miei = $sr->scrivania->mieiOrdini($G, $tick);
    Prove::uguale('e la Cartella sa che il Capo non e\' una persona al tavolo', 0,
        (int) ($miei[0]['firmatario_presente'] ?? -1));

    // Due operazioni altrui che il servizio francese conosce al terzo
    // gradino: una contro la Francia, una contro la Germania.
    $nuovoEvento = static function (int $mandante, int $bersaglio) use ($dbReg, $tick): int {
        $dbReg->esegui(
            'INSERT INTO sdb_evento (dominio, verbo, mandante_id, bersaglio_id, intensita, copertura, impronta,
                                     creato_tick, maturazione_tick, danno_base, attribuzione_vera, stato)
             VALUES ("int", "sabotaggio", ?, ?, 60, 20, 40, ?, ?, 40, 60, "in_volo")',
            [$mandante, $bersaglio, max(0, $tick - 1), $tick + 6]);
        return $dbReg->ultimoId();
    };
    $controNoi = $nuovoEvento($rus, $fra);
    $controAltri = $nuovoEvento($rus, $deu);
    $timido = $nuovoEvento($rus, $fra);
    foreach ([[$controNoi, 3], [$controAltri, 3], [$timido, 2]] as [$ev, $liv]) {
        $dbReg->esegui('INSERT INTO sdb_conoscenza (osservatore_id, evento_id, livello, primo_tick, confidenza, aggiornata_tick)
                        VALUES (?,?,?,?,60,?)', [$fra, $ev, $liv, $tick, $tick]);
    }

    // -------------------------------------------------------------- il quadro
    Prove::gruppo('Il quadro del servizio e le scelte possibili');
    $quadro = $sr->operazioni->quadro($fra, $tick);
    $riga = static fn(int $ev): ?array => array_values(array_filter($quadro,
        static fn(array $k): bool => (int) $k['evento'] === $ev))[0] ?? null;
    Prove::che('il servizio vede le tre operazioni', $riga($controNoi) && $riga($controAltri) && $riga($timido));
    Prove::uguale('contro di noi: sventare, seguire, far trapelare',
        ['sventa', 'sorveglia', 'trapela'], Contromosse::possibili($riga($controNoi) ?? [], $fra));
    Prove::uguale('contro altri: avvisare, far trapelare', ['avvisa', 'trapela'],
        Contromosse::possibili($riga($controAltri) ?? [], $fra));
    Prove::uguale('al secondo gradino ancora niente', [], Contromosse::possibili($riga($timido) ?? [], $fra));
    $no = $passoReg('scegli al secondo gradino', fn() => $sr->contromosse->scegli($intel, $timido, 'sventa', $tick));
    Prove::che('e scegliere lo stesso viene rifiutato', ($no[0] ?? true) === false);
    $esteri = $intel;
    $esteri['ruolo'] = 'esteri';
    $no = $passoReg('scegli dagli Esteri', fn() => $sr->contromosse->scegli($esteri, $controNoi, 'sventa', $tick));
    Prove::che('gli Esteri non decidono le contromosse', ($no[0] ?? true) === false);

    $ok1 = $passoReg('trapela', fn() => $sr->contromosse->scegli($intel, $controNoi, 'trapela', $tick));
    $ok2 = $passoReg('avvisa', fn() => $sr->contromosse->scegli($intel, $controAltri, 'avvisa', $tick));
    Prove::che('far trapelare e avvisare si accettano', ($ok1[0] ?? false) && ($ok2[0] ?? false));

    // ------------------------------------------------------ un tick vero
    Prove::gruppo('Le contromosse, al giro d\'orologio');
    $dep = new Deposito($dbReg);
    $mondo = Mondo::daSeme($radiceReg . '/db/seed/nazioni.csv');
    $passoReg('ripristina', fn() => $dep->ripristina($mondo, $tick));
    $mondo->tick = $tick + 1;
    $passoReg('tick', fn() => (new EsecutoreTick($calReg, $dbReg, false, $mondo))->esegui($tick + 1, 1, null));
    $passoReg('salva', fn() => $dep->salva($mondo, $tick + 1, Calendario::tickIso($tick + 1)));

    $k = static fn(int $ev): array => $dbReg->esegui(
        'SELECT stato, esito FROM sdb_contromossa WHERE nazione_id = ? AND evento_id = ?', [$fra, $ev])->fetch() ?: [];
    Prove::uguale('la fuga di notizie si e\' compiuta', 'conclusa', $k($controNoi)['stato'] ?? null);
    Prove::che('col suo esito', str_starts_with((string) ($k($controNoi)['esito'] ?? ''), 'rivelata'),
        (string) ($k($controNoi)['esito'] ?? ''));
    $notizia = (int) $dbReg->esegui('SELECT COUNT(*) FROM sdb_notizia WHERE tick = ? AND genere = "operazione_rivelata"',
        [$tick + 1])->fetchColumn();
    Prove::che('ed e\' in cronaca', $notizia >= 1);
    Prove::che('l\'avviso e\' arrivato', str_starts_with((string) ($k($controAltri)['esito'] ?? ''), 'avvisato'),
        (string) ($k($controAltri)['esito'] ?? ''));
    $saDeu = (int) $dbReg->esegui('SELECT livello FROM sdb_conoscenza WHERE osservatore_id = ? AND evento_id = ?',
        [$deu, $controAltri])->fetchColumn();
    Prove::che('e la Germania ora sa quel che sapevamo noi', $saDeu >= 3, (string) $saDeu);

    $ordine = $dbReg->esegui('SELECT stato, firmato_da_apparato, evento_id FROM sdb_ordine WHERE giocatore_id = ?',
        [$G])->fetch() ?: [];
    Prove::uguale('l\'apparato ha deciso sulla firma', 1, (int) ($ordine['firmato_da_apparato'] ?? 0));
    if (($ordine['stato'] ?? '') === 'eseguito') {
        Prove::che('l\'ordine eseguito sa quale operazione ha fatto partire', (int) $ordine['evento_id'] > 0);
        $reg = $sr->operazioni->registro($seggio);
        Prove::uguale('e il registro la mostra in corso', 'in_volo', $reg[0]['esito'] ?? null);
    } else {
        Prove::uguale('l\'apparato ha negato: il registro lo dice', 'annullato', $ordine['stato'] ?? null);
    }
    Prove::che('il quadro lo vede chi dirige il servizio o il governo',
        Operazioni::vedeIlQuadro('intelligence') && Operazioni::vedeIlQuadro('capo') && !Operazioni::vedeIlQuadro('difesa'));
} finally {
    $pdoReg->rollBack();
}

Prove::gruppo('Il registro dice anche che cosa ha fatto un\'operazione andata a segno');

// Chi l'ha ordinata ne conosce l'esito; il mondo no (docs/39). I numeri sono
// gli stessi che il motore applica: stanno in un posto solo.
$racconto = App\Simulazione\Fasi\Fase02Maturazione::raccontaEffetto('sabotaggio', 61);
Prove::che('un sabotaggio al 61% ha tolto lo 0,24% del prodotto e l\'1,5% dell\'equipaggiamento',
    $racconto === 'Ha tolto lo 0,24% del prodotto e l\'1,5% dell\'equipaggiamento militare.', (string) $racconto);
Prove::che('e i verbi non coperti non hanno racconto',
    App\Simulazione\Fasi\Fase02Maturazione::raccontaEffetto('emissario', 50) === null);
