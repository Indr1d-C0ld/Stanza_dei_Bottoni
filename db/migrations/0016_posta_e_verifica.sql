-- 0016 — La posta in uscita, e la verifica dell'indirizzo.
--
-- Finora chiunque trovasse l'indirizzo poteva registrarsi con una casella
-- inventata e prendersi una poltrona in un gabinetto. In un gioco persistente
-- dove le poltrone sono poche e si tengono per mesi, e' un problema pratico
-- prima che di sicurezza: un posto occupato da un indirizzo che non esiste e'
-- un posto perso per tutti.
--
-- La coda esiste perche' l'SMTP puo' non rispondere. Senza, il messaggio di
-- verifica andava perduto e l'utente restava fuori dal gioco senza capire
-- perche'. Ogni messaggio entra in coda PRIMA di essere tentato: se il
-- processo muore a meta', il messaggio resta.

CREATE TABLE IF NOT EXISTS sdb_posta (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  destinatario  VARCHAR(190) NOT NULL,
  oggetto       VARCHAR(190) NOT NULL,
  corpo         MEDIUMTEXT NOT NULL,
  genere        VARCHAR(32) NOT NULL DEFAULT 'generico',
  priorita      TINYINT UNSIGNED NOT NULL DEFAULT 5,
  tentativi     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  prossimo_il   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  inviato_il    DATETIME DEFAULT NULL,
  rinunciato_il DATETIME DEFAULT NULL,
  ultimo_errore VARCHAR(255) DEFAULT NULL,
  creato_il     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_da_fare (inviato_il, rinunciato_il, prossimo_il, priorita),
  KEY k_inviato (inviato_il)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE sdb_giocatore
  ADD COLUMN IF NOT EXISTS email_verificata TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS gettone_verifica CHAR(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS gettone_scade DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS verificata_il DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS ip_registrazione VARCHAR(45) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS sospeso_fino DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS nota_arbitro VARCHAR(255) DEFAULT NULL,
  ADD INDEX IF NOT EXISTS k_gettone (gettone_verifica);

-- I giocatori che c'erano prima di questa regola non vanno buttati fuori dal
-- gioco retroattivamente: sono verificati d'ufficio.
UPDATE sdb_giocatore SET email_verificata = 1, verificata_il = NOW()
 WHERE email_verificata = 0 AND creato < NOW();
