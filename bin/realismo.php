<?php

declare(strict_types=1);

/**
 * Misura le grandezze dichiarate dal mondo contro i riferimenti reali.
 *
 * Le fasce e le fonti stanno in App\Simulazione\Realismo, perche' le legge
 * anche la prova automatica: due tabelle che divergono sarebbero peggio di
 * nessuna tabella.
 *
 *   php bin/realismo.php --anni=15
 *   php bin/realismo.php --anni=35 --seme=7
 *
 * Esce con 1 se qualcosa e' fuori fascia: si puo' mettere in una catena.
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Simulazione\EsecutoreTick;
use App\Simulazione\Realismo;

$opz     = getopt('', ['anni::', 'profilo::', 'seme::']);
$anni    = max(1, (int) ($opz['anni'] ?? 15));
$profilo = (string) ($opz['profilo'] ?? 'osservazione');
$seme    = (int) ($opz['seme'] ?? 1);

$cal      = Calibrazione::carica($radice, $profilo);
$tickAnno = (int) $cal->numero('tempo.tick_per_anno', 52.0);
$mondo    = Mondo::daSeme($radice . '/db/seed/nazioni.csv');

printf("Stanza dei Bottoni — realismo delle grandezze\n");
printf("  profilo %s · seme %d · %d anni · %d nazioni\n\n",
    $profilo, $seme, $anni, count($mondo->nazioni));

$r = Realismo::misura($mondo, new EsecutoreTick($cal, null, true, $mondo), $anni, $tickAnno, $seme);

printf("  %-22s %12s %13s   %-20s %s\n",
    'grandezza', 'al seme', "dopo $anni anni", 'fascia attesa', 'fonte');
printf("  %s\n", str_repeat('-', 132));

$numero = static fn (float $x): string => number_format($x, abs($x) < 100 ? 2 : 0, ',', '.');
$fuori  = [];

foreach (Realismo::FASCE as $chiave => [$min, $max, $unita, $quando, $fonte]) {
    $v = $r['misure'][$chiave];
    if ($v < $min || $v > $max) {
        $fuori[] = $chiave;
    }

    printf("  %-22s %12s %13s   %-20s %s\n",
        $chiave . ($quando === 'seme' ? ' *' : ''),
        isset($r['seme'][$chiave]) ? $numero($r['seme'][$chiave]) : '—',
        isset($r['fine'][$chiave]) ? $numero($r['fine'][$chiave]) : $numero($v),
        sprintf('%s-%s %s',
            rtrim(rtrim(number_format($min, 1, ',', '.'), '0'), ','),
            rtrim(rtrim(number_format($max, 1, ',', '.'), '0'), ','), $unita),
        ($v >= $min && $v <= $max ? '  ' : '! ') . $fonte);
}

printf("\n  * la fascia vale al seme: un livello cresciuto per %d anni non si\n"
     . "    confronta col dato di oggi.\n\n", $anni);
printf("  %d grandezze su %d fuori fascia%s.\n",
    count($fuori), count(Realismo::FASCE),
    $fuori === [] ? '' : ': ' . implode(', ', $fuori));

exit($fuori === [] ? 0 : 1);
