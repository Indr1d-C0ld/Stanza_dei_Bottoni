# 19 — Il falso e la linea

Due cose opposte costruite insieme, perché sono la stessa cosa vista dai due
lati: quanto vale poter riscrivere le parole di qualcuno, e quanto costa
difendersi da chi ci prova.

## Il transito, che prima non c'era

Il primo problema non era di meccanica ma di tempo. Un messaggio partiva e
arrivava nello stesso tick, e la fase 08 lo intercettava **dopo** che il
destinatario lo aveva già letto. Utile per spiare, inutile per manomettere: non
esisteva nessun istante in cui il messaggio fosse in mano a un terzo e non
ancora in mano a chi doveva riceverlo.

Ora c'è un transito:

| canale | quando arriva | lo toccano i segnali? |
|---|---|---|
| aperto | subito — è già pubblico | tutti, per definizione |
| diplomatico | un giro | sì |
| cifrato | un giro | sì, con fatica |
| corriere | due giri | no |
| linea diretta | un giro | no |

## La manomissione non si improvvisa

Non esiste un pulsante «falsifica questo messaggio». Si ordina **prima**
un'operazione contro un corrispondente preciso, e la squadra resta appostata
finché i segnali non le portano qualcosa. Due ragioni, e sono tutte e due
vincoli veri:

- i giocatori non sono collegati nello stesso momento, e un meccanismo che
  richiede di reagire entro due ore non è un meccanismo, è una tassa su chi
  dorme poco;
- i servizi non lavorano così comunque. Una squadra su un canale è una squadra
  dedicata, tarata, e costosa.

Tre modi, in `Falsificazione::MODI`:

- **inserisci** — una frase infilata nel testo autentico. Il resto resta vero,
  ed è esattamente questo che la rende credibile. È il migliore.
- **sostituisci** — il testo intero rimpiazzato con uno preparato prima.
  Potente e rozzo: stona di più, e il controspionaggio se ne accorge quasi due
  volte su cinque.
- **sopprimi** — il messaggio non arriva mai. Nessuno saprà perché, e proprio
  perché non c'è niente da esaminare è il più difficile da scoprire.

### I tre vincoli

**Serve leggere prima di poter scrivere.** L'operazione scatta solo su un
messaggio intercettato a livello *integrale*. Metadati e frammenti non bastano:
non si riscrive quel che non si è capito.

**Serve essere già lì.** Senza presenza piantata nel paese bersaglio,
l'operazione non parte nemmeno.

**Chi ha scritto sa cosa ha scritto.** Il mittente vede sempre il proprio testo
originale, e chi era sul filo — i terzi che hanno intercettato — sente quel che
è stato detto davvero. Basta che le due parti si parlino altrove perché la
frode venga fuori. È la debolezza strutturale di ogni manomissione, ed è qui.

### Perché la lettura integrale è dovuta cambiare

La raccolta di routine affronta tre ostacoli in cascata — metadati, frammento,
testo — e la probabilità di superarli tutti e tre va come il cubo della
copertura. Anche a copertura piena si legge un messaggio per intero meno di una
volta su dieci. Con quei numeri una squadra appostata non sarebbe mai scattata,
e la manomissione sarebbe stata una voce di menu che non fa niente.

Una squadra tarata su un corrispondente preciso, però, non affronta tre
ostacoli separati: concentra i mezzi su un filo solo, e **o entra nel testo o
non ci entra**. Due passi invece di tre, e un moltiplicatore sui mezzi
(`intelligence.concentrazione_mirata` = 2,2). A copertura piena si passa da
circa il 10 % a circa il 30 %.

### Chi può ascoltare

Nel farlo è saltato fuori un errore più vecchio: l'intercettazione dei messaggi
era riservata alle **prime otto potenze per influenza**. Un paese medio che
avesse investito tutto nei segnali non leggeva niente, per sempre. Era il rango
a decidere, non la capacità. Ora ascolta chiunque abbia costruito un servizio, e
le grandi potenze restano nella lista perché ascoltano comunque.

## La linea diretta

Il telefono rosso. Un canale dedicato fra due capitali: arriva in un giro e i
segnali di terzi non lo toccano. È il canale migliore del gioco, e ha tre costi
che nessun altro ha.

**Si apre in due.** Una parte propone, l'altra accetta. Un gabinetto retto
dall'apparato risponde secondo l'affinità (`linee.soglia_affinita` = 20) e non
accetta mai da chi gli sta facendo la guerra. In una partita di prova Berlino
(+115) e Londra (+95) hanno accettato, Mosca (−33, e in guerra) ha rifiutato.

**È pubblica.** Chiunque vede quali capitali si parlano così. Aprirne una è una
dichiarazione di allineamento; chiuderla lo è ancora di più.

**È al sicuro dai segnali, non dalle persone.** Ed è qui che la linea diventa
interessante invece che comoda.

## La talpa legge tutto

Una poltrona reclutata non ha bisogno di essere intercettata: il suo titolare
porta fuori le carte. Da ora, chi ha reclutato il titolare di una poltrona
riceve **a livello integrale** tutta la corrispondenza di quella poltrona — su
ogni canale, corriere e linea diretta compresi, perché nessuna cifratura
protegge da chi ha legittimamente la chiave.

Verificato: un messaggio sulla linea diretta Parigi–Berlino non è stato letto da
nessuno, mentre un cifrato ordinario partito nello stesso giro è finito sotto
gli occhi di Washington e Teheran. E un messaggio sul **corriere** — il canale
che nessun segnale tocca — è arrivato integrale a Parigi, perché chi lo aveva
scritto lavorava per Parigi.

Il canale più sicuro del gioco è anche quello che ripaga di più chi ha saputo
mettere qualcuno dentro. È il motivo per cui esiste.

## Le tabelle

`sdb_manipolazione` tiene le squadre appostate: chi, contro chi, verso chi, con
che modo e con che testo preparato, quanto dura e quanti usi le restano.
`sdb_linea` tiene le linee, con la coppia ordinata in chiave unica perché una
linea è una sola e non ha un verso. Su `sdb_messaggio` sono comparse
`testo_originale`, `falsificato_da`, `soppresso` e `manomissione_sospetta`: il
testo che arriva e quello che era stato scritto vivono uno accanto all'altro,
perché alla chiusura d'epoca vanno mostrati insieme.
