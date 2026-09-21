# 21 — Il commercio

Il verbo `embargo` esisteva dall'inizio. Funzionava così:

```php
$b->pressioneEsterna = min(0.070,
    $b->pressioneEsterna + 0.030 * $i * min(1.5, $a->influenzaTotale / 8.0));
```

Toglieva punti di crescita al bersaglio in proporzione al peso di chi lo
imponeva. L'intuizione di Crawford c'era — **un embargo da soli è teatro** — ma
tre cose mancavano, e sono le tre che fanno di un embargo una decisione invece
che un pulsante:

1. non sapeva **che cosa** veniva tagliato;
2. non sapeva che certe forniture si rimpiazzano in un trimestre e altre no;
3. non sapeva che **chi chiude un rubinetto smette di essere pagato per
   l'acqua**.

Accanto, nel modello, c'erano quattro campi di dipendenza (`dipEnergia`,
`dipCibo`, `dipFinanza`, `dipTecnologia`), una tabella `sdb_flusso_commerciale`
progettata bene fin dal 2002 — con tanto di colonna `sostituibilita`, commentata
«decide se una sanzione morde o è teatro» — e due manopole di calibrazione,
`peso_commercio` e `peso_dipendenza`. Dichiarati, salvati a ogni tick, **mai
letti da nessuna fase**. Zero righe nella tabella.

## Il grafo

Cinque settori: energia, cibo, tecnologia, finanza, manifattura. Ogni paese ne
produce e ne consuma, e chi ha un disavanzo compra da chi ha un avanzo,
scegliendo i fornitori per gravità — il peso dell'altro diviso l'attrito che li
separa (confinanti 0,70; stessa regione 1,10; lontani 3,20, e l'affinità allenta
tutto del 22 %).

### Due correzioni che il modello ha preteso

**L'apertura.** Un modello puramente di avanzi e disavanzi non produce nessun
flusso fra due paesi che producono entrambi un settore — e Francia e Germania si
vendono automobili a vicenda. Senza, un embargo fra loro costava esattamente
zero a tutti e due. Ora ogni paese compra fuori almeno una quota dei propri
consumi, tanto maggiore quanto è piccolo (il Belgio il 46 %, un continente
economico il 20 %).

**La competenza.** Lo specchio: con la sola apertura, chiunque poteva vendere
qualunque cosa, e la Francia finiva per esportare petrolio in Germania. Ora si
vende all'estero in proporzione a quanto si sa fare — chi copre a stento un
terzo del proprio fabbisogno di energia non è un esportatore di energia, per
quanto grande sia.

### La concentrazione

I gasdotti non sono container, e i settori non si concentrano allo stesso modo:

| settore | quanto conta la taglia | da quanti si compra |
|---|---|---|
| energia | 1,00 | 6 |
| finanza | 0,90 | 8 |
| cibo, tecnologia | 0,80 | 10 |
| manifattura | 0,65 | 15 |

Con una concentrazione uniforme la fornitura energetica della Germania si
spalmava su quattordici paesi dal 2 al 7 % ciascuno — e la leva del gas, che è
il caso canonico, semplicemente non esisteva. Adesso:

```
ENERGIA alla Germania        MANIFATTURA alla Germania
  SAU   16,0%  sost. 38        CHN   19,5%
  RUS   16,0%  sost. 38        MEX    3,8%
  NOR    5,6%  sost. 42        BRA    2,6%
  IRN    5,4%  sost. 43        … e una coda lunga di dodici
```

## Quel che il seme non può contenere

Il modello ricava la forma generale da dati veri — terra per abitante,
ricchezza, istruzione, taglia — ma non sa niente di quel che sta sottoterra, e
non può saperlo: il World Factbook non porta riserve di idrocarburi né
superficie arabile.

Ho provato a dedurlo. Un petrostato dovrebbe essere ricco senza essere
istituzionalmente maturo, e `ricchezza × (1 − maturità)` dovrebbe isolarlo. Non
funziona: con quella firma il Kuwait finisce accanto alla Polonia, all'Estonia e
al Giappone. **Quando un indice non contiene un'informazione, non c'è modo
furbo di tirarla fuori.**

Quindi si dichiara, come il progetto già fa per gli scostamenti noti
dell'intelligence: `db/seed/commercio-noto.php` elenca 75 paesi con un
moltiplicatore sulla produzione di un settore. `'SAU' => ['energia' => 9.0]`
vuol dire che l'Arabia produce nove volte l'energia che la sua taglia farebbe
prevedere. Sono ordini di grandezza di dominio comune, marcati `[FABBRICATO]`
come tutto il resto del seme.

Con questo, gli esportatori netti diventano riconoscibili:

- **energia** — Arabia Saudita, Russia, Iran, Indonesia, Canada, Algeria
- **cibo** — Stati Uniti, Brasile, Cina, Francia, Argentina, Canada
- **tecnologia** — Stati Uniti, Giappone, Cina, Germania, Corea del Sud, Taiwan
- **finanza** — Stati Uniti, Regno Unito, Singapore, Svizzera, Emirati, Paesi Bassi
- **manifattura** — Cina, Messico, Vietnam, Brasile, Taiwan, Bangladesh

*(Per arrivarci è saltato fuori che l'alfabetizzazione arriva dal seme già fra 0
e 1, e io la dividevo per cento: tecnologia e manifattura non avevano un solo
esportatore al mondo.)*

## Il morso, e il contraccolpo

Un embargo non toglie più un numero: chiude un rubinetto, e il danno lo calcola
il grafo per **tutti e due**.

*A chi lo subisce*, sommando sui settori: la quota del fabbisogno che passava di
lì, per quanto quel settore pesa se manca (energia 1,00; cibo 0,95; tecnologia
0,55; finanza 0,45; manifattura 0,35), per quanto è difficile rimpiazzarlo.

*A chi lo impone*: l'export perduto, **rapportato al suo PIL** — non al totale
del suo export, che avrebbe dato lo stesso colpo a chi vive di vendite estere e
a chi ne fa poche.

Che, tradotto in punti di crescita annua:

| | a loro | a noi |
|---|---|---|
| Russia contro Germania | 4,17 % | 0,34 % |
| Arabia Saudita contro Italia | 3,84 % | 0,51 % |
| Stati Uniti contro Corea del Nord | 2,86 % | 0,00 % |
| Francia contro Germania | 2,09 % | 0,46 % |
| Cina contro Stati Uniti | 1,60 % | 0,53 % |
| Bolivia contro Giappone | 0,00 % | 0,00 % |

L'ultima riga è la regola di Crawford, adesso derivata invece che imposta: un
embargo verso chi non ti compra niente non è debole, è **inesistente**.

Verificato sul motore: Mosca chiude verso Berlino, la Germania passa da −0,98 %
a −1,67 % di crescita con pressione 0,0417, la Russia paga 0,0034, l'Italia
resta a zero perché non c'entra, e alla scadenza tutto torna a posto da solo.

## Quel che il giocatore vede prima di decidere

È il punto di tutta la funzione. Sulla Scrivania dell'Economia e del Capo, e per
esteso su `/commercio`, sta la tabella con le due colonne: *costa a loro*,
*costa a noi*. Quando la seconda è più grande della prima, l'arma è puntata
dalla parte sbagliata — ed è un'informazione che si può avere solo prima, non
dopo.

## Quel che ho tolto

Ho collegato anche il saldo commerciale alla crescita, che era il senso della
manopola `peso_commercio` rimasta lì dal principio. **L'ho tolto.** I colpi di
Stato irregolari sono passati da 11,9 a 18,1 l'anno e la legittimità media è
crollata di otto punti, perché nel mio modello le importazioni sono costruite
come fabbisogno scoperto più una quota di apertura — il che rende tutti i paesi
poveri cronicamente in disavanzo. Quel numero dice più cose sul modello che sul
paese. `Commercio::saldo()` resta, con un avvertimento in testa, ma solo per
essere mostrato: è una descrizione, non una forza. Servirebbe un modello di
partite correnti vero, e non c'è.

## Una conseguenza sulla taratura, dichiarata

La crescita mondiale del mondo a vuoto è passata da **3,6 % a 4,1 %** l'anno,
contro un riferimento storico di circa il 3 %. Non è un caso: il vecchio
embargo piatto colpiva ogni bersaglio a prescindere dal commercio, e in
aggregato faceva da freno globale. Il freno nuovo vale 0,0002 punti per
nazione-tick — praticamente nulla, perché quasi tutti gli embarghi fra paesi
che non commerciano ora costano giustamente zero.

Non ho reintrodotto un freno finto per far tornare il numero. La taratura della
crescita si reggeva in parte su un effetto che non avrebbe dovuto esserci, e
ora si vede: resta da capire dove il modello di crescita corre caldo. I tassi
che contano — cambi irregolari 11,9 l'anno contro ~10 storici — non si sono
mossi.

## Dove sta

`src/Dati/Commercio.php` è il grafo, costruito col mondo e tenuto in memoria:
è dato derivato e si ricostruisce in cinque centesimi di secondo, quindi **non
si salva**. Una copia nel database sarebbe solo un'altra cosa che la memoria
può riscrivere sopra, ed è un errore che questo progetto ha già fatto tre volte.
`src/Gioco/Mercato.php` lo ricostruisce su richiesta per il web.
`sdb_strozzatura` (migrazione 0015) tiene i rubinetti chiusi, che invece sono
stato e vanno salvati; vivono anche in `$mondo->strozzature`, perché il motore
gira pure senza database e un embargo deve funzionare lo stesso.
