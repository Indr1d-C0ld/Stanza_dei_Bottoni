# 29 — Il registro delle operazioni e le contromosse (settembre 2026)

## Da dove nasce

Un giocatore, da solo, si e' seduto all'Intelligence iraniana e ha dato due
ordini di sabotaggio contro Israele. Poi non ha visto piu' niente: la scrivania
diceva «manca la firma del Capo», e nient'altro. Gli e' sembrato che da solo
non si potesse giocare, perche' serviva una seconda persona al tavolo.

Nella base dati la storia era diversa. Il Capo iraniano non era una persona, e
**l'apparato aveva firmato al posto suo** — come e' previsto. Le due operazioni
erano partite. Il servizio israeliano le aveva scoperte fino al terzo gradino,
cioe' sapeva che erano dirette contro Israele, e **le aveva fermate tutte e
due**. Il motore sapeva tutto; il sito non ne diceva niente.

Il difetto non era nella firma. Erano tre buchi, tutti di chi guarda dalla
scrivania:

1. un ordine firmato **spariva**: la Cartella mostrava solo quelli in attesa;
2. i quattro gradini di conoscenza che il motore tiene per ogni servizio — che
   qualcosa si muove, di che genere e dove, contro chi, chi l'ha ordinato — non
   li vedeva **nessuno**;
3. le contromosse erano **tutte automatiche**: quando un servizio scopriva
   un'operazione, la fase 01 decideva da sola se sventarla. Il gioco promette
   che chi scopre in tempo puo' «fermarlo, negarlo, farlo trapelare o usarlo», e
   il giocatore all'Intelligence non aveva niente da scegliere.

---

## Il registro delle operazioni

Nella scrivania, per ogni poltrona: gli ordini dati, dal piu' recente, col loro
destino fino in fondo.

- **In attesa** della seconda firma — e, se chi deve firmare non e' una persona
  al tavolo, lo dice: al prossimo giro decide l'apparato, e firma piu'
  volentieri quel che e' coerente con la linea del governo. Lo dice anche il
  messaggio al momento dell'ordine.
- **Firmato**, da chi: da una persona o dall'apparato.
- **Negato** dall'apparato, **scaduto**, **ritirato**.
- **Partito**, e in corso fino alla data in cui arriva a destinazione.
- **Arrivato a segno**, o **fermato**: qualcuno l'ha visto in tempo, o il
  governo ci ha ripensato.
- **Attribuito**: se un servizio lo ha dimostrato e il mondo lo sa, il registro
  lo dice — e dice anche quando la colpa e' caduta su un altro, perche' la
  falsa bandiera ha funzionato.

Il registro dice solo cio' che chi ha ordinato **puo' sapere**. Chi ha visto
passare l'operazione senza dirlo resta affare dei servizi altrui.

Per farlo, l'ordine adesso sa quale operazione ha fatto partire
(`sdb_ordine.evento_id`), se la firma e' della macchina
(`firmato_da_apparato`), e l'operazione sa quando si e' chiusa
(`sdb_evento.chiuso_tick`). La notizia di uno scandalo porta l'identificativo
dell'operazione, cosi' il mandante vero la ritrova anche quando accusa un
altro. Migrazione 0034.

E gli **avvisi per posta** dicono le operazioni arrivate in fondo dall'ultimo
messaggio: bastano da sole a farne partire uno, perche' chi gioca da solo non ha
altro, e la pausa fra due avvisi impedisce che diventino una pioggia.

---

## Il quadro del servizio

Per chi dirige l'Intelligence e per il Capo: le operazioni altrui che il
servizio ha scoperto, in corso o chiuse nelle ultime venti settimane, ciascuna
col suo gradino e con quel che il gradino permette di dire.

| gradino | cosa si vede |
|---|---|
| 1 | qualcosa si muove, e il servizio non sa ancora leggerlo |
| 2 | di che genere (sabotaggio, disinformazione…) e in quale regione |
| 3 | contro chi — «contro di noi», se tocca a noi |
| 4 | il nome di chi l'ha ordinata, secondo il servizio: puo' essere quello sbagliato |

---

## Le contromosse

Dal terzo gradino in su — quando si sa almeno contro chi — il servizio sceglie.

| scelta | quando | che cosa fa |
|---|---|---|
| **Sventarla** | contro di noi, in corso, non dichiarata | si concentrano i mezzi: la probabilita' di fermarla a ogni giro e' 1,6 volte quella del lavoro ordinario, fino al 95% |
| **Lasciarla correre e seguirla** | contro di noi, in corso | non si ferma: ma si arriva al quarto gradino, il nome, due volte piu' in fretta |
| **Farla trapelare** | in corso, o gia' attribuita | al quarto gradino e' uno scandalo per chi il servizio accusa — anche se accusa l'innocente — e l'accusato non ce lo perdona; al terzo e' la notizia di un'operazione in corso di mano ignota, e chi l'ha avviata la abbandona sei volte su dieci |
| **Avvisare il paese colpito** | contro altri, in corso | il suo servizio sa quel che sappiamo noi, nome compreso; e il rapporto guadagna otto punti |

**Il silenzio e' una scelta.** Quando la sicurezza di un paese ha una persona al
tavolo — l'Intelligence o il Capo — la macchina non sventa piu' da sola le
operazioni contro di lui, e non manda da sola in stampa quel che il suo servizio
ha scoperto. Decide la persona. Si puo' lasciar arrivare a segno un sabotaggio
per non far sapere che lo si era visto; si puo' tenere per se' il nome del
mandante e farlo trapelare quando conviene. Dove non c'e' nessuno, l'apparato
lavora come prima.

Le quattro leve stanno nel blocco `contromosse` di `calibrazione/base.php`, e
sono dichiaratamente fabbricate: sono le regole del gioco, non grandezze del
mondo.

### Un difetto trovato strada facendo

Quando un'operazione veniva sventata dopo essere stata attribuita, la notizia e
la rappresaglia diplomatica colpivano **il mandante vero**, anche se il
servizio del bersaglio era stato ingannato da una falsa bandiera: come se il
bersaglio conoscesse la verita'. Adesso colpiscono chi il servizio accusa, come
gia' facevano gli scandali.

E `sdb_conoscenza.primo_tick` si riscriveva a ogni giro: la settimana in cui un
servizio si era accorto di qualcosa diventava sempre «questa». Adesso resta
quella vera, e `aggiornata_tick` si muove solo quando il servizio sa qualcosa di
nuovo.

---

## Da solo al tavolo

Adesso si puo' giocare da soli davvero, da qualunque poltrona che abbia un
dominio d'azione: si ordina, l'apparato firma o nega, si segue l'operazione fino
in fondo. La poltrona piu' ricca resta il **Capo**: ordina in ogni dominio,
siede al tavolo delle crisi e vede il quadro del servizio. Le operazioni coperte
e quelle militari chiedono anche a lui la seconda firma del ministro competente —
Intelligence o Informazione, Difesa — e se quel ministro non e' una persona al
tavolo la mette l'apparato; la diplomazia e l'economia le firma da solo. L'**Intelligence** e' adesso il posto per chi vuole giocare la
partita delle ombre: vedere, scegliere cosa fermare, cosa lasciar correre, cosa
dire e a chi.

## Le prove

`tests/15-registro-e-contromosse.php`, in una transazione annullata con un tick
vero su una copia del mondo vivo: l'ordine dell'Intelligence che dice che firma
l'apparato, il quadro con le scelte giuste per gradino e ruolo, la fuga di
notizie che finisce in cronaca, l'avviso che fa sapere alla Germania quel che
sapeva la Francia, il registro che segue l'ordine. Le prove sono 420.
