<?php

declare(strict_types=1);

/**
 * Quel che i dati non dicono, e che noi dobbiamo dire lo stesso.
 *
 * Era una tabella FABBRICATA: orientamenti politici e rapporti fra Stati
 * scritti a mano, perche' il tipo di governo del Factbook faceva della Russia
 * e dell'Ucraina due paesi amici all'83%. Da allora i dati l'hanno svuotata:
 *
 *   - l'ORIENTAMENTO viene dai voti all'ONU (docs/33). I 45 orientamenti che
 *     stavano qui valevano solo per chi non vota, e nessuno di quei 45 e' fra
 *     loro: erano morti, e li ho tolti (docs/37). Taiwan e il Kosovo prendono
 *     il valore della loro forma di governo;
 *   - l'AFFINITA' di ogni coppia si stima da voti all'ONU, patti di ATOP,
 *     dispute militarizzate, rivalita' strategiche e paesi ostili alla Russia
 *     (bin/importa_onu.php, docs/32 e docs/36). I pesi si stimano proprio su
 *     questi rapporti, che spiega all'81%.
 *
 * I rapporti restano qui, e vincono, per il resto: i fatti politici datati che
 * nessuna variabile strutturale vede — il Giappone e i rapimenti, la guerra
 * fra Israele e Hezbollah, l'Ungheria di Orban. Dove si discostano molto dalla
 * stima dei dati portano accanto la ragione e la data. Le sanzioni come
 * variabile le ho provate (OpenSanctions, la mappa delle sanzioni dell'UE) e
 * spiegano mezzo punto in piu': i loro bersagli sono gia' le rivalita' e i
 * paesi ostili alla Russia (docs/37).
 *
 * In fondo, gli EVENTI RECENTI: le ostilita' nate dopo il 2020 senza
 * combattimenti fra Stati, che vincono come i rapporti ma restano fuori dalla
 * stima dei pesi (docs/38).
 */

return [

    /**
     * Postura nucleare, sulla scala a sette livelli del "quadrante destro"
     * della città di Shadow President: 1 nessun nucleare · 2 centrale civile ·
     * 3 programma militare avviato · 4 ordigno provato · 5 gittata regionale ·
     * 6 macro-regionale · 7 globale.
     *
     * Questa non è fabbricata, è pubblica: sono gli Stati dotati e quelli con
     * un ciclo civile significativo.
     */
    'postura_nucleare' => [
        'USA' => 7, 'RUS' => 7, 'CHN' => 7,
        'FRA' => 6, 'GBR' => 6, 'IND' => 6,
        'PAK' => 5, 'ISR' => 5, 'PRK' => 5,
        'IRN' => 3,
        'JPN' => 2, 'DEU' => 2, 'KOR' => 2, 'CAN' => 2, 'BRA' => 2, 'ARG' => 2,
        'ZAF' => 2, 'UKR' => 2, 'ESP' => 2, 'BEL' => 2, 'CZE' => 2, 'FIN' => 2,
        'SWE' => 2, 'CHE' => 2, 'NLD' => 2, 'HUN' => 2, 'MEX' => 2, 'ROU' => 2,
        'SVK' => 2, 'SVN' => 2, 'BGR' => 2, 'ARE' => 2, 'BLR' => 2, 'TUR' => 2,
        'EGY' => 2, 'BGD' => 2,
    ],

    /**
     * Moltiplicatori della capacità di intelligence, dove il peso economico è
     * una misura ingannevole. Israele e il Regno Unito pesano poco e vedono
     * molto; il contrario vale per parecchi paesi grandi e distratti.
     *
     * [FABBRICATO] come tutto il resto di questo file.
     */
    'capacita_intelligence' => [
        'ISR' => 2.6, 'GBR' => 1.8, 'FRA' => 1.4, 'RUS' => 1.6, 'PRK' => 1.5,
        'IRN' => 1.4, 'PAK' => 1.3, 'CHE' => 1.3, 'SGP' => 1.4, 'NLD' => 1.2,
        'SWE' => 1.2, 'AUS' => 1.3, 'KOR' => 1.3, 'TUR' => 1.2, 'IND' => 0.9,
        'BRA' => 0.7, 'IDN' => 0.7, 'NGA' => 0.7, 'DEU' => 0.9, 'JPN' => 0.9,
    ],

    /**
     * Rapporti bilaterali notevoli: alleanze e inimicizie che nessuna formula
     * strutturale può indovinare. Valore di affinità -127..+127, applicato in
     * entrambe le direzioni salvo diversa indicazione.
     *
     * Non è un elenco esaustivo e non pretende di esserlo: copre i rapporti che
     * cambiano il comportamento del modello.
     */
    'rapporti' => [
        // Blocco atlantico
        ['USA', 'GBR',  110], ['USA', 'CAN',  115], ['USA', 'AUS',  110],
        ['USA', 'JPN',  105], ['USA', 'KOR',  100], ['USA', 'DEU',   95],
        ['USA', 'FRA',   90], ['USA', 'ITA',   95], ['USA', 'POL',  100],
        // Stati Uniti e Israele: 38 miliardi di aiuti militari nel memorandum
        // del 2016 per il 2019-2028; nessun patto di difesa formale, e i dati
        // danno +54.
        ['USA', 'ISR',  110], ['GBR', 'FRA',   95], ['DEU', 'FRA',  115],
        ['DEU', 'POL',   85], ['FRA', 'ITA',   95], ['NLD', 'DEU',  110],
        ['ESP', 'PRT',  110], ['SWE', 'FIN',  115], ['NOR', 'SWE',  110],
        ['USA', 'TUR',   45], ['TUR', 'GRC',  -35], ['GRC', 'CYP',  110],

        // Blocco orientale e partner
        ['RUS', 'BLR',  110], ['RUS', 'CHN',   70], ['RUS', 'IRN',   65],
        ['RUS', 'PRK',   55], ['RUS', 'SYR',   80], ['RUS', 'KAZ',   60],
        ['CHN', 'PRK',   75], ['CHN', 'PAK',   90], ['CHN', 'IRN',   60],
        ['CHN', 'RUS',   70], ['VEN', 'CUB',   95], ['CUB', 'RUS',   60],

        // Inimicizie storiche
        ['RUS', 'UKR', -110], ['UKR', 'RUS', -120],
        ['IND', 'PAK', -100], ['PAK', 'IND',  -95],
        ['PRK', 'KOR',  -95], ['KOR', 'PRK',  -85],
        ['ISR', 'IRN', -110], ['IRN', 'ISR', -115],
        // Israele e Hezbollah in guerra dall'8/10/2023, tregua del 27/11/2024
        // con le truppe israeliane ancora nel sud del Libano: i dati
        // strutturali (nessuna disputa fra Stati dal 2006) danno +16.
        ['ISR', 'SYR',  -90], ['ISR', 'LBN',  -70],
        // Le relazioni diplomatiche, rotte nel 2016, sono state ristabilite a
        // Pechino il 10/03/2023: la rivalita' strategica resta (Thompson,
        // Sakuwa e Suhas), l'ostilita' aperta no. Era -85.
        ['SAU', 'IRN',  -60], ['IRN', 'SAU',  -60],
        ['USA', 'IRN', -100], ['USA', 'PRK', -105], ['USA', 'CUB',  -60],
        ['USA', 'RUS',  -85], ['RUS', 'USA',  -85],
        ['USA', 'CHN',  -45], ['CHN', 'USA',  -45],
        ['CHN', 'TWN',  -80], ['TWN', 'CHN',  -75], ['USA', 'TWN',   70],
        ['CHN', 'IND',  -45], ['IND', 'CHN',  -45],
        ['ARM', 'AZE',  -95], ['AZE', 'ARM',  -95],
        ['SRB', 'XKX',  -90], ['XKX', 'SRB',  -85],
        ['DZA', 'MAR',  -60], ['MAR', 'DZA',  -60],
        // La pace del 2018 (Abiy, Nobel 2019) ha chiuso la rivalita' per
        // Thompson, Sakuwa e Suhas; ma dopo l'accordo di Pretoria sul Tigray
        // (11/2022) i due paesi sono tornati ostili, e Abiy rivendica uno
        // sbocco sul Mar Rosso dall'ottobre 2023.
        ['ETH', 'ERI',  -55], ['SDN', 'SSD',  -45],
        // Il Giappone vieta ogni importazione dalla Corea del Nord dal 2006 e
        // ogni esportazione dal 2009, per i missili e per i cittadini rapiti
        // negli anni Settanta-Ottanta; i dati, senza dispute fra i due e senza
        // sanzioni giapponesi nelle fonti aperte, danno +40.
        ['GRC', 'TUR',  -35], ['JPN', 'PRK',  -80], ['JPN', 'CHN',  -35],

        // La guerra in Ucraina (dal 24/02/2022). Prima di questa sezione il
        // seme non sapeva che l'Europa aveva rotto con la Russia: al primo
        // tick di guerra l'Ucraina riceveva aiuti solo dagli Stati Uniti e da
        // una manciata di baltici, e la Cina armava la Russia.
        //
        // I donatori maggiori, in ordine, secondo il Kiel Institute (Ukraine
        // Support Tracker, febbraio 2025): Stati Uniti, Germania, Regno Unito,
        // Danimarca, Paesi Bassi, Svezia, Norvegia, Polonia, Canada, Francia,
        // Finlandia, e in proporzione al PIL i baltici e la Cechia. Tutti nel
        // Gruppo di contatto per la difesa dell'Ucraina (Ramstein) e, gli
        // europei, nei diciannove pacchetti di sanzioni dell'UE (ottobre 2025).
        ['USA', 'UKR',   70], ['DEU', 'UKR',   75], ['GBR', 'UKR',   80],
        ['DNK', 'UKR',   85], ['NLD', 'UKR',   80], ['SWE', 'UKR',   80],
        ['NOR', 'UKR',   80], ['POL', 'UKR',   75], ['CAN', 'UKR',   75],
        ['FRA', 'UKR',   70], ['FIN', 'UKR',   80], ['EST', 'UKR',   90],
        ['LVA', 'UKR',   90], ['LTU', 'UKR',   90], ['CZE', 'UKR',   75],
        ['BEL', 'UKR',   65],
        ['DEU', 'RUS',  -70], ['GBR', 'RUS',  -80], ['DNK', 'RUS',  -75],
        ['NLD', 'RUS',  -75], ['SWE', 'RUS',  -75], ['NOR', 'RUS',  -70],
        ['POL', 'RUS',  -90], ['CAN', 'RUS',  -75], ['FRA', 'RUS',  -65],
        ['FIN', 'RUS',  -80], ['EST', 'RUS',  -95], ['LVA', 'RUS',  -95],
        ['LTU', 'RUS',  -95], ['CZE', 'RUS',  -70], ['BEL', 'RUS',  -60],
        // Il resto dell'UE: sanzioni votate, aiuti minori.
        ['ITA', 'UKR',   50], ['ESP', 'UKR',   50], ['PRT', 'UKR',   50],
        ['ROU', 'UKR',   50], ['IRL', 'UKR',   45], ['GRC', 'UKR',   40],
        ['BGR', 'UKR',   40], ['HRV', 'UKR',   50], ['SVN', 'UKR',   45],
        ['ITA', 'RUS',  -45], ['ESP', 'RUS',  -45], ['PRT', 'RUS',  -45],
        ['ROU', 'RUS',  -60], ['IRL', 'RUS',  -40], ['GRC', 'RUS',  -30],
        ['BGR', 'RUS',  -30], ['HRV', 'RUS',  -45], ['SVN', 'RUS',  -40],
        // Le eccezioni europee: l'Ungheria di Orban ha bloccato o ritardato
        // gli aiuti UE e non manda armi; la Slovacchia dal 2023 ha smesso di
        // mandarne.
        ['HUN', 'UKR',   -5], ['HUN', 'RUS',   20],
        ['SVK', 'UKR',   15], ['SVK', 'RUS',   -5],
        // Dall'altra parte: truppe nordcoreane nel Kursk dall'autunno 2024
        // (riconosciute da Mosca e Pyongyang nell'aprile 2025), munizioni e
        // droni iraniani. La Cina si dichiara neutrale, commercia con tutti e
        // due, e non risultano forniture di armi: ne' con l'Ucraina ne' con la
        // Russia e' in guerra.
        ['PRK', 'RUS',   90], ['IRN', 'RUS',   75],
        ['CHN', 'UKR',    0], ['UKR', 'CHN',    0],

        // Partner regionali
        ['BRA', 'ARG',   75], ['IND', 'BGD',   60], ['IND', 'NPL',   65],
        ['ZAF', 'NAM',   75], ['ZAF', 'BWA',   80], ['NGA', 'GHA',   70],
        ['SAU', 'ARE',   95], ['SAU', 'BHR',   95], ['ARE', 'EGY',   80],
        // Australia e Nuova Zelanda: mercato unico (Closer Economic Relations,
        // 1983), libera circolazione delle persone, l'ANZUS; i dati vedono
        // solo un patto e danno +54.
        ['IDN', 'MYS',   70], ['AUS', 'NZL',  115], ['MEX', 'CAN',   75],
    ],

    /**
     * Le ostilita' nate dopo il 2020 senza combattimenti fra Stati, che
     * nessuna fonte strutturale vede: le dispute dell'UCDP contano solo i
     * conflitti armati (e arrivano al 2025: India-Pakistan, Cambogia-
     * Thailandia, Iran-Israele ci sono gia'), le rivalita' strategiche si
     * fermano al 2020. Vincono come i rapporti, ma NON entrano nella stima dei
     * pesi di bin/importa_onu.php: insegnerebbero alla regressione dei fatti
     * che la struttura non puo' spiegare (docs/38). Ognuno con la sua data.
     */
    'eventi_recenti' => [
        // La sentenza arbitrale del 2016 ignorata da Pechino; gli scontri alla
        // secca di Second Thomas con idranti e speronamenti (2023-2024), fino
        // all'abbordaggio del 17/06/2024.
        ['CHN', 'PHL',  -40], ['PHL', 'CHN',  -45],
        // La Turchia sospende ogni commercio con Israele il 02/05/2024.
        ['TUR', 'ISR',  -50], ['ISR', 'TUR',  -40],
        // L'Algeria abbatte un drone maliano a Tinzaouaten nella notte del
        // 01/04/2025; Mali, Niger e Burkina Faso richiamano gli ambasciatori,
        // gli spazi aerei si chiudono a vicenda.
        ['DZA', 'MLI',  -40], ['MLI', 'DZA',  -45],
        // Le giunte del Sahel cacciano la Francia: Barkhane lascia il Mali il
        // 15/08/2022, il Burkina Faso a febbraio 2023, il Niger a dicembre
        // 2023 dopo aver espulso l'ambasciatore.
        ['MLI', 'FRA',  -50], ['BFA', 'FRA',  -45], ['NER', 'FRA',  -55],
        // Il Niger tiene chiuso il confine col Benin dal colpo del 2023, anche
        // dopo la fine delle sanzioni ECOWAS; la lite sull'oleodotto del 2024.
        ['NER', 'BEN',  -25],
        // L'Ufficio di rappresentanza di Taiwan a Vilnius (18/11/2021): la
        // Cina declassa le relazioni e blocca le merci lituane.
        ['CHN', 'LTU',  -30],
        // Il Sudafrica porta Israele davanti alla Corte internazionale di
        // giustizia per genocidio (29/12/2023), dopo aver richiamato i suoi
        // diplomatici a novembre.
        ['ZAF', 'ISR',  -45], ['ISR', 'ZAF',  -35],
        // Il volo AZAL 8243 abbattuto dalla contraerea russa il 25/12/2024; le
        // morti di due azeri nelle retate di Ekaterinburg (giugno 2025).
        ['AZE', 'RUS',  -15],
    ],
];
