<?php

declare(strict_types=1);

/**
 * I voti all'ONU e le alleanze di ATOP (docs/32): gli obblighi di trattato
 * del 2018 con quel che e' cambiato dopo, e l'affinita' di partenza misurata
 * invece che dedotta dall'ideologia. Tutto in memoria.
 */

use App\Dati\Mondo;

$radiceOnu = dirname(__DIR__);
$alleanzeOnu = require $radiceOnu . '/db/seed/alleanze.php';
$pattiOnu = require $radiceOnu . '/db/seed/patti-difesa.php';
$onu = require $radiceOnu . '/db/seed/onu.php';
$mOnu = Mondo::daSeme($radiceOnu . '/db/seed/nazioni.csv');

// --------------------------------------------------------------------- ATOP
Prove::gruppo('Le alleanze vengono da ATOP, aggiornate dopo il 2018');

Prove::uguale('gli Stati Uniti difendono il Giappone', 96, $alleanzeOnu['USA|JPN'] ?? 0);
Prove::che('e il Giappone non difende gli Stati Uniti: l\'obbligo e\' asimmetrico',
    ($alleanzeOnu['JPN|USA'] ?? 0) < 96, (string) ($alleanzeOnu['JPN|USA'] ?? 0));
Prove::che('la Russia e la Corea del Nord si difendono dal 2024', ($alleanzeOnu['RUS|PRK'] ?? 0) === 96
    && ($alleanzeOnu['PRK|RUS'] ?? 0) === 96);
Prove::uguale('l\'Arabia Saudita e il Pakistan dal 2025', 96, $alleanzeOnu['SAU|PAK'] ?? 0);
Prove::uguale('la Finlandia sta nella NATO', 96, $alleanzeOnu['FIN|USA'] ?? 0);
Prove::che('l\'Algeria e il Marocco non si difendono, ne\' l\'Armenia e l\'Azerbaigian',
    !isset($alleanzeOnu['DZA|MAR']) && !isset($alleanzeOnu['ARM|AZE']));
Prove::che('il Mali non e\' piu\' legato alla Nigeria dall\'ECOWAS', !isset($alleanzeOnu['MLI|NGA']));
Prove::che('ma lo e\' al Burkina Faso e al Niger dall\'Alleanza del Sahel',
    ($alleanzeOnu['MLI|BFA'] ?? 0) === 96 && ($alleanzeOnu['MLI|NER'] ?? 0) === 96);
Prove::uguale('la NATO e\' un patto integrato', 'integrato', $pattiOnu['USA|GBR'] ?? '');
Prove::uguale('la Cina e la Corea del Nord un patto bilaterale', 'bilaterale', $pattiOnu['CHN|PRK'] ?? '');
Prove::che('l\'OAS un patto sulla carta, coi suoi membri', str_starts_with($pattiOnu['PER|HTI'] ?? '', 'multilaterale:'));

// --------------------------------------------------------------------- ONU
Prove::gruppo('L\'affinita\' di partenza si misura');

Prove::che('i pesi sono stimati, e spiegano piu\' di meta\' dei rapporti dichiarati',
    (float) $onu['pesi']['r2'] > 0.5, (string) $onu['pesi']['r2']);
Prove::che('chi vota lontano si piace meno, chi si e\' sparato addosso ancora meno',
    $onu['pesi']['distanza'] < 0 && $onu['pesi']['dispute'] < 0);
Prove::che('gli Stati Uniti votano piu\' «occidentale» della Germania, la Germania dell\'Iran',
    $onu['punti']['USA'] > $onu['punti']['DEU'] && $onu['punti']['DEU'] > $onu['punti']['IRN']);
Prove::che('Taiwan non ha un punto ideale (l\'ultimo voto e\' del 1971)', !isset($onu['punti']['TWN']));

$aff = static fn(string $a, string $b): float => $mOnu->relazioni->fra($a, $b)?->affinita ?? -999.0;
Prove::fra('il Peru\' e Haiti: vicini di voto e di patto, non alleati stretti', 45.0, 80.0, $aff('PER', 'HTI'));
Prove::che('la Svezia e la Norvegia: un patto integrato, un rapporto stretto', $aff('SWE', 'NOR') >= 88.0,
    (string) round($aff('SWE', 'NOR')));
Prove::che('la Cambogia e la Thailandia: vicine di voto, ma con le dispute', $aff('KHM', 'THA') < 25.0,
    (string) round($aff('KHM', 'THA')));
$valori = array_map(static fn($r): float => $r->affinita, array_values($mOnu->relazioni->tutte()));
sort($valori);
$q1 = $valori[intdiv(count($valori), 4)];
$q3 = $valori[intdiv(3 * count($valori), 4)];
Prove::che('le coppie non valgono piu\' tutte 25: il primo e il terzo quartile si separano', $q3 - $q1 > 30.0,
    sprintf('%.0f … %.0f', $q1, $q3));
