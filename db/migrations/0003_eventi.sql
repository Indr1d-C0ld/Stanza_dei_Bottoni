-- 0003 — Gli eventi in volo, la compartimentazione, la conoscenza.
-- È il cuore del gioco: un'azione esiste nel database prima di realizzarsi,
-- e la domanda è chi la vede, quando, e con quanta precisione.

CREATE TABLE IF NOT EXISTS sdb_evento (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dominio          ENUM('soc','eco','info','int','mil','nuc') NOT NULL,
  verbo            VARCHAR(48)     NOT NULL,
  mandante_id      SMALLINT UNSIGNED NOT NULL,          -- chi ha ordinato (vero)
  esecutore_id     SMALLINT UNSIGNED NULL,              -- proxy, se c'e'
  bersaglio_id     SMALLINT UNSIGNED NULL,
  bersaglio_rif    VARCHAR(64)     NULL,                -- persona, sede, canale
  intensita        TINYINT UNSIGNED NOT NULL DEFAULT 1, -- il "rocker" di SP
  copertura        TINYINT UNSIGNED NOT NULL DEFAULT 0, -- investimento in OPSEC
  impronta         TINYINT UNSIGNED NOT NULL DEFAULT 50,
  creato_tick      INT UNSIGNED    NOT NULL,
  maturazione_tick INT UNSIGNED    NOT NULL,
  reversibilita    TINYINT UNSIGNED NOT NULL DEFAULT 100, -- decade con la maturazione
  danno_base       SMALLINT        NOT NULL DEFAULT 0,  -- Hurt: -127..+127
  attribuzione_vera TINYINT UNSIGNED NOT NULL DEFAULT 100,
  stato            ENUM('in_volo','scoperto','fermato','realizzato','depistaggio')
                   NOT NULL DEFAULT 'in_volo',
  PRIMARY KEY (id),
  KEY ix_evento_maturazione (maturazione_tick, stato),
  KEY ix_evento_bersaglio (bersaglio_id),
  KEY ix_evento_mandante (mandante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LA TABELLA PIU' IMPORTANTE DEL GIOCO.
-- È la lista dei sospetti quando qualcosa trapela: da qui discende tutta la
-- caccia alla talpa. Compartimentare di meno significa rinunciare alla
-- competenza delle poltrone; compartimentare di più significa non sapere mai
-- chi ti ha tradito.
CREATE TABLE IF NOT EXISTS sdb_evento_accesso (
  evento_id    BIGINT UNSIGNED NOT NULL,
  poltrona_id  INT UNSIGNED    NOT NULL,
  concesso_tick INT UNSIGNED   NOT NULL,
  PRIMARY KEY (evento_id, poltrona_id),
  KEY ix_accesso_poltrona (poltrona_id),
  CONSTRAINT fk_accesso_evento FOREIGN KEY (evento_id) REFERENCES sdb_evento(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cosa sa un osservatore di un evento. I livelli sono MONOTONI: la conoscenza
-- non regredisce mai.
--   1 esistenza  2 dominio e regione  3 bersaglio  4 attribuzione
CREATE TABLE IF NOT EXISTS sdb_conoscenza (
  osservatore_id  SMALLINT UNSIGNED NOT NULL,
  evento_id       BIGINT UNSIGNED   NOT NULL,
  livello         TINYINT UNSIGNED  NOT NULL DEFAULT 1,
  primo_tick      INT UNSIGNED      NOT NULL,
  confidenza      TINYINT UNSIGNED  NOT NULL DEFAULT 50,
  rapporto_id     BIGINT UNSIGNED   NULL,
  PRIMARY KEY (osservatore_id, evento_id),
  KEY ix_conoscenza_evento (evento_id),
  CONSTRAINT fk_conoscenza_evento FOREIGN KEY (evento_id) REFERENCES sdb_evento(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un rapporto è un'affermazione sul mondo, con provenienza. Può essere falso:
-- falsificare non serve a nascondere cio' che hai fatto, serve a far attribuire
-- a un terzo cio' che hai fatto.
CREATE TABLE IF NOT EXISTS sdb_rapporto (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  proprietario_id SMALLINT UNSIGNED NOT NULL,
  tipo            VARCHAR(32)     NOT NULL,
  riferimento     VARCHAR(64)     NULL,
  valore          TEXT            NULL,
  fonte_dichiarata ENUM('osint','humint','sigint','imint','cyber','finint') NOT NULL,
  raccolto_tick   INT UNSIGNED    NOT NULL,
  accuratezza     TINYINT UNSIGNED NOT NULL DEFAULT 50,
  falsificato     TINYINT(1)      NOT NULL DEFAULT 0,
  falsificatore_id SMALLINT UNSIGNED NULL,
  qualita_falso   TINYINT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY ix_rapporto_proprietario (proprietario_id, raccolto_tick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
