-- Il rischio d'incidente anche per chi sale dal sito.
--
-- Da meta' scala in su (gradino 6 e oltre) ogni passo puo' sfuggire di mano:
-- e' la regola di Balance of Power, e la fase 01 la applicava — ma solo alle
-- mosse dell'apparato, che tira il dado prima di salire. Un giocatore che
-- saliva dalla scrivania non correva nessun rischio: la scala era piu' sicura
-- per gli umani che per le macchine, cioe' esattamente al contrario di quel che
-- il gioco vuole insegnare.
--
-- Il sito non ha il mondo in memoria, ne' il caso deterministico del tick: la
-- mossa del giocatore si segna qui, col gradino raggiunto, e il dado lo tira la
-- fase 01 del tick successivo con le stesse regole dell'apparato.

ALTER TABLE sdb_crisi
    ADD COLUMN IF NOT EXISTS da_provare TINYINT UNSIGNED NOT NULL DEFAULT 0;
