<?php

declare(strict_types=1);

/**
 * Importa le condizioni iniziali dal World Factbook e produce db/seed/nazioni.csv.
 *
 * REGOLA (docs/05-dati-scenari.md): non ridistribuiamo mai i dataset originali.
 * Da qui esce solo il DERIVATO — i campi che ci servono e gli indici che
 * calcoliamo noi. Il clone del Factbook resta in storage/, fuori da git.
 *
 * Prerequisiti:
 *   git clone --depth 1 https://github.com/factbook/factbook.json storage/factbook
 *   curl -sL -o storage/codici-paese.csv \
 *     https://raw.githubusercontent.com/datasets/country-codes/master/data/country-codes.csv
 *
 * Uso:  php bin/importa_factbook.php [--verboso]
 */

$radice = require __DIR__ . '/_avvio.php';

$verboso  = in_array('--verboso', $argv, true);
$sorgente = $radice . '/storage/factbook';
$codici   = $radice . '/storage/codici-paese.csv';
$uscita   = $radice . '/db/seed/nazioni.csv';
$nomiIta  = require $radice . '/db/seed/nomi-italiani.php';

// Le potenze giocabili: il criterio non e' la potenza, e' che ognuna abbia un
// problema diverso da risolvere (docs/05-dati-scenari.md).
$giocabili = ['USA', 'CHN', 'RUS', 'IND', 'FRA', 'DEU', 'GBR', 'JPN',
              'TUR', 'IRN', 'ISR', 'SAU', 'BRA', 'IDN'];

foreach ([$sorgente, $codici] as $richiesto) {
    if (!file_exists($richiesto)) {
        fwrite(STDERR, "Manca: $richiesto\nVedi le istruzioni in testa a questo file.\n");
        exit(1);
    }
}

// ---------------------------------------------------------------- utilità ---

/** Prende il testo dell'annata più recente da un blocco "Campo ANNO". */
function piuRecente(?array $blocco): ?string
{
    if ($blocco === null) {
        return null;
    }
    if (isset($blocco['text'])) {
        return (string) $blocco['text'];
    }
    $migliore = null;
    $annoMigliore = -1;
    foreach ($blocco as $chiave => $valore) {
        if (!is_array($valore) || !isset($valore['text'])) {
            continue;
        }
        if (preg_match('/(\d{4})\s*$/', (string) $chiave, $m)) {
            $anno = (int) $m[1];
            if ($anno > $annoMigliore) {
                $annoMigliore = $anno;
                $migliore = (string) $valore['text'];
            }
        } elseif ($migliore === null) {
            $migliore = (string) $valore['text'];
        }
    }
    return $migliore;
}

/** "$3.133 trillion (2024 est.)" -> 3133000 (milioni). */
function importoInMilioni(?string $testo): ?float
{
    if ($testo === null || !preg_match('/\$?\s*([\d.,]+)\s*(trillion|billion|million)?/i', $testo, $m)) {
        return null;
    }
    $numero = (float) str_replace(',', '', $m[1]);
    return match (strtolower($m[2] ?? '')) {
        'trillion' => $numero * 1_000_000,
        'billion'  => $numero * 1_000,
        'million'  => $numero,
        default    => $numero / 1_000_000,
    };
}

/** "60,924,851 (2025 est.)" -> 60924851. */
function primoIntero(?string $testo): ?int
{
    if ($testo === null || !preg_match('/([\d][\d,\.]*)/', $testo, $m)) {
        return null;
    }
    $pulito = str_replace(',', '', $m[1]);
    return (int) round((float) $pulito);
}

/**
 * "approximately 1.28 million active duty" -> 1280000
 * "estimated 1.1-1.2 million active"       -> 1150000  (punto medio)
 * "approximately 185,000 active-duty"      -> 185000
 */
function forzaMilitare(?string $testo): ?int
{
    if ($testo === null) {
        return null;
    }
    // Il dettaglio fra parentesi e' la ripartizione per arma: va tolto, o si
    // finisce per scambiare un pezzo per il totale. Via anche l'anno finale.
    $t = preg_replace('/\([^)]*\)/', ' ', $testo) ?? $testo;

    // "1.1-1.2 million" -> punto medio. Attenzione: i due estremi possono avere
    // scale diverse ("850,000-1 million"), quindi si normalizzano separatamente.
    if (preg_match('/([\d.,]+)\s*-\s*([\d.,]+)\s*(million|thousand)/i', $t, $m)) {
        $scala = strtolower($m[3]) === 'million' ? 1_000_000 : 1_000;
        $normalizza = static fn(string $x): float
            => ((float) str_replace(',', '', $x)) >= 1_000
                ? (float) str_replace(',', '', $x)
                : (float) str_replace(',', '', $x) * $scala;
        return (int) round(($normalizza($m[1]) + $normalizza($m[2])) / 2);
    }
    // "35-40,000 active" -> e' il secondo numero a portare l'ordine di grandezza
    if (preg_match('/[\d.,]+\s*-\s*([\d][\d,]{2,})/', $t, $m)) {
        return (int) round((float) str_replace(',', '', $m[1]));
    }
    // "approximately 2 million active-duty"
    if (preg_match('/([\d.,]+)\s*(million|thousand)/i', $t, $m)) {
        return (int) round((float) str_replace(',', '', $m[1])
            * (strtolower($m[2]) === 'million' ? 1_000_000 : 1_000));
    }
    // altrimenti la prima quantita' plausibile: nessun esercito di questi testi
    // conta meno di duecento persone, e nessuno piu' di cento milioni.
    if (preg_match_all('/([\d][\d,\.]*)/', $t, $tutte)) {
        foreach ($tutte[1] as $candidato) {
            $valore = (float) str_replace(',', '', $candidato);
            if ($valore >= 200 && $valore < 100_000_000) {
                return (int) round($valore);
            }
        }
    }
    return null;
}

/**
 * Media delle annate disponibili in un blocco "Campo ANNO".
 *
 * La stima dell'ultimo anno e' rumorosa e tende all'ottimismo, specie per i
 * paesi in via di sviluppo. Il Factbook ne pubblica tre: usarle tutte costa
 * nulla e toglie di mezzo un bel po' di varianza spuria.
 */
function mediaPercentuale(?array $blocco): ?float
{
    if ($blocco === null) {
        return null;
    }
    $valori = [];
    foreach ($blocco as $chiave => $valore) {
        if (!is_array($valore) || !isset($valore['text']) || !preg_match('/\d{4}\s*$/', (string) $chiave)) {
            continue;
        }
        $p = percentuale((string) $valore['text']);
        if ($p !== null) {
            $valori[] = $p;
        }
    }
    return $valori === [] ? null : array_sum($valori) / count($valori);
}

/** "0.7% (2024 est.)" -> 0.007 ; "2% of GDP" -> 0.02. */
function percentuale(?string $testo): ?float
{
    if ($testo === null || !preg_match('/(-?[\d.]+)\s*%/', $testo, $m)) {
        return null;
    }
    return ((float) $m[1]) / 100.0;
}

/**
 * Tipo di governo del Factbook -> nostro codice di ideologia FORMALE.
 *
 * Attenzione: e' la descrizione giuridica che ogni Stato da' di se stesso, non
 * una misura di come e' governato davvero. Serve per il colore, non per il
 * modello: il comportamento dipendera' da maturita', etica e ambizione.
 * Le ideologie determinano etica, ambizione e attitudini reciproche
 * (CyberJudas, glossario delle ideologie), qui aggiornate al presente.
 */
function ideologiaDa(string $tipo): string
{
    $t = mb_strtolower($tipo);
    return match (true) {
        str_contains($t, 'communist')                              => 'comunismo',
        str_contains($t, 'theocra')                                => 'teocrazia',
        str_contains($t, 'absolute monarchy')                      => 'monarchia_assoluta',
        str_contains($t, 'junta') || str_contains($t, 'military')  => 'giunta_militare',
        str_contains($t, 'transition') || str_contains($t, 'interim') => 'stato_in_transizione',
        str_contains($t, 'dictatorship') || str_contains($t, 'authoritarian') => 'autoritarismo',
        str_contains($t, 'islamic')                                => 'islam_politico',
        str_contains($t, 'one-party') || str_contains($t, 'single-party') => 'partito_unico',
        str_contains($t, 'federal') && str_contains($t, 'republic') => 'democrazia_federale',
        str_contains($t, 'parliamentary') || str_contains($t, 'presidential')
            || str_contains($t, 'democracy') || str_contains($t, 'republic') => 'democrazia_liberale',
        str_contains($t, 'monarchy')                               => 'monarchia_costituzionale',
        default                                                    => 'indeterminato',
    };
}

/** [SEGNAPOSTO] Ripiego per l'alfabetizzazione, dal reddito pro capite. */
function alfabetizzazioneStimata(int $proCapite): float
{
    return match (true) {
        $proCapite >= 30_000 => 0.99,
        $proCapite >= 15_000 => 0.96,
        $proCapite >=  6_000 => 0.90,
        $proCapite >=  2_000 => 0.75,
        default              => 0.60,
    };
}

/**
 * [FABBRICATO] Valore Strategico 0..100 — "il valore geografico strategico di un
 * paese basato sulle sue riserve di petrolio, minerali strategici e vie d'acqua"
 * (glossario di Shadow President). La pesatura è nostra e va in calibrazione.
 */
function valoreStrategico(?string $risorse, ?float $pil): int
{
    $pesi = [
        'petroleum' => 22, 'crude oil' => 22, 'oil' => 12, 'natural gas' => 16,
        'uranium' => 14, 'rare earth' => 16, 'lithium' => 14, 'cobalt' => 12,
        'copper' => 6, 'iron ore' => 5, 'coal' => 4, 'gold' => 4, 'diamonds' => 4,
        'phosphate' => 4, 'bauxite' => 4, 'nickel' => 5, 'tin' => 3, 'timber' => 2,
        'arable land' => 2, 'fish' => 1, 'hydropower' => 3,
    ];
    $punteggio = 0;
    $t = mb_strtolower((string) $risorse);
    foreach ($pesi as $chiave => $peso) {
        if ($t !== '' && str_contains($t, $chiave)) {
            $punteggio += $peso;
        }
    }
    // Un'economia grande è di per sé un asset strategico.
    if ($pil !== null) {
        $punteggio += (int) min(25, sqrt(max(0.0, $pil)) / 60);
    }
    return (int) max(0, min(100, $punteggio));
}

// ------------------------------------------------------------ corrispondenze

$gecAIso = [];
$indipendenza = [];
$fh = fopen($codici, 'r');
$intestazione = fgetcsv($fh, 0, ',', '"', '\\');
$iFips = array_search('FIPS', $intestazione, true);
$iIso3 = array_search('ISO3166-1-Alpha-3', $intestazione, true);
$iIndip = array_search('is_independent', $intestazione, true);
while (($riga = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $fips = trim((string) ($riga[$iFips] ?? ''));
    $iso3 = trim((string) ($riga[$iIso3] ?? ''));
    if ($iso3 === '') {
        continue;
    }
    $indipendenza[$iso3] = trim((string) ($riga[$iIndip] ?? ''));
    if ($fips === '') {
        continue;
    }
    // Alcune righe accorpano piu' codici GEC: "RIKV" = Serbia + Kosovo,
    // "GZWE" = Gaza + Cisgiordania. Si spezzano in coppie di due lettere.
    $indipendente = trim((string) ($riga[$iIndip] ?? '')) === 'Yes';
    foreach (str_split(mb_strtolower($fips), 2) as $pezzo) {
        if (strlen($pezzo) !== 2) {
            continue;
        }
        // Un codice GEC puo' essere rivendicato da piu' righe: "NL" e' sia i
        // Paesi Bassi sia i Caraibi olandesi, e in ordine alfabetico vincono i
        // secondi. Fra due pretendenti vince lo Stato sovrano, sempre.
        $giaPresente = $gecAIso[$pezzo] ?? null;
        if ($giaPresente === null
            || ($indipendente && ($indipendenza[$giaPresente] ?? '') !== 'Yes')) {
            $gecAIso[$pezzo] = $iso3;
        }
    }
}
fclose($fh);

// Correzioni dichiarate: entita' che gli elenchi ISO non trattano come Stati
// ma che la simulazione deve avere.
$gecAIso['kv'] = 'XKX';                 // Kosovo
$indipendenza['XKX'] = 'Yes';
$indipendenza['TWN'] = 'Yes';           // Taiwan

$regioni = [
    'africa' => 'AFR', 'antarctica' => 'ANT', 'australia-oceania' => 'OCE',
    'central-america-n-caribbean' => 'CAM', 'central-asia' => 'ASC',
    'east-n-southeast-asia' => 'ASE', 'europe' => 'EUR', 'middle-east' => 'MEO',
    'north-america' => 'NAM', 'oceans' => 'OCN', 'south-america' => 'SAM',
    'south-asia' => 'ASS',
];

// Entità che non sono Stati e che non vogliamo in simulazione:
// Unione Europea, territori disabitati, aree contese senza governo proprio.
$escluse = ['ee', 'ay', 'fq', 'hm', 'bv', 'ip', 'jn', 'wq', 'um', 'xq', 'xo',
            'zh', 'oo', 'zn', 'xx', 'gz', 'we'];

// --------------------------------------------------------------- estrazione

$nazioni = [];
$scartate = [];
$dipendenze = [];
$senzaCodice = [];

foreach ($regioni as $cartella => $siglaRegione) {
    if ($cartella === 'oceans' || $cartella === 'antarctica') {
        continue;
    }
    foreach (glob($sorgente . '/' . $cartella . '/*.json') ?: [] as $percorso) {
        $gec = basename($percorso, '.json');
        if (in_array($gec, $escluse, true)) {
            continue;
        }
        $d = json_decode((string) file_get_contents($percorso), true);
        if (!is_array($d)) {
            continue;
        }

        $nome = $d['Government']['Country name']['conventional short form']['text']
            ?? $d['Government']['Country name']['conventional long form']['text']
            ?? null;
        if ($nome === null || $nome === 'none') {
            $nome = ucfirst($gec);
        }

        $popolazione = primoIntero(piuRecente($d['People and Society']['Population'] ?? null)
            ?? ($d['People and Society']['Population']['total']['text'] ?? null));
        $pil         = importoInMilioni(piuRecente($d['Economy']['Real GDP (purchasing power parity)'] ?? null));

        // Senza popolazione o PIL non è un soggetto simulabile: dipendenze,
        // territori e scogli restano fuori.
        if ($popolazione === null || $popolazione < 50_000 || $pil === null) {
            $scartate[] = "$gec ($nome)";
            continue;
        }

        $tipoGoverno = (string) ($d['Government']['Government type']['text'] ?? '');
        $risorse     = $d['Geography']['Natural resources']['text'] ?? null;
        $quotaMil    = percentuale(piuRecente($d['Military and Security']['Military expenditures'] ?? null));
        $soldati     = forzaMilitare($d['Military and Security']['Military and security service personnel strengths']['text'] ?? null);

        $iso3 = $gecAIso[$gec] ?? null;
        if ($iso3 === null) {
            $senzaCodice[] = "$gec ($nome)";
            continue;
        }
        // Solo Stati sovrani: territori e dipendenze restano fuori dalla
        // simulazione (ma non dal mondo: sono luoghi, non attori).
        if (($indipendenza[$iso3] ?? '') !== 'Yes') {
            $dipendenze[] = $nome;
            continue;
        }

        $nazioni[] = [
            'gec'             => $gec,
            'iso3'            => $iso3,
            'nome'            => $nomiIta[$iso3] ?? html_entity_decode($nome, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'nome_fonte'      => html_entity_decode($nome, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'regione'         => $siglaRegione,
            'giocabile'       => in_array($iso3, $giocabili, true) ? 1 : 0,
            'popolazione'     => $popolazione,
            'area_km2'        => primoIntero($d['Geography']['Area']['total ']['text']
                                 ?? $d['Geography']['Area']['total']['text'] ?? null) ?? 0,
            'pil_milioni'     => round($pil, 2),
            'pil_pro_capite'  => primoIntero(piuRecente($d['Economy']['Real GDP per capita'] ?? null)) ?? 0,
            'crescita_pil'    => mediaPercentuale($d['Economy']['Real GDP growth rate'] ?? null)
                                 ?? percentuale(piuRecente($d['Economy']['Real GDP growth rate'] ?? null)) ?? 0.0,
            'crescita_pop'    => percentuale($d['People and Society']['Population growth rate']['text'] ?? null) ?? 0.004,
            // Il Factbook omette l'alfabetizzazione dove la considera scontata
            // (quasi tutti i paesi ad alto reddito). Il ripiego e' dichiarato.
            'alfabetizzazione'=> percentuale($d['People and Society']['Literacy']['total population']['text'] ?? null)
                                 ?? alfabetizzazioneStimata(primoIntero(piuRecente($d['Economy']['Real GDP per capita'] ?? null)) ?? 0),
            'alfab_stimata'   => isset($d['People and Society']['Literacy']['total population']['text']) ? 0 : 1,
            'quota_militare'  => $quotaMil ?? 0.015,
            // Quando la fonte tace del tutto: 0,25% della popolazione, che e'
            // l'ordine di grandezza medio mondiale. Dichiarato come stima.
            'soldati'         => $soldati ?? (int) round($popolazione * 0.0025),
            'soldati_stimati' => $soldati === null ? 1 : 0,
            'ideologia_formale' => ideologiaDa($tipoGoverno),
            'valore_strategico' => valoreStrategico($risorse, $pil),
        ];
    }
}

usort($nazioni, static fn(array $a, array $b): int => $b['pil_milioni'] <=> $a['pil_milioni']);

// [FABBRICATO] Valore di Prestigio 1..2000 — quanto la sorte di un paese conta
// nell'ordine geopolitico. Crawford dava 200 alla Germania Ovest e 2 al
// Nicaragua: la scala è quella. Qui: peso economico e demografico, compressi.
$pilTotale = array_sum(array_column($nazioni, 'pil_milioni')) ?: 1.0;
$popTotale = array_sum(array_column($nazioni, 'popolazione')) ?: 1;
foreach ($nazioni as &$n) {
    $quotaPil = $n['pil_milioni'] / $pilTotale;
    $quotaPop = $n['popolazione'] / $popTotale;
    $n['valore_prestigio'] = (int) max(1, min(2000, round(2000 * (0.75 * $quotaPil + 0.25 * $quotaPop) ** 0.55)));
    // [SEGNAPOSTO] Maturità istituzionale (lo "stato di diritto" di Crawford,
    // che confessa di aver inventato i suoi valori: USA 240, Cina 100, Mali 24).
    // DA SOSTITUIRE con V-Dem. Nel frattempo NON la deriviamo dal nome formale
    // del regime — il Factbook chiama "semi-presidential federation" la Russia e
    // "federal parliamentary republic" l'India: e' una descrizione giuridica, non
    // una misura. Meglio due dati reali: reddito e alfabetizzazione.
    $reddito = max(500, (int) $n['pil_pro_capite']);
    $n['maturita'] = (int) max(10, min(255, round(
        40 + 150 * (log10($reddito) - 2.7) / 2.0 + 60 * ($n['alfabetizzazione'] - 0.6)
    )));
}
unset($n);

// ------------------------------------------------------------------ uscita

@mkdir(dirname($uscita), 0775, true);
$out = fopen($uscita, 'w');
fputcsv($out, array_keys($nazioni[0]), ',', '"', '\\');
foreach ($nazioni as $n) {
    fputcsv($out, $n, ',', '"', '\\');
}
fclose($out);

printf("Importate %d nazioni in %s\n", count($nazioni), str_replace($radice . '/', '', $uscita));
printf("Scartate %d entità senza popolazione o PIL%s\n", count($scartate), $verboso ? ':' : '.');
if ($verboso) {
    foreach (array_chunk($scartate, 6) as $blocco) {
        echo '  ' . implode(', ', $blocco) . "\n";
    }
}
printf("Escluse %d dipendenze e territori non sovrani.\n", count($dipendenze));
$senzaNome = array_filter($nazioni, static fn(array $n): bool => !isset($nomiIta[$n['iso3']]));
if ($senzaNome !== []) {
    printf("SENZA NOME ITALIANO (%d): %s\n", count($senzaNome),
        implode(', ', array_map(static fn(array $n): string => $n['iso3'] . ' ' . $n['nome_fonte'], $senzaNome)));
} else {
    echo "Tutte le nazioni hanno un nome italiano.\n";
}
if ($senzaCodice !== []) {
    printf("Senza corrispondenza ISO3 (%d): %s\n", count($senzaCodice), implode(', ', $senzaCodice));
}
if ($verboso && $dipendenze !== []) {
    foreach (array_chunk($dipendenze, 6) as $blocco) {
        echo '  ' . implode(', ', $blocco) . "\n";
    }
}
