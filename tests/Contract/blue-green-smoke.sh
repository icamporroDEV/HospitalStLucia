#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
ROOT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/../.." && pwd)
. "$ROOT_DIR/deploy/bin/_common.sh"

cleanup()
{
    compose down -v --remove-orphans >/dev/null 2>&1 || true
}

trap cleanup EXIT HUP INT TERM

docker image inspect hospital-st-lucia:local >/dev/null 2>&1 || {
    echo "ERROR: primero construya hospital-st-lucia:local" >&2
    exit 1
}

rm -rf "$STATE_DIR"
ensure_state
compose down -v --remove-orphans >/dev/null 2>&1 || true
compose up -d --build

wait_healthy his-blue
wait_healthy his-green
wait_healthy nginx

[ "$(current_color)" = blue ] || { echo "ERROR: el laboratorio no inició en Blue" >&2; exit 1; }
"$ROOT_DIR/deploy/bin/switch-color" green
[ "$(current_color)" = green ] || { echo "ERROR: el switch a Green falló" >&2; exit 1; }

compose up -d --no-deps --force-recreate nginx
wait_healthy nginx
[ "$(current_color)" = green ] || { echo "ERROR: Nginx perdió el color activo al recrearse" >&2; exit 1; }

"$ROOT_DIR/deploy/bin/rollback-color" blue
[ "$(current_color)" = blue ] || { echo "ERROR: el rollback a Blue falló" >&2; exit 1; }

if "$ROOT_DIR/deploy/bin/switch-color" red >/dev/null 2>&1; then
    echo "ERROR: se aceptó un color inválido" >&2
    exit 1
fi
[ "$(current_color)" = blue ] || { echo "ERROR: el color inválido modificó el tráfico" >&2; exit 1; }

echo "Blue-Green smoke OK: Blue -> Green -> persistencia -> Blue"
