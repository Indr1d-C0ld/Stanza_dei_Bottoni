-- 0007 — Separare l'invio dalla consegna.
--
-- Un solo campo "tick" faceva due mestieri: quando il messaggio parte e quando
-- arriva. Per il corriere, che ci mette due giri, i due momenti sono diversi —
-- e la fase 08 cercava i messaggi nel momento sbagliato, quindi non intercettava
-- mai nulla.

ALTER TABLE sdb_messaggio
  ADD COLUMN IF NOT EXISTS tick_invio INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS intercettabile TINYINT(1) NOT NULL DEFAULT 1;

UPDATE sdb_messaggio SET tick_invio = tick WHERE tick_invio = 0;
