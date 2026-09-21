# 16 — Il Canale

La regola sta in sei parole: **i tuoi messaggi privati non sono sicuri.**

È la meccanica che l'utente aveva indicato fin dalla prima conversazione, ed è
quella che, da sola, produce buona parte del gioco politico — perché cambia
*cosa* scrivi, *a chi*, e *su quale canale*; e perché prima o poi qualcuno userà
un canale che **sa** compromesso per far leggere all'avversario esattamente ciò
che vuole.

## Quattro gradini

| | Canale | Cosa comporta |
|---|---|---|
| 1 | aperto | dichiarazione pubblica: la leggono tutti, per costruzione |
| 2 | diplomatico | valigia diplomatica: difesa ordinaria |
| 3 | cifrato | costoso e lento da violare, non impossibile |
| 4 | corriere | a mano: i segnali non lo toccano, **ma arriva fra due tick** |

La scarsità è nei canali riservati: **tre messaggi a sicurezza alta per poltrona
per tick**. Non è un limite tecnico, è la ragione per cui bisogna scegliere che
cosa vale la pena proteggere.

Il corriere è l'unico canale intoccabile dai segnali, e paga con il tempo. È il
genere di scambio che rende una decisione interessante.

## L'intercettazione

Nella fase 08, per ogni messaggio partito nella finestra fra un tick e l'altro,
chi ha segnali e presenza tenta di leggerlo. Tre livelli, progressivi:

- **metadati** — chi ha parlato con chi, e quando. Spesso basta: un'impennata di
  traffico fra due capitali alla vigilia di qualcosa dice tutto;
- **parziale** — un frammento, il resto non ricostruito;
- **integrale** — il testo.

La probabilità dipende dalla capacità SIGINT dell'ascoltatore, dalla sua presenza
nei due paesi coinvolti, dalla solidità cibernetica di chi parla e dal gradino
del canale. Il contenuto è molto più difficile dei metadati, deliberatamente.

Le intercettazioni le vede **solo chi ne ha il mestiere** — Intelligence,
Sicurezza interna, Capo. Non è roba da tutto il gabinetto: è il prodotto di un
lavoro, non un privilegio.

## La prova

Due messaggi dalla poltrona dell'Intelligence di Francia al Capo della Russia:

    canale diplomatico: «Il sabotaggio di Voronezh non era nostro.
                         Vi conviene guardare altrove.»
    canale cifrato:     «Era nostro. Ma non avete modo di dimostrarlo,
                         e lo sappiamo entrambi.»

Un giro d'orologio dopo:

    Stati Uniti   metadati   canale 2
    Brasile       metadati   canale 3

Nessuno dei due ha letto il contenuto. Ma entrambi sanno che l'Intelligence
francese ha scritto due volte al Capo russo nello stesso giorno — e questo, in
un mondo dove un sabotaggio francese in Russia è appena stato fermato, è già
un'informazione.

## Lo scarto di un tick

Alla prima prova le intercettazioni erano zero, e la causa era un difetto di
modellazione piccolo e significativo: **un solo campo faceva due mestieri**.
`tick` indicava insieme quando il messaggio parte e quando arriva — cosa che per
tre canali su quattro coincide, ma non per il corriere. E siccome il web timbra i
messaggi con l'ultimo tick *concluso* mentre la fase 08 cercava quelli del tick
*corrente*, la finestra non si sovrapponeva mai.

Ora ci sono due campi distinti, `tick_invio` e `tick`, più un `intercettabile`
che il corriere spegne alla partenza. La lezione è la solita: quando un campo
significa due cose, prima o poi ne significa una sola, e quasi sempre è quella
sbagliata.

## Quel che manca

Il **falso in transito**: fabbricare un messaggio e farlo trovare a chi
intercetta. La struttura c'è già — i falsi dossier funzionano allo stesso modo —
ma serve l'interfaccia.

E la **fiducia comprabile**: canali dedicati fra due potenze che si accordano,
più cari e più sicuri di quelli standard. Perché la contromisura al sapere che
si è letti non è tacere, è scegliere con chi si costruisce un filo diretto.
