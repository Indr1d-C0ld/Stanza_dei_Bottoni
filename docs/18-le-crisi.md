# 18 — Le crisi

È l'ultimo pezzo che mancava di *Balance of Power*, ed è quello che il gioco del
1985 metteva al centro: non le guerre, ma le scale che ci portano. Crawford
diceva che il suo gioco si vinceva non facendo niente di stupido, e che la cosa
difficile era riconoscere in tempo quale mossa fosse stupida.

## Che cos'è una crisi qui

Una crisi si apre quando un paese **contesta a un altro una cosa che ha fatto**,
e la contesta a viso aperto. Non è una guerra e non è una protesta: è una
partita a due dove ognuno può salire di un gradino o scendere dal treno, e dove
scendere costa — perché si scende davanti a tutti.

Il requisito d'ingresso è severo ed è la ragione per cui l'intelligence conta:

> Per aprire una crisi serve **conoscenza di livello 4** sull'evento, cioè
> l'attribuzione. Sapere che è successo qualcosa non basta. Sapere dove non
> basta. Contestare senza prove significa solo farsi smentire.

Questo lega le crisi al resto del modello: sono lo sbocco politico del lavoro dei
servizi. Un'operazione coperta che nessuno riesce ad attribuire non produce
nessuna crisi, e quindi non costa niente a chi l'ha ordinata — che è la stessa
equazione dell'oltraggio, vista dall'altro lato.

## I nove gradini

Stanno in `calibrazione/base.php`, sotto `crisi.gradini`:

| # | gradino |
|---|---|
| 1 | nota riservata |
| 2 | protesta pubblica |
| 3 | sede multilaterale |
| 4 | misure economiche mirate |
| 5 | embargo e richiamo degli ambasciatori |
| 6 | allerta militare |
| 7 | dimostrazione di forza |
| 8 | uso limitato della forza |
| 9 | **guerra aperta** |

Il terzo e il quarto erano invertiti nella prima stesura — le sanzioni sono un
passo più duro del portare la cosa in sede multilaterale, non il contrario.

Chi ha la mossa può **salire** o **cedere**. Non c'è una terza opzione, e non c'è
modo di lasciare la crisi ferma: chi non risponde entro `crisi.pazienza_tick`
tick **ha ceduto**. Una decisione non presa è comunque una decisione.

## La posta

Prima di ogni mossa il gioco dice a entrambe le parti quanto costerebbe cedere
adesso. La formula sta in `Crisi::poste()`:

```
posta = 26 · (danno / 127) · (0,5 + |affinità con il paese conteso| / 127)
           · (1 + gradino · 0,38) · (0,4 + prestigio_oggetto / 300)
```

Tre cose da leggerci dentro:

- **cresce col gradino** (`crescita_posta` = 0,38): più si sale, più diventa caro
  tornare indietro. È il motore che tiene in piedi le crisi vere;
- **è asimmetrica**: chi tiene di più al paese conteso ha più da perdere a
  mollare, e quindi mollerà per ultimo;
- **si paga in integrità**, che è la scala 0-128 di Crawford e si ricostruisce a
  5 punti l'anno. Una resa al sesto gradino costa una trentina di punti, cioè sei
  anni di gioco per tornare come prima.

## Come decide la macchina

In `Fase01ControAzioni::gestisciCrisi()`, ed è l'eccesso di oltraggio di
Crawford più tre termini che il gioco del 1985 non aveva bisogno di scrivere
perché li lasciava al giocatore:

```php
eccesso = (miaPosta − suaPosta)            // chi ci tiene di più
        + miaPosta · peso_impegno          // quel che ho già impegnato
        + max(0, 9 − gradino) · 1,2        // il bluff, che si sgonfia salendo
        + rumore
        − reluttanza
        − paura                            // quel che rischio a salire ancora
```

La **paura** è la parte nuova ed è in due pezzi distinti, e la distinzione
conta:

```php
paura  = (gradino/9)^2,4 · 28 / rapporto_di_forze          // convenzionale
paura += (gradino/9)^4,0 · 45 · min(postura_nucleare) / 7  // nucleare
```

Il primo pezzo si divide per il rapporto di forze — chi ha l'esercito più grande
teme meno — ed è tagliato a due volte in entrambe le direzioni. Il secondo **non
si divide per niente**: contro chi ha la bomba la superiorità convenzionale non
vale nulla, e si sveglia solo in cima alla scala, dove la quarta potenza lo fa
esplodere. È esattamente questo che deve tenere ferme le mani in alto.

### Come si comporta

Quattromila crisi simulate per ciascun tipo di coppia:

| coppia | rottura mediana | arrivo al nono gradino |
|---|---|---|
| due potenze nucleari | gradino 3 | **0,0 %** |
| una sola nucleare | gradino 3 | 0,6 % |
| nessuna nucleare | gradino 3 | 0,8 % |

Tutti e nove i gradini vengono usati — il quinto e il sesto da una crisi su
quattro — ma il nono resta raro, e fra due potenze nucleari praticamente
irraggiungibile. Il che lascia in piedi una sola strada per la catastrofe fra
chi ha la bomba, ed è quella giusta:

## L'incidente

Dal quinto gradino in su, **ogni passo può sfuggire di mano** prima ancora che
l'altro risponda. La probabilità sta in `crisi.incidente_base` e cresce con la
`nastiness` del mondo. Se scatta, la crisi non finisce né con una resa né con
una guerra dichiarata: finisce in stato `incidente`, con le ricadute su entrambi
e nessuno che possa dire di averlo voluto.

Questa è la vera ragione per cui salire è pericoloso anche quando si è
convinti che l'altro cederà.

## Il nono gradino è una guerra vera

Quando si arriva in fondo, `Crisi::fondoScala()` **scrive una riga in
`sdb_guerra`** con chi ha fatto l'ultimo passo come aggressore. Dal tick
successivo è la fase 07 a combatterla con le sue regole, con i garanti chiamati
a rispondere e i morti contati.

Non è un dettaglio di contabilità: finché la crisi si limitava a mettersi in
stato «guerra» senza dirlo al mondo, la scala di escalation era teatro. In una
partita di prova il nono gradino raggiunto davvero ha prodotto 680.000 morti in
due tick — che è quanto costa, e deve costare, non essersi fermati.

## Dove finiscono le conseguenze

C'è una trappola in cui questo progetto è già cascato due volte, e le crisi
l'hanno ritrovata.

Quando a muovere è **un giocatore**, la mossa avviene fra un tick e l'altro: la
penale si scrive nel database, e il tick successivo la rilegge con
`ripristina()`. Funziona.

Quando a muovere è **la macchina**, la mossa avviene dentro la fase 01: se la
penale si scrive nel database, undici fasi dopo `salva()` riscrive sopra tutto
partendo dal mondo che tiene in memoria — e la memoria non sa niente di quella
penale. Risultato: la Russia cedeva e non perdeva un solo punto di faccia.

Per questo `Crisi` ha un metodo `collega(Mondo $mondo)`: se il mondo c'è, le
conseguenze si applicano agli oggetti in memoria; se non c'è, si scrivono nel
database. È la stessa regola per ogni conseguenza che il gioco produce dentro un
tick.

## La sfera, e perché serviva una spinta

Il secondo effetto di una resa è che **la sfera d'influenza si sposta**: chi ha
tenuto duro guadagna un gradino sul paese conteso, chi ha mollato ne perde uno.

Solo che la sfera la ricalcola da zero la fase 06, ogni tick, da peso economico
e geografia. Spostarla direttamente non serviva a niente: sei fasi dopo era
tornata com'era. È lo stesso problema dell'**ancora** delle affinità, in un
altro posto.

La migrazione `0010` aggiunge `spinta_sfera` alla relazione: la fase 06 la
somma alla sfera strutturale e la consuma del 12 % l'anno
(`relazioni.consumo_spinta_anno`). Una crisi vinta vale per anni — metà del
vantaggio è svanito dopo sei — ma non per sempre. La storia pesa sulla
struttura, senza cancellarla.

## Quel che la crisi non fa

**Non annulla il fatto.** Se l'operazione contestata è già maturata, cedere non
la fa tornare indietro: si ferma solo quello che è ancora in volo. Una crisi si
gioca sulla faccia e sulle posizioni, non sul passato — il quale, come nel gioco
del 1985, resta.

## Le tabelle

`sdb_crisi` tiene lo stato — le due parti, l'evento contestato, il paese
oggetto, il gradino, le due poste, di chi è la mossa e quando scade la pazienza.
`sdb_crisi_passo` tiene la cronaca: ogni mossa di ogni parte con il gradino
raggiunto. È la cronaca che fa di una crisi una storia che si può raccontare a
partita finita, e che la stampa legge nella fase 09.
