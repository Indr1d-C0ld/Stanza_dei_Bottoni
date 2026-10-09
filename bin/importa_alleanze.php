<?php

declare(strict_types=1);

/**
 * Importa le alleanze di ATOP e produce db/seed/alleanze.php.
 *
 * PERCHE' ESISTE. Gli obblighi di trattato erano [FABBRICATO]: si deducevano
 * dall'affinita' — chi si piace abbastanza risulta alleato. Poi vennero dal
 * Correlates of War, Formal Alliances v4.1, che arriva al 2012. Adesso vengono
 * da
 *
 *   LEEDS, RITTER, MITCHELL, LONG (2002), «Alliance Treaty Obligations and
 *   Provisions, 1815-1944», International Interactions 28(3); ATOP v5.1
 *   (Leeds et al. 2020), 1815-2018 — www.atopdata.org
 *
 * che e' piu' recente di sei anni e, soprattutto, dice per ogni membro che
 * cosa promette e a chi, e se il patto e' bilaterale o fra molti. E' la
 * differenza fra il Patto atlantico e la Lega araba, che il Correlates of War
 * trattava allo stesso modo (docs/32).
 *
 * ATTENZIONE ALL'EPOCA, che e' la lezione di questo progetto. ATOP arriva al
 * **2018** e il nostro seme e' del gennaio 2026. Quel che e' cambiato dopo, o
 * che il dataset tiene in piedi contro i fatti, sta nelle tavole qui sotto,
 * ciascuna riga con la sua data. Non si prende un dataset autorevole e lo si
 * applica a un mondo di un'altra epoca.
 *
 *   php bin/importa_alleanze.php
 *
 * Legge storage/fonti/atop/ e storage/fonti/cow-codici.csv (i membri ATOP
 * hanno i codici del Correlates of War).
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

const ANNO = 2018;

/** Nomi COW che non coincidono col nome inglese del nostro seme. */
const NOMI = [
    'United Arab Emirates' => 'ARE', 'United States of America' => 'USA',
    'Bahamas' => 'BHS', 'Dominican Republic' => 'DOM', 'St. Lucia' => 'LCA',
    'St. Vincent and the Grenadines' => 'VCT', 'Antigua & Barbuda' => 'ATG',
    'St. Kitts and Nevis' => 'KNA', 'German Federal Republic' => 'DEU', 'Germany' => 'DEU',
    'Czech Republic' => 'CZE', 'Cape Verde' => 'CPV', 'Gambia' => 'GMB',
    'Ivory Coast' => 'CIV', 'Swaziland' => 'SWZ', 'Yugoslavia' => 'SRB',
    'Central African Republic' => 'CAF', 'Congo' => 'COG',
    'Democratic Republic of the Congo' => 'COD', 'Taiwan' => 'TWN', 'Kosovo' => 'XKX',
    'Yemen' => 'YEM', 'East Timor' => 'TLS', 'Myanmar' => 'MMR', 'Macedonia' => 'MKD',
];

/**
 * I patti di difesa nati dopo il 2018. Ogni membro promette difesa a tutti
 * gli altri: gradino 96 (il 128 lo assegna Mondo a chi ha l'atomica).
 *
 * @var array<int, array{0:list<string>, 1:string}>
 */
const PATTI_NUOVI = [
    [['RUS', 'PRK'], 'Trattato di partenariato strategico globale Russia-Corea del Nord, firmato il '
        . '19/06/2024, in vigore dal 04/12/2024: articolo 4, assistenza militare reciproca'],
    [['SAU', 'PAK'], 'Accordo strategico di mutua difesa Arabia Saudita-Pakistan, 17/09/2025'],
    [['TUR', 'AZE'], 'Dichiarazione di Shusha sulle relazioni alleate, 15/06/2021: assistenza reciproca '
        . 'in caso di aggressione'],
    [['MLI', 'BFA', 'NER'], 'Carta del Liptako-Gourma, Alleanza degli Stati del Sahel, 16/09/2023: '
        . 'ogni attacco a uno e\' un attacco a tutti'],
];

/**
 * Chi entra in un patto che ATOP gia' conosce, dopo il 2018: l'alleanza ATOP
 * e il nuovo membro.
 *
 * @var array<int, array{0:string, 1:string, 2:string}>
 */
const INGRESSI = [
    ['3180', 'MKD', 'Macedonia del Nord nella NATO, 27/03/2020'],
    ['3180', 'FIN', 'Finlandia nella NATO, 04/04/2023'],
    ['3180', 'SWE', 'Svezia nella NATO, 07/03/2024'],
];

/**
 * Le basi: truppe straniere schierate con un mandato, che non sono un
 * trattato fra Stati. Gradino 64.
 *
 * @var array<int, array{0:string, 1:string, 2:string}> [chi schiera, dove, perche' e quando]
 */
const BASI = [
    ['USA', 'XKX', 'KFOR, Camp Bondsteel: la forza NATO in Kosovo per la risoluzione ONU 1244 (10/06/1999)'],
    ['ITA', 'XKX', 'KFOR: l\'Italia e\' fra i contributori maggiori e ne ha avuto piu\' volte il comando'],
    // Non un patto di difesa — la «ambiguita' strategica» e' proprio questo —
    // ma un impegno scritto in una legge e truppe sul posto: e' un filo
    // d'inciampo (Schelling), e il gradino giusto e' quello delle basi.
    ['USA', 'TWN', 'Taiwan Relations Act (10/04/1979): un attacco a Taiwan e\' «di grave preoccupazione», '
        . 'e gli Stati Uniti ne mantengono la capacita\' di difendersi; addestratori militari americani '
        . 'sull\'isola autorizzati dal Taiwan Enhanced Resilience Act (NDAA 2023)'],
];

/**
 * Gli scioglimenti: i legami che il dataset porta ancora e che non esistono
 * piu', o che i fatti smentiscono. Si tolgono in entrambe le direzioni, a ogni
 * gradino.
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
    // dichiara di non credere piu' non trattiene nessuno.
    ['ARM', ['RUS', 'BLR', 'KAZ', 'KGZ', 'TJK'],
        'Armenia: partecipazione alla CSTO congelata (Pashinyan, 22/02/2024) dopo che '
        . 'l\'alleanza non era intervenuta negli attacchi azeri del 2022 e del 2023'],
    ['ARM', ['AZE'], 'Armenia e Azerbaigian: ATOP li tiene in accordi della CSI di non aggressione e '
        . 'consultazione, e si sono fatti la guerra nel 2016, nel 2020 e nel 2023; testo di pace siglato a '
        . 'Washington l\'08/08/2025, non ancora un trattato in vigore'],
    ['DZA', ['MAR'], 'Algeria e Marocco: rapporti diplomatici rotti dall\'Algeria il 24/08/2021, '
        . 'frontiera chiusa dal 1994. ATOP li lega nella Lega araba e nell\'Unione del Maghreb'],
    ['RWA', ['COD'], 'Ruanda e Congo: l\'M23 sostenuto da truppe ruandesi prende Goma il 27/01/2025 '
        . '(UCDP). ATOP li lega nel patto dei Grandi Laghi del 2006'],
    // L'ECOWAS: l'Alleanza degli Stati del Sahel ne esce il 29/01/2025.
    ['MLI', ['BEN', 'CIV', 'GMB', 'GHA', 'GNB', 'GIN', 'LBR', 'NGA', 'SEN', 'SLE', 'TGO', 'CPV'],
        'Mali fuori dall\'ECOWAS, 29/01/2025'],
    ['BFA', ['BEN', 'CIV', 'GMB', 'GHA', 'GNB', 'GIN', 'LBR', 'NGA', 'SEN', 'SLE', 'TGO', 'CPV'],
        'Burkina Faso fuori dall\'ECOWAS, 29/01/2025'],
    ['NER', ['BEN', 'CIV', 'GMB', 'GHA', 'GNB', 'GIN', 'LBR', 'NGA', 'SEN', 'SLE', 'TGO', 'CPV'],
        'Niger fuori dall\'ECOWAS, 29/01/2025'],
];

/**
 * Le alleanze di ATOP che non si prendono affatto.
 *
 * La 4400 comincia il 22/01/1993: e' la Carta della Comunita' degli Stati
 * Indipendenti, adottata a Minsk quel giorno, che ATOP codifica come patto di
 * difesa fra otto paesi — compresi l'Armenia e l'Azerbaigian, che si sono fatti
 * la guerra nel 2016, nel 2020 e nel 2023, e l'Uzbekistan e l'Azerbaigian, usciti
 * dal trattato di sicurezza collettiva nel 1999. La difesa collettiva vera
 * dello spazio post-sovietico e' la CSTO (la 4220), e i legami bilaterali veri
 * stanno in ATOP per conto loro: Russia-Uzbekistan (1992, 2005),
 * Tagikistan-Uzbekistan, Turchia-Azerbaigian. Era il difetto che docs/28
 * lasciava aperto, «la CSI come patto di difesa» (docs/33).
 */
const ESCLUSE = ['4400' => 'Carta della CSI, Minsk, 22/01/1993'];

$atop = $radice . '/storage/fonti/atop/ATOP 5.1 (.csv)';
foreach (["$atop/atop5_1m.csv", "$atop/atop5_1a.csv", $radice . '/storage/fonti/cow-codici.csv'] as $f) {
    if (!is_file($f)) {
        fwrite(STDERR, "Manca $f.\nATOP: http://www.atopdata.org/uploads/6/9/1/3/69134503/atop_5.1__.csv_.zip\n"
            . "Codici COW: https://correlatesofwar.org/wp-content/uploads/COW-country-codes.csv\n");
        exit(1);
    }
}

// --- i codici ----------------------------------------------------------------
$nomeDi = [];
foreach (file($radice . '/storage/fonti/cow-codici.csv', FILE_IGNORE_NEW_LINES) ?: [] as $r) {
    $p = str_getcsv($r, ',', '"', '\\');
    if (count($p) >= 3 && ctype_digit(trim((string) $p[1]))) {
        $nomeDi[(int) $p[1]] = trim((string) $p[2]);
    }
}
$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$perNome = [];
$fh = fopen($radice . '/db/seed/nazioni.csv', 'r');
$int = fgetcsv($fh, 0, ',', '"', '\\');
while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($int, $r);
    $perNome[mb_strtolower(trim((string) $d['nome_fonte']))] = (string) $d['iso3'];
}
fclose($fh);
$iso = static function (int $ccode) use ($nomeDi, $perNome): ?string {
    $nome = $nomeDi[$ccode] ?? '';
    return $nome === '' ? null : (NOMI[$nome] ?? $perNome[mb_strtolower($nome)] ?? null);
};

// --- le alleanze in vigore nel 2018, membro per membro -------------------
$bilaterale = [];
$f = fopen("$atop/atop5_1a.csv", 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    $bilaterale[(string) $d['atopid']] = (int) $d['bilat'] === 1;
}
fclose($f);

$membri = [];   // atopid => iso => promesse
$f = fopen("$atop/atop5_1m.csv", 'r');
$t = fgetcsv($f, 0, ',', '"', '\\');
$saltati = [];
while (($r = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
    $d = array_combine($t, $r);
    $entra = (int) $d['yrent'];
    $esce  = (int) $d['yrexit'];
    if ($entra > ANNO || ($esce !== 0 && $esce < ANNO) || isset(ESCLUSE[(string) $d['atopid']])) {
        continue;
    }
    $chi = $iso((int) $d['member']);
    if ($chi === null || !isset($mondo->nazioni[$chi])) {
        $saltati[$nomeDi[(int) $d['member']] ?? $d['member']] = true;
        continue;
    }
    $prima = $membri[(string) $d['atopid']][$chi] ?? [];
    foreach (['defense', 'offense', 'neutral', 'nonagg', 'consul'] as $k) {
        $prima[$k] = ($prima[$k] ?? false) || (int) $d[$k] === 1;
    }
    $membri[(string) $d['atopid']][$chi] = $prima;
}
fclose($f);

foreach (INGRESSI as [$id, $chi, $perche]) {
    if (isset($membri[$id]) && isset($mondo->nazioni[$chi])) {
        $membri[$id][$chi] = ['defense' => true, 'offense' => false, 'neutral' => false,
            'nonagg' => true, 'consul' => true];
    }
}

/**
 * Dalla promessa di ATOP al nostro gradino. La nostra scala misura QUANTO
 * impegna: 16 consultazione · 32 neutralita' o non aggressione · 64 basi o
 * patto offensivo · 96 difesa · 128 difesa nucleare (la assegna Mondo).
 */
$gradino = static function (array $p): int {
    return match (true) {
        $p['defense'] => 96,
        $p['offense'] => 64,
        $p['neutral'], $p['nonagg'] => 32,
        $p['consul'] => 16,
        default => 0,
    };
};

/**
 * I patti fra molti con una struttura militare integrata, che non e' scritta
 * nel trattato e che ATOP quindi non vede (il suo «milcon» da' 0 al Patto
 * atlantico del 1949 e 2 alla Lega araba). Sono un fatto pubblico e datato:
 * gli altri patti regionali — l'OAS, l'Unione africana, la Lega araba, la SADC
 * — sono promesse sulla carta, e trattarli come la NATO faceva amici stretti il
 * Peru' e Haiti (docs/32).
 */
const INTEGRATI = [
    '3180' => 'NATO: comando militare integrato (SHAPE) dal 02/04/1951',
    '4220' => 'CSTO: Forza collettiva di reazione rapida dal 04/02/2009',
    '4965' => 'Consiglio di cooperazione del Golfo: Peninsula Shield Force dal 1984',
];

$obblighi = [];
$patti = [];      // "A|B" => 'bilaterale'|'integrato'|'multilaterale' per la difesa
foreach ($membri as $id => $chi) {
    $molti = count($chi) > 2 && !($bilaterale[$id] ?? false);
    foreach ($chi as $a => $promesse) {
        $g = $gradino($promesse);
        if ($g === 0) {
            continue;
        }
        foreach (array_keys($chi) as $b) {
            if ($a === $b) {
                continue;
            }
            $k = "$a|$b";
            $obblighi[$k] = max($obblighi[$k] ?? 0, $g);
            if ($g >= 96) {
                // Sulla carta, la promessa si diluisce coi membri: si annota
                // quanti sono, e vale il patto piu' stretto.
                $tipo = !$molti ? 'bilaterale' : (isset(INTEGRATI[$id]) ? 'integrato' : 'multilaterale:' . count($chi));
                $rango = static fn(string $t): float => match (true) {
                    $t === 'bilaterale' => 1000.0,
                    $t === 'integrato'  => 500.0,
                    default             => 100.0 - (float) explode(':', $t)[1],
                };
                if (!isset($patti[$k]) || $rango($tipo) > $rango($patti[$k])) {
                    $patti[$k] = $tipo;
                }
            }
        }
    }
}
foreach (PATTI_NUOVI as [$chi, $perche]) {
    foreach ($chi as $a) {
        foreach ($chi as $b) {
            if ($a !== $b && isset($mondo->nazioni[$a], $mondo->nazioni[$b])) {
                $obblighi["$a|$b"] = max($obblighi["$a|$b"] ?? 0, 96);
                $patti["$a|$b"] = count($chi) === 2 ? 'bilaterale' : 'multilaterale:' . count($chi);
            }
        }
    }
}
foreach (BASI as [$chi, $dove, $perche]) {
    if (isset($mondo->nazioni[$chi], $mondo->nazioni[$dove])) {
        $obblighi["$chi|$dove"] = max($obblighi["$chi|$dove"] ?? 0, 64);
    }
}
$sciolti = 0;
foreach (SCIOGLIMENTI as [$chi, $altri, $perche]) {
    foreach ($altri as $altro) {
        foreach (["$chi|$altro", "$altro|$chi"] as $k) {
            if (isset($obblighi[$k])) {
                unset($obblighi[$k], $patti[$k]);
                $sciolti++;
            }
        }
    }
}
ksort($obblighi);
ksort($patti);

// --- uscita -------------------------------------------------------------------
$conteggio = array_count_values($obblighi);
ksort($conteggio);
$out = "<?php\n\ndeclare(strict_types=1);\n\n"
     . "/**\n"
     . " * Obblighi di trattato fra Stati, sulla scala 0/16/32/64/96/128.\n"
     . " *\n"
     . " * GENERATO DA bin/importa_alleanze.php — non si modifica a mano.\n"
     . " *\n"
     . " * Fonte: ATOP v5.1 (Leeds et al.), alleanze in vigore nel " . ANNO . ", membro\n"
     . " * per membro. Dopo il " . ANNO . ", aggiunti a mano con la data: Russia-Corea\n"
     . " * del Nord 2024, Arabia Saudita-Pakistan 2025, Turchia-Azerbaigian 2021,\n"
     . " * Alleanza degli Stati del Sahel 2023; Macedonia del Nord, Finlandia e Svezia\n"
     . " * nella NATO. Tolti: l'Ucraina e la Georgia dalla CSI, l'Armenia dalla CSTO,\n"
     . " * Algeria-Marocco, Ruanda-Congo, il Sahel dall'ECOWAS; e la Carta della CSI\n"
     . " * del 1993, che ATOP codifica come patto di difesa.\n"
     . " *\n"
     . " * Il gradino 128 (difesa nucleare) NON sta qui: lo assegna Mondo, che sa\n"
     . " * quali Stati hanno l'atomica. Le chiavi sono direzionate «A|B»: l'obbligo\n"
     . " * di A verso B, che puo' essere asimmetrico (gli Stati Uniti difendono il\n"
     . " * Giappone, il Giappone non difende gli Stati Uniti).\n"
     . " */\n\nreturn [\n";
foreach ($obblighi as $k => $v) {
    $out .= sprintf("    '%s' => %d,\n", $k, $v);
}
$out .= "];\n";
file_put_contents($radice . '/db/seed/alleanze.php', $out);

// I patti di difesa, bilaterali o fra molti: li legge bin/importa_affinita.php.
$out = "<?php\n\ndeclare(strict_types=1);\n\n"
     . "// Generato da bin/importa_alleanze.php: per ogni obbligo di difesa «A|B»,\n"
     . "// se viene da un patto bilaterale, da uno fra molti con forze integrate\n"
     . "// (NATO, CSTO) o da uno fra molti sulla carta (ATOP v5.1, " . ANNO . ",\n"
     . "// piu' i patti posteriori). Lo legge bin/importa_affinita.php.\n\nreturn [\n";
foreach ($patti as $k => $v) {
    $out .= sprintf("    '%s' => '%s',\n", $k, $v);
}
$out .= "];\n";
file_put_contents($radice . '/db/seed/patti-difesa.php', $out);

printf("Scritte %d coppie in db/seed/alleanze.php (ATOP %d); %d scioglimenti.\n", count($obblighi), ANNO, $sciolti);
foreach ($conteggio as $g => $n) {
    printf("  gradino %3d: %5d coppie\n", $g, $n);
}
$quanti = array_count_values(array_map(static fn(string $t): string => explode(':', $t)[0], $patti));
printf("  difesa bilaterale: %d, in un patto integrato: %d, fra molti sulla carta: %d\n",
    $quanti['bilaterale'] ?? 0, $quanti['integrato'] ?? 0, $quanti['multilaterale'] ?? 0);
if ($saltati !== []) {
    printf("  membri senza paese nel nostro mondo: %s\n", implode(', ', array_keys($saltati)));
}
