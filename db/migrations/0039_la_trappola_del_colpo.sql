-- La trappola del colpo di Stato (docs/37).
--
-- Chi ha appena avuto un colpo di Stato ne ha altri: fra i regimi parziali del
-- 2000-2025 un colpo riuscito nei dieci anni prima moltiplica per 3,1 il
-- rischio dell'anno dopo (Powell e Thyne; Londregan e Poole 1990). Per
-- saperlo la nazione ricorda il tick del suo ultimo colpo riuscito, negativo
-- se e' prima del seme. -9999 vuol dire nessuno.

ALTER TABLE sdb_nazione_stato
    ADD COLUMN IF NOT EXISTS ultimo_colpo INT NOT NULL DEFAULT -9999;
