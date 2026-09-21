-- 0023 — Via la colonna cyber_offesa, che non e' mai esistita davvero.
--
-- C'era nella tabella dello stato fin dall'inizio, ma non c'e' mai stato un
-- campo corrispondente sull'oggetto Nazione: nessuno la scriveva, nessuno la
-- leggeva, e a ogni tick veniva riscritta col proprio default.
--
-- L'offesa cibernetica nel modello esiste gia', ed e' una delle sei discipline
-- di Intelligence. Un secondo numero che dicesse la stessa cosa sarebbe solo
-- un'altra manopola da tenere allineata a mano.
ALTER TABLE sdb_nazione_stato DROP COLUMN cyber_offesa;
