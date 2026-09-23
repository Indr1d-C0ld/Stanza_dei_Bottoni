# 28 — La guerra fra Stati, la guerriglia, le urne (settembre 2026)

Il seguito di `docs/27-audit-totale.md`: i punti che l'audit totale aveva
lasciato aperti, affrontati uno per uno. Come sempre, ogni numero qui sotto
viene da una misura.

---

## 1. Due scelte di progetto

**Al tavolo di una crisi siedono il Capo e gli Esteri** (`Crisi::TAVOLO`). Una
crisi e' una contestazione fra governi, e la conduce chi parla per il governo.
Prima contava qualunque poltrona occupata della nazione: il ministro
dell'Economia poteva portare il paese al nono gradino, e bastava un giocatore
seduto all'Informazione per togliere la crisi all'apparato. Adesso aprono,
rispondono e ricevono gli avvisi di crisi solo il Capo e gli Esteri; gli altri
ministri vedono la crisi ma non la muovono, e se nessuno dei due e' al suo
posto decide l'apparato.

**Chi perde il governo perde il gabinetto.** Prima un'elezione persa cambiava
la legittimita' e lasciava al loro posto il Capo sconfitto e tutti i suoi
ministri. Adesso la squadra si rinnova come dopo una caduta: alle urne resta
un ministro forte su quattro, con una sfiducia — la maggioranza che si
rimescola — piu' della meta'.

---

## 2. Il picco d'apertura: l'innesco di Fearon e Laitin

Il mondo partiva coi 34 paesi in conflitto di UCDP e un anno dopo ne aveva una
cinquantina, fra cui Kenya, Tanzania, Malawi e Lesotho. Il motivo era nella
dinamica: un'insurrezione nasceva **da sola** appena il reclutamento possibile
superava l'attrito del governo, e siccome al seme partivano tutte da zero,
nascevano tutte nello stesso momento.

Fearon e Laitin (2003, APSR 97(1)) non stimano quanti ribelli ci sono: stimano
la **probabilita' annua che una guerra civile cominci** — 127 inneschi in 6.610
anni-paese nel 1945-99, cioe' l'1,9% l'anno, fino a circa il 10% per i paesi
piu' esposti. Adesso e' cosi' anche qui: chi non ha un'insurrezione la accende
con una probabilita' che cresce con quanto il terreno e' favorevole (il
reclutamento possibile diviso l'attrito del governo), fino a un tetto. Molte si
spengono presto, come nel mondo vero.

| | prima | dopo |
|---|---:|---:|
| paesi in conflitto alla fine del primo anno (al seme 34; 36 dopo, con Russia e Ucraina) | 53-56 | 24-28 |
| paesi in conflitto, dopo quindici anni | 39-44 | 26-33 |
| paesi a livello di guerra | 11-22 | 8-20 |
| cambi irregolari l'anno | 5,3-6,0 | 4,1-5,1 |

Riferimenti: UCDP 2024, 36 paesi in conflitto (il massimo dal 1946; negli anni
Dieci erano circa 30) e 11 guerre; Powell & Thyne 2,2-3,8 colpi riusciti
l'anno, piu' una o due rivoluzioni.

---

## 3. La guerra fra Stati

### La guerra russo-ucraina e' nel seme

`db/seed/guerre-note.php`: l'unica grande guerra fra Stati in corso alla
divergenza, con l'inizio vero (24/02/2022, tick -202). I morti si contano
**dalla divergenza**, e il planisfero lo dice: le stime di quelli precedenti
sono troppo distanti fra loro per metterne una nel seme come un dato.

Per farla stare in piedi sono servite cinque cose.

**1. I dati.** Il Factbook si ferma per l'Ucraina al 2021 — spesa militare 4%
del PIL, prima dell'invasione — e media la crescita sul 2022-24, con il -28,8%
del crollo dentro: al seme l'Ucraina aveva una crescita strutturale di -6,8%
l'anno per sempre e una legittimita' di 21,6. L'importatore ha adesso una
tavola di **correzioni** datate: spesa militare 34% del PIL (SIPRI, *Trends in
World Military Expenditure 2024*, aprile 2025: 64,7 miliardi di dollari, il
carico piu' alto del mondo), crescita 4,2% (lo stesso Factbook, media 2023-24).

**2. I rapporti.** Il seme non sapeva che l'Europa aveva rotto con la Russia.
`db/seed/politica-nota.php` ha una sezione nuova: i donatori maggiori del Kiel
Institute (*Ukraine Support Tracker*, febbraio 2025) e il resto dell'UE che ha
votato diciannove pacchetti di sanzioni; le eccezioni ungherese e slovacca; le
truppe nordcoreane nel Kursk; la Cina che si dichiara neutrale.

**3. Gli aiuti.** Prima esisteva soltanto l'aiuto una tantum dei garanti alla
prima settimana di guerra. Adesso, ogni settimana, chi parteggia nettamente per
uno dei due manda una quota del proprio bilancio militare, e la toglie dai
propri arsenali. Tarati sul Kiel Institute: circa 45 miliardi di euro l'anno
all'Ucraina, meta' americani e meta' europei. Il modello ne manda circa 72.000
unita' l'anno (dollari a parita' di potere d'acquisto), 59% americani; alla
Russia meno di un ventesimo.

**4. La stanchezza.** Era la stessa per tutti e fortissima: -26 punti di
legittimita' l'anno dal terzo anno, e l'Ucraina arrivava a 8 in un anno. Adesso
pesa in proporzione a quanto il paese puo' dirla (Mueller, 1973: nelle
democrazie il sostegno cala col logaritmo dei caduti; le autocrazie lo
reprimono), e chi difende la propria terra la sente meno di chi l'ha invasa.

**5. Come finiscono.** C'erano la conquista e la ritirata, e una regola per cui
ogni guerra oltre i tre anni con un rapporto di forze sotto 1,4 finiva con la
ritirata dell'aggressore. Ma il difensore conta gia' la mobilitazione e il
vantaggio di chi sta in casa: un rapporto intorno a uno e' lo **stallo** della
regola del tre a uno, non una sconfitta. Adesso ci si ritira solo sotto 0,5, e
lo stallo finisce con un **armistizio**, con una probabilita' annua che cresce
con la durata (la Corea nel 1953 dopo tre anni, Iran e Iraq nel 1988 dopo otto).

Su sei semi la guerra finisce con un armistizio fra 4 mesi e 5 anni dalla
divergenza, o con una ritirata russa dopo 7-9 anni di stallo logorante; morti
militari circa 77.000 l'anno.

### Le guerre nuove

In quindici anni il motore ne faceva nascere 0-2, e sempre di grandi potenze. La
condizione dell'invasione chiedeva un'ambizione che solo le grandi potenze
raggiungono e un'etica estratta **da un numero a caso** (il codice del paese):
l'Azerbaigian, che ha attaccato l'Armenia nel 2020 e nel 2023, non poteva.

La letteratura dice altro: la maggior parte delle guerre fra Stati nasce da una
disputa territoriale fra **vicini** (Vasquez, *The War Puzzle*, 1993; Senese e
Vasquez 2008) dentro una **rivalita'** di lunga durata (Diehl e Goertz, *War
and Peace in International Rivalry*, 2000), e la iniziano piu' spesso le
autocrazie. Adesso:

- **fra vicini rivali** l'invasione e' possibile a chiunque abbia una
  superiorita' netta, con un peso che cresce quanto piu' l'aggressore e'
  un'autocrazia;
- **oltre i confini** resta la proiezione di una grande potenza, e porta al
  fronte solo il 40% della propria forza: il «potere d'arresto dell'acqua»
  (Mearsheimer, *The Tragedy of Great Power Politics*, 2001);
- **la deterrenza estesa**: non si invade chi sta sotto l'ombrello nucleare di
  un garante (prima contava solo l'arsenale del bersaglio, e i baltici erano
  invadibili), ne' chi ha un garante impegnato piu' forte dell'aggressore
  (Huth, *Extended Deterrence and the Prevention of War*, 1988);
- **una guerra alla volta** fra due paesi: prima una seconda invasione a guerra
  aperta ne apriva un'altra accanto.

Nel seme delle alleanze: la KFOR in Kosovo (Stati Uniti e Italia, risoluzione
ONU 1244 del 1999) come base con mandato, e l'Armenia che congela la CSTO
(22/02/2024) — senza, l'Armenia stava sotto l'ombrello nucleare russo.

Su sei semi da quindici anni: da zero a due guerre nuove per corsa, due su tre
corse in media — Azerbaigian-Armenia (chiusa da un armistizio in meno di due
anni), Cina-Taiwan, una Russia che torna all'attacco dopo l'armistizio. Prima
della deterrenza la Serbia conquistava il Kosovo in un anno, due volte su sei. Il 2010-25 vero ne ha avute due o tre lunghe; gli scontri brevi
(India-Pakistan, Israele-Iran, Thailandia-Cambogia) il modello li fa con
l'attacco mirato.

### Tre difetti della persistenza delle guerre (0033)

`inizio_tick` era senza segno (una guerra cominciata prima della divergenza non
si poteva salvare), i morti erano interi (la frazione si perdeva a ogni tick), e
la colonna `esito` non la scriveva nessuno. E `Calendario` scriveva la data di
un tick negativo come «+-1414 days».

---

## 4. Le prove

`tests/14-guerra-fra-stati.php`: la guerra nel seme e la sua data, le alleanze
corrette, i dati SIPRI, gli aiuti nell'ordine del Kiel Institute, mai due
guerre fra gli stessi paesi, nessun picco di conflitti nel primo anno, il
gabinetto che cambia con chi perde le elezioni. La prova di fedelta' della
persistenza (`tests/13`) confronta adesso anche morti e aiuti di ogni guerra.
La prova della banda storica del mondo a vuoto usava ancora i ~10 cambi
irregolari di Crawford (5-20); adesso legge la fascia datata di
`Realismo::FASCE`. Le prove sono 402.

Diciannove grandezze su diciannove in fascia, su quattro semi e due profili.

---

## 5. Quel che resta

- **Un'invasione cinese di Taiwan riesce** nelle corse in cui avviene (due semi
  su sei), anche col potere d'arresto dell'acqua: senza un trattato gli Stati
  Uniti mandano materiale e non combattono. Rappresentare l'intervento diretto
  di chi non e' alleato — l'«ambiguita' strategica» — chiede un meccanismo di
  cobelligeranza che il modello non ha. I giochi di guerra del CSIS (gennaio
  2023) danno l'invasione per lo piu' fallita proprio perche' gli Stati Uniti e
  il Giappone intervengono.
- **Un civile per ogni militare caduto** e' la media storica (Eckhardt) e vale
  per tutte le guerre; in quella russo-ucraina i civili sono molti meno (le
  Nazioni Unite ne contano circa quattordicimila in quasi quattro anni), e il
  totale dei morti ne esce raddoppiato.
- **Timor Est** ha nel Factbook una crescita media di -13,6%, perche' il suo PIL
  reale comprende il petrolio in esaurimento: e' per questo che il modello lo
  mette spesso in conflitto. Non c'e' un dato sostitutivo di cui essere certi,
  e resta.
- **La crescita della popolazione ucraina** e' +2,42% nel Factbook (stima 2025,
  che evidentemente presume il rientro dei profughi). Discutibile, ma e' il dato
  della fonte, e resta.
- **La CSI come patto di difesa** nel Correlates of War lega ancora fra loro
  paesi che non si difenderebbero mai (l'Armenia e l'Azerbaigian, per dirne
  due). Fra nemici dichiarati il motore rompe il trattato al primo tick; fra
  indifferenti resta, e una guerra puo' mettere alla prova garanti che non lo
  sono mai stati.
- **I paesi in conflitto** stanno a 26-33 contro i 36 del 2024, che e' pero'
  l'anno record: il riferimento degli anni Dieci e' intorno ai 30.
