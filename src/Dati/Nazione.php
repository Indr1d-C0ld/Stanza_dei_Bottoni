<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Lo stato di una nazione dentro un tick.
 *
 * Proprietà pubbliche e mutabili: questo oggetto viene toccato decine di
 * migliaia di volte per esecuzione, e l'incapsulamento qui costerebbe soltanto.
 * La disciplina sta nelle fasi, non negli accessori.
 */
final class Nazione
{
    public function __construct(
        public string $iso3,
        public string $nome,
        public string $regione,
        public string $ideologiaFormale,
        public int    $maturita,
        public int    $valoreStrategico,
        public int    $valorePrestigio,
        // Serve al commercio: chi ha molta terra e poca gente vende cibo ed
        // energia, chi ne ha poca e molta li compra. Dato fermo come la
        // regione, non stato che cambia.
        public int    $areaKm2,
        public bool   $giocabile,

        // economia
        public float $popolazione,
        public float $crescitaPopolazione,
        public float $pil,                 // milioni
        public float $crescitaPil,
        public float $crescitaStrutturale,
        public float $crescitaBase,
        public float $quotaInvestimentiIniziale,
        public float $pilProCapite,
        public float $consumoProCapite,
        public float $quotaConsumi,
        public float $quotaInvestimenti,
        public float $quotaMilitare,

        // societa
        public float $legittimita,
        public float $aspettativa,
        public float $clamoreSociale,
        public int   $qualitaVita,
        /**
         * Quanto e' stretta la presa del governo: 1 libero, 5 terrore.
         *
         * E' un numero CON I DECIMALI, e non per pedanteria. Da intero
         * restava fermo per sempre: il movimento di un tick e' una frazione,
         * l'arrotondamento se la mangiava, e il valore tornava identico.
         * Centottantanove paesi hanno avuto la stessa polizia per quindici
         * anni di gioco senza che niente segnalasse il problema.
         */
        public float $statoPolizia,
        /**
         * Quanta paura militare c'e' in giro, 0-100.
         *
         * Con i decimali: il decadimento vale una frazione di punto per tick,
         * e da intero l'arrotondamento la cancellava. E' il terzo campo di
         * questo modello a essere stato trovato fermo per la stessa ragione.
         */
        public float $ansiaMilitare,
        /** Quanto il governo tiene in mano il racconto pubblico, 0-100. */
        public float $controlloInfo,
        /**
         * Quanto e' difeso il traffico di un paese, 0-100.
         *
         * Con i decimali, per la stessa ragione di statoPolizia: il movimento
         * di un tick e' una frazione, e da intero l'arrotondamento la
         * cancellava. Restava al valore del seme per sempre — e questo e' il
         * numero da cui dipende se i messaggi di un paese si possono leggere
         * e riscrivere, cioe' meta' del gioco fra giocatori.
         */
        public float $cyberDifesa,
        public int   $etica,
        public int   $ambizione,
        public int   $orientamento,

        // sicurezza
        public float $soldati,
        public float $equipaggiamento,
        public float $forzaInsorti,
        public int   $netPeace,
        public int   $posturaNucleare,
        public float $alfabetizzazione,
        /** Influenza totale: quota del potere mondiale, economico e militare. */
        public float $influenzaTotale = 0.0,
        /** Integrità come garante, 0..128 (Balance of Power). Si perde in
         *  proporzione all'obbligo quando cade un cliente, e risale piano. */
        public float $integrita = 128.0,
        /**
         * Deriva politica: quanto il livello attorno a cui oscilla la
         * legittimità di questo paese si discosta dalla media mondiale.
         * È qualità di governo, fortuna, coesione — le cose che nessun dato
         * cattura e che pure decidono se un paese fragile regge o cede.
         * Vaga lentamente, e un cambio al vertice la riscrive.
         */
        public float $derivaPolitica = 0.0,
        /** Pressione esterna subita (sanzioni, embarghi): frena la crescita e decade. */
        public float $pressioneEsterna = 0.0,
        // Le quattro dipendenze contemporanee. Restano a zero finche' non
        // importeremo la matrice commerciale: le colonne esistono perche' il
        // modello le prevede, non perche' siano gia' popolate.
        /** Quanti eventi questa nazione ha in volo: limita quanto puo' fare insieme. */
        public int   $azioniInVolo = 0,
        /** Quante operazioni sporche attribuibili si porta sulle spalle. */
        public float $reputazioneSporca = 0.0,

        // contatori di storia
        public float $consumoProCapitePrec = 0.0,
        public int   $cambiEsecutivo = 0,
        public int   $cambiIrregolari = 0,
        /** Tick della prossima consultazione, 0 se il paese non ne ha. */
        public int   $prossimaElezione = 0,
        public int   $mandatoTick = 0,
        public int   $scandaliSubiti = 0,
        public int   $annoUltimoCambio = 0,
        public int   $vittorieInsorti = 0,
    ) {}

    /**
     * Potenza militare: media geometrica di uomini ed equipaggiamento.
     *
     * Riproduce la proprietà voluta da Crawford — con 100 soldati e 2 unità di
     * equipaggiamento, un soldato in più non dà quasi nulla, un'arma in più dà
     * molto. Serve un equilibrio fra i due, e l'eccesso dell'uno non compensa
     * la penuria dell'altro.
     */
    public function potenzaGoverno(): float
    {
        return sqrt(max(0.0, $this->soldati) * max(0.0, $this->equipaggiamento));
    }

    /** Capacità difensiva cibernetica, normalizzata a 0..0,3. */
    public function cyberDifesaNormalizzata(): float
    {
        return min(0.3, $this->cyberDifesa / 333.0);
    }

    /** Rapporto di forze governo/insorti: è su questo che stanno le soglie. */
    public function rapportoForze(): float
    {
        $insorti = max(0.001, $this->forzaInsorti);
        return $this->potenzaGoverno() / $insorti;
    }
}
