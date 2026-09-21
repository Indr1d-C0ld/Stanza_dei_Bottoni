-- 0018 — Il recupero della parola d'ordine, e gli avvisi di gioco.
--
-- Due buchi che si notano solo quando il sito e' pubblico davvero.
--
-- IL RECUPERO. Chi dimenticava la parola d'ordine restava fuori per sempre:
-- non c'era nessun modo di rientrare, nemmeno passando dall'arbitro, che puo'
-- cambiare un indirizzo ma non una parola. Il gettone e' separato da quello di
-- verifica apposta: sono due cose diverse e confonderle vorrebbe dire che un
-- collegamento di conferma vale anche a cambiare le credenziali.
--
-- GLI AVVISI. La posta c'era e serviva solo a registrarsi. Ma questo e' un
-- gioco che gira a tick di due ore e i giocatori non stanno collegati: una
-- crisi che aspetta la tua risposta, o una poltrona che sta per passare
-- all'apparato, sono cose che vanno dette. Il contatore dell'ultimo avviso
-- serve a non trasformare la cosa in molestia: al massimo un messaggio ogni
-- tanti tick, e dentro ci sta tutto quel che e' successo.

ALTER TABLE sdb_giocatore
  ADD COLUMN IF NOT EXISTS gettone_reimposta CHAR(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS reimposta_scade DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS reimposta_chiesta DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS avvisi TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS ultimo_avviso_tick INT UNSIGNED NOT NULL DEFAULT 0,
  ADD INDEX IF NOT EXISTS k_reimposta (gettone_reimposta);
