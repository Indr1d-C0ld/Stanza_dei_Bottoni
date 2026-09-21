# 14 — La persistenza e l'osservatorio

Il mondo smette di vivere solo in memoria, e diventa guardabile.

## La strategia di persistenza

Una decisione che vale la pena dichiarare: **la topologia si ricostruisce dal
seme, lo stato si legge dalla base dati.**

Nazioni, confini, coppie di relazione, profili iniziali dei servizi e bacini
onomastici sono deterministici e derivano dai file in `db/seed`. Nella base dati
finisce solo ciò che cambia nel tempo. Costa una riga di codice in meno per ogni
campo strutturale, e soprattutto **rende impossibile che base dati e seme
divergano in silenzio** — che è il modo in cui questi progetti muoiono.

Quindi `bin/tick.php` fa, in quest'ordine: costruisce il mondo dal seme,
sovrascrive lo stato dal tick precedente, esegue le dodici fasi, salva.

## Le tabelle

La migrazione `0004` aggiunge quel che il mondo in memoria ha imparato a tenere
strada facendo: capacità e presenza dei servizi, gabinetti, poltrone con i loro
personaggi e le loro vulnerabilità, fazioni, guerre, feed pubblico. Più due
colonne sugli eventi per la falsa bandiera e una sulla conoscenza per l'accusato.

Nella tabella delle poltrone ci sono già due colonne che oggi restano vuote —
`giocatore_id` e `reclutata_da` — perché sono le due che trasformeranno questo
apparato in un gioco: chi occupa la poltrona, e chi l'ha comprata.

## L'osservatorio

Il web è **in sola lettura**. Mostra il mondo, non lo tocca: le azioni dei
giocatori richiedono autenticazione, poltrone assegnate e il vaglio delle
controfirme, e arrivano dopo.

Quattro pagine:

- **Il mondo** — data di gioco, livello di pace, guerre in corso, chi conta,
  ultime notizie;
- **Le nazioni** — tutti e 189 gli Stati, ordinabili;
- **La scheda paese** — i quattro quadranti della "città" di Shadow President
  tradotti in dati (economia, società, influenza, forze), il gabinetto con
  poltrone e fazioni, e i rapporti bilaterali con umore e trattati;
- **La cronaca** — il feed pubblico, dove entra soltanto quel che il mondo ha
  potuto sapere.

Il registro visivo è quello di un terminale di sala operativa: scuro, denso,
niente ornamenti. Un foglio di stile unico, nessuna dipendenza esterna.

In fondo a ogni pagina, sempre, l'avvertenza: *gli Stati sono reali, le persone
no; nulla di quel che leggi qui è una previsione.*

Se la base dati c'è ma è vuota, il sito non si rompe: dice quali due comandi
servono e in che ordine.

## Come si avvia

    sudo bash deploy/00-avvio.sh     # database, utente, tabelle, Apache
    php bin/avvia_mondo.php          # costruisce il mondo al tick zero
    php bin/tick.php                 # fa scorrere il tempo di una settimana

Lo script di avvio è idempotente: se la configurazione esiste già non rigenera
la password, fa il backup di quel che sostituisce, e **verifica la
configurazione di Apache con `configtest` prima di ricaricare**. Il passo Apache
si salta con `CON_APACHE=no`.

Poi il tick va messo a cron ogni due ore, che è la cadenza di progetto: un tick
vale una settimana di gioco.

## Una mina in tre parole

Il primo avvio reale è fallito, e la diagnosi merita di restare scritta perché
l'errore è fra i più insidiosi che si possano piazzare in uno script di deploy.

La password veniva generata così:

    DB_PASS="$(tr -dc 'A-Za-z0-9!@%_+=-' </dev/urandom | head -c 32)"

Sembra innocuo. Ma lo script gira sotto `set -euo pipefail`, e quel `head`
chiude la pipe dopo trentadue byte: `tr` riceve SIGPIPE, la pipeline esce con
**141**, `pipefail` lo propaga e `set -e` uccide lo script — **in silenzio, senza
un messaggio di errore**, esattamente fra il passo 2 e il passo 3.

Il risultato era il peggiore possibile: la cartella dei segreti creata e vuota,
nessun utente di database, e un errore che si manifestava molto più tardi e
altrove — `Access denied for user 'sdb_stanza'@'localhost'` al primo avvio del
mondo, che porta a cercare il problema nei posti sbagliati.

Due correzioni, e la seconda è quella che conta:

1. la password si genera **senza pipe**, con PHP, che è già un requisito
   verificato in testa allo script;
2. **lo script non può più dichiarare successo lasciando un impianto rotto**: in
   fondo c'è una verifica che apre davvero una connessione con le credenziali
   appena scritte, e se non funzionano esce con errore.

La seconda correzione vale più della prima. Il difetto specifico è stato
rimosso, ma la classe di difetti — «lo script dice che ha finito e non ha
finito» — si chiude solo provando il risultato.

## Il secondo difetto, scoperto al primo giro vero

Superato l'avvio, il mondo girava — ma **non si ricordava di sé**. Bastava un
confronto prima/dopo un tick per vederlo:

    PRIMA  — capo di Francia: Luca Colombo      potere 77,7
    DOPO   — capo di Francia: Chiara Guerrini   potere 89,5

Il `Deposito` **salvava molto più di quanto rileggesse**. Il `ripristina`
riportava lo stato delle nazioni, le relazioni e lo stato globale, e basta.
Tutto il resto veniva ricostruito dal seme a ogni tick:

- i **gabinetti** non esistevano al caricamento, quindi la fase 10 ne creava di
  nuovi — con persone nuove — ogni due ore;
- gli **eventi in volo** restavano scritti in tabella ma il mondo non li
  caricava: non maturavano mai, e intanto se ne ordinavano altri;
- la **conoscenza** dei servizi ripartiva da zero a ogni tick;
- e quattordici campi di memoria per nazione — deriva politica, integrità,
  contatori storici, scadenze elettorali — non avevano nemmeno una colonna.

La migrazione `0005` aggiunge le colonne mancanti, e il `Deposito` ha ora quattro
metodi di ripristino in più: eventi con la relativa conoscenza, palazzo con le
persone che lo abitano, guerre aperte, capacità e presenza dei servizi. Più il
contatore degli identificatori degli eventi, che altrimenti ricominciava da uno
e si scontrava con quelli già scritti.

Verificato: su tre tick consecutivi il Capo di Francia resta Chiara Guerrini e
il suo potere evolve con continuità (89,5 → 89,3 → 89,2).

**La lezione generale**: salvare e rileggere sono due metà della stessa cosa, e
scriverne una sola produce un sistema che sembra funzionare. Nessun errore,
nessuna eccezione — solo un mondo che ogni due ore dimentica chi è.

## Le prestazioni

Un tick completo contro MariaDB costa **circa 0,7 secondi**: 189 nazioni, 3.074
relazioni, dodici fasi e la scrittura. Con un tick ogni due ore il margine è
abbondante. Venti tick di fila in quattordici secondi.

## Quel che manca ancora

L'autenticazione, l'assegnazione delle poltrone ai giocatori, la Scrivania con
la Cartella del Giorno e la regola delle tre righe, le controfirme, la
messaggistica con i canali sicuri. Cioè: il gioco.

Ma il mondo ora c'è, gira da solo, si ricorda di sé e si lascia guardare.
