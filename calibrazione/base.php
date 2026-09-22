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

        // --- dove punta il motore ------------------------------------------
        // Le tre quote convergono verso un obiettivo. Se quell'obiettivo e'
        // una costante universale, ogni paese si stacca dalla propria storia:
        // gli investimenti salivano dal 22% al 29% del prodotto per TUTTI, e
        // siccome la crescita premia chi investe piu' del proprio solito, il
        // mondo incassava un punto di crescita l'anno che nessuno aveva
        // guadagnato. L'obiettivo ruota invece intorno alla quota di partenza
        // del paese, e le pressioni lo spostano da li'.
        //
        // La spinta neutra e' il valore che la pressione assume in un paese
        // tranquillo al tick 0: mettendola qui, al tick 0 l'obiettivo coincide
        // con la quota osservata e nessuno parte in guadagno.
        'spinta_investimenti_neutra' => 0.71,
        'ampiezza_investimenti'      => 0.16,
        // I due punti neutri NON si deducono: si misurano sul seme, perche'
        // sono il valore che la spinta assume quando il mondo sta fermo al
        // tick 0. Messi a occhio, il mondo parte in guadagno o in perdita.
        'spinta_militare_neutra'     => 0.21,
        'ampiezza_militare'          => 0.09,
        // Quanto in fretta la tendenza di crescita torna alla propria base dopo
        // un programma di investimenti. Era 0,10 l'anno, cioe' piu' lenta del
        // ritmo con cui gli eventi la alzavano: saliva e non tornava piu'
        // (3,56% -> 4,82% in quindici anni, un mondo che cresce del 4% l'anno
        // contro il 3% storico).
        'rientro_strutturale'        => 0.60,
        // E comunque un programma di investimenti non puo' spostare la
        // tendenza di un paese di piu' di due punti: oltre, non e' politica
        // economica, e' fantasia.
        'margine_strutturale'        => 0.02,
        // Quanto in fretta un esercito logorato torna alla propria taglia.
        // Un terzo dello scarto all'anno: un paese che ha perso meta' degli
        // effettivi ne recupera la maggior parte in tre-quattro anni, che e'
        // il ritmo con cui si addestra e si inquadra della gente vera.
        'recupero_uomini_anno'       => 0.35,
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
        // Probabilita' annua che gli insorti, una volta piu' forti
        // dell'esercito, prendano DAVVERO il potere. Non e' uno: prevalere sul
        // campo non e' prendere la capitale, e fra i conflitti armati che
        // finiscono la vittoria dei ribelli e' l'esito piu' raro. Prima era
        // implicitamente 1 — il governo cadeva lo stesso tick in cui il
        // rapporto di forze si ribaltava — e le guerre civili non duravano.
        'vittoria_insorti_anno' => 0.22,
        // Quanta potenza insurrezionale genera il malcontento, per radice di
        // Quanta potenza insurrezionale genera il malcontento, PER ABITANTE e
        // per anno. E' il parametro che decide se il mondo ha guerre civili o
        // se non ne ha mai.
        //
        // Era 1,6 e moltiplicava la RADICE della popolazione, mentre la potenza
        // del governo cresce linearmente con essa: il rapporto fra le due
        // scalava come 1/radice(P) e i paesi piccoli risultavano
        // sistematicamente piu' insorti dei grandi. Misurato sul mondo vivo:
        // 29% dei paesi sotto il milione di abitanti in conflitto armato,
        // contro 0% di quelli sopra i duecento milioni. Monotono e rovesciato.
        //
        // Adesso moltiplica la popolazione e il moltiplicatore di poverta' di
        // Fearon & Laitin, e 3,0e-4 e' il valore che fa cadere il mondo sui
        // riferimenti UCDP del 2024: 61 conflitti statali attivi in 36 paesi,
        // di cui 11 arrivati al livello di guerra.
        //
        // A 1,0e-3 il profilo osservazione ne fa 26 a livello >= 4 e 9 a >= 5,
        // il profilo gioco 27 e 11: le guerre cadono sul riferimento, i paesi
        // in conflitto restano sotto perche' la nostra soglia di «guerriglia»
        // e' piu' alta dei venticinque morti l'anno con cui UCDP apre un
        // conflitto minore.
        //
        // E soprattutto i paesi che nomina sono quelli giusti: Congo, Sudan,
        // Somalia, Sud Sudan, Mozambico, Burkina Faso, Niger, Nigeria,
        // Afghanistan, Yemen, Centrafrica, Haiti stanno davvero nell'elenco
        // UCDP. Il modello non ha ne' etnie ne' storia ne' geografia: ci
        // arriva con reddito, popolazione, legittimita' e maturita'
        // istituzionale, che e' esattamente quel che Fearon e Laitin dicono
        // basti.
        'reclutamento_k'     => 1.0e-3,
    ],

    // ------------------------------------------------------- guerre fra Stati
    // I morti di una guerra non sono una funzione libera della potenza: sono
    // una frazione degli uomini che il fronte toglie davvero dai ruoli.
    // Riferimenti storici usati per tarare (morti annui di UNA guerra
    // bilaterale): Iran-Iraq 1980-88 ~50-150 mila/anno, Corea 1950-53
    // ~400 mila/anno, fronte orientale 1941-45 alcuni milioni/anno.
    'conflitto' => [
        // Di ogni soldato tolto dai ruoli, quanti muoiono. Il resto e' ferito,
        // prigioniero o disperso: il rapporto caduti/perdite totali sta intorno
        // a uno su tre da Verdun in poi.
        'quota_caduti'        => 0.33,
        // Civili morti per ogni militare caduto. Eckhardt, ripreso dal CICR:
        // la quota civile dei morti di guerra resta intorno al 50% da tre
        // secoli, cioe' circa un civile per militare. Nelle guerre totali sale
        // a 2 (seconda guerra mondiale), nelle guerre aeree asimmetriche
        // scende sotto 0,2: uno non copre l'altro, e la media e' la scelta
        // meno sbagliata per un modello che non distingue i tipi di guerra.
        'civili_per_militare' => 1.0,
    ],

    // --------------------------------------------- instabilita' politica
    // GOLDSTONE, BATES, EPSTEIN, GURR, LUSTIK, MARSHALL, ULFELDER, WOODWARD
    // (2010), «A Global Model for Forecasting Political Instability»,
    // American Journal of Political Science 54(1): 190-208.
    //
    // Quattro predittori, 81,7% di accuratezza a due anni su tutte le
    // instabilita' del mondo dal 1955 al 2003 — e la conclusione va contro
    // l'intuito: sono le ISTITUZIONI a predire, non l'economia, non la
    // demografia, non la geografia.
    //
    // Fonte contemporanea e viva: il PITF e' stato organizzato nel 1994 e il
    // modello e' del 2010. Non ha il problema d'epoca del World Handbook.
    'instabilita' => [
        // Di quanti punti di legittimita' un regime parziale «anticipa» la
        // soglia di caduta. NON e' un moltiplicatore del rischio: sposta il
        // centro della logistica, perche' un fattore lineare su una logistica
        // che spazia su ordini di grandezza resta schiacciato (provato e
        // misurato: da 4 a 25 il rapporto fra parziali e autocrazie si muoveva
        // solo da 1,1 a 1,6).
        //
        // Con la pendenza a 7: dodici punti valgono ~5 volte le probabilita',
        // e la faziosita' li raddoppia fino a ventiquattro, cioe' ~30 volte.
        // E' il rapporto che Goldstone misura fra una democrazia parziale
        // fazionalizzata e un'autocrazia piena.
        'spostamento_regime'   => 12.0,
        // Quanto la CHIUSURA protegge, in punti di legittimita'. E' il ramo
        // sinistro della U: un'autocrazia piena reprime e tiene. Senza questo
        // termine la repressione costava legittimita' e non comprava niente, e
        // le dittature risultavano piu' fragili delle democrazie.
        'protezione_chiusura'  => 10.0,
        // Il contagio: quattro o piu' confinanti in conflitto armato e
        // l'instabilita' passa il confine.
        'peso_vicinato'        => 1.2,
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
        // Tetto moltiplicativo al rischio annuo di colpo di Stato. Era 1,6 e
        // produceva 13,2 colpi riusciti l'anno su centottantanove paesi: il
        // tasso degli anni Sessanta-Settanta (103 riusciti negli anni '60, 95
        // negli anni '70 — Cline Center Coup d'Etat Project, dataset Powell &
        // Thyne), non quello del mondo che stiamo seminando. Dal 2000 il mondo
        // ne fa 2,2 l'anno, negli anni Venti circa 3,8.
        //
        // Era 0,25 finche' il reclutamento insurrezionale seguiva la radice
        // della popolazione. Rifatto quello secondo Fearon & Laitin, i colpi
        // sono risaliti da soli a 5-7 l'anno: piu' insurrezione significa meno
        // legittimita', e la logistica qui sotto la legge. E' la stessa
        // sostituzione di sempre, e va ritarata ogni volta che si tocca un
        // pezzo a monte.
        //
        // Ritarato una seconda volta quando il tipo di regime e' entrato DENTRO
        // la logistica (blocco 'instabilita' qui sopra): spostare il centro
        // cambia il tasso, non solo la distribuzione. A 0,10 i colpi stanno
        // intorno a 3-4 l'anno, dentro il riferimento 2,2-3,8 degli anni
        // Duemila-Venti, e le rivoluzioni a ~2,5, dentro quello di Crawford.
        'rischio_massimo_anno'   => 0.10,
        'pendenza'               => 7.0,
    ],

    // ----------------------------------------------------------- relazioni
    'relazioni' => [
        // Sotto quale affinita' un trattato non regge piu' nemmeno sulla
        // carta. NON e' la soglia a cui si smette di volersi bene: e' quella a
        // cui ci si considera nemici. Le alleanze vere sopravvivono al
        // raffreddamento — Grecia e Turchia stanno nella NATO da settant'anni
        // — e cedono solo alla rottura.
        'rottura_trattato' => -35.0,

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
        // Sopra quale indice V-Dem un paese tiene elezioni che contano.
        // Sotto, il potere cambia solo per la via irregolare della fase 05.
        // A 0,25 votano 119 paesi su 189, e il filtro sullo stato di polizia
        // ne toglie altri: V-Dem ne classifica circa un centinaio come
        // democrazie elettorali o liberali, quindi l'ordine di grandezza
        // torna. Dentro l'India (0,26) e il Ghana (0,61), fuori la Turchia
        // (0,11), la Russia (0,056) e la Cina (0,039) — che le elezioni le
        // tengono, ma non decidono niente.
        'democrazia_minima' => 0.25,

        // Sotto questa maturita' istituzionale non si vota davvero: resta solo
        // la via irregolare. [FABBRICATO]
        // NOTA: qui c'era 'maturita_minima' => 130. Tolta: decideva chi va alle
        // urne in base a `maturita`, che e' un indice di ricchezza e
        // alfabetizzazione marcato SEGNAPOSTO. Mandava a votare la Cina, Cuba,
        // la Bielorussia e gli Emirati, e teneva a casa il Ghana, Capo Verde e
        // la Giamaica. La sostituisce 'democrazia_minima', su dato V-Dem.
        // Curva del ricambio: a legittimita' pari al centro l'uscente ha una
        // probabilita' su due di perdere.
        'centro'          => 52.0,
        'pendenza'        => 9.0,
        // Crisi di governo fra un voto e l'altro, massimo annuo. [FABBRICATO]
        //
        // Era 1,8 e produceva governi che duravano 3,3 anni in media su
        // centottantanove paesi. Nel mondo di oggi un esecutivo dura 4-8 anni
        // nelle democrazie competitive e decenni nei sistemi autoritari: 3,3
        // sta sotto il minimo della forchetta democratica, in un mondo dove
        // circa meta' dei paesi non e' una democrazia.
        //
        // Il numero non veniva da nessuna fonte — e' marcato FABBRICATO — ma
        // la nota che lo giustificava in Fase10 si appoggiava alla Francia e
        // all'Italia del World Handbook: la Quarta Repubblica e la Prima
        // Repubblica, cioe' le due democrazie piu' instabili del dopoguerra,
        // prese come metro per tutti e per sempre.
        //
        // A 0,8 due riferimenti indipendenti cadono insieme: i governi durano
        // 4,5 anni (gioco) e 5,4 (osservazione), e la quota di uscite
        // irregolari sale al 16-18%, contro il ~20% che Archigos misura sul
        // 1946-2004. Non e' una coincidenza: meno crisi ordinarie significa
        // che una fetta maggiore delle uscite avviene per la via irregolare.
        'rischio_crisi_anno' => 0.8,
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
