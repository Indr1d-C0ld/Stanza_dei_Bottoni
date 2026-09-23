-- Le colonne che nessuno nomina.
--
-- Il settimo controllo di bin/audit.php incrocia lo schema col sorgente e
-- cerca ogni colonna come parola intera in src/, bin/, views/ e index.php. Ne
-- ha trovate ventisei mai scritte ne' lette dal 0001: il residuo di un modello
-- economico (bilancio, debito, riserve, prezzi di energia e cibo) e delle
-- «ansie» e «fami» di Shadow President che il motore ha poi realizzato in
-- altro modo, piu' una tavola intera, sdb_rapporto, che i rapporti
-- d'intelligence non hanno mai usato: vivono nel mondo in memoria e si
-- salvano in sdb_conoscenza. Una colonna sempre al valore di partenza dice a
-- chi legge lo schema che quella grandezza esiste, e non esiste.
--
-- Sono tutte al valore predefinito in ogni riga (verificato prima di togliere).

ALTER TABLE sdb_nazione_stato
    DROP COLUMN IF EXISTS saldo_bilancio,
    DROP COLUMN IF EXISTS debito_su_pil,
    DROP COLUMN IF EXISTS riserve,
    DROP COLUMN IF EXISTS ansia_economia,
    DROP COLUMN IF EXISTS ansia_stranieri,
    DROP COLUMN IF EXISTS ansia_governo,
    DROP COLUMN IF EXISTS ansia_nucleare,
    DROP COLUMN IF EXISTS ansia_rango,
    DROP COLUMN IF EXISTS fame_sociale,
    DROP COLUMN IF EXISTS fame_economica,
    DROP COLUMN IF EXISTS fame_intelligence,
    DROP COLUMN IF EXISTS fame_militare,
    DROP COLUMN IF EXISTS fame_nucleare,
    DROP COLUMN IF EXISTS orientamento_insorti,
    DROP COLUMN IF EXISTS capacita_attribuzione;

ALTER TABLE sdb_mondo_stato
    DROP COLUMN IF EXISTS prezzo_energia,
    DROP COLUMN IF EXISTS prezzo_cibo;

ALTER TABLE sdb_ideologia
    DROP COLUMN IF EXISTS etica_base,
    DROP COLUMN IF EXISTS ambizione_base;

ALTER TABLE sdb_evento
    DROP COLUMN IF EXISTS esecutore_id,
    DROP COLUMN IF EXISTS bersaglio_rif;

ALTER TABLE sdb_conoscenza
    DROP COLUMN IF EXISTS rapporto_id;

DROP TABLE IF EXISTS sdb_rapporto;
