<?php

declare(strict_types=1);

/**
 * Calibrazione BASE — parametri condivisi dai due profili.
 *
 * REGOLA: se lo ritocca un progettista sta qui (in git, diffabile);
 *         se lo cambia il gioco sta nel database.
 *
 * Ogni voce marcata [FABBRICATO] non deriva da nessuna fonte: è una scelta
 * d'autore, esattamente come l'indice di maturità istituzionale che Crawford
 * confessa di aver inventato. Vanno dichiarate, non nascoste.
 */

return [
    'versione' => '0.1.0',

    // ---------------------------------------------------------------- tempo
    'tempo' => [
        // NOTA: qui c'era 'giorni_per_tick' = 7. Tolta: diceva la stessa cosa
        // di 'tick_per_anno' qui sotto (cinquantadue settimane fanno un anno) e
        // nessuno la leggeva. Il numero vive in Calendario::GIORNI_PER_TICK,
        // dove serve; due numeri per un vincolo solo si scordano di essere
        // allineati.
        'tick_per_anno'   => 52,
    ],

    // ------------------------------------------------------------ economia
    // Modello a tre pressioni di Balance of Power, più commercio e dipendenze.
    'economia' => [
        // Sotto questa quota di investimenti il capitale si consuma più in
        // fretta di quanto lo rinnovi: crescita negativa. (BoP: ~12% del PIL)
        'soglia_investimento'      => 0.12,
        // Resa marginale dell'investimento oltre la soglia, per anno.
        'resa_investimento'        => 0.32,
        // Tetto alla crescita annua. Shadow President cappava intorno al 10%:
        // senza un tetto il modello si autoalimenta e diverge. [FABBRICATO]
        'crescita_max_anno'        => 0.10,
        'crescita_min_anno'        => -0.15,
        // Quanto la pressione dei consumi risponde a una legittimita' bassa.
        'pressione_consumi_k'      => 1.0,
        'pressione_investimenti_k' => 0.35,
        'pressione_militare_k'     => 1.0,
        // NOTA: qui c'erano 'peso_commercio' e 'peso_dipendenza'. Sono state
        // tolte, non dimenticate. Esprimevano un modello — il saldo
        // commerciale che muove la crescita — che e' stato provato e scartato:
        // legarlo alla crescita portava i colpi di Stato irregolari da 11,9 a
        // 18,1 l'anno, perche' nel nostro grafo tutti i paesi poveri risultano
        // in disavanzo cronico. Il racconto sta in docs/21. Il commercio agisce
        // sulla crescita per un'altra strada, le strozzature, che ha le sue
        // manopole sotto 'commercio'.
    ],

    // ------------------------------------------------------------ società
    'societa' => [
        // [FABBRICATO] quanto in fretta passa la paura militare, per anno.
        // Prima non passava affatto.
        'sfogo_ansia_anno'      => 14.0,
        // Crawford usava una costante globale (-3, tarata a playtest).
        // Noi la rendiamo mobile: la gente si aspetta cio' che ha avuto di
        // recente. Finestra della media mobile, in anni.
        'finestra_aspettativa'  => 5,
        'aspettativa_minima'    => 0.005,
        'aspettativa_massima'   => 0.085,
        // Inerzia della legittimita': quanto pesa l'anno precedente.
        'inerzia_legittimita'   => 0.85,
        // Bonus di radicalita' (governi estremi reprimono il dissenso).
        'bonus_radicalita'      => 1.0,
        'rientro_ansie'         => 0.10,   // le ansie decadono verso la media
        // La deriva politica: quanto forte la si riconduce a zero (per anno) e
        // con quanta ampiezza vaga. Insieme determinano quanto due corse dello
        // stesso mondo possono divergere. [FABBRICATO]
        'ritorno_deriva'        => 0.15,
        'ampiezza_deriva'       => 1.1,
    ],

    // -------------------------------------------------- sicurezza interna
    // Soglie di Balance of Power sul rapporto forza governo / insorti.
    'insurrezione' => [
        'soglia_pace'       => 512.0,
        'soglia_terrorismo' => 32.0,
        'soglia_guerriglia' => 2.0,
        'soglia_guerra_civile' => 1.0,
        // Attrito annuo: ciascuno toglie all'altro un quarto della PROPRIA forza.
        'attrito_anno'      => 0.25,
        // Le armi consegnate agli insorti valgono il doppio: le usano meglio.
        'moltiplicatore_armi_insorti' => 2.0,
        'effetto_carrozzone' => 0.20,
        // Quanta potenza insurrezionale genera il malcontento, per radice di
        // popolazione e per anno. [FABBRICATO] — e' il parametro che decide se
        // il mondo ha guerre civili o se non ne ha mai: da ritarare per primo.
        'reclutamento_k'     => 1.6,
    ],

    'colpo_di_stato' => [
        // La destabilizzazione SI SOMMA alla soglia, non la sostituisce:
        // non puoi far cadere un governo che reggerebbe comunque.
        //
        // Crawford usava 0 perché la sua popolarità andava da 1 a 20. La nostra
        // va da 0 a 100 e si assesta intorno a 50: la soglia equivalente non è
        // zero, è circa un quinto della scala. Tradurre un modello significa
        // anche tradurne le scale, ed è il genere di errore che passa inosservato.
        'soglia_legittimita'     => 38.0,
        // NOTA: qui c'era 'peso_destabilizzazione'. Tolta: il verbo
        // «destabilizzare» agisce gia' su legittimita' e clamore sociale, che
        // sono gli ingressi del rischio di colpo di Stato. Un secondo peso che
        // dicesse la stessa cosa sarebbe una manopola da tenere allineata a
        // mano con la prima.
        'resistenza_estremisti'  => 2.0,
        // Il rischio di cadere e' una curva logistica sulla legittimita', non
        // un cancello: massimo annuo quando la legittimita' e' a zero, e
        // pendenza della curva. A legittimita' pari al "centro" il rischio e'
        // meta' del massimo. [FABBRICATO]
        'rischio_massimo_anno'   => 1.6,
        'pendenza'               => 7.0,
    ],

    // ----------------------------------------------------------- relazioni
    'relazioni' => [
        // Tabella degli obblighi di trattato (BoP, invariata).
        // A che affinita' scatta ciascun gradino della tavola qui sotto.
        //
        // Sono tarate sull'affinita' che il mondo produce DAVVERO, non sulla scala
        // teorica: il massimo osservato dopo quindici anni e' 104, non 127, perche'
        // la deriva delle relazioni comprime gli estremi verso l'ancora storica.
        // Con le soglie tarate sul 127 il gradino piu' alto non si raggiungeva mai.
        'soglie_obbligo'    => [
            'difesa_nuc'   => 100,
            'difesa_conv'  => 88,
            'basi'         => 72,
            'commerciali'  => 50,
            'diplomatiche' => 22,
        ],

        'obbligo' => [
            'nessuna'       => 0,
            'diplomatiche'  => 16,
            'commerciali'   => 32,
            'basi'          => 64,
            'difesa_conv'   => 96,
            'difesa_nuc'    => 128,
        ],
        // L'integrità cala in proporzione all'obbligo quando un cliente cade,
        // e risale di poco per tick. Crawford usava +5/anno.
        'recupero_integrita_anno' => 5.0,
        // [FABBRICATO] quanto in fretta il mondo dimentica chi ha vinto una
        // crisi: a 0.12 l'anno, meta' del vantaggio e' svanito in sei anni.
        'consumo_spinta_anno'     => 0.12,
        // La storia pesa 8 volte l'ideologia nel determinare l'affinità.
        'peso_storia_su_ideologia' => 8.0,
    ],

    // -------------------------------------------------------------- crisi
    // [FABBRICATO] le linee dirette fra capitali.
    // [FABBRICATO] il commercio e le sue armi.
    'commercio' => [
        // Quanto dura una strozzatura prima di sciogliersi da sola. Un embargo
        // regge un anno di gioco; le restrizioni molto meno, perche' sono un
        // segnale piu' che un'arma.
        'durata_embargo'     => 52,
        'durata_restrizioni' => 18,
        // Quanto morde, in punti di crescita annua, un flusso tagliato del
        // tutto e insostituibile. E' la scala di tutto il resto.
        'morso'              => 0.42,
        // Perdere un mercato costa meno che restare senza fornitore: un
        // compratore si ritrova piu' facilmente di un giacimento.
        'quota_fornitore'    => 0.45,
        // Si puo' strangolare un paese, non annientarlo: il tetto vale per
        // chiunque, anche per chi ha il mondo intero contro.
        'tetto_pressione'    => 0.085,
    ],

    // Chi puo' registrarsi: aperte, invito, chiuse. Sta qui e non solo nella
    // configurazione perche' e' una leva che l'arbitro muove a mondo acceso, e
    // le leve passano tutte dalla calibrazione.
    // [FABBRICATO] la repressione interna.
    // [FABBRICATO] la proliferazione, che prima non esisteva: posturaNucleare
    // non veniva scritta da nessuna fase e nessuno prendeva ne' posava la bomba.
    // Tarato perche' nel mondo se ne armi circa uno ogni quindici anni, che e'
    // l'ordine di grandezza storico.
    'nucleare' => [
        'rateo_proliferazione_anno' => 0.0022,
        'rateo_disarmo_anno'        => 0.0016,
        'soglia_armato'             => 3,
        // Sotto questa volonta' non si prova nemmeno: la capacita' da sola non
        // arma nessuno, o il modello produce la Svizzera atomica.
        'soglia_volonta'            => 0.14,
    ],

    'sicurezza' => [
        // Quanto in fretta un governo stringe o allenta la presa, per anno.
        'risposta_polizia_anno' => 0.55,
        // Sopra quanta minaccia si comincia a stringere. La minaccia somma
        // clamore sociale, deficit di legittimita' e presenza di insorti.
        'soglia_repressione'    => 28.0,
    ],

    'gioco' => [
        'registrazioni' => 'invito',
    ],

    'linee' => [
        // Quanta affinità serve perché un gabinetto retto dall'apparato accetti
        // di aprire un canale dedicato. Non è un'alleanza, ma è pubblico.
        'soglia_affinita' => 20.0,
    ],

    'crisi' => [
        // Probabilità di incidente per gradino, dal 6 in su, modulata dalla
        // Nastiness globale. [FABBRICATO]
        'incidente_base'      => [6 => 0.002, 7 => 0.006, 8 => 0.015, 9 => 0.040],
        'peso_nastiness'      => 1.5,
        'decadimento_nastiness' => 0.02,   // per tick
        // La scala di escalation: dieci gradini, e da sei in su ogni passo
        // porta con se' una probabilita' di incidente.
        'gradini' => [
            1 => 'nota riservata',            2 => 'protesta pubblica',
            3 => 'sede multilaterale',  4 => 'misure economiche mirate',
            5 => 'embargo e richiamo degli ambasciatori',
            6 => 'allerta militare',          7 => 'dimostrazione di forza',
            8 => 'uso limitato della forza',  9 => 'guerra aperta',
        ],
        // Quanto cresce la posta a ogni gradino, e ogni quanti tick una crisi
        // senza risposta si chiude da sola.
        // [FABBRICATO] le due forze opposte che tengono in piedi una crisi.
        // L'impegno: chi e' salito ha gia' molta faccia impegnata e cedere ora
        // costa piu' di prima. La paura: in fondo alla scala c'e' una guerra
        // vera. Calibrate perche' la rottura mediana cada al terzo gradino e il
        // nono si raggiunga in una crisi su trecento, incidenti esclusi.
        'peso_impegno'    => 0.18,
        'peso_paura'      => 28.0,
        // La paura nucleare non si divide per il rapporto di forze: contro chi
        // ha la bomba la superiorita' convenzionale non vale niente, ed e'
        // esattamente questo che tiene ferme le mani in cima alla scala.
        'peso_nucleare'      => 45.0,
        'esponente_nucleare' => 4.0,
        'tetto_forze'        => 2.0,
        'esponente_paura' => 2.4,
        'reluttanza'      => 2.0,
        'crescita_posta'  => 0.38,
        'pazienza_tick'   => 3,
    ],

    // ---------------------------------------------------------- elezioni
    'elezioni' => [
        // Sotto questa maturita' istituzionale non si vota davvero: resta solo
        // la via irregolare. [FABBRICATO]
        'maturita_minima' => 130.0,
        // Curva del ricambio: a legittimita' pari al centro l'uscente ha una
        // probabilita' su due di perdere.
        'centro'          => 52.0,
        'pendenza'        => 9.0,
        // Crisi di governo fra un voto e l'altro, massimo annuo. [FABBRICATO]
        'rischio_crisi_anno' => 1.8,
    ],

    // ---------------------------------------------------------- dottrina
    // Quanto e' intraprendente il mondo quando non ci sono giocatori. Con i
    // giocatori questa macchina governa solo le nazioni non presidiate.
    'dottrina' => [
        'attivita'             => 0.5,   // moltiplicatore generale [FABBRICATO]
        // [FABBRICATO] la durata tipica di un'operazione coperta, in tick. Serve
        // a normalizzare la probabilita' di sventarla: la prova si ripete a
        // ogni giro, e senza questo un'azione lenta non arrivava mai in fondo.
        'durata_di_riferimento' => 4.5,
        'azioni_in_volo_max'   => 4,     // quante operazioni insieme per Stato
    ],

    // ------------------------------------------------------- intelligence
    'intelligence' => [
        // Il livello 4 (attribuzione) è un ordine di grandezza più difficile
        // degli altri tre. È il perno dell'intero design.
        // I primi tre livelli — gli indizi sul "dove" — sono generosi: nel
        // gioco originale il Tracer ne sforna in continuazione e la mappa dei
        // bersagli possibili si restringe da sola. Il quarto e' il muro.
        'difficolta_livello' => [1 => 6.0, 2 => 5.0, 3 => 3.5, 4 => 3.0],
        // [FABBRICATO] quanto rende concentrare i mezzi su un filo solo invece
        // di ascoltare tutto. E' il moltiplicatore che rende possibile leggere
        // un messaggio per intero, e quindi poterlo riscrivere.
        'concentrazione_mirata' => 2.2,
        // [FABBRICATO] le difese informatiche, che prima non si muovevano.
        // Quanto in fretta un paese raggiunge il livello di difesa che le sue
        // istituzioni e i suoi soldi gli consentono.
        'manutenzione_difese_anno' => 0.35,
        // Quanto costa, in punti di difesa, essere letti per intero senza
        // accorgersene: la strada che l'altro ha trovato resta aperta.
        'costo_violazione'         => 0.9,
        // E quanto si guadagna accorgendosi di una manomissione: si tappa il
        // buco, e si esce piu' forti di prima.
        'premio_scoperta'          => 2.5,
        'decadimento_copertura' => 0.01,   // per tick, se non finanziata
        // NOTA: qui c'era 'decadimento_rapporto'. Tolta: i rapporti non
        // decadono, si tagliano — la fase 08 ne tiene gli ultimi
        // millecinquecento e butta il resto. Una chiave che promette un
        // comportamento assente e' peggio di nessuna chiave.
        // Tetto di operazioni simultanee per servizio: il collo di bottiglia
        // non è il denaro, è il numero di operazioni che puoi condurre.
        // NOTA: qui c'era 'operazioni_max' = 6. Tolta perche' diceva una cosa
        // falsa: il tetto alle operazioni in volo esiste davvero, si chiama
        // 'dottrina.azioni_in_volo_max' e vale 4. Chi leggeva questa credeva
        // che fosse sei.
        // NOTA: qui c'era 'sonde_per_bersaglio_tick'. Tolta: non esiste un
        // limite di sonde per bersaglio nel modello, e dichiararlo faceva
        // credere il contrario.
    ],
];
