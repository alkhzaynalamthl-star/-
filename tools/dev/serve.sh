#!/usr/bin/env bash
# Serve the local development store (see tools/dev/setup.sh).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DEV="${DEV_DIR:-$ROOT/.dev}"
PORT="${PORT:-8080}"
cd "$DEV"
echo "Optimum Closets dev store → http://localhost:$PORT   (English: /en/, concepts: /wp-content/themes/optimum-closets/concepts/)"
PHP_CLI_SERVER_WORKERS="${WORKERS:-6}" exec php -S "0.0.0.0:$PORT" -t wp router.php
