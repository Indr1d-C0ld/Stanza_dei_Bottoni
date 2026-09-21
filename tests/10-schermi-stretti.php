<?php

declare(strict_types=1);

/**
 * Il foglio di stile sugli schermi stretti.
 *
 * Qui non si prova come APPARE — per quello serve un occhio — ma le due cose
 * che si possono verificare senza guardare, e che sono quelle che si rompono
 * per distrazione:
 *
 * 1. che tutto quel che e' stato aggiunto per telefoni e tavolette stia dentro
 *    una media query, cioe' che il desktop resti esattamente com'era. E' la
 *    richiesta esplicita con cui il lavoro e' stato commissionato, ed e' anche
 *    il modo piu' facile di fare danni: basta una parentesi fuori posto.
 * 2. che i numeri che contano ci siano: sedici pixel nei campi di modulo,
 *    perche' sotto quella soglia iOS ingrandisce la pagina da solo e non la
 *    rimpicciolisce piu'; e un'area toccabile grande abbastanza per un dito.
 */

$radice = dirname(__DIR__);
$percorso = $radice . '/assets/css/stanza.css';
$css = (string) file_get_contents($percorso);
$senzaCommenti = (string) preg_replace('/\/\*.*?\*\//s', '', $css);

Prove::gruppo('Il foglio di stile e\' valido');

$aperte = substr_count($senzaCommenti, '{');
$chiuse = substr_count($senzaCommenti, '}');
Prove::uguale('le graffe sono bilanciate', $aperte, $chiuse);

$profondita = 0;
$minimo = 0;
foreach (str_split($senzaCommenti) as $c) {
    if ($c === '{') {
        $profondita++;
    } elseif ($c === '}') {
        $profondita--;
        $minimo = min($minimo, $profondita);
    }
}
Prove::uguale('e non si chiudono mai in anticipo', 0, $minimo);
Prove::uguale('ne\' restano aperte alla fine', 0, $profondita);

Prove::gruppo('Le media query ci sono');

foreach (['(max-width: 900px)', '(max-width: 600px)', '(hover: none)'] as $q) {
    Prove::che("c'e' la query $q", str_contains($css, '@media ' . $q));
}

Prove::gruppo('Il desktop non e\' stato toccato');

// Tutto quel che viene dopo l'intestazione del blocco mobile deve stare dentro
// una media query. Una regola libera li' dentro cambierebbe anche il desktop.
$inizio = strpos($css, 'SCHERMI STRETTI');
Prove::che('il blocco per schermi stretti e\' riconoscibile', $inizio !== false);

$coda = (string) preg_replace('/\/\*.*?\*\//s', '', substr($css, (int) $inizio));
$fuori = [];
$d = 0;
preg_match_all('/@media[^{]*\{|\{|\}|([^{}@]+)\{/', $coda, $m, PREG_SET_ORDER);
foreach ($m as $pezzo) {
    $t = $pezzo[0];
    if (str_starts_with($t, '@media')) {
        $d++;
    } elseif ($t === '{') {
        $d++;
    } elseif ($t === '}') {
        $d--;
    } else {
        if ($d === 0) {
            $fuori[] = trim($t);
        }
        $d++;
    }
}
Prove::uguale('nessuna regola nuova fuori da una media query', [], $fuori);

$prima = substr($css, 0, (int) $inizio);
Prove::che('e la classe .prosa non esiste fuori dal blocco mobile',
    !str_contains($prima, '.prosa'),
    'su desktop una tabella «prosa» deve essere una tabella come le altre');

Prove::gruppo('I numeri che contano su un telefono');

Prove::che('i campi di modulo arrivano a sedici pixel',
    (bool) preg_match('/font-size:\s*16px/', $css),
    'sotto i sedici, iOS ingrandisce la pagina da solo quando ci si tocca dentro');
Prove::che('i bottoni hanno un\'area toccabile',
    (bool) preg_match('/min-height:\s*2\.[3-9]rem/', $css),
    'un dito non e\' un puntatore');
Prove::che('le tabelle scorrono dentro se\' stesse',
    str_contains($css, 'overflow-x: auto') && str_contains($css, 'display: block'),
    'altrimenti a scorrere di lato e\' la pagina intera');

Prove::gruppo('L\'intestazione fa il suo mestiere');

$testa = (string) file_get_contents($radice . '/views/parti/intestazione.php');
Prove::che('c\'e\' il meta viewport',
    str_contains($testa, 'width=device-width'),
    'senza, il telefono finge di essere largo 980 pixel e rimpicciolisce tutto');
Prove::che('lo zoom con le dita NON e\' bloccato',
    !str_contains($testa, 'user-scalable=no') && !str_contains($testa, 'maximum-scale'),
    'sul planisfero e\' l\'unico modo di toccare un paese piccolo');
Prove::che('il foglio di stile porta la data in coda',
    str_contains($testa, 'filemtime'),
    'senza, una correzione allo stile non arriva a chi e\' gia\' stato sul sito');

Prove::gruppo('Le tabelle di prosa sono marcate');

$marcate = 0;
foreach (glob($radice . '/views/*.php') ?: [] as $f) {
    $marcate += substr_count((string) file_get_contents($f), 'class="tabella prosa"');
}
Prove::che('qualche tabella e\' marcata come prosa', $marcate >= 5,
    sprintf('%d trovate: senza, una descrizione diventa una riga larga seicento pixel', $marcate));
