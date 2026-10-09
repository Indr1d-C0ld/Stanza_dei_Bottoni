<?php

declare(strict_types=1);

/**
 * Importa chi vende a chi — le esportazioni bilaterali di beni — e produce
 * db/seed/commercio-bilaterale.php.
 *
 * PERCHE' ESISTE. Con i totali per settore della Banca Mondiale
 * (bin/importa_commercio.php) il grafo sapeva quanto ciascuno vende e compra,
 * ma chi vende a chi lo decideva ancora la gravita' — taglia, confine,
 * regione — e sbagliava le coppie: la Cina risultava comprare manufatti
 * soprattutto dall'India (docs/35).
 *
 * Fonte: Fondo Monetario Internazionale, International Trade in Goods
 * (IMTS, gia' Direction of Trade Statistics), esportazioni FOB in dollari,
 * dichiarate dall'esportatore o stimate dai partner, media 2023-2024. API SDMX:
 *
 *   https://api.imf.org/external/sdmx/2.1/data/IMF.STA,IMTS/.XG_FOB_USD..A
 *       ?startPeriod=2023&endPeriod=2024
 *
 * Si tengono i flussi che valgono almeno lo 0,1% delle esportazioni di chi
 * vende, oppure lo 0,5% delle importazioni di chi compra: il resto non sposta
 * niente e gonfierebbe il file. Il secondo criterio serve ai paesi piccoli: la
 * Cina vende al Nepal lo 0,06% di quel che esporta, ma per il Nepal e' il
 * secondo fornitore, e senza di lei il riequilibrio del grafo gli faceva
 * comprare tutto dal Bhutan (docs/35). Taiwan, che fra gli
 * esportatori del FMI non c'e', si ricava dalle importazioni dei suoi partner
 * (MG_CIF_USD con controparte TWN), riportate a FOB dividendo per 1,06.
 *
 *   php bin/importa_imts.php
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$file = $radice . '/storage/fonti/commercio/imts-esportazioni-2023-2024.xml';
if (!is_file($file)) {
    fwrite(STDERR, "Manca $file: si scarica dall'API del FMI (vedi l'intestazione).\n");
    exit(1);
}

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$nostri = array_flip(array_keys($mondo->nazioni));
// Il FMI chiama il Kosovo col codice del vecchio protettorato ONU.
$alias = ['UVK' => 'XKX', 'KOS' => 'XKX'];

$valori = [];
$xml = new XMLReader();
$xml->open($file);
$serie = null;
while ($xml->read()) {
    if ($xml->nodeType !== XMLReader::ELEMENT) {
        continue;
    }
    if ($xml->localName === 'Series') {
        $da = (string) $xml->getAttribute('COUNTRY');
        $a  = (string) $xml->getAttribute('COUNTERPART_COUNTRY');
        $da = $alias[$da] ?? $da;
        $a  = $alias[$a] ?? $a;
        $serie = isset($nostri[$da], $nostri[$a]) && $da !== $a ? "$da|$a" : null;
    } elseif ($xml->localName === 'Obs' && $serie !== null) {
        $v = $xml->getAttribute('OBS_VALUE');
        if (is_numeric($v) && (float) $v > 0.0) {
            $valori[$serie][] = (float) $v;
        }
    }
}
$xml->close();

// Taiwan non compare fra gli esportatori del FMI. I suoi partner pero'
// dichiarano quanto ne importano: da li', riportati da CIF a FOB col fattore
// 1,06 che il FMI usa quando stima dai dati dei partner.
$daTaiwan = $radice . '/storage/fonti/commercio/imts-importazioni-da-taiwan-2023-2024.xml';
if (is_file($daTaiwan) && isset($nostri['TWN'])) {
    $xml->open($daTaiwan);
    $serie = null;
    while ($xml->read()) {
        if ($xml->nodeType !== XMLReader::ELEMENT) {
            continue;
        }
        if ($xml->localName === 'Series') {
            $chi = (string) $xml->getAttribute('COUNTRY');
            $chi = $alias[$chi] ?? $chi;
            $serie = isset($nostri[$chi]) && $chi !== 'TWN' ? "TWN|$chi" : null;
        } elseif ($xml->localName === 'Obs' && $serie !== null) {
            $v = $xml->getAttribute('OBS_VALUE');
            if (is_numeric($v) && (float) $v > 0.0) {
                $valori[$serie][] = (float) $v / 1.06;
            }
        }
    }
    $xml->close();
}

$medie = [];
$totale = [];
$comprato = [];
foreach ($valori as $k => $v) {
    $medie[$k] = array_sum($v) / count($v) / 1.0e6;   // milioni di dollari
    [$da, $a] = explode('|', $k);
    $totale[$da] = ($totale[$da] ?? 0.0) + $medie[$k];
    $comprato[$a] = ($comprato[$a] ?? 0.0) + $medie[$k];
}
$tenuti = array_filter($medie, static function (float $v, string $k) use ($totale, $comprato): bool {
    [$da, $a] = explode('|', $k);
    return $v >= 0.001 * ($totale[$da] ?? 0.0) || $v >= 0.005 * ($comprato[$a] ?? 0.0);
}, ARRAY_FILTER_USE_BOTH);
ksort($tenuti);

$righe = [];
foreach ($tenuti as $k => $v) {
    $righe[] = sprintf("    '%s' => %.1f,", $k, $v);
}
$senza = array_values(array_diff(array_keys($mondo->nazioni), array_keys($totale)));
$testa = <<<PHP
<?php

// Generato da bin/importa_imts.php — non modificare a mano.
//
// Esportazioni di beni «A|B» da A a B, milioni di dollari, media 2023-2024.
// FMI, International Trade in Goods (IMTS), esportazioni FOB dichiarate o
// stimate dai partner. Flussi oltre lo 0,1%% delle esportazioni di chi vende o
// lo 0,5%% delle importazioni di chi compra.
// Paesi senza esportazioni nel dataset: %s.

return [

PHP;
file_put_contents($radice . '/db/seed/commercio-bilaterale.php',
    sprintf($testa, $senza === [] ? 'nessuno' : implode(', ', $senza)) . implode("\n", $righe) . "\n];\n");
printf("%d flussi tenuti su %d; esportatori: %d; senza: %s\n", count($tenuti), count($medie), count($totale),
    implode(' ', $senza));
