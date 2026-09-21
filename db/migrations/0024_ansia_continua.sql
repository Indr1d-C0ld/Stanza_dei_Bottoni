-- 0024 — L'ansia militare diventa un numero con i decimali.
--
-- Terza occorrenza dello stesso difetto, dopo stato_polizia e cyber_difesa: una
-- grandezza che il motore muove per frazioni non puo' essere un intero, o
-- l'arrotondamento cancella il movimento a ogni tick. Qui il decadimento vale
-- 0,27 punti per tick e spariva tutto: la paura non passava mai, e una
-- dimostrazione di forza subita nel 2027 pesava identica quindici anni dopo.
ALTER TABLE sdb_nazione_stato MODIFY COLUMN ansia_militare DECIMAL(5,2) NOT NULL DEFAULT 10.00;
