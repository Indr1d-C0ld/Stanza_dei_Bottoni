# 22 — La porta, il banco, e le due cose che mancavano

Tre pezzi di infrastruttura che non cambiano il gioco ma decidono se il gioco
può esistere: chi entra, chi comanda, e cosa succede quando qualcosa si rompe.

## La porta: l'indirizzo si conferma

Prima chiunque trovasse l'indirizzo poteva registrarsi con una casella
inventata e prendersi una poltrona. In un gioco dove le poltrone sono poche e
si tengono per mesi è un problema pratico prima che di sicurezza: **un posto
occupato da un indirizzo che non esiste è un posto perso per tutti.**

Ora la registrazione crea un accesso non confermato, manda un collegamento che
vale 48 ore, e finché non si apre quel collegamento non si entra. Il relay è
l'account Brevo già in uso sugli altri progetti di questa macchina.

### La coda, e perché non basta mandare la mail

`Postino` sa parlare SMTP — STARTTLS o TLS implicito, AUTH LOGIN, testo
semplice, senza una libreria, perché per un messaggio di verifica tirarsene
dietro una sarebbe sproporzionato. Ma il Postino non sa cosa fare quando il
relay non risponde, e quello lo sa `Posta`:

- ogni messaggio entra in coda **prima** di essere tentato: se il processo
  muore a metà, il messaggio resta;
- se non riesce si riprova con attesa crescente — 1, 5, 15, 60, 180, 360
  minuti — e poi si rinuncia **dicendolo**, con il motivo agli atti;
- il tetto giornaliero del fornitore si rispetta contando gli invii riusciti
  nelle ultime ventiquattr'ore, non sperando che bastino.

Senza la coda, un messaggio di verifica perduto lascia qualcuno fuori dal gioco
senza che nessuno se ne accorga. `bin/posta.php` gira nel cron ogni cinque
minuti.

### I modi di non entrare non sono equivalenti

`entra()` tornava un booleano. Adesso torna una coppia, perché dire «credenziali
errate» a chi ha solo dimenticato di aprire la posta è il modo più sicuro di far
andare via qualcuno che aveva diritto di entrare:

| situazione | cosa si dice |
|---|---|
| parola d'ordine sbagliata | «Indirizzo o parola d'ordine non corrispondono» |
| indirizzo non confermato | «Devi prima confermare il tuo indirizzo» + modulo per riaverlo |
| sospeso | «Accesso sospeso fino al…», con la data |
| chiuso | «Questo accesso è stato chiuso da un arbitro» |

La prima riga si dice **prima** di verificare la parola d'ordine, le altre tre
solo dopo: da lì in poi sappiamo che è lui, e possiamo dirgli la verità senza
che l'informazione serva a un estraneo.

## Chi può entrare

La registrazione era aperta a chiunque trovasse l'indirizzo. Per una partita fra
qualche decina di persone che si tengono il posto per mesi non è il modello
giusto: non è un forum dove chi arriva legge e se ne va, è un tavolo dove **un
posto occupato male rovina la partita a tutti gli altri**.

Tre modi, e si sceglie dalle leve: `aperte`, `invito`, `chiuse`. In modalità
invito serve un codice, che l'arbitro fa dal banco e consegna a chi vuole.

Un codice vale **una volta sola** — non esiste il codice buono per tutti, che
sarebbe una registrazione aperta scritta peggio — dura trenta giorni, e si può
revocare finché nessuno l'ha usato. L'alfabeto non ha `0`, `O`, `1`, `I`, `L`:
un codice si detta al telefono, e sbagliarlo significa dover scrivere a qualcuno
per chiederne un altro.

### La leva che non muoveva niente

`Inviti::modo()` leggeva la configurazione in `/etc`, mentre la leva
dell'arbitro finisce in `sdb_leva` e da lì nella **calibrazione**. Due posti
diversi che non si parlavano: l'arbitro spostava la leva, la leva si spostava
davvero, e non succedeva niente — senza un errore, senza un avviso.

Se ne è accorta una prova che mi ero scritto apposta per dimostrare che
funzionasse. Adesso `modo()` legge dalla calibrazione, con il file come valore
di partenza, esattamente come ogni altra leva.

*(In coda a quel difetto ne è venuto fuori un altro: `muoviLeva()` accettava
solo numeri, e le registrazioni sono una parola fra tre. Costringerla in un
numero sarebbe stata una cifratura inutile da tenere a mente.)*

## La guida

Chi arrivava atterrava su un elenco di poltrone e doveva indovinare cosa fosse
tutto quanto. Ora c'è `/guida`: dieci minuti, dieci sezioni, e dice le cose che
non si deducono guardando l'interfaccia.

Che il mondo va avanti anche senza di te. Che occupi **una** poltrona di otto e
le cose che contano vogliono una seconda firma. Che niente succede subito, e che
in quella finestra si può essere scoperti e fermati. Che sapere non basta:
**un'operazione che nessuno riesce ad attribuirti non ti costa niente**, ed è da
lì che viene metà del gioco. Che i tuoi messaggi non sono sicuri e possono
essere *riscritti*, non solo letti. Come funziona la scala delle crisi e perché
cedere costa. Che un embargo ferisce chi lo impugna. Che hai due agende che
nessuno conosce. E che un giorno si scopre tutto.

## Il banco dell'arbitro

Sotto `/arbitrio`, e invisibile a chi non ne ha diritto — chi non è arbitro
riceve «Non trovato», non un «vietato» che confermerebbe l'esistenza della
pagina.

**Gli arrivi.** Chi si è registrato, chi non ha confermato, da quale indirizzo
di rete. Ogni registrazione manda un avviso agli arbitri.

**Gli account.** Dare per buono un indirizzo quando la posta non arriva,
riscrivere, sospendere a tempo, chiudere, riaprire, cambiare l'indirizzo,
liberare una poltrona, promuovere al banco. Gli atti distruttivi su sé stessi
sono rifiutati: se sbagli resti fuori e non c'è nessuno a riaprirti.

**Le leve.** È il potere più grosso che esista nel programma: cambiare la
taratura del mondo mentre il mondo gira. I file in `calibrazione/` restano la
verità di partenza e stanno in git; le leve sono scostamenti dichiarati in
`sdb_leva`, con il valore di prima accanto, revocabili uno per uno. Si impongono
prima che i file vengano letti — sia nel tick sia nel web, altrimenti il
giocatore vedrebbe numeri diversi da quelli con cui gira il mondo.

Due guardie. Solo le chiavi in un elenco esplicito si possono muovere: tentare
`db.password` viene rifiutato. E un salto di oltre dieci volte viene respinto —
*«Se è voluto, arrivaci in due passi: così non ci si arriva per uno zero di
troppo»*.

**Il registro.** Ogni atto finisce in `sdb_atto_arbitro` con chi, cosa, quando e
da che valore a che valore. In un gioco dove l'arbitro può cambiare le regole a
partita in corso, **un atto non tracciato è indistinguibile da un favore.**

## Il recupero della parola d'ordine

Mancava del tutto: chi la dimenticava restava fuori per sempre, e nemmeno
l'arbitro poteva farci niente — può cambiare un indirizzo, non una parola. Con
il sito pubblico è un buco che si nota al primo giocatore vero.

Il gettone è **separato** da quello di verifica, e non per pignoleria: sono due
cose diverse, e confonderle vorrebbe dire che un collegamento di conferma vale
anche a cambiare le credenziali. Dura un'ora — non quarantotto come la conferma,
perché qui in gioco c'è l'accesso — e una volta usato si brucia.

La risposta è **identica** che l'indirizzo esista o no: dire il contrario
trasformerebbe il modulo in un modo comodo per scoprire chi gioca. E non si può
chiedere in continuazione: un minuto fra una richiesta e l'altra basta a non
farne un modo di riempire la casella di qualcuno.

## Gli avvisi di gioco

La posta c'era e serviva solo a registrarsi. Ma questo è un mondo che gira ogni
due ore e i giocatori non stanno collegati ad aspettarlo: una crisi scade in tre
tick — sei ore — e se nessuno te lo dice hai ceduto senza saperlo.

Il rischio opposto è la molestia: un gioco che scrive a ogni giro smette di
essere letto in tre giorni. Da cui tre regole.

**Si scrive solo se c'è qualcosa da dire**, e il messaggio le dice tutte insieme
— un riepilogo, non un avviso per evento.

**Non più di uno ogni dodici tick**, cioè uno ogni ventiquattr'ore vere.

**Chi non li vuole li spegne**, dalla propria scrivania, e restano spenti.

Quattro cose vengono dette: una crisi che aspetta la tua mossa (con quanti giri
restano, e col tono che cambia quando la pazienza è finita), gli ordini che
aspettano la tua seconda firma, le proposte riservate ricevute, e — la più
importante — la poltrona che sta per passare all'apparato, detta **due tick
prima** che succeda.

Gli avvisi stanno fuori dalle dodici fasi, e apposta: le fasi sono il mondo, e
il mondo non sa che esiste la posta elettronica. Un avviso che non parte non fa
fallire un tick: il mondo è già stato scritto, e perderlo per un problema di
posta sarebbe sproporzionato.

*Le prove su questo hanno sbagliato bersaglio alla prima stesura: guardavano il
trasporto finto, ma `manda()` **accoda** e non spedisce — parte col prossimo
giro di `bin/posta.php`. Nove prove fallivano con il codice giusto. Adesso
guardano la coda.*

## Il salvataggio

`deploy/salvataggio.sh`, ogni notte alle 4:17. Salva tre cose, e servono tutte e
tre per rimettere in piedi il gioco: il database (dove vive il mondo), la
configurazione in `/etc/stanzadeibottoni` (che non sta in git perché ha le
credenziali, e quindi non si recupera da nessun'altra parte), e il codice col
seme.

E poi **controlla quel che ha salvato**, perché un salvataggio che nessuno ha
mai verificato non è un salvataggio, è una cartella che cresce:

- l'archivio si apre (`gzip -t`);
- c'è la riga di chiusura di `mariadb-dump` — se manca, il dump si è interrotto
  a metà e ricaricarlo darebbe un mondo mutilato senza dirlo;
- il numero di `CREATE TABLE` corrisponde alle tabelle vive;
- dentro c'è davvero uno stato del mondo.

Provato su un dump troncato a mano: lo rifiuta. Sulla copia buona: passa.

*Limite dichiarato:* non si prova a **ricaricare** il dump in un database di
servizio, perché l'utente del gioco ha i permessi solo sul proprio schema e non
può crearne un altro. Il controllo è strutturale, non un recupero vero.

La cartella è a 700 e i file a 600: contengono le credenziali.

## Le prove automatiche

La cartella `tests/` era vuota da sempre. L'impalcatura sta in cinquanta righe
senza dipendenze — servono tre cose, dire cosa si prova, confrontare due valori,
contare quel che non torna — e tirarsi dietro un framework sarebbe la stessa
sproporzione del client SMTP.

**83 prove in cinque secondi**, divise in cinque file. Quasi tutte
corrispondono a un errore vero già successo:

- **il caso** — `frazione()` tornava valori in [0, 0.5) e quindi ogni scossa
  casuale spingeva il mondo all'ingiù. Ci sono voluti giorni per accorgersene,
  perché un mondo che peggiora piano sembra plausibile;
- **il commercio** — l'alfabetizzazione divisa per cento (tecnologia e
  manifattura senza un solo esportatore), i due produttori dello stesso settore
  che non si vendevano niente, la Francia che esportava petrolio, l'energia che
  si spalmava su quattordici fornitori;
- **le crisi** — che la scala si usi tutta ma il fondo resti raro, e che fra due
  potenze nucleari il nono gradino non si raggiunga;
- **la taratura** — che le leve si impongano e si tolgano, e che il mondo a
  vuoto resti nella banda storica dopo dodici anni simulati;
- **la posta** — che quel che non parte resti, che si riprovi, che si rinunci
  dicendolo, e che lo smistamento non tocchi quel che non deve toccare.

Le prove sul database girano dentro una transazione annullata; quelle sulla
posta usano un trasporto finto — la configurazione vera manda posta davvero, e
una prova non deve poter svegliare la casella di nessuno.

### La prima cosa che hanno trovato

Appena scritte, hanno subito bocciato il commercio: il Belgio esportava zero.
Era una regressione vera, introdotta dalla modifica sulla concentrazione per
settore e mai riverificata.

Indagando, però, non era un difetto da correggere: è un **limite del modello**.
Il Belgio è tredicesimo fra i fornitori di tecnologia della Francia e il taglio
è a dieci. Allargare i tagli non lo fa entrare — resta fuori per un posto — e
intanto annacqua le leve che contano: i Paesi Bassi passavano dal 23 al 10 per
cento di export sul PIL. Il modello non ha un concetto di specializzazione né di
riesportazione, e non si fabbrica allargando una classifica.

Così il limite è stato **scritto** — in `Commercio.php`, nel documento sul
commercio, e nella prova stessa, che ora verifica quel che il modello può
davvero sostenere e dice in un commento perché non prova il Belgio. Una prova
piegata per passare è peggio di nessuna prova; una prova che dichiara il confine
del modello è una prova onesta.
