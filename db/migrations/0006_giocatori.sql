-- 0006 — I giocatori: chi sono, che poltrona occupano, che ordini impartiscono.

CREATE TABLE IF NOT EXISTS sdb_giocatore (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome           VARCHAR(48)  NOT NULL,          -- il nome in gioco
  email          VARCHAR(190) NOT NULL,
  hash_password  VARCHAR(255) NOT NULL,
  ruolo          ENUM('giocatore','arbitro') NOT NULL DEFAULT 'giocatore',
  -- L'integrita' di Crawford, ma applicata alle PERSONE: segue il giocatore
  -- quando cambia poltrona e quando cambia paese. E' la memoria sociale del
  -- mondo, ed e' cio' che rende persistente un mondo persistente.
  integrita      DECIMAL(6,2) NOT NULL DEFAULT 128,
  creato         DATETIME     NOT NULL,
  ultimo_accesso DATETIME     NULL,
  attivo         TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_giocatore_email (email),
  UNIQUE KEY uq_giocatore_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gli ordini impartiti fra un tick e l'altro. La fase 00 li raccoglie e li
-- trasforma in eventi in volo: e' il punto esatto in cui la volonta' di una
-- persona entra nel modello.
CREATE TABLE IF NOT EXISTS sdb_ordine (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  giocatore_id    INT UNSIGNED    NOT NULL,
  poltrona_id     INT UNSIGNED    NOT NULL,
  nazione_id      SMALLINT UNSIGNED NOT NULL,
  verbo           VARCHAR(48)     NOT NULL,
  bersaglio_id    SMALLINT UNSIGNED NOT NULL,
  intensita       TINYINT UNSIGNED NOT NULL DEFAULT 50,   -- 0..100
  copertura       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  falsa_bandiera_id SMALLINT UNSIGNED NULL,
  -- La controfirma: certe cose non le puo' decidere una poltrona sola.
  richiede_controfirma VARCHAR(24) NULL,          -- il ruolo che deve firmare
  controfirmato_da     INT UNSIGNED NULL,         -- il giocatore che ha firmato
  controfirmato_il     DATETIME    NULL,
  stato           ENUM('in_attesa','firmato','eseguito','annullato','scaduto')
                  NOT NULL DEFAULT 'in_attesa',
  creato_tick     INT UNSIGNED    NOT NULL,
  scade_tick      INT UNSIGNED    NOT NULL,
  creato          DATETIME        NOT NULL,
  PRIMARY KEY (id),
  KEY ix_ordine_stato (stato, creato_tick),
  KEY ix_ordine_nazione (nazione_id, stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La messaggistica fra poltrone. Il contenuto e' in chiaro nel database
-- (l'arbitro deve poter leggere): la sicurezza e' una proprieta' del CANALE,
-- cioe' di quanto e' difficile intercettarlo, non della cifratura a riposo.
CREATE TABLE IF NOT EXISTS sdb_messaggio (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  da_poltrona   INT UNSIGNED NOT NULL,
  a_poltrona    INT UNSIGNED NOT NULL,
  testo         TEXT         NOT NULL,
  -- 1 canale aperto · 2 diplomatico · 3 cifrato · 4 corriere
  sicurezza     TINYINT UNSIGNED NOT NULL DEFAULT 2,
  tick          INT UNSIGNED NOT NULL,
  inviato       DATETIME     NOT NULL,
  letto         TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_messaggio_dest (a_poltrona, tick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chi ha letto cosa senza esserne il destinatario.
CREATE TABLE IF NOT EXISTS sdb_intercettazione (
  messaggio_id  BIGINT UNSIGNED NOT NULL,
  nazione_id    SMALLINT UNSIGNED NOT NULL,
  livello       ENUM('metadati','parziale','integrale') NOT NULL,
  tick          INT UNSIGNED NOT NULL,
  PRIMARY KEY (messaggio_id, nazione_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
