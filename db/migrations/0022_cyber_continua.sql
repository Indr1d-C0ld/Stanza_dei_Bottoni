-- 0022 — La difesa cibernetica diventa un numero con i decimali, e si muove.
--
-- Stesso difetto dello stato di polizia, e piu' grave: cyber_difesa non veniva
-- scritta da nessuna fase, quindi restava al valore del seme per sempre. E' il
-- numero che decide se i messaggi di un paese si possono leggere e riscrivere,
-- cioe' la meta' del gioco che riguarda i servizi.
ALTER TABLE sdb_nazione_stato MODIFY COLUMN cyber_difesa DECIMAL(5,2) NOT NULL DEFAULT 50.00;
ALTER TABLE sdb_nazione_stato MODIFY COLUMN cyber_offesa DECIMAL(5,2) NOT NULL DEFAULT 50.00;
