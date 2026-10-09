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

        // La tendenza di ogni paese e' la proiezione 2026-2030 del FMI
        // (db/seed/crescita.php). Per quanti anni vale, e poi a che ritmo la
        // tendenza pro capite torna a quella del paese mediano: Pritchett e
        // Summers (2014) misurano una correlazione fra 0,1 e 0,3 fra la
        // crescita di un decennio e quella del successivo, cioe' circa il 14%
        // l'anno di ritorno alla media (docs/30).
        'orizzonte_proiezioni'     => 5.0,

        // Il rimbalzo dopo una guerra (fase 03, docs/34). Quando il costo di
        // una guerra cala, la crescita guadagna questa quota del calo, e il
        // guadagno si spegne con questa costante di tempo. Misurato sulle 49
        // guerre finite nel 1990-2019 (UCDP; FMI): nei cinque anni dopo, 1,9
        // punti l'anno sopra la mediana mondiale in media, 0,8 di mediana
        // (il Kuwait e la Bosnia tirano la media). Con 0,8 e quattro anni una
        // guerra civile piena che finisce da' circa 1,8 punti subito e 1,1 di
        // media sui cinque anni.
        'rimbalzo' => [
            'quota' => 0.8,
            'anni'  => 4.0,
        ],
        'ritorno_alla_media_anno'  => 0.14,

        // Il ciclo economico (fase 03, docs/30): un'onda lenta in tre pezzi.
        //   KOSE, OTROK, WHITEMAN (2003), AER 93(4): il fattore mondiale pesa
        //   di piu' nelle economie avanzate, il regionale poco, il resto e'
        //   del paese.
        //   KOREN, TENREYRO (2007), QJE 122(1): le economie povere oscillano
        //   circa il doppio di quelle ricche.
        // La volatilita' e' la deviazione tipica del tasso di crescita
        // istantaneo; si passa dai poveri ai ricchi col reddito (50.000 $).
        // Tarata sulla quota di economie in recessione del FMI (10% di
        // mediana fuori dalle crisi mondiali): le guerre, le sanzioni e gli
        // investimenti fanno oscillare per conto loro, e il ciclo e' quel che
        // resta. Con 1,8 e 4 punti i paesi in pace erano in recessione un
        // anno su sei; con 1,2 e 2,5, dopo aver tolto alle guerre del seme
        // il costo che il FMI aveva gia' messo in conto, uno su dodici.
        // Il periodo e' la distanza fra due nodi dell'onda, in tick.
        'ciclo' => [
            'periodo'            => 52,
            'volatilita_ricchi'  => 0.014,
            'volatilita_poveri'  => 0.029,
            'quota_mondo_ricchi' => 0.30,
            'quota_mondo_poveri' => 0.10,
            'quota_regione'      => 0.10,
        ],
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
        // I punti neutri delle spinte non stanno piu' qui: si derivano dalle
        // stesse formule in un paese in condizioni normali (Fase03Economia,
        // docs/30). Misurati a mano, erano invecchiati col seme.
        'ampiezza_investimenti'      => 0.16,
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
        // Quanto gli effettivi seguono la spesa. Non uno a uno: un bilancio
        // che raddoppia compra soprattutto mezzi, e gli uomini crescono di
        // circa la radice (elasticita' 0,5). E comunque non oltre il triplo
        // della taglia di partenza, ne' oltre il 5% della popolazione — la
        // Corea del Nord, il paese piu' militarizzato del mondo, ne tiene
        // ~5% (IISS, The Military Balance 2024: 1,28 milioni su 26). Con la
        // proporzione diretta un paese che partiva da una quota minuscola la
        // decuplicava e decuplicava l'esercito: la Corea del Nord arrivava a
        // quindici milioni di soldati, e gli effettivi del mondo da 21 a 48
        // milioni in quindici anni. [FABBRICATO l'elasticita', i tetti no]
        'elasticita_uomini'          => 0.5,
        'tetto_uomini_relativo'      => 3.0,
        'tetto_uomini_popolazione'   => 0.05,
        // Ammortamento annuo dello stock di equipaggiamento. Fissa l'equilibrio
        // a «spesa / ammortamento»: con 0,25 sono quattro anni di spesa, che e'
        // come il seme lo avvia. Era 0,08, e lo stock triplicava in dieci anni.
        'ammortamento_equipaggiamento' => 0.25,
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
        // quanto vaga a regime (deviazione tipica, in punti di legittimita').
        // Insieme determinano quanto due corse dello stesso mondo possono
        // divergere. «Qualche punto» e' la richiesta di docs/08. [FABBRICATO]
        'ritorno_deriva'        => 0.15,
        'ampiezza_deriva'       => 2.0,
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
        // Di quanto un governo moltiplica la controinsurrezione quando gli
        // insorti arrivano alla sua forza (a zero insorti: nessun aumento).
        // Crea la guerriglia cronica che l'attrito fisso non permetteva; vedi
        // Fase05 e docs/27. [FABBRICATO]
        'risposta_governo'   => 6.0,   // a 3 i paesi in guerra erano 21-25
        // La probabilita' annua che un'insurrezione si ACCENDA dove non c'e',
        // per il paese mediano del seme, e il tetto. Fearon (2010): fra tutti
        // i conflitti UCDP, anche minori, il 3,3% degli anni-paese nel
        // 1946-2008 ne vede cominciare uno; per il paese mediano, stabile e
        // senza petrolio, circa un terzo di quello. [TARATO su
        // paesi_in_conflitto: qui accendersi non vuol dire diventare guerra,
        // e molte si spengono presto]
        'innesco_base_anno'    => 0.010,
        'innesco_massimo_anno' => 0.10,
        // Il rischio relativo, in log-odds per unita' (Fase05::
        // rischioDiGuerraCivile). FEARON (2010), «Governance and Civil War
        // Onset», WDR 2011, tabella 2 modello 3 (tutti i conflitti UCDP,
        // 1946-2008) e tabella 21 (qualita' del governo). Non fabbricati.
        'rischio' => [
            'reddito'         => -0.26,    // per log del reddito pro capite; -0,351 senza la qualita' del governo
            'popolazione'     => 0.238,    // per log della popolazione
            'montuoso'        => 0.151,    // per log(1 + % di territorio accidentato)
            'petrolio'        => 0.715,    // produttore di greggio o gas
            'regime_parziale' => 0.355,    // anocrazia (Polity fra -5 e 5)
            'instabilita'     => 0.466,    // cambio di regime nell'anno prima
            'governo'         => -0.93,    // per unita' di stabilita' politica WGI (PV), tabella 21
        ],
        // La disuguaglianza ORIZZONTALE di Cederman, Wimmer e Min: quanto
        // pesa l'esclusione etnica dal potere sul reclutamento insurrezionale.
        // E' il MOTIVO, che il modello non aveva: fin qui c'erano solo le
        // opportunita' (poverta', popolazione, debolezza dello Stato).
        'peso_esclusione'     => 3.0,
        // E la frammentazione: il Myanmar ha meno esclusi della Siria (29%
        // contro 86%) ma in undici gruppi invece che in quattro, e undici
        // fronti sono peggio di uno.
        'peso_frammentazione' => 0.5,
        // Probabilita' annua che gli insorti, una volta piu' forti
        // dell'esercito, prendano DAVVERO il potere. Non e' uno: prevalere sul
        // campo non e' prendere la capitale, e fra i conflitti armati che
        // finiscono la vittoria dei ribelli e' l'esito piu' raro. Prima era
        // implicitamente 1 — il governo cadeva lo stesso tick in cui il
        // rapporto di forze si ribaltava — e le guerre civili non duravano.
        // 0,22 fino all'audit di settembre 2026, che l'ha trovata a quattro
        // rivoluzioni l'anno: il mondo degli anni Venti ne ha una o due
        // (Afghanistan 2021, Siria 2024). Abbassata insieme al reclutamento e
        // alla risposta del governo qui sotto: vedi docs/27.
        'vittoria_insorti_anno' => 0.18,
        // Quanta potenza insurrezionale si recluta, in unita' dell'attrito
        // che il governo del seme infligge, nel paese col terreno di guerra
        // mediano. E' il parametro che decide se il mondo ha guerre civili o
        // se non ne ha mai. [TARATO sui riferimenti UCDP 2024: 61 conflitti
        // statali in 36 paesi, 11 arrivati al livello di guerra]
        //
        // Fino a settembre 2026 moltiplicava la popolazione e un
        // moltiplicatore di poverta' (0,8e-3), contro una forza del governo
        // che cresce con la radice di popolazione per PIL: il rapporto aveva
        // un'elasticita' di -1,5 al reddito e nessuna alla popolazione, e ogni
        // paese povero finiva in guerra civile appena qualcosa si accendeva —
        // Togo, Burundi, Ruanda, Madagascar. Adesso il «terreno» e' il rischio
        // relativo di guerra maggiore stimato da Fearon (2010), qui sotto
        // (docs/30).
        'reclutamento'       => 1.0,
        // Fin dove possono arrivare i ribelli, in multipli della forza che lo
        // Stato aveva al seme: il reclutamento rallenta e li' si ferma
        // (Cunningham, Gleditsch e Salehyan 2013: i ribelli alla pari o piu'
        // forti dello Stato sono una piccola minoranza; docs/33). [FABBRICATO]
        // come numero preciso.
        'tetto_insorti'      => 1.5,
        // FEARON (2010), «Governance and Civil War Onset», WDR 2011: tabella 2
        // modello 1 (guerre oltre i mille morti l'anno, 1946-2008) e tabella 20
        // (qualita' del governo, che dimezza il peso del reddito). Log-odds
        // per unita', rispetto al paese mediano. Non fabbricati.
        'terreno' => [
            'reddito'         => -0.20,    // per log del reddito; -0,404 senza la qualita' del governo
            // La popolazione di Fearon (+0,203) predice che una guerra COMINCI
            // da qualche parte nel paese, e sta nell'innesco. Qui si misura
            // quanto i ribelli crescono contro uno Stato che cresce con la
            // popolazione quanto loro: con +0,203 la Cina passava otto anni su
            // dieci in insurrezione grave.
            'popolazione'     => 0.0,
            'montuoso'        => 0.360,    // per log(1 + % di territorio accidentato)
            'petrolio'        => 1.095,    // produttore di greggio o gas
            'regime_parziale' => 0.258,    // anocrazia
            'governo'         => -0.97,    // per unita' di stabilita' politica WGI (PV), tabella 20
        ],
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
        // Civili morti nei combattimenti per ogni combattente caduto, nelle
        // guerre fra Stati. Era 1,0, la media di Eckhardt su tre secoli, che
        // comprende le guerre totali; nelle guerre fra Stati di oggi l'UCDP
        // GED v26.1 ne conta 0,09 (1989-2025, bin/misura_civili.php): 0,03
        // nella guerra russo-ucraina dal 2023, 0,28 nel 2022 di Mariupol. Con
        // 1,0 la guerra russo-ucraina del modello faceva 150.000 morti l'anno
        // contro i 76.000-102.000 dell'UCDP, che i civili uccisi in battaglia li
        // conta gia'; con 0,09 ne fa circa 82.000 (docs/34). Le uccisioni
        // deliberate di civili (la violenza unilaterale) sono un'altra cosa,
        // e il modello non le ha.
        'civili_per_militare' => 0.09,
        // Gli aiuti militari a un paese in guerra: la quota del proprio
        // bilancio militare annuo che manda chi parteggia pienamente, e
        // l'inclinazione minima per parteggiare (differenza di affinita' su
        // 254). Tarati sul Kiel Institute, Ukraine Support Tracker (febbraio
        // 2025): circa 45 miliardi di euro l'anno di aiuti militari
        // all'Ucraina nel 2022-24, meta' americani e meta' europei.
        'quota_aiuti_anno'    => 0.07,
        'soglia_aiuti'        => 0.25,
        // La probabilita' annua che una guerra di logoramento finisca a un
        // tavolo: dopo il primo anno parte dalla base e cresce ogni anno fino
        // al tetto. [FABBRICATO] nella forma; l'ordine di grandezza e' quello
        // delle guerre lunghe del dopoguerra (Corea tre anni, Iran-Iraq otto).
        'armistizio_base_anno'     => 0.10,
        'armistizio_crescita_anno' => 0.08,
        'armistizio_massimo_anno'  => 0.35,
        // Sotto questo rapporto di forze (aggressore su difensore mobilitato)
        // l'aggressore e' battuto e si ritira. Era 0,8: vedi Fase07.
        'soglia_ritirata'          => 0.5,
        // Quanta della propria forza porta al fronte chi attacca senza un
        // confine comune (mare, o un paese in mezzo). [FABBRICATO] l'ordine di
        // grandezza: Mearsheimer (2001) sul «potere d'arresto dell'acqua»; i
        // giochi di guerra del CSIS su Taiwan (gennaio 2023) danno a un'invasione
        // anfibia esiti per lo piu' falliti anche con una superiorita' netta.
        'proiezione_oltre_confine' => 0.4,
        // Quanta della propria forza un garante mette in campo quando combatte
        // accanto al difensore (docs/33). Una grande potenza non svuota le
        // proprie caserme per una guerra lontana: nei giochi di guerra del
        // CSIS su Taiwan (2023) gli Stati Uniti impegnano due o tre gruppi
        // portaerei e le forze aeree del Pacifico, un quarto-un terzo del
        // totale. [FABBRICATO] come ordine di grandezza.
        'impegno_cobelligeranti'   => 0.3,
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
        // Il quarto predittore di PITF: la qualita' della vita, che Goldstone
        // et al. misurano con la mortalita' infantile — 75esimo percentile
        // contro 25esimo, SETTE VOLTE le probabilita'.
        //
        // 2,7 punti per livello non e' una scelta di gusto: i quartili della
        // nostra qualitaVita stanno a 3 e a 8, e con la pendenza a 7 servono
        // 7*ln(7) = 13,6 punti su quei cinque livelli di scarto.
        //
        // E' anche la via per cui entra la DISUGUAGLIANZA: quel livello si
        // calcola sul consumo mediano, non su quello medio.
        'peso_qualita_vita'    => 2.7,
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
        //
        // Ritarato una terza volta con l'audit di settembre 2026: la deriva
        // politica che finalmente vaga, la soglia unica dell'insurrezione e
        // gli eserciti che non si gonfiano piu' (docs/27) avevano riportato i
        // colpi a 5,3 l'anno. A 0,065 tornano nel riferimento.
        'rischio_massimo_anno'   => 0.065,
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

        // Per quante settimane una caduta conta come «con una mano straniera»
        // dopo l'ultima ingerenza ostile andata a segno (armi ai ribelli,
        // fondi all'opposizione, destabilizzazione, una trama). Solo quelle
        // cadute mettono alla prova una garanzia (docs/30): un anno.
        'finestra_ingerenza' => 52,

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
        // Sulla scala del seme (db/seed/politica-nota.php) 3 e' il programma
        // avviato, 4 l'ordigno provato: armato e' chi l'ha provato. A 3
        // l'Iran contava come potenza nucleare — ombrello, deterrenza, club —
        // lo stesso errore che docs/26 §7.3 aveva corretto nel metro.
        'soglia_armato'             => 4,
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
        // Quanti ordini una poltrona puo' impartire in un giro d'orologio.
        // Nessun limite voleva dire cento operazioni coperte in una settimana
        // da un solo ministro, contro l'unica mossa che la dottrina concede
        // all'apparato. Due: una decisione e il suo ripensamento. [FABBRICATO]
        'ordini_per_tick' => 2,
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
    // Le contromosse di un servizio con una persona al tavolo (docs/29).
    // [FABBRICATO] tutte: sono le leve del gioco, non grandezze del mondo.
    'contromosse' => [
        // Concentrare i mezzi su un'operazione nota rende piu' probabile
        // fermarla rispetto al lavoro ordinario dell'apparato.
        'bonus_sventare'        => 1.6,
        // Seguire un'operazione invece di fermarla: quanto piu' in fretta si
        // arriva al nome di chi l'ha ordinata.
        'bonus_sorveglianza'    => 2.0,
        // Un'operazione in corso sbattuta sui giornali senza il nome del
        // mandante: quanto spesso chi l'ha avviata la lascia cadere.
        'abbandono_se_rivelata' => 0.6,
        // Quanto guadagna il rapporto col paese che avvisiamo di
        // un'operazione contro di lui.
        'gratitudine_avviso'    => 8.0,
    ],

    // ---------------------------------------------------------- il regime
    // Quanto un cambio di regime stringe repressione e censura (V-Dem, 0..1;
    // docs/33). Dopo un colpo di Stato: la media dei quindici colpi riusciti
    // del 2010-2023 di Powell e Thyne, tre anni dopo contro l'anno prima. Dopo
    // una presa del potere armata: la media di Afghanistan 2021, Yemen 2015 e
    // Libia 2011 — tre casi soli e diversissimi, ed e' dichiarato.
    'regime' => [
        'repressione_dopo_colpo'      => 0.12,
        'censura_dopo_colpo'          => 0.13,
        'repressione_dopo_rivoluzione' => 0.14,
        'censura_dopo_rivoluzione'    => 0.09,
    ],

    'dottrina' => [
        'attivita'             => 0.5,   // moltiplicatore generale [FABBRICATO]
        // La guerra fra rivali (Fase00::guerreFraRivali, docs/31): il rischio
        // annuo che una coppia ammissibile — ostile, invadibile, raggiungibile,
        // senza garanti ne' ombrelli, con una superiorita' netta — diventi una
        // guerra. Misurato sulle dispute Correlates of War 1946-2014
        // (bin/importa_rivalita.php): 0,53% nelle rivalita' (tre dispute in
        // vent'anni), 1,26% in quelle durature (sei). Senza una storia di
        // dispute, un decimo.
        'guerra' => [
            'rivalita'        => 0.0053,
            'duratura'        => 0.0126,
            'senza_rivalita'  => 0.00053,
            // Il tasso vero vale per tutte le rivalita'-anno; qui scatta solo
            // in quelle in cui la guerra e' possibile, che sono un sottoinsieme.
            // [TARATO] sul numero di guerre fra Stati: quattro-sei nel
            // 2010-2025 (UCDP/PRIO; Russia-Ucraina due volte, Armenia-
            // Azerbaigian due volte, Kirghizistan-Tagikistan).
            // Con 1 uscivano due guerre in sei mondi, con 5 tre per mondo, con 10
            // da quattro a otto. Con le affinita' misurate (docs/32) le coppie
            // ostili sono di piu': 6 ne faceva cinque-sette, 4,5 ne fa tre-nove,
            // media cinque — Russia-Ucraina, Arabia Saudita-Yemen,
            // Azerbaigian-Armenia, Cina-Taiwan, Etiopia-Eritrea, Iran-Afghanistan.
            'condizionamento' => 4.5,
        ],
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
