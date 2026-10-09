-- I cobelligeranti (docs/33).
--
-- Un garante che onora la sua parola adesso combatte accanto al difensore, e
-- non si limita a mandargli un quarto del proprio arsenale alla prima
-- settimana: chi combatte va ricordato fino alla fine della guerra. Codici
-- ISO separati da virgole.

ALTER TABLE sdb_guerra
    ADD COLUMN IF NOT EXISTS cobelligeranti VARCHAR(255) NOT NULL DEFAULT '';
