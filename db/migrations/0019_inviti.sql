-- 0019 — Gli inviti.
--
-- Finora chiunque trovasse l'indirizzo poteva registrarsi e prendersi una
-- poltrona in un gabinetto. Per una partita fra qualche decina di persone che
-- si tengono il posto per mesi, non e' il modello giusto: non e' un forum dove
-- chi arriva legge e se ne va, e' un tavolo dove un posto occupato male rovina
-- la partita a tutti gli altri.
--
-- Tre modi, e si sceglie dal banco dell'arbitro:
--   aperte  — chiunque, com'era prima;
--   invito  — serve un codice, che l'arbitro consegna a chi vuole lui;
--   chiuse  — nessuno, e la pagina lo dice invece di far perdere tempo.
--
-- Un codice vale UNA volta sola. Non c'e' un codice buono per tutti: quello
-- sarebbe una registrazione aperta scritta peggio.

CREATE TABLE IF NOT EXISTS sdb_invito (
  codice     VARCHAR(24) NOT NULL PRIMARY KEY,
  creato_da  INT UNSIGNED NOT NULL,
  creato_il  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  scade_il   DATETIME DEFAULT NULL,
  nota       VARCHAR(120) NOT NULL DEFAULT '',
  usato_da   INT UNSIGNED DEFAULT NULL,
  usato_il   DATETIME DEFAULT NULL,
  KEY k_liberi (usato_da, scade_il)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
