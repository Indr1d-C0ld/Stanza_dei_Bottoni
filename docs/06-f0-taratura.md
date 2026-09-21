# 06 — F0: quel che il motore a vuoto ha insegnato

Stato: **il metabolismo gira.** Fasi 03 (economia), 04 (società), 05 (sicurezza
interna) implementate; le altre nove sono contratti scritti e corpo vuoto.

    php bin/simula.php --anni=15 --profilo=osservazione --cronaca

15 anni di mondo in circa un secondo, 188 nazioni, nessuna base dati.

## I tre errori che hanno reso il mondo inerte

Meritano di restare scritti, perché sono tutti e tre errori di *traduzione* fra
un modello del 1986 e il nostro, non errori di formula. E sono silenziosi: il
programma gira, non segnala nulla, e produce un mondo morto.

**1. Le unità dell'inerzia.** L'inerzia della popolarità in Balance of Power è
annua. Applicata per tick riportava ogni governo alla media in due mesi: nessuno
cadeva mai, perché niente restava impresso abbastanza a lungo da contare.

**2. La scala della soglia.** Crawford fa cadere un governo quando la sua
popolarità scende sotto zero, su una scala da 1 a 20. La nostra legittimità va
da 0 a 100 e si assesta intorno a 50: la soglia equivalente non è zero, è circa
22. Tradurre un modello significa tradurne anche le scale.

**3. Le unità del reclutamento.** La forza del governo è una media geometrica di
uomini ed equipaggiamento; contare gli insorti a testa le rende
incommensurabili, e il governo li annientava sempre, ovunque, in ogni scenario.

Più un quarto errore, di modello e non di unità: **i consumi vanno trattati come
residuo**, non come una voce che compete con le altre. Trattandoli alla pari, gli
investimenti si prendevano il 46% del prodotto e ogni economia del mondo
cresceva al tetto di calibrazione. Il PIL mondiale triplicava in quindici anni.

E un quinto, di conseguenze: **dopo una rivoluzione gli insorti diventano
l'esercito.** Se il vincitore eredita uno Stato più debole di quello che ha
appena battuto, il paese ricade in rivoluzione ogni pochi mesi. Prima della
correzione: 1087 rivoluzioni in 8 paesi in 15 anni.

## Dove siamo, contro i riferimenti storici

Profilo `osservazione`, 15 anni, cinque semi:

| Misura | Il nostro mondo | Riferimento (Crawford, da Taylor e Jodice) |
|---|---|---|
| Cambi di esecutivo | 7,1–7,5 all'anno | ~10 all'anno (238 irregolari in 30 anni su ~150 paesi) |
| Nazioni toccate in 15 anni | 52–56 su 188 | plausibile |
| Vittorie insurrezionali | 10–11 in 15 anni | ~15 (40 successi in 40 anni) |
| Crescita del PIL mondiale | 3,0% annuo composto | plausibile |

E i paesi che si muovono sono quelli giusti: Sudan, Haiti, Sud Sudan, Timor Est,
Repubblica Centrafricana, Comore, Venezuela. Non è un risultato da poco — nessuna
riga del codice nomina quei paesi: emergono da maturità, reddito e crescita.

## Quel che non va ancora

- **La dispersione fra semi è troppo stretta** (7,1–7,5). Un ensemble dovrebbe
  aprirsi di più: significa che il rumore non si propaga fino agli esiti
  strutturali. Da indagare prima di fidarsi degli ensemble.
- **La legittimità media deriva lentamente verso il basso** (50 → 44 in 15 anni).
  Piccolo, ma è una deriva, e le derive si compongono.
- **Qualche anomalia individuale**: il Botswana cambia governo quattro volte pur
  avendo maturità 178. Da guardare.
- **`maturita` è ancora un segnaposto** derivato da reddito e alfabetizzazione.
  È il primo dato da sostituire con V-Dem, ed è quello che decide quasi tutto.
- Le insurrezioni sono un po' sotto il tasso storico: `reclutamento_k` è il
  parametro da muovere per primo, ed è dichiaratamente fabbricato.

## Il seme

`db/seed/nazioni.csv` — 188 Stati sovrani, nomi in italiano, 19 campi.
Prodotto da `bin/importa_factbook.php` a partire dal World Factbook (pubblico
dominio) e da una tabella di corrispondenza dei codici paese. Nel repository
entra **solo il derivato**: il clone del Factbook resta in `storage/`, ignorato
da git.

Campi marcati nel codice: `[FABBRICATO]` per gli indici che nessuna fonte ci dà
(valore strategico, valore di prestigio, reclutamento), `[SEGNAPOSTO]` per
quelli che una fonte ci darebbe ma che non abbiamo ancora integrato (maturità,
alfabetizzazione mancante, forze armate mancanti).
