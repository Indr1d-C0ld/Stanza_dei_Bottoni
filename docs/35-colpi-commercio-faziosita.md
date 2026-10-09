# 35 — I colpi, il commercio e la faziosità (ottobre 2026)

## Da dove nasce

`docs/34` chiudeva con quattro difetti vecchi, ancora nell'elenco del README:

- la U rovesciata di Goldstone sotto la sua magnitudine (`docs/26` §13);
- la faziosità che esisteva solo per le quattordici potenze giocabili;
- il commercio in cui la taglia assoluta decideva troppo, e il Belgio esportava
  zero;
- i conflitti del seme di cui il modello non sapeva quali dovessero durare.

Sono stati ripresi tutti e quattro, ciascuno partendo da un dato nuovo. Due
hanno avuto una sorpresa: il difetto non era dove lo cercavo.

---

## 1. Le autocrazie non cadono: la blindatura

**Il difetto.** Il modello faceva cadere le autocrazie piene più spesso dei regimi
parziali. In quindici anni la Corea del Nord, l'Eritrea e il Turkmenistan
subivano colpi di Stato tre o quattro volte più dei paesi a metà strada:
**48-70 colpi ogni mille anni-paese**. Lo spostamento del centro che proteggeva i
regimi chiusi (`docs/26` §13) non bastava più da quando la legittimità ha
un'ancora per paese, presa dai WGI (`docs/31`): i regimi chiusi partono bassi, e
la logistica sulla legittimità li faceva cadere.

**Il dato.** Powell e Thyne, elenco dei colpi di Stato (versione del 29/08/2026),
incrociato con la democrazia liberale di V-Dem dell'anno prima, per il 2000-2025:

| regime (democrazia liberale V-Dem) | colpi riusciti ogni mille anni-paese |
|---|---|
| autocrazia piena (sotto 0,05) | **0** su 265 anni-paese |
| regime parziale | **12,9** |
| democrazia (sopra 0,55) | **0** |

È il *coup-proofing*. QUINLIVAN (1999), «Coup-Proofing: Its Practice and
Consequences in the Middle East», *International Security* 24(2). POWELL (2012),
«Determinants of the Attempting and Outcome of Coups d'état», *Journal of
Conflict Resolution* 56(6). Ne fanno parte unità d'élite fedeli, forze parallele
che si bilanciano e la sorveglianza sui militari.

**Il meccanismo.** Il rischio di colpo si moltiplica per exp(−3·s). La chiusura
*s* va da 0 a 1 quando la democrazia liberale scende da 0,10 a 0,05
(`colpo_di_stato.blindatura`, [TARATO]). Si misura sulla democrazia liberale e
non sull'apertura istituzionale. Il primo tentativo usava l'apertura, che sconta
la stretta di polizia e la censura: metteva fra le autocrazie piene metà dei
regimi parziali, e i colpi nei parziali crollavano a 2-4 per mille.

**L'esito**, sui quattro semi del profilo «osservazione», quindici anni:

| | modello | mondo vero |
|---|---|---|
| autocrazie piene | 0-9,5 | 0 (fino a 11 ‰ è compatibile con lo zero osservato) |
| regimi parziali | 11-16 | 12,9 |
| democrazie | 0 | 0 |

La U di Goldstone ora ha la forma giusta e la magnitudine giusta: zero ai due
estremi e il massimo nel mezzo. Il difetto non stava nel mezzo della U, come
diceva `docs/26`, ma nel suo ramo sinistro.

## 2. Le fasce dei cambi irregolari

Con la blindatura due fasce del cruscotto sono uscite. Erano costruite su altre
fonti: il Cline Center per i colpi e Archigos dal 1875 per la quota irregolare.
Ora usano lo stesso dataset su cui il modello è tarato:

- `cambi_irregolari`, **1,2-6 l'anno**. Nei paesi del seme Powell e Thyne contano
  36 colpi riusciti nel 2000-2025, cioè 1,4 l'anno (2,5 negli anni Venti). Vanno
  aggiunte circa 0,4 prese del potere armate l'anno: Libia 2011, Centrafrica 2013,
  Yemen 2015, Afghanistan 2021, Siria 2024. La fascia di prima era 3-9.
- `quota_irregolare`, **3-15%**. Nell'elenco dei leader di Powell e Thyne le
  uscite dal potere sono 35 l'anno, e il 5,1% avviene per colpo o presa armata.
  Il «circa un quinto» di Archigos vale dal 1875, cioè per un mondo di colpi di
  Stato che non è più questo.
- `durata_governo` ha ora il suo riferimento: 189 paesi diviso 35 uscite l'anno
  fa **5,4 anni**. La fascia 4-9 resta.

## 3. I conflitti del seme che durano

**Il difetto, come lo diceva il README.** Metà dei conflitti del seme si spegneva
e il modello non sapeva quali dovessero durare.

**Il dato.** UCDP/PRIO Armed Conflict Dataset v26.1. Dei paesi con un conflitto
interno attivo in un anno del 1990-2010, quindici anni dopo il **59%** ne ha
ancora uno; il 69% se era una guerra. Che una parte dei conflitti si spenga è
quindi la storia vera, non un difetto.

**La misura.** Nel cruscotto c'è una fascia nuova, `persistenza_conflitti`: la
quota dei paesi col conflitto del seme (livello 4 o più, esclusi i belligeranti
delle guerre fra Stati) che sono ancora in conflitto alla fine della corsa. La
fascia è il 59% più o meno due errori binomiali. I conflitti del seme sono una
trentina, e su trenta casi l'errore è di 8,6 punti: **42-77%**. Il modello fa
45-76% sugli otto mondi.

Quali durino non lo decide più una lista. Lo decidono il rischio di Fearon
(`docs/33`), la forza dei ribelli e quella dello Stato.

## 4. Il commercio dai dati

**Il difetto.** Chi vende cosa lo ricavava una formula su terra, ricchezza,
istruzione e taglia, con moltiplicatori [FABBRICATO] per chi ha il petrolio.
Quanto ognuno compra fuori lo decideva il suo peso nel mondo, e chi vende a chi
la gravità. Confrontate col dato, le esportazioni in rapporto al PIL avevano una
**correlazione di 0,06**: il Belgio esportava lo 0% contro l'87% vero, l'Irlanda
il 16% contro il 139%, l'Arabia Saudita il 91% contro il 31%. E la Cina comprava
manufatti soprattutto dall'India.

**I dati.** Tre fonti, ciascuna col suo importatore:

- **Quanto, settore per settore** (`bin/importa_commercio.php`): Banca Mondiale,
  World Development Indicators, media 2021-2024. Beni e servizi esportati e
  importati; per le merci la quota di combustibili, cibo, materie prime agricole,
  manufatti, minerali e beni informatici; per i servizi la quota finanziaria e
  quella informatica. Ogni settore entra in frazione del PIL corrente e il
  modello la applica al proprio PIL.
- **Chi vende a chi** (`bin/importa_imts.php`): Fondo Monetario, International
  Trade in Goods (IMTS), esportazioni bilaterali FOB, media 2023-2024. Sono
  9.400 coppie. Si tengono quelle che valgono lo 0,1% delle esportazioni di chi
  vende *oppure* lo 0,5% delle importazioni di chi compra. Senza il secondo
  criterio la Cina spariva dai fornitori del Nepal, perché per lei il Nepal vale
  lo 0,06%, e il riequilibrio faceva comprare tutto al Nepal dal Bhutan.
  Taiwan non compare fra gli esportatori del FMI: si ricava da quanto i partner
  dichiarano di importarne, riportato da CIF a FOB.
- **Chi la Banca Mondiale non copre** (Taiwan, Birmania, Turkmenistan, Afghanistan,
  Yemen, Somalia e altri nove) prende i beni dai flussi del FMI e il PIL in dollari
  dal World Economic Outlook (NGDPD, 2023-2024). La composizione è la mediana
  della sua regione. L'Iran no: il petrolio che vende alla Cina sotto sanzioni
  le dogane cinesi lo registrano come malese, e coi flussi del FMI l'Iran
  esporterebbe il 3% del PIL contro il 20% circa delle stime. Restano al modello
  di prima l'Iran, Cuba, l'Eritrea, la Corea del Nord e la Siria.

**Il meccanismo** (`src/Dati/Commercio.php`). Per chi ha i dati, offerta e
domanda sono quelle vere. I pesi bilaterali sono i flussi del FMI moltiplicati
per la composizione settoriale del fornitore. Il grafo si equilibra con un
fitting proporzionale iterativo sulla matrice completa e si sfoltisce solo alla
fine. Sfoltirlo prima faceva oscillare il riequilibrio: l'Australia al 452%,
poi l'Algeria. Non c'è più il taglio ai primi N fornitori, che lasciava a zero i
paesi piccoli. Un flusso si tiene se vale l'1% della domanda del cliente o il 3%
delle vendite del fornitore.

**L'esito.**

| | prima | adesso |
|---|---|---|
| correlazione col dato dei cinque settori (184 paesi) | — | **0,99** |
| correlazione con esportazioni totali/PIL (Banca Mondiale) | 0,06 | **0,84** |
| idem, importazioni | — | **0,81** |
| Belgio, esportazioni/PIL | 0% | 54% (cinque settori: 68%; tutto: 87%) |

Le liste dei fornitori di manufatti sono quelle vere:

- la Cina compra da Taiwan, Giappone, Vietnam, Corea, Germania;
- gli Stati Uniti da Messico, Cina, Canada, India, Vietnam;
- la Germania da Polonia, Paesi Bassi, Cina, Francia, Italia, Repubblica Ceca.

La Russia compra metà di quel che le serve dalla Cina.

Il resto dello scarto col totale della Banca Mondiale viene da tre cose: i
servizi fuori dai cinque settori (trasporti, turismo), i totali mondiali di
offerta e domanda che non coincidono, e il conseguente riporto dell'offerta
sulla domanda. Le economie piccole e molto aperte (Lussemburgo, Irlanda,
Slovacchia, Baltici) stanno così circa un quinto sotto l'obiettivo.

**Le prove** (`tests/02-commercio.php`) sono state riscritte dove pretendevano
il mondo di prima:

- La Svizzera fra i primi esportatori di finanza è fama più che dato. Con la
  Banca Mondiale la classifica è quella della WTO: Stati Uniti, Regno Unito,
  Lussemburgo, Singapore, Germania.
- «L'energia è più concentrata della manifattura» si verifica ora in media su
  tutti i paesi. Il Giappone, che compra i manufatti soprattutto dalla Cina, la
  smentiva preso da solo.
- Il Belgio e la Slovacchia sono tornati nella prova «non esportano solo i
  giganti».

## 5. La faziosità, per tutti e col peso vero

**Il difetto, come lo diceva il README.** La faziosità, il predittore più forte
del modello PITF (Goldstone et al. 2010), si ricavava dal gabinetto. Il
gabinetto esiste solo per le quattordici potenze giocabili, quindi per gli altri
175 paesi la faziosità valeva zero.

**Il dato.** È una proprietà del regime e Polity la misura: PARCOMP = 3,
«factional», è la variabile di Goldstone. Polity5 (Marshall e Gurr), ultimo anno
2018 (`bin/importa_faziosita.php`, `db/seed/faziosita.php`): **31 paesi
faziosi**, dal Libano all'Iraq, dal Pakistan al Venezuela. Ci sono anche
democrazie come il Regno Unito, il Belgio, Israele e gli Stati Uniti, che però
non sono regimi parziali e quindi non pesano. Polity non copre i 23 paesi sotto
il mezzo milione di abitanti, che restano a zero.

**La sorpresa.** Prima di estendere il raddoppio a tutti, ho misurato quanto
pesa davvero. Colpi riusciti di Powell e Thyne, ogni mille anni-paese, fra i
regimi parziali:

| classificazione | anni | faziosi | non faziosi | rapporto |
|---|---|---|---|---|
| Polity (polity2 da −5 a 5) | 1990-2018 | 24,9 | 20,7 | 1,2 |
| Polity | 2000-2018 | 21,9 | 18,8 | 1,2 |
| V-Dem come il modello, faziosità ferma al 2018 | 2000-2025 | 17,4 | 10,9 | 1,6 |

Gli *inizi* di conflitto interno dell'UCDP (1990-2018) vanno addirittura al
contrario: 69 per mille nei parziali faziosi contro 101 negli altri.

Le «trenta volte» di Goldstone riguardano l'instabilità in generale (guerre
civili, genocidi, crolli di regime) nel 1955-2003. Confrontano un parziale
fazioso con un'autocrazia piena, e quindi misurano quasi tutto il tipo di
regime, che il modello ha già. Il raddoppio dello spostamento per la faziosità
del gabinetto contava il regime due volte.

**Il meccanismo.** La faziosità è una proprietà della nazione, presa dal seme. Fra
i regimi parziali alza il rischio di colpo di un quinto
(`instabilita.peso_faziosita` = 0,2, [TARATO]). Dove c'è un gabinetto conta per
metà anche la sua, che invece cambia col momento. Lo spostamento del regime
resta di dodici punti e non si raddoppia più.

**Quel che resta.** Nel modello i parziali faziosi cadono **29,6** volte ogni
mille anni-paese (21-36 sui quattro semi), contro 17,4 nel mondo vero. È al bordo
dell'intervallo di Poisson del dato (9-30, dodici colpi in tutto). I non faziosi
fanno 9,5 contro 10,9. Lo scarto non viene dal termine nuovo: col peso a zero i
numeri sono identici. Viene dalla legittimità bassa di quei paesi (Congo, Iraq,
Uganda, Ciad), che i WGI già ancorano in basso perché la stabilità politica
misura in parte proprio la faziosità.

---

## Le misure

Quattro semi per due profili, quindici anni: **22 grandezze su 22 in fascia in
tutti e otto i mondi**.

| grandezza | sugli otto mondi |
|---|---|
| cambi irregolari | 1,6-2,6 l'anno |
| conflitti del seme ancora aperti | 45-76% |
| crescita del PIL mondiale | 2,2-3,3% |

Le prove sono 503, e l'audit non trova niente: 22 file del seme, compresi i tre
nuovi.

## Serve un riavvio?

No. Il grafo del commercio e la faziosità si ricostruiscono dal seme a ogni tick
e non sono salvati, e non ci sono migrazioni nuove. Dal primo tick dopo
l'aggiornamento il mondo vivo commercia coi flussi veri. Le dipendenze cambiano
di colpo, una volta.

## Che cosa resta aperto

- **Il 38% dei rapporti dichiarati**, scritti a mano, finché non c'è una fonte di
  eventi abbastanza densa (`docs/33`).
- **I parziali faziosi cadono più del vero**, circa 1,7 volte, per via
  della loro legittimità di partenza (§5).
- **Le economie piccole e aperte commerciano un quinto meno del vero**, perché
  l'offerta mondiale si riporta sulla domanda (§4).
- **L'Iran, Cuba, l'Eritrea, la Corea del Nord e la Siria** restano al modello
  del commercio di prima: per loro un dato affidabile non c'è.
