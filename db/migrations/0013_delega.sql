-- 0013 — La delega, e l'assenza.
--
-- Un gioco persistente a tick va avanti anche quando i giocatori dormono. Se
-- una poltrona presidiata da una persona che non torna blocca le crisi, le
-- controfirme e le risposte diplomatiche, il mondo si ferma per tutti gli
-- altri — che e' il modo piu' sicuro di uccidere una partita.
--
-- Due rimedi, e fanno cose diverse.
--
-- La DELEGA e' volontaria: affidi la tua poltrona a un altro giocatore mentre
-- non ci sei. Agisce in tuo nome e la firma resta agli atti, la sua accanto
-- alla tua. Puo' fare per te quello che avresti fatto tu — e anche quello che
-- non avresti mai fatto. E' un rischio che si sceglie.
--
-- L'ASSENZA e' automatica: dopo un tot di tick senza che nessuno tocchi la
-- poltrona, l'apparato riprende in mano il paese e decide al posto tuo, come
-- farebbe in un gabinetto che non ha mai avuto un titolare. Al rientro trovi
-- il conto di quel che e' stato fatto mentre non c'eri.

ALTER TABLE sdb_poltrona
  ADD COLUMN IF NOT EXISTS delega_a INT UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS delega_dal_tick INT UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS ultimo_tick_attivo INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE sdb_ordine
  ADD COLUMN IF NOT EXISTS firmato_per_delega_da INT UNSIGNED DEFAULT NULL;

-- Quel che e' successo mentre il titolare non c'era, per poterglielo dire.
CREATE TABLE IF NOT EXISTS sdb_assenza_fatto (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  poltrona_id INT UNSIGNED NOT NULL,
  tick        INT UNSIGNED NOT NULL,
  chi         ENUM('apparato','delegato') NOT NULL,
  cosa        VARCHAR(48) NOT NULL,
  dettaglio   VARCHAR(255) NOT NULL DEFAULT '',
  visto       TINYINT(1) NOT NULL DEFAULT 0,
  KEY k_poltrona (poltrona_id, visto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
