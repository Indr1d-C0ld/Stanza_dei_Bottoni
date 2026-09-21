<?php

declare(strict_types=1);

/**
 * Applica le migrazioni non ancora applicate, e tiene il conto.
 *
 * Fino a ieri le migrazioni si applicavano a mano, una query alla volta,
 * copiando e incollando. E' andata bene finche' e' andata bene: alla 0011 un
 * pezzo di file e' stato saltato perche' cominciava con delle righe di
 * commento, le colonne nuove non sono mai nate, e la pagina dei messaggi
 * rispondeva 500 senza che il motivo fosse evidente.
 *
 * Questo file esiste perche' quel modo di sbagliare non torni. Tiene il conto
 * in sdb_migrazione: quel che risulta applicato non si riapplica, il resto
 * parte in ordine, e se una query fallisce si ferma li' e lo dice.
 *
 *   php bin/migra.php            applica quel che manca
 *   php bin/migra.php --stato    dice soltanto a che punto siamo
 *   php bin/migra.php --segna    segna tutto come applicato senza eseguire
 *
 * L'ultimo serve a una cosa sola: l'installazione da zero, dove lo script di
 * deploy ha gia' passato tutti i file al client mariadb. Li' il registro va
 * riempito, non rieseguito.
 */

require __DIR__ . '/_avvio.php';

use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;

$soloStato = in_array('--stato', $argv, true);
$soloSegna = in_array('--segna', $argv, true);

$db = new Basedati((array) Configurazione::leggi('db', []));
$db->esegui(
    'CREATE TABLE IF NOT EXISTS sdb_migrazione (
        nome      VARCHAR(190) NOT NULL PRIMARY KEY,
        applicata DATETIME NOT NULL,
        query     SMALLINT UNSIGNED NOT NULL
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$fatte = $db->esegui('SELECT nome FROM sdb_migrazione')->fetchAll(PDO::FETCH_COLUMN);
$fatte = array_flip($fatte);

$file = glob(dirname(__DIR__) . '/db/migrations/*.sql') ?: [];
sort($file);

$nuove = 0;
foreach ($file as $percorso) {
    $nome = basename($percorso);
    if (isset($fatte[$nome])) {
        echo "  · $nome  già applicata\n";
        continue;
    }
    if ($soloStato) {
        echo "  ! $nome  DA APPLICARE\n";
        $nuove++;
        continue;
    }
    if ($soloSegna) {
        $db->esegui('INSERT INTO sdb_migrazione (nome, applicata, query) VALUES (?, NOW(), 0)', [$nome]);
        echo "  = $nome  segnata (non eseguita)\n";
        $nuove++;
        continue;
    }

    // Prima si tolgono le righe di commento, poi si divide: il contrario
    // sarebbe l'errore che ha reso necessario questo programma.
    $sql = (string) file_get_contents($percorso);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $query = array_values(array_filter(array_map('trim', explode(';', $sql)), fn($q) => $q !== ''));

    $n = 0;
    foreach ($query as $q) {
        try {
            $db->esegui($q);
            $n++;
        } catch (PDOException $e) {
            fwrite(STDERR, "\n  ✗ $nome — query " . ($n + 1) . " fallita:\n    "
                . $e->getMessage() . "\n\n    " . substr($q, 0, 200) . "\n");
            exit(1);
        }
    }
    $db->esegui('INSERT INTO sdb_migrazione (nome, applicata, query) VALUES (?, NOW(), ?)', [$nome, $n]);
    echo "  ✓ $nome  applicata ($n query)\n";
    $nuove++;
}

if ($soloStato) {
    echo $nuove === 0 ? "\nTutto applicato.\n" : "\n$nuove migrazioni da applicare.\n";
} elseif ($soloSegna) {
    echo "\n$nuove migrazioni segnate come applicate.\n";
} else {
    echo $nuove === 0 ? "\nNiente da fare.\n" : "\n$nuove migrazioni applicate.\n";
}
