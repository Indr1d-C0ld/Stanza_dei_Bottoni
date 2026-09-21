-- 0020 — Una collazione sola per tutte le tabelle.
--
-- Le migrazioni dalla 0009 in poi dichiaravano `DEFAULT CHARSET=utf8mb4` senza
-- dire anche `COLLATE`. Sembra innocuo e non lo e': MariaDB 11.8 non eredita la
-- collazione del database, ci mette la propria per quel charset —
-- utf8mb4_uca1400_ai_ci — e cosi' dodici tabelle nuove sono nate diverse dalle
-- trenta vecchie.
--
-- Il risultato e' un errore a tempo di esecuzione, non a tempo di scrittura:
--
--   Illegal mix of collations (utf8mb4_uca1400_ai_ci,IMPLICIT)
--   and (utf8mb4_unicode_ci,IMPLICIT) for operation '='
--
-- Scatta appena una query confronta una stringa di una tabella nuova con una di
-- una vecchia. L'ho scoperto cancellando un account: la cancellazione confronta
-- sdb_posta.destinatario con sdb_giocatore.email, e si e' fermata li'.
--
-- Qui si riportano tutte e dodici alla collazione del database. Da qui in poi
-- ogni CREATE TABLE deve dire COLLATE per esteso.

ALTER TABLE sdb_assenza_fatto  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_atto_arbitro   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_epoca          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_invito         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_leva           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_linea          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_manipolazione  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_migrazione     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_posta          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_punteggio      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_rivelazione    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sdb_strozzatura    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
