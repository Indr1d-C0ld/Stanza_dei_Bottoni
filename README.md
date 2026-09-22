# Stanza dei Bottoni

Un **browsergame geopolitico multigiocatore a mondo persistente**: centottantanove
paesi che vanno avanti per conto loro, un tick ogni due ore, e una decina di
gabinetti in cui si possono sedere delle persone.

Non si comanda un paese. Si occupa **una poltrona** in un governo di otto, e da
lì si cerca di spostare il mondo — sapendo che le cose che contano richiedono la
firma di un collega, che niente succede nell'istante in cui lo si ordina, e che
quasi tutto quello che si fa può essere scoperto.

> Chris Crawford diceva che *Balance of Power* si vinceva non facendo niente di
> stupido, e che la parte difficile era riconoscere in tempo quale mossa fosse
> stupida. Questo gioco è costruito intorno a quella frase.

---

## Da dove viene

Tre opere, e nessuna di queste è nostra.

**Shadow President** (D.C. True, 1993) e il suo seguito spirituale **CyberJudas**
(D.C. True / Merit, 1996): simulatori geopolitici per MS-DOS in cui si guidava la
politica estera degli Stati Uniti su dati reali. Da lì vengono l'impianto — un
mondo intero descritto da indici, un gabinetto che si può ascoltare o ignorare —
e da CyberJudas l'idea che qualcuno al tavolo lavori per qualcun altro.

**Balance of Power** (Chris Crawford, 1985): da qui viene il *modello di
processo*, ed è la parte che conta. L'equazione dell'oltraggio, la scala delle
crisi, la tavola degli obblighi fra alleati, l'integrità come misura della
parola data. Crawford ha pubblicato le sue formule e le sue riflessioni sugli
errori che aveva commesso; questo progetto le ha rilette e reimplementate da
capo.

**Questo non è un rifacimento** di nessuna delle tre. È un'opera originale che
ne traduce le meccaniche in un gioco di un altro genere — asincrono,
multigiocatore, persistente — e in un'altra lingua. Non è affiliata né
autorizzata dagli autori o dai detentori dei diritti delle opere citate.

I dati di partenza vengono dal **CIA World Factbook** (opera del governo
statunitense, di pubblico dominio) e da **Natural Earth** (pubblico dominio) per
i confini della mappa.

---

## Che cosa c'è dentro

### Il mondo

Centottantanove nazioni sovrane, con popolazione, prodotto, crescita,
alfabetizzazione, maturità istituzionale, forze armate e postura nucleare
derivate dal Factbook. Trecentoundici coppie di confini. Un tick è una
settimana di gioco e due ore vere.

Ogni tick attraversa **dodici fasi** in ordine fisso, e ciascuna fa una cosa
sola: chiusura degli ordini, contro-azioni, maturazione degli eventi, economia,
società, sicurezza interna, relazioni, conflitti, intelligence, stampa,
politica di gabinetto, scadenze globali.

Il mondo gira anche senza giocatori. Anzi: gira **prima** che ci siano
giocatori, ed è così che è stato tarato.

### L'evento in volo

Nessuna azione è immediata. Ogni cosa che qualcuno ordina esiste nel mondo
**prima** di produrre effetti: parte, viaggia, matura qualche giro dopo. In
quella finestra può essere scoperta, e può essere fermata.

È il meccanismo su cui poggia tutto il resto, perché rende l'intelligence una
necessità invece che un ornamento.

### Quattro livelli di conoscenza

Di ogni evento si può sapere:

1. che è successo qualcosa
2. di che genere, e in che regione
3. chi l'ha subìto
4. **chi l'ha ordinato**

Il quarto è un ordine di grandezza più difficile degli altri tre, e i primi tre
non si saltano. La conoscenza è monotona: non si dimentica.

Da qui l'equazione che governa la diplomazia:

```
Oltraggio = danno × (affinità + obbligo) × sfera × valore × avventurismo × ATTRIBUZIONE
```

**Un'operazione che nessuno riesce ad attribuirti non ti costa niente.** È la
riga più importante del progetto: si può colpire e restare puliti, e si può far
ricadere la colpa su un terzo — col rischio che il falso venga smontato.

### L'intelligence

Sei discipline (fonti aperte, umana, segnali, immagini, cibernetica,
finanziaria) moltiplicate per la presenza che si è costruita in ciascun paese.
Nessuno sorveglia tutti: è un vincolo, ed è ciò che rende l'intelligence una
scelta invece che una barra da riempire.

I messaggi privati fra giocatori **non sono sicuri**. Il canale che si sceglie
decide quanta gente legge, e nessun canale è sicuro davvero. Un servizio che
ascolta abbastanza da vicino non solo legge: se si è preparato in anticipo, può
**riscrivere** un messaggio prima che arrivi, e il destinatario leggerà una cosa
che il mittente non ha mai detto.

La sola difesa strutturale è che *chi ha scritto sa cosa ha scritto*.

### Le crisi

Quando si può **dimostrare** chi ha colpito, lo si può contestare apertamente.
Da lì parte una scala di nove gradini, dalla nota riservata alla guerra aperta.
A ogni mossa si sale o si cede — non c'è una terza scelta, e chi non risponde in
tempo ha ceduto.

Cedere costa la faccia, e la faccia il mondo la misura. Salire costa di più a
ogni gradino, e dal quinto in su ogni passo può sfuggire di mano da solo. Fra
due potenze nucleari il nono gradino è praticamente irraggiungibile: la
deterrenza non è una regola scritta a parte, è il risultato dei numeri.

Prima di ogni mossa il gioco dice a entrambe le parti quanto costerebbe cedere
adesso. È l'unica informazione che trasforma la scala in una decisione invece
che in un binario.

### Il commercio

Cinque settori — energia, cibo, tecnologia, finanza, manifattura — e un grafo di
gravità che decide chi compra da chi. Da lì escono la **dipendenza** (quanta
parte del fabbisogno passa da un fornitore) e la **sostituibilità** (quanto in
fretta se ne trova un altro), che insieme decidono se una sanzione morde o è
teatro.

Un embargo non toglie punti a caso: chiude un rubinetto, e il danno lo calcola
il grafo **per tutti e due**. Chi lo impone smette di essere pagato.

| | costa a loro | costa a chi lo impone |
|---|---|---|
| Russia contro Germania | 4,17 % di crescita | 0,34 % |
| Stati Uniti contro Corea del Nord | 2,86 % | 0,00 % |
| Bolivia contro Giappone | 0,00 % | 0,00 % |

L'ultima riga è la regola di Crawford — un embargo da soli è teatro — ottenuta
per derivazione invece che per decreto.

### Il gabinetto, e il tradimento

Le poltrone sono otto per paese e ciascuna comanda un dominio. Le cose che
contano vogliono una **seconda firma**: è così che un gabinetto è un gabinetto e
non un uomo solo al comando.

Ogni giocatore riceve **due agende private** che nessun altro conosce, non
coincidono con l'interesse nazionale e a volte lo contraddicono. E qualcuno,
prima o poi, riceverà una proposta riservata da una potenza straniera.

Il traditore **non si sorteggia: matura**. Se ci sarà un Giuda al tavolo, sarà
perché qualcuno ha avuto un movente e qualcun altro ha saputo vederlo.

### L'epoca, e il giorno in cui si scopre tutto

Un'epoca dura qualche centinaio di giri. Quando finisce si conta — interesse
nazionale, agende portate a casa, prezzo pagato — ma soprattutto **si rivela**:
ogni operazione coperta col suo vero mandante, ogni talpa col suo padrone, ogni
lettera riscritta accanto al suo originale, ogni crisi con quel che c'era in
palio.

Per un'epoca intera il gioco è fatto di cose non dette. È quando si dicono tutte
insieme che si capisce che partita si stava giocando.

---

## Il simulatore autonomo

Lo stesso motore gira **senza nessun giocatore**, su un secondo profilo di
taratura. Il profilo `gioco` è tarato perché succedano cose; il profilo
`osservazione` è tarato contro i tassi storici, anche a costo di decenni in cui
non succede niente.

```
php bin/simula.php        quindici anni di mondo in sette secondi
```

Serve a due cose: a collaudare il profilo di gioco, e a guardare un mondo
geopolitico che evolve da solo.

I riferimenti storici presi da Crawford — circa dieci cambi di esecutivo
irregolari l'anno nel mondo, una quindicina di rivoluzioni in quindici anni —
sono la banda entro cui il motore deve restare, e una prova automatica lo
verifica.

---

## I numeri devono essere credibili

Un modello può avere tutte le meccaniche al posto giusto e dichiarare numeri da
fantascienza. Questo li dichiarava: il planisfero annunciava **64.710.856 morti**
per un anno di guerra fra Francia e Cina, quando la seconda guerra mondiale ne
fece settanta milioni in sei anni e su tutti i fronti del pianeta. Il modello
toglieva centocinquantamila uomini dai ruoli e poi ne dichiarava morti
duecentoventidue volte tanto, perché i caduti si ottenevano moltiplicando
l'attrito per un sessanta che non aveva nessuna unità di misura dietro.

Duecentonove prove automatiche non se ne erano accorte, perché verificavano
tutte delle **meccaniche** — che la guerra cominciasse, che finisse, che le
garanzie scattassero — e nessuna verificava delle **grandezze**.

```bash
php bin/realismo.php --anni=15
```

Lo strumento fa girare il mondo a vuoto e confronta undici grandezze con la
fascia in cui il mondo vero le tiene, ciascuna con la fonte accanto: ONU per la
popolazione, Banca Mondiale per il prodotto, SIPRI per la spesa militare, IISS
per gli effettivi, UCDP/PRIO per i morti di guerra, Crawford per i cambi di
governo. Le fasce stanno in `App\Simulazione\Realismo::FASCE`, in un posto
solo, e le legge anche la prova automatica: due tabelle che divergono sarebbero
peggio di nessuna tabella.

**Una distinzione che sembra pedanteria e non lo è.** Un *livello* — il prodotto
mondiale, gli effettivi sotto le armi — si giudica al seme, perché dopo quindici
anni di crescita non è più confrontabile col dato di oggi: rimproverare al 2040
di non somigliare al 2024 non è una misura, è un errore di categoria. Un *tasso*
o un *rapporto* si giudica invece sulla corsa, perché è lì che vive il
comportamento del motore — e un motore può partire giusto e andare alla deriva.

Andava alla deriva. Cercando altri numeri irreali sono emerse tre pompe
nell'economia, tutte con lo stesso vizio: il motore puntava a un bersaglio
uguale per tutti, diverso da dove il mondo parte davvero, e la differenza
diventava un premio permanente. Il prodotto mondiale cresceva del 4,0% l'anno
contro il 3% storico; l'onere militare si dimezzava, dal 2,3% all'1,0%, mentre
il riferimento SIPRI è il 2,5%.

Poi è arrivata una seconda lezione, che vale quanto la prima. I cambi irregolari
di governo erano a ~13 l'anno, e il bersaglio naturale sembravano i «~10
storici» di Crawford. Ma quei dieci coincidono con gli anni Sessanta e Settanta
quasi alla cifra — 103 colpi di Stato riusciti negli anni '60, 95 negli anni '70
— perché Crawford li ricava dal *World Handbook of Political and Social
Indicators*, che copre il 1948-77. Dal 2000 il mondo ne fa 2,2 l'anno, negli
anni Venti circa 3,8.

Il nostro seme è del 2024-25 e il calendario comincia il 5 gennaio 2026: un
mondo del 2026 con dodici colpi di Stato l'anno non è il 2026, è il 1968. Una
fonte non basta che sia seria — deve parlare del mondo che si sta simulando.

Sotto c'era un difetto strutturale: **nessuno dei due profili di taratura
toccava il rischio di colpo di Stato**. Entrambi ereditavano lo stesso valore, e
il profilo che si dichiara «tarato contro i tassi storici» non era mai stato
tarato su nessun tasso.

Le due invarianti che mancavano, e che in retrospettiva sono ovvie:

> I morti non possono essere più degli uomini che il fronte ha tolto dai ruoli.
>
> Un riferimento deve parlare del mondo che si sta simulando, non solo essere
> autorevole.

Il racconto completo, con le misure e i due tentativi sbagliati prima di quello
giusto, è in `docs/26-i-numeri-realistici.md`.

---

## Che cosa non è

**Non è una previsione.** Nessuna delle cifre che il modello produce dice
qualcosa sul mondo vero. I tassi di partenza vengono da dati reali; tutto quel
che succede dopo è il prodotto di un modello dichiaratamente approssimativo,
tarato per raccontare bene invece che per avere ragione.

**Non è un'opinione politica.** Le ideologie sono etichette, le affinità sono
numeri in una matrice, e il modello non pensa che qualcuno abbia ragione.

**Non è un manuale.** Le meccaniche coperte — operazioni, falsi, reclutamenti,
manomissione delle comunicazioni — sono una traduzione ludica di cose che
esistono, ridotte a poche variabili. Chi cerca qui come si fa davvero una di
quelle cose non troverà niente di utile, ed è voluto.

La taratura è piena di numeri marcati `[FABBRICATO]`: sono ordini di grandezza
plausibili scelti perché il gioco funzionasse, e stanno tutti in
`calibrazione/`, in chiaro, uno accanto all'altro. Non sono stime.

---

## Le carte in tavola

Il progetto documenta i propri difetti, e in `docs/` ce ne sono parecchi
raccontati per esteso: un generatore casuale che spingeva il mondo all'ingiù per
giorni senza che nessuno se ne accorgesse, l'etica derivata dalle istituzioni
che rendeva le democrazie mature incapaci di azione coperta, tre casi di una
fase che scriveva e un'altra che sovrascriveva.

**`docs/24-audit-della-simulazione.md`** è un audit sistematico fatto con misure
e non con riletture. Dice quel che è risultato sano — determinismo, persistenza,
salute numerica su quindici anni — e i quattordici punti in cui il modello
promette una cosa e ne fa un'altra: tre dimensioni che il motore legge e nessuno
scrive, un gradino della tavola di Crawford irraggiungibile, quattro verbi su
diciotto che l'apparato non sceglie mai, quindici manopole di taratura scollegate.

Sono difetti noti e scritti, non nascosti.

---

## Come si mette in piedi

Serve **PHP 8.2+** (sviluppato su 8.4), **MariaDB 10.6+** o MySQL 8, e un
server web. Nessuna dipendenza: niente Composer, niente framework, niente
pacchetti da scaricare. L'autoloader è quaranta righe.

```bash
# 1. database, utente, schema, seme, primo tick
sudo bash deploy/00-avvio.sh

# 2. pubblicazione sotto Apache (sotto-percorso del vhost esistente)
sudo bash deploy/01-apache.sh

# 3. il mondo va avanti da solo
crontab -e
  0 */2 * * * cd /percorso/del/progetto && php bin/tick.php --silenzioso >> storage/logs/tick.log 2>&1
  */5  * * * * cd /percorso/del/progetto && php bin/posta.php  >> storage/logs/posta.log 2>&1
  17 4 * * *   cd /percorso/del/progetto && bash deploy/salvataggio.sh >> storage/logs/salvataggio.log 2>&1
```

La configurazione con le credenziali vive **fuori dal docroot**, in
`/etc/stanzadeibottoni/config.php`; in `config/config.example.php` c'è la
struttura senza valori.

### Gli strumenti

```
php bin/tick.php              un passo del mondo
php bin/simula.php            quindici anni a vuoto, con le statistiche
php bin/diagnostica.php       la salute del modello
php bin/prova.php             le prove automatiche (169, cinque secondi)
php bin/migra.php             applica le migrazioni non ancora applicate
php bin/epoca.php             apre e chiude le epoche, e conta
php bin/costruisci_mappa.php  scarica i confini e li converte in tracciati SVG
php bin/importa_factbook.php  rigenera il seme dal World Factbook
```

### Che cosa manca in questa copia

Il **World Factbook** non è ridistribuito qui: è materiale di terzi e pesa
tredici megabyte. Il seme già derivato — `db/seed/nazioni.csv`, centottantanove
righe di indici — **è incluso**, quindi il gioco parte lo stesso. Chi vuole
rigenerarlo scarica il Factbook e lancia `bin/importa_factbook.php`.

Non ci sono account, partite in corso, né le impostazioni della nostra
installazione: questa è la copia **factory default**.

---

## La lingua

Tutto è in italiano: le tabelle, le colonne, le classi, i metodi, i commenti,
la documentazione, l'interfaccia. Non è un vezzo — è più facile ragionare su un
modello quando i nomi sono nella lingua in cui ci si pensa, e questo progetto è
fatto di modelli.

---

## Licenza

**GNU General Public License v3.0** — vedi `LICENSE`.

I dati di partenza sono di pubblico dominio (CIA World Factbook, Natural Earth).
I numeri di taratura marcati `[FABBRICATO]` sono invenzioni di questo progetto e
seguono la stessa licenza del codice.

Opera amatoriale, senza scopo di lucro, non affiliata né autorizzata dagli
autori o dai detentori dei diritti di *Shadow President*, *CyberJudas* e
*Balance of Power*. Su richiesta degli aventi diritto, qualunque parte che li
riguardi viene rimossa.
