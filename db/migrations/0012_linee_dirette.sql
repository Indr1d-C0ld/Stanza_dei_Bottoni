-- 0012 — Le linee dirette fra potenze.
--
-- Il telefono rosso. Due paesi concordano un canale dedicato: veloce, e che i
-- segnali di terzi non toccano. Non e' un regalo, perche' ha tre costi veri.
--
-- Primo: si stabilisce in due. Serve che l'altra parte accetti, e accettare e'
-- un atto politico visibile.
--
-- Secondo: e' pubblica. L'esistenza di una linea diretta fra due capitali e'
-- un fatto diplomatico che tutti vedono, e dice al mondo chi parla con chi.
--
-- Terzo, ed e' il piu' bello: la linea e' al sicuro dai segnali, non dalle
-- persone. Chi ha un uomo dentro uno dei due gabinetti legge tutto quello che
-- ci passa, e lo legge meglio di come leggerebbe qualunque cifrato. Il canale
-- piu' sicuro del gioco e' anche quello che premia di piu' chi ha saputo
-- reclutare.

CREATE TABLE IF NOT EXISTS sdb_linea (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  a_nazione_id   SMALLINT UNSIGNED NOT NULL,
  b_nazione_id   SMALLINT UNSIGNED NOT NULL,
  proposta_da    INT UNSIGNED NOT NULL,        -- la poltrona che ha proposto
  accettata_da   INT UNSIGNED DEFAULT NULL,    -- la poltrona che ha accettato
  stato          ENUM('proposta','attiva','rifiutata','revocata') NOT NULL DEFAULT 'proposta',
  proposta_tick  INT UNSIGNED NOT NULL,
  attivata_tick  INT UNSIGNED DEFAULT NULL,
  chiusa_tick    INT UNSIGNED DEFAULT NULL,
  UNIQUE KEY k_coppia (a_nazione_id, b_nazione_id),
  KEY k_stato (stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
