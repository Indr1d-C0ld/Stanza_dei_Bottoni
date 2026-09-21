# 23 — Il planisfero

Il cruscotto aveva quattro sezioni e tutte e quattro erano tabelle. Per un gioco
geopolitico è una mancanza che si nota subito: una mappa dice in un colpo
d'occhio quello che in una tabella si legge in due minuti, e soprattutto dice
cose che una tabella non dice affatto — che i paesi in crisi sono tutti
confinanti, per esempio.

## Il problema: non avevo la geografia

Il seme viene dal World Factbook e porta regione, superficie e confini (312
coppie con i chilometri di frontiera). **Nessuna coordinata.** Sapevo con chi
confina l'Italia, non dove sta.

I confini vengono da **Natural Earth**, che è di dominio pubblico. Risoluzione a
1:50 milioni e non a 1:110: quella grossolana perde ventitré paesi, fra cui
Singapore — e sparire dalla mappa perché si è piccoli non va bene in un gioco
dove i piccoli contano, visto che Singapore nel modello commerciale è una delle
prime piazze finanziarie del mondo.

## La conversione, una volta sola

`bin/costruisci_mappa.php` scarica il GeoJSON, lo proietta, lo sgrossa e scrive
`db/seed/confini-svg.json`: tracciati SVG già pronti, uno per codice ISO. **A
runtime non si fa nessun conto geografico**: si colora e basta.

Il GeoJSON pesa 3 MB e non sta in git; quel che sta in git è il risultato, 133
KB. Chi vuole ricostruirlo lancia il programma senza argomenti e se lo
riscarica.

### La proiezione

Robinson. Non conserva né le aree né gli angoli — non conserva niente, è un
compromesso dichiarato — ma è quella che «somiglia a un planisfero» e non gonfia
la Groenlandia fino a farla sembrare l'Africa. In un gioco dove il peso dei
paesi si legge dai numeri e non dalla mappa, la leggibilità vale più della
correttezza metrica.

### Tre cose che la conversione ha preteso

**La tolleranza proporzionata.** Douglas-Peucker con una soglia fissa portava il
file da 972 a 98 KB, ma riduceva Cipro e il Lussemburgo a un punto: una mano
tarata sulla Russia è troppo pesante per loro. Ora la tolleranza è proporzionale
alla taglia del paese — un paese piccolo ha bisogno di una mano leggera, e costa
poco perché è piccolo. Il file sale a 133 KB e Cipro esiste.

**Il rombo al posto dell'isola.** Le Maldive sono un arcipelago di atolli: a
scala mondiale non c'è un contorno da disegnare, qualunque tolleranza si usi. Al
loro posto va un rombo — restano una nazione del gioco come le altre, si
colorano e si cliccano.

**I buchi si buttano.** Laghi ed enclavi costano byte e a questa scala non si
vedono: si tiene solo il contorno esterno di ogni poligono.

## Le sei letture

Una mappa politica non serve a sapere dove stanno i posti. Serve a vedere una
cosa che nelle tabelle si perde, e ogni lettura ne mostra una diversa:

| lettura | cosa mostra |
|---|---|
| **Chi tiene in piedi il proprio governo** | legittimità: verde regge, rosso sta per cadere |
| **Chi conta** | quota di influenza mondiale |
| **Dove si spara** | dalla quiete alla guerra totale |
| **Come si vive** | qualità della vita, che non coincide col prodotto |
| **Chi ha i rubinetti chiusi** | pressione degli embarghi, in punti di crescita persi |
| **Chi ci vuole bene** | come ci guardano, dal proprio paese — solo per chi siede a una poltrona |

La scala dell'influenza non è lineare, e non per vezzo: la Cina sta al 17% e la
mediana sotto lo 0,2%. Su scala lineare il mondo sarebbe tutto rosso con tre
macchie verdi e non si leggerebbe niente. Una radice apre il basso della scala.

I rapporti usano rosso-grigio-blu invece di rosso-verde, perché l'indifferenza
non è una via di mezzo fra amicizia e ostilità: è un'altra cosa, e merita un
colore che non sia una sfumatura.

Il planisfero sta in testa al cruscotto (colorato su «dove si spara», che è
quello che si vuole sapere per primo) e ha una pagina sua, `/mappa`, con le
altre letture e le due code — i cinque messi peggio e i cinque messi meglio,
perché una mappa dice *dove* e una lista dice *quanto*, e servono tutte e due.

## Le prove

Una mappa sbagliata non dà errore: disegna, e sembra plausibile finché qualcuno
non nota che il Giappone sta in Atlantico. Le prove controllano la geografia con
affermazioni che un bambino saprebbe verificare — l'Islanda a nord dell'Italia,
il Brasile a ovest del Sudafrica, la Nuova Zelanda a est dell'Australia — ed è
il livello giusto: se salta una di queste, è saltata la proiezione.

**Ne hanno già colta una.** Il primo controllo diceva che la Nuova Zelanda stava
a ovest dell'Australia. Non era la mappa: era il mio modo di calcolare il centro
di un paese, la media di tutti i suoi punti. Le Chatham stanno oltre
l'antimeridiano, e quella media piazzava la Nuova Zelanda in mezzo al Pacifico.
Si guarda il pezzo più grande.

C'è anche la prova che nessun poligono si spalmi da un capo all'altro della
mappa — il modo tipico in cui l'antimeridiano rovina un planisfero. Non succede,
perché ogni anello è un sottotracciato a sé.

## Il peso

La pagina con la mappa pesa 168 KB, che Apache comprime a **54 KB** sul filo —
i tracciati SVG si comprimono benissimo. Per una pagina che mostra il mondo
intero è un prezzo onesto.
