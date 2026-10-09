# 34 — I civili e la pace (ottobre 2026)

## Da dove nasce

`docs/33` aveva lasciato aperte due cose sulla guerra. Il modello metteva un
civile morto per ogni militare in tutte le guerre. E una guerra che finiva non
restituiva niente alla crescita. Tutte e due ora vengono dai dati.

---

## 1. I civili

**Il difetto.** Un civile morto per ogni militare caduto: è la media di Eckhardt
su tre secoli, ripresa dal CICR, e comprende le guerre totali. Nella guerra
russo-ucraina il modello faceva circa 75.000 caduti militari l'anno e altrettanti
civili, 150.000 in tutto. L'UCDP, che nei morti in battaglia conta anche i civili
uccisi nei combattimenti, ne registra 76.000-102.000 l'anno (stima centrale,
2022-2025). In `docs/33` avevo scritto che i due errori si compensavano. Era
sbagliato: il numero dei caduti militari era già quasi giusto, e il rapporto 1:1
gonfiava soltanto il totale.

**Il dato.** UCDP Georeferenced Event Dataset v26.1 (Sundberg e Melander, *Journal
of Peace Research* 50(4), 2013), che registra i morti evento per evento e
distingue i civili dai combattenti. Nelle guerre fra due governi del 1989-2025
(secondo l'UCDP/PRIO Armed Conflict Dataset) i civili morti nei combattimenti
sono **0,09** per ogni combattente. Nella guerra russo-ucraina sono 0,03 dal 2023
in poi e 0,28 nel 2022, l'anno di Mariupol. Lo strumento `bin/misura_civili.php`
rifà il conto leggendo direttamente lo zip del GED, che scompattato pesa 274 MB.

Con 0,09 la guerra russo-ucraina del modello fa circa 82.000 morti l'anno,
dentro la forchetta UCDP. Le uccisioni deliberate di civili, cioè la violenza
unilaterale dell'UCDP, sono un'altra cosa e il modello non le ha.

## 2. Il rimbalzo

**Il difetto.** Una guerra costava fino a 2,3 punti di crescita l'anno (Collier
2003), e quando finiva non restituiva niente.

**Il dato.** Ho incrociato gli episodi di guerra dell'UCDP (oltre mille morti
l'anno) con la crescita del FMI. Le guerre finite nel 1990-2019 e seguite da
almeno cinque anni di pace sono 54, e 49 hanno i dati. Il confronto è con la
mediana mondiale degli stessi anni.

| | sopra la mediana mondiale |
|---|---|
| ultimi tre anni di guerra | −2,0 punti |
| cinque anni dopo, media | +1,9 punti |
| cinque anni dopo, mediana | +0,8 punti |

I −2,0 della guerra coincidono con i 2,3 di Collier che il modello già usa. La
media del dopo è tirata su dal Kuwait (+83% nel 1992) e dalla Bosnia (+62% nel
1996). Le mediane anno per anno vanno da +0,5 a +1,7.

**Il meccanismo.** Quando il costo di una guerra cala, la crescita guadagna l'80%
del calo, e il guadagno si spegne con una costante di quattro anni. Una guerra
civile piena che finisce dà subito circa 1,8 punti, e 1,1 in media sui cinque
anni. Vale solo per le guerre oltre quelle del seme: la ripresa di quelle il FMI
l'ha già messa nelle sue proiezioni, come ci aveva messo la guerra. La nazione
ricorda il costo della guerra al tick prima e il rimbalzo che le resta
(migrazione 0038).

---

## Le misure

Quattro semi per due profili, quindici anni: **21 grandezze su 21 in fascia in
tutti e otto i mondi.** I morti di guerra fra Stati scendono a 20.000-100.000
l'anno per mondo. Le prove sono 497: `tests/19-guerra-e-pace.php` verifica
l'orientamento e la radicalità, i cobelligeranti, il tetto ai ribelli, i civili e
il rimbalzo.

Il mondo vivo non ha bisogno di un riavvio: i due campi nuovi partono da zero, che
è il valore giusto per un mondo senza guerre finite.

## Che cosa resta aperto

- **Il 38% dei rapporti dichiarati**, scritti a mano, finché non c'è una fonte di
  eventi abbastanza densa (POLECAT non lo è: `docs/33`).
- Dall'elenco dei difetti noti del README restano, con le ragioni già scritte nei
  loro documenti: la U rovesciata di Goldstone sotto la sua magnitudine
  (`docs/26` §13), la faziosità che esiste solo per le quattordici potenze
  giocabili, il commercio in cui la taglia assoluta decide troppo, e i conflitti
  del seme che il modello non sa quali debbano durare.
