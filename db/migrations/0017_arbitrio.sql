-- 0017 — Il banco dell'arbitro.
--
-- Due cose che finora si facevano solo da riga di comando, e una che non si
-- poteva fare affatto.
--
-- Il REGISTRO tiene nota di ogni atto d'arbitrio: chi ha sospeso chi, chi ha
-- mosso quale leva e da che valore a che valore. Non e' burocrazia: in un gioco
-- dove l'arbitro puo' cambiare le regole del mondo mentre si gioca, un atto non
-- tracciato e' indistinguibile da un favore.
--
-- Le LEVE sono le sovrascritture della calibrazione. I file in calibrazione/
-- restano la verita' di partenza e stanno in git; qui dentro ci va solo quel
-- che l'arbitro ha cambiato a mondo acceso, con il valore di prima accanto,
-- cosi' si puo' sempre tornare indietro e si vede sempre cosa e' stato toccato.

CREATE TABLE IF NOT EXISTS sdb_leva (
  chiave        VARCHAR(120) NOT NULL PRIMARY KEY,
  valore        TEXT NOT NULL,            -- JSON: numero, stringa, o struttura
  valore_prima  TEXT DEFAULT NULL,
  cambiata_da   INT UNSIGNED NOT NULL,
  cambiata_il   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nota          VARCHAR(190) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sdb_atto_arbitro (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  arbitro_id INT UNSIGNED NOT NULL,
  genere     VARCHAR(32) NOT NULL,
  bersaglio  VARCHAR(190) NOT NULL DEFAULT '',
  dettaglio  TEXT NOT NULL,
  tick       INT UNSIGNED NOT NULL DEFAULT 0,
  quando     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_quando (quando)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
