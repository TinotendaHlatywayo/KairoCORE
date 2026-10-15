#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="/opt/kuber/clients/KairoCORE"
IMAGE="kairocore-app"
LOCAL_IMAGE="${IMAGE}:latest"
INCOMING_DIR="${APP_DIR}/deploy/incoming"

SHA="${1:-}"
ARCHIVE="${2:-}"

if [[ ! "$SHA" =~ ^[0-9a-f]{40}$ ]] || [[ -z "$ARCHIVE" ]]; then
    echo "Usage: $0 <40-character-commit-SHA> <image-archive>" >&2
    exit 2
fi

if [[ ! -f "$ARCHIVE" ]]; then
    echo "Image archive not found: $ARCHIVE" >&2
    exit 2
fi

case "$(realpath "$ARCHIVE")" in
    "$(realpath "$INCOMING_DIR")"/*) ;;
    *)
        echo "Archive must be inside $INCOMING_DIR" >&2
        exit 2
        ;;
esac

cd "$APP_DIR"

exec 9>"${APP_DIR}/deploy/.deploy.lock"
if ! flock -n 9; then
    echo "Another KairoCORE deployment is running." >&2
    exit 1
fi

# Confirm Compose is valid before changing the image.
docker compose config -q

# Record the currently deployed image for rollback.
PREVIOUS_ID="$(docker image inspect "$LOCAL_IMAGE" \
    --format '{{.Id}}' 2>/dev/null || true)"

NEW_IMAGE="${IMAGE}:${SHA}"
UPDATED=0

rollback() {
    rc=$?
    trap - ERR
    set +e

    if [[ "$UPDATED" -eq 1 && -n "$PREVIOUS_ID" ]]; then
        echo "Deployment failed. Restoring previous image..."

        docker image tag "$PREVIOUS_ID" "$LOCAL_IMAGE"

        docker compose up -d --no-build --no-deps \
            kairocore-web

        echo "Previous web image restored. Check its health and logs."
    else
        echo "Automatic rollback unavailable; inspect container logs."
    fi

    exit "$rc"
}
trap rollback ERR

echo "Loading Docker image archive..."
docker load -i "$ARCHIVE"

if ! docker image inspect "$NEW_IMAGE" >/dev/null 2>&1; then
    echo "Expected image not found in archive: $NEW_IMAGE" >&2
    false
fi

echo "Tagging new image as latest..."
docker image tag "$NEW_IMAGE" "$LOCAL_IMAGE"
UPDATED=1

echo "Updating web service..."
docker compose up -d --no-build --no-deps kairocore-web

WEB_ID="$(docker compose ps -q kairocore-web)"
if [[ -z "$WEB_ID" ]]; then
    echo "Web container was not created." >&2
    false
fi

echo "Waiting up to 7.5 minutes for web health..."
HEALTHY=0

for i in $(seq 1 90); do
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

echo "Final service status:"
docker compose ps

echo "Deployment succeeded: $SHA"

# Remove the transferred archive only after successful deployment.
rm -f -- "$ARCHIVE"

trap - ERR
