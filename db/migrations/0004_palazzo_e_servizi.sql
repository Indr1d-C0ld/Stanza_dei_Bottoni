-- 0004 — Quel che il mondo in memoria ha imparato a tenere: gabinetti,
-- servizi di intelligence, guerre, feed pubblico.

-- Il modello dell'intelligence e' FATTORIZZATO (vedi docs/10): capacita' per
-- disciplina, presenza per bersaglio, e le due si moltiplicano.
CREATE TABLE IF NOT EXISTS sdb_capacita_intel (
  nazione_id  SMALLINT UNSIGNED NOT NULL,
  disciplina  ENUM('osint','humint','sigint','imint','cyber','finint') NOT NULL,
  livello     DECIMAL(5,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (nazione_id, disciplina),
  CONSTRAINT fk_cap_nazione FOREIGN KEY (nazione_id) REFERENCES sdb_nazione(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_presenza_intel (
  nazione_id   SMALLINT UNSIGNED NOT NULL,
  bersaglio_id SMALLINT UNSIGNED NOT NULL,
  livello      DECIMAL(5,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (nazione_id, bersaglio_id),
  KEY ix_presenza_bersaglio (bersaglio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Il palazzo. Le poltrone esistono solo per le potenze giocabili.
CREATE TABLE IF NOT EXISTS sdb_gabinetto (
  nazione_id      SMALLINT UNSIGNED NOT NULL,
  coesione        DECIMAL(6,2) NOT NULL DEFAULT 60,
  ultimo_rimpasto INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (nazione_id),
  CONSTRAINT fk_gab_nazione FOREIGN KEY (nazione_id) REFERENCES sdb_nazione(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_poltrona (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nazione_id     SMALLINT UNSIGNED NOT NULL,
  ruolo          VARCHAR(24) NOT NULL,
  -- il titolare: personaggio fittizio, generato
  nome           VARCHAR(96) NOT NULL,
  eta            TINYINT UNSIGNED NOT NULL DEFAULT 55,
  etica          TINYINT UNSIGNED NOT NULL DEFAULT 3,
  ambizione      TINYINT UNSIGNED NOT NULL DEFAULT 3,
  competenza     DECIMAL(4,3) NOT NULL DEFAULT 0.500,
  vulnerabilita  TEXT NULL,             -- il materiale dei dossier
  potere         DECIMAL(6,2) NOT NULL DEFAULT 50,
  lealta         DECIMAL(6,2) NOT NULL DEFAULT 70,
  -- quando ci saranno i giocatori: chi la occupa e chi l'ha comprata
  giocatore_id   INT UNSIGNED NULL,
  reclutata_da   SMALLINT UNSIGNED NULL,
  insediato_tick INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_poltrona (nazione_id, ruolo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_fazione (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nazione_id SMALLINT UNSIGNED NOT NULL,
  nome       VARCHAR(64)  NOT NULL,
  forza      DECIMAL(6,2) NOT NULL DEFAULT 25,
  favore     DECIMAL(6,2) NOT NULL DEFAULT 0,
  agenda     VARCHAR(96)  NOT NULL,
  PRIMARY KEY (id),
  KEY ix_fazione_nazione (nazione_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_guerra (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  aggressore_id SMALLINT UNSIGNED NOT NULL,
  difensore_id  SMALLINT UNSIGNED NOT NULL,
  inizio_tick   INT UNSIGNED NOT NULL,
  fine_tick     INT UNSIGNED NULL,
  esito         VARCHAR(24) NULL,
  morti         BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_guerra_aperta (fine_tick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Il feed pubblico: cio' che il mondo sa. Ci entra solo cio' che ha superato
-- il vaglio della fase 09.
CREATE TABLE IF NOT EXISTS sdb_notizia (
  id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tick     INT UNSIGNED NOT NULL,
  genere   VARCHAR(32)  NOT NULL,
  dati     TEXT         NOT NULL,      -- JSON
  PRIMARY KEY (id),
  KEY ix_notizia_tick (tick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggiunte agli eventi: la falsa bandiera, e chi ciascuno CREDE sia il mandante.
ALTER TABLE sdb_evento
  ADD COLUMN IF NOT EXISTS falsa_bandiera_id SMALLINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS qualita_falso DECIMAL(4,3) NOT NULL DEFAULT 0;

ALTER TABLE sdb_conoscenza
  ADD COLUMN IF NOT EXISTS accusato_id SMALLINT UNSIGNED NULL;
