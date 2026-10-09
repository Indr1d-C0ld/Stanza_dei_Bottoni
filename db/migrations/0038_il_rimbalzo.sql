-- Il rimbalzo dopo una guerra (docs/34).
--
-- Quando una guerra finisce l'economia recupera: nei cinque anni dopo le 49
-- guerre finite nel 1990-2019 i paesi sono cresciuti in media 1,9 punti sopra
-- la mediana mondiale (FMI e UCDP). Per saperlo la nazione ricorda quanto le
-- costava la guerra al tick prima, e il rimbalzo che le resta.

ALTER TABLE sdb_nazione_stato
    ADD COLUMN IF NOT EXISTS costo_guerra DOUBLE NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS rimbalzo     DOUBLE NOT NULL DEFAULT 0;
