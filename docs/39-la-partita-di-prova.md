# 39 — La partita di prova (ottobre 2026)

## Come si è giocata

Dopo i blocchi sul realismo (`docs/30`-`docs/38`) mancava la prova che conta per
il lato giocatore: sedersi a una poltrona e giocare. L'ho fatta all'Intelligence
iraniana, come in `docs/29`, ma **senza toccare il mondo vivo**:

- un MariaDB a parte, in un container legato solo a 127.0.0.1, con dentro il
  dump del mondo appena riavviato;
- una copia del codice servita dal server di sviluppo di PHP, con una sua
  configurazione e la posta solo nel registro;
- un giocatore di prova iscritto dal sito come chiunque altro (le iscrizioni
  sono aperte da una leva dell'arbitro del 27/09), con l'indirizzo confermato
  dal collegamento finito nel registro della posta.

Tredici settimane di gioco, un tick alla volta.

**Le mosse.** Le agende segrete dicevano di tenere Israele fuori dal club delle
potenze e di farne cadere il governo.

| mossa | esito |
|---|---|
| destabilizzare Israele (40%, occultamento 70%) | firmata dall'apparato, arrivata a segno il 30/03; ma Israele l'ha attribuita all'Iran il 02/03: lo scandalo è nostro |
| sabotaggio contro Israele (50%, occultamento 80%) | firmata, arrivata a segno il 02/02 |
| colpo di Stato contro Israele | l'apparato ha negato la firma |
| armare gli insorti nello Yemen | l'apparato ha negato la firma |
| comprare l'Intelligence israeliana (denaro) | rifiutata |
| comprare l'Intelligence russa (denaro e appoggio) | rifiutata |

La firma negata due volte non è un difetto. L'apparato firma con una
probabilità che dipende da quanto la mossa è coerente con la linea del governo:
circa il 75% per il colpo contro Israele, fra il 45% e il 60% per le armi agli
yemeniti. Due no di fila sono sfortuna.

## Quel che il giocatore non vedeva

**1. Le offerte fatte.** La scrivania mostrava le offerte *ricevute*, non quelle
*fatte*. Un'offerta spariva dopo l'invio, e un rifiuto era indistinguibile da un
silenzio: l'apparato israeliano aveva risposto di no al primo giro, e la base
dati lo sapeva. Adesso c'è «Le offerte fatte», con il destinatario e l'esito:
sul tavolo fino a una data, accettata, rifiutata («da loro la proposta resta
agli atti: è una prova»), denunciata in pubblico, scaduta
(`Reclutamento::offerteFatte`).

**2. Che cosa ha fatto un'operazione.** Il registro diceva «arrivato a segno» e
basta. Chi ha ordinato un sabotaggio ne conosce l'esito, anche se il mondo no.
Adesso il registro lo dice: «Ha tolto lo 0,24% del prodotto e l'1,5%
dell'equipaggiamento militare»; «Ha tolto 2,7 punti di legittimità al governo e
ha acceso la piazza». I coefficienti stanno in un posto solo
(`Fase02Maturazione::EFFETTI_COPERTE`), lo stesso che il motore applica, perché
due copie dello stesso numero divergono.

**3. L'intensità vera.** Un ordine al 50% partiva al 61%: la competenza del
servizio corregge l'intensità, ed è giusto, ma il registro mostrava solo quella
ordinata. Adesso dice anche «eseguita al 61%: conta la competenza del servizio».

**4. Due ritocchi di testo.** «Ora tocca a lui» valeva per chiunque sedesse
nella poltrona; adesso «la decisione è sua». E un «e'» con l'apostrofo era
scappato nel testo per il giocatore.

**Una cosa che non ho cambiato.** Il cursore dell'occultamento parte da zero:
chi lancia un'operazione coperta senza toccarlo la firma col proprio nome.
Può essere voluto, è una scelta di disegno.

## Quel che il motore sbaglia, e che resta per il prossimo blocco

Nella cronaca, all'undicesima settimana: «Iran scivola verso guerra civile». Non è un caso
di quel mondo. In tutti e sei i semi che ho provato gli insorti iraniani passano
dal 10% della forza dello Stato al 55% in tredici settimane, e l'Iran è in guerra
civile entro un anno. Lo stesso succede all'Afghanistan e all'Iraq; a nessun
altro dei 14 paesi che partono da un conflitto minore.

**Il dato.** UCDP/PRIO, conflitti interni del 1990-2024:

| | mondo vero | modello |
|---|---|---|
| un conflitto minore diventa guerra l'anno dopo | 8,8% | 21%, sempre gli stessi tre |
| nei paesi petroliferi | 8,5% | — |
| negli altri | 8,8% | — |
| un conflitto si spegne l'anno dopo | 15-19% | ~21% |
| un nuovo conflitto, 2-5 anni dopo la fine del precedente | 17,9% l'anno (×7,3 a parità di stabilità) | non previsto |
| ancora in conflitto dopo 15 anni | 59% | 52-67% (misura annuale) |

**La causa.** La crescita degli insorti usa i coefficienti di Fearon (2010), che
stimano la probabilità che una guerra civile *cominci*, come velocità di
*crescita* di un'insurrezione già accesa. Il petrolio (×3) e la stabilità
politica bassa (×4-8) si moltiplicano, e per l'Iran, l'Iraq e l'Afghanistan il
«terreno di guerra» vale 16-19 contro 1-4 degli altri: la crescita è
deterministica e supera sempre la risposta del governo. Nel dato il petrolio
non accelera l'escalation affatto.

**Perché non l'ho corretto qui.** Le correzioni giuste sono tre:
- togliere il petrolio dalla crescita;
- una «fortuna dei ribelli» annua che renda l'escalation un evento e non un
  destino;
- la trappola del conflitto per le ricadute.

Le ho scritte e provate, ma toccano insieme escalation, spegnimento, ricadute e
persistenza. Con le prime due la persistenza scende al 36-58%; con la trappola
le ricadute salgono al 31-46% l'anno, contro il 17,9% vero. Vanno tarate insieme
su quei quattro numeri, e non in fondo a una partita di prova col tick vivo
che arriva fra venti minuti. Il lavoro è salvato fuori dal codice vivo, ed è il
prossimo blocco.

**E una questione di misura.** Il cruscotto conta i conflitti nell'ultima
settimana della corsa; l'UCDP li conta per anno. Un conflitto vivo ma calmo
proprio quella settimana risulta spento. Sulla misura annuale la persistenza del
modello pubblicato è 52-67%, quella dell'ultima settimana 48-64%.

---

## Le misure

Le prove sono 523, tre in più: l'esito delle offerte fatte nel ciclo del giocatore
(`tests/12`), il racconto degli effetti nel registro (`tests/15`). Il motore non
cambia, quindi il realismo è quello di `docs/38`.

## Serve un riavvio?

No: cambiano solo la scrivania e il registro. L'ambiente di prova (container,
server, giocatore) è stato smontato.
