#!/usr/bin/env bash
set -euo pipefail

VPS_HOST="191.252.200.172"
VPS_USER="root"
VPS_KEY="${HOME}/.ssh/conectta_vps"
REMOTE_BACKUP_DIR="/var/backups/conectta/mysql"
LOCAL_BACKUP_DIR="storage/app/private/backups/production-db"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "${ROOT_DIR}"

ssh_prod() {
    ssh -F /dev/null -i "${VPS_KEY}" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_HOST}" "$@"
}

echo "Backups na VPS"
echo "=============="

ssh_prod "bash -s" <<'REMOTE'
set -euo pipefail

BACKUP_ROOT="/var/backups/conectta/mysql"

for dir in daily weekly; do
    path="${BACKUP_ROOT}/${dir}"
    echo
    echo "${dir}: ${path}"

    if [[ ! -d "${path}" ]]; then
        echo "  pasta nao encontrada"
        continue
    fi

    found=0
    while IFS= read -r file; do
        found=1
        size="$(du -h "${file}" | awk '{print $1}')"
        date="$(stat -c '%y' "${file}" | cut -d'.' -f1)"
        sha_file="${file}.sha256"
        sha="sem checksum"

        if [[ -f "${sha_file}" ]]; then
            sha="$(awk '{print $1}' "${sha_file}")"
        fi

        printf '  %s | %s | %s | sha256=%s\n' "${date}" "${size}" "$(basename "${file}")" "${sha}"
    done < <(find "${path}" -type f -name '*.sql.gz' -printf '%T@ %p\n' | sort -nr | cut -d' ' -f2-)

    if [[ "${found}" == "0" ]]; then
        echo "  nenhum backup encontrado"
    fi
done

echo
echo "Cron:"
if [[ -f /etc/cron.d/conectta-db-backup ]]; then
    sed 's/^/  /' /etc/cron.d/conectta-db-backup
else
    echo "  cron nao encontrado"
fi

echo
echo "Ultimas linhas do log:"
if [[ -f /var/log/conectta-db-backup.log ]]; then
    tail -n 10 /var/log/conectta-db-backup.log | sed 's/^/  /'
else
    echo "  log ainda nao existe"
fi

echo
echo "Google Drive / rclone:"
ENV_FILE="/etc/default/conectta-db-backup"

if ! command -v rclone >/dev/null 2>&1; then
    echo "  rclone nao instalado"
elif [[ ! -f "${ENV_FILE}" ]]; then
    echo "  rclone instalado, mas ${ENV_FILE} nao existe"
else
    set -a
    # shellcheck source=/dev/null
    source "${ENV_FILE}"
    set +a

    RCLONE_REMOTE="${RCLONE_REMOTE:-}"
    RCLONE_DESTINATION="${RCLONE_DESTINATION:-Conectta/backups/mysql}"

    if [[ -z "${RCLONE_REMOTE}" ]]; then
        echo "  rclone instalado, mas RCLONE_REMOTE esta vazio em ${ENV_FILE}"
    else
        echo "  destino: ${RCLONE_REMOTE}:${RCLONE_DESTINATION}"

        for dir in daily weekly; do
            echo
            echo "  ${dir}:"

            output="$(rclone lsf "${RCLONE_REMOTE}:${RCLONE_DESTINATION}/${dir}" --files-only --format 'tsp' 2>&1)" || {
                if grep -qi 'directory not found' <<<"${output}"; then
                    echo "    nenhum backup encontrado"
                else
                    sed 's/^/    erro: /' <<<"${output}"
                fi
                continue
            }

            if [[ -z "${output}" ]]; then
                echo "    nenhum backup encontrado"
                continue
            fi

            sort -r <<<"${output}" \
                | head -n 10 \
                | while IFS=';' read -r date size file; do
                    [[ -n "${file}" ]] || continue
                    printf '    %s | %s bytes | %s\n' "${date}" "${size}" "${file}"
                done
        done
    fi
fi
REMOTE

echo
echo "Ultimos 2 backups baixados localmente"
echo "====================================="

if [[ ! -d "${LOCAL_BACKUP_DIR}" ]]; then
    echo "Pasta local nao encontrada: ${LOCAL_BACKUP_DIR}"
    exit 0
fi

if ! find "${LOCAL_BACKUP_DIR}" -type f -name '*.sql.gz' | grep -q .; then
    echo "Nenhum backup local encontrado em ${LOCAL_BACKUP_DIR}"
    exit 0
fi

find "${LOCAL_BACKUP_DIR}" -type f -name '*.sql.gz' -printf '%T@ %p\n' \
    | sort -nr \
    | head -n 2 \
    | cut -d' ' -f2- \
    | while IFS= read -r file; do
        size="$(du -h "${file}" | awk '{print $1}')"
        date="$(stat -c '%y' "${file}" | cut -d'.' -f1)"
        sha="$(sha256sum "${file}" | awk '{print $1}')"
        printf '  %s | %s | %s | sha256=%s\n' "${date}" "${size}" "$(basename "${file}")" "${sha}"
    done
