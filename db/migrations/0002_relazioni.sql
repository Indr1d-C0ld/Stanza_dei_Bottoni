-- 0002 — Le matrici: relazioni, commercio, copertura di intelligence.
-- Le matrici NON si salvano per tick (esploderebbero): stato corrente più
-- registro delle variazioni.

CREATE TABLE IF NOT EXISTS sdb_relazione (
  da_nazione_id   SMALLINT UNSIGNED NOT NULL,
  a_nazione_id    SMALLINT UNSIGNED NOT NULL,
  -- Country Interaction Mood (SP): 1 Armonia di interessi .. 7 Inimicizia
  umore           TINYINT UNSIGNED NOT NULL DEFAULT 4,
  -- affinità diplomatica (BoP): -127 .. +127
  affinita        SMALLINT NOT NULL DEFAULT 0,
  -- obbligo di trattato (BoP): 0/16/32/64/96/128
  obbligo         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  -- sfera di influenza (DontMess): 1..15
  sfera           TINYINT UNSIGNED NOT NULL DEFAULT 1,
  aggiornata_tick INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (da_nazione_id, a_nazione_id),
  KEY ix_relazione_a (a_nazione_id),
  CONSTRAINT fk_rel_da FOREIGN KEY (da_nazione_id) REFERENCES sdb_nazione(id),
  CONSTRAINT fk_rel_a  FOREIGN KEY (a_nazione_id)  REFERENCES sdb_nazione(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_relazione_variazione (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tick          INT UNSIGNED    NOT NULL,
  da_nazione_id SMALLINT UNSIGNED NOT NULL,
  a_nazione_id  SMALLINT UNSIGNED NOT NULL,
  campo         VARCHAR(24)     NOT NULL,
  valore_prima  INT             NOT NULL,
  valore_dopo   INT             NOT NULL,
  evento_id     BIGINT UNSIGNED NULL,     -- la causa, se nota
  PRIMARY KEY (id),
  KEY ix_relvar_tick (tick),
  KEY ix_relvar_coppia (da_nazione_id, a_nazione_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_flusso_commerciale (
  da_nazione_id  SMALLINT UNSIGNED NOT NULL,
  a_nazione_id   SMALLINT UNSIGNED NOT NULL,
  settore        VARCHAR(24)   NOT NULL,
  volume         DECIMAL(18,2) NOT NULL DEFAULT 0,
  -- quanto è facile sostituire questo flusso: decide se una sanzione morde
  -- o è teatro. 0 = insostituibile, 100 = sostituibile domani.
  sostituibilita TINYINT UNSIGNED NOT NULL DEFAULT 50,
  aggiornato_tick INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (da_nazione_id, a_nazione_id, settore),
  KEY ix_flusso_a (a_nazione_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_copertura_intel (
  proprietario_id SMALLINT UNSIGNED NOT NULL,
  bersaglio_id    SMALLINT UNSIGNED NOT NULL,
  disciplina      ENUM('osint','humint','sigint','imint','cyber','finint') NOT NULL,
  livello         TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- 0..100
  mantenimento    DECIMAL(12,2)    NOT NULL DEFAULT 0,
  aggiornata_tick INT UNSIGNED     NOT NULL DEFAULT 0,
  PRIMARY KEY (proprietario_id, bersaglio_id, disciplina),
  KEY ix_copertura_bersaglio (bersaglio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
