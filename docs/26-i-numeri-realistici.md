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

## 7. Quel che questo episodio insegna

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
