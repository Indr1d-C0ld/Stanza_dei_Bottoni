# 20 — La delega e l'epoca

Due problemi che nessun gioco da tavolo ha e che ogni gioco persistente deve
risolvere: cosa succede quando un giocatore non c'è, e cosa succede quando la
partita non finisce mai.

## Chi siede davvero a una poltrona

La domanda sembra banale e non lo è. Una poltrona può avere un titolare che non
si collega da tre giorni: sulla carta è presidiata, nei fatti è vuota. E in un
mondo che gira a tick anche di notte, una poltrona vuota che blocca una crisi,
una controfirma o una risposta diplomatica ferma il mondo **per tutti gli
altri** — il modo più sicuro di uccidere una partita persistente.

Il motore chiedeva `giocatore_id IS NOT NULL`, che è la risposta sbagliata.
Adesso la risposta sta in un posto solo, `Delega::sqlPresidiata()`, perché
averla in un posto solo è ciò che impedisce al motore e all'interfaccia di
finire in disaccordo su chi comanda.

## Due rimedi che fanno cose diverse

### La delega, che si sceglie

Affidi la tua poltrona a un altro giocatore mentre non ci sei. Lui si siede
lì, agisce in tuo nome, e la firma resta **sua** accanto alla tua negli atti.

Può fare quello che avresti fatto tu. Può anche fare quello che non avresti mai
fatto: aprire una crisi, chiudere una linea, ordinare un'operazione, scrivere
a un tuo nemico. Non c'è nessun limite tecnico a quello che il delegato può
fare, ed è voluto — in un gioco che parla di tradimento, la delega è il gesto
di fiducia più caro che si possa fare, e deve costare quanto costa.

### L'assenza, che non si sceglie

Dopo **sei tick** senza che nessuno tocchi la poltrona, l'apparato riprende il
paese in mano e decide al posto del titolare: risponde alle crisi, risponde
alle proposte di linea, fa quello che farebbe in un gabinetto che non ha mai
avuto un titolare.

Non persegue le agende private dell'assente: quelle sono sue.

Al rientro, il titolare trova il conto in `sdb_assenza_fatto`, e lo trova una
volta sola. In una partita di prova, sette giri di silenzio hanno prodotto
questo:

> t101 · l'apparato **ha alzato una crisi** — gradino 2 con Russia
> t103 · l'apparato **ha ceduto in una crisi** — gradino 4 con Russia

Che è insieme un servizio e un rimprovero, ed è giusto che sia tutti e due.

## L'epoca

Una partita persistente senza fine non è una partita: è un acquario. Serve un
momento in cui si conta, e soprattutto un momento in cui **si scopre**.

Un'epoca si apre con una durata, e si chiude da sola quando il tempo finisce —
la fase 11 ci pensa a ogni tick. Non serve un arbitro sveglio al momento
giusto: il momento in cui si conta deve arrivare comunque, o non arriverebbe
mai.

### Il conto

Tre gruppi di voci che **non tirano nella stessa direzione**, ed è il punto.

*L'interesse nazionale* — influenza guadagnata nel mondo, benessere di chi ci
vive, legittimità del governo, parola mantenuta. Sono differenze fra l'inizio e
la fine dell'epoca, ciascuna con il suo moltiplicatore, perché le scale native
sono incomparabili (l'influenza è una percentuale di mondo, la legittimità va su
100, l'integrità su 128).

*Le agende private* — venticinque punti ciascuna se portata a casa, otto in meno
se fallita.

*Il conto da pagare* — le guerre in cui ci si è infilati (ventidue punti
ciascuna, ed è la voce più pesante del gioco), le crisi cedute, le crisi in cui
hanno ceduto loro, e le decisioni che sono state prese senza di te.

Si può servire benissimo il proprio paese e fallire ogni agenda, o portarne a
casa tre lasciando il paese a pezzi. Il totale non dice chi ha giocato meglio:
dice **che partita ha giocato**. Da una chiusura vera:

```
Randolph — Intelligence di Francia: +15,63
   influenza guadagnata nel mondo        -0,10
   benessere di chi ci vive              -0,37
   legittimità del governo               -9,42
   parola mantenuta nel mondo            +0,52
   agende private portate a casa        +25,00
```

Agenda privata portata a casa, legittimità del paese crollata di quasi venti
punti. Il numero in fondo è positivo, e non è una vittoria: è un ritratto.

### La rivelazione

Vale più del punteggio. Alla chiusura diventa pubblico tutto insieme:

- **ogni operazione coperta** col suo vero mandante — comprese quelle che
  portavano la bandiera di qualcun altro, con accanto di chi era la bandiera;
- **ogni talpa** col suo padrone, e da quando;
- **ogni lettera riscritta**, con il testo che è arrivato e quello che era stato
  scritto, uno sotto l'altro. Le lettere soppresse compaiono con quel che
  dicevano e non è mai arrivato;
- **ogni crisi**, con quanta faccia c'era in palio per ciascuna delle due parti
  a ogni gradino.

Per un'epoca intera il gioco è fatto di cose non dette. Quando si dicono tutte
insieme si capisce finalmente che partita si stava giocando — ed è questo, non
la classifica, il momento per cui vale la pena arrivare in fondo.

## Le tabelle e gli strumenti

`sdb_epoca` (numero, titolo, inizio, durata, stato), `sdb_punteggio` (la
scomposizione in JSON accanto al totale, così il conto resta leggibile e
contestabile), `sdb_rivelazione` (quel che si scopre, per genere).
Su `sdb_poltrona` sono comparse `delega_a`, `delega_dal_tick` e
`ultimo_tick_attivo`.

`bin/epoca.php` apre, chiude e racconta. `bin/migra.php` applica le migrazioni
e tiene il conto di quali sono già passate — esiste perché fino alla 0011 si
applicavano a mano, e alla 0011 un pezzo di file è stato saltato perché
cominciava con delle righe di commento: le colonne nuove non sono mai nate e la
pagina dei messaggi rispondeva 500 senza che il motivo fosse evidente.
