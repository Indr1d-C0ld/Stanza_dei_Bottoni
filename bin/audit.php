<?php

declare(strict_types=1);

/**
 * Audit del motore: che cosa gira, che cosa e' scritto e mai letto, che cosa
 * e' dichiarato e mai raggiunto.
 *
 * Non ripete le prove automatiche — quelle verificano che le cose FUNZIONINO.
 * Questo verifica che le cose ESISTANO e vengano USATE, che e' la classe di
 * difetti che le prove non vedono: una chiave di calibrazione che nessuno
 * legge, un verbo che la dottrina non sceglie mai, un campo calcolato a ogni
 * tick e mai consultato.
 *
 * E' la forma di difetto che questo progetto ha trovato piu' spesso: i quattro
 * campi interi mossi per frazioni, la democrazia che cambiava senza essere
 * salvata, la qualita' della vita che nessuno leggeva, il blocco 'validazione'
 * senza usi.
 *
 *   php bin/audit.php [--anni=15]
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Simulazione\EsecutoreTick;

$opz  = getopt('', ['anni::', 'semi::']);
$anni = (int) ($opz['anni'] ?? 15);
$semi = array_map('intval', explode(',', (string) ($opz['semi'] ?? '1,2,3')));

$problemi = [];
$segnala = static function (string $gruppo, string $cosa) use (&$problemi): void {
    $problemi[$gruppo][] = $cosa;
};

echo "Stanza dei Bottoni — audit del motore\n";
printf("  %d anni · semi %s\n\n", $anni, implode(',', $semi));

// ---------------------------------------------------------------- calibrazione
echo "1/7  Le chiavi di calibrazione\n";
$sorgente = '';
foreach (array_merge(glob($radice . '/src/**/*.php') ?: [], glob($radice . '/src/**/**/*.php') ?: [],
                     glob($radice . '/src/*.php') ?: [], glob($radice . '/bin/*.php') ?: []) as $f) {
    $sorgente .= (string) file_get_contents($f);
}
$cal = Calibrazione::carica($radice, 'gioco');
$piatte = [];
$appiattisci = static function (array $a, string $prefisso) use (&$appiattisci, &$piatte): void {
    foreach ($a as $k => $v) {
        if ($k === 'profilo' || $k === 'versione') {
            continue;
        }
        $chiave = $prefisso === '' ? (string) $k : "$prefisso.$k";
        if (is_array($v) && $v !== [] && !is_int(array_key_first($v))) {
            $appiattisci($v, $chiave);
        } else {
            $piatte[] = $chiave;
        }
    }
};
$appiattisci((array) (require $radice . '/calibrazione/base.php'), '');

$morte = [];
foreach ($piatte as $k) {
    // Si cerca la chiave intera o il suo ultimo pezzo dentro una stringa.
    $pezzo = substr($k, strrpos($k, '.') === false ? 0 : strrpos($k, '.') + 1);
    if (!str_contains($sorgente, "'$k'") && !str_contains($sorgente, "'$pezzo'")) {
        $morte[] = $k;
    }
}
printf("  %d chiavi dichiarate · %d mai lette\n", count($piatte), count($morte));
foreach ($morte as $k) {
    $segnala('chiavi di calibrazione mai lette', $k);
}

// E il valore di riserva scritto accanto a ogni lettura: se la chiave manca
// nei file e' quello che vale, e l'audit di settembre 2026 ne ha trovati nove
// lontani dalla calibrazione — il rischio di colpo di Stato 0,9 contro 0,10,
// il reclutamento insurrezionale 2,5 contro 0,001. Qui si confrontano con
// base.php, solo quelli scritti come numero letterale.
$base = (array) (require $radice . '/calibrazione/base.php');
$valoreDi = static function (array $a, string $k): mixed {
    foreach (explode('.', $k) as $pezzo) {
        if (!is_array($a) || !array_key_exists($pezzo, $a)) {
            return null;
        }
        $a = $a[$pezzo];
    }
    return $a;
};
$riserve = 0;
if (preg_match_all("/numero\('([a-z_.]+)',\s*(-?[0-9][0-9.eE+-]*)\)/", $sorgente, $mm, PREG_SET_ORDER)) {
    foreach ($mm as [, $k, $v]) {
        $b = $valoreDi($base, $k);
        if (!is_int($b) && !is_float($b)) {
            continue;
        }
        $riserve++;
        if (abs((float) $v - (float) $b) > 1e-9 * max(1.0, abs((float) $b))) {
            $segnala('valori di riserva diversi dalla calibrazione', "$k: $v nel codice, $b in base.php");
        }
    }
}
printf("  %d valori di riserva confrontati con base.php\n", $riserve);

// ---------------------------------------------------------------- i semi
echo "2/7  I file del seme\n";
$attesi = ['nazioni.csv', 'democrazia.php', 'disuguaglianza.php', 'esclusione.php',
           'alleanze.php', 'politica-nota.php', 'commercio-noto.php',
           'nomi-italiani.php', 'nomi-personaggi.php', 'confini.csv'];
foreach ($attesi as $s) {
    $p = $radice . '/db/seed/' . $s;
    if (!is_file($p)) {
        $segnala('file del seme mancanti', $s);
        continue;
    }
    if (str_ends_with($s, '.php')) {
        $v = @include $p;
        if (!is_array($v) || $v === []) {
            $segnala('file del seme vuoti o illeggibili', $s);
        }
    }
}
printf("  %d file attesi, %d presenti e leggibili\n", count($attesi),
    count($attesi) - count($problemi['file del seme mancanti'] ?? [])
                   - count($problemi['file del seme vuoti o illeggibili'] ?? []));

// ---------------------------------------------------------------- la corsa
echo "3/7  Il mondo gira: fasi, verbi, annotazioni\n";
$catalogo = array_keys((array) (require $radice . '/calibrazione/verbi.php'));
$verbiVisti = [];
$generiVisti = [];
$fasiViste = [];
$campiFermi = null;

foreach ($semi as $seme) {
    $c = Calibrazione::carica($radice, 'gioco');
    $m = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
    $e = new EsecutoreTick($c, null, true, $m);

    $primoStato = [];
    foreach ($m->elenco() as $n) {
        foreach (get_object_vars($n) as $campo => $val) {
            if (is_int($val) || is_float($val)) {
                $primoStato[$campo][$n->iso3] = (float) $val;
            }
        }
    }

    for ($t = 1; $t <= $anni * 52; $t++) {
        $m->tick = $t;
        foreach ($e->esegui($t, $seme) as $f) {
            if (isset($f['codice']) && ($f['saltata'] ?? false) === false) {
                $fasiViste[(string) $f['codice']] = true;
            }
        }
        foreach ($e->giornale() as $g) {
            $generiVisti[(string) ($g['genere'] ?? '')] = true;
        }
        foreach ($m->eventi as $ev) {
            $verbiVisti[(string) ($ev->verbo ?? '')] = true;
        }
    }

    // Campi numerici che non si sono mossi per NESSUN paese: e' lo stampo dei
    // quattro interi arrotondati, e va cercato da solo.
    $fermi = [];
    foreach ($primoStato as $campo => $valori) {
        $mosso = false;
        foreach ($m->elenco() as $n) {
            $ora = get_object_vars($n)[$campo] ?? null;
            if (is_numeric($ora) && abs((float) $ora - ($valori[$n->iso3] ?? 0.0)) > 1e-9) {
                $mosso = true;
                break;
            }
        }
        if (!$mosso) {
            $fermi[] = $campo;
        }
    }
    $campiFermi = $campiFermi === null ? $fermi : array_intersect($campiFermi, $fermi);
}

printf("  fasi eseguite: %d su 12\n", count($fasiViste));
for ($i = 0; $i <= 11; $i++) {
    $cod = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    if (!isset($fasiViste[$cod])) {
        $segnala('fasi mai eseguite', $cod);
    }
}
$verbiMai = array_values(array_diff($catalogo, array_keys($verbiVisti)));
printf("  verbi del catalogo: %d · mai scelti in %d semi: %d\n",
    count($catalogo), count($semi), count($verbiMai));
foreach ($verbiMai as $v) {
    $segnala('verbi mai scelti dalla dottrina', $v);
}
printf("  generi di annotazione visti: %d\n", count(array_filter(array_keys($generiVisti))));
printf("  campi numerici fermi in tutti i semi: %d\n", count((array) $campiFermi));
foreach ((array) $campiFermi as $campo) {
    // I campi strutturali NON devono muoversi: sono il seme, non lo stato.
    if (in_array($campo, ['areaKm2', 'valoreStrategico', 'valorePrestigio',
                          'quotaInvestimentiIniziale', 'quotaMilitareIniziale',
                          'soldatiIniziali', 'crescitaBase', 'crescitaPopolazione',
                          'disuguaglianza', 'esclusioneEtnica', 'gruppiEsclusi',
                          'alfabetizzazione', 'maturita'], true)) {
        continue;
    }
    // Ne' quelli transitori: la scossa esterna nasce e muore dentro lo stesso
    // tick (la scrivono le fasi 01 e 02, la consuma la 05), e fra un tick e
    // l'altro vale zero per costruzione.
    if ($campo === 'scossaEsterna') {
        continue;
    }
    $segnala('campi che non si muovono mai', $campo);
}

// ---------------------------------------------------------------- le viste
echo "4/7  Le viste e le rotte\n";
$rotte = [];
if (preg_match_all("/case '([a-z0-9\-]+)':/", (string) file_get_contents($radice . '/index.php'), $mm)) {
    $rotte = array_unique($mm[1]);
}
$viste = array_map(static fn (string $p): string => basename($p, '.php'),
    glob($radice . '/views/*.php') ?: []);
printf("  rotte dichiarate: %d · viste presenti: %d\n", count($rotte), count($viste));
foreach ($viste as $v) {
    $uso = (string) file_get_contents($radice . '/index.php');
    if (!str_contains($uso, "'$v'") && !str_contains($uso, "\"$v\"")) {
        $segnala('viste mai rese da index.php', $v . '.php');
    }
}

// ---------------------------------------------------------------- migrazioni
echo "5/7  Le migrazioni\n";
$file = glob($radice . '/db/migrations/*.sql') ?: [];
printf("  %d migrazioni sul disco\n", count($file));

// ---------------------------------------------------------------- documenti
echo "6/7  I rimandi dei documenti\n";
$rotti = 0;
foreach (array_merge(glob($radice . '/docs/*.md') ?: [], [$radice . '/README.md']) as $d) {
    if (preg_match_all('/`(docs\/[a-z0-9\-]+\.md|bin\/[a-z_]+\.php|db\/seed\/[a-z\-]+\.(php|csv))`/',
            (string) file_get_contents($d), $mm)) {
        foreach (array_unique($mm[1]) as $rif) {
            if (!file_exists($radice . '/' . $rif)) {
                $segnala('rimandi rotti nei documenti', basename($d) . ' → ' . $rif);
                $rotti++;
            }
        }
    }
}
printf("  rimandi verificati, %d rotti\n", $rotti);

// ---------------------------------------------------------------- colonne
echo "7/7  Le colonne dello schema\n";
// Una colonna che nessun sorgente nomina e' uno stato che nessuno scrive ne'
// legge: l'audit di settembre 2026 ne ha trovate una ventina, residui di un
// modello economico (debito, riserve, bilancio) e di ansie mai implementate.
// Si cerca il nome come parola intera in src/, bin/, views/ e index.php; le
// chiavi tecniche (id, creato, aggiornato) non contano.
try {
    $dbAudit = new \App\Nucleo\Basedati((array) \App\Nucleo\Configurazione::leggi('db', []));
    $colonne = $dbAudit->esegui(
        'SELECT table_name AS t, column_name AS c FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name LIKE "sdb\\_%"
          ORDER BY table_name, ordinal_position')->fetchAll();
    $sorgente = '';
    foreach (array_merge(glob($radice . '/src/*/*.php') ?: [], glob($radice . '/src/*/*/*.php') ?: [],
                         glob($radice . '/bin/*.php') ?: [], glob($radice . '/views/*.php') ?: [],
                         glob($radice . '/views/*/*.php') ?: [], [$radice . '/index.php']) as $f) {
        if (!str_ends_with($f, '/bin/audit.php')) {
            $sorgente .= (string) file_get_contents($f);
        }
    }
    $mute = 0;
    foreach ($colonne as $col) {
        $c = (string) $col['c'];
        if (in_array($c, ['id', 'creato', 'aggiornato'], true)) {
            continue;
        }
        if (!preg_match('/\b' . preg_quote($c, '/') . '\b/', $sorgente)) {
            $segnala('colonne che nessuno nomina', $col['t'] . '.' . $c);
            $mute++;
        }
    }
    printf("  %d colonne · %d mai nominate dal codice\n", count($colonne), $mute);
} catch (\Throwable $e) {
    printf("  (saltato: nessuna base dati — %s)\n", $e->getMessage());
}

// ---------------------------------------------------------------- esito
echo "\n";
if ($problemi === []) {
    echo "  Nessun problema trovato.\n";
    exit(0);
}
$totale = 0;
foreach ($problemi as $gruppo => $elenco) {
    printf("  %s (%d):\n", $gruppo, count($elenco));
    foreach ($elenco as $x) {
        printf("    · %s\n", $x);
        $totale++;
    }
}
printf("\n  %d rilievi in %d categorie.\n", $totale, count($problemi));
exit(1);
