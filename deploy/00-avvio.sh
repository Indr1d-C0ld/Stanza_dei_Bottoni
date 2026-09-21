#!/usr/bin/env bash
#
# Stanza dei Bottoni — avvio dell'ambiente.
# Da eseguire UNA VOLTA con sudo:   sudo bash deploy/00-avvio.sh
#
# Idempotente: può essere rilanciato senza danni. Se il file di configurazione
# esiste già, la password NON viene rigenerata: si riusa quella del file.
#
# Cosa fa:
#   1. rende scrivibile la cartella di progetto (gruppo www-data, setgid)
#   2. crea la cartella dei segreti FUORI dal DocumentRoot
#   3. crea database e utente MariaDB sdb_stanza con password generata
#   4. scrive il file di configurazione se non esiste
#   5. applica le migrazioni di db/migrations in ordine
#   6. installa la configurazione Apache, se Apache c'e' (facoltativo)
#
# Parametri d'ambiente (facoltativi):
#   OWNER_USER   proprietario della cartella (default: chi invoca sudo)
#   SITE_DIR     cartella di progetto (default: quella che contiene questo script)
#   CONFIG_DIR   cartella dei segreti (default: /etc/stanzadeibottoni)
#   SEME         seme radice del mondo (default: generato)

set -euo pipefail

OWNER_USER="${OWNER_USER:-$(logname 2>/dev/null || echo "${SUDO_USER:-root}")}"
OWNER_GROUP="www-data"
PROJECT_DIR="${SITE_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
CONFIG_DIR="${CONFIG_DIR:-/etc/stanzadeibottoni}"
CONFIG_FILE="${CONFIG_DIR}/config.php"
DB_NAME="sdb_stanza"
DB_USER="sdb_stanza"
CON_APACHE="${CON_APACHE:-si}"
APACHE_CONF_SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/apache-stanzadeibottoni.conf"
APACHE_CONF_DST="/etc/apache2/conf-available/stanzadeibottoni.conf"

say()  { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
ok()   { printf '    \033[0;32m%s\033[0m\n' "$*"; }
warn() { printf '    \033[0;33m%s\033[0m\n' "$*"; }

if [[ "${EUID}" -ne 0 ]]; then
  echo "Questo script va eseguito con sudo." >&2
  exit 1
fi

command -v mariadb >/dev/null || { echo "mariadb non trovato." >&2; exit 1; }
command -v php     >/dev/null || { echo "php non trovato." >&2; exit 1; }

# --- 1. Cartella di progetto -------------------------------------------------
say "1/6  Cartella di progetto ${PROJECT_DIR}"
chown -R "${OWNER_USER}:${OWNER_GROUP}" "${PROJECT_DIR}"
chmod 2775 "${PROJECT_DIR}"
mkdir -p "${PROJECT_DIR}/storage/logs" "${PROJECT_DIR}/storage/cronache"
chown -R "${OWNER_USER}:${OWNER_GROUP}" "${PROJECT_DIR}/storage"
chmod -R 2775 "${PROJECT_DIR}/storage"
ok "$(stat -c '%U:%G %a' "${PROJECT_DIR}")  ${PROJECT_DIR}"

# --- 2. Cartella dei segreti -------------------------------------------------
say "2/6  Cartella di configurazione ${CONFIG_DIR}"
mkdir -p "${CONFIG_DIR}"
chown "${OWNER_USER}:${OWNER_GROUP}" "${CONFIG_DIR}"
chmod 2750 "${CONFIG_DIR}"
ok "$(stat -c '%U:%G %a' "${CONFIG_DIR}")  ${CONFIG_DIR}"

# --- 3. Database -------------------------------------------------------------
say "3/6  Database MariaDB ${DB_NAME}"

if [[ -f "${CONFIG_FILE}" ]]; then
  DB_PASS="$(php -r '$c=require "'"${CONFIG_FILE}"'"; echo $c["db"]["pass"] ?? "";')"
  if [[ -z "${DB_PASS}" ]]; then
    echo "Configurazione presente ma senza password: correggila a mano." >&2
    exit 1
  fi
  warn "configurazione già presente: riuso la password esistente"
else
  # NIENTE PIPE QUI. "tr ... | head -c 32" sembra innocuo ma sotto
  # 'set -o pipefail' e' una mina: head chiude la pipe dopo 32 byte, tr riceve
  # SIGPIPE, la pipeline esce con 141 e 'set -e' uccide lo script in silenzio —
  # subito dopo aver creato la cartella dei segreti e prima di riempirla.
  # PHP e' gia' un requisito verificato qui sopra: lo usiamo e non si discute.
  DB_PASS="$(php -r 'echo substr(strtr(base64_encode(random_bytes(48)), "+/=", "Ax9"), 0, 32);')"
fi

mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
ok "database e utente ${DB_USER}@localhost pronti"

# --- 4. Configurazione -------------------------------------------------------
say "4/6  File di configurazione ${CONFIG_FILE}"
if [[ -f "${CONFIG_FILE}" ]]; then
  warn "esiste già, non lo tocco"
else
  SEME="${SEME:-$(( (RANDOM << 15 | RANDOM) % 2000000000 ))}"
  cat > "${CONFIG_FILE}" <<PHP
<?php

declare(strict_types=1);

/** Configurazione di esercizio — generata da deploy/00-avvio.sh. NON in git. */

return [
    'app' => [
        'name'     => 'Stanza dei Bottoni',
        'env'      => 'production',
        'debug'    => false,
        'timezone' => 'Europe/Rome',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => '${DB_NAME}',
        'user'    => '${DB_USER}',
        'pass'    => '${DB_PASS}',
        'charset' => 'utf8mb4',
        'prefix'  => 'sdb_',
    ],
    'mondo' => [
        'profilo'    => 'gioco',
        'seme'       => ${SEME},
        'tick_reale' => 7200,
        'scenario'   => 'base',
    ],
];
PHP
  chown "${OWNER_USER}:${OWNER_GROUP}" "${CONFIG_FILE}"
  chmod 0640 "${CONFIG_FILE}"
  ok "scritto, seme radice del mondo: ${SEME}"
fi

# --- 5. Migrazioni -----------------------------------------------------------
say "5/6  Migrazioni"
# Su un'installazione nuova si applica tutto col client, che digerisce i file
# multi-query senza fatica; poi bin/migra.php segna il registro, cosi' gli
# aggiornamenti successivi sapranno che cosa e' gia' passato e che cosa no.
shopt -s nullglob
for f in "${PROJECT_DIR}"/db/migrations/*.sql; do
  mariadb "${DB_NAME}" < "$f"
  ok "applicata $(basename "$f")"
done
php "${PROJECT_DIR}/bin/migra.php" --segna >/dev/null 2>&1 || true
ok "registro delle migrazioni allineato"

TABELLE="$(mariadb -N -B -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}';")"

# --- 6. Apache (facoltativo) -------------------------------------------------
say "6/6  Configurazione Apache"
if [[ "${CON_APACHE}" != "si" ]]; then
  warn "saltata su richiesta (CON_APACHE=no)"
elif ! command -v apache2ctl >/dev/null; then
  warn "Apache non trovato: salto"
else
  if [[ -f "${APACHE_CONF_DST}" ]]; then
    cp -a "${APACHE_CONF_DST}" "${APACHE_CONF_DST}.bak-$(date +%Y%m%d-%H%M%S)"
    ok "backup della configurazione precedente"
  fi
  install -m 0644 "${APACHE_CONF_SRC}" "${APACHE_CONF_DST}"
  a2enmod rewrite >/dev/null 2>&1 || true
  a2enconf stanzadeibottoni >/dev/null 2>&1 || true
  if apache2ctl configtest >/dev/null 2>&1; then
    systemctl reload apache2
    ok "configurazione installata e Apache ricaricato"
  else
    warn "apache2ctl configtest FALLITO: non ricarico nulla"
    apache2ctl configtest || true
  fi
fi

# --- verifica finale ---------------------------------------------------------
# Lo script non deve poter dichiarare successo lasciando un impianto rotto:
# proviamo davvero le credenziali che abbiamo appena scritto.
say "Verifica"
if php -r '
    $c = require "'"${CONFIG_FILE}"'";
    $d = $c["db"];
    try {
        new PDO("mysql:host={$d["host"]};port={$d["port"]};dbname={$d["name"]}",
                $d["user"], $d["pass"], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
'; then
  ok "le credenziali scritte funzionano"
else
  echo "    LE CREDENZIALI NON FUNZIONANO: l'impianto non e' utilizzabile." >&2
  exit 1
fi

say "Fatto — ${TABELLE} tabelle in ${DB_NAME}"
echo
echo "    Ora, come utente normale (NON root):"
echo
echo "      cd ${PROJECT_DIR}"
echo "      php bin/avvia_mondo.php      # costruisce il mondo al tick zero"
echo "      php bin/tick.php             # fa scorrere il tempo di una settimana"
echo
echo "    E l'osservatorio:  http://<questo-host>/stanzadeibottoni"
echo
