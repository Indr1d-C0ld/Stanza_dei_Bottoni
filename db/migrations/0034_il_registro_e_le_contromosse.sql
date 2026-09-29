-- Il registro delle operazioni e le contromosse.
--
-- Un giocatore da solo al tavolo dell'Intelligence iraniana ha dato due ordini
-- di sabotaggio contro Israele. L'apparato li ha controfirmati al posto del
-- Capo, sono partiti, il servizio israeliano li ha scoperti e li ha fermati —
-- e lui non ha saputo niente: la scrivania mostrava solo gli ordini in attesa,
-- e un ordine firmato spariva. Il motore sapeva tutto; il sito non lo diceva.
--
--   sdb_ordine.evento_id           l'operazione che l'ordine ha fatto partire
--   sdb_ordine.firmato_da_apparato la seconda firma l'ha messa la macchina
--   sdb_evento.chiuso_tick         quando l'operazione e' arrivata in fondo,
--                                  riuscita o fermata
--   sdb_conoscenza.primo_tick      si riscriveva a ogni giro: adesso resta la
--                                  settimana in cui il servizio se n'e' accorto
--
-- E la tavola delle contromosse: che cosa il servizio di un giocatore ha
-- deciso di fare di un'operazione altrui che ha scoperto (docs/29).

ALTER TABLE sdb_ordine
    ADD COLUMN IF NOT EXISTS evento_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS firmato_da_apparato TINYINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE sdb_evento
    ADD COLUMN IF NOT EXISTS chiuso_tick INT NULL;

CREATE TABLE IF NOT EXISTS sdb_contromossa (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nazione_id   SMALLINT UNSIGNED NOT NULL,
    evento_id    BIGINT UNSIGNED NOT NULL,
    scelta       ENUM('sventa','sorveglia','trapela','avvisa') NOT NULL,
    giocatore_id INT UNSIGNED NULL,
    tick         INT NOT NULL,
    stato        ENUM('attiva','conclusa') NOT NULL DEFAULT 'attiva',
    esito        VARCHAR(64) NULL,
    esito_tick   INT NULL,
    UNIQUE KEY uq_contromossa (nazione_id, evento_id),
    KEY ix_contromossa_stato (stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
