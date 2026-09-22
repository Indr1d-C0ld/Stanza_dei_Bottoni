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
        'nucleari'             => [8,    14,   'Stati',       'seme',
            'SIPRI Yearbook: nove Stati dotati di armi nucleari'],

        // --- tassi e rapporti: come si comporta il motore ---------------------
        'crescita_popolazione' => [0.6,  1.2,  '%/anno',      'corsa',
            'ONU, World Population Prospects 2024: il mondo cresce dello 0,9% l\'anno'],
        'crescita_pil'         => [2.0,  4.0,  '%/anno',      'corsa',
            'Banca Mondiale: 2,9% nel 2024, ~3,4% medio dal 2000 in PPA'],
        'onere_militare'       => [1.8,  3.2,  '% del PIL',   'corsa',
            'SIPRI 2024: 2,5% del prodotto mondiale'],
        'cambi_irregolari'     => [5,    16,   '/anno',       'corsa',
            'Crawford, Balance of Power: circa dieci cambi di governo irregolari l\'anno'],
        'guerre_aperte'        => [0,    8,    'in corso',    'corsa',
            'UCDP: i conflitti interstatali attivi sono pochi, ogni anno'],
        'morti_guerra_anno'    => [0,    1.5,  'milioni/anno','corsa',
            'UCDP/PRIO: Corea ~0,4 milioni l\'anno, Iran-Iraq ~0,1, Russia-Ucraina ~0,1'],
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
            $nucleari    += $n->posturaNucleare >= 3 ? 1 : 0;
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
        foreach ($mondo->elenco() as $n) {
            $irregolari += $n->cambiIrregolari;
        }

        $misure = $alSeme + [
            'crescita_popolazione' =>
                ((($allaFine['popolazione_mondo'] / max(1e-9, $alSeme['popolazione_mondo'])) ** (1 / $anni)) - 1) * 100,
            'crescita_pil' =>
                ((($allaFine['pil_mondo'] / max(1e-9, $alSeme['pil_mondo'])) ** (1 / $anni)) - 1) * 100,
            'cambi_irregolari'  => $irregolari / $anni,
            'guerre_aperte'     => (float) count($mondo->guerre),
            'morti_guerra_anno' => array_sum($guerreViste) / 1e6 / max(1, $anni),
        ];
        // L'onere militare e' un rapporto: vale quello di fine corsa, non quello
        // del seme, perche' la domanda e' se il motore lo tiene o lo perde.
        $misure['onere_militare'] = $allaFine['onere_militare'];

        return ['seme' => $alSeme, 'fine' => $allaFine, 'misure' => $misure];
    }
}
