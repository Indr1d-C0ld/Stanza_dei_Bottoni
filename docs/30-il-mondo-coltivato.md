# 30 — Il mondo coltivato (ottobre 2026)

## Da dove nasce

Dopo tre anni e mezzo di mondo vivo (tick 186, fine luglio 2029) l'osservatore
ha chiesto se Stanza dei Bottoni stesse davvero «coltivando» un mondo
realistico, e di cercare prima di tutto le incoerenze.

Il mondo, a grandi linee, teneva. La popolazione cresceva dello 0,85% l'anno, il
prodotto del 2,6%, l'onere militare saliva come nei dati SIPRI. C'erano nove Stati
nucleari e circa tre cambi irregolari l'anno, sempre nei posti giusti (Sudan,
Mali, Burkina Faso, Birmania, Haiti, Venezuela). La guerra russo-ucraina era
ancora aperta. Ma guardando dentro i numeri sono saltate fuori sei incoerenze,
e due di queste nascondevano un difetto del motore più vecchio di loro.

---

## 1. L'integrità dei garanti

**Il sintomo.** Gli Stati Uniti avevano un'integrità di 0,2 su 128, l'Arabia
Saudita 7, il Canada e il Messico 10, il Brasile 41. Settantasei paesi stavano
sotto la metà. L'Europa e l'Asia invece erano intatte.

**La causa.** Una caduta irregolare costava la faccia a chiunque avesse
garantito il paese, e i patti multilaterali del Correlates of War (il Trattato
di Rio, la Lega Araba, i patti africani) rendono garanti decine di paesi alla
volta. Undici governi caduti per colpo di Stato o rivoluzione, nessuno con una
mano straniera sopra, avevano azzerato la credibilità di mezzo mondo. Ma un
patto di difesa promette protezione da un nemico esterno, non da un colpo di
Stato interno.

**La correzione.** Conta solo la caduta su cui c'è una mano straniera: un'ingerenza
ostile andata a segno nell'ultimo anno (armi ai ribelli, fondi all'opposizione,
destabilizzazione, una trama) o una guerra in corso con un altro Stato. La
nazione ricorda l'ultima ingerenza e chi l'ha fatta (migrazione 0035). Chi ha
messo le mani sul proprio cliente non perde la faccia di garante: quella la
paga la sua reputazione. Nei patti a molti il conto lo paga per intero il
garante più forte, e gli altri in proporzione al loro peso:

> OLSON, ZECKHAUSER (1966), «An Economic Theory of Alliances», *Review of
> Economics and Statistics* 48(3): la difesa comune è un bene pubblico, e chi
> conta di più ne porta il carico.

La stessa regola vale per le garanzie tradite in guerra (fase 07).
Nel mondo vivo l'integrità è stata riportata a 128 per tutti, perché tutta
quella perduta lo era per la regola sbagliata.

---

## 2. Il ciclo economico, che non c'era

**Il sintomo.** Contato sul tasso settimanale, un paese su cinque risultava in
recessione. Ma contato sul PIL di un anno intero la quota era 10,6-12,2%, cioè
nella norma: il primo numero era un artefatto. Il difetto vero era un altro: in
recessione c'erano sempre gli stessi paesi.

**La causa.** Il ciclo si estraeva con l'indice «tick/26», per restare fermo sei
mesi. Ma il generatore casuale ha un seme diverso a ogni tick, quindi
l'estrazione era nuova ogni settimana: rumore bianco di ±3 punti, che in un anno
si media a un quarto di punto. Il ciclo economico non esisteva. Allo stesso modo
si estraeva ogni settimana la «tregua» che segue un cambio di governo, che
doveva valere fra 0,8 e 2,6 anni e durava quasi sempre il minimo.

**La correzione.** Al contesto del tick si aggiunge un caso del mondo, con il seme
radice (`ContestoTick::delMondo()`). Il ciclo diventa un'onda lenta, con un nodo
all'anno e un'interpolazione continua (`Caso::onda()`), in tre componenti:

> KOSE, OTROK, WHITEMAN (2003), «International Business Cycles: World, Region,
> and Country-Specific Factors», *American Economic Review* 93(4): un fattore
> mondiale che pesa di più nelle economie avanzate, uno regionale minore, il
> resto nazionale.
>
> KOREN, TENREYRO (2007), «Volatility and Development», *Quarterly Journal of
> Economics* 122(1): le economie povere oscillano circa il doppio.

L'ampiezza (1,4% per i paesi ricchi, 2,9% per i poveri) è tarata sulla quota di
economie in recessione del FMI.

---

## 3. Le tendenze di crescita

**Il sintomo.** Venezuela, Yemen, Sud Sudan, Afghanistan e Timor Est avevano una
tendenza di −4% l'anno, per sempre.

**La causa.** La crescita del seme era la media delle ultime tre voci del Factbook,
e per alcuni paesi quelle voci erano vecchie: il Venezuela era fermo al 2017-18
(−17,7%, mentre nel 2022-24 è cresciuto del 4-8%), il Sud Sudan al 2015-17, lo
Yemen al 2016-18. Quella media diventava la tendenza del paese e non tornava mai.

**La correzione.** Le tendenze vengono dal **FMI, World Economic Outlook, aprile
2026**: per ogni paese la crescita recente (mediana 2023-2025) e quella di medio
periodo (media delle proiezioni 2026-2030), con `bin/importa_fmi.php`.
Oltre l'orizzonte delle proiezioni, la tendenza pro capite torna verso quella del
paese mediano:

> PRITCHETT, SUMMERS (2014), «Asiaphoria Meets Regression to the Mean», NBER
> Working Paper 20573: il ritorno alla media è il fatto più robusto sulla
> crescita dei paesi, e la crescita di un decennio dice pochissimo su quella del
> successivo.

Strada facendo sono emersi tre doppi conteggi, che tenevano la crescita mondiale
mezzo punto sotto il FMI:

- **il freno di maturazione.** Era una formula fabbricata che schiacciava la
  crescita verso l'1,4% in proporzione al reddito, e faceva crescere la Germania
  dell'1,5% invece dello 0,96%. Via: il rallentamento di chi si arricchisce è
  dentro le proiezioni del FMI, e oltre c'è il ritorno alla media;
- **le guerre del seme pagate due volte.** Le proiezioni del FMI per la Nigeria,
  il Pakistan o il Messico contano già i loro conflitti. Adesso il costo della
  guerra (Collier 2003) vale solo per quanto un conflitto è peggiore di quello
  della divergenza;
- **i punti neutri del bilancio.** Erano due costanti misurate a mano su un seme
  vecchio (0,71 e 0,21). Col seme nuovo il paese medio puntava al 19% di
  investimenti invece del suo 22% fin dal primo giorno. Adesso si derivano dalle
  stesse formule, in un paese in condizioni normali.

C'era anche un errore di unità: l'aspettativa di crescita partiva dal PIL totale
e si confrontava col consumo **pro capite**. Dove la popolazione cresce del 2-3%
l'anno, la gente risultava delusa di altrettanto per i primi cinque anni.

---

## 4. La legittimità

**Il sintomo.** La legittimità media scendeva di tre punti l'anno, da 60 a 49.

**La causa.** Il seme la faceva partire da una formula sua (maturità, crescita,
alfabetizzazione), con media 60, mentre il modello la riportava verso 50,
uguale per tutti, dalla Danimarca alla Somalia. La discesa era un'incoerenza del
seme, non qualcosa che accadeva nel mondo.

**La correzione.** Il punto di riposo diventa una proprietà del paese:
`Nazione::ancoraLegittimita()`, cioè 50 più 7 punti per deviazione standard di
qualità del governo. La qualità è la media di stabilità politica, efficacia del
governo e stato di diritto dei **Worldwide Governance Indicators** della Banca
Mondiale (edizione 2026, media 2023-2025), importata con `bin/importa_wgi.php`.

> GILLEY (2006), «The Meaning and Measure of State Legitimacy», *European
> Journal of Political Research* 45(3): la legittimità dello Stato va con la
> qualità del governo e lo stato di diritto più che con la forma democratica.

Il seme parte dal punto di riposo. La Danimarca riposa a 63, la Somalia a 34, il
Ruanda a 52 e il Madagascar a 44.

La legittimità del modello fa però due mestieri: è la solidità del regime, che
decide colpi di Stato e insurrezioni, ed è la popolarità del governo, che decide
le elezioni. Le elezioni, le sfiducie e la pressione a comprare consenso con i
consumi guardano quindi lo **scarto** dal punto di riposo del paese. Un governo
danese mediocre perde le elezioni anche se il regime danese non è in discussione.

Taiwan manca dall'API della Banca Mondiale, anche se il dataset completo la
pubblica: per ora prende i valori della Corea del Sud, ed è un ripiego dichiarato.

---

## 5. Le guerre civili

**Il sintomo.** Liberia, Togo, Benin, Burundi e Zambia erano in guerra civile
piena: il Togo aveva 36.600 insorti contro un esercito che valeva 6.000. Sedici
paesi stavano al tetto del 10% annuo di probabilità che un'insurrezione si
accenda, compreso il Ruanda, mentre l'India era al 2,7%.

**La causa.** Era meccanica, due volte. L'innesco cresceva col rapporto fra il
reclutamento possibile e la forza del governo. E il reclutamento cresceva con
popolazione e povertà, contro una forza del governo che cresce con la radice di
popolazione per PIL. Il rapporto fra le due aveva un'elasticità di −1,5 al
reddito e nessuna alla popolazione: il Madagascar risultava ventiquattro volte
più esposto dell'India. Sopra la soglia oltre la quale il governo non regge più
(sette volte la propria forza), ogni paese povero finiva in guerra civile.

**La correzione.** Sia l'innesco sia il terreno su cui l'insurrezione cresce sono
il rischio stimato, con i suoi coefficienti:

> FEARON (2010), «Governance and Civil War Onset», documento di base del *World
> Development Report 2011*, Banca Mondiale. È la ristima, con dati più completi e
> il reddito in logaritmo, del modello di FEARON e LAITIN (2003), «Ethnicity,
> Insurgency, and Civil War», *APSR* 97(1), con in più la qualità del governo.

| | innesco (tabella 2, mod. 3: tutti i conflitti UCDP) | terreno (tabella 2, mod. 1: guerre maggiori) |
|---|---|---|
| reddito (log) | −0,26 (−0,351 senza governo) | −0,20 (−0,404 senza governo) |
| popolazione (log) | +0,238 | — |
| territorio accidentato (log) | +0,151 | +0,360 |
| petrolio | +0,715 | +1,095 |
| regime parziale | +0,355 | +0,258 |
| instabilità recente | +0,466 | — |
| stabilità politica WGI | −0,93 (tabella 21) | −0,97 (tabella 20) |

Due scelte, dichiarate:

- **la popolazione predice che una guerra cominci, non che i ribelli diventino
  forti quanto lo Stato**, che cresce con la popolazione quanto loro. Sta
  nell'innesco e non nel terreno: con +0,203 nel terreno la Cina passava otto
  anni su dieci in insurrezione grave;
- **la qualità del governo è la sola stabilità politica**, che è la variabile di
  Fearon. Col composito di stabilità, efficacia e stato di diritto la Corea del
  Nord e l'Eritrea, pessime nelle ultime due ma stabilissime, finivano in guerra
  civile.

Il terreno accidentato viene da NUNN e PUGA (2012), «Ruggedness: The Blessing of
Bad Geography in Africa», *Review of Economics and Statistics* 94(1). La loro
quota di territorio molto accidentato ha la stessa distribuzione della quota
montuosa di Gerrard usata da Fearon (mediana 11,8% contro 9%). Il petrolio è il
primo prodotto esportato nel Factbook, se è greggio o gas, con due correzioni
dichiarate: l'Iran sì, gli Stati Uniti no. Tutto con `bin/importa_terreno.php`.

Il reclutamento si misura in unità della forza del governo al seme: un governo
che si arma resta più forte dei ribelli. Il malcontento, l'esclusione etnica
(Cederman, Wimmer e Min) e l'effetto carrozzone restano come prima.

Su dieci anni le guerre civili cadono quasi tutte dove le mette l'UCDP. Il Togo
resta guerriglia, Benin e Senegal si spengono, la Corea del Nord non si
accende. Fuori dall'elenco UCDP i livelli più alti sono guerre fra Stati
(Russia-Ucraina, Cina-Taiwan) oppure paesi petroliferi instabili (Venezuela,
Algeria, Angola).

---

## 6. Il profilo del mondo vivo

Il mondo vivo gira col profilo «gioco», tarato perché succedano più cose. Quello
tarato sul realismo è «osservazione». Il cambio tocca
`/etc/stanzadeibottoni/config.php` (`mondo.profilo`) e lo decide l'osservatore.

---

## Le misure

Quattro semi per due profili, quindici anni. **Tutte e 21 le grandezze stanno in
fascia in tutti e otto i mondi.** Le tre fasce nuove:

| grandezza | prima | adesso | riferimento |
|---|---|---|---|
| crescita del PIL mondiale | 1,9-2,4% | 2,3-3,1% | FMI 2026-30: 3,1-3,3%; OCSE (Guillemette e Turner 2021): 2,7% nei primi anni Trenta, 2,1% nei primi Quaranta |
| economie in recessione in un anno | sempre le stesse | 7-13% | FMI: 10% di mediana fuori dalle crisi mondiali |
| paesi con almeno una recessione in 15 anni | meno di uno su sei | 41-62% | FMI 2010-2024 senza il 2020: 52% |
| paesi in insurrezione grave o guerra civile | 11-19 (docs/27) | 6-14 | UCDP 2024: 11 al livello di guerra |
| gradiente per taglia (punti) | — | +18/+22 | UCDP 2024: circa +25 |

Le prove sono 445, e `tests/16-il-mondo-coltivato.php` verifica le sei
correzioni una per una.

---

## I dati

| file del seme | fonte | importatore |
|---|---|---|
| `db/seed/crescita.php` | FMI, World Economic Outlook aprile 2026 (NGDP_RPCH), API DataMapper | `bin/importa_fmi.php` |
| `db/seed/governo.php` | Banca Mondiale, Worldwide Governance Indicators 2026 (PV, GE, RL, VA) | `bin/importa_wgi.php` |
| `db/seed/terreno.php` | Nunn e Puga 2012 (rugged_pc); Factbook, primo prodotto esportato | `bin/importa_terreno.php` |

I file grezzi stanno in `storage/fonti/`, fuori dal repo pubblico come il
Factbook; ogni importatore dice da dove riscaricarli.

## Che cosa resta aperto

*Risolti in `docs/31`: le invasioni (un rischio annuo per rivalità, dai dati),
Taiwan nei WGI (dal dataset completo), il petrolio (dalle esportazioni di
combustibili della Banca Mondiale).*

**Le invasioni sono troppo rare.** In sei mondi da quindici anni la dottrina ne
lancia una (Azerbaigian contro Armenia); col codice di prima erano quattro, e il
mondo vero ne ha avute quattro-sei nel 2010-2025. Le condizioni passano: la
Cina sceglie di invadere Taiwan, la Russia di tornare in Ucraina, molte volte.
Ma ogni volta che una nazione agisce, pesca fra le **cinque coppie col rapporto
più intenso**, in un senso o nell'altro, e quelle coppie stanno fra il sesto e
l'undicesimo posto su 188, dietro alleati e rivali più prestigiosi. Prima ci
arrivavano un po' più spesso per caso: in un mondo più ostile (40-50 coppie
sotto −70 contro le 30 di adesso, che sono circa le rivalità strategiche
attive) le altre coppie stavano più in basso. Quel filtro non è un dato, e la
correzione giusta è un rischio annuo di guerra per rivalità, preso dai dati
(Thompson e Dreyer, *Handbook of International Rivalries*, 2012; ICOW, Hensel),
al posto della pesca fra le prime cinque. È anche il rilievo dell'audit:
`invasione` non scelta in tre semi.

- Taiwan nei WGI, dal dataset completo invece che dalla Corea del Sud.
- Il petrolio dalle rendite naturali della Banca Mondiale (NY.GDP.TOTL.RT.ZS),
  che è la definizione di Fearon (2010), invece che dal primo prodotto esportato.
- Una guerra che finisce non regala niente alla crescita: la ricostruzione non è
  modellata.
- Le insurrezioni del seme possono finire in pochi mesi con la vittoria dei
  ribelli (la Birmania, su un seme): è il disegno della fase 05, non un dato.
- Restano aperti anche i punti di docs/28 §5: Cina-Taiwan, un civile per
  militare, Timor Est, la CSI.
