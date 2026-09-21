-- 0026 — Via le quattro colonne di dipendenza, che non hanno mai fatto niente.
--
-- dip_energia, dip_cibo, dip_finanza, dip_tecnologia: dichiarate sull'oggetto
-- Nazione, salvate a ogni tick per centottantanove paesi, e mai lette da
-- nessuna fase. Erano i segnaposto di un modello di interdipendenza che poi e'
-- stato costruito per un'altra strada — il grafo di Commercio, con i suoi
-- cinque settori e la sostituibilita' — senza che le vecchie venissero tolte.
--
-- Restare li' non era gratis: chi legge il codice le conta come parte del
-- modello, e non lo sono.
ALTER TABLE sdb_nazione_stato
  DROP COLUMN dip_energia,
  DROP COLUMN dip_cibo,
  DROP COLUMN dip_finanza,
  DROP COLUMN dip_tecnologia;
