# 31 — I dati del mondo (ottobre 2026)

## Da dove nasce

`docs/30` aveva corretto le incoerenze del mondo vivo e lasciato aperte quattro
cose: le invasioni troppo rare, Taiwan senza i suoi indicatori di governo, il
petrolio letto dal primo prodotto esportato, e il passaggio del mondo vivo al
profilo «osservazione». L'osservatore ha deciso: si passa a «osservazione», si
riavvia il mondo e si scaricano tutti i dati che servono.

Questo documento è il primo blocco di quei dati. Ogni file grezzo sta in
`storage/fonti/`, che non va nel repo pubblico, e ogni importatore dice da dove
riscaricarlo.

---

## 1. La guerra fra rivali

**Il difetto.** L'invasione era un verbo come gli altri nella lotteria della
dottrina. Ogni volta che una nazione agiva, sceglieva una mossa per ogni
coppia e poi pescava fra le cinque coppie col rapporto più intenso, in un senso
o nell'altro. Cina-Taiwan e Russia-Ucraina arrivavano fra il sesto e
l'undicesimo posto su 188. In sei mondi da quindici anni usciva un'invasione,
contro le quattro-sei del 2010-2025.

**I dati.** Le guerre fra Stati nascono quasi tutte dentro rivalità durature:

> KLEIN, GOERTZ, DIEHL (2006), «The New Rivalry Dataset: Procedures and
> Patterns», *Journal of Peace Research* 43(3); DIEHL, GOERTZ (2000), *War and
> Peace in International Rivalry*.

`bin/importa_rivalita.php` conta le dispute militarizzate nei vent'anni prima
della divergenza (2006-2025). Fino al 2014 sono le **Dyadic Militarized
Interstate Disputes v4.03** del Correlates of War. Dal 2015 sono i conflitti fra
governi dell'**UCDP/PRIO Armed Conflict Dataset v26.1**: ogni anno di conflitto
vale una disputa. Valgono anche i conflitti interni in cui un altro Stato
combatte coi ribelli con le sue truppe, perché è così che l'UCDP registra
l'Armenia nel Karabakh e il Ruanda con l'M23. Ne escono 58 rivalità, 19 delle
quali durature (sei dispute o più): Russia-Ucraina, Iran-Israele, India-Pakistan,
le due Coree, Armenia-Azerbaigian, Ruanda-Congo, la coalizione saudita contro lo
Yemen degli Houthi, Cina-Taiwan…

Dalle stesse dispute, fra il 1946 e il 2014, viene il tasso: in una rivalità una
guerra (oltre mille morti) comincia nello **0,53%** degli anni, in una rivalità
duratura nell'**1,26%**.

**Il meccanismo** (`Fase00Chiusura::guerreFraRivali()`). Ogni settimana, per
ogni coppia in cui la guerra è materialmente possibile (`puoInvadere()`: ostilità,
superiorità netta, nessuna atomica né ombrello né garante forte, la possibilità
di arrivarci), l'invasione parte con il tasso misurato. Fra due paesi che si
odiano senza una storia di dispute il tasso è un decimo. Le autocrazie le
iniziano più spesso, e più volentieri contro chi sembra già mezzo caduto. Il
tasso vero vale per tutti gli anni di rivalità, mentre qui scatta solo in quelli
ammissibili, che sono un sottoinsieme. Il condizionamento (6) è tarato sul
numero di guerre del 2010-2025: con 1 uscivano due guerre in sei mondi, con 5 tre
per mondo, con 10 da quattro a otto.

Le coppie che ne escono sono quelle vere: Russia-Ucraina, Azerbaigian-Armenia,
Etiopia-Eritrea, Pakistan-Afghanistan, Cina-Taiwan, Thailandia-Birmania,
Iran-Iraq. Nella lotteria resta il gradino di sotto, la dimostrazione di forza.

---

## 2. Il petrolio

Fearon e Laitin (2003) chiamano «esportatore di petrolio» chi ricava dai
combustibili più di un terzo delle esportazioni. Ora il dato è proprio quello:
Banca Mondiale, *World Development Indicators*, TX.VAL.FUEL.ZS.UN, media 2019-2024.
L'Iran (48%) e gli Stati Uniti (17%) non richiedono più una correzione a mano.
Il Factbook resta solo dove la Banca Mondiale non ha il dato (Venezuela, Guinea
Equatoriale, Sud Sudan…).

## 3. Taiwan

L'API della Banca Mondiale non restituisce Taiwan, ma il dataset completo dei
WGI 2026 (`wgidataset_with_sourcedata-2026.xlsx`) lo pubblica. Ora l'importatore
legge quello, convertito in CSV con il comando scritto in testa a
`bin/importa_wgi.php`. Taiwan ha i suoi valori (stabilità 1,01, efficacia 1,53),
e il ripiego sulla Corea del Sud non c'è più.

## 4. I conflitti alla divergenza

Il seme partiva dai conflitti UCDP del 2024; ora parte da quelli del **2025**
(ACD v26.1 e Battle-Related Deaths v26.1), che è l'anno prima della divergenza.
Il livello del seme è un rapporto di forze, non un conto dei morti: il Pakistan
perde più di tremila persone l'anno ma il TTP non vale l'esercito pachistano.
Per questo l'elenco resta un giudizio, scritto riga per riga coi morti accanto:

- Haiti sale a guerra civile;
- la Siria del dopo-Assad scende a insurrezione grave, l'Afghanistan a guerriglia;
- entrano Israele (guerra con Hamas e Hezbollah, ma nessuna minaccia al controllo
  dello Stato), Indonesia (Papua), Iran (curdi e beluci), Kenya, Ruanda e Angola
  (Cabinda);
- escono Ciad, Libia, Mauritania, Senegal, Congo, Egitto e Burundi, che l'UCDP
  nel 2025 non registra più;
- il Messico resta, per scelta dichiarata: l'UCDP conta i cartelli fra i
  conflitti non statali, ma lo Stato combatte e i cartelli tengono territorio.

## 5. La polizia e la censura

**Il difetto.** Lo stato di polizia partiva da 2 per tutti e il controllo
dell'informazione da 50, e poi si muovevano solo con la minaccia interna. Una
Corea del Nord tranquilla scivolava verso la polizia di una democrazia, e la
Norvegia partiva col racconto controllato a metà.

**I dati.** V-Dem v16 (2026) via Our World in Data, anno 2025, con
`bin/importa_repressione.php`:

- *Physical Integrity Rights Index*: libertà da uccisioni politiche e tortura;
- *Freedom of Expression and Alternative Sources of Information Index*.

La repressione del regime è la media di (1 − integrità) e (1 − espressione), la
censura è (1 − espressione). La polizia «normale» del regime
(`Nazione::basePolizia()`) va da 1 a 5: Norvegia 1,1, Stati Uniti 2, Filippine
3, Russia 4,4, Corea del Nord 4,9. La minaccia la spinge da lì verso la morsa
piena. Il controllo dell'informazione parte dalla censura del regime (Singapore
60, Norvegia 5) e cresce con la stretta.

**Le elezioni.** Si votava solo con la polizia fino a 3. Con il dato V-Dem le
Filippine e l'India, che votano pur avendo uno Stato violento, avrebbero smesso
di votare. Ora a sospendere il voto è una stretta **oltre la norma** del
regime, non la norma.

---

## Le misure

Quattro semi per due profili, quindici anni: **21 grandezze su 21 in fascia in
tutti e otto i mondi.** Paesi in conflitto 19-31, in insurrezione grave o guerra
civile 5-15, cambi irregolari 3,6-5 l'anno, crescita mondiale 2,4-3,2%,
economie in recessione 7-13%.

Le prove sono 465: `tests/17-i-dati-del-mondo.php` verifica rivalità, rischio di
guerra, petrolio, Taiwan, conflitti del 2025, polizia e censura.

## I dati

| file del seme | fonte | importatore |
|---|---|---|
| `db/seed/rivalita.php` | COW Dyadic MID v4.03; UCDP/PRIO ACD v26.1 | `bin/importa_rivalita.php` |
| `db/seed/terreno.php` (petrolio) | Banca Mondiale, TX.VAL.FUEL.ZS.UN | `bin/importa_terreno.php` |
| `db/seed/governo.php` | WGI 2026, dataset completo | `bin/importa_wgi.php` |
| `db/seed/conflitti-noti.php` | UCDP/PRIO ACD e BRD v26.1, anno 2025 | a mano, riga per riga |
| `db/seed/repressione.php` | V-Dem v16 via Our World in Data | `bin/importa_repressione.php` |

## Il prossimo blocco

- **I voti all'Assemblea generale dell'ONU** (Bailey, Strezhnev e Voeten 2017):
  le affinità di partenza misurate per tutte le coppie, al posto della formula
  ideologica più i rapporti scritti a mano.
- **ATOP** (Leeds et al.) al posto del Correlates of War per le alleanze: arriva
  più avanti nel tempo e distingue i patti bilaterali da quelli multilaterali.

Toccano entrambi lo stato delle relazioni, quindi vanno ritarati insieme e
richiedono un altro riavvio del mondo.
