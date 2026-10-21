#!/bin/sh
set -eu
# This container has its own disposable database; it never mounts the Compose volume.
docker run --rm --stop-timeout=3 --label minicrm.browser-test=true --name minicrm-browser-check \
  -p 127.0.0.1:8010:8000 \
  -e APP_ENV=local -e APP_DEBUG=false \
  -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
  -e APP_URL=http://127.0.0.1:8010 \
  -e DB_CONNECTION=sqlite -e DB_DATABASE=/tmp/minicrm-browser.sqlite \
  -e SESSION_DRIVER=file -e CACHE_STORE=array -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array \
  -e SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8010,localhost:8010 \
  minicrm-local:latest &
server_pid=$!
cleanup() {
  docker stop --timeout=3 minicrm-browser-check >/dev/null 2>&1 || true
  wait "$server_pid" 2>/dev/null || true
}
trap cleanup EXIT
trap 'exit 0' INT TERM
wait "$server_pid"
