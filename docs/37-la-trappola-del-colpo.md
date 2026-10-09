# 37 — La trappola del colpo (ottobre 2026)

## Da dove nasce

`docs/36` lasciava tre cose aperte:

1. il 19% dei rapporti dichiarati che i dati non spiegano;
2. le rivalità strategiche ferme al 2020;
3. i colpi di Stato fra 0,15 e 0,25 di democrazia liberale, nella parte alta
   dell'intervallo del dato.

Il terzo ha portato a un meccanismo che mancava. Il primo si è rivelato
diverso da come lo descrivevo. Il secondo è un limite della fonte, e resta.

---

## 1. La trappola del colpo

**Il sintomo.** La fascia 0,15-0,25 era alta, ma la forma del «gobbo» dei regimi
parziali non c'entrava. Spianarlo in un altipiano (sale fino a 0,15, piatto
fino a 0,35) la alzava invece di abbassarla. Guardando i paesi uno per uno il
problema era un altro:

| | rischio nel modello | colpi veri |
|---|---|---|
| Niger | 46 ‰ | 2010, 2023 |
| Iraq | 45 ‰ | nessuno dal 2003 |
| Pakistan | 31 ‰ | nessuno dal 1999 |
| Mali | 28 ‰ | 2012, 2020, 2021 |

Il modello dava all'Iraq e al Pakistan, fragili ma senza colpi da decenni, più
rischio che al Mali, che ne ha avuti tre in nove anni.

**Il dato.** Chi ha appena avuto un colpo di Stato ne ha altri. È la «trappola
del colpo» di LONDREGAN e POOLE (1990), «Poverty, the Coup Trap, and the Seizure
of Executive Power», *World Politics* 42(2). Ho rifatto il conto sul dato di
oggi: Powell e Thyne, regimi parziali (democrazia liberale V-Dem fra 0,05 e
0,55), 2000-2025, regressione di Poisson con la stabilità politica WGI.

| | colpi riusciti ogni mille anni-paese |
|---|---|
| con un colpo riuscito nei dieci anni prima | **38,7** (11 su 284) |
| senza | **10,4** (24 su 2.312) |

A parità di stabilità politica il moltiplicatore è **3,1**, con logaritmo
1,14 ± 0,37 (fra 1,5 e 6,5).

**Il meccanismo.**

- La nazione ricorda il tick del suo ultimo colpo riuscito (`ultimoColpo`,
  migrazione 0039). Al seme viene da Powell e Thyne (`bin/importa_colpi.php`,
  `db/seed/colpi.php`): 22 paesi con un colpo dal 2000, e i più recenti sono il
  Madagascar e la Guinea-Bissau nel 2025, il Niger e il Gabon nel 2023, il
  Burkina Faso nel 2022. Il tick 0 è il 5 gennaio 2026, e un colpo precedente
  ha un tick negativo.
- Per dieci anni dopo un colpo il rischio si moltiplica per 3,1
  (`colpo_di_stato.trappola`).
- Il tetto del rischio scende da 3% a 2,4% l'anno, perché i colpi restino
  quelli veri.

**L'esito**, misurato con la sonda dell'azzardo atteso su due semi:

| | modello | mondo vero |
|---|---|---|
| con un colpo recente | 35-43 ‰ | 38,7 ‰ |
| senza | 8,5-11 ‰ | 10,4 ‰ |
| a stabilità politica 0 | 7 ‰ | 8,7 ‰ |
| a stabilità politica −1 | 15-16,5 ‰ | 15,4 ‰ |
| media dei regimi parziali | 12-13 ‰ | 12,9 ‰ |
| colpi riusciti l'anno | 1,3-1,5 | 1,4 |

Per fascia di democrazia liberale:

| | modello | mondo vero | intervallo al 95% |
|---|---|---|---|
| 0,05-0,15 | 14-17 | 9,2 | 4-18 |
| 0,15-0,25 | 25-26 | 14,5 | 7-28 |
| 0,25-0,40 | 8-10 | 16,2 | 9-28 |
| 0,40-0,55 | 4,5-5,6 | 8,2 | 3-18 |

Tutte le fasce restano nei loro intervalli; la 0,15-0,25 non scende molto. Il
guadagno vero è che dentro ogni fascia il rischio sta adesso sui paesi giusti:
il Sahel prima dell'Iraq.

## 2. I rapporti dichiarati, e una tabella morta

**Le sanzioni.** Ho provato la variabile che `docs/36` diceva mancare. La fonte
non è il Global Sanctions Data Base, che si ottiene solo su richiesta, ma il
catalogo dei programmi di sanzioni di OpenSanctions: 262 programmi, ciascuno con
chi lo emette, contro chi e quali misure. L'ho incrociato con la mappa ufficiale
delle sanzioni dell'Unione Europea.

Il criterio: contano i programmi attivi di Stati Uniti, Unione Europea, Regno
Unito, Svizzera, Australia e Canada che combinano controlli sulle esportazioni
con restrizioni su importazioni, finanza o investimenti. Ne escono i
recepimenti puri dei regimi ONU (la Libia, la Somalia) e i programmi contro un
governo che non c'è più (la Siria di Assad). Ne vengono 93 coppie.

L'R² passa da 0,806 a 0,811. Non l'ho adottata: i bersagli delle sanzioni
(Russia, Bielorussia, Corea del Nord, Iran) sono già le rivalità strategiche e
i paesi ostili alla Russia.

**Che cosa resta davvero.** Le coppie che i dati sbagliano di più non sono
strutturali. Sono fatti politici datati:

- il Giappone vieta ogni commercio con la Corea del Nord dal 2006-2009, per i
  missili e i rapimenti;
- Israele e Hezbollah sono stati in guerra dall'ottobre 2023 alla tregua del
  novembre 2024;
- l'Etiopia e l'Eritrea sono tornate ostili dopo Pretoria (novembre 2022);
- l'Australia e la Nuova Zelanda hanno un mercato unico;
- l'Ungheria di Orban sta a metà strada fra l'Unione e Mosca.

Per queste il rapporto dichiarato è lo strumento giusto. Quelle lontane dai
dati portano ora accanto la ragione e la data (`db/seed/politica-nota.php`).

**Una correzione.** L'Arabia Saudita e l'Iran erano a −85. Hanno ristabilito le
relazioni diplomatiche a Pechino il 10 marzo 2023; la rivalità strategica resta,
l'ostilità aperta no. Ora sono a −60, che è anche quanto stimano i dati.

**Una tabella morta.** `politica-nota.php` conteneva 45 orientamenti politici
scritti a mano. Da `docs/33` l'orientamento viene dai voti all'ONU, e quei 45
valevano solo per chi non vota: Taiwan e il Kosovo, che fra i 45 non c'erano.
Non toccavano niente, e li ho tolti. Taiwan e il Kosovo prendono 45 dalla loro
forma di governo, vicino al Giappone (48) e alla Corea (56).

## 3. Le rivalità dopo il 2020

L'inventario di Thompson, Sakuwa e Suhas si ferma al 2020 e non ne esiste uno
più recente. Quel che è cambiato dopo lo portano le dispute e i rapporti
dichiarati: la guerra fra Israele e Iran del 2024-2025 cade su una rivalità già
in corso dal 1979, l'Etiopia e l'Eritrea sono nei rapporti dichiarati. Resta
un limite della fonte.

---

## Le misure

Quattro semi per due profili, quindici anni: **22 grandezze su 22 in fascia in
tutti e otto i mondi**. I cambi irregolari stanno a 1,5-2,1 l'anno (il vero è
1,8), la quota irregolare dei cambi al 5-8% (5,1%), la durata dei governi a
5,7-7,1 anni (5,4). L'audit non trova niente: 23 file del seme, 39 migrazioni.
Le prove sono 519; quelle nuove (`tests/19-guerra-e-pace.php`) verificano che il seme conosca i
colpi recenti del Niger e del Mali, e che l'Iraq e il Pakistan non ne abbiano.

## Serve un riavvio?

No. La migrazione 0039 è applicata, e la trappola parte dal seme anche nel
mondo vivo: le righe salvate prima della migrazione hanno il valore «mai»
(−9999), e allora vale il seme.

Le affinità invece non si aggiornano da sole. Il rapporto dichiarato cambiato è uno solo (Arabia
Saudita e Iran, da −85 a −60), e la nuova stima dei pesi sposta le altre coppie
di pochi punti. L'ancora delle relazioni del mondo vivo resta quella del
riavvio del 9 ottobre: non vale un riavvio, ma il prossimo le porterà.
