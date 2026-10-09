# 33 — Gli aspetti rimasti aperti (ottobre 2026)

## Da dove nasce

I documenti 28, 30, 31 e 32 avevano lasciato aperte alcune cose. Questo
documento le riprende una per una. Strada facendo sono saltati fuori due difetti
più vecchi, e uno era grave.

---

## 1. L'orientamento e la radicalità

L'orientamento politico di un paese (−128..+128) faceva due mestieri.

- **Allineamento internazionale.** Lo usano l'attrazione strutturale fra paesi
  (fase 06), la conquista (fase 07) e l'agenda «aprire al blocco avverso».
- **Radicalità del regime.** Lo usano il piccolo premio di legittimità ai governi
  radicali, il centro del rischio di colpo di Stato, la legittimità dopo una
  rivoluzione e l'ambizione.

Per il primo mestiere la misura giusta è il voto all'ONU. Ma su quell'asse il
polo è l'Occidente, e usato anche per il secondo avrebbe fatto degli Stati Uniti
il regime più radicale del mondo. Adesso le due cose sono separate:

- **l'orientamento è l'allineamento**, preso dal punto ideale ONU di
  `db/seed/onu.php`: il paese mediano a 0, gli Stati Uniti a +128, sulla stessa
  scala in giù. Germania +65, India +8, Russia −2, Cina −17, Iran −48. I 45
  orientamenti scritti a mano e la formula del Factbook restano solo per chi
  all'ONU non vota (Taiwan, il Kosovo);
- **la radicalità è la repressione del regime** (`Nazione::radicalita()`), cioè
  la misura V-Dem di `docs/31`: Corea del Nord 0,97, Russia 0,85, Stati Uniti
  0,24.

La repressione adesso si muove, e quindi si salva (migrazione 0036). Di quanto
si muove non l'ho scelto: l'ho misurato sui dati V-Dem, confrontando tre anni
dopo con l'anno prima:

| evento | casi | repressione | censura |
|---|---|---|---|
| colpo di Stato riuscito | i 15 del 2010-2023 (Powell e Thyne) | +0,12 | +0,13 |
| presa del potere armata | Afghanistan 2021, Yemen 2015, Libia 2011 | +0,14 | +0,09 |

Sul secondo rigo i casi sono tre e diversissimi (la Libia apre, l'Afghanistan
chiude), ed è dichiarato.

## 2. La CSI come patto di difesa

ATOP codifica come patto di difesa fra otto paesi la Carta della Comunità degli
Stati Indipendenti (alleanza 4400, Minsk, 22/01/1993). Lì dentro stanno anche
l'Armenia e l'Azerbaigian, che si sono fatti tre guerre, e l'Uzbekistan e
l'Azerbaigian, usciti dal trattato di sicurezza collettiva nel 1999. L'alleanza
ora è esclusa per intero. I legami veri dello spazio post-sovietico restano: la
CSTO, e i trattati bilaterali che ATOP registra a parte (Russia-Uzbekistan 1992
e 2005, Tagikistan-Uzbekistan, Turchia-Azerbaigian). Era il difetto aperto in
`docs/28` §5.

## 3. Taiwan, e i garanti che non combattevano

**Il sintomo.** Un'invasione cinese di Taiwan riusciva sempre, in un anno e
mezzo-due. I giochi di guerra del CSIS (Cancian, Cancian e Heginbotham, *The
First Battle of the Next War*, gennaio 2023) la danno quasi sempre fallita,
perché intervengono gli Stati Uniti e il Giappone.

**La causa** è più larga di Taiwan: nel modello nessun terzo combatteva mai. Un
garante che onorava la parola mandava un quarto del suo arsenale alla prima
settimana, e basta. Nemmeno un alleato della NATO entrava in guerra per un altro.

**Il rimedio**, in tre pezzi:

- **i garanti che onorano diventano cobelligeranti.** Mettono in campo il 30% della
  loro forza, ridotto dalla distanza come quello dell'aggressore, e ne pagano le
  perdite (migrazione 0037). Il 30% è un ordine di grandezza preso dai giochi di
  guerra del CSIS, ed è marcato [FABBRICATO];
- **combatte anche chi ha truppe sul posto e buoni rapporti** (gradino 64 e
  affinità sopra 60). È il «filo d'inciampo» di Schelling (*Arms and Influence*,
  1966): pochi soldati che non fermerebbero nessuno rendono credibile una
  promessa;
- **Stati Uniti-Taiwan, gradino 64.** Non è un patto di difesa, e l'«ambiguità
  strategica» è proprio questo. È un impegno scritto in una legge (Taiwan
  Relations Act, 10/04/1979) più gli addestratori militari autorizzati dal
  Taiwan Enhanced Resilience Act (NDAA 2023).

Adesso un'invasione cinese di Taiwan finisce in armistizio dopo tre-quattro anni,
oppure è ancora in corso dopo cinque, con circa 190.000 morti l'anno.

## 4. I ribelli senza tetto (il difetto grave)

Cercando perché l'Afghanistan «conquistasse» l'Iran è saltato fuori un difetto
più vecchio. Durante una guerra con l'Afghanistan i ribelli iraniani arrivavano a
**quattordici volte** l'esercito iraniano: il reclutamento non aveva nessun tetto
rispetto a quanto un paese può esprimere. Quando poi vincevano, il loro esercito
diventava quello dello Stato, e l'Iran rivoluzionato valeva più della Cina.

- **Il reclutamento ora ha un tetto.** Rallenta man mano che i ribelli si
  avvicinano a una volta e mezza la forza che lo Stato aveva al seme. Nei dati sugli
  attori non statali (Cunningham, Gleditsch e Salehyan 2013) i ribelli alla pari
  o più forti dello Stato sono una piccola minoranza.
- **Chi vince eredita al massimo lo Stato che ha abbattuto**, com'era al seme.

Le vittorie dei ribelli scendono a 0,07-0,6 l'anno (media 0,27), contro le circa
0,4 reali: Libia 2011, Centrafrica 2013, Yemen 2015, Afghanistan 2021, Siria
2024. I colpi di Stato restano 2,5-3,3 l'anno (Powell e Thyne: 2,2 nel
2000-2019, 3,8 negli anni Venti). Il pavimento della fascia dei cambi
irregolari scende quindi da 3 a 2,5. Il 3 valeva finché le rivoluzioni erano
gonfiate da eserciti ribelli senza limite.

## 5. Il riavvio senza poltrone

I gabinetti, e con loro le poltrone, li costruiva la fase 10 al primo tick, e un
mondo appena riavviato ne restava senza fino all'ora pari successiva. Inoltre il
riavvio toglieva a ogni giocatore la sua poltrona. Adesso `bin/avvia_mondo.php`
costruisce subito i gabinetti, con il caso del primo tick, e rimette ciascuno al
suo posto con agende private nuove.

## 6. Provato e scartato: gli eventi di POLECAT

Le affinità del seme spiegano il 62% dei rapporti dichiarati (`docs/32`). Per il
resto serviva una misura dei rapporti correnti, e il candidato era POLECAT
(Halterman et al., successore di ICEWS; Harvard Dataverse, file 2023 e 2024).
Contando solo gli eventi fra governi e forze armate di due Stati diversi, i dati
sono troppo radi e troppo rumorosi. Solo 99 coppie hanno almeno 20 eventi in un
anno e mezzo. Stati Uniti-Regno Unito valgono −0,38 su 20 eventi, India-Pakistan
+0,96 su 14. Cina-Taiwan e Giappone-Corea del Nord non hanno nessun evento.
Sostituirci i rapporti dichiarati avrebbe peggiorato il seme. I file sono stati
cancellati.

---

## Le misure

Quattro semi per due profili, quindici anni: **21 grandezze su 21 in fascia**.
Paesi in conflitto 23-33 (UCDP 2024: 36), gradiente per taglia +21/+28 (UCDP:
circa +25), invasioni due-sei per mondo. Le prove sono 486 e passano tutte.

## Il mondo vivo

L'orientamento, le alleanze senza la Carta della CSI e l'impegno americano per
Taiwan stanno nel seme: per averli nel mondo vivo serve un altro riavvio, che
adesso rimette anche i giocatori al loro posto.

    bash deploy/02-riavvio.sh --profilo=osservazione

## Che cosa resta aperto

- **Un civile per militare** in tutte le guerre. Nella guerra russo-ucraina i
  civili sono molti meno, ma il modello conta anche meno caduti militari delle
  stime indipendenti (circa 75.000 l'anno, contro stime che per le due parti
  insieme vanno da circa 100.000 in su), e i due errori si compensano nel
  totale. Correggerne uno solo peggiorerebbe la cifra che si vede.
- **La pace non ricostruisce.** Una guerra che finisce non dà alcun rimbalzo
  alla crescita.
- **Il 38% dei rapporti dichiarati** resta scritto a mano, finché non c'è una
  fonte di eventi abbastanza densa.
