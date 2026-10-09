<?php

declare(strict_types=1);

/**
 * Importa la crescita reale del PIL dal World Economic Outlook del Fondo
 * Monetario Internazionale e produce db/seed/crescita.php.
 *
 * PERCHE' ESISTE. La crescita del seme veniva dal Factbook, media delle ultime
 * tre voci — e le ultime tre voci non hanno lo stesso anno per tutti. Il
 * Venezuela era fermo al 2017-18 (-17,7% l'anno, quando nel 2022-24 e'
 * cresciuto del 4-8%), il Sud Sudan al 2015-17, lo Yemen al 2016-18; Fiji e
 * Barbados portavano dentro il rimbalzo del 2022 dopo la pandemia. E quella
 * media diventava la TENDENZA del paese per sempre: col pavimento a -4%, il
 * Venezuela, lo Yemen e il Sud Sudan erano condannati a recedere ogni anno
 * della simulazione (docs/30).
 *
 * Il FMI da' due cose che il Factbook non da':
 *
 *   - la crescita RECENTE con lo stesso anno per tutti — qui la mediana del
 *     2023-2025, che resiste ai salti di un anno solo (il Sud Sudan: +3, -26,
 *     +46 per l'oleodotto chiuso e riaperto);
 *   - la crescita di MEDIO PERIODO, cioe' la media delle proiezioni 2026-2030:
 *     e' la tendenza strutturale secondo chi la stima di mestiere, e incorpora
 *     gia' il rientro dei boom e delle crisi.
 *
 * Fonte: IMF, World Economic Outlook, edizione di aprile 2026, indicatore
 * NGDP_RPCH (Real GDP growth, annual percent change), via l'API DataMapper:
 *
 *   https://www.imf.org/external/datamapper/api/v1/NGDP_RPCH
 *
 *   php bin/importa_fmi.php [--json=percorso]
 *
 * Senza --json legge storage/fonti/fmi-weo-ngdp_rpch.json. I paesi che il FMI
 * non copre (Siria, Corea del Nord, Cuba...) restano col dato del Factbook.
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$opz  = getopt('', ['json::']);
$file = (string) ($opz['json'] ?? $radice . '/storage/fonti/fmi-weo-ngdp_rpch.json');
$dati = json_decode((string) @file_get_contents($file), true);
$serie = $dati['values']['NGDP_RPCH'] ?? null;
if (!is_array($serie)) {
    fwrite(STDERR, "Non riesco a leggere $file.\n"
        . "Scaricalo da https://www.imf.org/external/datamapper/api/v1/NGDP_RPCH\n");
    exit(1);
}

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');

$valori = static function (array $s, int $da, int $a): array {
    $v = [];
    for ($y = $da; $y <= $a; $y++) {
        if (isset($s[(string) $y]) && is_numeric($s[(string) $y])) {
            $v[] = (float) $s[(string) $y] / 100.0;
        }
    }
    return $v;
};
$mediana = static function (array $v): float {
    sort($v);
    $n = count($v);
    return $n % 2 ? $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2.0;
};

$esito = [];
$mancano = [];
foreach ($mondo->elenco() as $n) {
    // Il FMI chiama il Kosovo col codice del vecchio protettorato ONU.
    $s = $serie[['XKX' => 'UVK'][$n->iso3] ?? $n->iso3] ?? null;
    $recente = is_array($s) ? $valori($s, 2023, 2025) : [];
    if ($recente === []) {
        $mancano[] = $n->iso3;
        continue;
    }
    // Il medio periodo: le proiezioni 2026-2030. Dove il FMI ha sospeso le
    // proiezioni (Venezuela, Afghanistan, Libano) si prende quel che c'e' dal
    // 2025 in poi, e se non c'e' nulla la crescita recente.
    $lungo = $valori($s, 2026, 2030);
    if (count($lungo) < 2) {
        $lungo = $valori($s, 2025, 2030);
    }
    $esito[$n->iso3] = [
        'recente' => round($mediana($recente), 4),
        'lungo'   => round($lungo !== [] ? array_sum($lungo) / count($lungo) : $mediana($recente), 4),
    ];
}
ksort($esito);

$righe = [];
foreach ($esito as $iso => $v) {
    $righe[] = sprintf("    '%s' => ['recente' => %.4f, 'lungo' => %.4f],", $iso, $v['recente'], $v['lungo']);
}
$testa = <<<PHP
<?php

// Generato da bin/importa_fmi.php — non modificare a mano.
//
// Crescita reale del PIL, frazione annua. Fonte: IMF, World Economic Outlook,
// aprile 2026 (NGDP_RPCH). 'recente' = mediana 2023-2025; 'lungo' = media
// delle proiezioni 2026-2030. Mancano, e restano al Factbook: %s.

return [

PHP;
file_put_contents($radice . '/db/seed/crescita.php',
    sprintf($testa, $mancano === [] ? 'nessuno' : implode(', ', $mancano)) . implode("\n", $righe) . "\n];\n");

printf("%d paesi dal FMI, %d al Factbook: %s\n", count($esito), count($mancano), implode(' ', $mancano));
