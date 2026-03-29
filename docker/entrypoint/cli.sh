#!/usr/bin/env sh
set -eu

APP_DIR="${APP_DIR:-/opt/apps/pix-withdrawal}"

cd "$APP_DIR"

./docker/entrypoint/reset-runtime-cache.sh

if [ "$#" -eq 0 ]; then
    exec tail -f /dev/null
fi

exec ./bin/bootstrap-cli "$@"
