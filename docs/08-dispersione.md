# 08 — L'indagine sulla dispersione

Il problema: su cinque semi diversi, il mondo produceva 6,7 – 7,0 cambi di
esecutivo all'anno. Una forbice del 4%. Un insieme che non si apre non è un
insieme, e tutta la validazione statistica del simulatore autonomo si regge
sugli insiemi.

## Misurare invece di ipotizzare

`bin/diagnostica.php` fa girare N semi e misura quattro cose:

1. lo scarto del totale fra le corse, **confrontato con lo scarto di Poisson**
   che ci si aspetterebbe da eventi indipendenti;
2. l'indice di Jaccard fra gli insiemi di paesi toccati (1,00 = sempre
   esattamente gli stessi);
3. quanti paesi producono un esito *identico* in ogni corsa;
4. come si apre nel tempo la forbice delle traiettorie di legittimità.

Il primo referto:

    scarto osservato 0,99 · atteso da Poisson 9,60 · rapporto 0,10
    Jaccard 0,93 · paesi con esito identico: 34 su 45

Il mondo era **dieci volte più prevedibile del caso puro**. Ma — e qui la
diagnosi si è ribaltata — le traiettorie di legittimità *divergevano* eccome:
Haiti con scarto 14,6 punti a fine corsa, il Sudan 10,6. Il rumore c'era e si
propagava. Semplicemente non arrivava a cambiare gli esiti.

## Il metronomo

Guardando gli **intervalli fra un cambio e il successivo** invece dei totali:

    seme 1000  Haiti  cambi ai tick 104,249,393,517,641  intervalli 145,144,124,124
    seme 1411  Haiti  cambi ai tick 104,247,410,533,656  intervalli 143,163,123,123,124

Un orologio. E il primo colpo cade al tick 104 in entrambe le corse — cioè
esattamente alla scadenza del raffreddamento di due anni.

Il modello era un **oscillatore a rilassamento a periodo deterministico**: ogni
nuovo governo ripartiva da una legittimità identica (56,0), il raffreddamento
era una costante, la discesa era guidata da parametri strutturali. Il caso
spostava di qualche settimana *quando* accadeva, mai *se* accadeva.

## Le tre cause, e le tre correzioni

**1. Il livello di riposo era 50,0 per ogni paese del mondo, per sempre.** Un
ancoraggio deterministico enorme. Correzione: ogni nazione ha una **deriva
politica**, un processo a ritorno alla media che vaga di qualche punto — è
qualità di governo, coesione, fortuna: le cose che nessun dato cattura e che
pure decidono se un paese fragile regge o cede.

**2. Ogni nuovo governo era il precedente con la legittimità ricaricata.**
Correzione: un cambio al vertice **riscrive la deriva politica**. Alcuni
consolidano per un decennio, altri cadono in sei mesi, e questo non è
deducibile da nulla. Anche il raffreddamento è diventato variabile, o restava
lui il periodo dell'oscillatore.

**3. La soglia era un cancello.** Sopra 22 il rischio era zero, sotto era quasi
certezza: la sorte di ogni paese era una proprietà della sua struttura.
Correzione: **un rischio continuo**, una curva logistica sulla legittimità. Nel
mondo vero i governi cadono anche con consensi discreti, solo più di rado — ed è
quel "più di rado" a fare la differenza fra una storia e un orologio.

## Il referto finale

Dieci semi, quindici anni:

| Misura | Prima | Dopo | Obiettivo |
|---|---|---|---|
| Scarto osservato / Poisson | 0,10 | **0,62** | ≥ 0,5 |
| Jaccard fra insiemi di paesi | 0,93 | **0,31** | ≤ 0,8 |
| Paesi con esito identico | 34 su 45 | **0 su 179** | pochi |
| Cambi irregolari all'anno | 6,9 | **9,5** | ~10 |

E la forbice si apre dove deve: l'Italia ha uno scarto di 3,8 punti di
legittimità fra le corse, il Sudan 10,8, il Venezuela 12,7, il Botswana 12,8.
Gli Stati solidi restano sé stessi in ogni storia possibile; quelli fragili no.
È esattamente ciò che si vuole da un modello di questo genere.

## Una nota sul confronto storico

Il confronto andava separato. Crawford riporta, per trent'anni e ~150 paesi,
**238 cambi irregolari** e **1645 regolari**. Il nostro modello produce 9,5
irregolari all'anno contro ~10 attesi — buono — e 16,5 cambi totali contro ~79
attesi, perché **le elezioni a calendario non sono ancora modellate**. Non è un
difetto di taratura: è una funzione che manca, e arriva con la fase 10.

## Cosa resta da guardare

- ~~La legittimità media mondiale deriva ancora verso il basso (52 → 44 in
  quindici anni)~~ **RISOLTO, ed era un artefatto**: un difetto nel generatore
  di casualità rendeva `rumore()` sempre negativo. Vedi `docs/09-eventi.md`.
  Tutte le tarature riportate qui sotto sono state rifatte dopo la correzione.
- Il Sudan tocca lo zero in alcune corse: il pavimento della legittimità andrebbe
  reso un po' più morbido.
- `ampiezza_deriva` e `rischio_massimo_anno` sono entrambi dichiaratamente
  fabbricati e sono ora i due parametri che governano la varianza del mondo. Vanno
  citati per primi in qualunque discussione sui risultati.
