# 07 — Le matrici e la fase 06

Stato: **il mondo è un sistema.** Fase 06 implementata, più la parte globale
della 11 (livello di pace mondiale, decadimento della cattiveria).

## Il grafo dei confini

`bin/importa_confini.php` estrae dal Factbook i confini terrestri dichiarati e
produce `db/seed/confini.csv`: **311 coppie, 152 Stati con almeno un vicino, 37
isolati**. Russia e Cina hanno 14 vicini ciascuna, il Brasile 10, Germania e
Francia 9 — che sono i numeri veri.

Il campo è in prosa (`"Austria 404 km; France 476 km; ..."`) e i nomi sono
inglesi, quindi serve un dizionario di alias. Cinque nomi restavano ignoti —
fra cui **i Paesi Bassi, che mancavano proprio dal seme**: nella tabella dei
codici paese i Caraibi olandesi rivendicano lo stesso codice GEC `NL` e, in
ordine alfabetico, arrivavano prima. Ora fra due pretendenti vince sempre lo
Stato sovrano. Siamo passati da 188 a 189 nazioni.

## La matrice, e perché è sparsa

189 × 189 farebbero 35.700 coppie. Ne teniamo **circa 3.000**, quelle che hanno
una ragione di esistere: fra le 24 maggiori potenze, fra vicini di confine, e
fra ogni Stato e le sei grandi potenze. La motivazione non è la memoria (quella
ce l'abbiamo, Crawford no) ma il rumore: **simulare rapporti inesistenti non
aggiunge informazione**.

Ogni relazione porta: umore (le sette gradazioni di Shadow President, da Armonia
di interessi a Inimicizia), affinità (−127…+127), obbligo di trattato (la
tabella di Crawford: 0/16/32/64/96/128), sfera di influenza (1…15), contiguità,
e un'**ancora storica**.

## `politica-nota.php`, ovvero i limiti di una fonte descrittiva

Alla prima prova Russia e Ucraina risultavano amiche all'83%, e così India e
Pakistan. Il motivo è già scritto in `05-dati-scenari.md`: il World Factbook
descrive la Russia come *"semi-presidential federation"* e l'Ucraina pure. È una
descrizione giuridica, non una misura di allineamento, e da una descrizione
giuridica non si ricava con chi uno Stato sta.

Quindi `db/seed/politica-nota.php`: 45 scostamenti di orientamento politico e
una settantina di rapporti bilaterali notevoli — alleanze e inimicizie che
nessuna formula strutturale può indovinare. **È fabbricata e dichiarata tale**,
nello stesso spirito in cui Crawford fabbricò il suo indice di maturità. È
versionata perché la si possa discutere, e va sostituita con V-Dem e Correlates
of War quando le integreremo.

## L'ancora storica

Alla seconda prova le relazioni funzionavano ma **la deriva cancellava la
storia**: Israele e Siria passavano da −90 a +42 in quindici anni, gli Stati
Uniti e la Corea del Nord da −105 a −7. Pura aritmetica: un attrattore
strutturale che non sa nulla del passato trascina tutto verso la media.

Crawford è esplicito sul punto — nel suo modello **la storia pesa otto volte
l'ideologia**. La correzione è un'ancora per relazione, che il tempo non tocca e
che solo gli eventi spostano:

    bersaglio = 0,80 × ancora + 0,20 × compatibilità strutturale
    deriva    = 3% all'anno verso il bersaglio
    un cambio di regime sposta l'ANCORA del 45% verso la struttura

Con questo, i rapporti cambiano **per quel che accade, non per il passare del
tempo**. Nelle corse attuali si muovono un centinaio di rapporti in quindici
anni, e si muovono per un motivo leggibile: il Venezuela e Cuba si separano dopo
una rivoluzione venezuelana, la Corea del Nord si raffredda con la Cina dopo un
cambio al vertice.

## Due altri errori corretti

**L'indifferenza, non l'amicizia, è il valore di riposo.** L'attrattore partiva
da 75: due Stati che non hanno nulla da spartire risultavano amici, e il mondo
diventava un club. Ora parte da 25.

**Un trattato è un atto raro e costoso, non il sottoprodotto di una simpatia.**
Generando obblighi da ogni affinità, ogni grande potenza si ritrovava a
garantire centotrenta paesi, e l'integrità di tutti crollava a zero entro il
terzo anno. Ora si garantisce un vicino o un cliente di peso, e solo le cadute
**irregolari** mettono alla prova una garanzia: se il tuo cliente perde
un'elezione non hai tradito nessuno.

## Dove siamo

35 anni, profilo `osservazione`, seme 33:

| Misura | Valore | Riferimento |
|---|---|---|
| Cambi di esecutivo | 8,6 all'anno | ~10 |
| Vittorie insurrezionali | 20 in 35 anni | ~35 |
| Crescita PIL mondiale | 2,8% annuo composto | plausibile |
| Rapporti spostati di oltre 25 punti | ~120 in 15 anni | — |

Aggiunta alla fase 03: le economie **rallentano avvicinandosi alla frontiera
tecnologica**. Senza, chi parte al 6,5% ci resta per sempre e in trentacinque
anni l'India vale un quinto del mondo.

## Quel che non va ancora

- **La dispersione fra semi resta strettissima** (6,7–7,0 cambi l'anno su cinque
  semi). È il difetto più serio: un ensemble che non si apre non è un ensemble.
  Il sospetto è che il rumore economico venga assorbito dall'inerzia della
  legittimità prima di arrivare agli esiti. Da indagare prima di qualunque uso
  statistico del simulatore.
- **Nessun meccanismo forma nuove alleanze.** I blocchi si erodono e non si
  ricostruiscono, perché firmare un trattato è un *verbo* e i verbi arrivano
  con le fasi 00 e 02. Al momento è corretto che sia così, ma va ricordato
  leggendo le corse lunghe.
- L'integrità non viene quasi mai messa alla prova (una garanzia tradita per
  corsa): con trattati così rari, il meccanismo esiste ma non morde. Si vedrà
  quando i giocatori firmeranno trattati per scelta.
