# 10 — La fase 08: la scoperta

Stato: **l'attribuzione si conquista.** La fase 08 è implementata e le fasi 01 e
02 non hanno più segnaposti: agiscono sulla conoscenza costruita qui.

## Il modello fattorizzato

Non teniamo una copertura per ogni terna (servizio × paese × disciplina):
sarebbero duecentomila celle quasi tutte a zero. Ne teniamo due che si
moltiplicano:

    capacità[servizio][disciplina]   quanto vale quel servizio in quel mestiere
    presenza[servizio][bersaglio]    quanto è radicato in quel paese

È una semplificazione vera e va detta: implica che un servizio bravo con i
segnali lo sia ovunque abbia presenza. In cambio costa poco e conserva la cosa
che conta — che la copertura sia una risorsa scarsa, costruita nel tempo e
distribuita male. Decade se non la si coltiva.

Le sei discipline hanno ciascuna il proprio campo: le intenzioni si rubano alle
persone, i movimenti si vedono dal cielo, il denaro si segue nei conti. Quale
serve dipende dal dominio dell'evento.

E siccome il peso economico è una misura ingannevole della capacità di vedere —
Israele e il Regno Unito pesano poco e vedono molto — `politica-nota.php` porta
anche venti moltiplicatori dichiarati.

## I quattro livelli

Sono quelli di CyberJudas, generalizzati da un solo traditore a tutti gli eventi
del mondo: prima gli indizi sul *dove*, poi quelli sul *chi*.

| | |
|---|---|
| 1 | esistenza — "sta per succedere qualcosa" |
| 2 | dominio e regione — "un'operazione di intelligence, in Africa occidentale" |
| 3 | bersaglio — "il paese è il Mali" |
| 4 | **attribuzione** — "il mandante sono loro, e posso dimostrarlo" |

La conoscenza è **monotona**: non si dimentica mai quel che si è capito.

Tre asimmetrie deliberate:

- **i primi tre livelli sono generosi**, come nel gioco originale, dove il
  Tracer sforna indizi in continuazione e la mappa dei bersagli possibili si
  restringe da sola. Il quarto è il muro;
- **per i primi tre livelli si guarda dove l'operazione atterra, per il quarto
  da dove parte** — ed è tutt'altro mestiere. È per questo che un paese piccolo
  si accorge benissimo di essere colpito e non riesce quasi mai a dimostrare da
  chi;
- **per il bersaglio i livelli due e tre sono impliciti**: i gradini "che
  dominio" e "quale paese" servono a chi guarda da fuori, non a chi si ritrova
  l'operazione in casa.

## Il buco che mancava

Alla prima prova le operazioni coperte venivano notate nell'8% dei casi. Il
motivo era strutturale: **un paese non ha "presenza" dentro sé stesso**, e la
formula della copertura non prevedeva altro. Vedere in casa propria non è
questione di presenza, è controspionaggio, e ora ha la sua funzione.

Nella stessa passata: le azioni dichiarate non si "scoprono", si leggono sul
giornale — vengono marcate al quarto livello senza alcun tiro.

## Il risultato, che è il progetto in una tabella

Quindici anni, profilo `osservazione`:

| | totale | notate | bersaglio identificato | **dimostrate** |
|---|---|---|---|---|
| azioni dichiarate | 919 | 96% | 96% | **96%** |
| operazioni coperte | 153 | 71% | 71% | **3%** |

Nel profilo `gioco`, più indulgente: 81% notate, **10% dimostrate**.

*Ti accorgi quasi sempre di essere colpito. Non riesci quasi mai a provarlo.*

Da qui discende tutto:

- la **fase 02** calcola il contraccolpo diplomatico sull'attribuzione
  **raggiunta**, non su quella vera: un'operazione mai attribuita non costa
  nulla in rapporti, per quanto danno faccia. E i terzi che hanno dimostrato la
  stessa cosa reagiscono anche loro, con meno intensità;
- la **fase 01** lascia reagire il bersaglio solo su ciò che sa: serve il terzo
  livello per provare a fermare un'operazione, il quarto perché il rapporto
  diplomatico ne risenta. Nel giornale, un'operazione sventata senza
  attribuzione compare con mandante «ignoti».

## Quel che non c'è ancora

- **La falsificazione dei dossier.** I rapporti hanno già proprietario,
  disciplina, accuratezza e tick di raccolta, ma nessuno li fabbrica e nessuno
  li legge: senza giocatori non ci sarebbe chi ingannare. È il primo pezzo da
  aggiungere quando arriveranno.
- **L'intercettazione dei messaggi**, per lo stesso motivo.
- **Il bilancio dell'intelligence**: capacità e presenza sono condizioni
  iniziali che decadono, ma nessuno le finanzia. Serve la scelta a somma zero
  fra raccolta, analisi, controspionaggio e operazioni.
- **I depistaggi**: la struttura dell'evento li prevede (`stato` =
  `depistaggio`), la dottrina non li genera.
