-- 0008 — Le agende private e il reclutamento.

CREATE TABLE IF NOT EXISTS sdb_agenda (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  giocatore_id  INT UNSIGNED NOT NULL,
  poltrona_id   INT UNSIGNED NOT NULL,
  codice        VARCHAR(32)  NOT NULL,
  -- il parametro che la rende concreta: un paese, una soglia
  bersaglio_id  SMALLINT UNSIGNED NULL,
  soglia        DECIMAL(8,3) NULL,
  assegnata_tick INT UNSIGNED NOT NULL,
  stato         ENUM('aperta','riuscita','fallita') NOT NULL DEFAULT 'aperta',
  chiusa_tick   INT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY ix_agenda_giocatore (giocatore_id, stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- L'offerta di reclutamento: il momento in cui un servizio straniero prova a
-- comprarsi una poltrona. Non e' una carta pescata: e' una proposta, con un
-- prezzo, che si puo' accettare o rifiutare — e rifiutarla lascia in mano una
-- prova.
CREATE TABLE IF NOT EXISTS sdb_offerta (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  da_nazione_id  SMALLINT UNSIGNED NOT NULL,
  da_poltrona    INT UNSIGNED NOT NULL,
  a_poltrona     INT UNSIGNED NOT NULL,
  testo          TEXT         NOT NULL,
  -- che cosa si mette sul piatto
  offre_denaro   TINYINT(1)   NOT NULL DEFAULT 0,
  offre_dossier  TINYINT(1)   NOT NULL DEFAULT 0,
  offre_appoggio TINYINT(1)   NOT NULL DEFAULT 0,
  stato          ENUM('aperta','accettata','rifiutata','denunciata','scaduta')
                 NOT NULL DEFAULT 'aperta',
  tick           INT UNSIGNED NOT NULL,
  scade_tick     INT UNSIGNED NOT NULL,
  risposta_tick  INT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY ix_offerta_dest (a_poltrona, stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sdb_poltrona
  ADD COLUMN IF NOT EXISTS reclutata_tick INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS sospettata     TINYINT(1) NOT NULL DEFAULT 0;
