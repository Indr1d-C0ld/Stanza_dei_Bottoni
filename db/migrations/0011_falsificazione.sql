-- 0011 — I messaggi falsificati in transito.
--
-- Finora un messaggio partiva e arrivava nello stesso tick, e veniva
-- intercettato dalla fase 08 solo dopo che il destinatario lo aveva gia'
-- letto. Utile per spiare, inutile per manomettere: non esisteva nessuna
-- finestra in cui il messaggio fosse in mano a un terzo e non ancora in mano a
-- chi doveva riceverlo. Il transito la crea.
--
-- La manipolazione non si improvvisa sul singolo messaggio: si ordina prima,
-- contro un corrispondente preciso, e resta appostata finche' i segnali non le
-- portano qualcosa. E' cosi' che funziona davvero, ed e' anche l'unico modo di
-- farla stare in un gioco dove i giocatori non sono collegati nello stesso
-- momento.

ALTER TABLE sdb_messaggio
  ADD COLUMN IF NOT EXISTS testo_originale TEXT DEFAULT NULL AFTER testo,
  ADD COLUMN IF NOT EXISTS falsificato_da SMALLINT UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS soppresso TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS manomissione_sospetta TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS sdb_manipolazione (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nazione_id    SMALLINT UNSIGNED NOT NULL,   -- chi falsifica
  ordinata_da   INT UNSIGNED NOT NULL,        -- la poltrona che ha firmato
  da_nazione_id SMALLINT UNSIGNED NOT NULL,   -- di chi si manomettono le parole
  a_nazione_id  SMALLINT UNSIGNED DEFAULT NULL, -- verso chi (NULL = chiunque)
  modo          ENUM('sostituisci','inserisci','sopprimi') NOT NULL,
  testo         TEXT DEFAULT NULL,            -- preparato in anticipo
  usi_max       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  usi           TINYINT UNSIGNED NOT NULL DEFAULT 0,
  aperta_tick   INT UNSIGNED NOT NULL,
  scade_tick    INT UNSIGNED NOT NULL,
  stato         ENUM('attiva','esaurita','scaduta','scoperta') NOT NULL DEFAULT 'attiva',
  KEY k_nazione (nazione_id, stato),
  KEY k_bersaglio (da_nazione_id, a_nazione_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
