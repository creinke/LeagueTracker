#!/usr/bin/env bash
set -euo pipefail

APP_NAME="leaguetracker"
LIVE_DIR="/var/www/${APP_NAME}"
STAGING_DIR="/var/www/deploy/${APP_NAME}-build"
BACKUP_ROOT="/var/www/backups"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${BACKUP_ROOT}/${APP_NAME}-${TIMESTAMP}"

PHP_BIN="/usr/bin/php"
COMPOSER_BIN="/usr/bin/composer"

echo "Starting deployment for ${APP_NAME}..."

# Ensure backup directory exists
mkdir -p "${BACKUP_ROOT}"

# Verify staging exists
if [ ! -d "${STAGING_DIR}" ]; then
    echo "ERROR: Staging directory does not exist: ${STAGING_DIR}"
    exit 1
fi

# Backup current live app if it exists
if [ -d "${LIVE_DIR}" ]; then
    echo "Creating backup: ${BACKUP_DIR}"
    cp -a "${LIVE_DIR}" "${BACKUP_DIR}"
fi

# Preserve server-specific env file if present
TMP_ENV_LOCAL="/tmp/${APP_NAME}.env.local.${TIMESTAMP}"
if [ -f "${LIVE_DIR}/.env.local" ]; then
    echo "Preserving existing .env.local"
    cp "${LIVE_DIR}/.env.local" "${TMP_ENV_LOCAL}"
fi

# Rsync from staging to live
echo "Syncing files from staging to live..."
mkdir -p "${LIVE_DIR}"

rsync -av --delete \
    --exclude=".git" \
    --exclude=".idea" \
    --exclude="node_modules" \
    --exclude="var/log" \
    --exclude=".env.local" \
    "${STAGING_DIR}/" "${LIVE_DIR}/"

# Restore preserved env file
if [ -f "${TMP_ENV_LOCAL}" ]; then
    echo "Restoring .env.local"
    cp "${TMP_ENV_LOCAL}" "${LIVE_DIR}/.env.local"
    rm -f "${TMP_ENV_LOCAL}"
fi

# Install/update composer deps if composer.json exists
if [ -f "${LIVE_DIR}/composer.json" ]; then
    echo "Running composer install..."
    cd "${LIVE_DIR}"
    "${COMPOSER_BIN}" install --no-dev --optimize-autoloader
fi

# Clear and warm Symfony cache
if [ -f "${LIVE_DIR}/bin/console" ]; then
    echo "Clearing Symfony cache..."
    cd "${LIVE_DIR}"
    "${PHP_BIN}" bin/console cache:clear --env=prod || true
    "${PHP_BIN}" bin/console cache:warmup --env=prod || true
fi

# Fix permissions if needed
echo "Fixing permissions..."
chown -R www-data:www-data "${LIVE_DIR}"
chmod -R u=rwX,g=rX,o=rX "${LIVE_DIR}"

echo "Deployment complete."
echo "Backup created at: ${BACKUP_DIR}"