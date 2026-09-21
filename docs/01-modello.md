# 01 — Il modello

## Entità

Nazione (~190, tutte simulate) · Potenza giocabile (~14) · Poltrona · Giocatore
(persistente, sopravvive alla poltrona) · Attore non statale · **Evento in volo**
· Rapporto · Operazione · Trattato/Coalizione · Crisi · Agenda.

Le sette poltrone, valide per qualunque regime: **Capo · Capo di Gabinetto ·
Esteri · Difesa · Intelligence · Sicurezza interna · Economia · Informazione.**

## Variabili di nazione

Tre strati: il valore vero (server), la stima del governo, la stima di ogni
osservatore esterno. **Le stime non si materializzano**: si compongono a runtime
dai rapporti, con decadimento in funzione dell'età del dato.

Strutturali: popolazione, PIL, settori, risorse, Valore Strategico, maturità
istituzionale, ideologia, tipo di regime.

Di stato, ogni tick:

| Variabile | Scala | Origine |
|---|---|---|
| Influenza Totale | % mondiale. Great Power >5%, Major Power 3-5% | SP |
| Etica | Selfless, Honest, Cooperative, Wary, Corrupt, Treacherous | SP |
| Ambizione | Reserved, Active, Assertive, Forceful, Aggressive, Ruthless | SP |
| Qualità della Vita | 10 livelli: beni **e** diritti **e** assenza di paura | SP |
| Stato di Polizia | Free, Mostly Free, Restricted, Closed | SP |
| Net Peace | Righteous, Just Peace, Uneasy Peace, Low Conflict, Limited War, Total War | SP |
| Legittimita' | 0-100 (la "government popularity" di Crawford) | BoP |
| Clamore Sociale | % di vulnerabilita' a disordini e attentati | SP |
| Ansie (6) | economia, interferenza straniera, governo, militare, nucleare, rango | SP |
| Fame (5) | quanto cerca aiuti nei 5 domini | SP |
| Insurrezione | rapporto di forze governo/ribelli | BoP |
| Dipendenza (4) | energia, cibo, finanza, tecnologia | nuovo |
| Ambiente informativo | chi controlla la narrazione interna | nuovo |
| Postura cyber | offesa, difesa, capacità di attribuzione | nuovo |

## Matrici (stato corrente + log delle variazioni)

- **CIM**: Harmony of Interest, Friendship, Cooperation, Indifference,
  Competition, Rivalry, Enmity.
- **Obbligo di trattato**, tabella di Crawford: 0 / 16 / 32 / 64 / 96 / 128.
- **Sfera di influenza** (DontMess) 1-15, +8 se in gioco c'e' un intervento.
- **Commercio**: volume, composizione, **sostituibilità** (la variabile che
  decide se una sanzione morde o è teatro).
- **Copertura di intelligence** per disciplina.
- **Reti di agenti**, incluse le poltrone dei gabinetti giocabili.

## Variabili di poltrona

Etica, Ambizione (stesse scale delle nazioni), LER, Popolarita', **Potere di
gabinetto e Ranking** (da CyberJudas), **Integrità** 0-128 per giocatore,
Agenda privata, Lealta'/compromissione.

## L'evento in volo

    dominio        SOC | ECO | INT | MIL | NUC | INFO
    mandante       chi l'ha ordinato (vero)
    esecutore      chi lo esegue (può essere un proxy)
    bersaglio      nazione / attore / persona
    maturazione    il tick in cui si realizza
    impronta       quanto rumore fa mentre è in volo
    copertura      investimento in occultamento
    reversibilità fin dove si può ancora fermare
    entità        magnitudine -> alimenta il calcolo di Hurt
    stato          in volo | scoperto | fermato | realizzato | depistaggio

Il divario fra decisione e realizzazione è lo spazio in cui vive il gioco.

## L'ordine del tick

| # | Fase | Cosa fa |
|---|---|---|
| 00 | Chiusura | gli ordini diventano eventi in volo |
| 01 | Contro-azioni | si risolvono le risposte a eventi in maturazione |
| 02 | Maturazione | INFO -> ECO -> interna -> INT -> MIL -> NUC |
| 03 | Economia | produzione, commercio, bilanci, 3 pressioni, consumo pro capite |
| 04 | Società | QdV, 6 ansie, clamore, legittimita' |
| 05 | Sicurezza interna | insurrezioni, cambi di esecutivo |
| 06 | Relazioni | CIM, allineamenti, Integrità, sfere |
| 07 | Conflitto | guerre in corso |
| 08 | Intelligence | scoperta, intercettazione, generazione rapporti |
| 09 | Stampa | il feed pubblico, gli scandali |
| 10 | Gabinetto | potere, ranking, dimissioni, elezioni |
| 11 | Globali e scadenze | World Peace Level, Nastiness, default d'ufficio |

L'informazione matura per prima perché cambia il contesto in cui tutto il resto
viene giudicato. L'intelligence sta dopo il conflitto perché i rapporti
descrivono il mondo com'era alla fine del tick.

## Formule

**Potenza militare** — media geometrica di uomini ed equipaggiamento: se hai 100
soldati e 2 equipaggiamenti, un soldato in più non ti da' nulla, un'arma si'.
*Adottata da BoP.*

**Insurrezione** — soglie sul rapporto di forze: >512 pace, 512-32 terrorismo,
32-2 guerriglia, 2-1 guerra civile, <1 vittoria dei ribelli. Attrito: ciascuno
toglie all'altro un quarto della propria forza. Armi ai ribelli valgono doppio.
*Adottata, soglie da ritarare sul tick settimanale.*

**Economia** — tre pressioni (consumi, investimenti, militare) si contendono il
PIL; sotto il ~12% di investimenti la crescita diventa negativa. **Aggiunte**:
saldo commerciale e shock di dipendenza.

**Legittimita'** — `legittimita(t-1) + delta consumo pro capite - aspettativa +
controllo narrativo +/- eventi`. **Cambio rispetto a Crawford**: la sua
aspettativa era una costante globale (-3, tarata a playtest); da noi e'
**specifica per paese e mobile**, perché la gente si aspetta cio' che ha avuto
di recente.

**Integrità** — cala in proporzione all'obbligo di trattato ogni volta che un
cliente cade (difesa nucleare = 128 = azzeramento), risale lentamente.

**Proiezione di forza** — contiguita', oppure truppe in un paese confinante che
ti riceve, altrimenti un pavimento di forze rapide.

**Oltraggio** — di Crawford, **moltiplicato per l'attribuzione**. Buttato via
tutto il modulo di anticipazione: da noi l'anticipazione la fanno i giocatori.
Resta il calcolo del **prestigio a rischio**, mostrato a entrambe le parti a
ogni gradino.

## La scala di escalation

0 azione coperta negabile · 1 nota riservata · 2 protesta pubblica · 3 misure
economiche mirate · 4 sede multilaterale · 5 embargo multinazionale e richiamo
ambasciatori · 6 allerta militare · 7 dimostrazione di forza · 8 uso limitato
della forza · 9 guerra aperta. Il nucleare ha scala separata.

Dal gradino 6 in su ogni scalata porta una probabilità di **incidente**,
funzione del livello e della Nastiness globale.
