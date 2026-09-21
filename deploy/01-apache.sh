#!/usr/bin/env bash
#
# Stanza dei Bottoni — pubblicazione sotto Apache.
#
# Fa una cosa sola: installa e abilita la configurazione che serve il progetto
# sotto /stanzadeibottoni del vhost esistente. Non tocca il database, non tocca
# le password, non tocca il mondo in corso — per quello c'è 00-avvio.sh, e
# rilanciarlo adesso rigenererebbe credenziali che funzionano già.
#
# Sotto-percorso e non porta dedicata: l'HSTS del vhost di questa macchina
# rompe i servizi esposti per porta.
#
#   sudo bash deploy/01-apache.sh
#
set -euo pipefail

PROGETTO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SORGENTE="${PROGETTO}/deploy/apache-stanzadeibottoni.conf"
DESTINAZIONE="/etc/apache2/conf-available/stanzadeibottoni.conf"

dice()  { printf '\n\033[1m%s\033[0m\n' "$*"; }
bene()  { printf '  \033[32m✓\033[0m %s\n' "$*"; }
male()  { printf '  \033[31m✗\033[0m %s\n' "$*" >&2; }

if [[ "${EUID}" -ne 0 ]]; then
  male "Serve root: sudo bash deploy/01-apache.sh"
  exit 1
fi

dice "1/5  Controlli"
command -v apache2ctl >/dev/null || { male "Apache non è installato"; exit 1; }
[[ -f "${SORGENTE}" ]] || { male "Manca ${SORGENTE}"; exit 1; }
[[ -f /etc/stanzadeibottoni/config.php ]] || { male "Manca /etc/stanzadeibottoni/config.php: esegui prima 00-avvio.sh"; exit 1; }
bene "Apache presente, configurazione del progetto al suo posto"

# Il sorgente contiene il percorso del progetto: se qualcuno lo ha spostato, va
# rigenerato invece di installare un file che punta altrove.
if ! grep -qF "${PROGETTO}" "${SORGENTE}"; then
  male "Il file ${SORGENTE} non parla di ${PROGETTO}: controllalo prima di installarlo"
  exit 1
fi
bene "Il percorso nella configurazione corrisponde al progetto"

dice "2/5  Moduli"
a2enmod rewrite >/dev/null 2>&1 || true
bene "mod_rewrite abilitato"

dice "3/5  Installazione"
if [[ -f "${DESTINAZIONE}" ]]; then
  cp -a "${DESTINAZIONE}" "${DESTINAZIONE}.bak-$(date +%Y%m%d-%H%M%S)"
  bene "copia di sicurezza della configurazione precedente"
fi
install -m 0644 "${SORGENTE}" "${DESTINAZIONE}"
a2enconf stanzadeibottoni >/dev/null 2>&1 || true
bene "configurazione installata e abilitata"

dice "4/5  Prova della configurazione"
if ! apache2ctl configtest; then
  male "configtest FALLITO: disabilito e non ricarico niente"
  a2disconf stanzadeibottoni >/dev/null 2>&1 || true
  exit 1
fi
bene "configtest superato"
systemctl reload apache2
bene "Apache ricaricato"

dice "5/5  Verifica che risponda davvero"
# Una configurazione installata non è una configurazione che funziona: si
# controlla chiedendo la pagina, non guardando i file.
CODICE="$(curl -s -o /dev/null -w '%{http_code}' -L http://127.0.0.1/stanzadeibottoni/ || echo 000)"
if [[ "${CODICE}" == "200" ]]; then
  bene "la pagina risponde 200"
else
  male "la pagina risponde ${CODICE} — installata ma non funzionante"
  printf '     Guarda: tail -20 /var/log/apache2/error.log\n'
  exit 1
fi

NOME="$(grep -m1 -oP 'ServerName\s+\K\S+' /etc/apache2/sites-enabled/*.conf 2>/dev/null || echo localhost)"
printf '\n  Raggiungibile a:  \033[1mhttps://%s/stanzadeibottoni\033[0m\n\n' "${NOME}"
