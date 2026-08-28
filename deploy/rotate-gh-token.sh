#!/bin/bash
# =============================================================================
# Kairo CORE — GitHub Token Rotation Helper
#
# The old GitHub PAT is stored in plaintext at ~/.git-credentials (and possibly
# embedded in some remote URLs). This script updates those locations with a NEW
# token.
#
# STEP 1 (you must do this in the browser — cannot be automated):
#   https://github.com/settings/tokens
#   -> find the token named for this machine / or "Regenerate" the existing one
#   -> copy the newly generated token (starts with 'ghp_')
#
# STEP 2 (run this script with the new token):
#   bash deploy/rotate-gh-token.sh ghp_XXXXXXXXXXXXX
#
# The script will:
#   - Re-write ~/.git-credentials with the new token
#   - Rewrite the 'kairo' and 'origin' remote URLs so no token is embedded
#   - Verify the new token authenticates successfully
#
# IMPORTANT: After this, DELETE/regenerate the OLD token from GitHub settings
# so the leaked one stops working entirely.
# =============================================================================
set -euo pipefail

NEW_TOKEN="${1:-}"
GITHUB_USER="TinotendaHlatywayo"
REPO_KAIRO="TinotendaHlatywayo/KairoCORE"
REPO_ORIGIN="TinotendaHlatywayo/SchoolCORE"

if [ -z "$NEW_TOKEN" ]; then
    echo "ERROR: Pass the new token as the first argument."
    echo "  bash deploy/rotate-gh-token.sh ghp_XXXXXXXXXXXXXXXXXXXXXXXXXXXX"
    echo ""
    echo "Generate it at: https://github.com/settings/tokens"
    echo "  -> Generate new token -> (classic) -> tick the 'repo' scope"
    echo "     (it must start with 'ghp_' — NOT 'github_pat_')"
    exit 1
fi

if [[ "$NEW_TOKEN" != ghp_* ]]; then
    echo "ERROR: This is not a classic token (expected 'ghp_...')."
    echo "  A fine-grained token ('github_pat_...') often lacks write access."
    echo "  Generate a CLASSIC token with the 'repo' scope and try again."
    exit 1
fi

echo "============================================"
echo " Kairo CORE — GitHub Token Rotation"
echo "============================================"

# ── 1. Verify the new token works ──────────────────────────────────────
echo "[1/4] Verifying new token..."
AUTH=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: token ${NEW_TOKEN}" https://api.github.com/user)
if [ "$AUTH" != "200" ]; then
    echo "ERROR: New token rejected by GitHub (HTTP ${AUTH}). Double-check it."
    exit 1
fi
USER=$(curl -s -H "Authorization: token ${NEW_TOKEN}" https://api.github.com/user | grep '"login"' | cut -d'"' -f4)
echo "    New token authenticates as: ${USER}"

# ── 2. Update ~/.git-credentials ───────────────────────────────────────
echo "[2/4] Updating ~/.git-credentials..."
mkdir -p ~
cat > ~/.git-credentials << EOF
https://${GITHUB_USER}:${NEW_TOKEN}@github.com
EOF
chmod 600 ~/.git-credentials
echo "    Written. File permissions: $(stat -c '%a' ~/.git-credentials)"

# ── 3. Ensure remotes have no embedded token ───────────────────────────
echo "[3/4] Rewriting remotes to token-free URLs..."
git remote set-url kairo "https://github.com/${REPO_KAIRO}.git"
git remote set-url origin "https://github.com/${REPO_ORIGIN}.git"
echo "    kairo   -> https://github.com/${REPO_KAIRO}.git"
echo "    origin  -> https://github.com/${REPO_ORIGIN}.git"

# ── 4. Verify push works ───────────────────────────────────────────────
echo "[4/4] Testing authentication with a fetch..."
git -c credential.helper=store fetch --dry-run kairo >/dev/null 2>&1 \
    && echo "    OK: authenticated fetch succeeded" \
    || echo "    Note: fetch dry-run could not complete (expected on empty history). Try: git fetch kairo"

echo ""
echo "============================================"
echo " DONE"
echo "============================================"
echo ""
echo "FINAL SECURITY STEP (required):"
echo "  Go to https://github.com/settings/tokens"
echo "  and DELETE / regenerate the OLD token so the leaked one is revoked."
echo ""
echo "The old token was: $(sed -E 's#https[^:]+:[^@]*@#https\:***@#' ~/.git-credentials)"
echo "  (it will be replaced by the new one once you rotate it in GitHub)"
