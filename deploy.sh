#!/usr/bin/env bash
#
# Deploy jebbari.nl on the server. Run from the project folder:
#
#   ./deploy.sh --first owner@example.com   first install (once)
#   ./deploy.sh                             every update after that
#
# Plesk: the PHP and Node versions on the command line can differ from the
# website's. Point the script at the right ones if needed:
#
#   PHP=/opt/plesk/php/8.4/bin/php NPM=/opt/plesk/node/22/bin/npm ./deploy.sh
#
# Full guide: docs/DEPLOY.md

set -euo pipefail

cd "$(dirname "$0")"

PHP="${PHP:-php}"
NPM="${NPM:-npm}"
COMPOSER="${COMPOSER:-$(command -v composer || true)}"
FIRST=false
OWNER_EMAIL=""

if [[ "${1:-}" == "--first" ]]; then
    FIRST=true
    OWNER_EMAIL="${2:-}"
fi

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }
fail() { printf '\n\033[1;31mStopped: %s\033[0m\n' "$1" >&2; exit 1; }
artisan() { "$PHP" artisan "$@"; }

# ---------------------------------------------------------------- checks
step "Checking the server"

# Files created by root (caches, logs, uploads) cannot be written by the website afterwards.
if [[ "$(id -u)" -eq 0 && "${ALLOW_ROOT:-}" != "1" ]]; then
    fail "Do not run this as root. Log in as the site's system user (Plesk: Hosting settings > SSH access), or from root: su - <site user> -s /bin/bash"
fi
[[ "$(stat -c %U .)" == "$(id -un)" ]] || echo "Warning: this folder is owned by $(stat -c %U .), you are $(id -un). Run as the folder's owner."

"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);' \
    || fail "PHP 8.4 or newer is needed (found $("$PHP" -r 'echo PHP_VERSION;')). Set PHP=/opt/plesk/php/8.4/bin/php"
for ext in gd intl mbstring pdo fileinfo zip; do
    "$PHP" -m | grep -qi "^$ext\$" || fail "PHP extension '$ext' is missing. Enable it in Plesk > PHP Settings."
done
[[ -n "$COMPOSER" ]] || fail "composer not found. Install it or set COMPOSER=/path/to/composer.phar"
command -v "$NPM" >/dev/null || fail "npm not found. Install Node.js 20.19+ (Plesk > Node.js) or set NPM=/path/to/npm"
echo "PHP $("$PHP" -r 'echo PHP_VERSION;'), Node $("$(dirname "$(command -v "$NPM")")/node" -v 2>/dev/null || node -v)"

if [[ ! -f .env ]]; then
    cp .env.example .env
    fail ".env was missing, so a copy of .env.example was made. Fill it in (see docs/DEPLOY.md, step 3) and run this again."
fi
grep -q '^APP_ENV=production' .env || fail "Set APP_ENV=production in .env"
grep -q '^APP_DEBUG=false' .env || fail "Set APP_DEBUG=false in .env"

# ---------------------------------------------------------------- code
if [[ "$FIRST" == false ]]; then
    step "Putting the site in maintenance mode"
    artisan down --retry=30 || true
    trap 'echo; echo "Something went wrong. The site is still in maintenance mode; fix it and run ./deploy.sh again, or bring it back with: $PHP artisan up"' ERR

    step "Fetching the latest code"
    git pull --ff-only
fi

step "Installing PHP packages"
composer_run() {
    # A composer.phar (or a PHP script) runs with the chosen PHP; a wrapper script runs as is.
    if [[ "$COMPOSER" == *.phar ]] || head -n1 "$COMPOSER" | grep -q php; then "$PHP" "$COMPOSER" "$@"; else "$COMPOSER" "$@"; fi
}
composer_run install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if ! grep -q '^APP_KEY=base64:' .env; then
    step "Generating the application key (back up .env after this!)"
    artisan key:generate --force
fi

step "Building CSS and JavaScript"
"$NPM" ci --no-audit --no-fund
"$NPM" run build

# ---------------------------------------------------------------- database
step "Updating the database"
artisan migrate --force

if [[ "$FIRST" == true ]]; then
    step "Seeding the starting content (all drafts, nothing goes live)"
    artisan db:seed --class=CmsSeeder --force
    artisan content:propose
    artisan cms:draft-about --force

    if [[ -n "$OWNER_EMAIL" ]]; then
        step "Creating the owner account $OWNER_EMAIL"
        artisan cms:create-owner "$OWNER_EMAIL" --name="Amir Jebbari" \
            || echo "Owner not created. Try again later: $PHP artisan cms:create-owner $OWNER_EMAIL --name=\"Amir Jebbari\""
    else
        echo "No owner e-mail given. Create the admin account later with:"
        echo "  $PHP artisan cms:create-owner you@example.com --name=\"Amir Jebbari\""
    fi
fi

# ---------------------------------------------------------------- files and caches
step "Linking uploads and publishing admin assets"
[[ -L public/storage ]] || artisan storage:link
artisan filament:assets
chmod -R ug+rwX storage bootstrap/cache

step "Caching configuration, routes and views"
artisan optimize:clear >/dev/null
artisan optimize
artisan queue:restart || true

if [[ "$FIRST" == false ]]; then
    trap - ERR
    step "Bringing the site back online"
    artisan up
fi

step "Done"
APP_URL="$(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '"')"
cat <<EOF

  Website:  $APP_URL
  Admin:    $APP_URL/admin
  Check:    $APP_URL/robots.txt  $APP_URL/llms.txt  $APP_URL/sitemap.xml

EOF
if [[ "$FIRST" == true ]]; then
    echo "  Next: log in at /admin, set up the authenticator app, review the drafts and publish."
fi
