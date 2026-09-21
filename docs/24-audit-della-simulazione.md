# 24 — Audit della simulazione, e le correzioni

Non una rilettura del codice: misure. Ogni reperto qui sotto è stato ottenuto
facendo girare il motore e contando, e ognuno riporta il numero che lo dimostra.

Il mondo di prova è quello del profilo `osservazione`, 780 tick — quindici anni
— dal seme delle 189 nazioni.

> **Secondo giro.** Dopo il primo audit è stato fatto un secondo passaggio, più
> profondo, e poi le correzioni. Ogni reperto qui sotto porta il suo esito:
> **corretto**, **tolto**, o **dichiarato** quando è un limite del modello e non
> un errore. In fondo c'è il riepilogo di cosa è cambiato e cosa resta.

---

## Quel che è risultato sano

Vale la pena dirlo per primo, perché è la parte più grande.

| controllo | esito |
|---|---|
| **Determinismo** — stesso seme, stesso mondo | ✓ impronte identiche su 60 tick; semi diversi divergono |
| **Persistenza** — salva, ricarica, confronta | ✓ due ricariche dello stesso stato coincidono in tutto |
| **Salute numerica** — 780 tick, 19 campi, ogni 60 tick | ✓ nessun NaN, nessun infinito, nessun negativo indebito |
| **Le dodici fasi** | ✓ nessuna saltata in 200 tick |
| **Il catalogo dei verbi** | ✓ tutti e 18 hanno un effetto; nessun effetto orfano |
| **Le guerre finiscono** | ✓ zero guerre aperte a fine corsa |
| **Le scale** | ✓ tutti gli indici entro i loro limiti |
| **Sovrascritture fra fasi** | ✓ sei candidati esaminati, tutti legittimi |

L'ultimo merita una parola. La classe di errore che ha morso questo progetto tre
volte — una fase scrive, una successiva sovrascrive — è stata cercata con un
rilevatore apposta: assegnazioni che **non leggono** il valore precedente, in
una fase successiva a un'altra che lo muove. Sei casi. Tutti e sei si sono
rivelati voluti: `forzaInsorti = 0` dopo una rivoluzione, `netPeace = 6` in
guerra, `ansiaMilitare = 100` sotto invasione. Nessuna nuova amnesia.

Invariante delle quote di bilancio: regge **in memoria** a 2,2·10⁻¹⁶, cioè
l'epsilon della macchina. Nel database lo scostamento arriva a 1·10⁻⁴, ma è
solo l'arrotondamento di `DECIMAL(5,4)` su tre colonne. Chi lo verifica sul
database e non in memoria conclude che l'invariante è rotto: va detto dove va
misurato.

---

## Gravi — dimensioni che sembrano vive e non si muovono

Sono campi dichiarati, salvati a ogni tick, **letti dal motore**, e che nessuna
fase scrive mai. Chi legge il codice li conta come parte del modello; non lo
sono.

### 1. Lo stato di polizia è congelato

`statoPolizia` vale **2 in tutti e 189 i paesi** dopo 780 tick. L'unico posto
che lo scrive è il caricamento dal database.

Lo leggono tre fasi:

- **09 Stampa** — `libertà = 1 − (statoPolizia − 1) · 0,30`: la libertà di
  stampa è quindi identica ovunque, 0,70;
- **04 Società** — il malcontento viene ridotto di `statoPolizia − 2`, cioè di
  zero, sempre;
- **10 Gabinetto** — le elezioni richiedono `statoPolizia ≤ 3`: nessuno è mai
  escluso.

Un paese non può reprimere né liberalizzare. La Corea del Nord e la Norvegia
hanno la stessa polizia.

### 2. La difesa cibernetica è congelata

`cyberDifesa` non viene scritta da nessuno: resta al valore del seme per sempre.
La leggono la **fase 01** e la **fase 08** — è la difesa contro
l'intercettazione dei messaggi e contro la loro manomissione.

Vuol dire che un paese non può investire per difendersi dopo essere stato letto,
e nessun evento può degradargliela. Il capitolo più giocabile del modello
poggia su un numero che non si muove.

### 3. Non esiste proliferazione nucleare

`posturaNucleare` non viene scritta da nessuno. La leggono la **fase 00** (chi
è invadibile) e la **fase 01** (la paura che ferma le crisi in cima alla scala,
documentata in `docs/18`).

Nessun paese acquisisce la bomba, nessuno la smantella, in quindici anni di
gioco. È probabilmente la lacuna più vistosa per un gioco che si chiama Stanza
dei Bottoni.

### 4. Il controllo dell'informazione va solo giù

`controlloInfo` ha un solo scrittore, in fase 02:

```php
$b->controlloInfo = max(0, (int) round($b->controlloInfo - 4.0 * $i));
```

Solo la disinformazione altrui lo abbassa; niente lo alza. Dopo quindici anni
**175 paesi su 189 sono ancora al valore iniziale di 50** e i restanti quattordici
sono scesi. Un governo non può stringere la presa sui propri mezzi
d'informazione.

### 5. Le quattro dipendenze (già noto)

`dipEnergia`, `dipCibo`, `dipFinanza`, `dipTecnologia`: dichiarate, salvate a
ogni tick, mai lette da nessuna fase. Documentato in `docs/21` quando è stato
costruito il commercio, che le ha sostituite col proprio grafo senza rimuoverle.

---

## Medi — comportamenti presenti ma irraggiungibili

### 6. Il gradino più alto della tavola degli obblighi non esiste

La calibrazione dichiara la tavola di Crawford — 0, 16, 32, 64, 96, **128** —
ma la fase 06 la ha incisa nel codice, e si ferma a 96:

```php
$obbligoNaturale = match (true) {
    $r->affinita >= 100 => 96,   // <- il massimo assegnabile
    ...
```

Nel mondo vivo: 16 relazioni a 96, zero a 128.

Conseguenza misurabile: chi tradisce una garanzia paga
`integrità × (1 − obbligo/128)`. Con 96 gli resta il **25 %** della credibilità;
con 128 non gliene resterebbe niente. La garanzia di difesa nucleare — il
patto che dovrebbe essere impossibile tradire senza finire — non è nel modello.

*(E le sei voci `relazioni.obbligo.*` della calibrazione non sono lette da
nessuno.)*

### 7. `strike` è nel catalogo e la dottrina non lo propone mai

Ha un effetto implementato in fase 02. Non compare in nessun elenco di
candidati della fase 00. È inarrivabile per l'apparato; solo un giocatore può
ordinarlo.

### 8. La mediazione è soffocata dalla classifica dei bersagli

Zero usi in 780 tick, pur avendo peso 1,5 contro lo 0,8 dell'emissario, che ne
fa 379.

La causa è un conflitto strutturale. La mediazione richiede **indifferenza**
(`|affinità| < 40`), ma il peso con cui si sceglie fra i bersagli **cresce**
con l'intensità del rapporto:

```php
$peso = (abs($r->affinita) / 127.0 + 0.2) * (0.5 + $b->valorePrestigio / 600.0);
```

| |affinità| | peso base |
|---|---|
| 10 | 0,28 |
| 39 — il massimo che la mediazione ammette | 0,51 |
| 100 | 0,99 |

Misurato: su **483 occasioni** in cui la mediazione era proponibile, solo **6
(1,24 %)** sono sopravvissute alla classifica dei primi cinque bersagli. Poi
deve ancora vincere l'estrazione pesata contro gli altri candidati.

Un verbo che richiede tiepidezza viene offerto solo a bersagli scelti per
passione.

### 9. Aiuto economico e vendita di armi quasi non escono

Richiedono `amico && fragile` — affinità > 55 **e** legittimità < 40 o
guerra. Nel mondo capita, ma di rado: **9 occorrenze su 6 istantanee** di 3074
coppie. Zero usi in 780 tick.

Nel modello gli amici stretti sono paesi stabili e i paesi fragili non sono
amici stretti di nessuno. La condizione è coerente ma quasi vuota.

### 10. Una delle sei discipline d'intelligence non è usata

`imint` — l'immagine da satellite — è costruita per ogni paese in
`Intelligence::iniziale()`, salvata in `sdb_capacita_intel`, e **non è nominata
da nessuna fase**. Le altre cinque sì: osint 3 volte, sigint 3, humint 2,
cyber 1, finint 1.

---

## Minori — debito dichiarativo

### 11. Quindici chiavi di calibrazione non sono lette da nessuno

Su 88 totali:

```
tempo.giorni_per_tick                     = 7      (il 7 è inciso in Calendario)
economia.peso_commercio                   = 0.25   (documentato in docs/21)
economia.peso_dipendenza                  = 0.30   (idem)
insurrezione.moltiplicatore_armi_insorti  = 2.0
colpo_di_stato.peso_destabilizzazione     = 1.0
relazioni.obbligo.* (sei voci)            = 0…128  (vedi reperto 6)
relazioni.peso_storia_su_ideologia        = 8.0
intelligence.decadimento_rapporto         = 0.03
intelligence.operazioni_max               = 6
intelligence.sonde_per_bersaglio_tick     = 1
```

Una manopola che non muove niente è peggio di una manopola assente: chi tara il
mondo la gira e aspetta un effetto che non arriverà.

### 12. Due chiavi per lo stesso concetto, una morta

`intelligence.operazioni_max = 6` non è letta. Il tetto alle operazioni in volo
esiste davvero, ma si chiama `dottrina.azioni_in_volo_max` e vale **4**. Chi
guarda la prima crede che il tetto sia sei.

### 13. Un commento che dice il falso

In fase 02, su `armare_insorti`:

```php
// Le armi consegnate agli insorti valgono il doppio: le usano
// con piu' cura perche' ne hanno poche (Crawford).
$b->forzaInsorti += $b->potenzaGoverno() * 0.11 * $i;
```

Nessun raddoppio è applicato, e la chiave che lo esprimerebbe
(`insurrezione.moltiplicatore_armi_insorti = 2.0`) è morta. O il 2 è già dentro
lo 0,11 — e allora il commento va riscritto — o il comportamento manca.

### 14. Dodici tabelle nate con una collazione diversa *(corretto)*

Le migrazioni dalla 0009 in poi dichiaravano `DEFAULT CHARSET=utf8mb4` senza
dire anche `COLLATE`. Sembra innocuo e non lo è: MariaDB 11.8 non eredita la
collazione del database, ci mette la propria per quel charset
(`utf8mb4_uca1400_ai_ci`). Dodici tabelle nuove sono nate diverse dalle trenta
vecchie.

L'errore non si vede scrivendo il codice: si vede solo quando una query
confronta una stringa di una tabella nuova con una di una vecchia.

```
Illegal mix of collations (utf8mb4_uca1400_ai_ci,IMPLICIT)
and (utf8mb4_unicode_ci,IMPLICIT) for operation '='
```

È emerso cancellando un account: la cancellazione confronta
`sdb_posta.destinatario` con `sdb_giocatore.email`, e si è fermata lì. La
transazione ha annullato tutto, quindi non è stato perso niente — ma qualunque
altra query fra vecchio e nuovo sarebbe caduta allo stesso modo, e nessuna
prova lo avrebbe intercettato prima.

Corretto con la migrazione `0020`, che riporta tutte e quarantadue le tabelle
alla collazione del database, e correggendo le otto migrazioni perché chi
installa da zero non erediti il problema.

### 15. Un campo identitario mai usato

`ideologiaFormale` non è letta da nessuna fase. Compare solo nella scheda di un
paese, e per un'altra via (una giunzione SQL su `sdb_ideologia`). Il campo
sull'oggetto è inerte.

---

## Che cosa vuol dire, in breve

Il motore è **solido dove si muove**: deterministico, fedele nel salvataggio,
numericamente sano su quindici anni, senza fasi morte e senza le sovrascritture
che lo avevano già tradito tre volte.

Ma è **più sottile di quanto sembri**. Tre dimensioni intere — repressione
interna, difesa cibernetica, armamento nucleare — sono costanti che il motore
legge e nessuno scrive. Una quarta, il controllo dell'informazione, può solo
peggiorare. Quattro verbi su diciotto non escono mai dalle mani dell'apparato, e
per la mediazione la causa è un conflitto fra due regole scritte in momenti
diversi. Quindici manopole di taratura non sono collegate a niente. E dodici tabelle su
quarantadue erano nate con una collazione diversa dalle altre — un difetto
invisibile a chi legge il codice, che si manifesta solo quando due tabelle di
epoche diverse si incontrano in un confronto.

Nessuno di questi è un errore che si vede giocando: sono tutti casi in cui il
modello promette una cosa e ne fa un'altra, in silenzio. È esattamente il tipo
di debito che un audit serve a trovare.


---

# Parte seconda — il secondo giro, e le correzioni

## Lo stampo dell'errore: quattro volte lo stesso

Il reperto più importante del secondo giro non è un difetto: è uno **stampo**.

Quattro campi — `statoPolizia`, `cyberDifesa`, `ansiaMilitare`,
`controlloInfo` — erano dichiarati, salvati a ogni tick, letti dal motore, e
fermi per sempre. La ragione è sempre la stessa, e non si vede leggendo il
codice:

```php
public int $statoPolizia;              // intero
$n->statoPolizia -= 0.55 / 52;         // il motore lo muove di 0,0106
// round(2 - 0,0106) = 2               // l'arrotondamento se lo mangia
```

**Una grandezza che il motore muove per frazioni non può essere un intero.**
Ogni tick l'arrotondamento cancella il movimento e il valore torna identico.
Centottantanove paesi hanno avuto la stessa polizia per quindici anni senza che
niente lo segnalasse.

Tutti e quattro sono ora `float`, con le migrazioni `0021`, `0022`, `0024` e
`0025`. E c'è una **prova che cerca lo stampo da sola**: scorre i campi interi di
`Nazione` e le fasi che li muovono, e fallisce se qualcuno ne reintroduce uno.

## Le tre dimensioni scongelate

**Lo stato di polizia** ora converge verso un livello imposto da minaccia e
istituzioni: chi si sente in pericolo stringe, chi ha istituzioni solide
stringe meno, e allentare è più lento che stringere. Dopo quindici anni va da
1,1 (Norvegia, Svizzera, Germania) a 4,2 (Sud Sudan), e i tre lettori — libertà
di stampa, malcontento, ammissibilità delle elezioni — finalmente differenziano:
la libertà di stampa va da 0,15 a 0,98 dove prima era 0,70 per tutti.

*Prima stesura sbagliata, per onestà:* derivava senza obiettivo e
centosettanta paesi su centottantanove finivano esattamente al pavimento.

**La difesa cibernetica** segue un obiettivo dato da istituzioni e ricchezza, e
paga il prezzo delle violazioni: chi viene letto per intero senza accorgersene
perde terreno — la strada che l'altro ha trovato resta aperta — e chi scopre una
manomissione la tappa e ne esce più forte. 173 paesi su 189 si muovono, da 23 a
90.

*(Il gemello offensivo `cyber_offesa` era una colonna del database senza un
campo corrispondente sull'oggetto: nessuno la scriveva, nessuno la leggeva.
Tolta con la `0023` — l'offesa cibernetica è già una delle sei discipline
d'intelligence.)*

**La proliferazione nucleare** non esisteva: nessuno prendeva né posava la bomba.
Ora tre forze decidono — il movente (paura, e soprattutto un vicino armato e
ostile), la capacità (economia, istituzioni, istruzione) e l'ombrello (una
garanzia forte di chi ce l'ha già, che è storicamente il freno più efficace che
si conosca). Risultato: **+1,8 stati nucleari ogni quindici anni**, contro circa
uno storico.

*Due stesure sbagliate:* la prima rifaceva il giro di tutte le 3074 relazioni
per ciascuna delle 189 nazioni a ogni tick — mezzo miliardo di passaggi, e il
mondo a vuoto non finiva più. La seconda accettava «stessa regione» come
sinonimo di «vicino», e armava la Svizzera, la Slovenia e la Finlandia: paesi con
la capacità e senza il movente.

## Il verbo che mancava, e la sesta disciplina

`imint` — le immagini dall'alto — era l'unica delle sei discipline che nessuna
fase usava. Non per un errore nel codice: la tabella delle discipline pertinenti
gliela assegna già ai domini militare e nucleare. Il punto è che **tutti i verbi
di quei domini erano palesi** — un'invasione non si spia, si vede — e il sistema
di scoperta non veniva mai interpellato per loro.

Mancava la cosa che in quei domini si fa di nascosto. Ora c'è
**`programma_nucleare`**: impronta bassa, maturazione lunga, e chi arriva in
fondo sale di un gradino nella postura nucleare. È anche l'unico modo per un
giocatore di entrare nel club.

Farlo funzionare ha scoperto due difetti più profondi, che valgono per tutto il
motore:

**La prova di intercettazione si ripeteva a ogni tick senza normalizzare sulla
durata.** Un sabotaggio matura in 3-6 tick e subisce altrettante prove; un
programma d'arma ne matura in 26-52 e ne subiva dieci volte tante. Risultato:
ogni azione lenta era condannata in partenza. Ora quel che si tara è la
probabilità **complessiva** di sventare, non quella del singolo giro.

**`danno > 0` è usato come sinonimo di «atto ostile».** Con `danno = 0` il
programma nucleare risultava un gesto amichevole verso un nemico, e la fase 01
lo annullava come privo di senso — trentacinque su trentacinque. Il programma
d'arma di un rivale *è* un danno alla sua sicurezza, e ora pesa anche
nell'equazione dell'oltraggio quando viene scoperto.

## I verbi che la dottrina non sceglieva mai

Quattro su diciotto. Adesso **nessuno**.

`strike` non compariva in nessuna riga della dottrina. Il primo tentativo lo
legava all'ansia militare e non usciva lo stesso: l'ansia alta ce l'hanno i
paesi in guerra, che sono piccoli e deboli, non quelli con eserciti forti e
nemici profondi. Il movente di un colpo mirato non è la paura — è l'ostilità più
la possibilità di permetterselo.

`mediazione` aveva peso 1,5 contro lo 0,8 dell'emissario e non usciva mai: chiede
indifferenza (|affinità| < 40) mentre la classifica dei bersagli premia
l'intensità del rapporto. Due regole scritte in momenti diversi che si
combattevano. Ora una guerra altrui che si può mediare pesa per quello che è —
prestigio da fare — a prescindere da come guardiamo i belligeranti. Da 0 a 241
usi.

`aiuto_economico` e `vendita_armi` chiedevano «amico e fragile», e nel modello gli
amici stretti sono paesi stabili. Ora «fragile» comprende anche chi ha
un'insurrezione in casa: è esattamente il momento in cui si manda aiuto e si
vendono armi a un amico.

## Il gradino più alto della tavola di Crawford

La tavola — 0, 16, 32, 64, 96, **128** — era incisa nel codice e si fermava a 96.
Adesso si legge dalla calibrazione e arriva in cima: tre relazioni al gradino
nucleare (Stati Uniti verso Regno Unito e Australia, Grecia verso Cipro).

*Anche qui una taratura sbagliata:* le soglie di affinità erano scritte contro la
scala teorica (0-127), ma il mondo produce al massimo 104, perché la deriva delle
relazioni comprime gli estremi verso l'ancora storica. Con le soglie tarate sul
127 il gradino più alto restava irraggiungibile in un altro modo. Ora stanno in
calibrazione, tarate sull'affinità che il mondo produce davvero.

## Le dimissioni che non potevano accadere

«Chi è ambizioso e conta niente se ne va sbattendo la porta», con la soglia a
`potere < 18`. Ma il potere di una poltrona **non scende mai sotto 39**: la
condizione non esisteva nell'intervallo che la variabile occupa. Ora è relativa
al capo, e in quindici anni sette ministri se ne vanno.

## Le manopole scollegate: da quindici a zero

Le quattro che esprimevano un comportamento vero sono state **collegate**:
il moltiplicatore delle armi agli insorti (il commento lo prometteva da sempre e
il codice non lo faceva), la tavola degli obblighi, il peso della storia
sull'ideologia — che valeva 8 volte secondo Crawford e 4 secondo il numero
inciso nel codice — e la durata di riferimento delle operazioni.

Le altre sono state **tolte**, ciascuna con una nota al suo posto che dice
perché. Una manopola che non muove niente è peggio di una manopola assente: chi
tara il mondo la gira e aspetta un effetto che non arriverà. `operazioni_max = 6`
era la peggiore, perché diceva una cosa falsa — il tetto esiste, vale 4, e si
chiama in un altro modo.

## Quel che resta dichiarato, e non corretto

**Il Belgio non esporta.** Nel grafo commerciale la taglia assoluta decide chi
entra nelle classifiche dei fornitori, e un paese piccolo resta fuori da quelle
dei vicini grandi per quanto sia aperto. Allargare i tagli non lo risolve e
annacqua le leve che contano. Scritto in `Commercio.php`, in `docs/21` e nella
prova che lo aggira dichiarandolo.

**La crescita mondiale è al 4,3 % contro il 3 % storico.** Viene da quando il
vecchio embargo piatto faceva da freno globale senza che nessuno lo avesse
voluto. Non ho reintrodotto un freno finto per far tornare il numero: resta da
capire dove il modello di crescita corre caldo.

**L'invariante delle quote di bilancio** regge in memoria a 2,2·10⁻¹⁶ e nel
database a 10⁻⁴, per l'arrotondamento di `DECIMAL(5,4)`. Va detto dove si
misura.

## Il conto

| | prima | dopo |
|---|---|---|
| dimensioni congelate | 4 | 0 |
| manopole di taratura scollegate | 15 | 0 |
| verbi che la dottrina non sceglie mai | 4 su 18 | 0 su 19 |
| gradini della tavola degli obblighi usati | 4 su 6 | 6 su 6 |
| discipline d'intelligence inutilizzate | 1 su 6 | 0 su 6 |
| colonne del database morte | 5 | 0 |
| prove automatiche | 169 | 193 |

I tassi storici non si sono mossi: cambi irregolari 11,7 l'anno contro ~10.
