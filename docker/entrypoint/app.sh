#!/usr/bin/env sh
set -eu

APP_DIR="${APP_DIR:-/opt/apps/pix-withdrawal}"
APP_RUNTIME_GID="${APP_RUNTIME_GID:-0}"
APP_RUNTIME_UID="${APP_RUNTIME_UID:-0}"
APP_TIMEZONE="${APP_TIMEZONE:-UTC}"
PREPARE_RUNTIME_SCRIPT="${APP_DIR}/prepare-runtime.sh"

assert_command_exists() {
    command_name="$1"

    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Required command not found: $command_name" >&2
        exit 1
    fi
}

assert_directory_exists() {
    directory_path="$1"

    if [ ! -d "$directory_path" ]; then
        echo "Required directory not found: $directory_path" >&2
        exit 1
    fi
}

assert_file_exists() {
    file_path="$1"

    if [ ! -f "$file_path" ]; then
        echo "Required file not found: $file_path" >&2
        exit 1
    fi
}

ensure_application_dependencies() {
    composer_manifest_path="$APP_DIR/composer.json"
    autoload_file_path="$APP_DIR/vendor/autoload.php"

    if [ -f "$autoload_file_path" ]; then
        return
    fi

    if [ ! -f "$composer_manifest_path" ]; then
        echo "Required file not found: $composer_manifest_path" >&2
        exit 1
    fi

    echo "Installing application dependencies"

    composer install \
        --working-dir="$APP_DIR" \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader
}

configure_timezone() {
    export TZ="$APP_TIMEZONE"

    cat > /usr/local/etc/php/conf.d/zz-timezone.ini <<EOF
date.timezone=${APP_TIMEZONE}
EOF
}

align_runtime_ownership() {
    if [ "$APP_RUNTIME_UID" = "0" ] && [ "$APP_RUNTIME_GID" = "0" ]; then
        return
    fi

    chown -R "${APP_RUNTIME_UID}:${APP_RUNTIME_GID}" \
        "$APP_DIR/runtime" \
        "$APP_DIR/storage"
}

run_application_command() {
    if [ "$APP_RUNTIME_UID" = "0" ] && [ "$APP_RUNTIME_GID" = "0" ]; then
        exec "$@"
    fi

    exec gosu "${APP_RUNTIME_UID}:${APP_RUNTIME_GID}" "$@"
}

main() {
    assert_command_exists gosu
    assert_command_exists php
    assert_command_exists composer
    assert_command_exists sh
    assert_directory_exists "$APP_DIR"
    assert_file_exists "$PREPARE_RUNTIME_SCRIPT"

    umask 0002
    sh "$PREPARE_RUNTIME_SCRIPT" "$APP_DIR"
    ensure_application_dependencies
    configure_timezone
    align_runtime_ownership

    run_application_command "$@"
}

main "$@"
