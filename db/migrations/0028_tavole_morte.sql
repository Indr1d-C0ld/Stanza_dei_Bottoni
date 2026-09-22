-- Cinque tabelle create e mai usate da nessuna riga di codice.
--
-- Trovate dall'audit (bin/audit.php) incrociando lo schema col sorgente: zero
-- righe, zero scritture, zero letture. Non erano un guasto — erano disegni
-- superati che nessuno aveva tolto, ed e' esattamente la forma di difetto che
-- questo progetto trova piu' spesso: una cosa dichiarata che non esiste.
--
--   sdb_flusso_commerciale    il commercio e' stato poi costruito in memoria
--                             (src/Dati/Commercio.php). docs/21 lo dichiarava
--                             gia' relitto: «salvati a ogni tick, mai letti».
--   sdb_relazione_variazione  il diario delle variazioni di affinita', mai
--                             scritto: la fase 06 lavora sull'oggetto in
--                             memoria e salva lo stato, non il delta.
--   sdb_copertura_intel       superata dai moltiplicatori di intelligence del
--                             seme (db/seed/politica-nota.php).
--   sdb_evento_accesso        superata da sdb_conoscenza, che tiene la
--                             conoscenza PER OSSERVATORE su quattro livelli,
--                             piu' il quadrante continuo `copertura`
--                             sull'evento. docs/03 descriveva ancora il
--                             disegno vecchio: corretto insieme a questa.
--   sdb_scenario              gli scenari non sono mai stati implementati;
--                             il seme e' uno solo.
--
-- Sono tutte vuote: si tolgono senza perdere niente.

DROP TABLE IF EXISTS sdb_flusso_commerciale;
DROP TABLE IF EXISTS sdb_relazione_variazione;
DROP TABLE IF EXISTS sdb_copertura_intel;
DROP TABLE IF EXISTS sdb_evento_accesso;
DROP TABLE IF EXISTS sdb_scenario;
