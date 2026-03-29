#!/usr/bin/env sh
set -eu

APP_DIR="${APP_DIR:-/opt/apps/pix-withdrawal}"
SCHEDULER_INTERVAL_SECONDS="${WITHDRAW_SCHEDULER_POLL_INTERVAL_SECONDS:-5}"
SCHEDULER_BATCH_SIZE="${WITHDRAW_SCHEDULER_BATCH_SIZE:-100}"

cd "$APP_DIR"

./docker/entrypoint/reset-runtime-cache.sh

while true; do
    ./bin/bootstrap-cli withdraw:scheduler:run --force --batch-size="${SCHEDULER_BATCH_SIZE}"
    sleep "${SCHEDULER_INTERVAL_SECONDS}"
done
