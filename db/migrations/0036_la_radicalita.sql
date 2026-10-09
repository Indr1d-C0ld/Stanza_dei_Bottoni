-- La radicalita' del regime (docs/33).
--
-- La repressione e la censura di V-Dem erano costanti del seme. Adesso un
-- cambio di regime le muove — dopo un colpo di Stato la repressione sale in
-- media di 0,12 e la censura di 0,13 (V-Dem, quindici colpi 2010-2023) — e
-- quindi vanno salvate: senza, a ogni tick tornerebbero al seme.
--
-- Il valore 0 di una riga gia' scritta vuol dire «mai salvato»: il Deposito
-- lascia allora il valore del seme.

ALTER TABLE sdb_nazione_stato
    ADD COLUMN IF NOT EXISTS repressione DOUBLE NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS censura     DOUBLE NOT NULL DEFAULT 0;
