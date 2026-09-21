# 25 — Telefoni e tavolette

Il vincolo era esplicito: **il desktop non si tocca**. Tutto quel che segue sta
dentro delle media query, e una prova automatica verifica che sia davvero così —
basta una parentesi fuori posto perché una regola pensata per un telefono
finisca addosso a un monitor.

## Cosa c'era già, e cosa no

Le fondamenta c'erano: il `meta viewport`, `main` con larghezza massima e
margini automatici, i quadranti con `auto-fit`, il planisfero a `width: 100%`.
Mancava il resto: **zero media query** in centosessantotto righe di stile.

Il problema vero misurato su uno schermo da 375 pixel:

```
/nazioni   tabella da 14 colonne, larga 452px
           → la PAGINA scorreva di lato di 85 pixel
```

È il modo classico in cui un sito diventa inusabile su un telefono: non è che si
legga male, è che tutto si sposta mentre provi a leggere.

## Le tabelle, che sono il punto

Questo progetto è fatto di tabelle — il banco dell'arbitro ne ha una da nove
colonne, le nazioni una da quattordici — e sono la cosa che rompe la pagina.

Sotto i 600 pixel ciascuna **scorre dentro sé stessa** e la pagina resta ferma:

```
prima:  pagina 460px in un riquadro da 375   → traboccava di 85
dopo:   pagina 375px, tabella 801px che scorre nei suoi 353
```

`display: block` su una tabella è il modo consolidato di ottenerlo senza toccare
l'HTML di venti viste. Un bordo a destra dice che c'è altro da vedere, che
altrimenti non si indovina.

### Ma non tutte le tabelle sono uguali

Il `nowrap` che rende sensato lo scorrimento di una griglia di numeri è
sbagliato per una descrizione: la trasformerebbe in una riga larga seicento
pixel. Le tabelle che contengono **prosa** — i ruoli e i livelli di conoscenza
nella guida, le descrizioni delle leve nel banco, i settori nel commercio —
portano la classe `prosa` e restano nello schermo, col testo che va a capo.

Sette tabelle marcate a mano, perché nessuna regola CSS può distinguere un
numero da una frase.

## Le altre cose che cambiano sotto i 600 pixel

**I campi di modulo a sedici pixel.** Sotto quella soglia iOS ingrandisce la
pagina da solo quando ci si tocca dentro, e poi non la rimpicciolisce. È il
dettaglio che fa sembrare rotto un sito che non lo è.

**Un'area toccabile per i bottoni.** I bottoni «minuti» del banco dell'arbitro
erano alti diciotto pixel: la metà di quel che Apple e Google raccomandano da
anni. Un dito non è un puntatore.

**La navigazione scorre invece di spingere.** Otto voci più l'orologio su
trecentosettantacinque pixel: adesso la barra sta su due righe e la navigazione
scivola di lato per conto suo.

**Un centimetro di margine in meno**, che su uno schermo da 375 sono venti pixel
di testo in più.

**Le stringhe che non si spezzano** — codici d'invito, chiavi di calibrazione —
adesso vanno a capo invece di sfondare il riquadro.

**Il planisfero esce a tutta larghezza**, da bordo a bordo, e le sei letture
diventano una fila che scorre. Lo zoom con le dita funzionava già e va lasciato
stare: su una mappa del mondo alta 190 pixel è l'unico modo di toccare un paese
piccolo.

**Dove si tocca invece di puntare** (`@media (hover: none)`) l'effetto di
passaggio del mouse sul planisfero si spegne: senza mouse resterebbe appiccicato
per sempre all'ultimo paese toccato.

## Il difetto trovato per strada

Il foglio di stile veniva servito sempre allo stesso indirizzo, quindi **il
browser teneva la propria copia e una correzione allo stile non sarebbe mai
arrivata a chi era già stato sul sito**. Ora l'indirizzo porta in coda la data
dell'ultima modifica del file: cambia quando cambia il file, e solo allora. La
cache continua a funzionare per tutto il resto del tempo, che è quel che deve
fare.

Non è un problema di telefoni — riguardava anche il desktop — ma è saltato fuori
perché le mie prove non vedevano mai le regole nuove.

## Quel che non ho potuto verificare

**Non ho visto le pagine.** Il pannello del browser di questo ambiente non
applica i fogli di stile esterni: a schermo largo `main` risulta senza margini e
il corpo senza sfondo, cioè la pagina è nuda. Gli screenshot vanno in timeout.

Ho aggirato il limite iniettando le regole dentro la pagina e **misurando** —
traboccamento, larghezze, altezze dei campi, dimensione dei caratteri — che è
più rigoroso di un'occhiata ma non la sostituisce. I numeri qui sopra sono tutti
misurati così.

Quello che i numeri non dicono è se si legge bene. Per quello serve un telefono
vero.
