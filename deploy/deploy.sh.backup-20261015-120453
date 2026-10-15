#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="/opt/kuber/clients/KairoCORE"
IMAGE="ghcr.io/tinotendahlatywayo/kairocore"
LOCAL_IMAGE="kairocore-app:latest"

SHA="${1:-}"

if [[ ! "$SHA" =~ ^[0-9a-f]{40}$ ]]; then
    echo "Usage: $0 <40-character-git-commit-sha>" >&2
    exit 2
fi

cd "$APP_DIR"

# Prevent simultaneous deployments to this client project.
exec 9>"$APP_DIR/deploy/.deploy.lock"
if ! flock -n 9; then
    echo "Another KairoCORE deployment is already running." >&2
    exit 1
fi

PREVIOUS_ID="$(docker image inspect "$LOCAL_IMAGE" \
    --format '{{.Id}}' 2>/dev/null || true)"
NEW_IMAGE="${IMAGE}:${SHA}"
UPDATED=0

rollback() {
    rc=$?
    trap - ERR
    set +e

    if [[ "$UPDATED" -eq 1 && -n "$PREVIOUS_ID" ]]; then
        echo "Deployment failed. Restoring previous application image..."
        docker image tag "$PREVIOUS_ID" "$LOCAL_IMAGE"

        docker compose up -d --no-build --no-deps \
            kairocore-web kairocore-queue kairocore-scheduler
    else
        echo "Automatic image rollback unavailable; inspect logs."
    fi

    exit "$rc"
}
trap rollback ERR

echo "Pulling image: $NEW_IMAGE"
docker pull "$NEW_IMAGE"

docker image tag "$NEW_IMAGE" "$LOCAL_IMAGE"
UPDATED=1

echo "Updating KairoCORE web service..."
docker compose up -d --no-build --no-deps kairocore-web

WEB_ID="$(docker compose ps -q kairocore-web)"
if [[ -z "$WEB_ID" ]]; then
    echo "Web container was not created." >&2
    false
fi

echo "Waiting for web health check (up to 5 minutes)..."
HEALTHY=0

for i in $(seq 1 60); do
    STATUS="$(docker inspect "$WEB_ID" \
        --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}missing{{end}}')"

    if [[ "$STATUS" == "healthy" ]]; then
        HEALTHY=1
        break
    fi

    if [[ "$STATUS" == "unhealthy" || "$STATUS" == "missing" ]]; then
        docker logs --tail 100 "$WEB_ID" || true
        false
    fi

    sleep 5
done

if [[ "$HEALTHY" -ne 1 ]]; then
    echo "Web health check timed out." >&2
    docker logs --tail 100 "$WEB_ID" || true
    false
fi

echo "Web is healthy. Updating queue and scheduler..."
docker compose up -d --no-build --no-deps \
    kairocore-queue kairocore-scheduler

docker compose ps
echo "KairoCORE deployment succeeded: $SHA"

trap - ERR
