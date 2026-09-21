-- 0025 — Il controllo dell'informazione diventa continuo, e puo' anche salire.
--
-- Aveva un solo scrittore — la disinformazione altrui, che lo abbassava — e
-- nessuno che lo alzasse: dopo quindici anni centosettantacinque paesi su
-- centottantanove erano ancora esattamente al valore di partenza. Ora segue la
-- presa del governo sulla piazza: chi reprime controlla anche il racconto.
ALTER TABLE sdb_nazione_stato MODIFY COLUMN controllo_info DECIMAL(5,2) NOT NULL DEFAULT 50.00;
