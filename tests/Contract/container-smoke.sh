#!/bin/sh

set -eu

base_url="${1:?Uso: container-smoke.sh BASE_URL VERSION_ESPERADA}"
expected_version="${2:?Uso: container-smoke.sh BASE_URL VERSION_ESPERADA}"

request()
{
    path=$1
    attempt=1

    while [ "$attempt" -le 3 ]; do
        if body=$(curl --fail --silent --show-error \
            --connect-timeout 2 --max-time 5 "${base_url}${path}"); then
            printf '%s' "$body"
            return 0
        fi

        attempt=$((attempt + 1))
        sleep 1
    done

    echo "ERROR: ${path} no respondió correctamente después de 3 intentos" >&2
    return 1
}

live=$(request /health/live)
ready=$(request /health/ready)
version=$(request /version)
appointments=$(request /api/turnos)

printf '%s' "$live" | grep -Eq '"status"[[:space:]]*:[[:space:]]*"ok"' \
    || { echo "ERROR: liveness inválido" >&2; exit 1; }
printf '%s' "$ready" | grep -Eq '"status"[[:space:]]*:[[:space:]]*"ok"' \
    || { echo "ERROR: readiness inválido" >&2; exit 1; }
printf '%s' "$version" | grep -Fq "\"version\":\"${expected_version}\"" \
    || { echo "ERROR: versión esperada ${expected_version} no encontrada" >&2; exit 1; }
printf '%s' "$version" | grep -Eq '"deploymentColor"[[:space:]]*:[[:space:]]*"[^"]+"' \
    || { echo "ERROR: color de despliegue ausente" >&2; exit 1; }
printf '%s' "$appointments" | grep -Eq '"id"[[:space:]]*:[[:space:]]*"T-[0-9]+"' \
    || { echo "ERROR: la lista de turnos está vacía o no respeta el contrato" >&2; exit 1; }

echo "Smoke test OK: ${base_url} sirve la versión ${expected_version}"
