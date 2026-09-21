# 05 — Dati, scenari, validazione

## Le nazioni giocabili

Il criterio non è la potenza: è che **ognuna abbia un problema diverso**.

| Potenza | La tensione che la definisce |
|---|---|
| Stati Uniti | troppi impegni, risorse finite |
| Cina | dipendenza da rotte e mercati che non controlla |
| Russia | potenza militare senza profondita' economica |
| India | non allineamento come posizione attiva |
| Francia | proiezione residua e autonomia da finanziare |
| Germania | peso economico senza strumenti coercitivi |
| Regno Unito | intelligence di prima classe, il resto di seconda |
| Giappone | ricchezza e dipendenza totale da import |
| Turchia | due blocchi, un collo di bottiglia, nessuna scelta definitiva |
| Iran | assedio economico e profondita' per procura |
| Israele | capacità enormi su base demografica minima |
| Arabia Saudita | rendita da convertire prima che finisca |
| Brasile | egemone regionale senza avversari e senza urgenze |
| Indonesia | posizione decisiva, ambizioni deliberatamente basse |

Riserva: Sudafrica, Nigeria, Pakistan, Corea del Sud, Egitto.
L'Unione Europea **non giocabile ma contesa**: più interessante come posta.

## Fonti

| Serve | Da | Licenza | Avvertenza |
|---|---|---|---|
| Geografia, popolazione, economia, forze armate | World Factbook (archivio 1990-2025) | pubblico dominio | dismesso online da feb 2026: usare gli archivi |
| Regime, diritti, stato di diritto | V-Dem | accademica | verificare ridistribuzione |
| Spesa militare, trasferimenti d'arma | SIPRI | termini propri | idem |
| Conflitti in corso | UCDP/PRIO | accademica | idem |
| Matrice commerciale | UN Comtrade | aperta con limiti | pesante da elaborare |
| PIL, debito, riserve | Banca Mondiale, FMI | aperta | |
| Alleanze storiche | Correlates of War | accademica | |

**Regola operativa**: non ridistribuiamo mai i dataset originali. Ne ricaviamo
**indici normalizzati 0-100 calcolati da noi**, pubblichiamo solo quelli,
documentiamo la provenienza. Pulito legalmente e migliore tecnicamente: ci
disaccoppia dalle fonti.

## Gli indici che fabbrichiamo noi

Crawford ha fabbricato la sua *Maturity* e l'ha confessato in stampa. Noi ne
fabbricheremo meno, ma li dichiariamo tutti:

- **Valore Strategico** — risorse, rotte, colli di bottiglia: pesatura nostra
- **Ambizione** ed **Etica** per nazione — nessun dataset esiste; ancorabili a
  comportamenti osservabili (uso della forza nel decennio, aderenza ai trattati,
  coerenza dei voti multilaterali), ma la scala è un atto d'autore
- **Sfera di influenza** — da basi, commercio e storia di intervento
- **Valore di Prestigio** — quanto la sorte di un paese conta nell'ordine mondiale
- **Copertura di intelligence iniziale** — stima pura, non esiste nulla di pubblico
- **Sostituibilità** per settore commerciale
- **Fratture interne**

Ogni indice fabbricato vive in `calibrazione/`, versionato e commentato: le
discussioni su "ma l'Italia non è messa così" diventano modifiche tracciate.

## Data di partenza

**Non** la data di oggi: chi legge le notizie avrebbe un oracolo, il gioco
invecchierebbe male, e conterrebbe verbi come *assassinio mirato* puntati su
persone reali e vive.

Soluzione: condizioni iniziali contemporanee, **data di divergenza dichiarata**,
cariche pubbliche ricoperte da **personaggi fittizi**. Gli Stati sono reali, le
persone no.

## Scenari (formato Ipotesi + Razionale, da Shadow President)

| Scenario | Ipotesi | Razionale |
|---|---|---|
| **base** | il mondo come lo conosciamo, da una divergenza dichiarata | — |
| **frammentazione** | il sistema dei pagamenti spezzato in tre blocchi | uso eccessivo delle sanzioni finanziarie |
| **cascata** | sette nuovi stati nucleari | crollo delle garanzie di sicurezza |
| **penuria** | stress idrico e alimentare acuto in tre regioni | decennio climatico avverso |

## I personaggi

Per ogni potenza si genera un **bacino di funzionari**: nome, biografia, etica,
ambizione, competenza per dominio, legami con le fazioni, e 2-3 **vulnerabilita'**
(un debito, un legame imbarazzante, un'ambizione frustrata). Da li' si pescano le
poltrone IA. Senza biografie, i dossier non hanno niente dentro.

## Il simulatore autonomo

Stesso motore, profilo `osservazione`. Non è un'estensione: è la F0 lanciata
senza giocatori.

**Cosa può fare**: storie controfattuali plausibili con catena causale
ispezionabile; far emergere dinamiche non progettate (nel gioco d'esempio di
Crawford i sovietici collassano per sovraestensione logistica, non per
sconfitta: nessuno l'aveva scritto); esplorare scenari; **tenere vivo il mondo
del gioco**, dove 176 nazioni su 190 saranno sempre governate dalla simulazione.

**Cosa non può fare**: prevedere. Parametri in parte fabbricati; processi
generalizzati mentre le crisi reali sono individuali; manca la contingenza;
deriva composta; e soprattutto **un gioco si tara per essere interessante, un
simulatore per avere ragione**.

## Validazione

Abbiamo sia le condizioni iniziali sia la verità a posteriori: l'archivio del
Factbook copre **1990-2025**, trentasei edizioni. Si inizializza al 1990 (la
stessa edizione che usava Shadow President) e si fanno girare 35 anni.

Tre regole metodologiche:

1. **Si valida su distribuzioni, non su traiettorie.** Il modello non deve
   riprodurre il 1991: sarebbe sovra-adattamento. Deve produrre quantita' di
   cambi di regime, insorgenze e conflitti negli ordini di grandezza giusti e
   distribuite in modo geograficamente riconoscibile.
2. **Si ragiona per ensemble.** Mai una esecuzione: 20-100 semi e si guarda la
   dispersione. Se convergono tutte il modello è troppo deterministico; se
   divergono del tutto è rumore.
3. **Si divide il periodo.** Taratura su 1990-2005, verifica su 2005-2025, senza
   guardare la seconda metà mentre si tara la prima.

### I tassi di riferimento (da Crawford)

| Assertivo | Riferimento storico |
|---|---|
| Insurrezioni | ~200 significative in 40 anni, **20% di successo** |
| Cambi irregolari di esecutivo | 238 riusciti / 304 falliti, **44%** |
| Cambi regolari | 1645 / 409, **80%** |
| Rivolte che producono un cambio di esecutivo | ~10.000 rivolte, **1%** |
| Guerra nucleare in 15 anni senza giocatori | **mai** |

Se il motore a vuoto sta in quegli ordini di grandezza e produce i conflitti dove
ci sono le tensioni invece che a caso, abbiamo un mondo.

## I rischi

1. **Falsa autorevolezza** — è il rischio serio. Un sistema pieno di dati veri
   *sembra* autorevole ("c'e' una differenza enorme fra l'impressione di
   verosimiglianza e la verosimiglianza", Crawford, cap. 1). Contromisure di
   progetto, non disclaimer: leader fittizi, divergenza dichiarata in testa,
   output come distribuzioni su ensemble e mai singoli eventi datati, nessuna
   pubblicazione in forma di previsione su paesi reali.
2. **Tentazione della complessita'** — più variabili significa quasi sempre meno
   accuratezza e più fragilita'.
3. **Sovra-adattamento** — coperto dalla divisione del periodo.
