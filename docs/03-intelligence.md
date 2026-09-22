# 03 — Il sottosistema di intelligence

## Le sei discipline

| Disciplina | Vede bene | È cieca su | Latenza | Attribuzione |
|---|---|---|---|---|
| OSINT | fatti pubblici, economia, movimenti visibili | intenzioni, coperto | nulla | nulla, ma **corrobora** |
| HUMINT | **intenzioni**, decisioni, chi ha deciso | quantita', dettagli tecnici | alta | **massima** |
| SIGINT | comunicazioni, coordinamento, reti | cio' che non passa per un canale | bassa | alta |
| IMINT | truppe, costruzioni, logistica | intenzioni | media | media |
| CYBER | archivi, pianificazione | cio' che è fuori rete | bassa | alta, **ma espone** |
| FININT | denaro, finanziamenti occulti, **proxy** | cio' che non costa | media | alta sui mandanti |

HUMINT è l'unica disciplina che arriva regolarmente al livello 4
dell'attribuzione. OSINT è il **pavimento che tiene in gioco i piccoli**: costa
poco e serve a corroborare, cioè è lo strumento con cui un giocatore povero
smaschera un dossier falso confezionato da un giocatore ricco.

## Copertura e bilancio

Per ogni coppia paese x disciplina esiste una copertura 0-100: si costruisce
investendo, decade se non la finanzi, crolla se ti espellono gli operativi.

Il bilancio si divide ogni tick fra **raccolta**, **analisi**,
**controspionaggio** e **operazioni coperte**. È a somma zero, e la tentazione
costante è sacrificare il controspionaggio, che non produce nulla di visibile
finché non è troppo tardi.

## Il tiro di scoperta

    P(scoperta al livello N) = impronta x copertura nel bersaglio x analisi / OPSEC

Quattro livelli progressivi e **monotoni** (la conoscenza non regredisce mai):

1. **Esistenza** — "sta per succedere qualcosa"
2. **Dominio e regione** — "operazione di intelligence, in Africa occidentale"
3. **Bersaglio** — "il paese è il Mali"
4. **Attribuzione** — "il mandante sono loro, e posso dimostrarlo"

Il quarto è un ordine di grandezza più difficile degli altri tre. È la
struttura degli indizi di CyberJudas (prima i *where*, poi i *who*),
generalizzata a tutti gli eventi del mondo.

Due dettagli dall'originale: **gli eventi sono molto più facili da fermare se
individuati presto** (la reversibilità decade con la maturazione), e i
**depistaggi** esistono e si verificano solo spendendo raccolta.

## Le reti di agenti

Ciclo in cinque fasi: individuazione, avvicinamento, sviluppo, reclutamento,
gestione. Un asset ha accesso (a quali compartimenti arriva), affidabilita',
motivazione (denaro, ideologia, risentimento, ego) e rischio per tick che cresce
con l'uso. **Un asset che non usi non ti tradisce.**

Un asset può essere **voltato**: il controspionaggio che lo scopre può
bruciarlo o — peggio per te — lasciarlo al suo posto e nutrirti di falsi. Un
canale HUMINT compromesso e non riconosciuto è la cosa più pericolosa del
gioco: alta affidabilita' dichiarata, contenuto scelto dal nemico.

## Il reclutamento di una poltrona

Il servizio individua una poltrona vulnerabile (ambizione alta, ranking basso,
etica bassa, agenda frustrata, debito, scandalo latente) e fa **un'offerta** —
che nel caso di un giocatore umano è letteralmente un messaggio con dentro
qualcosa di concreto. Chi accetta ottiene vantaggi reali e assume rischi reali.
Chi rifiuta ha in mano una prova, e può denunciare, ricattare o fare il doppio
gioco. Le poltrone IA seguono lo stesso procedimento con la loro etica e
ambizione a decidere.

## La compartimentazione: il trade-off centrale

> **NOTA, settembre 2026 — questo paragrafo descriveva un disegno che è stato
> superato.** La compartimentazione a poltrone non è mai stata costruita, e
> `sdb_evento_accesso` è stata tolta dallo schema (migrazione 0028) perché
> nessuna riga di codice la scriveva o la leggeva. L'ha trovata `bin/audit.php`
> incrociando le tabelle col sorgente.
>
> Quel che esiste al suo posto fa lo stesso mestiere con due pezzi diversi: il
> quadrante continuo **`copertura`** su ogni evento — quanto si investe in
> OPSEC, da 0 a 1 — e la tabella **`sdb_conoscenza`**, che tiene per OGNI
> osservatore a quale dei quattro livelli è arrivato su quell'evento. Il
> compromesso c'è ancora, ma è una manopola invece che un numero di poltrone.
>
> La tabella qui sotto resta come testimonianza del disegno originale.

Ogni evento in volo portava la **lista di chi ne è a conoscenza**. Quando
pianificavi, decidevi quante poltrone metterci:

| Compartimenti | Efficacia (LER) | Rischio di fuga | Sospetti se trapela |
|---|---|---|---|
| Solo tu | bassa | minimo | nessuno |
| 2-3 poltrone | media | medio | stretto, indagine facile |
| Gabinetto intero | alta | alto | largo, indagine quasi impossibile |

Ti serve la competenza delle poltrone perché l'operazione riesca. Quindi ogni
volta che agisci in segreto scegli fra **riuscire** e **sapere chi ti ha
tradito**.

## Il ciclo del controspionaggio

1. Anomalia — un'operazione fallisce in modo inspiegabile
2. Intersezione — quali compartimenti coprono tutte le anomalie?
3. Lista dei sospetti — chi aveva accesso a tutti
4. Indagine — sorveglianza con durata, costo e **tiro di esposizione**
5. Prova — accumulo fino alla soglia
6. Azione — arresto, ricatto, o lasciarlo al suo posto e nutrirlo di falsi

Condizione politica presa da CyberJudas: per far condannare un sospetto servono
**anche più popolarita' e più potere di gabinetto di lui**. Se non ce li hai,
prima lo devi indebolire. Se l'indagine fallisce, è scandalo pubblico: perdi
popolarita' e l'accusato guadagna potere.

## Intercettazione fra giocatori

Tre livelli progressivi: **metadati** (chi parla con chi e quando: spesso
basta), **contenuto parziale**, **contenuto integrale**.

Contromisure: canali cifrati (costosi, riducono i messaggi per tick), corrieri e
incontri di persona (lenti, attaccabili solo da HUMINT), e la contromisura che
emerge da sola — usare un canale che sai compromesso per far leggere
all'avversario quel che vuoi.

## Le tre regole di garanzia

1. **Nessuno è onnisciente**: tetti di tempo operativo per bersaglio e per tick
   (erede della regola "una sola sonda al giorno per consigliere"). Il collo di
   bottiglia non è il denaro, è il numero di operazioni simultanee.
2. **Nessuno è cieco**: il pavimento OSINT garantisce a chiunque il quadro
   pubblico del mondo.
3. **Il silenzio non deve convenire**: i canali sicuri devono costare ma essere
   accessibili. Se parlare è sempre pericoloso, i giocatori smettono di parlare
   e il gioco muore di afasia. L'intercettazione dev'essere un evento raro e
   memorabile, non il rumore di fondo.
