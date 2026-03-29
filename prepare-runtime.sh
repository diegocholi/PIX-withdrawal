#!/usr/bin/env sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
APP_DIR="${1:-$PROJECT_DIR}"

assert_directory_exists() {
    directory_path="$1"

    if [ ! -d "$directory_path" ]; then
        echo "Required directory not found: $directory_path" >&2
        exit 1
    fi
}

prepare_runtime_directories() {
    mkdir -p \
        "$APP_DIR/runtime/cache" \
        "$APP_DIR/runtime/container" \
        "$APP_DIR/runtime/container/proxy" \
        "$APP_DIR/runtime/logs/application" \
        "$APP_DIR/runtime/logs/php" \
        "$APP_DIR/runtime/tmp" \
        "$APP_DIR/storage/tmp"
}

apply_runtime_permissions() {
    find "$APP_DIR/runtime" "$APP_DIR/storage" -type d -exec chmod 0775 {} +
    find "$APP_DIR/runtime" "$APP_DIR/storage" -type f -exec chmod 0664 {} +
}

assert_runtime_is_writable() {
    writable_directory="$1"

    if [ ! -w "$writable_directory" ]; then
        echo "Directory is not writable: $writable_directory" >&2
        exit 1
    fi
}

main() {
    umask 0002

    assert_directory_exists "$APP_DIR"
    prepare_runtime_directories
    apply_runtime_permissions
    assert_runtime_is_writable "$APP_DIR/runtime"
    assert_runtime_is_writable "$APP_DIR/storage"
}

main "$@"
