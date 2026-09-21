# 09 — Gli eventi in volo

Stato: **il mondo agisce.** Fasi 00, 01 e 02 implementate. Da qui in poi le cose
non accadono soltanto: qualcuno le fa, e ci mette del tempo.

## L'entità centrale

`src/Dati/Evento.php`. Un'azione decisa ma non ancora realizzata, con mandante,
esecutore (il proxy, quando ci sarà), bersaglio, intensità, copertura, impronta,
tick di maturazione, danno di base e **attribuzione vera**.

La reversibilità decade con l'avanzare della maturazione — *"gli eventi sono
molto più facili da fermare quando individuati presto"* (manuale di CyberJudas).
Il divario fra la decisione e la sua maturazione è lo spazio in cui vive tutto
il resto.

## Il catalogo

`calibrazione/verbi.php`: diciassette verbi sui circa sessanta del bacino in
`docs/02-verbi.md`. Ognuno porta dominio, maturazione, impronta, attribuzione,
danno, gradino di escalation e **attesa** — quanti tick prima di poter ripetere
lo stesso verbo sullo stesso bersaglio.

L'attesa non è un dettaglio: senza, le grandi potenze si sanzionavano a vicenda
ogni settimana (1216 condanne pubbliche e 241 restrizioni commerciali in un solo
anno) e la crescita mondiale crollava dal 2,5% allo 0,4%.

## La dottrina

Senza giocatori, la fase 00 decide con una macchina semplice: guarda ogni
rapporto, valuta se il partner è ostile o amico, solido o fragile, e pesa i
verbi disponibili. Non deve essere astuta — deve essere **leggibile**, perché
quando il mondo farà qualcosa di strano dovremo poter risalire al perché.

Con i giocatori, questa stessa macchina governerà le nazioni non presidiate: le
176 su 189 che nessuno starà giocando.

## Il perno: l'attribuzione

Nella fase 02, il contraccolpo diplomatico è **moltiplicato per l'attribuzione
vera** dell'evento. Un'azione che nessuno può ricondurre a te non ti costa nulla
in rapporti, per quanto danno faccia.

È una sola moltiplicazione, ed è l'intero progetto: rende la guerra ibrida
conveniente, la guerra aperta l'ultimo gradino, e l'intelligence — che serve
proprio ad alzare l'attribuzione — l'arma che trasforma un danno subito in un
costo politico per chi l'ha inflitto.

Gli eventi spostano anche l'**ancora** delle relazioni, non solo l'affinità del
momento: è così che la storia entra nel modello e non se ne va col tempo.

## Due errori di modello, corretti

**L'etica non è una proprietà delle istituzioni.** Avevo legato la disponibilità
al lavoro sporco alla maturità istituzionale, e il risultato era che le
democrazie mature risultavano *incapaci* di azione coperta: in quindici anni di
mondo, zero operazioni. Storicamente falso, e cancellava metà del gioco.

L'etica è invece, come dice il glossario di Shadow President, *"il modo in cui
il mondo giudica i moventi delle azioni che compi"*: una base strutturale che
gli eventi spostano. Chi lavora nell'ombra se ne fa una fama, e la fama è lenta
a cambiare in meglio. E nella dottrina l'etica **non vieta: prezza**.

**Il colpo di stato non scavalca il modello, lo carica.** Il verbo
`colpo_di_stato` non impone una caduta: abbassa pesantemente legittimità e alza
il clamore, e sarà la fase 05 — più avanti nello stesso tick — a decidere se il
governo cade davvero. Vale la regola d'oro di Crawford: *"non puoi distruggere
un governo che altrimenti reggerebbe"*.

## E un bug vero, nel generatore

Cercando perché la dottrina non sceglieva mai certi verbi, è saltato fuori
questo:

    frazione(): min 0,0000  max 0,5000  media 0,2485   <-- doveva essere 0..1
    rumore():   min -1,0000 max -0,0000 media -0,5030  <-- doveva essere -1..+1

Tredici cifre esadecimali arrivano a 2⁵²−1, non a 2⁵³−1, e io dividevo per 2⁵³.
Per settimane `frazione()` ha restituito valori nella sola metà bassa
dell'intervallo, il che significa che **`rumore()` è sempre stato negativo**:
ogni scossa casuale del mondo era una spinta verso il basso.

Conseguenze scoperte a posteriori:

- la "deriva della legittimità da indagare" annotata in `docs/08` **era questo**:
  52 → 44 in quindici anni non era un artefatto del modello, era il rumore;
- l'estrazione pesata dei verbi era distorta: con pesi 2,9 / 1,5 / 2,0 / 1,8 /
  0,8 / 1,0 usciva 57,6% / 30,4% / 11,9% / 0 / 0 / 0. Ecco perché interi rami
  della dottrina non si vedevano mai;
- tutte le probabilità erano dimezzate, il che ha falsato ogni taratura fatta
  fin qui.

Corretto il divisore, l'estrazione torna a rispettare i pesi entro mezzo punto
percentuale su quattromila tiri, e **tutto il mondo è stato ritarato da capo**.

## Dove siamo, dopo la ritaratura

Otto semi, quindici anni, profilo `osservazione`:

| Misura | Valore | Riferimento |
|---|---|---|
| Cambi irregolari | 9,9 l'anno | ~10 |
| Scarto osservato / Poisson | 0,60 | ≥ 0,5 |
| Jaccard fra insiemi di paesi | 0,48 | ≤ 0,8 |
| Crescita PIL mondiale | 4,0% annuo composto | alto, vedi sotto |
| Eventi conclusi in 15 anni | ~1.800 | — |

E il mondo fa cose riconoscibili: gli Stati Uniti mettono sotto embargo l'Iran e
tramano contro la Corea del Nord, la Cina mette sotto embargo la Francia, l'India
e la Cina si sabotano a vicenda, qualcuno arma dei ribelli. Nessuna riga di
codice nomina quei paesi.

## Quel che non va ancora

- **La crescita mondiale è al 4,0%**, un po' alta: le stime di crescita del
  Factbook sono dell'ultimo anno disponibile e tendono all'ottimismo per i paesi
  in via di sviluppo. Va smorzata, o ancorata a una media di più anni.
- **La legittimità media sale** (54 → 61 in quindici anni). Ora che il rumore è
  simmetrico, la deriva si è invertita: ogni cambio di governo riparte sopra la
  media e il mondo diventa lentamente più contento di sé. Va guardato.
- **Le rivoluzioni sono poche**: 5 in quindici anni contro le ~15 storiche. Con
  legittimità più alta c'è meno malcontento, quindi meno reclutamento
  insurrezionale. `reclutamento_k` è il parametro, ed è fabbricato.
- La difesa passiva della fase 01 resta un **segnaposto** per la fase 08: blocca
  le operazioni coperte con un tiro cieco, senza costruire conoscenza per
  osservatore e per livello. È dichiarato nel codice.
