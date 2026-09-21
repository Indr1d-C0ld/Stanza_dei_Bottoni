# 17 — Le agende private e il Giuda

## Il movente prima del tradimento

Nel gioco da cui veniamo il traditore è assegnato dal sistema. Con giocatori
veri sarebbe brutto in entrambi i modi: se lo si sorteggia, chi lo pesca gioca
un altro gioco; se non c'è, manca la tensione che dà il nome a tutto.

La soluzione è la stessa annunciata nel documento di progetto: **il tradimento
non si estrae, si matura.** E perché maturi serve un movente, che è quel che
fanno le agende private.

## Le agende

Ogni giocatore ne riceve **due** quando prende posto, pescate fra quelle
compatibili col suo ruolo. Nessuno le conosce tranne lui. Non coincidono con
l'interesse nazionale, e a volte lo contraddicono apertamente.

Dodici in catalogo, e ciascuna ha una **condizione verificabile dalla
macchina** — questo è il requisito che le rende vere obiettivi e non
suggerimenti narrativi. Il modello deve poter dire da solo se un'agenda è stata
raggiunta, e lo fa nella fase 10, a ogni tick.

Esempi da una partita reale, assegnati all'Intelligence di Francia:

> **Smascherarli** *(segreta)* — Fa' in modo che al tuo paese risulti attribuita
> almeno una operazione coperta di **Cina**.
>
> **Far contare il tuo paese** — Porta l'influenza totale del tuo paese sopra il
> **2,6%**.

Le soglie non sono fisse: si calcolano dal punto in cui il paese si trova, con un
margine. Un obiettivo già raggiunto non è un obiettivo, e uno irraggiungibile
nemmeno.

Alcune agende si chiudono solo in positivo (si raggiunge la soglia), altre solo
in negativo (basta una guerra per far fallire «nessuna guerra sotto di me»).
È voluto: certe cose si conquistano, altre si possono soltanto non perdere.

## Il Giuda

Chi ha l'Intelligence o il comando può fare un'**offerta** a una poltrona di
un'altra potenza. Deve mettere qualcosa sul piatto — denaro, un dossier,
appoggio alla causa di quella persona — perché un'offerta senza niente sopra non
è un'offerta.

Chi la riceve ha tre strade, e nessuna è gratis:

- **accetta**, e da quel momento quel che passa dalla sua scrivania passa anche
  altrove;
- **rifiuta**, e la proposta resta agli atti: è una prova, e le prove si usano;
- **rende pubblica**, e allora il mondo lo sa — il rapporto fra i due paesi ne
  esce a pezzi, e chi ha denunciato ci guadagna in reputazione personale.

Le poltrone dell'apparato decidono da sé, e non a caso: pesano etica, ambizione,
lealtà, quanto contano nel loro gabinetto e come sta messo il loro paese con chi
sta offrendo. Le probabilità reali stanno fra il **26% e il 35%** — il
reclutamento è una cosa che di solito non riesce, ed è giusto così.

## Che cosa vede una talpa

Una poltrona comprata non è una fonte come le altre. **Non deve scoprire niente:
sta dentro.** Tutto ciò che il suo governo ha in volo arriva al servizio che
l'ha reclutata già al quarto livello — con nome e cognome del mandante, che è
esattamente la cosa che nessun'altra disciplina regala quasi mai.

Ed è per questo che vale tanto, e che costa.

## Che cosa rischia

Ogni settimana che passa è una settimana in cui il controspionaggio di casa può
accorgersene. La probabilità cresce con l'anzianità della talpa e con la
solidità delle istituzioni ospiti, e si arriva alla verità in due tempi:

1. **il sospetto** — qualcosa non torna, ma non basta per un arresto. Resta
   dentro le mura: la stampa non ne sa nulla, e il servizio che l'ha reclutata
   lo vede scritto accanto al nome del suo uomo;
2. **lo smascheramento** — la poltrona viene liberata, il rapporto fra i due
   paesi crolla di trentacinque punti, e la cosa finisce sul feed pubblico
   perché un processo è per definizione pubblico.

## Una partita vera

Il giocatore che tiene l'Intelligence di Francia ha fatto due offerte.

La prima, all'**Economia russa**: rifiutata. Zofia Nowicki aveva una
propensione del 31% e i dadi hanno detto di no.

La seconda, all'**Informazione russa**, con denaro, dossier e appoggio sul
piatto e un messaggio mirato — *«Il suo nome non compare mai nei verbali delle
riunioni che contano. Noi possiamo fare in modo che compaia altrove, e con
altro peso»* — **accettata**.

Da quel tick, Andrej Zelinski lavora per Parigi, e la Francia legge le
operazioni russe in volo col nome del mandante sopra.

## Una nota di onestà sulla verifica

Il meccanismo della fuga di notizie l'ho collaudato piazzando una talpa di prova
alla Difesa russa e controllando che la conoscenza arrivasse davvero. Nel
ripulire, ho cancellato **anche il reclutamento legittimo** con una `UPDATE`
troppo larga.

L'ho ripristinato dalla fonte di verità — il record dell'offerta accettata, che
porta il tick della risposta — e non dai miei ricordi. È la differenza fra
rimettere a posto e riscrivere la storia.
