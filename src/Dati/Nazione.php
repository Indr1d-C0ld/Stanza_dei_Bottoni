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
        /**
         * Indice di Gini, 0 (uguaglianza perfetta) .. 1. Dalla Banca Mondiale,
         * via db/seed/disuguaglianza.php.
         *
         * E' la disuguaglianza VERTICALE, fra individui. Non tocca le guerre:
         * per l'insorgenza di guerra civile la letteratura la trova non
         * significativa, ed e' quella ORIZZONTALE fra gruppi a contare
         * (Cederman, Weidmann, Gleditsch 2011). Tocca il malcontento.
         */
        public float $disuguaglianza,
        /**
         * Quota di popolazione in gruppi etnici ESCLUSI dal potere esecutivo,
         * da Ethnic Power Relations (ETH Zurigo).
         *
         * E' la disuguaglianza ORIZZONTALE — fra gruppi — e serve a una cosa
         * che quella verticale non sa fare: predire la guerra civile. Cederman,
         * Wimmer e Min (2010) mostrano che il consenso per cui contano solo le
         * opportunita' e non i motivi reggeva perche' si era misurata la
         * disuguaglianza sbagliata.
         *
         * La Siria basta come esempio: 86% della popolazione senza accesso al
         * potere, in quattro gruppi.
         */
        public float $esclusioneEtnica,
        /** Quanti sono quei gruppi: la frammentazione conta oltre alla taglia. */
        public int $gruppiEsclusi,
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
        /**
         * Uno shock esterno di QUESTO tick (un incidente in crisi, un attacco
         * mirato), sulla scala di netPeace. E' volutamente transitorio: lo
         * scrivono le fasi 01 e 02, lo consuma la fase 05 prendendone il
         * massimo con la componente interna, e lo rimette a zero. Non si salva,
         * perche' finito il tick non esiste piu'.
         */
        public int   $scossaEsterna = 0,
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

    /**
     * Quel che consuma il cittadino MEDIANO, non quello medio.
     *
     * L'equazione della legittimita' di Crawford guarda il consumo pro capite,
     * cioe' la media. Ma la media non e' quel che la gente sente: in Sudafrica
     * meta' della popolazione vive con poco piu' della meta' di quel che la
     * media promette, e un governo che festeggia la crescita mentre nessuno la
     * vede e' una delle storie piu' comuni del mondo vero.
     *
     * IL RAPPORTO NON E' INVENTATO, SI DERIVA. Se i redditi si distribuiscono
     * in modo lognormale — l'approssimazione standard — allora
     *
     *     mediana / media = exp(-sigma^2 / 2)      con  G = 2*Phi(sigma/sqrt2) - 1
     *
     * cioe' sigma = sqrt(2) * Phi^-1((1 + G) / 2). Il conto si verifica da se':
     * per gli Stati Uniti (G = 0,418) da' 0,739, e il rapporto vero fra reddito
     * familiare mediano (~75 mila) e medio (~106 mila) e' 0,71.
     *
     *   Slovacchia G 0,238 -> 0,91     Stati Uniti G 0,418 -> 0,74
     *   Norvegia   G 0,265 -> 0,89     Brasile     G 0,503 -> 0,63
     *   Italia     G 0,343 -> 0,82     Sudafrica   G 0,541 -> 0,58
     */
    public function consumoMediano(): float
    {
        return $this->consumoProCapite * $this->quotaMediana();
    }

    /** Il rapporto fra mediana e media implicato dal Gini. */
    public function quotaMediana(): float
    {
        $g = max(0.0, min(0.85, $this->disuguaglianza));
        $sigma = M_SQRT2 * self::phiInversa((1.0 + $g) / 2.0);

        return exp(-$sigma * $sigma / 2.0);
    }

    /**
     * Inversa della normale standard cumulata, algoritmo di Acklam.
     *
     * Serve per ricavare sigma dal Gini. Verificabile: phiInversa(0,975) deve
     * dare 1,9600, che e' il numero che ogni tavola statistica riporta.
     */
    private static function phiInversa(float $p): float
    {
        $a = [-3.969683028665376e+01, 2.209460984245205e+02, -2.759285104469687e+02,
              1.383577518672690e+02, -3.066479806614716e+01, 2.506628277459239e+00];
        $b = [-5.447609879822406e+01, 1.615858368580409e+02, -1.556989798598866e+02,
              6.680131188771972e+01, -1.328068155288572e+01];
        $c = [-7.784894002430293e-03, -3.223964580411365e-01, -2.400758277161838e+00,
              -2.549732539343734e+00, 4.374664141464968e+00, 2.938163982698783e+00];
        $d = [7.784695709041462e-03, 3.224671290700398e-01, 2.445134137142996e+00,
              3.754408661907416e+00];

        $p = max(1e-9, min(1.0 - 1e-9, $p));
        $limite = 0.02425;

        if ($p < $limite || $p > 1.0 - $limite) {
            $q = sqrt(-2.0 * log($p < $limite ? $p : 1.0 - $p));
            $v = ((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5])
               / (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0);

            return $p < $limite ? $v : -$v;
        }

        $q = $p - 0.5;
        $r = $q * $q;

        return ((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q
             / ((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1.0);
    }

    /**
     * C'e' un'insurrezione, o solo il suo residuo? La forza degli insorti
     * decade per moltiplicazione e non arriva mai a zero esatto: con «> 0»
     * un millesimo di uomo bastava a rendere un paese fragile per la
     * dottrina e minacciato per la sua polizia, mentre la fase 05 — che
     * decide la pace — lo contava in pace. Una soglia sola per tutti.
     */
    public function haInsorti(): bool
    {
        return $this->forzaInsorti >= 1.0;
    }

    public function aperturaIstituzionale(): float
    {
        // La base e' il dato V-Dem, che e' una misura vera e aggiornata.
        // Da li' il gioco puo' spostarla: uno Stato che stringe la morsa
        // sull'informazione si chiude davvero, ed e' una delle cose che un
        // giocatore fa. La repressione poliziesca vive su scala ~1..5, non
        // 0..100: normalizzarla a cento la faceva contare zero, ed e' l'errore
        // che ha fatto risultare il mondo senza nemmeno un'autocrazia.
        $strettaInfo    = max(0.0, ($this->controlloInfo - 50.0) / 50.0);
        $strettaPolizia = max(0.0, ($this->statoPolizia - 2.0) / 3.0);   // 5 = morsa piena
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
