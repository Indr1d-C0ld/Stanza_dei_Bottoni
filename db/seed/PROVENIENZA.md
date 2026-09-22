# Da dove viene il seme

Generato da `bin/importa_factbook.php` il **21/09/2026**.

- Fonte: CIA World Factbook, via <https://github.com/factbook/factbook.json>
- Revisione del clone: `144d697`
- Nazioni importate: 189

Il Factbook data le proprie voci una per una («2024 est.», «2025 est.») e le
vintage non coincidono fra campi: popolazione e prodotto possono essere di anni
diversi nello stesso paese. Le cifre sono quindi **contemporanee ma non
sincrone**, entro un paio d'anni. Il calendario del gioco comincia il
05/01/2026.

Verifiche fatte a campione contro le fonti ufficiali, al 22/09/2026:

| grandezza | seme | riferimento |
|---|---:|---|
| popolazione mondiale | 8,11 mrd | ONU WPP 2024: 8,2 |
| prodotto mondiale (PPA) | 172 mila mrd $ | Banca Mondiale: ~180 |
| spesa militare | 3.952 mrd $ | SIPRI 2024: 2.718 |
| effettivi sotto le armi | 21,1 mln | IISS: ~27 |
| Stati dotati di nucleare | 9 | SIPRI: 9 |

Il prodotto e' in dollari a **parita' di potere d'acquisto**, e si vede: la Cina
sta davanti agli Stati Uniti. Confrontarlo col PIL nominale e' un errore, ed e'
stato commesso una volta.

## Perche' questo file esiste

Un seme senza data non si puo' auditare: fra tre anni nessuno saprebbe se le
cifre sono del 2026 o del 2031, e **un riferimento che non si puo' datare non si
puo' nemmeno dichiarare scaduto**.

E' la lezione dei circa dieci cambi irregolari di governo l'anno che Crawford
prende dal *World Handbook of Political and Social Indicators*. Quel numero non
e' sbagliato: e' giusto per il 1948-77. Applicato a un mondo seminato nel 2026
produceva tredici colpi di Stato l'anno, cioe' gli anni Sessanta. Il racconto
sta in `docs/26-i-numeri-realistici.md`.
