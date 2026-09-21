<?php

declare(strict_types=1);

/**
 * Fa girare il mondo A VUOTO, senza giocatori e senza base dati.
 *
 * È la F0 del progetto e, allo stesso tempo, il simulatore autonomo: stesso
 * motore, profilo di calibrazione diverso. Serve a rispondere a una sola
 * domanda: questo mondo produce una storia plausibile?
 *
 *   php bin/simula.php --anni=15 --profilo=osservazione
 *   php bin/simula.php --anni=35 --seme=7 --cronaca
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Nucleo\Configurazione;
use App\Simulazione\EsecutoreTick;

$opz     = getopt('', ['anni::', 'profilo::', 'seme::', 'cronaca', 'silenzioso']);
$anni    = (int) ($opz['anni'] ?? 15);
$profilo = (string) ($opz['profilo'] ?? Configurazione::leggi('mondo.profilo', 'gioco'));
$seme    = (int) ($opz['seme'] ?? Configurazione::leggi('mondo.seme', 1));
$cronaca = array_key_exists('cronaca', $opz);

$cal      = Calibrazione::carica($radice, $profilo);
$tickAnno = (int) $cal->numero('tempo.tick_per_anno', 52.0);
$mondo    = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$totale   = $anni * $tickAnno;

printf("Stanza dei Bottoni — mondo a vuoto\n");
printf("  profilo %s@%s · seme %d · %d nazioni · %d anni (%d tick)\n\n",
    $cal->profilo, $cal->versione, $seme, count($mondo->nazioni), $anni, $totale);

$pilIniziale = $mondo->pilTotale();
$affinitaIniziali = [];
foreach ($mondo->relazioni->tutte() as $chiave => $r) {
    $affinitaIniziali[$chiave] = $r->affinita;
}
$esecutore   = new EsecutoreTick($cal, null, true, $mondo);
$cronologia  = [];
$annali      = [];
$avvio       = hrtime(true);

printf("%-6s %14s %7s %8s %7s %7s %9s\n",
    'anno', 'PIL mondiale', 'cresc.', 'conflitti', 'colpi', 'rivol.', 'legitt.');

$colpiTotali = 0;
$rivoluzioniTotali = 0;

for ($t = 1; $t <= $totale; $t++) {
    $mondo->tick = $t;
    $resoconto = $esecutore->esegui($t, $seme);

    // Il giornale narrativo del tick: in una partita vera lo leggerebbe la
    // fase 09 e deciderebbe cosa diventa pubblico. A vuoto, è la cronaca.
    foreach ($esecutore->giornale() as $voce) {
        $annali[] = ['anno' => (int) ceil($t / $tickAnno)] + $voce;
    }

    if ($t % $tickAnno === 0) {
        $anno = (int) ($t / $tickAnno);
        $pil  = $mondo->pilTotale();
        $conflitti = 0; $legittimitaMedia = 0.0;
        foreach ($mondo->nazioni as $n) {
            if ($n->netPeace >= 4) { $conflitti++; }
            $legittimitaMedia += $n->legittimita;
        }
        $legittimitaMedia /= max(1, count($mondo->nazioni));

        static $pilPrec = null;
        $crescita = $pilPrec === null ? 0.0 : ($pil / $pilPrec - 1.0);
        $pilPrec = $pil;

        $colpiAnno = 0; $rivAnno = 0;
        foreach ($mondo->nazioni as $n) {
            // i contatori sono cumulativi: la differenza la teniamo qui sotto
        }
        $cum = array_sum(array_map(static fn($n) => $n->cambiEsecutivo, $mondo->nazioni));
        $cumRiv = array_sum(array_map(static fn($n) => $n->vittorieInsorti, $mondo->nazioni));
        $colpiAnno = $cum - $colpiTotali;
        $rivAnno   = $cumRiv - $rivoluzioniTotali;
        $colpiTotali = $cum;
        $rivoluzioniTotali = $cumRiv;

        printf("%-6d %14s %6.1f%% %8d %7d %7d %9.1f\n",
            $anno, number_format($pil / 1_000_000, 1) . ' T', $crescita * 100,
            $conflitti, $colpiAnno, $rivAnno, $legittimitaMedia);
    }
}

$durata = (hrtime(true) - $avvio) / 1_000_000_000;

// ------------------------------------------------------------- validazione
echo "\n";
printf("%d anni simulati in %.1f s (%.0f tick/s)\n\n", $anni, $durata, $totale / max(0.001, $durata));

$nazioni = $mondo->elenco();
$conCambio  = count(array_filter($nazioni, static fn($n) => $n->cambiEsecutivo > 0));
$conRivolta = count(array_filter($nazioni, static fn($n) => $n->vittorieInsorti > 0));
$inGuerra   = count(array_filter($nazioni, static fn($n) => $n->netPeace >= 5));

$irregolari = array_sum(array_map(static fn($n) => $n->cambiIrregolari, $nazioni));
printf("Cambi di esecutivo: %d in %d anni (%.1f l'anno, %d nazioni toccate)\n",
    $colpiTotali, $anni, $colpiTotali / $anni, $conCambio);
printf("  di cui IRREGOLARI: %d (%.1f l'anno)\n", $irregolari, $irregolari / $anni);
printf("Vittorie insurrezionali: %d (%d nazioni)\n", $rivoluzioniTotali, $conRivolta);
printf("In guerra civile a fine corsa: %d su %d\n", $inGuerra, count($nazioni));
printf("PIL mondiale: %.1f T -> %.1f T (%.1f%% annuo composto)\n",
    $pilIniziale / 1_000_000, $mondo->pilTotale() / 1_000_000,
    ((($mondo->pilTotale() / $pilIniziale) ** (1 / max(1, $anni))) - 1) * 100);

// Riferimenti storici (Crawford, dal World Handbook of Political and Social
// Indicators): in trent'anni e su circa 150 paesi, 238 cambi irregolari
// riusciti e 1645 regolari. Riscalati sul nostro numero di nazioni.
$scala        = count($nazioni) / 150.0;
$attesiIrreg  = 238.0 / 30.0 * $scala;
$attesiTotali = (238.0 + 1645.0) / 30.0 * $scala;

echo "\n";
$rapIrr = ($irregolari / $anni) / $attesiIrreg;
printf("Cambi irregolari: %.1f l'anno contro ~%.1f storici → %.1f volte.%s\n",
    $irregolari / $anni, $attesiIrreg, $rapIrr,
    $rapIrr > 2.0 || $rapIrr < 0.5 ? '  << FUORI SCALA' : '  Ordine di grandezza accettabile.');
printf("Cambi totali:     %.1f l'anno contro ~%.1f storici → %.1f volte.\n",
    $colpiTotali / $anni, $attesiTotali, ($colpiTotali / $anni) / $attesiTotali);
echo "  (i cambi regolari restano sotto il riferimento: le elezioni a calendario\n";
echo "   non sono ancora modellate — arrivano con la fase 10.)\n";

// ----------------------------------------------------------------- eventi
$perVerbo = [];
$perDominio = [];
$perMandante = [];
foreach ($mondo->eventi as $e) {
    if ($e->stato !== 'realizzato') { continue; }
    $perVerbo[$e->verbo] = ($perVerbo[$e->verbo] ?? 0) + 1;
}
foreach ($annali as $v) {
    $perDominio[$v['genere']] = ($perDominio[$v['genere']] ?? 0) + 1;
}
$notevoli = array_values(array_filter($annali, static fn($v) => in_array(
    $v['genere'], ['trama', 'armi_ai_ribelli', 'attacco', 'embargo', 'operazione_sventata', 'mediazione'], true)));
if ($notevoli !== []) {
    echo "\n--- il mondo ha agito ---\n";
    $generi = [];
    foreach ($notevoli as $v) { $generi[$v['genere']] = ($generi[$v['genere']] ?? 0) + 1; }
    arsort($generi);
    foreach ($generi as $g => $quanti) { printf("  %-22s %d\n", str_replace('_', ' ', $g), $quanti); }
    echo "\n  qualche episodio:\n";
    foreach (array_slice($notevoli, 0, 6) as $v) {
        $d = $v['dati'];
        printf("    anno %2d  %-20s %s\n", $v['anno'], str_replace('_', ' ', $v['genere']),
            implode(' → ', array_map('strval', $d)));
    }
}

// --------------------------------------------------------------- palazzo
if ($mondo->gabinetti !== []) {
    $peggiore = null;
    foreach ($mondo->gabinetti as $g) {
        if ($peggiore === null || $g->pressioneInterna() > $peggiore->pressioneInterna()) {
            $peggiore = $g;
        }
    }
    $n = $mondo->nazioni[$peggiore->iso3];
    printf("\n--- il palazzo più agitato: %s ---\n", $n->nome);
    printf("  coesione %.0f · pressione interna %.1f · legittimità del capo %.0f\n\n",
        $peggiore->coesione, $peggiore->pressioneInterna(), $n->legittimita);
    foreach ($peggiore->poltrone as $ruolo => $p) {
        printf("  %-20s %-26s potere %3.0f  lealtà %3.0f\n",
            App\Dati\Gabinetto::RUOLI[$ruolo], $p->titolare->nome, $p->potere, $p->lealta);
    }
    foreach ($peggiore->fazioni as $f) {
        printf("    %-26s forza %3.0f  favore %+4.0f  «%s»\n", $f->nome, $f->forza, $f->favore, $f->agenda);
    }
    $dim = array_values(array_filter($annali, static fn($v) => $v['genere'] === 'dimissioni'));
    if ($dim !== []) {
        printf("\n  dimissioni eccellenti nel mondo: %d\n", count($dim));
        foreach (array_slice($dim, -3) as $v) {
            printf("    anno %2d  %s (%s, %s)\n", $v['anno'], $v['dati']['chi'],
                $v['dati']['ruolo'], $v['dati']['nazione']);
        }
    }
}

// ------------------------------------------------------------- il feed
$perGenere = [];
foreach ($mondo->notizie as $n) { $perGenere[$n['genere']] = ($perGenere[$n['genere']] ?? 0) + 1; }
if ($perGenere !== []) {
    arsort($perGenere);
    echo "\n--- il feed pubblico ---\n";
    foreach ($perGenere as $g => $quante) { printf("  %-22s %d\n", str_replace('_', ' ', $g), $quante); }
}
$guerre = array_values(array_filter($annali, static fn($v) => in_array($v['genere'],
    ['guerra', 'pace', 'garanzia_onorata', 'garanzia_tradita'], true)));
if ($guerre !== []) {
    echo "\n--- guerre fra Stati ---\n";
    foreach (array_slice($guerre, 0, 10) as $v) {
        printf("  anno %2d  %-18s %s\n", $v['anno'], str_replace('_', ' ', $v['genere']),
            implode(' · ', array_map('strval', $v['dati'])));
    }
}
$elezioni = array_values(array_filter($annali, static fn($v) => $v['genere'] === 'elezione'));
if ($elezioni !== []) {
    $esiti = [];
    foreach ($elezioni as $v) { $esiti[$v['dati']['esito']] = ($esiti[$v['dati']['esito']] ?? 0) + 1; }
    echo "\n--- ricambi per via ordinaria ---\n";
    foreach ($esiti as $e => $q) { printf("  %-22s %d\n", $e, $q); }
}

// ---------------------------------------------------------- intelligence
$intel = $mondo->intelligence;
$perDominioConoscenza = [];
foreach ($mondo->eventi as $e) {
    if ($e->stato === 'in_volo' || $e->stato === 'scoperto') { continue; }
    $liv = $intel->livello($e->bersaglio, $e->id);
    $coperto = $e->impronta < 0.60 ? 'coperte' : 'dichiarate';
    $r = &$perDominioConoscenza[$coperto];
    $r['totale'] = ($r['totale'] ?? 0) + 1;
    if ($liv >= 1) { $r['accorti'] = ($r['accorti'] ?? 0) + 1; }
    if ($liv >= 3) { $r['bersaglio'] = ($r['bersaglio'] ?? 0) + 1; }
    if ($liv >= 4) { $r['dimostrate'] = ($r['dimostrate'] ?? 0) + 1; }
    unset($r);
}
if ($perDominioConoscenza !== []) {
    echo "\n--- quel che si è saputo (ultimo anno di eventi) ---\n";
    printf("%-12s %8s %10s %12s %12s\n", '', 'totale', 'notate', 'bersaglio', 'DIMOSTRATE');
    foreach ($perDominioConoscenza as $tipo => $r) {
        $t = max(1, $r['totale'] ?? 1);
        printf("%-12s %8d %9.0f%% %11.0f%% %11.0f%%\n", $tipo, $t,
            100 * ($r['accorti'] ?? 0) / $t, 100 * ($r['bersaglio'] ?? 0) / $t, 100 * ($r['dimostrate'] ?? 0) / $t);
    }

    $perServizio = [];
    foreach ($intel->attribuito as $lista) {
        foreach ($lista as $iso) { $perServizio[$iso] = ($perServizio[$iso] ?? 0) + 1; }
    }
    arsort($perServizio);
    if ($perServizio !== []) {
        echo "\n  servizi che hanno dimostrato di più:\n";
        $n = 0;
        foreach ($perServizio as $iso => $quante) {
            if ($n++ >= 6) { break; }
            printf("    %-24s %d attribuzioni\n", $mondo->nazioni[$iso]->nome ?? $iso, $quante);
        }
    }
    $falseBandiere = 0;
    foreach ($mondo->eventi as $e) { if ($e->falsaBandiera !== null) { $falseBandiere++; } }
    $inganni = array_values(array_filter($annali, static fn($v) => in_array($v['genere'],
        ['accusa_sbagliata', 'falso_smascherato'], true)));
    if ($falseBandiere > 0 || $inganni !== []) {
        printf("\n  operazioni sotto falsa bandiera nella coda recente: %d\n", $falseBandiere);
        foreach (array_slice($inganni, -4) as $v) {
            if ($v['genere'] === 'accusa_sbagliata') {
                printf("    anno %2d  %s incolpa %s — è stata %s\n", $v['anno'],
                    $v['dati']['bersaglio'], $v['dati']['incolpa'], $v['dati']['vero']);
            } else {
                printf("    anno %2d  %s smaschera il falso: era %s, non %s\n", $v['anno'],
                    $v['dati']['chi'], $v['dati']['mandante'], $v['dati']['incolpato']);
            }
        }
    }

    $attribuzioni = array_values(array_filter($annali, static fn($v) => $v['genere'] === 'attribuzione'));
    if ($attribuzioni !== []) {
        echo "\n  smascheramenti:\n";
        foreach (array_slice($attribuzioni, -4) as $v) {
            printf("    anno %2d  %s dimostra: %s, %s contro %s\n", $v['anno'],
                $v['dati']['chi'], $v['dati']['verbo'], $v['dati']['mandante'], $v['dati']['contro']);
        }
        printf("  (%d attribuzioni riuscite su operazioni coperte in %d anni)\n", count($attribuzioni), $anni);
    }
}

// --------------------------------------------------------------- relazioni
echo "\n--- il sistema ---\n";
$perInfluenza = $mondo->elenco();
usort($perInfluenza, static fn($a, $b) => $b->influenzaTotale <=> $a->influenzaTotale);

printf("%-24s %7s %9s %8s %s\n", 'potenza', 'infl.%', 'integrità', 'blocco', 'rango');
foreach (array_slice($perInfluenza, 0, 8) as $n) {
    $blocco = 0;
    foreach ($mondo->nazioni as $altro) {
        $r = $mondo->relazioni->fra($altro->iso3, $n->iso3);
        if ($r !== null && $r->affinita >= 55.0) { $blocco++; }
    }
    $rango = $n->influenzaTotale >= 5.0 ? 'grande potenza'
        : ($n->influenzaTotale >= 3.0 ? 'potenza maggiore' : '');
    printf("%-24s %6.2f%% %9.0f %8d %s\n", $n->nome, $n->influenzaTotale, $n->integrita, $blocco, $rango);
}

$perdite = array_filter($annali, static fn($v) => $v['genere'] === 'integrita_perduta');
if ($perdite !== []) {
    echo "\n--- garanzie tradite ---\n";
    foreach (array_slice($perdite, 0, 8) as $v) {
        printf("  anno %2d  %s non ha retto %s (obbligo %d) — integrità residua %d\n",
            $v['anno'], $v['dati']['garante'], $v['dati']['cliente'],
            $v['dati']['obbligo'], $v['dati']['residuo']);
    }
    printf("  (%d in totale)\n", count($perdite));
}

$scostamenti = [];
foreach ($mondo->relazioni->tutte() as $chiave => $r) {
    $delta = $r->affinita - ($affinitaIniziali[$chiave] ?? $r->affinita);
    if (abs($delta) >= 25.0) { $scostamenti[$chiave] = $delta; }
}
if ($scostamenti !== []) {
    uasort($scostamenti, static fn($a, $b) => abs($b) <=> abs($a));
    echo "\n--- rapporti che sono cambiati ---\n";
    $mostrati = 0;
    foreach ($scostamenti as $chiave => $delta) {
        if ($mostrati++ >= 8) { break; }
        [$a, $b] = explode('|', $chiave);
        printf("  %-22s -> %-22s %+6.0f  (ora %.0f)\n",
            $mondo->nazioni[$a]->nome, $mondo->nazioni[$b]->nome, $delta,
            $mondo->relazioni->fra($a, $b)->affinita);
    }
    printf("  (%d rapporti spostati di oltre 25 punti)\n", count($scostamenti));
}

if ($cronaca) {
    echo "\n--- paesi più turbolenti ---\n";
    usort($nazioni, static fn($a, $b) => ($b->cambiEsecutivo + $b->vittorieInsorti * 2)
                                      <=> ($a->cambiEsecutivo + $a->vittorieInsorti * 2));
    foreach (array_slice($nazioni, 0, 12) as $n) {
        printf("  %-28s %d cambi, %d rivoluzioni, maturità %d, legittimità %.0f\n",
            $n->nome, $n->cambiEsecutivo, $n->vittorieInsorti, $n->maturita, $n->legittimita);
    }
}
