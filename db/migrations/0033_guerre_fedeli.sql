-- Le guerre, fedeli.
--
-- Tre difetti trovati preparando la guerra russo-ucraina per il seme:
--
--   inizio_tick     senza segno: una guerra cominciata PRIMA della divergenza
--                   (il 24/02/2022, cioe' al tick -202) non si poteva salvare.
--   morti           intero: si perdeva la frazione a ogni tick, e il mondo vivo
--                   si separava da quello misurato (tests/13).
--   esito           la colonna c'era e nessuno la scriveva: una guerra finiva
--                   senza che si sapesse come.
--
-- E gli aiuti militari, che prima non esistevano: quanto ha ricevuto ciascuna
-- parte da chi parteggiava per lei, cumulato dall'inizio.

ALTER TABLE sdb_guerra
    MODIFY inizio_tick INT NOT NULL,
    MODIFY fine_tick   INT NULL,
    MODIFY morti       DOUBLE NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS aiuti_difensore  DOUBLE NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS aiuti_aggressore DOUBLE NOT NULL DEFAULT 0;
