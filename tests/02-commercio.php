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
 */

use App\Dati\Commercio;
use App\Dati\Mondo;

$mondo = Mondo::daSeme(dirname(__DIR__) . '/db/seed/nazioni.csv');
$c = $mondo->commercio;
$c->morso = 0.42;
$c->quotaFornitore = 0.45;

$esportatori = static function (string $settore) use ($mondo, $c): array {
    $o = [];
    foreach ($mondo->nazioni as $n) {
        $avanzo = $c->produzione[$n->iso3][$settore] - $c->fabbisogno[$n->iso3][$settore];
        if ($avanzo > 0.0) {
            $o[$n->iso3] = $avanzo;
        }
    }
    arsort($o);
    return $o;
};

Prove::gruppo('Commercio: ogni settore ha degli esportatori');

foreach (Commercio::SETTORI as $s) {
    $e = $esportatori($s);
    Prove::che("$s ha esportatori netti", count($e) >= 5, sprintf('%d trovati', count($e)));
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
Prove::che('la Svizzera e\' fra i primi per finanza',
    in_array('CHE', $primi('finanza'), true), implode(',', $primi('finanza', 6)));
Prove::che('la Francia NON e\' fra i primi dieci per energia',
    !in_array('FRA', $primi('energia', 10), true), implode(',', $primi('energia', 10)));

Prove::gruppo('Commercio: non esportano solo i giganti');

// NOTA: qui non si prova il Belgio, e non per distrazione. Nel modello la
// taglia assoluta decide chi entra nelle classifiche dei fornitori, e un paese
// piccolo resta fuori da quelle dei vicini grandi per quanto sia aperto — il
// Belgio esporta zero, contro l'ottanta per cento del mondo vero. Allargare i
// tagli non lo risolve e annacqua le leve che contano: il limite e' scritto in
// Commercio.php e nel documento sul commercio. Qui si prova quel che il
// modello puo' davvero sostenere.
foreach (['NLD', 'CHE', 'SAU', 'BRA'] as $iso) {
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
$energia = $perSettore('DEU', 'energia');
$manif   = $perSettore('DEU', 'manifattura');

Prove::che('la Germania ha pochi fornitori di energia',
    count($energia) <= 8, sprintf('%d fornitori', count($energia)));
Prove::che('il primo fornitore di energia pesa parecchio',
    ($energia[0] ?? 0) >= 0.10, sprintf('%.1f%%', 100 * ($energia[0] ?? 0)));
Prove::che('la manifattura arriva da piu\' parti',
    count($manif) >= 10, sprintf('%d fornitori', count($manif)));

Prove::gruppo('Commercio: l\'embargo costa a tutti e due, in modo asimmetrico');

$russiaGermania  = $c->dannoAlCliente('RUS', 'DEU');
$russiaSuDiSe    = $c->dannoAlFornitore('RUS', 'DEU');
Prove::che('un embargo russo fa male alla Germania',
    $russiaGermania > 0.01, sprintf('%.2f%%', 100 * $russiaGermania));
Prove::che('e costa qualcosa anche alla Russia',
    $russiaSuDiSe > 0.0, sprintf('%.2f%%', 100 * $russiaSuDiSe));
Prove::che('ma molto meno di quanto costi a chi lo subisce',
    $russiaSuDiSe < $russiaGermania / 2.0);

Prove::che('un embargo verso chi non ci compra niente e\' teatro',
    $c->dannoAlCliente('BOL', 'JPN') < 0.001,
    sprintf('%.4f%%', 100 * $c->dannoAlCliente('BOL', 'JPN')));

Prove::gruppo('Commercio: due produttori dello stesso settore si vendono qualcosa');

foreach ([['FRA', 'DEU'], ['DEU', 'FRA'], ['FRA', 'BEL']] as [$a, $b]) {
    Prove::che("$a vende qualcosa a $b",
        array_sum($c->flusso[$a][$b] ?? []) > 0.0);
}

Prove::gruppo('Commercio: nessun fornitore domina un fabbisogno intero');

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
Prove::che('la dipendenza massima resta sotto il sessanta per cento',
    $peggiore <= 0.60, sprintf('%.1f%% in %s', 100 * $peggiore, $dove));
