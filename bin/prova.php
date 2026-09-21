<?php

declare(strict_types=1);

/**
 * Le prove automatiche.
 *
 *   php bin/prova.php            tutte
 *   php bin/prova.php 02         solo quelle che cominciano per 02
 *   php bin/prova.php --veloci   salta quelle lente (il mondo a vuoto)
 *
 * Le prove che toccano il database girano dentro una transazione annullata: il
 * mondo vivo non deve accorgersi che sono passate. Quelle che toccano la posta
 * usano un trasporto finto, perche' la configurazione vera manda posta davvero.
 */

require __DIR__ . '/_avvio.php';
require dirname(__DIR__) . '/tests/prove.php';

$filtro  = null;
$veloci  = false;
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--veloci') {
        $veloci = true;
    } elseif (!str_starts_with($a, '--')) {
        $filtro = $a;
    }
}

$lente = ['04-taratura-e-leve.php'];

$file = glob(dirname(__DIR__) . '/tests/[0-9]*.php') ?: [];
sort($file);

printf("Stanza dei Bottoni — prove\n");
$avvio = hrtime(true);
$saltate = 0;

foreach ($file as $percorso) {
    $nome = basename($percorso);
    if ($filtro !== null && !str_starts_with($nome, $filtro)) {
        continue;
    }
    if ($veloci && in_array($nome, $lente, true)) {
        printf("\n  %s — saltata (lenta)\n", $nome);
        $saltate++;
        continue;
    }
    require $percorso;
}

$secondi = (hrtime(true) - $avvio) / 1e9;
printf("  in %.1f s%s\n", $secondi, $saltate > 0 ? sprintf(', %d saltate', $saltate) : '');

exit(Prove::esito());
