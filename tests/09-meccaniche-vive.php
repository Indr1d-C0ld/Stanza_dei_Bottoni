<?php

declare(strict_types=1);

/**
 * Le meccaniche che l'audit aveva trovato ferme.
 *
 * Ognuna di queste prove corrisponde a una cosa che il modello prometteva e
 * non faceva: un campo dichiarato, salvato a ogni tick, letto dal motore, e che
 * nessuna fase muoveva mai. Nessuno di quei difetti si vedeva giocando — e
 * nessuna prova li avrebbe intercettati, perche' non c'erano prove.
 *
 * La prova piu' importante e' l'ultima: cerca da sola i campi interi che una
 * fase muove per frazioni. E' lo stampo dello stesso errore, ripetuto quattro
 * volte in questo progetto, e adesso si difende da solo.
 */

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Simulazione\EsecutoreTick;

$radice = dirname(__DIR__);

/** Un mondo fatto correre per quindici anni, una volta sola per tutte le prove. */
$cal   = Calibrazione::carica($radice, 'osservazione');
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$prima = [];
foreach ($mondo->nazioni as $n) {
    $prima[$n->iso3] = [
        'polizia' => $n->statoPolizia,
        'cyber'   => $n->cyberDifesa,
        'info'    => $n->controlloInfo,
        'nuc'     => $n->posturaNucleare,
        'ansia'   => $n->ansiaMilitare,
    ];
}
$esecutore = new EsecutoreTick($cal, null, true, $mondo);
$verbi = $giornale = [];
$visti = [];
for ($t = 1; $t <= 780; $t++) {
    $esecutore->esegui($t, 4242);
    foreach ($mondo->eventi as $ev) {
        if (!isset($visti[$ev->id])) {
            $visti[$ev->id] = true;
            $verbi[$ev->verbo] = ($verbi[$ev->verbo] ?? 0) + 1;
        }
    }
    foreach ($esecutore->giornale() as $g) {
        $giornale[$g['genere']] = ($giornale[$g['genere']] ?? 0) + 1;
    }
}

/** Quanti paesi hanno mosso un certo campo. */
$mossi = static function (string $chiave, float $soglia) use ($mondo, $prima): int {
    $n = 0;
    foreach ($mondo->nazioni as $x) {
        $ora = match ($chiave) {
            'polizia' => $x->statoPolizia,
            'cyber'   => $x->cyberDifesa,
            'info'    => $x->controlloInfo,
            'nuc'     => (float) $x->posturaNucleare,
            default   => $x->ansiaMilitare,
        };
        if (abs($ora - $prima[$x->iso3][$chiave]) > $soglia) {
            $n++;
        }
    }
    return $n;
};

Prove::gruppo('Lo stato di polizia non e\' piu\' una costante');

Prove::che('la maggior parte dei paesi lo ha mosso',
    $mossi('polizia', 0.2) > 100, sprintf('%d su 189', $mossi('polizia', 0.2)));
$p = array_map(static fn($x) => $x->statoPolizia, array_values($mondo->nazioni));
Prove::fra('resta nella sua scala', 1.0, 5.0, min($p));
Prove::fra('anche in cima', 1.0, 5.0, max($p));
Prove::che('esiste uno scarto fra il piu\' libero e il piu\' stretto',
    max($p) - min($p) > 1.5, sprintf('%.1f … %.1f', min($p), max($p)));

Prove::gruppo('La difesa cibernetica si muove');

Prove::che('quasi tutti i paesi la hanno mossa',
    $mossi('cyber', 1.0) > 120, sprintf('%d su 189', $mossi('cyber', 1.0)));
$d = array_map(static fn($x) => $x->cyberDifesa, array_values($mondo->nazioni));
Prove::fra('resta fra zero e cento', 0.0, 100.0, min($d));
Prove::fra('anche in cima', 0.0, 100.0, max($d));

Prove::gruppo('Il controllo dell\'informazione puo\' anche salire');

Prove::che('si e\' mosso per molti paesi',
    $mossi('info', 1.0) > 100, sprintf('%d su 189', $mossi('info', 1.0)));
$i = array_map(static fn($x) => $x->controlloInfo, array_values($mondo->nazioni));
Prove::che('qualcuno e\' salito sopra il valore di partenza',
    max($i) > 50.0, sprintf('massimo %.0f', max($i)));
Prove::che('e qualcuno e\' sceso', min($i) < 50.0, sprintf('minimo %.0f', min($i)));

Prove::gruppo('La bomba si prende e si posa');

$armatiPrima = count(array_filter($prima, static fn($x) => $x['nuc'] >= 3));
$armatiOra = 0;
foreach ($mondo->nazioni as $x) {
    if ($x->posturaNucleare >= 3) {
        $armatiOra++;
    }
}
Prove::che('qualcuno si e\' armato in quindici anni',
    $armatiOra > $armatiPrima, sprintf('%d → %d', $armatiPrima, $armatiOra));
Prove::fra('ma non e\' una corsa generale', 0.0, 6.0, (float) ($armatiOra - $armatiPrima));
Prove::che('esiste un verbo coperto nel dominio nucleare',
    ($verbi['programma_nucleare'] ?? 0) > 0,
    'senza, imint resta la sola disciplina che nessuno usa');

Prove::gruppo('L\'ansia militare passa, ma non sparisce');

$a = array_map(static fn($x) => $x->ansiaMilitare, array_values($mondo->nazioni));
Prove::che('non e\' zero per tutti', max($a) > 5.0, sprintf('massimo %.1f', max($a)));
Prove::che('e non e\' bloccata in alto', min($a) < 60.0, sprintf('minimo %.1f', min($a)));
Prove::che('chi e\' in conflitto ne ha piu\' degli altri', (static function () use ($mondo): bool {
    $guerra = $pace = [];
    foreach ($mondo->nazioni as $x) {
        if ($x->netPeace >= 4) {
            $guerra[] = $x->ansiaMilitare;
        } else {
            $pace[] = $x->ansiaMilitare;
        }
    }
    if ($guerra === []) {
        return true;   // nessuna guerra in corso: niente da confrontare
    }
    return array_sum($guerra) / count($guerra) > array_sum($pace) / count($pace);
})());

Prove::gruppo('La tavola degli obblighi si usa tutta');

$obblighi = [];
foreach ($mondo->relazioni->tutte() as $r) {
    $obblighi[$r->obbligo] = ($obblighi[$r->obbligo] ?? 0) + 1;
}
Prove::che('il gradino piu\' alto esiste nel mondo',
    ($obblighi[128] ?? 0) > 0, 'nessuna garanzia di difesa nucleare');
Prove::che('ma e\' raro', ($obblighi[128] ?? 0) < 20, sprintf('%d relazioni', $obblighi[128] ?? 0));
$usati = count(array_filter(array_keys($obblighi), static fn($k) => $k > 0));
Prove::che('e tutti i gradini sono usati', $usati >= 4, sprintf('%d gradini su 5', $usati));

Prove::gruppo('La dottrina usa tutto il catalogo dei verbi');

$catalogo = array_keys(require $radice . '/calibrazione/verbi.php');
$mai = array_values(array_diff($catalogo, array_keys($verbi)));
// colpo_di_stato e' l'atto piu' estremo del catalogo e puo' non uscire in una
// singola corsa: si ammette quello, non di piu'.
$ammessi = array_diff($mai, ['colpo_di_stato']);
Prove::uguale('nessun verbo resta inarrivabile', [], array_values($ammessi));
Prove::che('la mediazione esce', ($verbi['mediazione'] ?? 0) > 0);
Prove::che('il colpo mirato esce', ($verbi['strike'] ?? 0) > 0);

Prove::gruppo('I ministri si dimettono');

Prove::che('qualcuno se ne va sbattendo la porta',
    ($giornale['dimissioni'] ?? 0) > 0,
    'la soglia era sotto l\'intervallo che il potere occupa davvero');

Prove::gruppo('Nessun campo intero viene mosso per frazioni');

// E' lo stampo di un errore ripetuto quattro volte in questo progetto:
// statoPolizia, cyberDifesa, ansiaMilitare, controlloInfo. Una grandezza che
// il motore muove di frazioni non puo' essere un intero, o l'arrotondamento
// cancella il movimento a ogni tick e il campo resta fermo per sempre.
$src = (string) file_get_contents($radice . '/src/Dati/Nazione.php');
preg_match_all('/public int\s+\$(\w+)/', $src, $m);
$colpevoli = [];
foreach (glob($radice . '/src/Simulazione/Fasi/*.php') ?: [] as $f) {
    $righe = file($f) ?: [];
    foreach ($righe as $i => $riga) {
        foreach ($m[1] as $campo) {
            if (!preg_match('/->' . $campo . '\s*(=[^=]|\+=|-=|\*=)/', $riga)) {
                continue;
            }
            $blocco = implode(' ', array_slice($righe, $i, 3));
            if (preg_match('/perTick|tickAnno|\b0\.0\d|\/ *52/', $blocco)
                && !str_contains($blocco, 'mandatoTick')) {
                $colpevoli[] = $campo . ' in ' . basename($f, '.php');
            }
        }
    }
}
Prove::uguale('nessun intero mosso per frazioni', [], array_values(array_unique($colpevoli)));
