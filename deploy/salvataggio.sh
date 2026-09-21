#!/usr/bin/env bash
#
# Il salvataggio quotidiano di Stanza dei Bottoni.
#
# Salva tre cose, e sono tutte e tre necessarie per rimettere in piedi il gioco
# da zero:
#
#   1. il database — dove vive il mondo: centinaia di tick di storia, le crisi,
#      le talpe, le epoche. Senza questo non si recupera niente;
#   2. la configurazione in /etc/stanzadeibottoni — che non sta in git perche'
#      contiene le credenziali, e quindi non si recupera da nessun'altra parte;
#   3. il codice e il seme — che stanno anche altrove, ma un archivio coerente
#      con il dump dello stesso momento vale piu' di due pezzi scoordinati.
#
# E poi CONTROLLA quel che ha salvato. Un salvataggio che nessuno ha mai
# verificato non e' un salvataggio: e' una cartella che cresce.
#
#   bash deploy/salvataggio.sh              salva e verifica
#   bash deploy/salvataggio.sh --verifica   verifica l'ultimo e basta
#
set -euo pipefail

PROGETTO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOVE="${SDB_BACKUP:-$HOME/backup-stanzadeibottoni}"
CONFIG="/etc/stanzadeibottoni/config.php"
QUANTI_GIORNI="${SDB_BACKUP_GIORNI:-14}"
OGGI="$(date +%Y%m%d-%H%M%S)"

dice() { printf '\n\033[1m%s\033[0m\n' "$*"; }
bene() { printf '  \033[32m✓\033[0m %s\n' "$*"; }
male() { printf '  \033[31m✗\033[0m %s\n' "$*" >&2; }

mkdir -p "$DOVE"
chmod 700 "$DOVE"

leggi_config() {
  php -r '
    $c = require "'"$CONFIG"'";
    $d = $c["db"] ?? [];
    printf("%s\n%s\n%s\n%s\n%s\n",
      $d["name"] ?? $d["nome"] ?? $d["database"] ?? "",
      $d["user"] ?? $d["utente"] ?? "",
      $d["pass"] ?? $d["parola"] ?? $d["password"] ?? "",
      $d["host"] ?? "127.0.0.1",
      $d["port"] ?? 3306);'
}

# ---------------------------------------------------------------- verifica
verifica_dump() {
  local f="$1"
  [ -s "$f" ] || { male "il dump è vuoto"; return 1; }

  if ! gzip -t "$f" 2>/dev/null; then
    male "l'archivio è corrotto (gzip -t fallisce)"
    return 1
  fi

  # mariadb-dump chiude sempre con questa riga: se manca, il dump si e'
  # interrotto a meta' e ricaricarlo darebbe un mondo mutilato senza dirlo.
  #
  # Attenzione a come si cerca: "grep -q" chiude il tubo al primo riscontro,
  # zcat riceve SIGPIPE e muore con 141, e con "set -o pipefail" il controllo
  # legge un fallimento che non c'e'. E' gia' successo nello script di deploy.
  # Qui si conta invece di uscire presto, cosi' il flusso si consuma tutto.
  local chiusura
  chiusura="$(zcat "$f" | tail -5 | grep -c "Dump completed" || true)"
  if [ "$chiusura" -eq 0 ]; then
    male "il dump è troncato: manca la riga di chiusura"
    return 1
  fi

  local tabelle_dump tabelle_vive
  tabelle_dump="$(zcat "$f" | grep -c '^CREATE TABLE' || true)"
  tabelle_vive="$(php -r '
    require "'"$PROGETTO"'/src/autoload.php";
    App\Nucleo\Configurazione::carica("'"$PROGETTO"'");
    $db = new App\Nucleo\Basedati((array) App\Nucleo\Configurazione::leggi("db", []));
    echo (int) $db->esegui("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()")->fetchColumn();')"

  if [ "$tabelle_dump" -ne "$tabelle_vive" ]; then
    male "il dump ha $tabelle_dump tabelle, il database vivo ne ha $tabelle_vive"
    return 1
  fi
  bene "dump integro: $tabelle_dump tabelle, chiusura regolare"

  # Una riga che ci dev'essere per forza: se il mondo non ha tick, il dump non
  # vale niente anche se e' formalmente a posto.
  local mondo
  mondo="$(zcat "$f" | grep -c 'INSERT INTO `sdb_mondo_stato`' || true)"
  if [ "$mondo" -eq 0 ]; then
    male "nel dump non c'è nessuno stato del mondo: qualcosa non torna"
    return 1
  fi
  bene "il mondo c'è dentro"
  return 0
}

if [ "${1:-}" = "--verifica" ]; then
  ULTIMO="$(ls -1t "$DOVE"/db-*.sql.gz 2>/dev/null | head -1 || true)"
  [ -n "$ULTIMO" ] || { male "nessun salvataggio da verificare in $DOVE"; exit 1; }
  dice "Verifica di $(basename "$ULTIMO")"
  verifica_dump "$ULTIMO" && bene "va bene" || exit 1
  exit 0
fi

# ---------------------------------------------------------------- il dump
dice "1/4  Il database"
mapfile -t CFG < <(leggi_config)
DB_NOME="${CFG[0]}"; DB_UTENTE="${CFG[1]}"; DB_PAROLA="${CFG[2]}"
DB_HOST="${CFG[3]}"; DB_PORTA="${CFG[4]:-3306}"
[ -n "$DB_NOME" ] || { male "non riesco a leggere il nome del database da $CONFIG"; exit 1; }

DUMP="$DOVE/db-$OGGI.sql.gz"
mariadb-dump --skip-ssl -h"$DB_HOST" -P"$DB_PORTA" -u"$DB_UTENTE" -p"$DB_PAROLA" \
  --single-transaction --routines --triggers --default-character-set=utf8mb4 \
  "$DB_NOME" 2>/dev/null | gzip -9 > "$DUMP"
chmod 600 "$DUMP"
bene "salvato $(basename "$DUMP") ($(du -h "$DUMP" | cut -f1))"

dice "2/4  La configurazione e il codice"
tar czf "$DOVE/codice-$OGGI.tar.gz" \
  -C "$(dirname "$PROGETTO")" \
  --exclude='StanzaDeiBottoni/storage/logs' \
  --exclude='StanzaDeiBottoni/storage/factbook' \
  --exclude='StanzaDeiBottoni/.git' \
  "$(basename "$PROGETTO")" 2>/dev/null
chmod 600 "$DOVE/codice-$OGGI.tar.gz"
bene "codice e seme ($(du -h "$DOVE/codice-$OGGI.tar.gz" | cut -f1))"

if [ -r "$CONFIG" ]; then
  cp -a "$CONFIG" "$DOVE/config-$OGGI.php"
  chmod 600 "$DOVE/config-$OGGI.php"
  bene "configurazione (con le credenziali: per questo la cartella è 700)"
else
  male "configurazione non leggibile: il salvataggio non basta a un recupero"
fi

dice "3/4  Verifica"
verifica_dump "$DUMP" || { male "il salvataggio appena fatto NON è valido"; exit 1; }

dice "4/4  Potatura"
BUTTATI=0
while IFS= read -r v; do
  rm -f "$v"; BUTTATI=$((BUTTATI + 1))
done < <(find "$DOVE" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'codice-*.tar.gz' -o -name 'config-*.php' \) -mtime "+$QUANTI_GIORNI")
bene "tenuti gli ultimi $QUANTI_GIORNI giorni, buttati $BUTTATI file"

RESTANTI="$(find "$DOVE" -maxdepth 1 -name 'db-*.sql.gz' | wc -l)"
printf '\n  %s salvataggi in %s, %s in tutto\n\n' "$RESTANTI" "$DOVE" "$(du -sh "$DOVE" | cut -f1)"
