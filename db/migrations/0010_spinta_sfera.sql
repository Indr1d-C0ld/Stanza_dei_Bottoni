-- La sfera d'influenza la ricalcola ogni tick la fase 06, da geografia e peso
-- economico. Va bene per la struttura, ma cancella la storia: una crisi vinta
-- su un paese terzo non lasciava traccia. La spinta e' la memoria di quelle
-- vittorie e di quelle rese — si somma alla sfera strutturale e si consuma
-- piano, cosi' una crisi vinta vale per anni ma non per sempre.
ALTER TABLE sdb_relazione
    ADD COLUMN spinta_sfera FLOAT NOT NULL DEFAULT 0 AFTER sfera;
