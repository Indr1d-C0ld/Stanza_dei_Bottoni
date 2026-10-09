# 32 — I voti all'ONU e le alleanze di ATOP (ottobre 2026)

## Da dove nasce

È il secondo blocco di dati annunciato in `docs/31`. Tocca lo stato delle
relazioni fra gli Stati, cioè le due cose che il seme fabbricava ancora:

- **gli obblighi di trattato** venivano dal Correlates of War, *Formal Alliances*
  v4.1, che arriva al 2012 e tratta allo stesso modo il Patto atlantico e la Lega
  araba;
- **l'affinità di partenza** era una formula sull'orientamento politico (25 meno
  la distanza ideologica), più 137 rapporti scritti a mano e dichiarati
  «FABBRICATI». Fuori da quei 137 quasi ogni coppia del mondo valeva 25: il
  primo quartile, la mediana e il terzo quartile delle affinità erano lo stesso
  numero.

---

## 1. Le alleanze: ATOP

> LEEDS, RITTER, MITCHELL, LONG (2002), «Alliance Treaty Obligations and
> Provisions, 1815-1944», *International Interactions* 28(3); ATOP v5.1, 1815-2018,
> www.atopdata.org.

`bin/importa_alleanze.php` legge ATOP membro per membro: chi promette che cosa a
chi, in ogni alleanza in vigore nel 2018. Ne vengono gradini direzionati,
perché un obbligo può essere asimmetrico: gli Stati Uniti difendono il Giappone,
il Giappone non difende gli Stati Uniti. La scala è quella di sempre: 16
consultazione, 32 neutralità o non aggressione, 64 patto offensivo o basi, 96
difesa, 128 difesa con l'atomica, assegnato da `Mondo`.

ATOP arriva al 2018 e il seme è del gennaio 2026. Quel che è successo dopo, o che
il dataset tiene in piedi contro i fatti, sta in tavole scritte nell'importatore,
ogni riga con la sua data.

| | |
|---|---|
| **patti nuovi** | Russia-Corea del Nord (19/06/2024, in vigore dal 04/12/2024); Arabia Saudita-Pakistan (17/09/2025); Turchia-Azerbaigian (Dichiarazione di Shusha, 15/06/2021); Alleanza degli Stati del Sahel (16/09/2023) |
| **ingressi** | Macedonia del Nord (2020), Finlandia (2023) e Svezia (2024) nella NATO |
| **uscite** | Ucraina e Georgia dalla CSI; Armenia dalla CSTO (congelata nel 2024); Mali, Burkina Faso e Niger dall'ECOWAS (29/01/2025) |
| **smentite dai fatti** | Armenia-Azerbaigian (un patto collettivo del 1993; tre guerre dopo); Algeria-Marocco (rapporti rotti il 24/08/2021); Ruanda-Congo (l'M23 a Goma, 27/01/2025) |

Solo i patti di difesa e le basi fanno nascere una relazione nuova. ATOP porta
anche migliaia di patti di non aggressione e di consultazione fra paesi che non
hanno altro da spartire, e con quelli la matrice sarebbe raddoppiata. Dove la
relazione c'è già, il gradino si applica lo stesso.

**Patti integrati e patti sulla carta.** Fra i patti di molti, la NATO, la CSTO e
il Consiglio di cooperazione del Golfo hanno comandi o forze integrate: lo SHAPE
dal 1951, la forza di reazione rapida della CSTO dal 2009, il Peninsula Shield
dal 1984. ATOP non lo vede, perché codifica il testo del trattato: il suo
`milcon` dà 0 al Patto atlantico del 1949 e 2 alla Lega araba. Gli altri patti
regionali (l'OAS, l'Unione africana, la Lega araba, la SADC) sono promesse sulla
carta, e la promessa a ciascun membro si diluisce con il numero dei membri.

## 2. L'affinità: voti, patti, dispute

> BAILEY, STREZHNEV, VOETEN (2017), «Estimating Dynamic State Preferences from
> United Nations Voting Data», *Journal of Conflict Resolution* 61(2); Voeten,
> *UNGA Ideal Point Estimates 1946-2025*, Harvard Dataverse v39 (30/07/2026).
>
> SIGNORINO, RITTER (1999), «Tau-b or Not Tau-b», *International Studies
> Quarterly* 43(1); HÄGE (2011), *Political Analysis* 19(3): la somiglianza di
> politica estera si misura con i voti e con i portafogli di alleanze.

Ogni Stato ha un punto ideale, preso come media dei voti 2023-2025, su un asse che
va dal consenso dei paesi in via di sviluppo all'allineamento con gli Stati Uniti.
Ma votare uguale non vuol dire volersi bene: l'India e il Pakistan votano quasi
allo stesso modo. L'ostilità la dicono le dispute militarizzate di
`db/seed/rivalita.php`. L'affinità strutturale di una coppia è quindi

    costante + a·distanza dei punti ideali + b·difesa bilaterale
             + c·patto integrato + d·patto sulla carta / √(membri − 1)
             + e·dispute (fino a dieci)

e i pesi **non sono scelti**. `bin/importa_onu.php` li stima ai minimi quadrati
sui rapporti dichiarati di `db/seed/politica-nota.php`: la scala resta quella dei
rapporti dichiarati, il valore di ogni coppia viene dai dati.

    affinità = 42,7 − 32,9·distanza + 68,0·bilaterale + 68,5·integrato
               + 124,5·sulla carta/√(membri−1) − 10,8·dispute     (R² = 0,62)

I rapporti dichiarati restano, e vincono, solo dove i dati non vedono la
rivalità: l'Arabia Saudita e l'Iran, il Giappone e la Corea del Nord, i Baltici e
la Russia non si sono mai sparati addosso direttamente. Taiwan, il cui ultimo
voto all'ONU è della Repubblica di Cina nel 1971, e il Kosovo, che non è membro,
restano sulla formula ideologica.

Le coppie si separano: Perù-Haiti 70, Nigeria-Ghana 63, Cambogia-Thailandia 6,
Vietnam-Cina −12, Svezia-Norvegia 110. Il primo e il terzo quartile, che prima
coincidevano, ora sono lontani.

## 3. La guerra fra rivali, ritarata

Con le affinità misurate le coppie ostili aumentano (quelle sotto −70 passano
da 400-500 coppie-anno a 550-620), e il condizionamento della guerra fra rivali
(`docs/31`) faceva cinque-sette invasioni per mondo. A 4,5 ne fa da tre a nove,
cinque in media, sulle coppie del 2010-2025: Russia-Ucraina, Arabia
Saudita-Yemen, Azerbaigian-Armenia, Cina-Taiwan, Etiopia-Eritrea, Iran-Afghanistan.

## Le misure

Quattro semi per due profili, quindici anni: **21 grandezze su 21 in fascia in
tutti e otto i mondi.** Le prove sono 486: `tests/18-onu-e-atop.php` verifica le
alleanze, le loro correzioni datate, i pesi stimati e le affinità che ne escono.

## Il mondo vivo

Le relazioni del mondo vivo stanno nella base dati, e il mondo riavviato il
09/10/2026 alle 03:51 è nato col seme di prima. Per avere le alleanze di ATOP e
le affinità misurate serve un altro riavvio:

    bash deploy/02-riavvio.sh --profilo=osservazione

## Che cosa resta aperto

- Le affinità del seme spiegano il 62% dei rapporti dichiarati. Il resto sono
  rivalità senza scontri diretti, ed è lì che restano i rapporti scritti a mano.
  Una misura dei rapporti correnti (gli eventi diplomatici di POLECAT/ICEWS) li
  potrebbe sostituire.
- L'orientamento politico dei paesi viene ancora dal Factbook più 45 correzioni a
  mano. Il punto ideale dell'ONU misura proprio l'allineamento, ma nel modello
  l'orientamento fa anche da «radicalità» del regime, e gli Stati Uniti, polo
  dell'asse, risulterebbero il regime più radicale del mondo. Va separato prima.
