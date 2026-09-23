# 27 — L'audit totale (settembre 2026)

Il terzo audit, e il piu' largo: tutto il motore, tutto il gioco, il sito vero
via HTTP, i dati del seme. Come i due prima (`docs/24`, `docs/26`) e' fatto di
misure, non di riletture: ogni reperto qui sotto e' stato ottenuto facendo
girare qualcosa e contando.

Il riassunto in una riga: **il mondo vivo non era il mondo che si misurava**.
Adesso lo e', e c'e' una prova che lo dimostra a ogni giro.

---

## 1. Il reperto centrale: due mondi diversi

Tutte le misure di realismo fatte fino ad allora — le diciannove grandezze di
`docs/26`, le fasce di `App\Simulazione\Realismo::FASCE` — venivano da
`bin/realismo.php`, che fa girare il motore **in memoria** per quindici anni.
Il mondo vivo invece riparte a ogni tick dal seme (`Mondo::daSeme`), ci
sovrappone quel che la base dati ha conservato (`Deposito::ripristina`), gira,
e salva (`Deposito::salva`). Tutto cio' che il Deposito non salva torna al
seme ogni due ore; tutto cio' che salva arrotondato perde il movimento
frazionario.

La prova: una rigiocata in memoria che arrotondava a ogni tick come fanno le
colonne ha riprodotto il mondo vivo **quasi esattamente**. Stato di polizia
fermo in 147 paesi contro 146 reali, quota militare ferma in 109 contro 110,
soldati in 62 contro 62. Senza arrotondamento, gli stessi campi fermi
sarebbero stati da zero a due.

### Le correzioni

| cosa | prima | dopo |
|---|---|---|
| grandezze continue dello stato (0029) | `DECIMAL`/`SMALLINT`: l'affinita' si muove di 0,147 a tick e l'arrotondamento la cancellava sempre — 5.256 relazioni su 5.530 al valore del seme dopo 106 tick | `DOUBLE` |
| `stato_polizia` | un cricchetto: da 2,00 nessuno poteva allentare | `DOUBLE` |
| `crescita_strutturale` | un programma d'investimento non si riassorbiva mai del tutto | `DOUBLE` |
| l'**ancora** dei rapporti (0029) | non si salvava: un agente scoperto o un cambio di regime erano dimenticati il tick dopo | salvata |
| l'**obbligo firmato** (0029) | non si salvava: una denuncia di trattato si annullava da sola | salvato |
| la memoria delle mosse della dottrina (0029) | si azzerava: 28 eventi su 196 violavano l'attesa fra due mosse uguali | `sdb_memoria_azioni` |
| PIL pro capite, consumo pro capite (0031) | due decimali: la legittimita' si muove sulla VARIAZIONE del consumo, e 184 paesi su 189 avevano una legittimita' diversa dopo un solo tick | `DOUBLE` |
| popolazione, PIL (0031) | intera, due decimali | `DOUBLE` |
| presenza e capacita' d'intelligence (0031) | quattro decimali: uno scarto sotto mezzo centesimo non si muoveva mai | `DOUBLE` |
| parametri degli eventi | nascevano con tutte le cifre e si salvavano in centesimi: il tick di nascita e i successivi usavano numeri diversi | nascono in centesimi |
| quota delle strozzature commerciali | idem | nasce in centesimi |

E una cosa che non era di arrotondamento: `Deposito::salva` scriveva le sue
tavole una alla volta. Adesso tutto il tick — dodici fasi, salvataggio e
registro — sta in **una transazione sola** (`bin/tick.php`), sotto un lucchetto
(`storage/tick.lock`) che impedisce a due tick di sovrapporsi. Se qualcosa cade
si annulla tutto, e il giro dopo rifa' il tick da capo.

### La prova che adesso lo impedisce

`tests/13-fedelta-della-persistenza.php` fa girare **lo stesso mondo** due
volte, dentro una transazione annullata:

- **A** in memoria, come `bin/realismo.php`;
- **B** come il cron: seme, ripristino, tick, salvataggio, a ogni tick.

e alla fine confronta tutto — ogni campo numerico di ogni nazione, ogni
relazione, ogni poltrona e fazione, la presenza d'intelligence, lo stato del
mondo. Se il Deposito e' fedele i due mondi sono identici fino all'ultima
cifra, perche' il caso e' deterministico. Sono identici su 26 tick nella prova,
e sono stati verificati identici su **104 tick** (due anni) una volta a mano.

La prova di andata e ritorno che c'era gia' (file 12) non poteva accorgersene:
confrontava due mondi entrambi ricostruiti dal seme, e un campo che non si
salva vale il seme in tutti e due.

---

## 2. Il motore

| reperto | correzione |
|---|---|
| **La fase 05 cancellava la guerra.** Ricalcolava `netPeace` dalle sole insurrezioni: un paese in guerra con un altro Stato risultava in pace per tutta la fase 06 (disarmo, garanzie); gli shock di un incidente o di un attacco sparivano nello stesso tick | il livello e' il massimo fra la parte interna, la guerra fra Stati e una `scossaEsterna` transitoria del tick |
| **La deriva politica era inerte.** Il rumore era moltiplicato per il passo di tempo intero invece che per la sua radice: la deriva restava entro due decimi di punto, e ogni paese riposava a 50 | Ornstein-Uhlenbeck corretto, deviazione tipica 2 punti; all'avvio si estrae dalla distribuzione a regime invece di partire da zero |
| La repressione pesava meta' nell'apertura istituzionale (`/6` su una scala 1..5) | `/3`: 5 e' la morsa piena |
| **La presenza d'intelligence poteva solo calare** (1% a tick fino al 2%) e niente la ricostituiva: in pochi anni i servizi del mondo erano ciechi | tende all'interesse (`Intelligence::presenzaColtivata`), e sale dove l'interesse cresce |
| Soglia dell'«essere armati» a 3 (programma avviato): l'Iran contava come potenza nucleare, come garante, come deterrente | 4, «ordigno provato», come il metro di `docs/26` §7.3; uniforme in cinque punti del motore |
| Il pavimento dell'obbligo firmato scavalcava il tetto 96 dei garanti non nucleari | il tetto vale anche per la firma, e subito |
| **Le garanzie tradite quasi sempre** (30 su 31): la capacita' si misurava garante per garante | si misura sulla coalizione di chi e' disposto a entrare; gli aiuti escono dagli arsenali del garante (prima comparivano dal nulla), al piu' un quarto |
| **L'integrita' crollava in tutto il mondo** (mediana 19 su 128): la perdeva anche chi aveva solo un trattato commerciale con un paese caduto per colpo di Stato | solo chi aveva promesso protezione (obbligo >= 64) |
| Una caduta di governo regolare spostava l'ancora dei rapporti come una rivoluzione | solo rivoluzioni e colpi di Stato |
| Il verbo `trattato` non scriveva la firma: il patto evaporava in un anno | scrive anche l'obbligo firmato |
| L'ancora poteva scendere sotto -127 | limitata |
| La dottrina riduceva la dimostrazione di forza di chi preparava un'invasione | vale il massimo fra i due candidati |
| **Nove valori di riserva** nel codice lontani dalla calibrazione (rischio di colpo 0,9 contro 0,10, reclutamento 2,5 contro 0,001…) | allineati; `bin/audit.php` li confronta con `base.php` a ogni giro |
| `forzaInsorti > 0` in due punti e `< 1` in un altro: un millesimo di uomo rendeva un paese «fragile» per la dottrina e «minacciato» per la polizia | una soglia sola, `Nazione::haInsorti()` |
| Il rischio d'incidente in crisi era gonfiato di meta' anche in un mondo quieto | il peso moltiplica la cattiveria, non il rischio intero |
| **Uno scandalo per ogni servizio** che attribuiva la stessa operazione: vista da sei servizi costava sei volte | lo scandalo scoppia una volta per accusato |
| Chiavi del caso costruite col modulo: due osservatori su tre condividevano la sorte | una chiave per coppia |
| Due dimissioni nello stesso paese e tick producevano lo stesso nome | seme per ruolo |

### Fatti pubblici che non arrivavano in cronaca

Le fasi 10 e 11 girano **dopo** la stampa (fase 09), e quel che annotavano non
diventava mai notizia: la fine di un'epoca, i reclutamenti denunciati, le
dimissioni. Adesso lo mettono in cronaca da se', come gia' faceva l'elezione. E
sei generi di fatti che nessuno riesce a nascondere restavano nel giornale
interno del tick: l'**incidente** armato, il **test** atomico, il **disarmo**,
la **garanzia onorata** o **tradita**, le **sanzioni**. Sono pubblici, e la
cronaca li racconta in italiano invece di stampare i campi grezzi.

### Gli eserciti che si gonfiavano

Gli effettivi del mondo passavano da 21 a 37-48 milioni in quindici anni (IISS:
~27). Due cause:

1. il reclutamento seguiva la quota di spesa militare **in proporzione
   diretta**: chi partiva da una quota minuscola e la decuplicava, decuplicava
   l'esercito. Adesso elasticita' 0,5 (un bilancio che raddoppia compra
   soprattutto mezzi), al piu' il triplo della taglia di partenza e mai oltre il
   5% della popolazione — il livello della Corea del Nord, il paese piu'
   militarizzato del mondo;
2. **la Corea del Nord aveva una spesa militare di -30%** nel seme.
   L'importatore del Factbook leggeva il trattino di «20-30% of GDP» come un
   segno meno. Adesso un intervallo vale il suo punto medio (25%), e il seme e'
   corretto.

Dopo: 23,6-26,4 milioni in quindici anni.

### Le alleanze della CSI

Il dataset Correlates of War si ferma al 2012 e tiene la rete della CSI del
1991 come patto di difesa. Nel seme la **Russia era garante della difesa
dell'Ucraina**, e con lei Bielorussia, Kazakistan e altri sette: al primo tick
di una guerra russo-ucraina dieci «garanti» tradivano l'impegno. L'importatore
ha adesso una tavola di **scioglimenti** datati accanto a quella degli
allargamenti NATO: l'Ucraina fuori dagli accordi della CSI (decreto del
19/05/2018; trattato di amicizia con la Russia cessato il 01/04/2019), la
Georgia fuori dalla CSI dal 18/08/2009.

---

## 3. Il gioco

| reperto | correzione |
|---|---|
| **La pagina dell'arbitro cadeva sempre** (`TypeError`): una variabile del ciclo sovrascriveva quella degli atti | rinominata |
| **La falsificazione era inusabile dal sito**: il modulo mandava l'id della poltrona dove serviva quello della nazione, sempre 0 | corretto |
| **Chiunque poteva rispondere a qualunque crisi**: la parte arrivava dal modulo | la si ricava dalla nazione della poltrona (`Crisi::parteDi`) |
| Una mossa di crisi diversa da «cede» saliva, anche un campo vuoto | due mosse sole |
| La pazienza di tre giri ne concedeva quattro | la scadenza e' inclusiva come ogni altra del gioco |
| **Chi saliva la scala dal sito non correva il rischio d'incidente** che corre l'apparato: la scala era piu' sicura per gli umani che per le macchine | il gradino raggiunto si segna (0030) e la fase 01 tira il dado al tick dopo |
| Cinque agende su dodici non potevano mai chiudersi; le difensive non potevano mai riuscire | valutazione riscritta coi codici del catalogo, e chiusura d'epoca |
| Ordini senza limite: cento operazioni coperte in una settimana da un ministro | `gioco.ordini_per_tick` = 2 per poltrona |
| Il corriere, il canale piu' sicuro, era illimitato; la linea diretta pesava sul contingente del cifrato | il contingente copre cifrato e corriere, e solo loro |
| Il delegato agiva e negli atti restava solo il titolare; con la delega ci si poteva controfirmare da soli | la firma del delegato si scrive, e nessuno firma cio' che ha proposto |
| Chi lasciava la poltrona la lasciava in mano al delegato, con gli ordini in volo | lasciare chiude delega, ordini, agende, talpa e sospetti |
| La rivelazione di fine epoca ripeteva le talpe delle epoche precedenti | solo quelle reclutate nell'epoca |
| Una denuncia di reclutamento dal sito non spostava l'ancora del rapporto | la sposta, come quella della fase 10 |
| **In un mondo appena riavviato nessuno riceveva avvisi per dodici tick**, neanche per una crisi in scadenza | «mai avvisato» non e' «avvisato al tick zero» |
| Una mail poteva partire due volte se due giri della posta si sovrapponevano | il messaggio si prende con un aggiornamento atomico prima di spedirlo |
| Reimpostare la parola d'ordine lasciava aperte le altre sessioni | la sessione e' legata a un'impronta della parola d'ordine |
| Le viste si potevano aprire direttamente dal browser | ognuna comincia con una guardia |
| Registrazione, invito e verifica non erano atomici; un rinvio della verifica non aveva freno | una transazione; un rinvio ogni dieci minuti |

E il riavvio: `bin/avvia_mondo.php --ricomincia` lasciava in piedi epoche,
crisi, messaggi, offerte, agende, linee, intercettazioni e rivelazioni del mondo
vecchio. Dopo il riavvio del 22/09 il mondo nuovo aveva un'epoca «in corso»
cominciata al tick 118, e siccome gli eventi ripartono da 1 una crisi vecchia
poteva puntare a un evento nuovo che non c'entrava. L'elenco sta adesso in
`Deposito::azzeraMondo()`, che usa anche la prova 13.

---

## 4. La guerriglia cronica

Correggere la persistenza e la deriva ha riportato i cambi irregolari a 8-10
l'anno, contro i 5,9-7,2 di `docs/26`. Contandoli: 5,3 colpi riusciti e 4
rivoluzioni l'anno, dove il mondo degli anni Venti ne fa 2,5-3,8 e una o due. Il
tetto del rischio di colpo di Stato e' sceso da 0,10 a 0,065, la probabilita'
annua di vittoria degli insorti da 0,22 a 0,18, il reclutamento insurrezionale
da 1,0e-3 a 0,8e-3.

Ma e' venuto fuori un difetto piu' profondo, che c'era gia' prima dell'audit:
**i paesi a livello di guerra erano 23-35**, contro gli 11 di UCDP 2024, e nel
primo anno salivano a 42-46 — Kenya, Tanzania, Malawi, Lesotho in guerra
civile. Il motivo era nella dinamica: con un attrito fisso un'insurrezione o si
spegneva o cresceva senza freni fino a pareggiare l'esercito. **Non esisteva la
guerriglia cronica a bassa intensita'**, che e' la forma piu' comune di
conflitto armato nel mondo vero.

Adesso la controinsurrezione cresce con la minaccia: un governo che vede
crescere i ribelli sposta su di loro truppe, bilancio e polizia
(`insurrezione.risposta_governo`, un moltiplicatore dell'attrito che va da 1 a
insorti assenti a 7 quando arrivano alla forza del governo). Nasce un
equilibrio stabile sotto la guerra civile, e ci arriva solo chi ha un
reclutamento molte volte superiore.

| grandezza | prima dell'audit | dopo, osservazione | dopo, gioco | riferimento |
|---|---:|---:|---:|---|
| cambi irregolari /anno | 7,0-7,4 | 5,3-6,0 | 5,5-6,4 | Powell & Thyne, UCDP |
| paesi a livello di guerra | 23-25 | 11-22 | 20-32 | UCDP 2024: 11 |
| paesi in conflitto | 27-32 | 39-44 | 40-46 | UCDP 2024: 36 |
| effettivi, milioni (15 anni) | 36,9-40,1 | 23,8-26,4 | 23,6-25,8 | IISS: ~27 |
| durata media di un governo, anni | 5,4-5,9 | 5,7-6,2 | 4,9-5,2 | 4-9 |

Misure su quindici anni, semi 1-4 (prima dell'audit: semi 1-2). Tutte le
diciannove grandezze in fascia su sette corse su otto; l'ottava — profilo
gioco, seme 4 — ha 46 paesi in conflitto contro un tetto di 45.

---

## 5. Gli strumenti

- `tests/13-fedelta-della-persistenza.php` — il mondo vivo e il mondo misurato
  sono lo stesso mondo (§1).
- `bin/audit.php` confronta i valori di riserva del codice con la calibrazione, e
  ha un settimo controllo: **le colonne che nessuno nomina**.
  Ne ha trovate ventisei, mai scritte ne' lette dalla prima migrazione — il
  residuo di un modello economico (bilancio, debito, riserve, prezzi) e delle
  «ansie» e «fami» di *Shadow President* realizzate poi in altro modo — piu'
  una tavola intera, `sdb_rapporto`. La migrazione 0032 le toglie.
- Le prove sono **384**, e da questo audit non dipendono piu' dall'eta' del mondo
  vivo: su un mondo appena riavviato due file sottraevano tick sotto zero.
- Il collaudo del sito vero via HTTP, con un giocatore temporaneo: tredici pagine
  pubbliche, ingresso con parola giusta e sbagliata, gettone falso, scrivania,
  poltrone, messaggi, arbitro, una poltrona occupata e lasciata, un ordine dato
  e ritirato, un messaggio spedito, l'uscita. Nessun errore nel registro di
  Apache. Il giocatore e' stato rimosso.

Il mondo vivo e' stato riavviato da zero il 23/09/2026 alle 11:47, col seme
delle alleanze corretto.

---

## 6. Quel che resta aperto

Dichiarato, non nascosto.

- **La guerra russo-ucraina non e' nel seme.** Il seme ha le guerre civili in
  corso secondo UCDP ma non l'unica grande guerra fra Stati alla data di
  divergenza. Seminarla non basta: provata in memoria, il motore la chiude con
  una conquista russa in 1,1 anni, perche' non modella gli aiuti militari
  occidentali. Serve prima quel pezzo di modello.
- **Il mondo apre ancora con un picco**: dai 34 paesi in conflitto del seme a
  una cinquantina nel primo anno (a livello di guerra da 20 a una trentina),
  poi giu' verso i 40 e gli 11-17. Prima della guerriglia cronica il picco
  arrivava a 55-64. Qualche paese in pace nel mondo vero — la Tanzania, Timor
  Est, le Comore — ci finisce dentro.
- **Le guerre fra Stati quasi non nascono**: 0-0,03 milioni di morti l'anno
  nelle corse di quindici anni. La dottrina chiede molte condizioni insieme
  (odio dichiarato, superiorita' netta, raggiungibilita', un governo
  spregiudicato) ed e' giusto che l'invasione sia rara — ma il mondo vero dal
  2014 ne ha avute parecchie.
- **Chi perde un'elezione tiene il gabinetto**: e' una domanda di progetto, non
  un difetto.
- **Ogni poltrona presidiata di una nazione conta per le sue crisi**: se al
  tavolo debba sedere solo il Capo o gli Esteri e' anch'essa una scelta di
  progetto.
- **L'Armenia ha congelato la partecipazione alla CSTO nel 2024**; il seme non
  lo sa, perche' un congelamento non e' un'uscita.
- **L'alfabetizzazione della Corea del Nord e' stimata** (0,6) dal reddito,
  perche' il Factbook non la riporta; il dato dichiarato e' il 100%.
- **Il profilo gioco** sta sul bordo alto dei conflitti (40-46 paesi), com'e'
  nel suo mandato — ma a volte ne esce di uno.
