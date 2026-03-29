#!/usr/bin/env sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
ENV_EXAMPLE_FILE="$PROJECT_DIR/.env.example"
ENV_FILE="$PROJECT_DIR/.env"
PREPARE_RUNTIME_SCRIPT="$PROJECT_DIR/prepare-runtime.sh"

assert_command_exists() {
    command_name="$1"

    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Required command not found: $command_name" >&2
        exit 1
    fi
}

prepare_environment_file() {
    if [ -f "$ENV_FILE" ]; then
        echo ".env already exists"
        return
    fi

    cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
    echo ".env created from .env.example"
}

main() {
    assert_command_exists docker

    if docker compose version >/dev/null 2>&1; then
        :
    elif docker-compose version >/dev/null 2>&1; then
        :
    else
        echo "Docker Compose is required" >&2
        exit 1
    fi

    if [ ! -f "$ENV_EXAMPLE_FILE" ]; then
        echo "Required file not found: $ENV_EXAMPLE_FILE" >&2
        exit 1
    fi

    if [ ! -x "$PREPARE_RUNTIME_SCRIPT" ]; then
        echo "Required executable not found: $PREPARE_RUNTIME_SCRIPT" >&2
        exit 1
    fi

    prepare_environment_file
    "$PREPARE_RUNTIME_SCRIPT" "$PROJECT_DIR"

    echo "Runtime directories prepared"

    echo "Local setup finished"
    echo "Next step: ./start-local.sh"
}

main "$@"
