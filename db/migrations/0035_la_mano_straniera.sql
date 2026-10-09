-- La mano straniera (docs/30).
--
-- Una caduta irregolare costa la faccia ai garanti solo se c'e' sopra una mano
-- straniera: un'ingerenza ostile andata a segno nell'ultimo anno, o una guerra
-- con un altro Stato. Per saperlo, la nazione ricorda l'ultima ingerenza e chi
-- l'ha fatta.

ALTER TABLE sdb_nazione_stato
    ADD COLUMN IF NOT EXISTS ingerenza_tick INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ingerenza_da   CHAR(3) NOT NULL DEFAULT '';

-- E il mondo vivo si riprende la faccia che non doveva perdere. Nei primi tre
-- anni e mezzo sono caduti undici governi (Sud Sudan, Afghanistan, Sudan,
-- Mali, Burkina Faso, Haiti, Somalia, Mauritania, Yemen, Birmania, Venezuela)
-- e su nessuno c'era una mano straniera: tutta l'integrita' perduta lo era per
-- la regola sbagliata. Si corregge solo l'ultimo tick, da cui riparte il
-- mondo; la storia resta com'e' stata registrata.
UPDATE sdb_nazione_stato s
  JOIN (SELECT MAX(tick) AS t FROM sdb_nazione_stato) m ON s.tick = m.t
   SET s.integrita = 128;
