#!/usr/bin/env sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
SETUP_SCRIPT="$PROJECT_DIR/setup-local.sh"

print_usage() {
    cat <<'EOF'
Usage:
  ./start-local.sh [--foreground] [--cli] [service...]

Options:
  --foreground  Keep docker compose attached to the current terminal.
  --cli         Include the CLI container in the startup list.
  --help        Show this help message.

Examples:
  ./start-local.sh
  ./start-local.sh --cli
  ./start-local.sh --foreground app mysql
EOF
}

assert_setup_script_exists() {
    if [ ! -x "$SETUP_SCRIPT" ]; then
        echo "Required executable not found: $SETUP_SCRIPT" >&2
        exit 1
    fi
}

run_setup() {
    "$SETUP_SCRIPT"
}

resolve_runtime_identity() {
    if [ -z "${APP_RUNTIME_UID:-}" ]; then
        APP_RUNTIME_UID=$(id -u)
        export APP_RUNTIME_UID
    fi

    if [ -z "${APP_RUNTIME_GID:-}" ]; then
        APP_RUNTIME_GID=$(id -g)
        export APP_RUNTIME_GID
    fi
}

resolve_compose_command() {
    if docker compose version >/dev/null 2>&1; then
        echo "docker compose"
        return
    fi

    if docker-compose version >/dev/null 2>&1; then
        echo "docker-compose"
        return
    fi

    echo "Docker Compose is required" >&2
    exit 1
}

main() {
    run_in_background="true"
    include_cli_runtime="false"

    while [ "$#" -gt 0 ]; do
        case "$1" in
            --foreground)
                run_in_background="false"
                shift
                ;;
            --cli)
                include_cli_runtime="true"
                shift
                ;;
            --help)
                print_usage
                exit 0
                ;;
            --)
                shift
                break
                ;;
            -*)
                echo "Unknown option: $1" >&2
                print_usage >&2
                exit 1
                ;;
            *)
                break
                ;;
        esac
    done

    assert_setup_script_exists
    run_setup
    resolve_runtime_identity

    if [ "$#" -eq 0 ]; then
        set -- app app-worker-process app-scheduler mysql kafka mailhog
    fi

    if [ "$include_cli_runtime" = "true" ]; then
        set -- "$@" app-cli
    fi

    cd "$PROJECT_DIR"

    compose_command=$(resolve_compose_command)

    echo "Starting services: $*"
    echo "Runtime identity: ${APP_RUNTIME_UID}:${APP_RUNTIME_GID}"

    if [ "$run_in_background" = "true" ]; then
        # `set --` above guarantees tokenized services and avoids shell parsing surprises.
        # shellcheck disable=SC2086
        exec $compose_command up --build -d "$@"
    fi

    # shellcheck disable=SC2086
    exec $compose_command up --build "$@"
}

main "$@"
