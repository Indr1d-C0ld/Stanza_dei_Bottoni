<?php

declare(strict_types=1);

/**
 * Le vocazioni commerciali che il modello da solo non indovina.
 *
 * Il grafo di Commercio.php ricava chi produce cosa da quel che il seme porta
 * davvero: terra per abitante, ricchezza, istruzione, taglia. Basta per la
 * forma generale — i paesi vuoti vendono cibo, i ricchi istruiti vendono
 * servizi — ma non sa niente di quel che sta sottoterra, e non puo' saperlo:
 * il World Factbook da cui viene il seme non porta ne' riserve di idrocarburi
 * ne' superficie arabile.
 *
 * Ho provato a dedurlo — un petrostato dovrebbe essere ricco senza essere
 * istituzionalmente maturo — e non funziona: con quella firma il Kuwait
 * finisce accanto alla Polonia e all'Estonia. Quando un indice non contiene
 * un'informazione, non c'e' modo furbo di tirarla fuori.
 *
 * Quindi si dichiara, com'e' gia' stato fatto per gli scostamenti noti delle
 * capacita' di intelligence in politica-nota.php. Questi sono moltiplicatori
 * sulla produzione di un settore: 6.0 vuol dire che quel paese ne produce sei
 * volte quel che la sua taglia farebbe prevedere.
 *
 * [FABBRICATO] come tutto il resto del seme: sono ordini di grandezza
 * plausibili e di dominio comune, non cifre prese da statistiche commerciali.
 */

return [
    'vocazione' => [
        // --- idrocarburi ------------------------------------------------
        'SAU' => ['energia' => 9.0],
        'QAT' => ['energia' => 11.0, 'finanza' => 1.6],
        'KWT' => ['energia' => 9.5],
        'ARE' => ['energia' => 6.0, 'finanza' => 2.2],
        'IRQ' => ['energia' => 8.0],
        'IRN' => ['energia' => 5.0],
        'LBY' => ['energia' => 8.5],
        'DZA' => ['energia' => 5.5],
        'NGA' => ['energia' => 4.5],
        'AGO' => ['energia' => 6.0],
        'VEN' => ['energia' => 7.5],
        'NOR' => ['energia' => 5.0, 'finanza' => 1.7],
        'RUS' => ['energia' => 4.0],
        'KAZ' => ['energia' => 4.5],
        'AZE' => ['energia' => 6.0],
        'TKM' => ['energia' => 7.0],
        'OMN' => ['energia' => 6.5],
        'BRN' => ['energia' => 7.0],
        'TTO' => ['energia' => 6.0],
        'GNQ' => ['energia' => 8.0],
        'COG' => ['energia' => 6.0],
        'GAB' => ['energia' => 5.0],
        'ECU' => ['energia' => 2.6],
        'COL' => ['energia' => 2.2],
        'CAN' => ['energia' => 2.4, 'cibo' => 2.2],
        'AUS' => ['energia' => 2.6, 'cibo' => 2.4],
        'IDN' => ['energia' => 2.0],
        'MYS' => ['energia' => 1.9],

        // --- granai e dispense ------------------------------------------
        'ARG' => ['cibo' => 4.2],
        'UKR' => ['cibo' => 4.0],
        'NZL' => ['cibo' => 4.5],
        'URY' => ['cibo' => 4.0],
        'PRY' => ['cibo' => 3.8],
        'BRA' => ['cibo' => 3.0],
        'THA' => ['cibo' => 2.6],
        'VNM' => ['cibo' => 2.2, 'manifattura' => 2.2],
        'CIV' => ['cibo' => 3.0],
        'GHA' => ['cibo' => 2.2],
        'ETH' => ['cibo' => 1.8],
        'DNK' => ['cibo' => 2.4],
        'NLD' => ['cibo' => 2.8, 'finanza' => 1.5],
        'IRL' => ['cibo' => 2.0, 'tecnologia' => 2.2, 'finanza' => 2.0],
        'CHL' => ['cibo' => 2.2],
        'PER' => ['cibo' => 1.9],
        'ESP' => ['cibo' => 1.7],
        'FRA' => ['cibo' => 1.8],
        'USA' => ['cibo' => 1.6, 'tecnologia' => 1.8, 'finanza' => 1.8],

        // --- officine del mondo -----------------------------------------
        'CHN' => ['manifattura' => 2.6, 'tecnologia' => 1.4],
        'DEU' => ['manifattura' => 2.2, 'tecnologia' => 1.6],
        'KOR' => ['manifattura' => 2.2, 'tecnologia' => 2.4],
        'JPN' => ['manifattura' => 1.9, 'tecnologia' => 2.2],
        'TWN' => ['manifattura' => 2.0, 'tecnologia' => 3.0],
        'ITA' => ['manifattura' => 1.8],
        'CZE' => ['manifattura' => 2.0],
        'POL' => ['manifattura' => 1.7],
        'SVK' => ['manifattura' => 2.0],
        'HUN' => ['manifattura' => 1.8],
        'MEX' => ['manifattura' => 1.8],
        'TUR' => ['manifattura' => 1.6],
        'BGD' => ['manifattura' => 2.2],

        // --- banche, assicurazioni, bandiere di comodo -------------------
        'CHE' => ['finanza' => 3.2],
        'SGP' => ['finanza' => 3.0, 'tecnologia' => 1.8],
        'LUX' => ['finanza' => 4.5],
        'HKG' => ['finanza' => 3.0],
        'GBR' => ['finanza' => 2.4],
        'PAN' => ['finanza' => 2.4],
        'CYP' => ['finanza' => 2.2],
        'MLT' => ['finanza' => 2.2],
        'BHS' => ['finanza' => 2.6],
        'ISR' => ['tecnologia' => 2.6],
        'FIN' => ['tecnologia' => 1.8],
        'SWE' => ['tecnologia' => 1.8],
        'EST' => ['tecnologia' => 1.8],
        'IND' => ['tecnologia' => 1.5],
    ],
];
