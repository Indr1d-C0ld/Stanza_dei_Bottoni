<?php

declare(strict_types=1);

/**
 * Stanza dei Bottoni — l'osservatorio.
 *
 * Per ora il web e' in sola lettura: mostra il mondo, non lo tocca. Le azioni
 * dei giocatori richiedono autenticazione, poltrone assegnate e il vaglio delle
 * controfirme, e arrivano dopo.
 */

define('STANZA_DEI_BOTTONI', true);
$radice = __DIR__;

require $radice . '/src/autoload.php';
require $radice . '/src/Supporto/aiutanti.php';

use App\Dati\Lettura;
use App\Dati\Planisfero;
use App\Gioco\Agende;
use App\Gioco\Arbitrio;
use App\Gioco\Canale;
use App\Gioco\Crisi;
use App\Gioco\Delega;
use App\Gioco\Epoca;
use App\Gioco\Falsificazione;
use App\Gioco\Inviti;
use App\Gioco\Linea;
use App\Gioco\Mercato;
use App\Gioco\Reclutamento;
use App\Gioco\Servizi;
use App\Gioco\Scrivania;
use App\Gioco\Sessione;
use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;

try {
    Configurazione::carica($radice);
} catch (\Throwable $e) {
    http_response_code(500);
    exit('Configurazione assente.');
}
date_default_timezone_set((string) Configurazione::leggi('app.timezone', 'Europe/Rome'));

// Dove siamo montati. A radice del sito e' stringa vuota; sotto un Alias di
// Apache — che e' come sta su questa macchina, perche' l'HSTS del vhost rompe i
// servizi esposti per porta — e' il sotto-percorso. Senza questo l'applicazione
// funziona solo a radice: la prima briciola del percorso verrebbe scambiata per
// il nome di una rotta, e ogni collegamento punterebbe fuori dal programma.
$base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
if ($base === '.' || $base === '/') {
    $base = '';
}
define('BASE', $base);

/** Un percorso interno, montato dove siamo montati. */
function u(string $percorso = '/'): string
{
    return BASE . $percorso;
}

/**
 * Un numero scritto all'italiana: punto per le migliaia, virgola per i decimi.
 *
 * Le date le avevamo gia' messe in GG/MM/AAAA; i numeri erano rimasti
 * all'anglosassone in trentatre' punti su trentotto, e «25,680 mld» un occhio
 * italiano lo legge venticinque virgola sei.
 */
function n(float $x, int $decimali = 0): string
{
    return number_format($x, $decimali, ',', '.');
}

$percorso = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
if (BASE !== '' && str_starts_with('/' . $percorso, BASE . '/')) {
    $percorso = trim(substr('/' . $percorso, strlen(BASE)), '/');
} elseif (BASE !== '' && '/' . $percorso === BASE) {
    $percorso = '';
}
$pezzi    = $percorso === '' ? [] : explode('/', $percorso);

$db       = new Basedati((array) Configurazione::leggi('db', []));
// Le leve che l'arbitro ha mosso si impongono PRIMA di caricare la taratura:
// altrimenti il web mostrerebbe numeri diversi da quelli con cui gira il tick.
App\Nucleo\Calibrazione::imponiLeve(
    (new Arbitrio($db))->leveImposte());
$calCrisi = App\Nucleo\Calibrazione::carica(__DIR__, (string) Configurazione::leggi('mondo.profilo', 'gioco'));
$s        = new Servizi($db, $calCrisi, __DIR__);

// Nomi brevi per le viste, che li usano a piene mani. Gli oggetti sono gli
// stessi: $s resta l'unica cosa che si passa in giro.
$lettura      = $s->lettura;
$sessione     = $s->sessione;
$scrivania    = $s->scrivania;
$canale       = $s->canale;
$reclutamento = $s->reclutamento;
$crisi        = $s->crisi;
$agende       = $s->agende;
$falso        = $s->falso;
$linea        = $s->linea;
$delega       = $s->delega;
$epoca        = $s->epoca;
$mercato      = $s->mercato;
$arbitrio     = $s->arbitrio;
$avviso    = null;

if (!$lettura->mondoAvviato()) {
    mostra('non-avviato', ['titolo' => 'Il mondo non è ancora avviato']);
    exit;
}

$stato = $lettura->statoMondo();
$tick  = (int) ($stato['tick'] ?? 0);

$poltrona = $sessione->autenticato()
    ? $scrivania->poltronaDi($sessione->id(), isset($_SESSION['poltrona_scelta']) ? (int) $_SESSION['poltrona_scelta'] : null)
    : null;

// --- i moduli inviati ------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$sessione->gettoneValido($_POST['gettone'] ?? null)) {
        $avviso = [false, 'Modulo scaduto: riprova.'];
    } else {
        // Chi agisce e' presente: e' questo che tiene l'apparato lontano
        // dalla sua poltrona per un altro po'.
        if ($poltrona !== null) {
            $s->delega->tocca((int) $poltrona['id'], $tick);
        }
        [$avviso, $vai] = azione($_POST, $s, $poltrona, $tick);
        if ($vai !== null) {
            $_SESSION['avviso'] = $avviso;
            header('Location: ' . u($vai));
            exit;
        }
        $poltrona = $sessione->autenticato()
    ? $scrivania->poltronaDi($sessione->id(), isset($_SESSION['poltrona_scelta']) ? (int) $_SESSION['poltrona_scelta'] : null)
    : null;
    }
}
if (isset($_SESSION['avviso'])) {
    $avviso = $_SESSION['avviso'];
    unset($_SESSION['avviso']);
}

$comune = [
    'stato'     => $stato,
    'tick'      => $tick,
    'lettura'   => $lettura,
    'sessione'  => $sessione,
    'scrivania' => $scrivania,
    'poltrona'  => $poltrona,
    'canale'    => $canale,
    'avviso'    => $avviso,
];

switch ($pezzi[0] ?? '') {
    case '':
        // Il planisfero in testa al cruscotto: e' la prima cosa che si guarda,
        // e una mappa dice in un colpo d'occhio quel che nelle tabelle si
        // legge in due minuti. Qui non e' cliccabile — si va alla pagina
        // apposita per quello — perche' su un disegno piccolo si sbaglia mira.
        $planisfero = new Planisfero(__DIR__ . '/db/seed/confini-svg.json');
        $tutte = $lettura->nazioni($tick, 'nome');
        $nomiMappa = [];
        foreach ($tutte as $n) {
            $nomiMappa[(string) $n['codice']] = (string) $n['nome'];
        }
        mostra('cruscotto', $comune + [
            'titolo'  => 'Il mondo',
            'potenze' => $lettura->nazioni($tick, 'influenza_totale', 12),
            'cronaca' => $lettura->cronaca(14),
            'guerre'  => $lettura->guerre(),
            'planisfero'    => $planisfero,
            'colori'        => $planisfero->colori('tensione', $tutte),
            'nomiMappa'     => $nomiMappa,
            'letturaScelta' => 'tensione',
            'cliccabile'    => false,
        ]);
        break;

    case 'nazioni':
        $ordine = (string) ($_GET['ordine'] ?? 'influenza_totale');
        mostra('nazioni', $comune + [
            'titolo'  => 'Le nazioni',
            'ordine'  => $ordine,
            'nazioni' => $lettura->nazioni($tick, $ordine),
        ]);
        break;

    case 'nazione':
        $codice = strtoupper((string) ($pezzi[1] ?? ''));
        $n = $lettura->nazione($codice, $tick);
        if ($n === null) {
            http_response_code(404);
            mostra('non-trovato', ['titolo' => 'Paese sconosciuto']);
            break;
        }
        mostra('nazione', $comune + [
            'titolo'     => $n['nome'],
            'n'          => $n,
            'relazioni'  => $lettura->relazioni($codice),
            'gabinetto'  => $lettura->gabinetto($codice),
            'fazioni'    => $lettura->fazioni($codice),
        ]);
        break;

    case 'entra':
        mostra('entra', $comune + ['titolo' => 'Entra']);
        break;

    case 'guida':
        mostra('guida', $comune + ['titolo' => 'Come si gioca']);
        break;

    case 'dimenticata':
        mostra('dimenticata', $comune + ['titolo' => 'Parola d\'ordine dimenticata']);
        break;

    case 'reimposta':
        $gettoneReimposta = (string) ($_GET['g'] ?? $_POST['g'] ?? '');
        mostra('reimposta', $comune + [
            'titolo'  => 'Una parola d\'ordine nuova',
            'chi'     => $sessione->gettoneReimposta($gettoneReimposta),
            'gettone' => $gettoneReimposta,
        ]);
        break;

    case 'verifica':
        // Si conferma con un GET, perché arriva da un collegamento in un
        // messaggio di posta: non c'è un modulo da inviare.
        [$okVerifica, $detto] = $sessione->verifica((string) ($_GET['g'] ?? ''));
        mostra('verifica', $comune + [
            'titolo'   => 'Conferma dell\'indirizzo',
            'riuscita' => $okVerifica,
            'detto'    => $detto,
        ]);
        break;

    case 'registrati':
        mostra('registrati', $comune + [
            'titolo' => 'Registrati',
            'modo'   => (new Inviti($db))->modo(),
        ]);
        break;

    case 'poltrone':
        if (!$sessione->autenticato()) {
            header('Location: ' . u('/entra'));
            exit;
        }
        mostra('poltrone', $comune + [
            'titolo'  => 'Le poltrone',
            'libere'  => $scrivania->poltroneLibere(),
        ]);
        break;

    case 'scrivania':
        if (!$sessione->autenticato()) {
            header('Location: ' . u('/entra'));
            exit;
        }
        if ($poltrona === null) {
            header('Location: ' . u('/poltrone'));
            exit;
        }
        $catalogo = require __DIR__ . '/calibrazione/verbi.php';
        mostra('scrivania', $comune + [
            'titolo'         => 'La Scrivania',
            'nazione'        => $lettura->nazione((string) $poltrona['codice'], $tick),
            'colleghi'       => $scrivania->colleghi((int) $poltrona['nazione_id']),
            'verbi'          => $scrivania->verbiPossibili((string) $poltrona['ruolo'], $catalogo),
            'daFirmare'      => $scrivania->daControfirmare($poltrona),
            'mieiOrdini'     => $scrivania->mieiOrdini($sessione->id()),
            'paesi'          => $lettura->nazioni($tick, 'nome'),
            'relazioni'      => $lettura->relazioni((string) $poltrona['codice'], 8),
            'agende'         => $agende->di($sessione->id()),
            'crisiAperte'    => $crisi->aperte((int) $poltrona['nazione_id']),
            'contestabili'   => $crisi->contestabili((int) $poltrona['nazione_id']),
            'gradini'        => (array) $calCrisi->leggi('crisi.gradini', []),
            'offerte'        => $reclutamento->offerteRicevute((int) $poltrona['id']),
            'nostriUomini'   => in_array($poltrona['ruolo'], Reclutamento::RUOLI_AMMESSI, true)
                                ? $reclutamento->nostriUomini((int) $poltrona['nazione_id']) : null,
            'rubrica'        => in_array($poltrona['ruolo'], Reclutamento::RUOLI_AMMESSI, true)
                                ? $canale->rubrica((int) $poltrona['id']) : null,
            // Il resoconto «mentre non c'eri» e' del titolare: se lo apre il
            // delegato, si segnava come letto e il titolare non lo vedeva mai.
            'mentreNonCEri'  => ($poltrona['per_delega'] ?? false)
                                ? [] : $delega->mentreNonCEri((int) $poltrona['id']),
            'poltroneAffidate' => $delega->affidateA($sessione->id()),
            'altriGiocatori' => $delega->altriGiocatori($sessione->id()),
            'silenzio'       => $delega->silenzio($poltrona, $tick),
            'avvisiAccesi'   => (bool) ($sessione->giocatore()['avvisi'] ?? true),
            'ogniQuanti'     => (int) Configurazione::leggi('posta.avviso_ogni_tick', 12),
            // Le armi commerciali si mostrano a chi le puo' impugnare: il
            // ministro dell'Economia e il Capo. Costruire il grafo costa un
            // decimo di secondo, e non si fa pagare a chi non gli serve.
            'armi'           => in_array($poltrona['ruolo'], ['economia', 'capo'], true)
                                ? $mercato->armiDisponibili((string) $poltrona['codice'], 8) : null,
            'nomiPaesi'      => in_array($poltrona['ruolo'], ['economia', 'capo'], true)
                                ? $mercato->nomi() : [],
        ]);
        break;

    case 'messaggi':
        if ($poltrona === null) {
            header('Location: ' . u($sessione->autenticato() ? '/poltrone' : '/entra'));
            exit;
        }
        // Le intercettazioni le vede soltanto chi ne ha il mestiere.
        $vedeIntercetti = in_array($poltrona['ruolo'], ['intelligence', 'interni', 'capo'], true);
        mostra('messaggi', $comune + [
            'titolo'       => 'Il Canale',
            'ricevuti'     => $canale->ricevuti((int) $poltrona['id'], $tick),
            'inviati'      => $canale->inviati((int) $poltrona['id'], $tick),
            'rubrica'      => $canale->rubrica((int) $poltrona['id']),
            'intercettati' => $vedeIntercetti ? $canale->intercettati((int) $poltrona['nazione_id']) : null,
            // Riscrivere le parole altrui è mestiere di una poltrona sola.
            'manipolazioni' => $poltrona['ruolo'] === 'intelligence'
                ? $falso->nostre((int) $poltrona['nazione_id']) : null,
            'paesi'        => $lettura->nazioni($tick, 'nome'),
            'linee'        => $linea->nostre((int) $poltrona['nazione_id']),
            'linee_aperte' => $linea->apertePer((int) $poltrona['nazione_id']),
            'linee_mondo'  => $linea->pubbliche(),
        ]);
        break;

    case 'arbitrio':
        if (!$sessione->arbitro()) {
            // Stesso corpo E stesso codice di una pagina che non esiste. Il
            // corpo da solo non basta: chi cerca rotte nascoste guarda il
            // codice di stato, e un 200 qui direbbe «c'è qualcosa».
            http_response_code(404);
            mostra('non-trovato', $comune + ['titolo' => 'Non trovato']);
            break;
        }
        mostra('arbitrio', $comune + [
            'titolo'        => 'Il banco dell\'arbitro',
            'conteggi'      => $arbitrio->conteggi(),
            'salute'        => $arbitrio->salute($tick),
            'arrivi'        => $arbitrio->arrivi(),
            'leve'          => $arbitrio->leve(),
            'atti'          => $arbitrio->atti(),
            'conversazioni' => $arbitrio->conversazioni(),
            'cal'           => $calCrisi,
            'inviti'        => $s->inviti->elenco(),
            'contoInviti'   => $s->inviti->conto(),
            'modoInviti'    => $s->inviti->modo(),
        ]);
        break;

    case 'mappa':
        $planisfero = new Planisfero(__DIR__ . '/db/seed/confini-svg.json');
        $letturaScelta = (string) ($_GET['l'] ?? 'legittimita');
        if (!isset(Planisfero::LETTURE[$letturaScelta])
            || ($letturaScelta === 'rapporti' && $poltrona === null)) {
            $letturaScelta = 'legittimita';
        }
        $tutte = $lettura->nazioni($tick, 'nome');
        $affinita = [];
        if ($letturaScelta === 'rapporti' && $poltrona !== null) {
            foreach ($lettura->relazioni((string) $poltrona['codice'], 200) as $r) {
                $affinita[(string) $r['codice']] = (float) $r['affinita'];
            }
        }
        $colori = $planisfero->colori($letturaScelta, $tutte, $affinita);
        $nomiMappa = [];
        foreach ($tutte as $n) {
            $nomiMappa[(string) $n['codice']] = (string) $n['nome'];
        }

        // Le due code: i cinque messi peggio e i cinque messi meglio. Una mappa
        // dice dove; una lista dice quanto, e servono tutte e due.
        $ordinate = $tutte;
        usort($ordinate, static function ($a, $b) use ($colori) {
            return strcmp($colori[$a['codice']]['colore'] ?? '', $colori[$b['codice']]['colore'] ?? '');
        });
        $notevoli = [];
        foreach (array_merge(array_slice($ordinate, 0, 5), array_slice($ordinate, -5)) as $n) {
            $notevoli[] = [
                'codice' => $n['codice'],
                'nome'   => $n['nome'],
                'colore' => $colori[$n['codice']]['colore'] ?? '#555',
                'valore' => $colori[$n['codice']]['valore'] ?? '—',
            ];
        }

        mostra('mappa', $comune + [
            'titolo'        => 'Il planisfero',
            'planisfero'    => $planisfero,
            'colori'        => $colori,
            'nomiMappa'     => $nomiMappa,
            'letturaScelta' => $letturaScelta,
            'cliccabile'    => true,
            'guerre'        => $lettura->guerre(),
            'notevoli'      => $notevoli,
        ]);
        break;

    case 'commercio':
        if ($poltrona === null) {
            header('Location: ' . u($sessione->autenticato() ? '/poltrone' : '/entra'));
            exit;
        }
        $iso = (string) $poltrona['codice'];
        mostra('commercio', $comune + [
            'titolo'      => 'Il commercio',
            'nomi'        => $mercato->nomi(),
            'fornitori'   => $mercato->fornitori($iso),
            'clienti'     => $mercato->clienti($iso),
            'strozzature' => $mercato->strozzature($tick),
        ]);
        break;

    case 'bilancio':
        $chiuse = $epoca->chiuse();
        mostra('bilancio', $comune + [
            'titolo'         => 'Il bilancio',
            'epocaCorrente'  => $epoca->corrente(),
            'restano'        => $epoca->restano($tick) ?? 0,
            'epocheChiuse'   => $chiuse,
            'classifica'     => $chiuse === [] ? [] : $epoca->classifica((int) $chiuse[0]['id']),
            'rivelazioni'    => $chiuse === [] ? [] : $epoca->rivelazioni((int) $chiuse[0]['id']),
        ]);
        break;

    case 'cronaca':
        mostra('cronaca', $comune + [
            'titolo'  => 'La cronaca',
            'cronaca' => $lettura->cronaca(120),
        ]);
        break;

    default:
        http_response_code(404);
        mostra('non-trovato', ['titolo' => 'Non c\'è nulla qui']);
}

/**
 * I moduli inviati. Restituisce [avviso, dove reindirizzare].
 *
 * @param array<string,mixed> $post
 * @param array<string,mixed>|null $poltrona
 * @return array{0:array{0:bool,1:string}|null,1:string|null}
 */
function azione(array $post, Servizi $s, ?array $poltrona, int $tick): array
{
    // Gli stessi nomi brevi anche qui dentro, così il corpo della funzione
    // resta leggibile e i casi non cambiano una riga.
    $sessione     = $s->sessione;
    $scrivania    = $s->scrivania;
    $canale       = $s->canale;
    $reclutamento = $s->reclutamento;
    $crisi        = $s->crisi;
    $falso        = $s->falso;
    $linea        = $s->linea;
    $delega       = $s->delega;
    $arbitrio     = $s->arbitrio;
    $lettura      = $s->lettura;

    switch ((string) ($post['azione'] ?? '')) {
        case 'registrati':
            return [$sessione->registra(
                (string) ($post['nome'] ?? ''),
                (string) ($post['email'] ?? ''),
                (string) ($post['password'] ?? ''),
                (string) ($post['invito'] ?? '')), null];

        case 'entra':
            $esitoAccesso = $sessione->entra((string) ($post['email'] ?? ''),
                (string) ($post['password'] ?? ''));
            if ($esitoAccesso[0]) {
                return [$esitoAccesso, '/scrivania'];
            }
            return [$esitoAccesso, null];

        case 'dimenticata':
            return [$sessione->dimenticata((string) ($post['chi'] ?? '')), null];

        case 'reimposta':
            $esitoReimposta = $sessione->reimposta((string) ($post['g'] ?? ''),
                (string) ($post['nuova'] ?? ''), (string) ($post['conferma'] ?? ''));
            return [$esitoReimposta, $esitoReimposta[0] ? '/entra' : null];

        case 'rimanda_verifica':
            return [$sessione->rimandaVerifica((string) ($post['email'] ?? '')), null];

        case 'esci':
            $sessione->esci();
            return [[true, 'Sei uscito.'], '/'];

        case 'occupa':
            if (!$sessione->autenticato()) {
                return [[false, 'Devi entrare.'], '/entra'];
            }
            return [$scrivania->occupa($sessione->id(), (int) ($post['poltrona'] ?? 0), $tick), '/scrivania'];

        case 'lascia':
            if ($sessione->autenticato()) {
                $scrivania->lascia($sessione->id());
            }
            return [[true, 'Hai lasciato la poltrona.'], '/poltrone'];

        case 'ordina':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            $catalogo = require __DIR__ . '/calibrazione/verbi.php';
            $verbo = (string) ($post['verbo'] ?? '');
            $possibili = $scrivania->verbiPossibili((string) $poltrona['ruolo'], $catalogo);
            if (!isset($possibili[$verbo])) {
                return [[false, 'Quel verbo non è nelle tue competenze.'], '/scrivania'];
            }
            $bersaglio = $lettura->nazione(strtoupper((string) ($post['bersaglio'] ?? '')), $tick);
            if ($bersaglio === null) {
                return [[false, 'Bersaglio sconosciuto.'], '/scrivania'];
            }
            return [$scrivania->ordina($poltrona, $possibili[$verbo], $verbo,
                (int) $bersaglio['nazione_id'], (int) ($post['intensita'] ?? 50),
                (int) ($post['copertura'] ?? 0), $tick), '/scrivania'];

        case 'firma':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            $esito = $scrivania->firma((int) ($post['ordine'] ?? 0), $poltrona);
            return [[$esito, $esito ? 'Firmato: partirà al prossimo giro d\'orologio.'
                                    : 'Non risulta nulla da firmare.'], '/scrivania'];

        case 'scrivi':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$canale->invia($poltrona, (int) ($post['destinatario'] ?? 0),
                (string) ($post['testo'] ?? ''), (int) ($post['sicurezza'] ?? 2), $tick), '/messaggi'];

        case 'recluta':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$reclutamento->offri($poltrona, (int) ($post['destinatario'] ?? 0),
                (string) ($post['testo'] ?? ''),
                !empty($post['denaro']), !empty($post['dossier']), !empty($post['appoggio']),
                $tick), '/scrivania'];

        case 'rispondi_offerta':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$reclutamento->rispondi((int) ($post['offerta'] ?? 0), $poltrona,
                (string) ($post['risposta'] ?? ''), $tick), '/scrivania'];

        case 'apri_crisi':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$crisi->apri($poltrona, (int) ($post['evento'] ?? 0), $tick), '/scrivania'];

        case 'crisi':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            // La parte non si chiede al modulo: si ricava dalla nazione della
            // poltrona. Prima arrivava come campo nascosto, e chiunque poteva
            // muovere una crisi fra due altri paesi fino alla guerra.
            if (!\App\Gioco\Crisi::siedeAlTavolo((string) $poltrona['ruolo'])) {
                return [[false, 'Al tavolo di una crisi siedono il Capo e gli Esteri.'], '/scrivania'];
            }
            $idCrisi = (int) ($post['crisi'] ?? 0);
            $parte = $crisi->parteDi($idCrisi, (int) $poltrona['nazione_id']);
            if ($parte === null) {
                return [[false, 'Non è una crisi che vi riguardi.'], '/scrivania'];
            }
            return [$crisi->rispondi($idCrisi, $parte, (string) ($post['mossa'] ?? ''), $tick), '/scrivania'];

        case 'avvisi':
            return [$sessione->avvisi((string) ($post['acceso'] ?? '1') === '1'), '/scrivania'];

        case 'affida':
            if ($poltrona === null || ($poltrona['per_delega'] ?? false)) {
                return [[false, 'Solo il titolare può affidare la propria poltrona.'], '/scrivania'];
            }
            return [$delega->affida($poltrona, (int) ($post['giocatore'] ?? 0), $tick), '/scrivania'];

        case 'revoca_delega':
            if ($poltrona === null || ($poltrona['per_delega'] ?? false)) {
                return [[false, 'Solo il titolare può revocare la delega.'], '/scrivania'];
            }
            return [$delega->revoca($poltrona, $tick), '/scrivania'];

        case 'siedi':
            $quale = (int) ($post['poltrona'] ?? 0);
            if ($quale === 0) {
                unset($_SESSION['poltrona_scelta']);
                return [[true, 'Torni alla tua poltrona.'], '/scrivania'];
            }
            if ($scrivania->poltronaDi($sessione->id(), $quale) === null) {
                return [[false, 'Quella poltrona non è tua né ti è stata affidata.'], '/scrivania'];
            }
            $_SESSION['poltrona_scelta'] = $quale;
            return [[true, 'Ti siedi lì. Quel che firmerai resterà agli atti come tuo.'], '/scrivania'];

        case 'arbitrio_account':
            if (!$sessione->arbitro()) {
                return [[false, 'Non siedi al banco.'], '/'];
            }
            return [$arbitrio->suGiocatore($sessione->id(), (int) ($post['giocatore'] ?? 0),
                (string) ($post['atto'] ?? ''), (string) ($post['argomento'] ?? ''), $tick), '/arbitrio'];

        case 'invito_crea':
            if (!$sessione->arbitro()) {
                return [[false, 'Non siedi al banco.'], '/'];
            }
            return [$s->inviti->crea($sessione->id(), (string) ($post['nota'] ?? ''),
                (int) ($post['quanti'] ?? 1)), '/arbitrio'];

        case 'invito_revoca':
            if (!$sessione->arbitro()) {
                return [[false, 'Non siedi al banco.'], '/'];
            }
            return [$s->inviti->revoca((string) ($post['codice'] ?? '')), '/arbitrio'];

        case 'arbitrio_leva':
            if (!$sessione->arbitro()) {
                return [[false, 'Non siedi al banco.'], '/'];
            }
            return [$arbitrio->muoviLeva($sessione->id(), (string) ($post['chiave'] ?? ''),
                (string) ($post['valore'] ?? ''), $s->calibrazione, $tick), '/arbitrio'];

        case 'proponi_linea':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$linea->proponi($poltrona, (int) ($post['controparte'] ?? 0), $tick), '/messaggi'];

        case 'rispondi_linea':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$linea->rispondi($poltrona, (int) ($post['linea'] ?? 0),
                ($post['risposta'] ?? '') === 'si', $tick), '/messaggi'];

        case 'chiudi_linea':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$linea->chiudi($poltrona, (int) ($post['linea'] ?? 0), $tick), '/messaggi'];

        case 'manometti':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            $verso = (int) ($post['verso'] ?? 0);
            return [$falso->ordina($poltrona, (int) ($post['bersaglio'] ?? 0),
                $verso > 0 ? $verso : null, (string) ($post['modo'] ?? ''),
                (string) ($post['testo'] ?? ''), $tick), '/messaggi'];

        case 'revoca_manomissione':
            if ($poltrona === null) {
                return [[false, 'Non occupi alcuna poltrona.'], '/poltrone'];
            }
            return [$falso->revoca((int) ($post['operazione'] ?? 0), $poltrona), '/messaggi'];

        case 'annulla':
            $esito = $scrivania->annulla((int) ($post['ordine'] ?? 0), $sessione->id());
            return [[$esito, $esito ? 'Ordine ritirato.' : 'Troppo tardi.'], '/scrivania'];
    }
    return [[false, 'Azione sconosciuta.'], null];
}

/** @param array<string,mixed> $dati */
function mostra(string $vista, array $dati): void
{
    $radice = dirname(__FILE__);
    extract($dati, EXTR_SKIP);
    $contenuto = $radice . '/views/' . $vista . '.php';
    require $radice . '/views/parti/intestazione.php';
    if (is_file($contenuto)) {
        require $contenuto;
    }
    require $radice . '/views/parti/chiusura.php';
}
