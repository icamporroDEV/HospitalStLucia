#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
ROOT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/../.." && pwd)
COMPOSE_FILE="$ROOT_DIR/docker-compose.yml"
STATE_FILE=${HIS_STATE_FILE:-$ROOT_DIR/deploy/runtime/state.env}
STATE_DIR=$(dirname -- "$STATE_FILE")
STATE_EXAMPLE="$ROOT_DIR/deploy/state.env.example"
PUBLIC_URL=${PUBLIC_URL:-http://127.0.0.1:8080}
BLUE_URL=${BLUE_URL:-http://127.0.0.1:18081}
GREEN_URL=${GREEN_URL:-http://127.0.0.1:18082}

ensure_state()
{
    mkdir -p "$STATE_DIR"
    if [ ! -f "$STATE_FILE" ]; then
        cp "$STATE_EXAMPLE" "$STATE_FILE"
    fi
}

compose()
{
    ensure_state
    docker compose --env-file "$STATE_FILE" -f "$COMPOSE_FILE" "$@"
}

validate_color()
{
    case "${1:-}" in
        blue|green) return 0 ;;
        *) echo "ERROR: color inválido; use blue o green" >&2; return 1 ;;
    esac
}

validate_image()
{
    image=${1:-}
    [ -n "$image" ] || { echo "ERROR: imagen ausente" >&2; return 1; }
    printf '%s' "$image" | grep -Eq '^[A-Za-z0-9][A-Za-z0-9._/:@-]*$' \
        || { echo "ERROR: referencia de imagen inválida" >&2; return 1; }
}

service_for_color()
{
    validate_color "$1"
    printf 'his-%s\n' "$1"
}

url_for_color()
{
    case "$1" in
        blue) printf '%s\n' "$BLUE_URL" ;;
        green) printf '%s\n' "$GREEN_URL" ;;
        *) validate_color "$1" ;;
    esac
}

json_string()
{
    key=$1
    sed -n "s/.*\"${key}\":\"\([^\"]*\)\".*/\1/p"
}

current_color()
{
    attempt=1
    while [ "$attempt" -le 5 ]; do
        if response=$(curl --fail --silent --show-error --connect-timeout 2 --max-time 5 "$PUBLIC_URL/version" 2>/dev/null); then
            color=$(printf '%s' "$response" | json_string deploymentColor)
            case "$color" in
                blue|green) printf '%s\n' "$color"; return 0 ;;
            esac
        fi
        attempt=$((attempt + 1))
        sleep 1
    done
    echo "ERROR: no se pudo determinar el color público" >&2
    return 1
}

wait_public_color()
{
    expected=$1
    attempt=1
    while [ "$attempt" -le 10 ]; do
        if observed=$(current_color 2>/dev/null) && [ "$observed" = "$expected" ]; then
            return 0
        fi
        attempt=$((attempt + 1))
        sleep 1
    done
    echo "ERROR: el endpoint público no cambió a ${expected}" >&2
    return 1
}

wait_healthy()
{
    service=$1
    attempt=1
    while [ "$attempt" -le 30 ]; do
        container_id=$(compose ps -q "$service")
        if [ -n "$container_id" ]; then
            health=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container_id")
            [ "$health" = healthy ] && return 0
            [ "$health" = unhealthy ] && break
        fi
        attempt=$((attempt + 1))
        sleep 1
    done
    echo "ERROR: ${service} no alcanzó estado healthy" >&2
    return 1
}

smoke_color()
{
    color=$1
    base_url=$(url_for_color "$color")
    version_json=$(curl --fail --silent --show-error --connect-timeout 2 --max-time 5 "$base_url/version")
    version=$(printf '%s' "$version_json" | json_string version)
    commit_sha=$(printf '%s' "$version_json" | json_string commitSha)
    build_time=$(printf '%s' "$version_json" | json_string buildTime)
    if [ -z "$version" ] || [ "$version" = dev ] \
        || [ -z "$commit_sha" ] || [ "$commit_sha" = unknown ] \
        || [ -z "$build_time" ] || [ "$build_time" = unknown ]; then
        echo "ERROR: ${color} tiene metadatos de release incompletos" >&2
        return 1
    fi
    "$ROOT_DIR/tests/Contract/container-smoke.sh" "$base_url" "$version"
}

update_state()
{
    key=$1
    value=$2
    temporary="$STATE_FILE.next"
    awk -v key="$key" -v value="$value" '
        BEGIN { found = 0 }
        index($0, key "=") == 1 { print key "=" value; found = 1; next }
        { print }
        END { if (!found) print key "=" value }
    ' "$STATE_FILE" > "$temporary"
    mv "$temporary" "$STATE_FILE"
}
