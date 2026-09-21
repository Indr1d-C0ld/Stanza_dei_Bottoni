<?php

declare(strict_types=1);

/**
 * Il generatore deterministico.
 *
 * Queste prove esistono per un errore vero: frazione() tornava valori in
 * [0, 0.5) invece che in [0, 1), e quindi rumore() era sempre negativo. Ogni
 * scossa casuale del mondo spingeva all'ingiu', e ci sono voluti giorni per
 * accorgersene — perche' un mondo che peggiora piano sembra plausibile.
 */

use App\Nucleo\Caso;

$caso = new Caso(20260921);

Prove::gruppo('Il caso: la frazione copre tutto l\'intervallo');

$valori = [];
for ($i = 0; $i < 20000; $i++) {
    $valori[] = $caso->frazione('prova', $i, 1);
}
$min = min($valori);
$max = max($valori);
$media = array_sum($valori) / count($valori);

Prove::che('nessun valore sotto zero', $min >= 0.0, sprintf('minimo %.6f', $min));
Prove::che('nessun valore da uno in su', $max < 1.0, sprintf('massimo %.6f', $max));
Prove::che('arriva davvero oltre la meta\'', $max > 0.99, sprintf('massimo %.6f', $max));
Prove::vicino('la media sta a meta\'', 0.5, $media, 0.02);

Prove::gruppo('Il caso: il rumore e\' centrato sullo zero');

$rumori = [];
for ($i = 0; $i < 20000; $i++) {
    $rumori[] = $caso->rumore('prova', $i, 1, 1.0);
}
$mediaR = array_sum($rumori) / count($rumori);
$positivi = count(array_filter($rumori, static fn($x) => $x > 0));

Prove::vicino('media vicina a zero', 0.0, $mediaR, 0.03);
Prove::fra('circa meta\' dei valori sono positivi', 0.47, 0.53, $positivi / count($rumori));
Prove::che('esistono valori negativi', min($rumori) < -0.5);
Prove::che('esistono valori positivi', max($rumori) > 0.5);

Prove::gruppo('Il caso: lo stesso seme da\' lo stesso mondo');

$a = new Caso(999);
$b = new Caso(999);
$c = new Caso(1000);
Prove::uguale('due generatori con lo stesso seme concordano',
    $a->frazione('x', 5, 3), $b->frazione('x', 5, 3));
Prove::che('semi diversi danno valori diversi',
    $a->frazione('x', 5, 3) !== $c->frazione('x', 5, 3));
Prove::che('la prova di probabilita\' 0 non scatta mai',
    !$a->prova('x', 1, 1, 0.0));
Prove::che('la prova di probabilita\' 1 scatta sempre',
    $a->prova('x', 1, 1, 1.0));
