-- 0001 — Il mondo: anagrafiche, stato delle nazioni, stato globale, log del tick.
-- Stanza dei Bottoni. Prefisso: sdb_

CREATE TABLE IF NOT EXISTS sdb_regione (
  id        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codice    VARCHAR(16)  NOT NULL,
  nome      VARCHAR(64)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_regione_codice (codice)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_ideologia (
  id        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codice    VARCHAR(32)  NOT NULL,
  nome      VARCHAR(64)  NOT NULL,
  -- Le ideologie determinano etica, ambizione e attitudini reciproche
  -- (CyberJudas, glossario delle ideologie).
  etica_base      TINYINT UNSIGNED NOT NULL DEFAULT 3,
  ambizione_base  TINYINT UNSIGNED NOT NULL DEFAULT 3,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ideologia_codice (codice)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_scenario (
  id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codice     VARCHAR(32)  NOT NULL,
  nome       VARCHAR(96)  NOT NULL,
  ipotesi    TEXT         NULL,     -- formato Shadow President: Ipotesi...
  razionale  TEXT         NULL,     -- ...+ Razionale
  data_inizio DATE        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_scenario_codice (codice)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_nazione (
  id            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codice        CHAR(3)      NOT NULL,            -- ISO 3166-1 alpha-3
  nome          VARCHAR(96)  NOT NULL,
  regione_id    SMALLINT UNSIGNED NOT NULL,
  ideologia_id  SMALLINT UNSIGNED NOT NULL,
  area_km2      BIGINT UNSIGNED NOT NULL DEFAULT 0,
  giocabile     TINYINT(1)   NOT NULL DEFAULT 0,
  -- Indici FABBRICATI: dichiarati in calibrazione/, non derivati da fonti.
  valore_prestigio  SMALLINT UNSIGNED NOT NULL DEFAULT 1,   -- 1..2000
  valore_strategico TINYINT  UNSIGNED NOT NULL DEFAULT 0,   -- 0..100
  maturita          SMALLINT UNSIGNED NOT NULL DEFAULT 100, -- 0..255, stato di diritto
  PRIMARY KEY (id),
  UNIQUE KEY uq_nazione_codice (codice),
  KEY ix_nazione_regione (regione_id),
  CONSTRAINT fk_nazione_regione   FOREIGN KEY (regione_id)   REFERENCES sdb_regione(id),
  CONSTRAINT fk_nazione_ideologia FOREIGN KEY (ideologia_id) REFERENCES sdb_ideologia(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lo stato vero del mondo. Una riga per nazione per tick: ~190 x 1100 per era,
-- cioè niente, e in cambio abbiamo tutta la cronologia storica gratis.
CREATE TABLE IF NOT EXISTS sdb_nazione_stato (
  nazione_id  SMALLINT UNSIGNED NOT NULL,
  tick        INT UNSIGNED      NOT NULL,

  -- economia
  popolazione       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  pil               DECIMAL(18,2)   NOT NULL DEFAULT 0,   -- milioni di unità
  crescita_pil      DECIMAL(6,4)    NOT NULL DEFAULT 0,   -- frazione annua
  pil_pro_capite    DECIMAL(12,2)   NOT NULL DEFAULT 0,
  consumo_pro_capite DECIMAL(12,2)  NOT NULL DEFAULT 0,
  quota_consumi     DECIMAL(5,4)    NOT NULL DEFAULT 0.6000,
  quota_investimenti DECIMAL(5,4)   NOT NULL DEFAULT 0.2000,
  quota_militare    DECIMAL(5,4)    NOT NULL DEFAULT 0.2000,
  saldo_bilancio    DECIMAL(18,2)   NOT NULL DEFAULT 0,
  debito_su_pil     DECIMAL(6,3)    NOT NULL DEFAULT 0,
  riserve           DECIMAL(18,2)   NOT NULL DEFAULT 0,

  -- scale ordinali di Shadow President
  influenza_totale  DECIMAL(7,4)    NOT NULL DEFAULT 0,   -- % del totale mondiale
  etica             TINYINT UNSIGNED NOT NULL DEFAULT 3,  -- 1..6
  ambizione         TINYINT UNSIGNED NOT NULL DEFAULT 3,  -- 1..6
  qualita_vita      TINYINT UNSIGNED NOT NULL DEFAULT 5,  -- 1..10
  stato_polizia     TINYINT UNSIGNED NOT NULL DEFAULT 2,  -- 1..4
  net_peace         TINYINT UNSIGNED NOT NULL DEFAULT 2,  -- 1..6
  legittimita       DECIMAL(6,2)    NOT NULL DEFAULT 50,  -- 0..100
  aspettativa       DECIMAL(6,4)    NOT NULL DEFAULT 0.03,
  clamore_sociale   DECIMAL(5,2)    NOT NULL DEFAULT 0,   -- %
  orientamento      SMALLINT        NOT NULL DEFAULT 0,   -- -128 sinistra .. +128 destra

  -- le sei ansie (SP)
  ansia_economia    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ansia_stranieri   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ansia_governo     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ansia_militare    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ansia_nucleare    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ansia_rango       TINYINT UNSIGNED NOT NULL DEFAULT 0,

  -- le cinque fami (SP)
  fame_sociale      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fame_economica    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fame_intelligence TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fame_militare     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fame_nucleare     TINYINT UNSIGNED NOT NULL DEFAULT 0,

  -- militare
  soldati           INT UNSIGNED    NOT NULL DEFAULT 0,
  equipaggiamento   DECIMAL(14,2)   NOT NULL DEFAULT 0,
  potenza_militare  DECIMAL(14,4)   NOT NULL DEFAULT 0,
  postura_nucleare  TINYINT UNSIGNED NOT NULL DEFAULT 1,  -- 1..7 (city graphic di SP)
  forza_insorti     DECIMAL(14,4)   NOT NULL DEFAULT 0,
  orientamento_insorti SMALLINT     NOT NULL DEFAULT 0,

  -- contemporanee
  dip_energia       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  dip_cibo          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  dip_finanza       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  dip_tecnologia    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  controllo_info    TINYINT UNSIGNED NOT NULL DEFAULT 50,
  cyber_offesa      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  cyber_difesa      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  capacita_attribuzione TINYINT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (nazione_id, tick),
  KEY ix_stato_tick (tick),
  CONSTRAINT fk_stato_nazione FOREIGN KEY (nazione_id) REFERENCES sdb_nazione(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_mondo_stato (
  tick              INT UNSIGNED  NOT NULL,
  data_gioco        DATE          NOT NULL,
  livello_pace      TINYINT UNSIGNED NOT NULL DEFAULT 2,  -- 1..6 World Peace Level
  nastiness         DECIMAL(6,2)  NOT NULL DEFAULT 0,
  orologio          TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- 0..100
  prezzo_energia    DECIMAL(10,2) NOT NULL DEFAULT 100,
  prezzo_cibo       DECIMAL(10,2) NOT NULL DEFAULT 100,
  PRIMARY KEY (tick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ogni fase registra qui: seme, durata, esito. Un tick è rieseguibile e
-- riproducibile, e questo è il registro che lo dimostra.
CREATE TABLE IF NOT EXISTS sdb_tick_log (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tick          INT UNSIGNED    NOT NULL,
  fase          VARCHAR(32)     NOT NULL,
  iniziata      DATETIME        NOT NULL,
  durata_ms     INT UNSIGNED    NOT NULL DEFAULT 0,
  seme          BIGINT          NOT NULL,
  calibrazione  VARCHAR(32)     NOT NULL,
  esito         VARCHAR(16)     NOT NULL DEFAULT 'ok',
  note          TEXT            NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tick_fase (tick, fase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
