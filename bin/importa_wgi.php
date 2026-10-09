<?php

declare(strict_types=1);

/**
 * Importa i Worldwide Governance Indicators della Banca Mondiale e produce
 * db/seed/governo.php.
 *
 * PERCHE' ESISTE. La legittimita' di ogni paese tornava verso 50, uguale per
 * tutti: la Danimarca e la Somalia avevano lo stesso punto di riposo, e le
 * differenze vere le portava solo il caso. In piu' il seme partiva da una
 * media di 60, e il mondo passava i primi anni a scendere di tre punti l'anno
 * — una tendenza che non era del mondo ma del modello (docs/30).
 *
 * Il punto di riposo e' adesso una proprieta' del paese: quanto il suo Stato
 * e' stabile, efficace e soggetto alla legge. E' la lettura di
 *
 *   GILLEY (2006), «The Meaning and Measure of State Legitimacy: Results for
 *   72 Countries», European Journal of Political Research 45(3) — la
 *   legittimita' dello Stato va con la qualita' del governo, lo stato di
 *   diritto e il benessere, piu' che con la forma democratica;
 *
 * e la forma democratica il modello la conta gia' a parte (V-Dem, fase 05).
 *
 * Fonte: Banca Mondiale, Worldwide Governance Indicators, edizione 2026
 * (aggiornata il 25/09/2026, dati fino al 2025), stime in unita' normali
 * (circa -2,5..+2,5), via l'API:
 *
 *   https://api.worldbank.org/v2/country/all/indicator/GOV_WGI_PV.EST?source=3
 *
 * e lo stesso per GE (efficacia del governo), RL (stato di diritto), VA (voce
 * e responsabilita': si conserva per i prossimi usi, non entra nell'ancora).
 * Per ogni paese la media del 2023-2025: le stime hanno un errore tipico di
 * due decimi, e un anno solo ballerebbe.
 *
 *   php bin/importa_wgi.php [--cartella=storage/fonti]
 */

$radice = require __DIR__ . '/_avvio.php';

use App\Dati\Mondo;

$opz      = getopt('', ['cartella::']);
$cartella = (string) ($opz['cartella'] ?? $radice . '/storage/fonti');
$indici   = ['PV' => 'stabilita', 'GE' => 'efficacia', 'RL' => 'diritto', 'VA' => 'voce'];

$valori = [];
$prestati = [];
foreach ($indici as $codice => $nome) {
    $file = "$cartella/wgi-$codice.json";
    $dati = json_decode((string) @file_get_contents($file), true);
    if (!is_array($dati[1] ?? null)) {
        fwrite(STDERR, "Non riesco a leggere $file.\nScaricalo da https://api.worldbank.org/v2/country/all/"
            . "indicator/GOV_WGI_$codice.EST?format=json&date=2019:2025&per_page=20000&source=3\n");
        exit(1);
    }
    foreach ($dati[1] as $r) {
        $anno = (int) $r['date'];
        if ($r['value'] === null || $anno < 2023 || $anno > 2025) {
            continue;
        }
        $valori[(string) $r['countryiso3code']][$nome][] = (float) $r['value'];
    }
}

// L'API della Banca Mondiale non restituisce Taiwan, che il dataset completo
// pubblica ("Taiwan, China"). Finche' non lo si legge da li', si prende la
// Corea del Sud, il paese piu' simile per reddito, istituzioni ed esposizione:
// e' un ripiego dichiarato, non un dato (docs/30).
$vicini = ['TWN' => 'KOR'];
foreach ($vicini as $chi => $come) {
    if (!isset($valori[$chi]) && isset($valori[$come])) {
        $valori[$chi] = $valori[$come];
        $prestati[] = "$chi come $come";
    }
}

$mondo = Mondo::daSeme($radice . '/db/seed/nazioni.csv');
$esito = [];
$mancano = [];
foreach ($mondo->elenco() as $n) {
    $v = $valori[$n->iso3] ?? null;
    if ($v === null || count($v) < count($indici)) {
        $mancano[] = $n->iso3;
        continue;
    }
    foreach ($indici as $nome) {
        $esito[$n->iso3][$nome] = round(array_sum($v[$nome]) / count($v[$nome]), 3);
    }
}
ksort($esito);

$righe = [];
foreach ($esito as $iso => $v) {
    $righe[] = sprintf("    '%s' => ['stabilita' => %6.3f, 'efficacia' => %6.3f, 'diritto' => %6.3f, 'voce' => %6.3f],",
        $iso, $v['stabilita'], $v['efficacia'], $v['diritto'], $v['voce']);
}
$testa = <<<PHP
<?php

// Generato da bin/importa_wgi.php — non modificare a mano.
//
// Banca Mondiale, Worldwide Governance Indicators, edizione 2026: media
// 2023-2025 delle stime (unita' normali, circa -2,5..+2,5). stabilita = PV
// (stabilita' politica e assenza di violenza), efficacia = GE, diritto = RL,
// voce = VA. Presi a prestito: %s. Mancano, e prendono la mediana della
// regione: %s.

return [

PHP;
file_put_contents($radice . '/db/seed/governo.php',
    sprintf($testa, $prestati === [] ? 'nessuno' : implode(', ', $prestati),
        $mancano === [] ? 'nessuno' : implode(', ', $mancano)) . implode("\n", $righe) . "\n];\n");

printf("%d paesi dalla Banca Mondiale, %d alla mediana regionale: %s\n",
    count($esito), count($mancano), implode(' ', $mancano));
