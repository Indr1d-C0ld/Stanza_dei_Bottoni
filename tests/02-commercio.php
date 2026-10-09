<?php

declare(strict_types=1);

/**
 * Il grafo commerciale.
 *
 * Quattro di queste prove corrispondono a quattro errori veri, tutti trovati a
 * mano guardando tabelle di numeri:
 *
 *   - l'alfabetizzazione arriva dal seme gia' fra 0 e 1 e veniva divisa per
 *     cento: tecnologia e manifattura non avevano un solo esportatore;
 *   - senza apertura, due paesi che producono entrambi un settore non si
 *     vendevano niente, e un embargo fra loro costava zero a tutti e due;
 *   - con la sola apertura, chiunque vendeva qualunque cosa, e la Francia
 *     esportava petrolio in Germania;
 *   - con una concentrazione uniforme la fornitura energetica si spalmava su
 *     quattordici paesi e la leva del gas spariva.
 *
 * Da ottobre 2026 (docs/35) i totali per settore vengono dalla Banca Mondiale e
 * chi vende a chi dal FMI: le prove guardano le esportazioni vere del grafo, e
 * il mondo che descrivono e' quello del 2023-2024 — la Germania non compra piu'
 * gas russo.
 */

use App\Dati\Commercio;
use App\Dati\Mondo;

$mondo = Mondo::daSeme(dirname(__DIR__) . '/db/seed/nazioni.csv');
$c = $mondo->commercio;
$c->morso = 0.42;
$c->quotaFornitore = 0.45;

// Chi vende all'estero in un settore, e quanto: sui flussi del grafo.
$esportatori = static function (string $settore) use ($c): array {
    $o = [];
    foreach ($c->flusso as $fornitore => $clienti) {
        foreach ($clienti as $settori) {
            if (($settori[$settore] ?? 0.0) > 0.0) {
                $o[$fornitore] = ($o[$fornitore] ?? 0.0) + $settori[$settore];
            }
        }
    }
    arsort($o);
    return $o;
};

Prove::gruppo('Commercio: ogni settore ha degli esportatori');

foreach (Commercio::SETTORI as $s) {
    $e = $esportatori($s);
    Prove::che("$s ha degli esportatori", count($e) >= 5, sprintf('%d trovati', count($e)));
}

Prove::gruppo('Commercio: gli esportatori sono quelli giusti');

$primi = static fn(string $s, int $n = 8): array => array_slice(array_keys($esportatori($s)), 0, $n);

Prove::che('l\'Arabia Saudita e\' fra i primi per energia',
    in_array('SAU', $primi('energia'), true), implode(',', $primi('energia', 5)));
Prove::che('la Russia e\' fra i primi per energia',
    in_array('RUS', $primi('energia'), true));
Prove::che('gli Stati Uniti sono fra i primi per cibo',
    in_array('USA', $primi('cibo'), true));
Prove::che('la Cina e\' prima per manifattura',
    $primi('manifattura', 1) === ['CHN'], implode(',', $primi('manifattura', 3)));
// Coi servizi finanziari della Banca Mondiale la classifica e' quella della
// WTO: Stati Uniti, Regno Unito, Lussemburgo, Singapore, Germania. La Svizzera,
// che la prova pretendeva fra i primi, esporta meno di quanto dica la fama.
Prove::che('il Regno Unito e il Lussemburgo sono fra i primi cinque per finanza',
    array_intersect(['GBR', 'LUX'], $primi('finanza', 5)) === ['GBR', 'LUX'], implode(',', $primi('finanza', 6)));
Prove::che('la Francia NON e\' fra i primi dieci per energia',
    !in_array('FRA', $primi('energia', 10), true), implode(',', $primi('energia', 10)));

Prove::gruppo('Commercio: non esportano solo i giganti');

// Il Belgio prima esportava zero: la taglia assoluta decideva chi entrava fra i
// fornitori dei vicini grandi. Con i totali della Banca Mondiale e i flussi del
// FMI (docs/35) esporta quel che esporta davvero, e la prova c'e'.
foreach (['NLD', 'BEL', 'CHE', 'SVK', 'SAU', 'BRA'] as $iso) {
    $quota = ($c->exportTotale[$iso] ?? 0.0) / max(1.0, $c->pil[$iso] ?? 1.0);
    Prove::che("$iso esporta una parte non nulla del suo PIL",
        $quota > 0.05, sprintf('%.1f%%', 100 * $quota));
}

$esportanoQualcosa = 0;
foreach ($c->exportTotale as $iso => $v) {
    if ($v > 0.0) {
        $esportanoQualcosa++;
    }
}
Prove::che('almeno un terzo dei paesi esporta qualcosa',
    $esportanoQualcosa >= 63, sprintf('%d su 189', $esportanoQualcosa));

Prove::gruppo('Commercio: l\'energia e\' concentrata, la manifattura no');

$perSettore = static function (string $cliente, string $settore) use ($c): array {
    $q = [];
    foreach ($c->fornitoriDi($cliente, 80) as $r) {
        if ($r['settore'] === $settore) {
            $q[] = $r['quota'];
        }
    }
    return $q;
};
// La concentrazione si misura con l'indice di Herfindahl delle quote dei
// fornitori. Prima la prova pretendeva «al piu' otto fornitori di energia» per
// la Germania: coi flussi veri sono una ventina (Norvegia, Stati Uniti, Paesi
// Bassi, Belgio, Regno Unito, Kazakistan...), ed e' giusto. Quel che deve
// restare vero e' che l'energia sia piu' concentrata della manifattura.
$hhi = static function (array $q): float {
    $t = array_sum($q);
    return $t <= 0.0 ? 0.0 : array_sum(array_map(static fn(float $x): float => ($x / $t) ** 2, $q));
};
// Paese per paese non e' una legge: il Giappone compra i manufatti soprattutto
// dalla Cina, e li' la manifattura e' concentrata quanto l'energia. Lo e' in
// media, su tutti i paesi che comprano tutte e due le cose.
$somme = ['energia' => 0.0, 'manifattura' => 0.0];
$quanti = 0;
foreach (array_keys($c->pil) as $chi) {
    $energia = $perSettore($chi, 'energia');
    $manif   = $perSettore($chi, 'manifattura');
    if (count($energia) >= 2 && count($manif) >= 2) {
        $somme['energia'] += $hhi($energia);
        $somme['manifattura'] += $hhi($manif);
        $quanti++;
    }
}
Prove::che("in media l'energia e' piu' concentrata della manifattura",
    $quanti > 50 && $somme['energia'] > $somme['manifattura'],
    sprintf('%.3f contro %.3f su %d paesi', $somme['energia'] / max(1, $quanti), $somme['manifattura'] / max(1, $quanti), $quanti));
Prove::che('e la manifattura tedesca arriva da molte parti',
    count($perSettore('DEU', 'manifattura')) >= 10, sprintf('%d fornitori', count($perSettore('DEU', 'manifattura'))));

Prove::gruppo('Commercio: l\'embargo costa a tutti e due, in modo asimmetrico');

// Era la Germania: coi flussi del 2023-2024, dopo le sanzioni, un embargo russo
// alla Germania costa poco, ed e' il mondo vero. Chi dalla Russia dipende
// davvero e' la Bielorussia.
$russiaBielorussia = $c->dannoAlCliente('RUS', 'BLR');
$russiaSuDiSe      = $c->dannoAlFornitore('RUS', 'BLR');
Prove::che('un embargo russo fa male alla Bielorussia',
    $russiaBielorussia > 0.01, sprintf('%.2f%%', 100 * $russiaBielorussia));
Prove::che('e costa qualcosa anche alla Russia',
    $russiaSuDiSe > 0.0, sprintf('%.2f%%', 100 * $russiaSuDiSe));
Prove::che('ma molto meno di quanto costi a chi lo subisce',
    $russiaSuDiSe < $russiaBielorussia / 2.0);
Prove::che('e alla Bielorussia fa piu\' male di quanto un embargo russo ne faccia ormai alla Germania',
    $russiaBielorussia > 3.0 * $c->dannoAlCliente('RUS', 'DEU'),
    sprintf('%.2f%% contro %.2f%%', 100 * $russiaBielorussia, 100 * $c->dannoAlCliente('RUS', 'DEU')));

Prove::che('un embargo verso chi non ci compra niente e\' teatro',
    $c->dannoAlCliente('BOL', 'JPN') < 0.001,
    sprintf('%.4f%%', 100 * $c->dannoAlCliente('BOL', 'JPN')));

Prove::gruppo('Commercio: due produttori dello stesso settore si vendono qualcosa');

foreach ([['FRA', 'DEU'], ['DEU', 'FRA'], ['FRA', 'BEL']] as [$a, $b]) {
    Prove::che("$a vende qualcosa a $b",
        array_sum($c->flusso[$a][$b] ?? []) > 0.0);
}

Prove::gruppo('Commercio: le dipendenze forti sono quelle vere');

// Fino a docs/35 qui c'era un tetto: nessuna dipendenza oltre il sessanta per
// cento. Era una regola del modello di gravita'. Coi flussi del FMI le
// dipendenze forti esistono e sono note: il Lesotho dal Sudafrica, il Bhutan
// dall'India, il Canada dagli Stati Uniti (docs/36). La dipendenza qui e'
// sul fabbisogno intero, produzione propria compresa, quindi sta sotto la
// quota delle importazioni.
foreach ([['ZAF', 'LSO', 'manifattura'], ['IND', 'BTN', 'manifattura'], ['USA', 'CAN', 'manifattura']] as [$f, $cl, $s]) {
    Prove::che("$cl dipende da $f per la $s piu' che da chiunque altro",
        $c->dipendenza($f, $cl, $s) > 0.0
        && $c->dipendenza($f, $cl, $s) >= max(array_map(static fn(string $altro): float
            => $c->dipendenza($altro, $cl, $s), array_keys($c->flusso))),
        sprintf('%.0f%%', 100 * $c->dipendenza($f, $cl, $s)));
}

$peggiore = 0.0;
$dove = '';
foreach ($c->flusso as $forn => $clienti) {
    foreach ($clienti as $cli => $settori) {
        foreach (array_keys($settori) as $s) {
            $q = $c->dipendenza($forn, $cli, $s);
            if ($q > $peggiore) {
                $peggiore = $q;
                $dove = "{$forn}→{$cli} ({$s})";
            }
        }
    }
}
// Per settore la dipendenza puo' superare la quota sul totale dei beni: la
// Mongolia compra quasi tutto il carburante dalla Russia, l'eSwatini i
// manufatti dal Sudafrica. Ma un fabbisogno intero da uno solo, no.
Prove::che('ma nessun fornitore copre da solo un fabbisogno intero',
    $peggiore < 0.99, sprintf('%.1f%% in %s', 100 * $peggiore, $dove));

Prove::gruppo('Commercio: anche i paesi senza Banca Mondiale hanno i loro dati');

// L'Iran, Cuba, l'Eritrea, la Corea del Nord e la Siria dai conti nazionali
// dell'ONU, con la composizione dei partner dove c'e' (docs/36).
$datiCommercio = require dirname(__DIR__) . '/db/seed/commercio-dati.php';
Prove::uguale('tutti i 189 paesi hanno il commercio dai dati', 189, count($datiCommercio));
Prove::che('l\'Iran esporta soprattutto energia, che il FMI non vede',
    $datiCommercio['IRN']['esporta']['energia'] > 0.5 * array_sum($datiCommercio['IRN']['esporta']));
Prove::che('la Siria non vende piu\' petrolio ma cibo; l\'Eritrea minerali (UN Comtrade)',
    $datiCommercio['SYR']['esporta']['energia'] < 0.01 && $datiCommercio['SYR']['esporta']['cibo'] > $datiCommercio['SYR']['esporta']['manifattura']
    && $datiCommercio['ERI']['esporta']['cibo'] < 0.01);
