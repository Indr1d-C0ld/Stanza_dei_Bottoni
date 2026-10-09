# 36 — I conti che tornano (ottobre 2026)

## Da dove nasce

`docs/35` aveva chiuso quattro difetti vecchi e ne aveva lasciati aperti quattro:

1. i regimi parziali faziosi che cadevano circa 1,7 volte il vero;
2. le economie piccole e aperte che commerciavano un quinto meno del vero;
3. l'Iran, Cuba, l'Eritrea, la Corea del Nord e la Siria col commercio inventato;
4. i rapporti dichiarati, scritti a mano, che i dati spiegavano al 62%.

Li ho ripresi tutti. Il primo ha portato al difetto più grosso di questo blocco,
che non era quello che cercavo.

---

## 1. I colpi di Stato, fascia per fascia

**La domanda di partenza.** Perché i regimi parziali faziosi cadono il doppio del
vero? Il termine della faziosità non c'entrava: col peso a zero i numeri erano
identici. Ho messo una sonda nella fase 05 per misurare l'azzardo atteso di ogni
paese a ogni tick, invece di contare i colpi, che sono pochi e rumorosi. Poi ho
confrontato col dato in due modi.

**Per stabilità politica.** Powell e Thyne, regimi parziali (democrazia liberale
V-Dem fra 0,05 e 0,55), 2000-2025, regressione di Poisson sulla stabilità
politica WGI dell'anno prima:

| | pendenza | a stabilità 0 | a stabilità −1 |
|---|---|---|---|
| mondo vero | −0,57 ± 0,19 | 8,7 ‰ | 15,4 ‰ |
| modello di prima | −1,00 | 8-10 ‰ | 23-29 ‰ |

A parità di vicinato la pendenza vera è −0,45 ± 0,21. Il vicinato vale 2,0 volte
con quattro o più confinanti in conflitto (± 1,7), e il modello usa 2,2: quello
era giusto. Era la curva della legittimità a essere troppo ripida: i paesi
fragili, con la legittimità bassa, cadevano tre volte il vero.

**Per tipo di regime.** Qui il difetto vero. Colpi riusciti ogni mille anni-paese
per fascia di democrazia liberale:

| democrazia liberale | mondo vero | modello di prima | registrati come |
|---|---|---|---|
| sotto 0,05 | 0 (265 anni-paese) | 3-4 | colpi |
| 0,05-0,15 | 9,2 | 21-27 | colpi |
| 0,15-0,25 | 14,5 | 45-51 | colpi |
| 0,25-0,40 | **16,2** | 10-16 | **crisi di governo** |
| 0,40-0,55 | **8,2** | 6-9 | **crisi di governo** |
| sopra 0,55 | 0 (1.488 anni-paese) | 1,3-1,5 | crisi di governo |

Diciannove dei 36 colpi veri del 2000-2025 sono caduti fra 0,25 e 0,55: il Mali,
il Niger, la Birmania, la Thailandia due volte, il Burkina Faso, l'Honduras,
l'Ucraina nel 2014. Il modello aveva i rischi giusti per quei paesi, ma li
registrava come crisi di governo. Un cambio era «irregolare» sotto 0,25, che è
la soglia con cui la fase 10 decide chi vota. Un paese però può votare e subire
un colpo di Stato.

**La correzione.**

- Sotto 0,55 di democrazia liberale una caduta è un colpo di Stato, sopra è una
  crisi di governo (`colpo_di_stato.democrazia_regolare`).
- La curva è più dolce: pendenza da 7 a 18, rischio massimo da 6,5% a 3% l'anno,
  soglia da 38 a 34.
- Lo spostamento dei regimi parziali passa da 12 a 24, per tenere i paesi fra
  0,25 e 0,55 al loro livello.

| democrazia liberale | modello adesso (azzardo) | mondo vero | intervallo di Poisson al 95% |
|---|---|---|---|
| 0,05-0,15 | 12-18 | 9,2 | 4-18 |
| 0,15-0,25 | 21-25 | 14,5 | 7-28 |
| 0,25-0,40 | 10-12 | 16,2 | 9-28 |
| 0,40-0,55 | 6-7 | 8,2 | 3-18 |

Tutte e quattro le fasce stanno dentro l'intervallo del dato.

**L'esito sui faziosi**, quattro semi, quindici anni:

| | modello | mondo vero (2000-2025) |
|---|---|---|
| parziali faziosi | 19,7 ‰ (12-27) | 17,4 ‰ |
| parziali non faziosi | 10,9 ‰ (9-12) | 10,9 ‰ |
| autocrazie piene | 0-5 ‰ | 0 |

**Che cosa costa.** Con la pendenza a 18 un crollo di legittimità pesa meno di
prima: venti punti valgono tre volte il rischio, non diciassette. Il verbo
«destabilizzare» resta efficace, ma non è più una lotteria truccata. La qualità
della vita (Goldstone) ha lo stesso peso di 2,7 punti per livello. Il suo «sette
volte» riguardava l'instabilità in generale, e sui colpi conta la pendenza
misurata.

## 2. Il commercio che si perdeva

**Il difetto.** Le economie piccole e aperte (Lussemburgo, Irlanda, Slovacchia,
Baltici) commerciavano un quinto meno del vero. Il riequilibrio non c'entrava;
il volume si perdeva in due punti:

- **lo sfoltimento**, che dopo il riequilibrio buttava via i flussi piccoli: il
  6,5% del commercio mondiale, quasi tutto dei piccoli che vendono poco a molti;
- **la tecnologia**: le esportazioni contavano i servizi informatici, le
  importazioni no, perché la Banca Mondiale non li ha. Il mondo importava un
  quarto meno di tecnologia di quanta ne esportasse.

**La correzione.**

- Dopo lo sfoltimento si rifà il fitting proporzionale sui soli flussi tenuti.
  Il supporto non cambia più, quindi non oscilla.
- I servizi informatici importati da ciascun paese sono le sue importazioni di
  servizi per la quota informatica dei servizi esportati nel mondo (14,9%).
- Chi nei dati bilaterali non esporta mai (l'Andorra, che il FMI non ha) passa
  per la gravità anche verso i clienti coi dati. La scala la rimette a posto il
  moltiplicatore del riequilibrio.

**L'esito.** Flussi del modello sulle esportazioni vere, settore per settore:

| settore | prima | adesso |
|---|---|---|
| energia | 0,96 | 1,02 |
| cibo | 0,85 | 0,94 |
| tecnologia | 0,72 | 1,02 |
| finanza | 0,86 | 0,90 |
| manifattura | 0,89 | 0,97 |

La correlazione col dato dei cinque settori è 1,00 sui 189 paesi. Il Belgio
esporta il 66% del PIL (obiettivo 68%), il Lussemburgo il 131% (140%), la
Slovacchia l'81% (83%), l'Andorra il 9% (12%, era 0%).

**Il tetto alle dipendenze.** Il tetto del 55% a un solo fornitore era una regola
della gravità. Coi flussi del FMI le dipendenze forti esistono: il Lesotho compra
dal Sudafrica l'89% dei suoi beni, il Bhutan dall'India l'85%, il Canada dagli
Stati Uniti il 63%. Il tetto vale ora solo dove i dati non ci sono. La prova
verifica che il primo fornitore sia quello vero, e che nessuno copra da solo un
fabbisogno intero.

## 3. I cinque paesi senza dati

Ora ce li hanno tutti e 189. L'ultimo ripiego sono i conti nazionali dell'ONU
(UNSD, *National Accounts Main Aggregates*, dollari correnti, 2021-2024), che
coprono tutti con esportazioni, importazioni e PIL nella stessa valuta. Il
rapporto col PIL non dipende dal cambio, che per Cuba è un problema.

Quei totali sono beni *e* servizi, e i servizi del modello sono solo la finanza.
Si tiene quindi la quota di beni che i flussi del FMI vedono: per Cuba il 6%
delle esportazioni (il resto è turismo e medici all'estero), per la Corea del
Nord il 41%. L'Iran fa eccezione: i suoi beni il FMI non li vede, perché il
petrolio venduto alla Cina sotto sanzioni le dogane cinesi lo registrano come
malese.

La composizione per settore è la mediana della regione, tranne dove i partner
dicono che cosa comprano. Per Siria ed Eritrea uso UN Comtrade, importazioni
2022-2023 per capitolo del Sistema Armonizzato. La regione dava alla Siria due
terzi di petrolio che non vende più e all'Eritrea il cibo.

| | esportazioni in % del PIL | di cui |
|---|---|---|
| Iran | 20 | 68% energia |
| Siria | 10 | 78% cibo (olio d'oliva, frutta, spezie), niente energia |
| Eritrea | 23 | 99% minerali (Bisha) |
| Corea del Nord | 2,6 | manufatti |
| Cuba | 0,3 | cibo e manufatti (nichel, zucchero, tabacco) |

## 4. Le ostilità che non sparano

**Il difetto.** L'affinità di partenza di ogni coppia viene da una regressione
sui 132 rapporti dichiarati: voti all'ONU, patti, dispute (`docs/32`). Spiegava
il 62%. I residui peggiori erano di due tipi:

- rivalità che non passano mai alle armi: Arabia Saudita–Iran, Algeria–Marocco;
- l'ostilità fra la Russia e l'Europa dopo il 2022, che le dispute del
  Correlates of War, ferme al 2014, non vedono.

**I dati.**

- **Le rivalità strategiche** di THOMPSON, SAKUWA e SUHAS (2021), *Analyzing
  Strategic Rivalries in World Politics*, Springer, cioè l'inventario di
  Thompson e Dreyer (2012) aggiornato al 2020, nel file di Kentaro Sakuwa. Sono
  le coppie che si considerano nemiche o concorrenti; 56 erano in corso nel
  2020, fra cui Iran–Arabia Saudita, Algeria–Marocco, Russia–Ucraina (dal 2014),
  Iran–Stati Uniti, Cina–Giappone, Grecia–Turchia. La prima prova l'ho fatta con
  l'edizione ferma al 2010, che teneva aperte rivalità finite dopo: l'Iran e
  l'Iraq risultavano nemici a −59, l'Iraq e l'Arabia Saudita (pace nel 2018) a
  −13. Con l'aggiornamento sono +30 e +75.
- **I paesi «ostili» per la Russia**, dalle ordinanze del governo russo 430-r
  del 5 marzo 2022 e 2018 del 23 luglio 2022 (le Bahamas): 49 Stati, cioè
  l'Unione Europea, il Nord America, il Giappone, la Corea, l'Oceania e altri.

Il Global Sanctions Data Base sarebbe stato la fonte migliore, ma si ottiene
solo su richiesta per posta.

**L'esito.** R² da 0,62 a **0,81**. La rivalità strategica vale −85, l'ostilità
verso la Russia −85. La distanza dei voti all'ONU scende da −33 a −14: prima
doveva spiegare da sola l'ostilità fra la Russia e l'Europa. Ne segue che due
paesi lontani nei voti ma senza rivalità né sanzioni partono meno ostili. Sulle
coppie non dichiarate il primo decile passa da −11 a 33 e la mediana da 54 a 69.
L'ostilità vera fuori dalle rivalità e dalle sanzioni (Stati Uniti–Iran,
Giappone–Corea del Nord) resta nei rapporti dichiarati, che restano come
correzioni.

**Una conseguenza visibile.** Con le 49 ostilità verso la Russia il modello
riarma l'Europa: in quindici anni la Germania passa dal 3,2 al 4,8% del PIL, il
Regno Unito al 4,8%, il Canada al 4,4%, la Polonia al 6,9%. È l'impegno preso
dalla NATO all'Aia il 24-25 giugno 2025: 3,5% per la difesa in senso stretto
entro il 2035, 5% in tutto. La fascia dell'onere militare mondiale, costruita sul
solo trend 2020-2024, aveva il tetto a 3,6% e ora l'ha a 4,0%.

## 5. Le fasce del cruscotto

- `cambi_irregolari` scende da 1,2 a **1,0**. Il dato vero per finestre di
  quindici anni fa 1,2-1,5 colpi l'anno, più circa 0,4 prese armate. Una corsa
  di quindici anni con 1,8 eventi l'anno attesi ne fa 1,1-2,5 per puro caso
  (Poisson, due errori).
- `onere_militare` sale da 3,6 a **4,0** (§4).

## 6. Timor Est

Era nell'elenco dei difetti di `docs/28`: crescita a −13,6% per il petrolio in
esaurimento, e un paese spesso in conflitto. Il FMI pubblica per Timor Est il PIL
non petrolifero, e da `docs/30` la crescita viene da lì: +3,6%. Su due mondi di
quindici anni Timor Est non è mai in conflitto. Era chiuso e non l'avevo scritto.

---

## Le misure

Quattro semi per due profili, quindici anni: **22 grandezze su 22 in fascia in
tutti e otto i mondi**. I cambi irregolari stanno a 1,3-2,1 l'anno, contro
l'1,8 vero; l'onere militare a 3,0-3,8% del PIL. Le prove sono 516, e l'audit non
trova niente.

## Serve un riavvio?

Sì, consigliato. La taratura dei colpi e il commercio valgono dal primo tick dopo
l'aggiornamento e non toccano lo stato salvato. Le affinità invece sono
nell'«ancora» di ogni relazione, che il mondo vivo ha salvato al seme: un mondo
non riavviato terrebbe le affinità vecchie. Il riavvio si fa come le altre volte:

    bash deploy/02-riavvio.sh --profilo=osservazione

## Che cosa resta aperto

- **I rapporti dichiarati** restano come correzioni, per il 19% che i dati non
  spiegano: Stati Uniti–Iran, Giappone–Corea del Nord, Israele–Libano.
  Servirebbero le sanzioni del Global Sanctions Data Base, che si ottiene solo
  su richiesta.
- **Le rivalità strategiche sono ferme al 2020**: quel che è cambiato dopo
  (Israele e Iran in guerra aperta nel 2024-2025, la Siria dopo Assad) lo
  portano le dispute e i rapporti dichiarati.
- **Fra 0,15 e 0,25 di democrazia liberale** i colpi del modello stanno ancora
  nella parte alta dell'intervallo del dato (21-25 contro 14,5).
