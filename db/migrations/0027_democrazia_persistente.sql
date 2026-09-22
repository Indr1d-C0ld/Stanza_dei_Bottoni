-- Le istituzioni diventano uno stato del mondo, non una costante del seme.
--
-- `democrazia` arriva da V-Dem attraverso db/seed/democrazia.php, e finche'
-- restava immobile bastava leggerla dal seme a ogni avvio. Adesso si muove: un
-- colpo di Stato la erode, un'alternanza pacifica la consolida. Senza questa
-- colonna quei movimenti sparirebbero a ogni tick, perche' il mondo si
-- ricostruisce dal seme e poi si sovrascrive con lo stato salvato — e quel che
-- non e' salvato torna com'era.
--
-- E' lo stesso stampo dei quattro campi che il motore muoveva per frazioni
-- mentre erano dichiarati interi: una grandezza che cambia e non viene
-- registrata e' una grandezza che non cambia.

ALTER TABLE sdb_nazione_stato
    ADD COLUMN democrazia DECIMAL(5,4) NOT NULL DEFAULT 0.3550
    AFTER legittimita;
