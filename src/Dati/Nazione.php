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
        /**
         * SVILUPPO, non stato di diritto — malgrado il nome.
         *
         * Nasce come «la maturita' istituzionale che Crawford confessa di aver
         * inventato», ma l'importatore la ricava da reddito e alfabetizzazione
         * e MISURATO correla 0,989 col logaritmo del reddito pro capite: non e'
         * una misura delle istituzioni, e' il reddito con un'altra faccia.
         * Con l'indice di democrazia di V-Dem correla appena 0,520.
         *
         * Serve ancora, e va bene dov'e' rimasta: capacita' industriale,
         * solidita' di un apparato, competenza di un servizio. Dove invece si
         * intendevano le ISTITUZIONI — chi va alle urne, quando un ricambio e'
         * irregolare, quanto un regime stringe sull'informazione, chi si
         * disarma — adesso decide `democrazia`.
         *
         * E non va moltiplicata per il reddito: era il reddito due volte, e in
         * un caso tre.
         */
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
        // La quota militare di partenza: e' l'ancora dell'obiettivo di spesa.
        // Senza, il bersaglio del motore e' una costante universale e il mondo
        // converge a un onere militare che non e' quello di nessuno.
        public float $quotaMilitareIniziale,
        // Gli uomini di partenza: e' il bersaglio verso cui l'esercito si
        // ricostruisce. Senza, l'attrito li toglieva e nessuna fase li
        // rimpiazzava — un esercito logorato rimpiccioliva PER SEMPRE.
        public float $soldatiIniziali,
        /**
         * Indice di democrazia liberale, 0 (autocrazia piena) .. 1 (piena).
         * Viene da V-Dem (db/seed/democrazia.php). E' l'unico asse
         * democrazia-autocrazia che il motore abbia: `maturita` e' marcata
         * SEGNAPOSTO e si ricava da reddito e alfabetizzazione, quindi mette
         * Singapore accanto alla Norvegia; `ideologiaFormale` e' la
         * descrizione giuridica che ogni Stato da' di se stesso.
         */
        public float $democrazia,
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
    /**
     * Apertura istituzionale, da 0 (autocrazia piena) a 1 (democrazia piena).
     *
     * NON viene da ideologiaFormale. Quella e' la descrizione giuridica che
     * ogni Stato da' di se stesso, e centoquarantasei paesi su centottantanove
     * si dichiarano democrazie liberali: l'importatore stesso avverte che
     * «serve per il colore, non per il modello».
     *
     * Viene invece da come lo Stato TRATTA i propri cittadini — quanto
     * reprime e quanto controlla cio' che possono sapere — che e' la sostanza
     * di quel che Polity misura e che il Political Instability Task Force usa
     * per classificare i regimi.
     */
    /** Sotto questa apertura il regime e' un'autocrazia piena: reprime e tiene. */
    private const AUTOCRAZIA_PIENA = 0.05;

    /** Il vertice dell'arco: la democrazia parziale, quella che salta. */
    private const PARZIALE_MASSIMO = 0.25;

    /** Sopra questa apertura il regime incanala il dissenso invece di subirlo. */
    private const DEMOCRAZIA_PIENA = 0.55;

    public function aperturaIstituzionale(): float
    {
        // La base e' il dato V-Dem, che e' una misura vera e aggiornata.
        // Da li' il gioco puo' spostarla: uno Stato che stringe la morsa
        // sull'informazione si chiude davvero, ed e' una delle cose che un
        // giocatore fa. La repressione poliziesca vive su scala ~1..5, non
        // 0..100: normalizzarla a cento la faceva contare zero, ed e' l'errore
        // che ha fatto risultare il mondo senza nemmeno un'autocrazia.
        $strettaInfo    = max(0.0, ($this->controlloInfo - 50.0) / 50.0);
        $strettaPolizia = max(0.0, ($this->statoPolizia - 2.0) / 6.0);
        $chiusura = 0.35 * $strettaInfo + 0.25 * min(1.0, $strettaPolizia);

        return max(0.0, min(1.0, $this->democrazia - $chiusura));
    }

    /**
     * Quanto questo regime sta nella parte PARZIALE dell'arco: 0 agli estremi
     * (autocrazia piena o democrazia piena), 1 nel mezzo.
     *
     * E' l'ingrediente della U rovesciata di Goldstone et al. (2010): il
     * rischio d'instabilita' non cresce ne' cala con l'apertura, ha un massimo
     * in mezzo. Le autocrazie piene reprimono il dissenso, le democrazie piene
     * lo incanalano; e' il mezzo — istituzioni aperte abbastanza da far
     * competere ma non abbastanza da far perdere senza perdere tutto — che
     * salta.
     */
    public function regimeParziale(): float
    {
        $a = $this->aperturaIstituzionale();

        // Gli ancoraggi NON sono 0, 0,5 e 1. L'indice di V-Dem non e' lineare
        // come la scala Polity che PITF usa: e' compresso, e mezzo punto non
        // e' «meta' democrazia». Guardando dove cadono i paesi veri:
        //
        //   autocrazie piene  Corea del Nord 0,014 · Cina 0,039 · Russia 0,056
        //   parziali          Turchia 0,110 · India 0,260 · Ungheria 0,315 ·
        //                     Singapore 0,360
        //   democrazie piene  Stati Uniti 0,571 · Italia 0,642 · Norvegia 0,847
        //
        // Col vertice ingenuo a 0,5 gli Stati Uniti risultavano «piu' parziali»
        // di Singapore, che e' esattamente il contrario di quel che il modello
        // deve dire.
        if ($a <= self::AUTOCRAZIA_PIENA || $a >= self::DEMOCRAZIA_PIENA) {
            return 0.0;
        }
        if ($a <= self::PARZIALE_MASSIMO) {
            return ($a - self::AUTOCRAZIA_PIENA)
                 / (self::PARZIALE_MASSIMO - self::AUTOCRAZIA_PIENA);
        }

        return (self::DEMOCRAZIA_PIENA - $a)
             / (self::DEMOCRAZIA_PIENA - self::PARZIALE_MASSIMO);
    }

    public function rapportoForze(): float
    {
        $insorti = max(0.001, $this->forzaInsorti);
        return $this->potenzaGoverno() / $insorti;
    }
}
