<?php

declare(strict_types=1);

/**
 * Svuota un po' di coda della posta.
 *
 * Esiste perche' un invio puo' fallire e va ritentato: senza questo, un
 * messaggio di verifica perso lascerebbe qualcuno fuori dal gioco per sempre.
 * Va nel cron accanto al tick, ma piu' spesso — le attese fra un tentativo e
 * l'altro partono da un minuto.
 *
 *   php bin/posta.php              smista quel che tocca
 *   php bin/posta.php --stato      dice soltanto come va
 *   php bin/posta.php --pota       butta i messaggi vecchi di un mese
 */

require __DIR__ . '/_avvio.php';

use App\Nucleo\Basedati;
use App\Nucleo\Configurazione;
use App\Nucleo\Posta;

$db    = new Basedati((array) Configurazione::leggi('db', []));
$posta = new Posta($db);

if (in_array('--stato', $argv, true)) {
    $s = $posta->stato();
    printf("  in coda %d · inviate nelle 24 ore %d/%d · rinunciate %d\n",
        $s['in_coda'], $s['inviate_24h'], $s['tetto'], $s['rinunciate']);
    foreach ($posta->ultime(10) as $m) {
        printf("   #%-5d %-30s %-14s %s%s\n", $m['id'],
            mb_substr((string) $m['destinatario'], 0, 30), $m['genere'],
            $m['inviato_il'] ? 'inviato' : ($m['rinunciato_il'] ? 'RINUNCIATO' : 'in attesa'),
            $m['ultimo_errore'] ? ' — ' . $m['ultimo_errore'] : '');
    }
    exit(0);
}

if (in_array('--pota', $argv, true)) {
    printf("  potati %d messaggi vecchi\n", $posta->pota(30));
    exit(0);
}

$e = $posta->smista();
if ($e['tentati'] > 0) {
    printf("%s  posta: %d tentati, %d inviati, %d rinunciati\n",
        date('Y-m-d H:i:s'), $e['tentati'], $e['inviati'], $e['rinunciati']);
}
