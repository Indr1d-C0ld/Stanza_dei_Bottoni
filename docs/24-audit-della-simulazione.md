# 24 — Audit della simulazione

Non una rilettura del codice: misure. Ogni reperto qui sotto è stato ottenuto
facendo girare il motore e contando, e ognuno riporta il numero che lo dimostra.

Il mondo di prova è quello del profilo `osservazione`, 780 tick — quindici anni
— dal seme delle 189 nazioni.

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
