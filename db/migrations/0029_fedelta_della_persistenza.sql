-- La fedelta' della persistenza: il mondo vivo deve essere il mondo che si
-- misura.
--
-- L'audit di settembre 2026 ha trovato che il mondo vivo e il simulatore in
-- memoria DIVERGEVANO, e che tutte le misure di realismo fatte fino ad allora
-- descrivevano il secondo. La prova e' stata una rigiocata in memoria che
-- arrotondava a ogni tick come fanno le colonne: ha riprodotto il mondo vivo
-- quasi esattamente — stato di polizia fermo in 147 paesi contro 146 reali,
-- quota militare ferma in 109 contro 110, soldati in 62 contro 62. Senza
-- arrotondamento, gli stessi campi fermi sarebbero stati da zero a due.
--
-- E' lo stesso stampo dei quattro interi mossi per frazioni trovato
-- nell'audit precedente, un piano piu' sotto: il motore muove una grandezza di
-- qualche millesimo per tick, la colonna tiene due decimali, e il movimento
-- sparisce al salvataggio. I casi piu' gravi:
--
--   affinita          SMALLINT: la deriva e' al massimo 0,147 a tick, e
--                     l'arrotondamento la cancellava sempre. 5.256 relazioni su
--                     5.530 erano ancora al valore del seme dopo 106 tick.
--   stato_polizia     DECIMAL(4,2): un cricchetto. Da 2,00 nessun paese poteva
--                     allentare; in 107 tick 43 hanno stretto e uno ha allentato.
--   quota_militare    DECIMAL(5,4): ferma in 110 paesi su 189; e con lei il
--                     reclutamento, che la insegue.
--   crescita_strutturale  DECIMAL(6,4): un programma d'investimento non si
--                     riassorbiva mai del tutto — la pompa che il commento di
--                     Fase03 dice di impedire.
--
-- Il rimedio non e' scegliere una precisione per campo: e' DOUBLE per ogni
-- grandezza continua dello stato. Quindici cifre significative, e il problema
-- non si ripresenta quando qualcuno cambia un ritmo.
--
-- E TRE COSE CHE NON SI SALVAVANO AFFATTO:
--
--   sdb_relazione.ancora           la memoria del rapporto. Tornava al seme a
--                                  ogni tick: un agente scoperto, un
--                                  reclutamento denunciato, un cambio di regime
--                                  spostavano l'ancora, e il tick dopo era come
--                                  non fosse successo niente.
--   sdb_relazione.obbligo_firmato  il trattato sulla carta. Una denuncia (fase
--                                  06) si annullava da sola al tick successivo.
--   sdb_memoria_azioni             l'attesa fra due mosse uguali della
--                                  dottrina. Si azzerava a ogni tick, e 28
--                                  eventi su 196 del mondo vivo la violavano:
--                                  mediazioni a due tick di distanza quando ne
--                                  servivano cinquantadue.

ALTER TABLE sdb_nazione_stato
    MODIFY crescita_pil          DOUBLE NOT NULL DEFAULT 0,
    MODIFY quota_consumi         DOUBLE NOT NULL DEFAULT 0.6,
    MODIFY quota_investimenti    DOUBLE NOT NULL DEFAULT 0.2,
    MODIFY quota_militare        DOUBLE NOT NULL DEFAULT 0.2,
    MODIFY influenza_totale      DOUBLE NOT NULL DEFAULT 0,
    MODIFY stato_polizia         DOUBLE NOT NULL DEFAULT 2,
    MODIFY legittimita           DOUBLE NOT NULL DEFAULT 50,
    MODIFY democrazia            DOUBLE NOT NULL DEFAULT 0.355,
    MODIFY aspettativa           DOUBLE NOT NULL DEFAULT 0.03,
    MODIFY clamore_sociale       DOUBLE NOT NULL DEFAULT 0,
    MODIFY ansia_militare        DOUBLE NOT NULL DEFAULT 10,
    MODIFY soldati               DOUBLE NOT NULL DEFAULT 0,
    MODIFY equipaggiamento       DOUBLE NOT NULL DEFAULT 0,
    MODIFY potenza_militare      DOUBLE NOT NULL DEFAULT 0,
    MODIFY forza_insorti         DOUBLE NOT NULL DEFAULT 0,
    MODIFY controllo_info        DOUBLE NOT NULL DEFAULT 50,
    MODIFY cyber_difesa          DOUBLE NOT NULL DEFAULT 50,
    MODIFY deriva_politica       DOUBLE NOT NULL DEFAULT 0,
    MODIFY integrita             DOUBLE NOT NULL DEFAULT 128,
    MODIFY crescita_strutturale  DOUBLE NOT NULL DEFAULT 0,
    MODIFY pressione_esterna     DOUBLE NOT NULL DEFAULT 0,
    MODIFY reputazione_sporca    DOUBLE NOT NULL DEFAULT 0,
    -- con segno: all'avvio l'ultimo cambio di governo sta nel PASSATO del
    -- mondo, cioe' prima del tick zero.
    MODIFY anno_ultimo_cambio    INT NOT NULL DEFAULT 0;

ALTER TABLE sdb_relazione
    MODIFY affinita              DOUBLE NOT NULL DEFAULT 0,
    MODIFY spinta_sfera          DOUBLE NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ancora          DOUBLE NULL,
    ADD COLUMN IF NOT EXISTS obbligo_firmato TINYINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE sdb_gabinetto
    MODIFY coesione              DOUBLE NOT NULL DEFAULT 60;

ALTER TABLE sdb_fazione
    MODIFY forza                 DOUBLE NOT NULL DEFAULT 25,
    MODIFY favore                DOUBLE NOT NULL DEFAULT 0;

ALTER TABLE sdb_poltrona
    MODIFY competenza            DOUBLE NOT NULL DEFAULT 0.5,
    MODIFY potere                DOUBLE NOT NULL DEFAULT 50,
    MODIFY lealta                DOUBLE NOT NULL DEFAULT 70;

ALTER TABLE sdb_mondo_stato
    MODIFY nastiness             DOUBLE NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS sdb_memoria_azioni (
    chiave  VARCHAR(96) NOT NULL PRIMARY KEY,   -- «ISO|verbo|bersaglio»
    tick    INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
