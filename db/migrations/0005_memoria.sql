-- 0005 — La memoria che mancava.
--
-- Il primo avvio reale ha mostrato che il mondo salvava molto piu' di quanto
-- rileggesse: a ogni tick si ricostruiva dal seme e perdeva la deriva politica,
-- l'integrita', i contatori storici, le scadenze elettorali. Queste colonne
-- sono lo stato che deve sopravvivere fra un tick e l'altro.

ALTER TABLE sdb_nazione_stato
  ADD COLUMN IF NOT EXISTS deriva_politica       DECIMAL(7,3)  NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS integrita             DECIMAL(7,2)  NOT NULL DEFAULT 128,
  ADD COLUMN IF NOT EXISTS crescita_strutturale  DECIMAL(6,4)  NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS pressione_esterna     DECIMAL(6,4)  NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS reputazione_sporca    DECIMAL(6,2)  NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS consumo_pro_capite_prec DECIMAL(12,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS azioni_in_volo        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS cambi_esecutivo       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS cambi_irregolari      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS vittorie_insorti      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS scandali_subiti       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS anno_ultimo_cambio    INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS prossima_elezione     INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS mandato_tick          INT UNSIGNED NOT NULL DEFAULT 0;

-- La conoscenza va salvata per tick, altrimenti a ogni ripartenza i servizi
-- dimenticano quel che avevano scoperto e ricominciano da capo.
ALTER TABLE sdb_conoscenza
  ADD COLUMN IF NOT EXISTS aggiornata_tick INT UNSIGNED NOT NULL DEFAULT 0;
