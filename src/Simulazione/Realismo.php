<?php

declare(strict_types=1);

namespace App\Simulazione;

use App\Dati\Mondo;

/**
 * Le grandezze che il mondo dichiara, e le fasce in cui il mondo vero le tiene.
 *
 * Questa classe esiste perche' un modello puo' raccontare una storia
 * bellissima e intanto affermare che una guerra bilaterale fa sessanta
 * milioni di morti l'anno — e' successo, ed e' il bug che l'ha fatta nascere.
 * Le fasce stanno qui, in un posto solo, e le leggono sia lo strumento
 * `bin/realismo.php` sia la prova automatica: due tabelle che si allontanano
 * l'una dall'altra sarebbero peggio di nessuna tabella.
 *
 * DUE CATEGORIE, e la distinzione non e' un dettaglio.
 *
 * Un LIVELLO — il prodotto mondiale, gli effettivi sotto le armi, la spesa in
 * dollari — si giudica AL SEME, perche' dopo quindici anni di crescita non e'
 * piu' confrontabile col dato di oggi: rimproverare al 2040 di non somigliare
 * al 2024 non e' una misura, e' un errore di categoria.
 *
 * Un TASSO o un RAPPORTO — la crescita, l'onere militare, i cambi irregolari
 * per anno — si giudica SULLA CORSA, perche' e' li' che vive il comportamento
 * del motore, e un motore puo' partire giusto e andare alla deriva.
 *
 * E UN TERZO TRANELLO, che e' costato la seconda revisione di questa tabella:
 * un riferimento puo' essere autorevole e insieme DELL'EPOCA SBAGLIATA. I ~10
 * cambi irregolari l'anno di Crawford sono giusti — per il 1948-77, che e' il
 * periodo del World Handbook da cui li ricava. Il nostro seme e' del 2024-25.
 * Una fonte non basta che sia seria: deve parlare del mondo che si sta
 * simulando.
 */
final class Realismo
{
    /**
     * @var array<string, array{0:float,1:float,2:string,3:string,4:string}>
     *      chiave => [minimo, massimo, unita', 'seme'|'corsa', fonte]
     */
    public const FASCE = [
        // --- livelli: quanto il seme somiglia al mondo reale -----------------
        'popolazione_mondo'    => [7.8,  8.6,  'miliardi',    'seme',
            'ONU, World Population Prospects 2024: 8,2 miliardi'],
        'pil_mondo'            => [150,  210,  'mila mrd $',  'seme',
            'Banca Mondiale: ~180 mila miliardi in dollari a parita\' di potere d\'acquisto. '
            . 'Il seme e\' in PPA, e si vede: la Cina sta davanti agli Stati Uniti'],
        'spesa_militare'       => [2200, 4500, 'mrd $',       'seme',
            'SIPRI, Trends in World Military Expenditure 2024: 2.718 miliardi di dollari'],
        'soldati_mondo'        => [18,   35,   'milioni',     'seme',
            'IISS, The Military Balance: ~27 milioni di effettivi in servizio'],
        'nucleari'             => [8,    12,   'Stati',       'seme',
            'SIPRI Yearbook: nove Stati dotati di armi nucleari (postura >= 4, «ordigno provato»)'],

        // --- tassi e rapporti: come si comporta il motore ---------------------
        'crescita_popolazione' => [0.6,  1.2,  '%/anno',      'corsa',
            'ONU, World Population Prospects 2024: il mondo cresce dello 0,9% l\'anno'],
        'crescita_pil'         => [2.0,  4.0,  '%/anno',      'corsa',
            'Banca Mondiale: 2,9% nel 2024, ~3,4% medio dal 2000 in PPA'],
        'onere_militare'       => [1.8,  3.6,  '% del PIL',   'corsa',
            'SIPRI 2024: 2,5% del prodotto mondiale, e in salita — la piu\' ripida dal 1988. '
            . 'Il tetto e\' alto apposta: il mondo vero e\' passato dal 2,2% del 2020 al 2,5% '
            . 'del 2024, cioe\' +0,075 punti l\'anno, che su quindici farebbero +1,1. Il '
            . 'modello ne fa +0,7, quindi sale piu\' piano del reale, non piu\' in fretta'],
        'cambi_irregolari'     => [3,    9,    '/anno',       'corsa',
            'Cline Center / Powell & Thyne: 2,2 colpi di Stato riusciti l\'anno nel 2000-2019, '
            . '~3,8 negli anni Venti, piu\' le rivoluzioni. Il ~10 di Crawford NON vale qui: '
            . 'descrive il 1948-77 (103 colpi negli anni \'60, 95 negli anni \'70) e il nostro '
            . 'seme e\' del 2024-25'],
        'guerre_aperte'        => [0,    8,    'in corso',    'corsa',
            'UCDP: i conflitti interstatali attivi sono pochi, ogni anno'],
        'morti_guerra_anno'    => [0,    1.5,  'milioni/anno','corsa',
            'UCDP/PRIO: Corea ~0,4 milioni l\'anno, Iran-Iraq ~0,1, Russia-Ucraina ~0,1'],
        'durata_governo'       => [4,    9,    'anni',        'corsa',
            'Un esecutivo dura 4-8 anni nelle democrazie competitive e decenni nei sistemi '
            . 'autoritari; con meta\' del mondo non democratico la media globale sta in alto '
            . 'nella forchetta. Il modello faceva 3,3 anni, sotto il minimo democratico'],
        'quota_irregolare'     => [8,    28,   '% dei cambi', 'corsa',
            'Archigos (Goemans, Gleditsch, Chiozza), 188 paesi dal 1875: circa un quinto '
            . 'delle uscite dal potere avviene per via irregolare'],
        'paesi_in_conflitto'   => [15,   45,   'paesi',       'corsa',
            'UCDP 2024: 61 conflitti statali attivi in 36 paesi, il massimo dal 1946'],
        'paesi_in_guerra'      => [3,    32,   'paesi',       'corsa',
            'UCDP 2024: 11 conflitti hanno raggiunto il livello di guerra (oltre mille '
            . 'morti in battaglia nell\'anno). La fascia e\' larga per due ragioni oneste: '
            . 'la nostra soglia e\' un rapporto di forze, non un conto di morti; e misurata '
            . 'su quarant\'anni dal seme questa grandezza oscilla fra 13 e 29 senza divergere'],
        'gradiente_taglia'     => [5,    45,   'punti',       'corsa',
            'Fearon & Laitin (2003), APSR 97(1): la popolazione grande e\' fra i predittori '
            . 'piu\' forti dell\'insorgenza, non fra i protettivi. Quota di paesi in conflitto '
            . 'sopra i 10 milioni MENO quella sotto: nel mondo vero (UCDP 2024) vale circa '
            . '+25 punti. Il modello faceva MENO 21, cioe\' il mondo alla rovescia'],
        'raggruppamento'       => [1.5,  14.0, 'volte',       'corsa',
            'Goldstone et al. (2010), PITF: la prossimita\' a vicini in conflitto e\' uno dei '
            . 'quattro predittori. Quanti confinanti in guerra ha in media un paese in '
            . 'conflitto, diviso quanti ne ha uno in pace: i conflitti si raggruppano, non '
            . 'si spargono a caso. La fascia e\' larga in alto perche\' quando i conflitti '
            . 'sono pochi e vicini il rapporto sale molto: su un seme ha toccato 9'],
    ];

    /**
     * Le grandezze di livello di un mondo, nell'istante in cui lo si guarda.
     *
     * @return array<string,float>
     */
    public static function livelli(Mondo $mondo): array
    {
        $popolazione = 0.0;
        $soldati     = 0.0;
        $spesa       = 0.0;
        $nucleari    = 0;

        foreach ($mondo->elenco() as $n) {
            $popolazione += $n->popolazione;
            $soldati     += $n->soldati;
            $spesa       += $n->pil * $n->quotaMilitare;
            // >= 4 e' «ordigno provato»: sono gli Stati DOTATI, che e' quel che
            // dice il riferimento SIPRI. A >= 3 si conta anche chi ha solo un
            // programma avviato — cioe' l'Iran — e il seme, che e' corretto,
            // sembrava dichiarare dieci potenze nucleari invece di nove.
            $nucleari    += $n->posturaNucleare >= 4 ? 1 : 0;
        }

        $pil = $mondo->pilTotale();

        return [
            'popolazione_mondo' => $popolazione / 1e9,
            'pil_mondo'         => $pil / 1e6,          // il PIL e' in milioni
            'spesa_militare'    => $spesa / 1e3,        // milioni -> miliardi
            'soldati_mondo'     => $soldati / 1e6,
            'nucleari'          => (float) $nucleari,
            'onere_militare'    => $spesa / max(1.0, $pil) * 100,
        ];
    }

    /**
     * Fa girare il mondo a vuoto e misura tutto: i livelli al seme e alla fine,
     * i tassi sull'intera corsa.
     *
     * @return array{seme:array<string,float>, fine:array<string,float>, misure:array<string,float>}
     */
    public static function misura(Mondo $mondo, EsecutoreTick $esecutore, int $anni, int $tickAnno, int $seme): array
    {
        $alSeme = self::livelli($mondo);

        // I morti si accumulano anche nelle guerre che finiscono: contarli solo
        // su quelle aperte a fine corsa direbbe «zero» in un mondo pacificato.
        $guerreViste = [];

        for ($t = 1; $t <= $anni * $tickAnno; $t++) {
            $mondo->tick = $t;
            $esecutore->esegui($t, $seme);
            foreach ($mondo->guerre as $g) {
                $guerreViste[$g['aggressore'] . '>' . $g['difensore'] . '@' . $g['inizio']] = $g['morti'];
            }
        }

        $allaFine = self::livelli($mondo);

        $irregolari = 0;
        $cambi      = 0;
        $conflitto  = 0;
        $guerra     = 0;
        // Il gradiente demografico di Fearon & Laitin: quota di paesi in
        // conflitto fra i grandi contro quella fra i piccoli.
        $grandi = [0, 0];
        $piccoli = [0, 0];
        foreach ($mondo->elenco() as $n) {
            $irregolari += $n->cambiIrregolari;
            $cambi      += $n->cambiEsecutivo;
            $inGuerra    = $n->netPeace >= 4 ? 1 : 0;
            $conflitto  += $inGuerra;
            $guerra     += $n->netPeace >= 5 ? 1 : 0;
            $dove = $n->popolazione >= 1e7 ? 'grandi' : 'piccoli';
            if ($dove === 'grandi') { $grandi[0]++;  $grandi[1]  += $inGuerra; }
            else                    { $piccoli[0]++; $piccoli[1] += $inGuerra; }
        }
        $quotaGrandi  = $grandi[0]  > 0 ? $grandi[1]  / $grandi[0]  : 0.0;
        $quotaPiccoli = $piccoli[0] > 0 ? $piccoli[1] / $piccoli[0] : 0.0;

        // Il raggruppamento geografico: quanti confinanti in conflitto ha in
        // media chi e' in guerra, contro chi e' in pace.
        $vicini = [];
        foreach ($mondo->elenco() as $n) {
            $vicini[$n->iso3] = 0;
        }
        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            if (!$r->confinanti) {
                continue;
            }
            $pezzi = explode('|', $chiave);
            if (count($pezzi) !== 2) {
                continue;
            }
            $altro = $mondo->nazioni[$pezzi[1]] ?? null;
            if ($altro !== null && $altro->netPeace >= 4 && isset($vicini[$pezzi[0]])) {
                $vicini[$pezzi[0]]++;
            }
        }
        $sommaGuerra = [0, 0];
        $sommaPace   = [0, 0];
        foreach ($mondo->elenco() as $n) {
            if ($n->netPeace >= 4) { $sommaGuerra[0]++; $sommaGuerra[1] += $vicini[$n->iso3]; }
            else                   { $sommaPace[0]++;   $sommaPace[1]   += $vicini[$n->iso3]; }
        }
        $mediaGuerra = $sommaGuerra[0] > 0 ? $sommaGuerra[1] / $sommaGuerra[0] : 0.0;
        $mediaPace   = $sommaPace[0]   > 0 ? $sommaPace[1]   / $sommaPace[0]   : 0.0;
        $cambiAnno = $cambi / $anni;

        $misure = $alSeme + [
            'crescita_popolazione' =>
                ((($allaFine['popolazione_mondo'] / max(1e-9, $alSeme['popolazione_mondo'])) ** (1 / $anni)) - 1) * 100,
            'crescita_pil' =>
                ((($allaFine['pil_mondo'] / max(1e-9, $alSeme['pil_mondo'])) ** (1 / $anni)) - 1) * 100,
            'cambi_irregolari'  => $irregolari / $anni,
            // Quanto dura un governo: i paesi diviso i ricambi annui. E' la
            // grandezza che un giocatore percepisce senza doverla calcolare,
            // ed era l'unica del modello fuori da OGNI forchetta reale.
            'durata_governo'    => $cambiAnno > 0 ? count($mondo->nazioni) / $cambiAnno : 0.0,
            'quota_irregolare'  => $cambi > 0 ? $irregolari / $cambi * 100 : 0.0,
            'paesi_in_conflitto' => (float) $conflitto,
            'paesi_in_guerra'    => (float) $guerra,
            // Differenza in PUNTI, non rapporto. Il rapporto fra due
            // proporzioni esplode quando il denominatore e' piccolo: con pochi
            // paesi sotto i dieci milioni in conflitto dava 16 su un seme e 2
            // su un altro, senza che il modello fosse cambiato. La differenza
            // e' stabile e si legge da sola.
            'gradiente_taglia'  => ($quotaGrandi - $quotaPiccoli) * 100.0,
            // Se nessuno e' in pace accanto a una guerra il rapporto non si
            // puo' formare: si dichiara neutro invece di dividere per zero.
            'raggruppamento'    => $mediaPace > 0.0 ? $mediaGuerra / $mediaPace : 1.5,
            'guerre_aperte'     => (float) count($mondo->guerre),
            'morti_guerra_anno' => array_sum($guerreViste) / 1e6 / max(1, $anni),
        ];
        // L'onere militare e' un rapporto: vale quello di fine corsa, non quello
        // del seme, perche' la domanda e' se il motore lo tiene o lo perde.
        $misure['onere_militare'] = $allaFine['onere_militare'];

        return ['seme' => $alSeme, 'fine' => $allaFine, 'misure' => $misure];
    }
}
