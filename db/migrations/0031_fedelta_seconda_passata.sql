-- La fedelta' della persistenza, seconda passata.
--
-- La 0029 aveva portato a DOUBLE le grandezze che si muovono per frazioni. La
-- prova nuova di tests/13 — lo stesso mondo fatto girare in memoria e col giro
-- completo dalla base dati a ogni tick, poi confrontato campo per campo — ne ha
-- trovate altre:
--
--   pil_pro_capite, consumo_pro_capite, consumo_pro_capite_prec
--                        due decimali. La legittimita' si muove sulla VARIAZIONE
--                        del consumo pro capite fra un tick e l'altro, che e'
--                        dell'ordine del centesimo: l'arrotondamento era rumore
--                        sulla grandezza che decide chi cade. 184 paesi su 189
--                        avevano una legittimita' diversa dopo un solo tick.
--   pil, popolazione     il PIL con due decimali, la popolazione intera: meno
--                        grave, ma e' il numeratore e il denominatore di tutto.
--   presenza_intel.livello, capacita_intel.livello
--                        quattro decimali. La presenza si muove verso il suo
--                        obiettivo dell'uno per cento dello scarto a tick: uno
--                        scarto sotto mezzo centesimo non si muoveva mai.

ALTER TABLE sdb_nazione_stato
    MODIFY pil                     DOUBLE NOT NULL DEFAULT 0,
    MODIFY pil_pro_capite          DOUBLE NOT NULL DEFAULT 0,
    MODIFY consumo_pro_capite      DOUBLE NOT NULL DEFAULT 0,
    MODIFY consumo_pro_capite_prec DOUBLE NOT NULL DEFAULT 0,
    MODIFY popolazione             DOUBLE NOT NULL DEFAULT 0;

ALTER TABLE sdb_presenza_intel
    MODIFY livello DOUBLE NOT NULL DEFAULT 0;

ALTER TABLE sdb_capacita_intel
    MODIFY livello DOUBLE NOT NULL DEFAULT 0;
