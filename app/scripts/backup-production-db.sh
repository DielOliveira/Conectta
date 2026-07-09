#!/usr/bin/env bash
set -euo pipefail

VPS_HOST="191.252.200.172"
VPS_USER="root"
VPS_KEY="${HOME}/.ssh/conectta_vps"
REMOTE_BACKUP_SCRIPT="/usr/local/sbin/conectta-db-backup"
REMOTE_BACKUP_DIR="/var/backups/conectta/mysql"
REMOTE_CRON="/etc/cron.d/conectta-db-backup"
REMOTE_ENV="/etc/default/conectta-db-backup"
LOCAL_BACKUP_DIR="storage/app/private/backups/production-db"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ACTION="${1:-run-and-download}"

cd "${ROOT_DIR}"

ssh_prod() {
    ssh -F /dev/null -i "${VPS_KEY}" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_HOST}" "$@"
}

install_remote_backup() {
    echo "Instalando rotina de backup na VPS..."

    ssh_prod bash -s <<'REMOTE'
set -euo pipefail

cat >/usr/local/sbin/conectta-db-backup <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/conectta/repo/app"
BACKUP_ROOT="/var/backups/conectta/mysql"
DAILY_DIR="${BACKUP_ROOT}/daily"
WEEKLY_DIR="${BACKUP_ROOT}/weekly"
ENV_FILE="/etc/default/conectta-db-backup"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
WEEKLY_RETENTION_DAYS="${WEEKLY_RETENTION_DAYS:-90}"

if [[ -f "${ENV_FILE}" ]]; then
    set -a
    # shellcheck source=/dev/null
    source "${ENV_FILE}"
    set +a
fi

RCLONE_REMOTE="${RCLONE_REMOTE:-}"
RCLONE_DESTINATION="${RCLONE_DESTINATION:-Conectta/backups/mysql}"
RCLONE_RETENTION_DAYS="${RCLONE_RETENTION_DAYS:-90}"
RCLONE_WEEKLY_RETENTION_DAYS="${RCLONE_WEEKLY_RETENTION_DAYS:-365}"

env_value() {
    php -r '$env = parse_ini_file($argv[1], false, INI_SCANNER_RAW); echo $env[$argv[2]] ?? "";' "${APP_DIR}/.env" "$1"
}

rclone_target() {
    local group="$1"
    local file="$2"

    printf '%s:%s/%s/%s' "${RCLONE_REMOTE}" "${RCLONE_DESTINATION}" "${group}" "$(basename "${file}")"
}

upload_backup_to_drive() {
    local group="$1"
    local file="$2"
    local retention_days="$3"

    if [[ -z "${RCLONE_REMOTE}" ]]; then
        echo "Upload Google Drive: desativado. Configure RCLONE_REMOTE em ${ENV_FILE} para ativar."
        return 0
    fi

    if ! command -v rclone >/dev/null 2>&1; then
        echo "Erro: RCLONE_REMOTE esta configurado, mas rclone nao esta instalado na VPS." >&2
        return 1
    fi

    echo "Enviando backup para Google Drive (${group})..."
    rclone copyto "${file}" "$(rclone_target "${group}" "${file}")"
    rclone copyto "${file}.sha256" "$(rclone_target "${group}" "${file}.sha256")"

    rclone delete "${RCLONE_REMOTE}:${RCLONE_DESTINATION}/${group}" \
        --include '*.sql.gz' \
        --include '*.sql.gz.sha256' \
        --min-age "${retention_days}d" || true

    echo "Upload Google Drive concluido: ${RCLONE_REMOTE}:${RCLONE_DESTINATION}/${group}/$(basename "${file}")"
}

mkdir -p "${DAILY_DIR}" "${WEEKLY_DIR}"
chmod 700 /var/backups/conectta "${BACKUP_ROOT}" "${DAILY_DIR}" "${WEEKLY_DIR}"

DB_HOST="$(env_value DB_HOST)"
DB_PORT="$(env_value DB_PORT)"
DB_DATABASE="$(env_value DB_DATABASE)"
DB_USERNAME="$(env_value DB_USERNAME)"
db_password_value="$(env_value DB_PASSWORD)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

if [[ -z "${db_password_value}" && -r /root/conectta-db-password ]]; then
    db_password_value="$(cat /root/conectta-db-password)"
fi

if [[ -z "${DB_DATABASE}" || -z "${DB_USERNAME}" ]]; then
    echo "Erro: DB_DATABASE ou DB_USERNAME nao encontrado no .env." >&2
    exit 1
fi

TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_FILE="${DAILY_DIR}/conectta-${DB_DATABASE}-${TIMESTAMP}.sql.gz"
TMP_FILE="${BACKUP_FILE}.tmp"

MYSQL_CNF="$(mktemp)"
cleanup() {
    rm -f "${MYSQL_CNF}"
}
trap cleanup EXIT

chmod 600 "${MYSQL_CNF}"
{
    printf '[client]\n'
    printf 'host=%s\n' "${DB_HOST}"
    printf 'port=%s\n' "${DB_PORT}"
    printf 'user=%s\n' "${DB_USERNAME}"
    printf 'password=%s\n' "${db_password_value}"
} > "${MYSQL_CNF}"

mysqldump \
    --defaults-extra-file="${MYSQL_CNF}" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    --no-tablespaces \
    --default-character-set=utf8mb4 \
    "${DB_DATABASE}" | gzip -9 > "${TMP_FILE}"

mv "${TMP_FILE}" "${BACKUP_FILE}"
chmod 600 "${BACKUP_FILE}"
sha256sum "${BACKUP_FILE}" > "${BACKUP_FILE}.sha256"
chmod 600 "${BACKUP_FILE}.sha256"

if [[ "$(date +%u)" == "7" ]]; then
    WEEKLY_FILE="${WEEKLY_DIR}/$(basename "${BACKUP_FILE}")"
    cp "${BACKUP_FILE}" "${WEEKLY_FILE}"
    cp "${BACKUP_FILE}.sha256" "${WEEKLY_FILE}.sha256"
    chmod 600 "${WEEKLY_FILE}" "${WEEKLY_FILE}.sha256"
fi

upload_backup_to_drive daily "${BACKUP_FILE}" "${RCLONE_RETENTION_DAYS}"

if [[ -n "${WEEKLY_FILE:-}" ]]; then
    upload_backup_to_drive weekly "${WEEKLY_FILE}" "${RCLONE_WEEKLY_RETENTION_DAYS}"
fi

find "${DAILY_DIR}" -type f \( -name '*.sql.gz' -o -name '*.sql.gz.sha256' \) -mtime +"${RETENTION_DAYS}" -delete
find "${WEEKLY_DIR}" -type f \( -name '*.sql.gz' -o -name '*.sql.gz.sha256' \) -mtime +"${WEEKLY_RETENTION_DAYS}" -delete

echo "Backup criado: ${BACKUP_FILE}"
sha256sum "${BACKUP_FILE}"
SCRIPT

chmod 700 /usr/local/sbin/conectta-db-backup

if [[ ! -f /etc/default/conectta-db-backup ]]; then
cat >/etc/default/conectta-db-backup <<'ENV'
# Configuracao opcional de upload externo via rclone.
# 1. Instale/configure o rclone como root na VPS.
# 2. Preencha RCLONE_REMOTE com o nome do remote configurado.
#
# Exemplo:
# RCLONE_REMOTE="gdrive"
# RCLONE_DESTINATION="Conectta/backups/mysql"
# RCLONE_RETENTION_DAYS="90"
# RCLONE_WEEKLY_RETENTION_DAYS="365"

RCLONE_REMOTE=""
RCLONE_DESTINATION="Conectta/backups/mysql"
RCLONE_RETENTION_DAYS="90"
RCLONE_WEEKLY_RETENTION_DAYS="365"
ENV
chmod 600 /etc/default/conectta-db-backup
fi

cat >/etc/cron.d/conectta-db-backup <<'CRON'
15 2 * * * root /usr/local/sbin/conectta-db-backup >> /var/log/conectta-db-backup.log 2>&1
CRON

chmod 644 /etc/cron.d/conectta-db-backup
echo "Rotina instalada: /etc/cron.d/conectta-db-backup"
echo "Configuracao opcional: /etc/default/conectta-db-backup"
REMOTE
}

run_remote_backup() {
    echo "Executando backup na VPS..."
    ssh_prod "${REMOTE_BACKUP_SCRIPT}"
}

download_remote_backup() {
    local remote_file="$1"
    local local_file
    local remote_sha
    local local_sha

    mkdir -p "${LOCAL_BACKUP_DIR}"
    local_file="${LOCAL_BACKUP_DIR}/$(basename "${remote_file}")"

    echo "Baixando ${remote_file}..."
    ssh_prod "cat '${remote_file}'" > "${local_file}"

    remote_sha="$(ssh_prod "sha256sum '${remote_file}' | awk '{print \$1}'")"
    local_sha="$(sha256sum "${local_file}" | awk '{print $1}')"

    if [[ "${remote_sha}" != "${local_sha}" ]]; then
        echo "Erro: checksum local difere do checksum da VPS." >&2
        rm -f "${local_file}"
        exit 1
    fi

    echo "Backup baixado e validado: ${local_file}"
    echo "Checksum SHA256: ${local_sha}"
}

latest_remote_backup() {
    ssh_prod "find '${REMOTE_BACKUP_DIR}/daily' -type f -name '*.sql.gz' -printf '%T@ %p\n' | sort -nr | head -n 1 | cut -d' ' -f2-"
}

install_rclone() {
    echo "Instalando rclone na VPS..."
    ssh_prod "if command -v rclone >/dev/null 2>&1; then rclone version | head -n 1; else apt-get update --allow-releaseinfo-change && apt-get install -y rclone; fi"
}

configure_rclone() {
    echo "Abrindo configuracao interativa do rclone na VPS..."
    ssh -t -F /dev/null -i "${VPS_KEY}" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_HOST}" "rclone config"
}

rclone_status() {
    ssh_prod "bash -s" <<'REMOTE'
set -euo pipefail

ENV_FILE="/etc/default/conectta-db-backup"

if command -v rclone >/dev/null 2>&1; then
    rclone version | head -n 1
else
    echo "rclone nao instalado"
    exit 0
fi

if [[ ! -f "${ENV_FILE}" ]]; then
    echo "${ENV_FILE} nao encontrado"
    exit 0
fi

set -a
# shellcheck source=/dev/null
source "${ENV_FILE}"
set +a

RCLONE_REMOTE="${RCLONE_REMOTE:-}"
RCLONE_DESTINATION="${RCLONE_DESTINATION:-Conectta/backups/mysql}"

if [[ -z "${RCLONE_REMOTE}" ]]; then
    echo "RCLONE_REMOTE vazio em ${ENV_FILE}"
    exit 0
fi

echo "Destino configurado: ${RCLONE_REMOTE}:${RCLONE_DESTINATION}"
rclone lsd "${RCLONE_REMOTE}:${RCLONE_DESTINATION}" || true
REMOTE
}

usage() {
    cat <<USAGE
Uso: $0 [acao]

Acoes:
  install            Instala/atualiza script e cron de backup na VPS.
  install-rclone     Instala o pacote rclone na VPS, se ainda nao existir.
  rclone-config      Abre a configuracao interativa do rclone na VPS.
  rclone-status      Mostra se o rclone e o destino estao configurados.
  run                Executa um backup agora na VPS.
  download-latest    Baixa o backup diario mais recente da VPS.
  run-and-download   Instala, executa e baixa o backup criado agora. Padrao.

Google Drive:
  O upload via rclone e ativado na VPS em ${REMOTE_ENV}, preenchendo RCLONE_REMOTE.
  Sem RCLONE_REMOTE, o backup local da VPS continua funcionando normalmente.

Backups locais ficam em:
  ${LOCAL_BACKUP_DIR}
USAGE
}

case "${ACTION}" in
    install)
        install_remote_backup
        ;;
    install-rclone)
        install_rclone
        ;;
    rclone-config)
        configure_rclone
        ;;
    rclone-status)
        rclone_status
        ;;
    run)
        run_remote_backup
        ;;
    download-latest)
        remote_file="$(latest_remote_backup)"
        if [[ -z "${remote_file}" ]]; then
            echo "Erro: nenhum backup encontrado na VPS." >&2
            exit 1
        fi
        download_remote_backup "${remote_file}"
        ;;
    run-and-download)
        install_remote_backup
        output="$(run_remote_backup)"
        echo "${output}"
        remote_file="$(printf '%s\n' "${output}" | awk -F'Backup criado: ' '/Backup criado:/ {print $2}' | tail -n 1)"
        if [[ -z "${remote_file}" ]]; then
            echo "Erro: nao foi possivel identificar o arquivo gerado." >&2
            exit 1
        fi
        download_remote_backup "${remote_file}"
        ;;
    -h|--help|help)
        usage
        ;;
    *)
        echo "Erro: acao desconhecida: ${ACTION}" >&2
        usage
        exit 1
        ;;
esac
