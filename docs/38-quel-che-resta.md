# 38 — Quel che resta, e perché (ottobre 2026)

## Da dove nasce

`docs/37` lasciava due cose aperte:

1. i colpi di Stato fra 0,15 e 0,25 di democrazia liberale, nella parte alta
   dell'intervallo del dato;
2. le rivalità strategiche ferme al 2020.

La prima si chiude con una misura: lo scarto non è distinguibile dal caso. La
seconda era aperta solo a metà, e l'altra metà ha ora un posto suo.

---

## 1. I colpi fra 0,15 e 0,25: compatibili col dato

Nei regimi parziali il modello fa dipendere i colpi dalla stabilità politica più
del mondo vero. La pendenza di Poisson è −0,8 nel modello contro −0,49 ± 0,20
nel dato, a parità di trappola del colpo. Ho cercato da dove venisse.

**La qualità della vita.** Era il sospettato: il suo peso viene da Goldstone, che
misurava l'instabilità in generale, non i colpi. Ho rifatto il conto con la
mortalità infantile della Banca Mondiale (SP.DYN.IMRT.IN), la stessa variabile di
Goldstone, sui colpi di Powell e Thyne del 2000-2025:

| regressione di Poisson, regimi parziali | stabilità politica | colpo nei 10 anni prima | log mortalità infantile |
|---|---|---|---|
| senza mortalità | −0,49 ± 0,20 | +1,14 ± 0,37 | — |
| con mortalità | −0,32 ± 0,22 | +1,02 ± 0,37 | +0,44 ± 0,26 |

Fra il primo e il terzo quartile della mortalità infantile i colpi raddoppiano,
ed è quel che il modello fa con 2,7 punti di legittimità per livello e la
pendenza a 18 (`docs/36`). Il termine è giusto.

**La curva.** Ammorbidirla ancora non serve. Con la pendenza a 25 la stima sulla
stabilità passa a −0,75, a 35 a −0,72; nel frattempo cala tutto il livello e la
fascia 0,25-0,40, che è già bassa, scende ancora. Il gradiente viene dall'ancora
della legittimità (i WGI, che contengono la stabilità politica) e da chi è in
guerra, non dalla forma della logistica.

**La conclusione.** −0,8 contro −0,49 ± 0,20 sono un errore standard e mezzo; la
fascia 0,15-0,25 sta dentro l'intervallo di Poisson al 95% del dato (21-26 nel
modello contro 7-28). Il dato ha 35 colpi in tutto. Inseguire lo scarto vorrebbe
dire tarare il modello sul rumore di una manciata di eventi. Lo chiudo qui: non
è un difetto misurabile.

## 2. Le ostilità dopo il 2020

**Quel che c'era già.** Le dispute militarizzate del seme vengono dal Correlates
of War fino al 2014 e dai conflitti fra governi dell'UCDP dal 2015 al 2025
(`bin/importa_rivalita.php`). Le ostilità armate nate dopo il 2020 ci sono:

| coppia | ultimo anno di conflitto |
|---|---|
| India–Pakistan | 2025 |
| Cambogia–Thailandia | 2025 |
| Iran–Israele | 2025 |
| Afghanistan–Pakistan | 2025 |
| Congo–Ruanda | 2025 |
| Armenia–Azerbaigian | 2023 |
| Kirghizistan–Tagikistan | 2022 |

**Quel che mancava.** Le ostilità senza combattimenti fra Stati, che nessuna fonte
strutturale vede: né l'UCDP, che conta i conflitti armati, né Thompson, Sakuwa e
Suhas, che si fermano al 2020. Il modello aveva la Cina e le Filippine a +53,
l'Algeria e il Mali a +75. Fra la Turchia e Israele, e fra la Francia e le
giunte del Sahel, la relazione non esisteva nemmeno.

**La correzione.** Un blocco nuovo in `db/seed/politica-nota.php`, gli «eventi
recenti». Ogni coppia ha la sua data:

| coppia | affinità | perché |
|---|---|---|
| Cina–Filippine | −40 / −45 | la secca di Second Thomas, l'abbordaggio del 17/06/2024 |
| Turchia–Israele | −50 / −40 | la Turchia sospende ogni commercio, 02/05/2024 |
| Algeria–Mali | −40 / −45 | il drone abbattuto a Tinzaouaten, 01/04/2025 |
| Mali, Burkina Faso, Niger–Francia | −50, −45, −55 | le truppe francesi cacciate, 2022-2023 |
| Niger–Benin | −25 | il confine chiuso dal 2023, l'oleodotto nel 2024 |
| Cina–Lituania | −30 | l'ufficio di Taiwan a Vilnius, 18/11/2021 |
| Sudafrica–Israele | −45 / −35 | il ricorso alla Corte internazionale di giustizia, 29/12/2023 |
| Azerbaigian–Russia | −15 | il volo AZAL abbattuto il 25/12/2024 |

Vincono come i rapporti dichiarati, ma **non entrano nella stima dei pesi** delle
affinità. I rapporti dichiarati servono anche a insegnare alla regressione come
la struttura (voti, patti, dispute, rivalità) diventa affinità; un fatto del
2024 che la struttura non può vedere le insegnerebbe soltanto rumore. Le prove
verificano che `bin/importa_onu.php` non li legga: i pesi e l'R² 0,81 sono
identici a prima.

Il limite della fonte resta, ma ora ha un posto dove si scrive quel che succede
dopo, ciascuno con la sua data.

---

## Le misure

Quattro semi per due profili, quindici anni: **22 grandezze su 22 in fascia in
tutti e otto i mondi**, con le guerre aperte a fine corsa come prima (0-2): le
ostilità nuove non ne accendono. Le prove sono 520, e l'audit non trova niente.

## Serve un riavvio?

Non è urgente, ma è consigliato. Le coppie che prima non esistevano (Turchia e
Israele, la Francia e il Sahel, il Sudafrica e Israele) compaiono nel mondo vivo
al primo tick. Quelle che esistevano già (Cina e Filippine, Algeria e Mali, Niger
e Benin, Cina e Lituania, Azerbaigian e Russia) hanno l'ancora salvata e tengono
il valore vecchio fino a un riavvio. Lo stesso vale per Arabia Saudita e Iran
(`docs/37`).

    bash deploy/02-riavvio.sh --profilo=osservazione
