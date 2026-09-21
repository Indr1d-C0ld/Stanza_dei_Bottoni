-- 0009 — Le crisi.
--
-- "Balance of Power si perde quasi sempre in una crisi, o facendo saltare il
-- mondo o cedendo." La scala e' quella dell'escalation dichiarata in docs/01:
-- si sale un gradino alla volta, a turno, finche' qualcuno molla.

CREATE TABLE IF NOT EXISTS sdb_crisi (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id      BIGINT UNSIGNED NULL,          -- l'azione contestata
  sfidante_id    SMALLINT UNSIGNED NOT NULL,
  sfidato_id     SMALLINT UNSIGNED NOT NULL,
  oggetto_id     SMALLINT UNSIGNED NULL,        -- il paese su cui si litiga
  livello        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  -- di chi e' il turno: chi deve decidere se cedere o salire
  tocca_a        ENUM('sfidante','sfidato') NOT NULL DEFAULT 'sfidato',
  stato          ENUM('aperta','ceduto_sfidante','ceduto_sfidato','incidente','guerra')
                 NOT NULL DEFAULT 'aperta',
  posta_sfidante DECIMAL(8,2) NOT NULL DEFAULT 0,
  posta_sfidato  DECIMAL(8,2) NOT NULL DEFAULT 0,
  aperta_tick    INT UNSIGNED NOT NULL,
  ultimo_tick    INT UNSIGNED NOT NULL,
  scade_tick     INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY ix_crisi_aperte (stato, tocca_a)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_crisi_passo (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  crisi_id     INT UNSIGNED NOT NULL,
  tick         INT UNSIGNED NOT NULL,
  attore       ENUM('sfidante','sfidato') NOT NULL,
  azione       ENUM('apre','scala','cede','media') NOT NULL,
  livello_dopo TINYINT UNSIGNED NOT NULL,
  nota         VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY ix_passo_crisi (crisi_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
