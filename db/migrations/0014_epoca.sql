-- 0014 — L'epoca, e il suo bilancio.
--
-- Una partita persistente senza fine non e' una partita: e' un acquario. Serve
-- un momento in cui si conta, e soprattutto un momento in cui **si scopre**.
--
-- Il bilancio d'epoca fa due cose, e la seconda vale piu' della prima.
--
-- Conta: l'interesse nazionale che hai servito, le agende private che hai
-- portato a casa, e quel che il paese ha perso mentre ci sedevi.
--
-- E poi rivela. Tutte le operazioni coperte con il loro vero mandante, tutte
-- le talpe con il loro padrone, tutti i messaggi riscritti con il loro
-- originale accanto. Per un'intera epoca il gioco e' stato un gioco di cose
-- non dette; alla fine si dicono tutte insieme, e quello e' il momento in cui
-- si capisce che partita si e' giocata davvero.

CREATE TABLE IF NOT EXISTS sdb_epoca (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  numero      SMALLINT UNSIGNED NOT NULL,
  titolo      VARCHAR(96) NOT NULL DEFAULT '',
  inizio_tick INT UNSIGNED NOT NULL,
  durata_tick INT UNSIGNED NOT NULL,
  fine_tick   INT UNSIGNED DEFAULT NULL,
  stato       ENUM('in_corso','chiusa') NOT NULL DEFAULT 'in_corso',
  UNIQUE KEY k_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sdb_punteggio (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  epoca_id     INT UNSIGNED NOT NULL,
  giocatore_id INT UNSIGNED NOT NULL,
  poltrona_id  INT UNSIGNED NOT NULL,
  nazione_id   SMALLINT UNSIGNED NOT NULL,
  voci         TEXT NOT NULL,          -- JSON: la scomposizione, voce per voce
  totale       DECIMAL(8,2) NOT NULL,
  UNIQUE KEY k_epoca_giocatore (epoca_id, giocatore_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quel che si scopre solo alla fine.
CREATE TABLE IF NOT EXISTS sdb_rivelazione (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  epoca_id  INT UNSIGNED NOT NULL,
  genere    VARCHAR(32) NOT NULL,      -- operazione, talpa, falso, crisi
  tick      INT UNSIGNED NOT NULL,
  titolo    VARCHAR(190) NOT NULL,
  dettaglio TEXT NOT NULL,
  KEY k_epoca (epoca_id, genere)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
