# 40 — I casi estremi (ottobre 2026)

## Il difetto

Sotto il planisfero, «I casi estremi» doveva mostrare le due code della lettura
scelta: dove va peggio e dove va meglio. Non lo faceva in nessuna delle cinque
letture pubbliche.

La pagina ordinava i paesi per **colore**, confrontando il codice esadecimale
come testo. Così l'ordine lo decide il canale rosso, e sulla scala rosso-ocra-verde
il rosso più acceso non sta nel rosso: sta nell'ocra di mezzo (`#c9a23f` contro
`#c0443c`). In fondo all'elenco, al posto dei peggiori, finivano i paesi medi.

Nel mondo vivo, settimana 52:

| lettura | la pagina dava per peggiori | i peggiori veri |
|---|---|---|
| legittimità | Vietnam, Ghana, Malaysia, Serbia, Mauritius (49-50 su 100) | Siria 19, Yemen 20, Sud Sudan 26, Centrafrica 28, Haiti 29 |
| influenza | Turchia, Regno Unito, Francia, Brasile, Indonesia, più le Isole Marshall | Isole Marshall, Micronesia, Kiribati, Tonga, Dominica |
| tensione | Messico, Centrafrica, Ruanda, Togo, Turchia (conflitto aperto) | sei paesi in guerra totale |
| benessere | Sudafrica, Tonga, Turkmenistan, Ucraina, Vietnam (5 su 10) | ventitré paesi a 1 su 10 |
| commercio | fra i «migliori» Stati Uniti, India e Cina, gli unici sotto pressione | Cina, India, Stati Uniti |

La lettura dei rapporti, sulla scala rosso-grigio-blu, era quasi giusta per
caso: lì il rosso cala in modo monotono dal rosso al blu.

## La correzione

**Si ordina sul valore.** `Planisfero::colori` restituisce accanto al colore la
«misura», orientata in modo che più alto voglia dire meglio:
- la legittimità, l'influenza e il benessere così come sono;
- la tensione e la pressione commerciale col segno cambiato;
- l'affinità nei rapporti.

Le code le calcola `Planisfero::code`, non più la pagina, e così si possono
provare.

**Gli ex aequo.** Su certe letture i valori sono pochi (sei gradi di tensione,
dieci di benessere), e il quinto posto è spesso condiviso. Sceglierne cinque in
ordine alfabetico direbbe una cosa falsa: il Camerun non sta peggio del Ciad.
Allora:
- se la coda è tutta un gruppo alla pari, la pagina lo dice e nomina tutti,
  finché sono al massimo venticinque: «23 paesi a pari valore, 1 su 10: …».
  Oltre li conta soltanto: «157 paesi a pari valore, pace»;
- altrimenti mostra i primi cinque e, sotto, chi sta allo stesso valore
  dell'ultimo: il sesto paese in guerra totale non sparisce più.

**I peggiori non arrivano al valore migliore.** Centottantasei paesi senza
sanzioni non sono i peggiori di nessuno: per il commercio la coda peggiore è di
tre paesi, perché sotto pressione ce ne sono tre. Un paese con cui non abbiamo
rapporti non entra nelle code dei rapporti: non è né amico né nemico.

**L'influenza dei piccoli.** Con due decimali ventitré paesi stavano a «0,00%», e
altri ottantaquattro sotto lo 0,1% avevano una cifra sola. Sotto lo 0,1% ora le
cifre significative sono due: Tonga 0,00056%, le Isole Marshall 0,00020%.

## Le misure

Le prove sono 535, dodici in più, tutte in `tests/06`: i casi estremi, gli ex
aequo, le sanzioni, i rapporti senza rapporto, l'influenza dei piccoli.

## Serve un riavvio?

No. Cambiano solo la pagina del planisfero e il modo di scrivere l'influenza; il
motore non si tocca.
