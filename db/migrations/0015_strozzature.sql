-- 0015 — Le strozzature commerciali.
--
-- Fino a ieri un embargo era un numero tolto alla crescita del bersaglio, in
-- proporzione al peso di chi lo imponeva. L'intuizione di Crawford era giusta —
-- un embargo da soli e' teatro — ma il modello non sapeva *che cosa* veniva
-- tagliato, non sapeva che certe forniture si rimpiazzano in un trimestre e
-- altre no, e soprattutto non sapeva che chi chiude un rubinetto smette anche
-- di essere pagato per l'acqua.
--
-- Una strozzatura e' un flusso commerciale chiuso o ridotto fra due paesi, con
-- una scadenza. Il danno che produce lo calcola il grafo di Commercio.php: per
-- chi la subisce dipende da quanta parte del suo fabbisogno passava di li' e da
-- quanto e' difficile rimpiazzarla; per chi la impone, da quanta parte del suo
-- export perde. Il secondo e' piu' piccolo del primo, ma non e' zero — ed e'
-- quello che trasforma l'embargo da pulsante a decisione.

CREATE TABLE IF NOT EXISTS sdb_strozzatura (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fornitore_id SMALLINT UNSIGNED NOT NULL,
  cliente_id   SMALLINT UNSIGNED NOT NULL,
  -- quanta parte del flusso e' chiusa: 100 = embargo pieno, 35 = restrizioni
  quota        TINYINT UNSIGNED NOT NULL DEFAULT 100,
  dal_tick     INT UNSIGNED NOT NULL,
  al_tick      INT UNSIGNED NOT NULL,
  KEY k_cliente (cliente_id, al_tick),
  KEY k_fornitore (fornitore_id, al_tick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
