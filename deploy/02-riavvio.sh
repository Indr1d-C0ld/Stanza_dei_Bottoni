#!/usr/bin/env bash
#
# Cambia il profilo di taratura del mondo vivo e lo riavvia dal seme.
#
# Serve quando il motore o il seme cambiano tanto che il mondo in corso non e'
# piu' quello che le misure descrivono (docs/30), o quando si passa da un
# profilo all'altro: «gioco», tarato perche' succedano piu' cose, o
# «osservazione», tarato sul realismo.
#
# Fa, in quest'ordine, e si ferma al primo errore:
#
#   1. un salvataggio verificato del mondo che sta per sparire
#      (deploy/salvataggio.sh);
#   2. una copia datata di /etc/stanzadeibottoni/config.php, poi il cambio di
#      'profilo', poi la verifica che PHP legga il file e il valore nuovo;
#   3. il riavvio del mondo (bin/avvia_mondo.php --ricomincia), che conserva
#      giocatori, inviti, posta, leve e registro dell'arbitro — TENENDO il
#      lucchetto del tick, perche' nessun tick parta a meta';
#   4. un salvataggio del mondo nuovo, che e' quello che finisce nel repo
#      privato alla prossima sincronizzazione.
#
#   bash deploy/02-riavvio.sh --profilo=osservazione           fa tutto
#   bash deploy/02-riavvio.sh --profilo=osservazione --prova   dice cosa farebbe,
#                                                              su una copia
#
# Non chiede sudo: il file di configurazione appartiene all'utente che fa
# girare il gioco.
set -euo pipefail

PROGETTO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONFIG="${SDB_CONFIG:-/etc/stanzadeibottoni/config.php}"
PROFILO=""
PROVA=0
for a in "$@"; do
  case "$a" in
    --profilo=*) PROFILO="${a#--profilo=}" ;;
    --prova)     PROVA=1 ;;
    *) echo "Argomento sconosciuto: $a" >&2; exit 2 ;;
  esac
done

dice() { printf '\n\033[1m%s\033[0m\n' "$*"; }
bene() { printf '  \033[32m✓\033[0m %s\n' "$*"; }
male() { printf '  \033[31m✗\033[0m %s\n' "$*" >&2; exit 1; }

[ -n "$PROFILO" ] || male "manca --profilo=gioco|osservazione"
[ -f "$PROGETTO/calibrazione/$PROFILO.php" ] || male "profilo sconosciuto: $PROFILO (non c'e' calibrazione/$PROFILO.php)"
[ -r "$CONFIG" ] || male "non leggo $CONFIG"
[ -w "$CONFIG" ] || male "non posso scrivere $CONFIG (va lanciato dall'utente che possiede il file)"

ATTUALE="$(php -r '$c = require $argv[1]; echo $c["mondo"]["profilo"] ?? "";' "$CONFIG")"
grep -qE "'profilo'[[:space:]]*=>[[:space:]]*'[a-z]+'" "$CONFIG" \
  || male "in $CONFIG non trovo la riga 'profilo' => '...'"

cambia_profilo() {   # $1 = file su cui lavorare
  sed -i -E "s/('profilo'[[:space:]]*=>[[:space:]]*)'[a-z]+'/\1'$PROFILO'/" "$1"
  php -l "$1" >/dev/null || male "dopo il cambio $1 non e' PHP valido"
  local letto
  letto="$(php -r '$c = require $argv[1]; echo $c["mondo"]["profilo"] ?? "";' "$1")"
  [ "$letto" = "$PROFILO" ] || male "dopo il cambio il profilo letto e' «$letto», non «$PROFILO»"
}

if [ "$PROVA" -eq 1 ]; then
  dice "Prova: niente viene toccato"
  COPIA="$(mktemp)"
  trap 'rm -f "$COPIA"' EXIT
  cp "$CONFIG" "$COPIA"
  cambia_profilo "$COPIA"
  bene "profilo attuale «$ATTUALE», sulla copia diventa «$PROFILO» e PHP lo legge"
  diff <(grep -n "'profilo'" "$CONFIG") <(grep -n "'profilo'" "$COPIA") | sed 's/^/    /' || true
  bene "poi: salvataggio, riavvio dal seme col lucchetto del tick, salvataggio"
  exit 0
fi

dice "1/4  Salvataggio del mondo che sta per sparire"
bash "$PROGETTO/deploy/salvataggio.sh" >/dev/null
bene "fatto e verificato (in \$HOME/backup-stanzadeibottoni)"

dice "2/4  Profilo: «$ATTUALE» -> «$PROFILO»"
COPIA_SICUREZZA="$CONFIG.bak-$(date +%Y%m%d-%H%M%S)"
cp -p "$CONFIG" "$COPIA_SICUREZZA"
bene "copia della configurazione: $COPIA_SICUREZZA"
cambia_profilo "$CONFIG"
bene "il gioco adesso legge il profilo «$PROFILO»"

dice "3/4  Riavvio del mondo dal seme"
cd "$PROGETTO"
# Il tick prende questo lucchetto senza aspettare (LOCK_NB): se e' gia' preso,
# salta il giro. Qui lo si prende ASPETTANDO, cosi' se un tick e' in corso lo
# si lascia finire.
flock -w 600 "$PROGETTO/storage/tick.lock" php bin/avvia_mondo.php --ricomincia
bene "mondo riavviato al tick zero"

dice "4/4  Salvataggio del mondo nuovo"
bash "$PROGETTO/deploy/salvataggio.sh" >/dev/null
bene "fatto e verificato"

printf '\n  Il primo tick lo fa il cron, alla prossima ora pari.\n'
printf '  Per tornare indietro: cp %s %s e ricaricare il salvataggio.\n\n' "$COPIA_SICUREZZA" "$CONFIG"
