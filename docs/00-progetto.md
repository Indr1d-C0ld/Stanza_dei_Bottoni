# 00 — Il progetto

## Le fonti

Tre documenti studiati integralmente, tutti e tre alla base del design:

- **Manuale di Shadow President** (D.C. True, 1993) — il glossario è di fatto lo
  schema del database: sei scale ordinali per nazione, 26 verbi, 5 canali di
  aiuto, 29 filtri di mappa, 4 scenari controfattuali.
- **Manuale di CyberJudas** (D.C. True / Merit, 1996) — tre modalita'
  (Presidential Simulator, Cabinet Wars, CyberJudas Gambit), sei consiglieri
  modellati con le stesse statistiche delle nazioni, l'Event Horizon Vortex,
  il menu delle Crisis Action Options, il ciclo investigativo.
- **Balance of Power, the Book** (Chris Crawford, 1986) — gli algoritmi di
  insurrezione, colpo di stato, finlandizzazione e crisi, più il capitolo sui
  fattori esclusi, che per noi è una lista della spesa.

## La tesi

Dal Forward di Brad Stock, che è la tesi anche nostra:

> Senza etica la strategia è cieca; senza strategia l'etica è ingenua.

E da Crawford, il principio che governa ogni scelta di modellazione:

> I fatti sono transitori, i processi sono le verità durevoli.

Il World Factbook ci da' le **condizioni iniziali**. Non ci da' un modello.

## Le decisioni fondanti

1. **Mondo contemporaneo**, data di divergenza dichiarata, **leader e ministri
   fittizi**. Gli Stati sono reali, le persone no.
2. **Un solo mondo persistente**. ~190 nazioni simulate, ~14 giocabili.
3. Ogni potenza giocabile è retta da un **gabinetto di 7 poltrone**; le
   poltrone vacanti le riempie l'IA, e un'IA può essere reclutata da un
   servizio straniero come un umano.
4. La meccanica portante è l'**evento in volo**: ogni azione esiste nel
   database prima di realizzarsi, e la domanda del gioco è chi lo vede, quando,
   e con quanta precisione.
5. L'equazione che tiene insieme tutto:

       Oltraggio = Hurt x (affinita + obbligo) x sfera x valore x avventurismo x ATTRIBUZIONE

   Il fattore attribuzione è l'aggiunta nostra rispetto a Crawford. Un'azione
   non attribuita non genera crisi, qualunque danno faccia. Da qui discende che
   la guerra ibrida viene prima della guerra, e che l'intelligence non è un
   lusso informativo ma l'arma che trasforma un danno subito in un costo
   politico per chi l'ha inflitto.
6. **Il tradimento non si sorteggia**: agende private più offerte di
   reclutamento. La defezione è una scelta con un prezzo.
7. **Perdere la poltrona non è game over**: esilio, opposizione, carcere,
   rientro. Chi va in esilio può essere reclutato altrove e si porta dietro
   quel che sapeva.
8. **Tick = 2 ore reali = 1 settimana di gioco**, 12 fasi, RNG deterministico
   con seme salvato: un tick è rieseguibile e riproducibile.

## Cosa NON e'

Non è un sistema predittivo. I parametri sono in parte fabbricati (come la
*Maturity* di Crawford, che lui stesso confessa di aver inventato); i processi
sono generalizzati mentre le crisi reali sono individuali; manca la contingenza.
Un gioco si tara per essere interessante, un simulatore per avere ragione, e
sono due tarature diverse: per questo esistono due profili di calibrazione.

Vedi `05-dati-scenari.md` per il metodo di validazione.
