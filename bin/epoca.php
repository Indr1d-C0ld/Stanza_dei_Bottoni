<?php

declare(strict_types=1);

/**
 * Apre e chiude le epoche — il mestiere dell'arbitro.
 *
 * Un'epoca si chiude anche da sola, quando finisce il suo tempo: la fase 11 ci
 * pensa a ogni tick. Questo file serve per aprirne una, per chiuderne una in
 * anticipo, e per guardare dove siamo.
 *
 *   php bin/epoca.php                          a che punto siamo
 *   php bin/epoca.php apri "Il lungo inverno" 520
 *   php bin/epoca.php chiudi
 */

require __DIR__ . '/_avvio.php';

use App\Gioco\Epoca;
use App\Nucleo\Basedati;
use App\Nucleo\Calendario;
use App\Nucleo\Configurazione;

$db = new Basedati((array) Configurazione::leggi('db', []));
$epoca = new Epoca($db);
$tick = (int) $db->esegui('SELECT COALESCE(MAX(tick), 0) FROM sdb_mondo_stato')->fetchColumn();

$comando = $argv[1] ?? 'stato';

if ($comando === 'apri') {
    $titolo = (string) ($argv[2] ?? 'Senza nome');
    $durata = (int) ($argv[3] ?? 520);
    [$ok, $m] = $epoca->apri($titolo, $tick, $durata);
    echo "  $m\n";
    exit($ok ? 0 : 1);
}

if ($comando === 'chiudi') {
    [$ok, $m] = $epoca->chiudi($tick);
    echo "  $m\n";
    if ($ok) {
        $e = $db->esegui('SELECT id FROM sdb_epoca WHERE stato = "chiusa" ORDER BY numero DESC LIMIT 1')
            ->fetchColumn();
        echo "\n  IL BILANCIO\n";
        foreach ($epoca->classifica((int) $e) as $i => $r) {
            printf("   %d. %-16s %-10s %-14s %+8.2f\n",
                $i + 1, $r['giocatore'], $r['ruolo'], $r['nazione'], (float) $r['totale']);
            foreach ((array) json_decode((string) $r['voci'], true) as $voce => $v) {
                if (abs((float) $v) >= 0.005) {
                    printf("        %-34s %+8.2f\n", $voce, (float) $v);
                }
            }
        }
        echo "\n  QUEL CHE NESSUNO SAPEVA\n";
        foreach ($epoca->rivelazioni((int) $e) as $genere => $righe) {
            printf("   -- %s (%d)\n", $genere, count($righe));
            foreach (array_slice($righe, 0, 6) as $r) {
                printf("      t%-4d %s\n", (int) $r['tick'], $r['titolo']);
            }
            if (count($righe) > 6) {
                printf("      … e altre %d\n", count($righe) - 6);
            }
        }
    }
    exit($ok ? 0 : 1);
}

$e = $epoca->corrente();
if ($e === null) {
    echo "  Nessuna epoca in corso. Aprine una con: php bin/epoca.php apri \"Titolo\" 520\n";
} else {
    printf("  Epoca %d — «%s»\n  aperta il %s (tick %d), dura %d giri, ne restano %d\n"
        . "  oggi nel mondo e' il %s\n",
        (int) $e['numero'], $e['titolo'],
        Calendario::tick((int) $e['inizio_tick']), (int) $e['inizio_tick'],
        (int) $e['durata_tick'], (int) $epoca->restano($tick), Calendario::tick($tick));
}
foreach ($epoca->chiuse() as $c) {
    printf("  Epoca %d «%s» chiusa il %s\n", (int) $c['numero'], $c['titolo'],
        Calendario::tick((int) $c['fine_tick']));
}
