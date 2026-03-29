#!/usr/bin/env sh
set -eu

APP_DIR="${APP_DIR:-/opt/apps/pix-withdrawal}"

cd "$APP_DIR"

./docker/entrypoint/reset-runtime-cache.sh
./bin/bootstrap-cli app:schema:migrate
./bin/bootstrap-cli app:seed:case

exec ./bin/bootstrap-http "$@"
