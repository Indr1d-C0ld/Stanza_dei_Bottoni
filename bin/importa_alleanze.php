<?php

declare(strict_types=1);

/**
 * Importa le alleanze formali del Correlates of War e produce
 * db/seed/alleanze.php.
 *
 * PERCHE' ESISTE. Gli obblighi di trattato erano [FABBRICATO]: si deducevano
 * dall'affinita' — chi si piace abbastanza risulta alleato. E' un modo per
 * avere dei trattati, non per avere QUELLI VERI, e l'integrita' — il
 * meccanismo con cui Crawford rende costose le promesse — mordeva su garanzie
 * che nessuno aveva mai firmato.
 *
 * La sostituzione era gia' scritta nel codice: «Da sostituire con Correlates
 * of War».
 *
 * ATTENZIONE ALL'EPOCA, che e' la lezione di questo progetto. COW v4.1 arriva
 * al **2012** e il nostro seme e' del 2024-25: dodici anni di scarto, in cui
 * sono successe cose grosse. Le differenze note stanno nella tavola
 * AGGIORNAMENTI qui sotto, ciascuna con la propria data. Non si prende un
 * dataset autorevole e lo si applica a un mondo di un'altra epoca: e' lo
 * stesso errore dei ~10 cambi irregolari l'anno di Crawford, che erano giusti
 * per il 1968.
 *
 *   php bin/importa_alleanze.php [--dir=percorso]
 *
 * Senza --dir scarica e scompatta da correlatesofwar.org.
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

/** Nomi COW che non coincidono col nome inglese del nostro seme. */
const NOMI = [
    'United Arab Emirates' => 'ARE', 'United States of America' => 'USA',
    'Bahamas' => 'BHS', 'Dominican Republic' => 'DOM', 'St. Lucia' => 'LCA',
    'St. Vincent and the Grenadines' => 'VCT', 'Antigua & Barbuda' => 'ATG',
    'St. Kitts and Nevis' => 'KNA', 'German Federal Republic' => 'DEU',
    'Czech Republic' => 'CZE', 'Cape Verde' => 'CPV', 'Gambia' => 'GMB',
    'Ivory Coast' => 'CIV', 'Swaziland' => 'SWZ', 'Yugoslavia' => 'SRB',
    'Central African Republic' => 'CAF', 'Congo' => 'COG',
    'Democratic Republic of the Congo' => 'COD',
];

/**
 * Quel che e' cambiato dopo il 2012, con la data e la ragione.
 *
 * Solo allargamenti e scioglimenti di patti di DIFESA, che sono quelli che
 * contano per l'integrita'. Ogni riga e' un fatto pubblico e databile.
 *
 * @var array<int, array{0:string, 1:list<string>, 2:string}>
 *      [chi entra, con chi si allea, perche' e quando]
 */
const AGGIORNAMENTI = [
    ['HRV', ['NATO'], 'Croazia nella NATO, 01/04/2009 — gia\' in COW, qui per memoria'],
    ['MNE', ['NATO'], 'Montenegro nella NATO, 05/06/2017'],
    ['MKD', ['NATO'], 'Macedonia del Nord nella NATO, 27/03/2020'],
    ['FIN', ['NATO'], 'Finlandia nella NATO, 04/04/2023'],
    ['SWE', ['NATO'], 'Svezia nella NATO, 07/03/2024'],
];

/**
 * Le basi: truppe straniere schierate con un mandato, che COW non conta perche'
 * non sono un trattato fra Stati del suo elenco. Gradino 64.
 *
 * @var array<int, array{0:string, 1:string, 2:string}> [chi schiera, dove, perche' e quando]
 */
const BASI = [
    ['USA', 'XKX', 'KFOR, Camp Bondsteel: la forza NATO in Kosovo per la risoluzione ONU 1244 (10/06/1999)'],
    ['ITA', 'XKX', 'KFOR: l\'Italia e\' fra i contributori maggiori e ne ha avuto piu\' volte il comando'],
];

/**
 * Gli scioglimenti: i legami che il dataset porta ancora e che non esistono
 * piu'. Si tolgono in entrambe le direzioni, a ogni gradino.
 *
 * Il caso che li ha fatti nascere: COW al 2012 tiene la rete della CSI del
 * 1991 come patto di difesa, e il seme rendeva la Russia GARANTE della
 * difesa dell'Ucraina — e con lei Bielorussia, Kazakistan e altri sette. Al
 * primo tick di una guerra russo-ucraina dieci «garanti» tradivano l'impegno.
 *
 * @var array<int, array{0:string, 1:list<string>, 2:string}>
 *      [chi esce, da chi si separa, perche' e quando]
 */
const SCIOGLIMENTI = [
    ['UKR', ['RUS', 'BLR', 'ARM', 'AZE', 'GEO', 'KAZ', 'KGZ', 'MDA', 'TJK', 'TKM', 'UZB'],
        'Ucraina fuori dagli accordi della CSI (decreto del 19/05/2018), annessione della '
        . 'Crimea 2014, trattato di amicizia con la Russia cessato il 01/04/2019, invasione '
        . 'russa dal 24/02/2022'],
    ['GEO', ['RUS', 'BLR', 'ARM', 'AZE', 'KAZ', 'KGZ', 'MDA', 'TJK', 'TKM', 'UZB'],
        'Georgia fuori dalla CSI dal 18/08/2009, dopo la guerra con la Russia del 2008'],
    // Un congelamento non e' un'uscita, ma una garanzia che il garantito
    // dichiara di non credere piu' non trattiene nessuno: nel 2022 e nel 2023
    // la CSTO non si e' mossa per l'Armenia, e con lei in piedi il seme
    // metteva l'Armenia sotto l'ombrello nucleare russo.
    ['ARM', ['RUS', 'BLR', 'KAZ', 'KGZ', 'TJK'],
        'Armenia: partecipazione alla CSTO congelata (Pashinyan, 22/02/2024) dopo che '
        . 'l\'alleanza non era intervenuta negli attacchi azeri del 2022 e del 2023'],
];

/** I membri NATO al 2012 secondo COW, piu' quelli aggiunti sopra. */
const NATO_2012 = [
    'USA', 'CAN', 'GBR', 'FRA', 'DEU', 'ITA', 'ESP', 'PRT', 'NLD', 'BEL',
    'LUX', 'DNK', 'NOR', 'ISL', 'GRC', 'TUR', 'POL', 'CZE', 'HUN', 'SVK',
    'SVN', 'EST', 'LVA', 'LTU', 'ROU', 'BGR', 'ALB', 'HRV',
];

$opz = getopt('', ['dir::']);
$dir = (string) ($opz['dir'] ?? '');

if ($dir === '') {
    echo "Scarico da correlatesofwar.org...\n";
    $zip = sys_get_temp_dir() . '/cow-alleanze.zip';
    $dati = @file_get_contents('https://correlatesofwar.org/wp-content/uploads/version4.1_csv.zip');
    if ($dati === false || strlen($dati) < 100000) {
        fwrite(STDERR, "Scaricamento fallito. Usa --dir=<cartella scompattata>.\n");
        exit(1);
    }
    file_put_contents($zip, $dati);
    $dir = sys_get_temp_dir() . '/cow-alleanze';
    $z = new ZipArchive();
    if ($z->open($zip) !== true) {
        fwrite(STDERR, "Archivio illeggibile.\n");
        exit(1);
    }
    $z->extractTo($dir);
    $z->close();
    $dir .= '/version4.1_csv';
}

// --- i codici COW -----------------------------------------------------------
$codici = $dir . '/../COW-country-codes.csv';
if (!is_file($codici)) {
    $codici = sys_get_temp_dir() . '/COW-country-codes.csv';
    $c = @file_get_contents('https://correlatesofwar.org/wp-content/uploads/COW-country-codes.csv');
    if ($c === false) {
        fwrite(STDERR, "Non riesco a prendere i codici COW.\n");
        exit(1);
    }
    file_put_contents($codici, $c);
}
// Il file usa ritorni a capo vecchio stile: si normalizzano prima di leggerlo.
$testo = str_replace("\r", "\n", (string) file_get_contents($codici));
$nomeDi = [];
foreach (explode("\n", $testo) as $r) {
    $p = str_getcsv($r);
    if (count($p) < 3 || !ctype_digit(trim((string) $p[1]))) {
        continue;
    }
    $nomeDi[(int) $p[1]] = trim((string) $p[2]);
}

// --- il nostro seme, per nome inglese ---------------------------------------
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$perNome = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $r);
    $perNome[mb_strtolower(trim((string) $d['nome_fonte']))] = $d['iso3'];
}
fclose($fh);

$iso = static function (int $ccode) use ($nomeDi, $perNome): ?string {
    $nome = $nomeDi[$ccode] ?? '';
    if ($nome === '') {
        return null;
    }
    if (isset(NOMI[$nome])) {
        return NOMI[$nome];
    }

    return $perNome[mb_strtolower($nome)] ?? null;
};

// --- le alleanze dell'ultimo anno disponibile -------------------------------
$f = fopen($dir . '/alliance_v4.1_by_directed_yearly.csv', 'r');
if ($f === false) {
    fwrite(STDERR, "Non trovo alliance_v4.1_by_directed_yearly.csv in $dir\n");
    exit(1);
}
fgetcsv($f);

$righe = [];
$ultimoAnno = 0;
while (($r = fgetcsv($f)) !== false) {
    $anno = (int) ($r[17] ?? 0);
    if ($anno > $ultimoAnno) {
        $ultimoAnno = $anno;
    }
    $righe[] = $r;
}
fclose($f);

/**
 * Dal tipo di patto COW al nostro gradino di obbligo.
 *
 * La nostra scala misura QUANTO impegna, non che cosa promette:
 * 16 diplomatiche · 32 commerciali · 64 basi · 96 difesa · 128 difesa nucleare.
 * Il gradino 128 non si decide qui: lo assegna Mondo, che sa chi ha l'atomica.
 */
$gradino = static function (array $r): int {
    if ((int) ($r[13] ?? 0) === 1) { return 96; }   // defense
    if ((int) ($r[15] ?? 0) === 1) { return 32; }   // nonaggression
    if ((int) ($r[14] ?? 0) === 1) { return 32; }   // neutrality
    if ((int) ($r[16] ?? 0) === 1) { return 16; }   // entente

    return 0;
};

$obblighi = [];
$saltati = [];
foreach ($righe as $r) {
    if ((int) ($r[17] ?? 0) !== $ultimoAnno) {
        continue;
    }
    $a = $iso((int) $r[1]);
    $b = $iso((int) $r[3]);
    if ($a === null || $b === null) {
        $saltati[($nomeDi[(int) $r[1]] ?? '?') . '/' . ($nomeDi[(int) $r[3]] ?? '?')] = true;
        continue;
    }
    if ($a === $b || !isset($mondo->nazioni[$a]) || !isset($mondo->nazioni[$b])) {
        continue;
    }
    $g = $gradino($r);
    $chiave = $a . '|' . $b;
    // Fra due Stati possono esserci piu' trattati: vale il piu' impegnativo.
    $obblighi[$chiave] = max($obblighi[$chiave] ?? 0, $g);
}

// --- gli allargamenti posteriori al dataset ---------------------------------
$nato = NATO_2012;
$aggiunti = [];
foreach (AGGIORNAMENTI as [$chi, $patti, $perche]) {
    if (!in_array('NATO', $patti, true) || !isset($mondo->nazioni[$chi])) {
        continue;
    }
    if (!in_array($chi, $nato, true)) {
        $nato[] = $chi;
        $aggiunti[] = $chi;
    }
}
foreach ($nato as $a) {
    foreach ($nato as $b) {
        if ($a === $b || !isset($mondo->nazioni[$a]) || !isset($mondo->nazioni[$b])) {
            continue;
        }
        $obblighi[$a . '|' . $b] = max($obblighi[$a . '|' . $b] ?? 0, 96);
    }
}
// --- le basi con mandato -----------------------------------------------------
foreach (BASI as [$chi, $dove, $perche]) {
    if (isset($mondo->nazioni[$chi], $mondo->nazioni[$dove])) {
        $obblighi[$chi . '|' . $dove] = max($obblighi[$chi . '|' . $dove] ?? 0, 64);
    }
}

// --- gli scioglimenti posteriori (o sfuggiti) al dataset -------------------
$sciolti = 0;
foreach (SCIOGLIMENTI as [$chi, $altri, $perche]) {
    foreach ($altri as $altro) {
        foreach ([$chi . '|' . $altro, $altro . '|' . $chi] as $k) {
            if (isset($obblighi[$k])) {
                unset($obblighi[$k]);
                $sciolti++;
            }
        }
    }
}
ksort($obblighi);

// --- uscita -----------------------------------------------------------------
$conteggio = array_count_values($obblighi);
ksort($conteggio);

$out = "<?php\n\ndeclare(strict_types=1);\n\n"
     . "/**\n"
     . " * Obblighi di trattato fra Stati, sulla scala 0/16/32/64/96/128.\n"
     . " *\n"
     . " * GENERATO DA bin/importa_alleanze.php — non si modifica a mano.\n"
     . " *\n"
     . " * Fonte: Correlates of War, Formal Alliances v4.1 (Gibler, Universita'\n"
     . " * dell'Alabama). Ultimo anno del dataset: $ultimoAnno.\n"
     . " *\n"
     . " * IL DATASET FINISCE NEL $ultimoAnno e il nostro seme e' del 2024-25. Gli\n"
     . " * allargamenti successivi sono aggiunti a mano nell'importatore, con la\n"
     . " * data accanto: Montenegro 2017, Macedonia del Nord 2020, Finlandia 2023,\n"
     . " * Svezia 2024. E gli scioglimenti: l'Ucraina e la Georgia fuori dalla CSI,\n"
     . " * l'Armenia che congela la CSTO nel 2024; e le basi con mandato (KFOR),\n"
     . " * che COW tiene ancora come patto di difesa. Prendere un dataset\n"
     . " * autorevole e applicarlo a un mondo di\n"
     . " * un'altra epoca e' l'errore che questo progetto ha gia' fatto una volta.\n"
     . " *\n"
     . " * Il gradino 128 (difesa nucleare) NON sta qui: lo assegna Mondo, che sa\n"
     . " * quali Stati hanno l'atomica.\n"
     . " *\n"
     . " * Le chiavi sono direzionate «A|B», come le relazioni: un impegno puo'\n"
     . " * essere asimmetrico.\n"
     . " */\n\nreturn [\n";
foreach ($obblighi as $k => $v) {
    $out .= sprintf("    '%s' => %d,\n", $k, $v);
}
$out .= "];\n";

file_put_contents($radice . '/db/seed/alleanze.php', $out);

printf("Scritte %d coppie in db/seed/alleanze.php (anno COW %d)\n", count($obblighi), $ultimoAnno);
foreach ($conteggio as $g => $n) {
    printf("  gradino %3d: %5d coppie\n", $g, $n);
}
if ($aggiunti !== []) {
    printf("  allargamenti NATO posteriori al dataset: %s\n", implode(', ', $aggiunti));
}
if ($saltati !== []) {
    printf("  coppie saltate per Stati non nel nostro mondo: %d\n", count($saltati));
}
