#!/usr/bin/env sh
set -eu

APP_DIR="${APP_DIR:-/opt/apps/pix-withdrawal}"

cd "$APP_DIR"

mkdir -p runtime/cache runtime/container runtime/container/proxy

find runtime/cache -mindepth 1 -delete
find runtime/container -mindepth 1 -delete

rm -f runtime/hyperf.pid
