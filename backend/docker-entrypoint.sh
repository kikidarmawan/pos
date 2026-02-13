#!/bin/sh
set -e
# GCP Cloud Run menyetel PORT (biasanya 8080). Lokal default 8080.
PORT="${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
