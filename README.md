# Stanza dei Bottoni

Un **browsergame geopolitico multigiocatore a mondo persistente**: centottantanove
paesi che vanno avanti per conto loro, un tick ogni due ore, e una decina di
gabinetti in cui si possono sedere delle persone.

Non si comanda un paese. Si occupa **una poltrona** in un governo di otto, e da
lì si cerca di spostare il mondo — sapendo che le cose che contano richiedono la
firma di un collega, che niente succede nell'istante in cui lo si ordina, e che
quasi tutto quello che si fa può essere scoperto.

> Chris Crawford diceva che *Balance of Power* si vinceva non facendo niente di
> stupido, e che la parte difficile era riconoscere in tempo quale mossa fosse
> stupida. Questo gioco è costruito intorno a quella frase.

---

## Da dove viene

Tre opere, e nessuna di queste è nostra.

**Shadow President** (D.C. True, 1993) e il suo seguito spirituale **CyberJudas**
(D.C. True / Merit, 1996): simulatori geopolitici per MS-DOS in cui si guidava la
politica estera degli Stati Uniti su dati reali. Da lì vengono l'impianto — un
mondo intero descritto da indici, un gabinetto che si può ascoltare o ignorare —
e da CyberJudas l'idea che qualcuno al tavolo lavori per qualcun altro.

**Balance of Power** (Chris Crawford, 1985): da qui viene il *modello di
processo*, ed è la parte che conta. L'equazione dell'oltraggio, la scala delle
crisi, la tavola degli obblighi fra alleati, l'integrità come misura della
parola data. Crawford ha pubblicato le sue formule e le sue riflessioni sugli
errori che aveva commesso; questo progetto le ha rilette e reimplementate da
capo.

**Questo non è un rifacimento** di nessuna delle tre. È un'opera originale che
ne traduce le meccaniche in un gioco di un altro genere — asincrono,
multigiocatore, persistente — e in un'altra lingua. Non è affiliata né
autorizzata dagli autori o dai detentori dei diritti delle opere citate.

I dati di partenza vengono dal **CIA World Factbook** (opera del governo
statunitense, di pubblico dominio) e da **Natural Earth** (pubblico dominio) per
i confini della mappa.

---

## Che cosa c'è dentro

### Il mondo

Centottantanove nazioni sovrane, con popolazione, prodotto, crescita,
alfabetizzazione, maturità istituzionale, forze armate e postura nucleare
derivate dal Factbook. Trecentoundici coppie di confini. Un tick è una
settimana di gioco e due ore vere.

Ogni tick attraversa **dodici fasi** in ordine fisso, e ciascuna fa una cosa
sola: chiusura degli ordini, contro-azioni, maturazione degli eventi, economia,
società, sicurezza interna, relazioni, conflitti, intelligence, stampa,
politica di gabinetto, scadenze globali.

Il mondo gira anche senza giocatori. Anzi: gira **prima** che ci siano
giocatori, ed è così che è stato tarato.

### L'evento in volo

Nessuna azione è immediata. Ogni cosa che qualcuno ordina esiste nel mondo
**prima** di produrre effetti: parte, viaggia, matura qualche giro dopo. In
quella finestra può essere scoperta, e può essere fermata.

È il meccanismo su cui poggia tutto il resto, perché rende l'intelligence una
necessità invece che un ornamento.

### Quattro livelli di conoscenza

Di ogni evento si può sapere:

1. che è successo qualcosa
2. di che genere, e in che regione
3. chi l'ha subìto
4. **chi l'ha ordinato**

Il quarto è un ordine di grandezza più difficile degli altri tre, e i primi tre
non si saltano. La conoscenza è monotona: non si dimentica.

Da qui l'equazione che governa la diplomazia:

```
Oltraggio = danno × (affinità + obbligo) × sfera × valore × avventurismo × ATTRIBUZIONE
```

**Un'operazione che nessuno riesce ad attribuirti non ti costa niente.** È la
riga più importante del progetto: si può colpire e restare puliti, e si può far
ricadere la colpa su un terzo — col rischio che il falso venga smontato.

### L'intelligence

Sei discipline (fonti aperte, umana, segnali, immagini, cibernetica,
finanziaria) moltiplicate per la presenza che si è costruita in ciascun paese.
Nessuno sorveglia tutti: è un vincolo, ed è ciò che rende l'intelligence una
scelta invece che una barra da riempire.

I messaggi privati fra giocatori **non sono sicuri**. Il canale che si sceglie
decide quanta gente legge, e nessun canale è sicuro davvero. Un servizio che
ascolta abbastanza da vicino non solo legge: se si è preparato in anticipo, può
**riscrivere** un messaggio prima che arrivi, e il destinatario leggerà una cosa
che il mittente non ha mai detto.

La sola difesa strutturale è che *chi ha scritto sa cosa ha scritto*.

### Le crisi

Quando si può **dimostrare** chi ha colpito, lo si può contestare apertamente.
Da lì parte una scala di nove gradini, dalla nota riservata alla guerra aperta.
A ogni mossa si sale o si cede — non c'è una terza scelta, e chi non risponde in
tempo ha ceduto.

Cedere costa la faccia, e la faccia il mondo la misura. Salire costa di più a
ogni gradino, e dal quinto in su ogni passo può sfuggire di mano da solo — anche
quello di un giocatore dalla scrivania, che corre lo stesso rischio
dell'apparato. Chi non risponde entro tre giri ha ceduto. Fra
due potenze nucleari il nono gradino è praticamente irraggiungibile: la
deterrenza non è una regola scritta a parte, è il risultato dei numeri.

Prima di ogni mossa il gioco dice a entrambe le parti quanto costerebbe cedere
adesso. È l'unica informazione che trasforma la scala in una decisione invece
che in un binario. Al tavolo di una crisi siedono il Capo e gli Esteri: gli
altri ministri la vedono, ma non la muovono.

### La guerra fra Stati

Il mondo comincia nel gennaio 2026 con la guerra che c'era: la Russia contro
l'Ucraina, dal 24 febbraio 2022, coi dati del SIPRI (l'Ucraina spende il 34% del
PIL in difesa, il carico più alto del mondo) e gli aiuti che le arrivano dai
suoi sostenitori nella misura del Kiel Institute.

Una guerra non la combattono solo in due: chi parteggia nettamente per uno dei
due gli manda ogni settimana una parte del proprio bilancio militare, togliendola
ai propri arsenali. Il difensore combatte in casa e si mobilita; chi deve
attraversare il mare porta al fronte una frazione della propria forza. Si vince
per conquista, si perde ritirandosi, e le guerre di logoramento — che sono la
maggior parte — finiscono a un tavolo, con un armistizio.

Le guerre nuove nascono dove nascono nel mondo vero: fra vicini rivali, più
spesso per mano di un'autocrazia. Non si attacca chi sta sotto l'ombrello
nucleare di un alleato, né chi ha accanto un garante più forte di chi attacca.

### Il commercio

Cinque settori — energia, cibo, tecnologia, finanza, manifattura — e un grafo di
gravità che decide chi compra da chi. Da lì escono la **dipendenza** (quanta
parte del fabbisogno passa da un fornitore) e la **sostituibilità** (quanto in
fretta se ne trova un altro), che insieme decidono se una sanzione morde o è
teatro.

Un embargo non toglie punti a caso: chiude un rubinetto, e il danno lo calcola
il grafo **per tutti e due**. Chi lo impone smette di essere pagato.

| | costa a loro | costa a chi lo impone |
|---|---|---|
| Russia contro Germania | 4,17 % di crescita | 0,34 % |
| Stati Uniti contro Corea del Nord | 2,86 % | 0,00 % |
| Bolivia contro Giappone | 0,00 % | 0,00 % |

L'ultima riga è la regola di Crawford — un embargo da soli è teatro — ottenuta
per derivazione invece che per decreto.

### Il gabinetto, e il tradimento

Le poltrone sono otto per paese e ciascuna comanda un dominio. Le cose che
contano vogliono una **seconda firma**: è così che un gabinetto è un gabinetto e
non un uomo solo al comando.

Ogni giocatore riceve **due agende private** che nessun altro conosce, non
coincidono con l'interesse nazionale e a volte lo contraddicono. E qualcuno,
prima o poi, riceverà una proposta riservata da una potenza straniera.

Il traditore **non si sorteggia: matura**. Se ci sarà un Giuda al tavolo, sarà
perché qualcuno ha avuto un movente e qualcun altro ha saputo vederlo.

### L'epoca, e il giorno in cui si scopre tutto

Un'epoca dura qualche centinaio di giri. Quando finisce si conta — interesse
nazionale, agende portate a casa, prezzo pagato — ma soprattutto **si rivela**:
ogni operazione coperta col suo vero mandante, ogni talpa col suo padrone, ogni
lettera riscritta accanto al suo originale, ogni crisi con quel che c'era in
palio.

Per un'epoca intera il gioco è fatto di cose non dette. È quando si dicono tutte
insieme che si capisce che partita si stava giocando.

---

## Il simulatore autonomo

Lo stesso motore gira **senza nessun giocatore**, su un secondo profilo di
taratura. Il profilo `gioco` è tarato perché succedano cose; il profilo
`osservazione` è tarato contro i tassi storici, anche a costo di decenni in cui
non succede niente.

```
php bin/simula.php        quindici anni di mondo in un paio di minuti
```

Serve a due cose: a collaudare il profilo di gioco, e a guardare un mondo
geopolitico che evolve da solo.

I riferimenti storici sono quelli del mondo da cui il seme parte, non quelli di
Crawford: i suoi ~10 cambi di esecutivo irregolari l'anno erano giusti per il
1948-77. Oggi il Cline Center e Powell & Thyne contano 2,2-3,8 colpi riusciti
l'anno, a cui si aggiungono una o due rivoluzioni; UCDP conta 36 paesi in
conflitto armato (circa 30 negli anni Dieci) e 11 in guerra. Il profilo
`osservazione` fa 4,1-4,7 cambi irregolari l'anno, 26-29 paesi in conflitto e
8-17 in guerra, e le prove automatiche lo verificano.

---

## Il motore, fase per fase

Un **tick** è una settimana di gioco e due ore vere. Dodici fasi in ordine
fisso, ciascuna con un contratto dichiarato in testa al proprio file: che cosa
legge, che cosa scrive, quale invariante rispetta.

| | fase | che cosa fa |
|---|---|---|
| **00** | Chiusura degli ordini | la dottrina delle nazioni non giocate sceglie le proprie azioni; gli ordini dei giocatori si chiudono |
| **01** | Contro-azioni | chi ha scoperto un'operazione altrui può fermarla, sventarla o lasciarla correre |
| **02** | Maturazione | gli eventi in volo arrivano a destinazione e producono le loro conseguenze |
| **03** | Economia | crescita, quote di consumo, investimento e difesa; stock di equipaggiamento e di uomini |
| **04** | Società | l'equazione della legittimità, il clamore sociale, la qualità della vita, le ansie |
| **05** | Sicurezza interna | insurrezioni, stato di polizia, controllo dell'informazione, colpi di Stato, rivoluzioni |
| **06** | Relazioni | affinità, obblighi di trattato, integrità dei garanti, proliferazione e disarmo nucleare |
| **07** | Conflitti fra Stati | attrito, perdite, garanzie messe alla prova, esito delle guerre |
| **08** | Intelligence | raccolta, scoperta, intercettazione, attribuzione, rapporti |
| **09** | Stampa | che cosa diventa pubblico, e gli scandali |
| **10** | Gabinetto | elezioni, sfiducie, rimpasti, dimissioni, fazioni |
| **11** | Globali e scadenze | livello di pace mondiale, scadenze, chiusura d'epoca |

### Le meccaniche, una per una

**L'evento in volo.** Ogni azione esiste nella base dati *prima* di maturare.
Nel frattempo è scopribile e fermabile. È il cuore del gioco: non si agisce e si
vede il risultato, si agisce e si aspetta sapendo che qualcuno potrebbe
accorgersene.

**I quattro livelli di conoscenza.** Su ogni evento, per ogni osservatore:
*qualcosa si muove* → *di che genere* → *contro chi* → **chi l'ha ordinato**. Il
quarto livello, l'attribuzione, è la soglia politica: sotto, non si può accusare
nessuno. La conoscenza non regredisce mai.

**L'Oltraggio.** Quanto costa politicamente un'azione scoperta: danno fatto,
rapporto preesistente, e l'attribuzione come **moltiplicatore**. Un'operazione
attribuita costa molte volte una identica ma anonima.

**Le sei discipline di intelligence** — umint, sigint, imint, osint, cyber,
finint — ciascuna con una presenza per paese, e una tabella che dice quali
servono per quale dominio. Una potenza cieca su una disciplina è cieca su
un'intera classe di operazioni. La presenza si coltiva dove c'è interesse — un
vicino, un rivale, un paese che pesa — e si allarga o si sfalda con lentezza
quando l'interesse cambia. Uno scandalo scoppia una volta: il secondo servizio
che fa lo stesso nome non è una notizia nuova.

**Le crisi.** Una scala di escalation a nove gradini fra due potenze, dalla nota
diplomatica alla guerra aperta. Chi cede perde faccia; chi non cede rischia il
gradino successivo. In fondo alla scala c'è una guerra vera, e il motore la
apre.

**La falsa bandiera e i messaggi falsificati.** Si può far ricadere la colpa su
un terzo, e si possono manomettere le comunicazioni altrui in transito.

**Il Canale e le linee dedicate.** Comunicazione fra giocatori, con canali
sicuri fra potenze che si fidano abbastanza da aprirne uno.

**Il gabinetto.** Le poltrone hanno persone, con competenza, lealtà e agenda
propria. Le fazioni che ti hanno messo lì possono smettere di volerti — è il
Panel di *CyberJudas*, e pesa quanto la piazza.

**Le agende private e il Giuda.** Ogni personaggio ha obiettivi propri che non
coincidono coi tuoi, e qualcuno può tradire.

**Il commercio.** Cinque settori, modello gravitazionale, concentrazione per
settore. Una sanzione morde solo se il fornitore non è sostituibile — e chi
chiude un rubinetto smette di essere pagato per l'acqua.

**L'integrità e le garanzie.** Chi ha firmato un trattato di difesa col paese
invaso deve scegliere: entrare in guerra o perdere credibilità. È il meccanismo
con cui Crawford rende costose le promesse, e morde su **alleanze vere**. La
forza si misura sulla coalizione di chi è disposto a entrare, non sul singolo
alleato, e gli aiuti escono davvero dagli arsenali di chi li manda.
L'integrità la perde chi aveva promesso protezione — basi o difesa — a un
governo caduto per colpo di Stato o rivoluzione, non chi aveva solo un
trattato commerciale.

**L'innesco e la guerriglia cronica.** Un'insurrezione non nasce da sola dove
il terreno è favorevole: si accende con una probabilità annua, come la stimano
Fearon e Laitin — l'1,9% in media, fino a circa il 10% per i paesi più esposti.
Una volta accesa, il governo che vede crescere i ribelli sposta su di loro
truppe, bilancio e polizia: la controinsurrezione cresce con la minaccia, fino a
sette volte. È ciò che permette al mondo di avere, come quello vero, molti
conflitti a bassa intensità che durano decenni e pochi che arrivano alla guerra
civile.

**Le urne.** Chi governa male perde le elezioni, e chi perde il governo perde
anche il gabinetto: dell'apparato resta qualche ministro forte, il resto è gente
nuova.

**La deriva politica.** Accanto alla legittimità che l'economia e la società
spiegano c'è una parte che nessun dato cattura — qualità di governo, coesione,
fortuna — che vaga di un paio di punti e si riconduce piano allo zero. Un nuovo
governo la riscrive: alcuni consolidano per un decennio, altri cadono in sei
mesi.

**La cronaca.** Colpi di Stato, elezioni, guerre, incidenti armati, test
atomici, disarmi, garanzie onorate e tradite, sanzioni, talpe scoperte,
reclutamenti denunciati, dimissioni, la fine di un'epoca: tutto ciò che nessuno
riesce a nascondere arriva in cronaca, raccontato. Un'operazione coperta ci
arriva solo quando qualcuno la dimostra.

**La delega e l'epoca.** Un giocatore assente lascia il posto all'apparato; chi
delega a un altro giocatore gli presta la poltrona, e negli atti restano tutte e
due le firme. A fine epoca si contano i punti, e le agende difensive arrivate
fino in fondo sono riuscite.

**I limiti del giocatore.** Due ordini per poltrona a ogni giro, tre messaggi
sui canali riservati (cifrato e corriere), e nessuno può controfirmare ciò che
ha proposto — nemmeno con la poltrona di un altro in mano per delega.

**Il banco dell'arbitro.** Leve di calibrazione imponibili a mondo acceso.

---

## Le fonti: che cosa c'è dentro, e da dove viene

Questo motore non inventa quasi niente. Le cose inventate sono marcate
`[FABBRICATO]` nella taratura — ventidue voci, tutte in chiaro. Tutto il resto
viene da qualche parte, e la regola è che **la fonte si data quando la si
cita**: un riferimento che non si può datare non si può nemmeno dichiarare
scaduto.

### I tre giochi, e il libro

| | |
|---|---|
| **Chris Crawford**, *Balance of Power* (1985) e *Balance of Power: the Book* | il modello dei processi: la regola logistica, l'equazione della legittimità, l'Oltraggio, l'integrità dei garanti, la scala 0-128 degli obblighi, «la storia pesa otto volte l'ideologia» |
| **Shadow President** (1993) | il quadrante della città: qualità della vita, postura nucleare a sette livelli, il *rocker* dell'intensità, il tetto alla crescita |
| **CyberJudas** (1996) | il Panel delle fazioni, i quattro livelli di conoscenza, il glossario delle ideologie |

### Le condizioni iniziali

| fonte | che cosa dà | vintage |
|---|---|---|
| **CIA World Factbook** (via `factbook.json`) | popolazione, prodotto, crescita, alfabetizzazione, effettivi, quota militare, area | clone dell'11/09/2026, voci «2024 est.» e «2025 est.»; un intervallo («20-30%») vale il suo punto medio |
| **V-Dem Institute**, Università di Göteborg — *Liberal Democracy Index* | l'asse democrazia-autocrazia: chi vota, quando un ricambio è irregolare, chi si disarma, quanto un regime stringe sull'informazione | 2025 |
| **Banca Mondiale** (PIP/WDI) — *indice di Gini* | la disuguaglianza verticale, da cui il consumo mediano | anno mediano 2021 |
| **Ethnic Power Relations (EPR) Core**, ETH Zurigo | la disuguaglianza orizzontale: quanta popolazione è esclusa dal potere esecutivo, e in quanti gruppi | 2021 |
| **Correlates of War** — *Formal Alliances v4.1* | gli obblighi di trattato veri, per diade direzionata | 2012, con gli allargamenti NATO successivi aggiunti a mano e datati, e gli scioglimenti che il dataset non sa: l'Ucraina fuori dalla CSI (2018), la Georgia (2009) |
| **UCDP/PRIO** — *Armed Conflict Dataset* | i conflitti armati in corso al momento della divergenza | 2024 |
| **Freedom House** — *Freedom in the World* | i sedici micro-Stati che V-Dem non copre | stima dichiarata |

### I modelli e le misure

| fonte | che cosa dà al motore |
|---|---|
| **Fearon & Laitin (2003)**, *Ethnicity, Insurgency, and Civil War*, APSR 97(1) | i predittori dell'insorgenza: popolazione grande e povertà, non l'etnia. Il reclutamento insurrezionale scala con la popolazione e col reddito inverso; l'innesco è una probabilità annua (1,9% in media nel 1945-99) |
| **Vasquez**, *The War Puzzle* (1993); **Senese & Vasquez** (2008); **Diehl & Goertz**, *War and Peace in International Rivalry* (2000) | le guerre fra Stati nascono soprattutto fra vicini con una disputa territoriale, dentro rivalità di lunga durata |
| **Huth**, *Extended Deterrence and the Prevention of War* (1988) | non si attacca chi ha un garante impegnato e più forte |
| **Mearsheimer**, *The Tragedy of Great Power Politics* (2001) | il «potere d'arresto dell'acqua»: chi attraversa il mare porta al fronte una frazione della sua forza |
| **Mueller** (1973) | la stanchezza di guerra, che nelle democrazie pesa più che nelle autocrazie |
| **Kiel Institute**, *Ukraine Support Tracker* (febbraio 2025) | gli aiuti militari a un paese in guerra: circa 45 miliardi di euro l'anno all'Ucraina |
| **Cederman, Wimmer & Min (2010)**, *Why Do Ethnic Groups Rebel?*, World Politics 62(1)<br>**Cederman, Weidmann & Gleditsch (2011)**, APSR 105(3) | la disuguaglianza orizzontale: il **motivo** accanto all'occasione |
| **Goldstone et al. (2010)**, *A Global Model for Forecasting Political Instability*, AJPS 54(1) — il **Political Instability Task Force** | la U rovesciata del tipo di regime, la faziosità, il contagio dal vicinato, la qualità della vita |
| **Collier et al. (2003)**, *Breaking the Conflict Trap*, Banca Mondiale | quanto costa una guerra civile: 2,3 punti di crescita l'anno |
| **Archigos** (Goemans, Gleditsch, Chiozza) | la quota di uscite irregolari dal potere: circa un quinto |
| **Cline Center Coup d'État Project** e **Powell & Thyne** | i colpi di Stato riusciti per decennio — e la scoperta che i «~10 l'anno» di Crawford sono gli anni Sessanta, non il presente |
| **SIPRI** — *Military Expenditure* e *Yearbook* | l'onere militare mondiale, gli Stati dotati di nucleare, e la spesa ucraina del 2024 (34% del PIL) dove il Factbook si ferma al 2021 |
| **IISS** — *The Military Balance* | gli effettivi sotto le armi, e il tetto del 5% della popolazione (la Corea del Nord, il paese più militarizzato del mondo) |
| **ONU** — *World Population Prospects* | la crescita della popolazione |
| **Eckhardt**, ripreso dal **CICR** | la quota civile dei morti di guerra: circa metà, da tre secoli |
| **Acklam** | l'inversa della normale, per ricavare dal Gini il rapporto fra consumo mediano e medio |

### E il cruscotto che tiene tutto onesto

```bash
php bin/realismo.php --anni=15      # diciannove grandezze contro la loro fascia
php bin/audit.php                   # che cosa è dichiarato e mai usato
php bin/prova.php                   # quattrocentodue prove
```

`bin/realismo.php` confronta diciannove grandezze con la fascia in cui il mondo
vero le tiene, **ciascuna con la fonte accanto**. La distinzione che ci sta
dentro non è pedanteria: un *livello* si giudica al seme, perché dopo quindici
anni di crescita non è più confrontabile col dato di oggi; un *tasso* si giudica
sulla corsa, perché è lì che vive il comportamento del motore.

`bin/audit.php` cerca l'altra classe di difetti — quella che le prove non
vedono: una chiave di calibrazione che nessuno legge, un verbo che la dottrina
non sceglie mai, una tabella o una colonna dello schema che nessuna riga di
codice nomina, un valore di riserva nel codice diverso dalla taratura. È la
forma di guasto che questo progetto ha trovato più spesso.

E una prova, `tests/13`, tiene onesto il cruscotto stesso: fa girare lo stesso
mondo in memoria — come fa `bin/realismo.php` — e attraverso la base dati a
ogni tick — come fa il server — e pretende che coincidano fino all'ultima
cifra. Fino al settembre 2026 non coincidevano, e tutte le misure descrivevano
un mondo diverso da quello che girava davvero.

---

## Che cosa non è

**Non è una previsione.** Nessuna delle cifre che il modello produce dice
qualcosa sul mondo vero. I tassi di partenza vengono da dati reali; tutto quel
che succede dopo è il prodotto di un modello dichiaratamente approssimativo,
tarato per raccontare bene invece che per avere ragione.

**Non è un'opinione politica.** Le ideologie sono etichette, le affinità sono
numeri in una matrice, e il modello non pensa che qualcuno abbia ragione.

**Non è un manuale.** Le meccaniche coperte — operazioni, falsi, reclutamenti,
manomissione delle comunicazioni — sono una traduzione ludica di cose che
esistono, ridotte a poche variabili. Chi cerca qui come si fa davvero una di
quelle cose non troverà niente di utile, ed è voluto.

La taratura è piena di numeri marcati `[FABBRICATO]`: sono ordini di grandezza
plausibili scelti perché il gioco funzionasse, e stanno tutti in
`calibrazione/`, in chiaro, uno accanto all'altro. Non sono stime.

---

## Le carte in tavola

Il progetto documenta i propri difetti, e in `docs/` ce ne sono parecchi
raccontati per esteso: un generatore casuale che spingeva il mondo all'ingiù per
giorni senza che nessuno se ne accorgesse, l'etica derivata dalle istituzioni
che rendeva le democrazie mature incapaci di azione coperta, tre casi di una
fase che scriveva e un'altra che sovrascriveva.

**`docs/24-audit-della-simulazione.md`** è un audit sistematico fatto con misure
e non con riletture. Dice quel che è risultato sano — determinismo, persistenza,
salute numerica su quindici anni — e i quattordici punti in cui il modello
promette una cosa e ne fa un'altra: tre dimensioni che il motore legge e nessuno
scrive, un gradino della tavola di Crawford irraggiungibile, quattro verbi su
diciotto che l'apparato non sceglie mai, quindici manopole di taratura scollegate.

**`docs/26-i-numeri-realistici.md`** è il secondo, in quindici sezioni: il
sessanta che non voleva dire niente e faceva sessantaquattro milioni di morti in
una guerra bilaterale, le tre pompe nell'economia, la variabile che nessuno
leggeva, la riscrittura che abbiamo **deciso di non fare** e perché.

**`docs/28-la-guerra-fra-stati.md`** è il seguito del terzo: la guerra
russo-ucraina nel seme, le guerre fra vicini, la deterrenza, l'armistizio,
l'innesco delle insurrezioni.

**`docs/27-audit-totale.md`** è il terzo, e il più largo: motore, gioco, sito e
seme. Il reperto centrale è che il mondo vivo non era il mondo misurato — quel
che la base dati non salvava, o salvava arrotondato, tornava al seme ogni due
ore. E poi: la guerra fra Stati che una fase cancellava, i servizi segreti che
diventavano ciechi, le garanzie tradite trenta volte su trentuna, la Corea del
Nord con una spesa militare di -30% e quindici milioni di soldati, la Russia
garante della difesa dell'Ucraina, e la guerriglia cronica che il modello non
sapeva produrre.

### Quel che oggi non funziona come dovrebbe

- **Un'invasione cinese di Taiwan, quando avviene, riesce.** Senza un trattato
  gli Stati Uniti mandano materiale e non combattono: l'intervento diretto di
  chi non è alleato — l'«ambiguità strategica» — chiede un meccanismo che il
  modello non ha. I giochi di guerra del CSIS (gennaio 2023) la danno per lo più
  fallita proprio perché gli Stati Uniti e il Giappone intervengono.
- **Un civile per ogni militare caduto** è la media storica, e vale per tutte
  le guerre: in quella russo-ucraina i civili sono molti meno, e il totale dei
  morti ne esce raddoppiato.
- **Timor Est** finisce spesso in conflitto perché il suo PIL reale, nel
  Factbook, comprende il petrolio in esaurimento: una crescita media di -13,6%.
- **La CSI come patto di difesa** nel Correlates of War lega ancora paesi che
  non si difenderebbero mai: fra nemici dichiarati il motore rompe il trattato al
  primo tick, fra indifferenti resta.
- **Metà dei conflitti seminati si spegne in quindici anni**, e altrettanti ne
  nascono altrove. In parte è giusto — i conflitti veri finiscono — ma il
  modello non sa *quali* devono durare.
- **La crescita sta sul lato basso**, fra l'1,9% e il 2,4% contro un riferimento
  del 2,9%: il freno di maturazione agisce su tendenze che quella maturità la
  incorporano già, e c'è un doppio conteggio dichiarato.
- **La U rovesciata di Goldstone non raggiunge la sua magnitudine.** Le
  democrazie piene stanno correttamente a 0,2 volte le autocrazie, ma i regimi
  parziali restano intorno a 1 invece di 5-30. Crawford e Goldstone sono in
  tensione strutturale, e la sezione 13 di `docs/26` spiega perché abbiamo
  scelto di non riscrivere l'equazione della legittimità.
- **La faziosità copre quattordici nazioni su centottantanove**, perché i
  gabinetti esistono solo per le potenze giocabili.
- **Il commercio ha un limite strutturale**: la taglia assoluta decide troppo, e
  il Belgio esporta zero.

Sono difetti noti e scritti, non nascosti.

---

## Come si mette in piedi

Serve **PHP 8.2+** (sviluppato su 8.4), **MariaDB 10.6+** o MySQL 8, e un
server web. Nessuna dipendenza: niente Composer, niente framework, niente
pacchetti da scaricare. L'autoloader è quaranta righe.

```bash
# 1. database, utente, schema, seme, primo tick
sudo bash deploy/00-avvio.sh

# 2. pubblicazione sotto Apache (sotto-percorso del vhost esistente)
sudo bash deploy/01-apache.sh

# 3. il mondo va avanti da solo
crontab -e
  0 */2 * * * cd /percorso/del/progetto && php bin/tick.php --silenzioso >> storage/logs/tick.log 2>&1
  */5  * * * * cd /percorso/del/progetto && php bin/posta.php  >> storage/logs/posta.log 2>&1
  17 4 * * *   cd /percorso/del/progetto && bash deploy/salvataggio.sh >> storage/logs/salvataggio.log 2>&1
```

La configurazione con le credenziali vive **fuori dal docroot**, in
`/etc/stanzadeibottoni/config.php`; in `config/config.example.php` c'è la
struttura senza valori.

### Gli strumenti

```
php bin/tick.php              un passo del mondo
php bin/simula.php            quindici anni a vuoto, con le statistiche
php bin/diagnostica.php       la salute del modello
php bin/prova.php             le prove automatiche (402, circa due minuti)
php bin/realismo.php          diciannove grandezze contro le fonti
php bin/audit.php             che cosa è dichiarato e mai usato
php bin/migra.php             applica le migrazioni non ancora applicate
php bin/epoca.php             apre e chiude le epoche, e conta
php bin/costruisci_mappa.php  scarica i confini e li converte in tracciati SVG
php bin/importa_factbook.php  rigenera il seme dal World Factbook
```

### Che cosa manca in questa copia

Il **World Factbook** non è ridistribuito qui: è materiale di terzi e pesa
tredici megabyte. Il seme già derivato — `db/seed/nazioni.csv`, centottantanove
righe di indici — **è incluso**, quindi il gioco parte lo stesso. Chi vuole
rigenerarlo scarica il Factbook e lancia `bin/importa_factbook.php`.

Non ci sono account, partite in corso, né le impostazioni della nostra
installazione: questa è la copia **factory default**.

---

## La lingua

Tutto è in italiano: le tabelle, le colonne, le classi, i metodi, i commenti,
la documentazione, l'interfaccia. Non è un vezzo — è più facile ragionare su un
modello quando i nomi sono nella lingua in cui ci si pensa, e questo progetto è
fatto di modelli.

---

## Licenza

**GNU General Public License v3.0** — vedi `LICENSE`.

I dati di partenza sono di pubblico dominio (CIA World Factbook, Natural Earth).
I numeri di taratura marcati `[FABBRICATO]` sono invenzioni di questo progetto e
seguono la stessa licenza del codice.

Opera amatoriale, senza scopo di lucro, non affiliata né autorizzata dagli
autori o dai detentori dei diritti di *Shadow President*, *CyberJudas* e
*Balance of Power*. Su richiesta degli aventi diritto, qualunque parte che li
riguardi viene rimossa.
