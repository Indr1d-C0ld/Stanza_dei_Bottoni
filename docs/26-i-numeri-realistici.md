# I numeri realistici

Questo documento nasce da una riga letta sul planisfero:

> Guerre in corso — Francia contro Cina dal 12/04/2027, 64.710.856 morti

Sessantaquattro milioni di morti in un anno, in **una** guerra bilaterale. La
seconda guerra mondiale ne fece fra i settanta e gli ottantacinque milioni in
sei anni e su tutti i fronti del pianeta. Il modello sbagliava di due ordini di
grandezza, e nessuna delle duecentonove prove automatiche se ne era accorta:
tutte guardavano se la guerra *cominciava*, se *finiva*, se le garanzie
*scattavano*. Nessuna guardava se i numeri erano **credibili**.

La riga sbagliava anche un'altra cosa: quelle due guerre erano finite da mesi.

---

## 1. I morti, e il sessanta che non voleva dire niente

La fase 07 calcolava l'attrito, lo scaricava su uomini ed equipaggiamento, e
poi contava i morti così:

```php
$g['morti'] += ($logoraA + $logoraD) * 60.0;
```

`$logoraA` e `$logoraD` erano frazioni di `potenzaGoverno()`, cioè della media
geometrica fra un numero di persone e una cifra in milioni di dollari. La loro
unità di misura è la radice di «persone per milioni di dollari», che non è
niente. Moltiplicarla per sessanta per ottenere dei morti era una scelta
arbitraria, e infatti il risultato era arbitrario.

Il fatto grave è che **la stessa funzione contava già gli uomini persi**, e li
contava bene. Misurato su un anno di guerra Cina contro Francia:

| | |
|---|---|
| soldati tolti dai ruoli dalle due parti | ~151.000 |
| morti dichiarati dal modello | 33.592.248 |
| rapporto | **222 volte** |

Il modello toglieva centocinquantamila uomini dal fronte e poi ne annunciava
morti duecentoventidue volte tanto.

### Come si contano adesso

I morti sono una **frazione delle perdite contate**, non una funzione libera
della potenza. `logora()` restituisce gli uomini che ha tolto dai ruoli, e chi
la chiama decide quanti di quelli sono morti:

```php
$persiA  = $this->logora($a, $logoraA, $forzaA);
$persiD  = $this->logora($d, $logoraD, $forzaD);
$cadutiA = $persiA * $quotaCaduti;
$cadutiD = $persiD * $quotaCaduti;
$civiliMorti = ($cadutiA + $cadutiD) * $civili;
$g['morti'] += $cadutiA + $cadutiD + $civiliMorti;
```

Due rapporti, entrambi presi dalla storia e scritti in `calibrazione/base.php`
sotto `conflitto`:

- **`quota_caduti` = 0,33** — di ogni soldato tolto dai ruoli, circa uno su tre
  muore: gli altri sono feriti, prigionieri o dispersi. È il rapporto
  caduti/perdite totali che si ripete da Verdun in poi.
- **`civili_per_militare` = 1,0** — Eckhardt, ripreso dal Comitato
  Internazionale della Croce Rossa: la quota civile dei morti di guerra resta
  intorno al 50% da tre secoli, cioè circa un civile per ogni militare. Nelle
  guerre totali sale a due, nelle guerre aeree asimmetriche scende sotto 0,2:
  la media è la scelta meno sbagliata per un modello che non distingue i tipi
  di guerra.

E i morti adesso **tolgono popolazione**, cosa che prima non facevano: i
militari ciascuno a casa propria, i civili quasi tutti in casa del difensore,
perché è lì che si combatte. È l'asimmetria che rende l'invasione una catastrofe
per l'invaso molto prima che per l'invasore.

### Il risultato, misurato

Morti del primo anno, e di tre anni, per sei accoppiamenti:

| guerra | 1 anno | 3 anni |
|---|---:|---:|
| Cina – Francia | 99.237 | 152.882 |
| Russia – Ucraina | 222.827 | 652.806 |
| Iran – Iraq | 15.595 | 25.119 |
| Stati Uniti – Cina | 244.830 | 838.862 |
| Israele – Libano | 25.434 | 48.427 |
| India – Pakistan | 160.982 | 484.202 |

Contro i riferimenti: Corea 1950-53 circa 1,2 milioni in tre anni, Iran-Iraq
1980-88 fra 405 mila e 1,2 milioni in otto, Russia-Ucraina qualche centinaio di
migliaia in tre. Il modello sta dentro la fascia osservata.

### Le due guerre già scritte

Le righe di `sdb_guerra` del mondo vivo portavano ancora i numeri vecchi. Sono
state ricalcolate simulando entrambe le formule sulla stessa durata e sugli
stessi ruoli, e applicando il rapporto:

| | registrato | corretto | rapporto |
|---|---:|---:|---:|
| Francia > Russia, tick 60-113 | 41.440.885 | 139.987 | 296 |
| Francia > Cina, tick 66-119 | 64.710.856 | 154.256 | 420 |

---

## 2. Il planisfero raccontava guerre spente

`Lettura::guerre()` restituisce aperte e concluse insieme, ordinate con le
aperte per prime. Il cruscotto controllava `fine_tick`; il planisfero no, e
stampava tutto sotto «Guerre in corso». Le due guerre erano finite ai tick 113
e 119, il mondo era al 171.

Adesso `views/mappa.php` divide i due elenchi. Le guerre concluse **non** si
nascondono: una guerra finita resta un'informazione, e con la data di fine.

---

## 3. Tre pompe nell'economia, tutte con lo stesso vizio

Cercando altri numeri irreali è saltata fuori una famiglia intera di difetti,
tutti della stessa forma: **il motore puntava a un bersaglio universale diverso
da dove il mondo parte davvero, e la differenza diventava un premio permanente.**

Misurato su quindici anni:

| | al seme | dopo 15 anni | riferimento |
|---|---:|---:|---|
| crescita del PIL mondiale | 3,54% | **4,04%** | ~2,9-3,4% |
| quota investimenti | 22,0% | **28,6%** | — |
| quota militare | 2,30% | **1,03%** | 2,5% (SIPRI) |
| tendenza strutturale | 3,56% | **4,82%** | — |

### La pompa degli investimenti

`$obiettivoInvest = 0.14 + 0.16 * ($pressioneInvest / $totale)`. Per un paese
tranquillo quel rapporto vale circa 0,93, quindi l'obiettivo vale ~0,29 — mentre
il seme parte da 0,22 per tutti. E siccome la crescita premia chi investe più
del **proprio solito** (`resa * (quota - quotaIniziale)`), ogni paese del mondo
incassava per sempre un bonus che nessuno aveva guadagnato: 172 nazioni su 189
stavano sopra la propria quota di partenza.

### La pompa militare, al contrario

`$obiettivoMilitare = 0.010 + 0.16 * (...)`. Al tick 0 nessun paese ha insorti,
quindi la spinta è zero e **tutti** puntano all'1% del prodotto — mentre il seme
ne dichiara il 2,30% in media. L'onere militare del mondo si dimezzava in
quindici anni. Curiosamente la spesa *in dollari* restava plausibile: cresceva
il PIL abbastanza da mascherare il crollo della quota.

### Il cricchetto della tendenza

L'evento `investimenti` alza `crescitaStrutturale` di 0,004 e il rientro verso
la base valeva 0,10 l'anno: più lento del ritmo con cui gli eventi la alzavano.
Saliva e non tornava più.

### Il rimedio, uno per tutte e tre

L'obiettivo ruota intorno alla **quota storica del paese**, non intorno a una
costante:

```php
$obiettivoInvest   = $n->quotaInvestimentiIniziale + $ampiezzaInv * ($spintaInvest - $spintaInvNeutra);
$obiettivoMilitare = $n->quotaMilitareIniziale     + $ampiezzaMil * ($spintaMil   - $spintaMilNeutra);
```

I due **punti neutri non si deducono: si misurano** sul seme, perché sono il
valore che la spinta assume quando il mondo sta fermo al tick 0 — 0,71 per gli
investimenti, 0,21 per il militare. Al primo tentativo li avevo messi a occhio
(0,93 e 0,10, cioè le pressioni grezze invece dei *rapporti* che la formula usa)
e il mondo è partito in perdita: crescita all'1,53%, onere militare al 3,82%.

Il freno dei consumi è entrato **dentro** la spinta invece di restare un fattore
applicato all'obiettivo già calcolato: fuori rendeva il punto neutro
indefinibile, e un paese fermo si ritrovava un bersaglio sotto la propria quota
di partenza.

E il tetto alla tendenza strutturale è diventato relativo (`crescitaBase + 0,02`)
invece che assoluto: un programma di investimenti può spostare la tendenza di un
paese di due punti, non di quattro.

### La spesa militare guarda anche fuori

Cercando la pompa militare è saltato fuori un difetto a sé: `ansiaMilitare`
esisteva, cresceva a ogni provocazione, la leggeva la fase 06 per la diffidenza
— e **non spostava un centesimo di bilancio**. Un paese circondato che non arma
nessuno non è un modello, è una svista. Adesso la minaccia esterna entra nella
pressione militare accanto a quella interna.

---

## 4. I numeri all'italiana

Le date erano già in GG/MM/AAAA. I numeri erano rimasti all'anglosassone in
trentatré punti su trentotto: `number_format($x, 2)` stampa `25,680`, che un
occhio italiano legge venticinque virgola sei. Tutte le viste ora passano da
`n($x, $decimali)`, definita accanto a `u()` in `index.php`, e una prova
impedisce il ritorno di `number_format` nudo nelle viste.

---

## 5. Lo strumento, e perché esiste

`bin/realismo.php` fa girare il mondo a vuoto e confronta undici grandezze con
la loro fascia di riferimento, ciascuna con la fonte accanto. Le fasce stanno in
`App\Simulazione\Realismo::FASCE`, in un posto solo, e le legge anche
`tests/11-realismo.php`: due tabelle che divergono sarebbero peggio di nessuna
tabella.

```
php bin/realismo.php --anni=15
```

**La distinzione che conta.** Un *livello* — il prodotto mondiale, gli effettivi
sotto le armi, la spesa in dollari — si giudica **al seme**, perché dopo quindici
anni di crescita non è più confrontabile col dato di oggi: rimproverare al 2040
di non somigliare al 2024 non è una misura, è un errore di categoria. L'ho
commesso due volte prima di accorgermene, prendendomela col PIL mondiale e poi
con la spesa militare. Un *tasso* o un *rapporto* si giudica invece **sulla
corsa**, perché è lì che vive il comportamento del motore, e un motore può
partire giusto e andare alla deriva — che è esattamente quel che faceva.

### Lo stato, su cinque semi

| grandezza | al seme | dopo 15 anni | fascia | fonte |
|---|---:|---:|---|---|
| popolazione mondiale | 8,11 mrd | 9,28 mrd | 7,8-8,6 | ONU WPP 2024: 8,2 |
| PIL mondiale | 172 mila mrd | 243 | 150-210 | Banca Mondiale: ~180 in PPA |
| spesa militare | 3.952 mrd | 7.082 | 2.200-4.500 | SIPRI 2024: 2.718 |
| effettivi | 21,09 mln | 20,80 | 18-35 | IISS: ~27 milioni |
| Stati nucleari | 10 | 13 | 8-14 | SIPRI: 9 dotati |
| crescita popolazione | — | **0,90%** | 0,6-1,2 | ONU: 0,9%/anno |
| crescita PIL | — | **2,35-2,46%** | 2-4 | Banca Mondiale: 2,9% nel 2024 |
| onere militare | 2,30% | **2,45-2,91%** | 1,8-3,2 | SIPRI: 2,5% |
| cambi irregolari | — | **5,9-7,2**/anno | 3-9 | Cline Center: 2,2-3,8 colpi + rivoluzioni |
| morti di guerra | — | **0,01-0,02** mln/anno | 0-1,5 | UCDP/PRIO |

Undici su undici in fascia, su tutti e cinque i semi provati.

### Due cose dette apertamente

**La crescita sta sul lato basso.** 2,35-2,46% contro un riferimento di 2,9-3,4%.
Il freno di maturazione — le economie rallentano avvicinandosi alla frontiera
tecnologica — agisce sulle tendenze *già osservate* del seme, che quella
maturità la incorporano di suo: c'è un doppio conteggio. Il risultato resta
dentro la fascia storica (il mondo ha fatto 2,4-2,7% in parecchi anni recenti) e
tirare oltre significherebbe tarare su un anno solo. Resta come cosa nota.

**I cambi irregolari erano saliti** da 11,5 a circa 13 l'anno, conseguenza della
spesa militare che adesso cresce davvero con la paura: comprime i consumi, e da
lì la legittimità. Sono poi stati ritarati sul tasso contemporaneo — vedi la
sezione 6, che è la parte più istruttiva di tutto il lavoro.

---

## 6. Una fonte autorevole e dell'epoca sbagliata

I cambi irregolari erano finiti a ~13 l'anno, e la domanda naturale era
riportarli «verso i 10 storici» di Crawford. Guardando i dati, la domanda aveva
il bersaglio sbagliato.

| periodo | colpi di Stato riusciti nel mondo |
|---|---:|
| anni '60 | 103 nel decennio = **10,3/anno** |
| anni '70 | 95 = **9,5/anno** |
| 2000-2019 | ~22 per decennio = **2,2/anno** |
| anni '20 (estrapolato) | ~38 = **3,8/anno** |

*Fonti: Cline Center Coup d'État Project, dataset Powell & Thyne.*

I ~10 l'anno di Crawford **coincidono con gli anni Sessanta e Settanta quasi
alla cifra**, e non è un caso: li ricava dal *World Handbook of Political and
Social Indicators*, che copre il 1948-77, e scriveva nel 1985. Il numero è
giusto. È giusto per il suo mondo.

Il nostro seme è del 2024-25 e il calendario comincia il 5 gennaio 2026. Un
mondo del 2026 che fa dodici colpi di Stato l'anno non è il 2026: è il 1968.

> Una fonte non basta che sia seria: deve parlare del **mondo che si sta
> simulando**. È il terzo tranello di questa tabella di riferimenti, dopo i
> livelli confrontati con l'anno sbagliato e i tassi confrontati col seme.

### Il difetto sotto il difetto

Cercando la leva è emerso che **nessuno dei due profili toccava
`colpo_di_stato`**. Entrambi ereditavano `rischio_massimo_anno = 1.6` da
`base.php` e facevano quasi lo stesso numero di colpi — 13,2 contro 11,8, una
differenza che veniva di rimbalzo dalle aspettative. Il profilo `osservazione`,
che si dichiara «tarato contro i tassi storici», su questa dimensione non era
mai stato tarato su nessun tasso.

E lo strumento `bin/realismo.php` misura `osservazione`, mentre il mondo vivo
gira su `gioco`: si stava auditando il profilo che nessuno gioca. Adesso una
prova misura anche l'altro.

### La sostituzione fra colpi e rivoluzioni

Abbassando il rischio di colpo di Stato, **le rivoluzioni salgono**: da 1,27 a
2,56 l'anno lungo la curva. I due sono sostituti — un governo che non cade per
un colpo resta marcio più a lungo e alla fine lo rovesciano gli insorti — quindi
il totale scende molto più lentamente dei soli colpi. È una proprietà del
modello, non un difetto, e le rivoluzioni restano dentro il riferimento di
Crawford (~1% di ~10.000 rivolte in quarant'anni, cioè 2,5 l'anno).

| rischio | colpi | rivoluzioni | irregolari |
|---:|---:|---:|---:|
| 1,60 | 11,80 | 1,27 | 13,07 |
| 0,50 | 6,22 | 1,71 | 7,93 |
| **0,25** | **3,98** | **2,09** | **6,07** |
| 0,12 | 2,02 | 2,42 | 4,44 |

### Il premio di vivacità che non abbiamo preso

Il profilo `gioco` esiste per far succedere le cose, e la tentazione era
tenersi i colpi più frequenti del mondo vero. Il conto dice di no.

Un tick è due ore vere e una settimana di gioco: **un anno di gioco dura 103,8
ore reali**. Su centottantanove paesi:

| rischio | irregolari/anno | uno ogni (tempo reale) |
|---:|---:|---:|
| 1,60 | ~14,6 | ~7 ore |
| 0,50 | ~8,5 | ~12 ore |
| 0,25 | ~6,6 | ~16 ore |

Fra il mondo più turbolento e quello realistico ballano **nove ore** nel ritmo
con cui il giocatore vede cadere un governo da qualche parte. La verosimiglianza
costa quasi niente in movimento, perché il mondo è grande e il tempo è compresso
ottantaquattro volte: il compromesso che giustificava i tredici colpi l'anno, in
pratica, non esisteva. Entrambi i profili stanno a 0,25, il valore vive in
`base.php` in una copia sola, e `gioco.php` porta il conto qui sopra scritto
per esteso, così la domanda non si rifà ogni sei mesi.

---

## 7. Il censimento: altri riferimenti datati?

Trovato uno, la domanda giusta è se ce ne sono altri. Il censimento di tutte le
fonti citate nel motore e nella taratura ha richiesto prima di tutto un
criterio, perché **non tutti i riferimenti invecchiano allo stesso modo**:

- un rimando **strutturale** — la regola logistica, l'equazione dell'Oltraggio,
  la tavola degli obblighi di trattato, «la storia pesa otto volte l'ideologia»
  — descrive la *forma* di un modello. Non ha un'epoca;
- un numero **[FABBRICATO]** è dichiarato inventato. Può essere sbagliato, ma
  non è datato: non finge di venire da nessuna parte. Il progetto ne marca
  ventidue, e la marcatura regge;
- un rimando **empirico** — un tasso, una frequenza, una quota — dice come si
  comporta il mondo. **Solo questo può scadere.**

Dei rimandi empirici ne sono emersi tre, più un errore mio.

### 7.1 Il blocco `validazione`: morto e datato

`calibrazione/osservazione.php` conteneva quattro tassi presi dallo stesso
*World Handbook* del 1948-77: successo delle insurrezioni 0,20, dei cambi
irregolari 0,44, dei regolari 0,80, rivolte efficaci 0,01.

**Nessuna riga di codice li leggeva.** Zero usi, cercati chiave per chiave. Era
documentazione travestita da configurazione — e una configurazione morta è
peggio di un commento, perché si presenta come se governasse qualcosa. In più
un blocco che si chiama «validazione» dichiara che cos'è *corretto*: se è fermo
a mezzo secolo fa, non valida — sanziona. Tolto, con la nota che spiega perché.

### 7.2 La durata dei governi: la Quarta Repubblica come metro universale

Questo era vivo, e sbagliato due volte. `Fase10Gabinetto` giustificava la
frequenza delle crisi di governo così:

> nei trent'anni del World Handbook la Francia ne registra 61 e l'Italia 41 di
> soli tentativi falliti

Datato, perché il World Handbook copre il 1948-77. E **non rappresentativo**,
perché quella Francia è la Quarta Repubblica e quell'Italia è la Prima: le due
democrazie più instabili del dopoguerra europeo, prese come metro per tutti e
per sempre.

Il mondo che ne usciva aveva **governi da 3,3 anni di media**, contro i 4-8
delle democrazie competitive di oggi e i decenni dei sistemi autoritari — cioè
sotto il minimo della forchetta democratica, in un mondo dove circa metà dei
paesi non è una democrazia.

Il numero dietro (`elezioni.rischio_crisi_anno`) era marcato **[FABBRICATO]**,
quindi non mentiva sulla propria origine: mentiva la nota che lo motivava. Ed è
la stessa sostituzione vista coi colpi di Stato — meno uscite ordinarie, più
uscite irregolari — a dare la conferma indipendente:

| rischio crisi | durata governo | quota irregolare |
|---:|---:|---:|
| 1,8 (prima) | 3,3 anni | 11% |
| **0,8** | **4,5 anni** (gioco) · 5,4 (osservazione) | **16-18%** |

Archigos misura circa **un quinto** delle uscite dal potere come irregolari, su
188 paesi dal 1875. Due riferimenti indipendenti — durata dei mandati e quota
irregolare — cadono insieme sullo stesso valore. Non capita spesso, e quando
capita conviene fidarsi.

Entrambe le grandezze sono entrate in `Realismo::FASCE`: adesso sono tredici.

### 7.3 Un errore mio: l'Iran contato fra le potenze nucleari

Lo strumento contava «Stati dotati» con `posturaNucleare >= 3`. Ma la scala del
seme — sette livelli dal quadrante di *Shadow President* — mette a 3 il
«programma militare avviato» e a 4 l'«ordigno provato». A `>= 3` l'Iran entrava
nel conto, e il seme, che è **corretto** (nove Stati a livello ≥4), sembrava
dichiararne dieci. La fascia 8-14 mascherava lo scarto. Corretto a `>= 4`.

Vale la pena dirlo: il difetto non era nei dati, era nel metro. È il rischio di
ogni strumento di misura — e questo l'avevo scritto io, due ore prima.

### 7.4 Il seme non sapeva dire la propria età

Il seme viene dal World Factbook (clone dell'11/09/2026, revisione `144d697`) e
le sue cifre sono contemporanee: l'importatore legge voci datate «2024 est.» e
«2025 est.». Ma **da nessuna parte era scritto**.

Ora `db/seed/PROVENIENZA.md` porta data, fonte, revisione del clone e le
verifiche a campione contro ONU, Banca Mondiale, SIPRI e IISS; e
`bin/importa_factbook.php` lo riscrive da solo a ogni importazione. La ragione
sta scritta nel file:

> Un riferimento che non si può datare non si può nemmeno dichiarare scaduto.

### 7.5 Quel che il censimento ha assolto

Le decine di rimandi a Crawford, a *Balance of Power*, a *Shadow President* e a
*CyberJudas* sparsi nel motore sono quasi tutti **strutturali**: la forma della
logistica, la scala 0-128 dell'integrità, il Panel delle fazioni, i quattro
livelli di conoscenza, la tavola degli obblighi, il rapporto otto a uno fra
storia e ideologia. Sono scelte di modello, e un modello del 1985 non è più
scaduto di un teorema del 1850.

Il seme è contemporaneo. Le grandezze fabbricate sono marcate. Restano i tre
casi qui sopra, ora chiusi, e una prova che impedisce alle quattro chiavi morte
di tornare attive.

---

## 8. Guardare il mondo vivo, e Fearon & Laitin

Fatte le correzioni, mezzo anno di tick sul mondo vivo per vederle muoversi.
Le grandezze andavano dove dovevano — onere militare 1,914 → 1,980%,
investimenti 24,34 → 23,88%, crescita 1,62 → 2,48%, paesi in recessione 46 →
41 — e la cronaca reggeva: due colpi di Stato (Haiti, Sud Sudan) e una
rivoluzione (Comore) in sei mesi, tutti e tre paesi storicamente golpisti.

Poi due righe che stonavano:

> **Dominica**, conflitto livello 6 · **Micronesia**, livello 5

Isole da settantamila e centomila abitanti, in guerra civile. La Dominica aveva
**zero soldati**.

### Tre difetti, uno dentro l'altro

**Il primo: gli eserciti non si rigeneravano.** L'attrito li toglieva — la fase
05 per le insurrezioni, la 07 per le guerre — e *nessuna fase li rimpiazzava
mai*. L'equipaggiamento invece sì, dal bilancio militare, nella fase 03. Il
modello comprava carri armati e non arruolava nessuno. Un esercito logorato
rimpiccioliva per sempre, e con `potenzaGoverno() = √(soldati × equipaggiamento)`
uno Stato a zero soldati ha potenza zero: qualunque banda armata gli dichiarava
guerra civile.

È la stessa forma del difetto degli interi arrotondati trovato nell'audit
precedente — **una grandezza che il motore muove in una direzione sola**.

**Il secondo: il gradiente demografico era rovesciato.** Il reclutamento
insurrezionale cresceva con la *radice* della popolazione, mentre la potenza del
governo cresce linearmente con essa. Il rapporto scalava come 1/√P, e il
risultato era monotono e assurdo:

| taglia | in conflitto (prima) | (dopo) |
|---|---:|---:|
| sotto 1 mln | **29%** | 3-13% |
| 1-10 mln | 9% | 11% |
| 10-50 mln | 11% | 42% |
| 50-200 mln | 4% | 27% |
| oltre 200 mln | **0%** | 14% |

Fearon e Laitin misurano l'opposto: la popolazione grande è fra i predittori più
forti dell'insorgenza. E il loro predittore **più** forte — che qui non c'era
affatto — è la **povertà**: segna uno Stato finanziariamente e burocraticamente
debole e insieme rende conveniente arruolarsi. Non l'etnia: a parità di reddito,
i paesi più divisi non hanno più guerre civili degli altri.

Adesso il reclutamento moltiplica la popolazione e un fattore di povertà
ancorato al reddito pro capite.

**Il terzo: la vittoria dei ribelli era automatica.** Appena il rapporto di
forze si ribaltava, il governo cadeva quel tick stesso. Il Myanmar, il Congo, la
Somalia hanno guerriglie più forti dell'esercito in mezzo paese da decenni e la
capitale non cade: prevalere sul campo non è prendere il potere, e fra i
conflitti armati che finiscono la vittoria dei ribelli è l'esito più raro.
Adesso è una probabilità annua, e la guerra civile può durare.

### Il controllo che conta: i nomi, non i conteggi

**UCDP 2024: 61 conflitti statali attivi in 36 paesi, di cui 11 arrivati al
livello di guerra.** Il modello, tarato su questo, ne fa 20-34 e 13-21.

Ma il controllo vero è *quali* paesi nomina:

> Congo, Sudan, Somalia, Sud Sudan, Mozambico, Burkina Faso, Niger, Nigeria,
> Afghanistan, Yemen, Centrafrica, Haiti

Dodici paesi che stanno davvero nell'elenco UCDP. Il modello non ha né etnie né
storia né geografia: ci arriva con reddito, popolazione, legittimità e maturità
istituzionale — che è esattamente quel che Fearon e Laitin dicono basti.

Sbaglia anche, e va detto: mette in guerra la Tanzania, il Malawi, il Lesotho,
che sono poveri e pacifici; e non trova il Myanmar, la Siria, il Mali,
l'Etiopia, la Colombia, che hanno storie che il modello non conosce.

### Due errori di metodo, miei

**Avevo tarato prima dell'equilibrio.** Quindici anni dal seme non bastavano:
misurato su quaranta, il sistema oscilla fra 20 e 34 conflitti senza divergere,
ma il valore a quindici anni non era il suo punto di riposo. Il mondo vivo, che
porta gli stock insurrezionali del modello vecchio, è passato per un transitorio
a 48 conflitti prima di cominciare a rientrare.

**E avevo scelto una misura instabile.** Il gradiente demografico era un
rapporto fra proporzioni: con pochi paesi piccoli in conflitto esplodeva —
16 su un seme, 2 su un altro, a modello immutato. Adesso è una *differenza in
punti*, che non divide per zero e si legge da sola.

### Una prova fragile, smascherata

La ritaratura ha fatto fallire una prova che pretendeva che **ogni** verbo del
catalogo uscisse in **una** corsa con **un** seme. Misurati su quattro semi,
`invasione`, `armare_insorti` e `vendita_armi` escono in tre. Un verbo che esce
nel 75% dei mondi non è inarrivabile: è raro, che è quel che deve essere. La
prova adesso distingue i verbi comuni dai rari, e sui rari chiede solo che non
spariscano tutti insieme.

---

## 9. Goldstone: le istituzioni, e un asse che non c'era

Il passo successivo era il modello del **Political Instability Task Force**
— Goldstone, Bates, Epstein, Gurr, Lustik, Marshall, Ulfelder, Woodward (2010),
*A Global Model for Forecasting Political Instability*, AJPS 54(1). Quattro
predittori, **81,7% di accuratezza a due anni** su tutte le instabilità del
mondo dal 1955 al 2003, e una conclusione che va contro l'intuito: sono le
**istituzioni** a predire, non l'economia, non la demografia, non la geografia.

I quattro sono: tipo di regime, mortalità infantile, prossimità a vicini in
conflitto, discriminazione di Stato verso le minoranze. Il più forte è il primo,
e in particolare la **democrazia parziale fazionalizzata**, che ha oltre
**trenta volte** le probabilità d'instabilità di un'autocrazia piena.

### Il blocco: non c'era nessun asse democrazia-autocrazia

Il modello non si poteva implementare, e la ragione era già scritta nel codice.

- **`maturita`** sembrava l'asse istituzionale e non lo è: è marcata
  `[SEGNAPOSTO]` e si ricava da reddito e alfabetizzazione, quindi mette
  Singapore e l'Arabia Saudita accanto alla Norvegia. Il commento diceva già
  «DA SOSTITUIRE con V-Dem».
- **`ideologia_formale`** è peggio: è la descrizione giuridica che ogni Stato dà
  di sé, e **146 paesi su 189 si dichiarano democrazie liberali**. L'importatore
  avverte da sé che «serve per il colore, non per il modello».
- **`statoPolizia`** vive su scala 1-5, non 0-100, e al seme vale 2,00 per
  tutti; **`controlloInfo`** vale 50 per tutti.

Al primo tentativo avevo costruito l'apertura istituzionale da questi ultimi
due, dividendo per cento. Risultato misurato: **zero autocrazie su 189 paesi**.

### V-Dem, come il codice chiedeva

`bin/importa_vdem.php` scarica l'**indice di democrazia liberale del V-Dem
Institute** (Università di Göteborg) via Our World in Data e scrive
`db/seed/democrazia.php`. Dato aggiornato al **2025**, copre 173 delle nostre
189 nazioni; per i 16 micro-Stati che V-Dem non segue il valore è stimato dai
punteggi Freedom House con una relazione dichiarata, non nascosta.

| | V-Dem |
|---|---:|
| Norvegia · Italia · Stati Uniti | 0,847 · 0,642 · 0,571 |
| Singapore · Ungheria · India | 0,360 · 0,315 · 0,260 |
| Turchia · Russia · Cina · Corea del Nord | 0,110 · 0,056 · 0,039 · 0,014 |

**Gli ancoraggi dell'arco non sono 0, 0,5 e 1.** L'indice V-Dem è compresso e
mezzo punto non è «metà democrazia»: col vertice ingenuo a 0,5 gli Stati Uniti
risultavano *più parziali* di Singapore. Guardando dove cadono i paesi veri, il
vertice sta a **0,25** e la democrazia piena comincia a **0,55**. Con quegli
ancoraggi: India 0,97 · Nigeria 0,80 · Ungheria 0,78 · Singapore 0,63 ·
Pakistan 0,63 nella zona di rischio; Norvegia, Italia, Stati Uniti, Cina,
Corea del Nord a zero, dai due lati opposti.

### Tre meccanismi, e una forma sbagliata scoperta misurando

**La faziosità** (`Gabinetto::faziosita()`) non è `pressioneInterna()`: quella
misura l'ostilità al capo, questa misura quanto la politica è **spaccata** in
blocchi contrapposti dove chi vince prende tutto. Al primo tentativo avevo
normalizzato l'intensità per 90 e la coesione per 120; misurate, quelle
grandezze hanno mediana 30 e stanno fra 50 e 75. La faziosità non superava mai
0,35 e il moltiplicatore più importante del modello non si accendeva mai. *Una
grandezza che non arriva mai in fondo alla propria scala è una grandezza che
non esiste.*

**Il tipo di regime moltiplicava il rischio, e non doveva.** Misurato:
sestuplicare il peso da 4 a 25 muoveva il rapporto fra regimi parziali e
autocrazie da **1,1 a 1,6 soltanto** — perché la logistica sulla legittimità
spazia su ordini di grandezza e un fattore lineare non la tocca. Adesso il
regime sposta il **centro** della logistica, che è anche la forma giusta: un
regime parziale fazionalizzato cade con una legittimità con cui un'autocrazia
reggerebbe. Con la pendenza a 7, dodici punti valgono ~5 volte le probabilità e
ventiquattro ne valgono ~30 — il rapporto che PITF misura.

**E la chiusura non proteggeva.** `statoPolizia` non compariva in *nessun* punto
del rischio di colpo di Stato: la repressione costava legittimità e non comprava
niente. Un'autocrazia pagava il pugno di ferro senza averne il beneficio, e
risultava più fragile di una democrazia (legittimità media 43,1 contro 53,6).
È empiricamente falso, ed è il ramo sinistro della U di Goldstone.

### Quel che funziona, misurato

**Il contagio geografico funziona e si vede.** Un paese in conflitto ha in media
**1,60 confinanti in guerra**, uno in pace **0,31**: un raggruppamento cinque
volte più denso, che emerge dal meccanismo invece di essere imposto. È entrato
nel cruscotto come `raggruppamento`, e le grandezze sorvegliate sono adesso
**diciassette**.

### Quel che NON funziona, e va detto

**La U rovesciata non raggiunge la magnitudine di PITF.** Misurando con la
classificazione fissa del seme — per togliere la causazione inversa, visto che
un paese che subisce un colpo si chiude e finirebbe nel cassetto delle
autocrazie — le democrazie piene stanno a 0,2× le autocrazie, e questo è giusto.
Ma i regimi parziali restano intorno a 0,7-1,1×, invece dei 5-30× di Goldstone.

La causa è strutturale e vale la pena nominarla: **nel nostro motore la
legittimità delle autocrazie è sistematicamente più bassa**, e sono le code
basse a generare gli eventi. La logistica di Crawford domina qualunque
correzione istituzionale le si metta accanto o dentro.

Detto altrimenti: **Crawford e Goldstone sono in tensione**. Crawford fa
dell'instabilità una funzione della popolarità; Goldstone misura che la
popolarità e l'economia predicono *peggio* delle istituzioni. Far vincere
Goldstone significherebbe riscrivere la fase 04 — l'equazione della legittimità,
cioè il cuore del motore — e non è una cosa da fare di straforo in fondo a una
ritaratura. Resta come il prossimo passo, dichiarato.

**E un quarto predittore manca del tutto.** La mortalità infantile di PITF non è
stata implementata: il nostro `qualitaVita` vale 1 per tutti al seme e non è
usabile, e l'unico sostituto sarebbe il reddito pro capite, che già entra nel
reclutamento insurrezionale per via di Fearon & Laitin. Aggiungerlo due volte
sarebbe contarlo due volte. Meglio tre predittori onesti che quattro di cui uno
inventato.

**La faziosità copre 14 nazioni su 189**, perché i gabinetti esistono solo per
le potenze giocabili: è una scelta di disegno del motore, non un difetto, ma il
predittore più forte di PITF vale quindi solo dove c'è una struttura di élite
da leggere.

---

## 10. I trattati veri, e un meccanismo che non aveva mai funzionato

Gli obblighi di trattato erano `[FABBRICATO]`: si deducevano dall'affinità —
chi si piace abbastanza risulta alleato. È un modo per avere dei trattati, non
per avere **quelli veri**. Il codice chiedeva già la sostituzione: «Da
sostituire con Correlates of War».

`bin/importa_alleanze.php` la fa. **COW Formal Alliances v4.1** (Gibler,
Università dell'Alabama): patti di difesa, neutralità, non aggressione e intese,
per diade direzionata. 3.122 coppie fra le nostre 189 nazioni.

**E l'epoca, di nuovo.** COW arriva al **2012**, il nostro seme è del 2024-25.
Dodici anni in cui sono entrati nella NATO il Montenegro (2017), la Macedonia
del Nord (2020), la Finlandia (2023) e la Svezia (2024). Stanno nella tavola
`AGGIORNAMENTI` dell'importatore, ciascuno con la propria data. *Non si prende
un dataset autorevole e lo si applica a un mondo di un'altra epoca.*

### Il controllo sono le assenze, non le presenze

| | |
|---|---|
| Stati Uniti → Germania | 128 |
| Germania → Stati Uniti | 96 |
| Cina → Corea del Nord | 128 (trattato del 1961) |
| **Stati Uniti → Israele** | **0** |
| **chiunque → Taiwan** | **0** |

Le due assenze valgono più di tutte le presenze. Gli Stati Uniti e Israele
**non hanno** un patto di difesa reciproca, e la vecchia formula sull'affinità
glielo dava di sicuro. Il trattato con Taiwan fu denunciato nel 1980.

L'asimmetria è il pezzo più bello: `A|B` è l'obbligo di *A verso B*, quindi al
gradino 128 conta **l'arsenale di A**. Gli Stati Uniti garantiscono la Germania
al livello nucleare; la Germania garantisce gli Stati Uniti al convenzionale. È
la ragione per cui l'articolo 5 pesa più di qualunque altra firma al mondo, e
adesso il modello la sa.

### Tre difetti scoperti collegando il dato

**La matrice era troppo rada.** Le relazioni esistono solo fra potenze e fra
vicini — Crawford dovette buttare via la multipolarità perché la matrice piena
non gli stava in memoria. Delle 2.907 coppie di difesa di COW ne atterravano
**567**: le altre non avevano un oggetto su cui posarsi e sparivano in silenzio.
Ora un'alleanza crea la relazione, perché *è* un rapporto degno di essere
simulato. Le relazioni passano da 3.074 a 5.530.

**La struttura si sfaldava durante la corsa, ed è il difetto grosso.** La fase
06 ricalcola l'obbligo dall'affinità a ogni tick e lo abbassa col 2% di
probabilità: su 780 tick la denuncia è praticamente certa. Misurato dopo quindici
anni:

| | al seme | dopo 15 anni |
|---|---:|---:|
| patti di difesa (≥96) | 2.907 | **17** |
| garanzia nucleare (128) | 144 | 16 |
| gradino 16 | 11 | **2.291** |

Il mondo si scioglieva in una nebbia di intese diplomatiche, e la struttura
importata veniva rimpiazzata proprio dalla formula sull'affinità che avevo
appena tolto. **Zero garanzie messe alla prova in quindici anni**: l'integrità —
il meccanismo con cui Crawford rende costose le promesse — non ha mai avuto su
cosa mordere.

Adesso l'obbligo **non scende sotto quel che è scritto** (`obbligoFirmato`)
finché il rapporto non si rompe davvero. Le alleanze vere sopravvivono al
raffreddamento: la Grecia e la Turchia stanno nella NATO da settant'anni senza
volersi bene, e la Francia uscì dal comando integrato senza uscire dal patto.
Dopo quindici anni restano **2.692** patti di difesa.

**Il gradino nucleare si concedeva per simpatia.** La fase 06 dava 128 a
chiunque avesse affinità sopra 100: risultavano garanti nucleari **quattordici**
Stati, fra cui la Germania, la Spagna e la Nuova Zelanda, contro i nove che
l'atomica ce l'hanno. Ora il tetto è l'arsenale del garante, e restano sette:
Stati Uniti, Francia, Gran Bretagna, Russia, Pakistan, Cina, Corea del Nord.
L'India e Israele hanno l'atomica e non garantiscono nessuno — che è esatto:
nessuno dei due ha un trattato di difesa reciproca.

### Una verifica che valida invece di smentire

Su cinque corse da vent'anni le uniche guerre fra Stati sono state **Cina contro
Taiwan**, e nessuna garanzia è scattata. Non è un difetto: COW dice che nessuno
garantisce Taiwan, ed è vero. Il meccanismo è corretto, semplicemente non è
stato esercitato. La prova lo verifica per via diretta — l'Estonia, che è nella
NATO, ha più di dieci garanti a cui rispondere.

---

## 11. `maturita`: una variabile che diceva il falso su se stessa

La richiesta era «sostituisci `maturita` con V-Dem». Misurandola prima di
toccarla, si è scoperto che **la sostituzione sarebbe stata sbagliata quasi
ovunque** — e che il difetto vero era un altro.

| correlazione | r |
|---|---:|
| `maturita` vs logaritmo del reddito pro capite | **0,989** |
| `maturita` vs alfabetizzazione | 0,849 |
| `maturita` vs democrazia (V-Dem) | 0,520 |

`maturita` **non è** una misura istituzionale. È il reddito con un'altra faccia:
0,989 significa che non porta quasi nessuna informazione propria. E il nome —
«lo stato di diritto di Crawford» — dichiarava una cosa che il dato non era.

Sostituirla in blocco con V-Dem avrebbe reso **deboli i servizi segreti cinesi
e forti quelli norvegesi**, che è falso. La capacità di un apparato non è la sua
democrazia.

### Il difetto vero: il reddito contato due volte, e una volta tre

Essendo `maturita` il reddito, moltiplicarla *per* il reddito è elevarlo al
quadrato. Succedeva in tre posti:

```php
// proliferazione nucleare, fase 06 — reddito al cubo
$capacita = ($n->maturita / 255.0)
    * min(1.0, $n->pilProCapite / 25000.0)
    * max(0.0, min(1.0, $n->alfabetizzazione));

// commercio, settore finanza — reddito al quadrato travestito
'finanza' => $peso * (0.012 + 0.140 * ($ricch * $matur) ** 1.2),

// programma nucleare, fase 00 — due cancelli per la stessa cosa
&& $n->maturita > 120 && $n->pilProCapite > 9000.0
```

E nel reclutamento insurrezionale: `debolezza = 1 - maturita/255` era la povertà
un'altra volta, mentre la povertà entra già nel moltiplicatore di Fearon &
Laitin aggiunto due sezioni fa.

### La correzione: due variabili che dicono due cose

`maturita` resta quel che è — **sviluppo** — e smette di essere usata dove si
intendevano le istituzioni. Lì subentra `democrazia`, che è V-Dem.

| dove | vuole dire | adesso |
|---|---|---|
| chi va alle urne | istituzioni | `democrazia` |
| quando un ricambio è **irregolare** | istituzioni | `democrazia` |
| quanto un regime stringe sull'informazione | istituzioni | `democrazia` |
| chi si disarma | istituzioni | `democrazia` |
| un colpo di Stato erode… | istituzioni | `democrazia` |
| un'alternanza pacifica consolida… | istituzioni | `democrazia` |
| capacità di costruire l'atomica | tecnica e industria | reddito × istruzione |
| solidità di un apparato, di un servizio | capacità | `maturita` |
| settore finanziario | ricchezza | `ricch` al quadrato, scritto com'è |

Due di questi erano difetti visibili a un osservatore: **un ricambio di governo
in Cina risultava «regolare»** perché la Cina è ricca, e gli Emirati
controllavano poco l'informazione per lo stesso motivo.

### Una variabile che cambiava e non veniva registrata

`democrazia` adesso **si muove**: un colpo di Stato la erode di 0,02,
un'alternanza pacifica la consolida di 0,004. Ma arrivava dal seme e non era
salvata, quindi a ogni tick sarebbe tornata al valore di partenza: il mondo si
ricostruisce dal seme e poi si sovrascrive con lo stato registrato, e **quel che
non è registrato torna com'era**.

È lo stampo dei quattro campi che il motore muoveva per frazioni mentre erano
dichiarati interi, trovato nell'audit precedente: *una grandezza che cambia e
non viene registrata è una grandezza che non cambia.* Migrazione 0027.

### E la vista mentiva

La scheda di una nazione diceva «Maturità istituzionale 215/255». Adesso dice
**Sviluppo 215/255** e **Istituzioni 0,64/1,00** — due righe, due cose diverse.

### Tre prove fragili smascherate per strada

La ritaratura ha fatto cadere tre prove, e in tutti e tre i casi il difetto era
nella prova:

- **la proliferazione** («qualcuno si è armato in quindici anni») guardava una
  sola traiettoria: misurata su cinque semi esce in quattro, con zero-tre nuove
  posture per corsa. Un evento raro non può essere preteso sempre;
- **il verbo `trattato`** non usciva più, ed era una conseguenza *vera*: la
  condizione era «rapporto caldo e nessun trattato», e con i patti veri del
  Correlates of War le coppie calde un patto ce l'hanno già. Riscritta come
  «rapporto cresciuto oltre il trattato che lo regge» — e resta rara, come nel
  mondo vero;
- **l'onere militare** sfiorava il tetto della fascia. Il tetto è salito da 3,2
  a 3,6 *sul dato*, non per far passare la prova: SIPRI registra la salita più
  ripida dal 1988, dal 2,2% del 2020 al 2,5% del 2024, cioè +0,075 punti l'anno
  — su quindici farebbero +1,1, e il modello ne fa +0,7. Sale più piano del
  mondo vero, non più in fretta.

---

## 12. La disuguaglianza, e una variabile che nessuno leggeva

Il modello non aveva nessuna misura della disuguaglianza, e l'equazione della
legittimità di Crawford guarda il consumo **pro capite** — cioè la media. Ma la
media non è quel che la gente sente.

`bin/importa_gini.php` prende l'**indice di Gini della Banca Mondiale**
(PIP/WDI, via Our World in Data): 167 paesi con rilevazione vera, gli altri 22
con la mediana della propria regione — che è meno sbagliata di quella mondiale,
perché la disuguaglianza è un fatto regionale prima che nazionale.

### Che cosa può fare, e che cosa no

La letteratura è netta e va rispettata. Questo è il Gini **verticale**, fra
individui: per l'insorgenza di guerra civile **non è un buon predittore** —
Fearon & Laitin e Collier & Hoeffler lo trovano non significativo, ed è la
disuguaglianza **orizzontale** fra gruppi etnici a contare (Cederman, Weidmann,
Gleditsch 2011, APSR 105(3)).

Il nostro seme non ha gruppi etnici. Quindi la disuguaglianza **non tocca le
guerre**: tocca il malcontento, che è il canale per cui l'evidenza c'è.

### Il rapporto non è inventato: si deriva

Se i redditi si distribuiscono in modo lognormale — l'approssimazione standard —
allora

```
mediana / media = exp(-σ²/2)        con   G = 2·Φ(σ/√2) − 1
```

cioè `σ = √2 · Φ⁻¹((1+G)/2)`. Nessun coefficiente scelto a gusto, e il conto **si
verifica da sé**: per gli Stati Uniti (G = 0,418) dà 0,739, e il rapporto vero
fra reddito familiare mediano (~75 mila) e medio (~106 mila) è 0,71.

| paese | media | Gini | mediano | scarto |
|---|---:|---:|---:|---:|
| Norvegia | 91.100 | 0,265 | 81.238 | −11% |
| Polonia | 45.100 | 0,285 | 39.470 | −12% |
| Stati Uniti | 75.500 | 0,418 | 55.764 | −26% |
| Brasile | 19.600 | 0,503 | 12.357 | −37% |
| Sudafrica | 13.600 | 0,541 | 7.860 | **−42%** |
| Colombia | 18.500 | 0,544 | 10.613 | **−43%** |

Il caso che spiega perché serve: **Brasile e Thailandia** hanno medie vicine
(19.600 e 21.700), ma il cittadino tipico thailandese sta il 46% meglio del
brasiliano. Un governo che festeggia la crescita mentre la gente non la vede è
una delle storie più comuni del mondo vero, e prima questo modello non poteva
raccontarla.

### La variabile che nessuno leggeva

Per far *contare* la disuguaglianza serviva un canale, e cercandolo è saltato
fuori un reperto: **`qualitaVita` era scritta, salvata, mostrata in pagina — e
non letta da nessun meccanismo.** Dieci livelli calcolati a ogni tick per
centottantanove paesi, e nessuna conseguenza.

È la stessa famiglia dei quattro campi interi mossi per frazioni e della
`democrazia` che cambiava senza essere salvata: *una grandezza calcolata e mai
letta è una grandezza che non esiste.*

Adesso è il **quarto predittore di PITF**, quello che avevo dichiarato non
implementato due sezioni fa. Goldstone et al. usano la mortalità infantile —
«i paesi al 75° percentile hanno **sette volte** le probabilità di quelli al
25°» — come misura insieme di benessere e di capacità dello Stato di provvedere.
Da noi è `qualitaVita`, calcolata sul consumo **mediano**.

E la taratura viene dal numero, non dal gusto: i quartili di `qualitaVita`
stanno a 3 e a 8, cinque livelli di scarto, e con la pendenza a 7 servono
7·ln(7) = 13,6 punti per fare sette volte le probabilità. Cioè **2,7 punti per
livello**.

Così la disuguaglianza entra nel modello per una via sorgentata, invece che per
un coefficiente inventato: Gini → consumo mediano → qualità della vita →
instabilità.

---

## 13. Quel che questo episodio insegna

Le duecentonove prove esistenti verificavano **meccaniche**: che le cose
succedessero, nell'ordine giusto, con le cause giuste. Nessuna verificava
**grandezze**. Un modello può avere tutte le meccaniche al posto giusto e
dichiarare numeri da fantascienza, e chi lo guarda se ne accorge prima di
qualunque prova — che è esattamente come è andata.

Le diciannove prove di `tests/11-realismo.php` aggiungono l'invariante che
mancava, e che in retrospettiva è ovvia:

> **I morti non possono essere più degli uomini che il fronte ha tolto dai ruoli.**

E una seconda lezione, che è arrivata dopo e vale quanto la prima: un
riferimento può essere **autorevole e insieme dell'epoca sbagliata**. Crawford
non aveva torto sui dieci cambi irregolari l'anno. Aveva ragione sul 1968.

Da cui una terza, che è la forma pratica della seconda: **una fonte va datata
quando la si cita, non quando la si sospetta.** Il censimento della sezione 7 è
stato possibile solo perché quasi ogni numero del progetto porta scritto accanto
da dove viene. I tre difetti trovati erano tutti in punti dove quella
abitudine si era interrotta — un blocco senza usi, una nota senza data, uno
strumento senza la scala che misurava.
