#!/usr/bin/env bash
#
# Builds a client website on the preview subdomain, or clears it.
# Started by Jebbari's queue (App\Jobs\DeployPreview / ClearPreview), never by hand
# on a live preview. Guide: docs/PREVIEW-DEPLOY.md
#
#   preview-deploy.sh deploy
#   preview-deploy.sh clear
#
# Everything comes in through environment variables, never as arguments, so no
# value is ever parsed by a shell and no secret shows up in the process list:
#
#   PREVIEW_PATH          the subdomain's httpdocs folder (document root: httpdocs/public)
#   PREVIEW_REPO          https://github.com/<owner>/<repo>.git (validated by Jebbari)
#   PREVIEW_BRANCH        branch to check out (validated by Jebbari)
#   PREVIEW_ENV           the complete .env of the preview site
#   PREVIEW_ACCESS_KEY    cookie value the subdomain lets in ([A-Za-z0-9], 64 chars)
#   PREVIEW_COOKIE        cookie name
#   PREVIEW_FRAME_ORIGIN  origin allowed to show the preview in a frame (https://jebbari.nl)
#   PREVIEW_DEV           1 installs composer dev packages too (seeders that use Faker)
#   PREVIEW_PHP, PREVIEW_COMPOSER, PREVIEW_NPM   tools (optional)
#   JEBBARI_ROOT          Jebbari's own folder, which must never be touched
#   GIT_CONFIG_*          GitHub token header for private repositories (optional)

set -euo pipefail
umask 022

# Never inherit a database or app configuration: the preview's .env must win.
unset APP_KEY APP_ENV APP_URL APP_DEBUG DB_CONNECTION DB_URL DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD DB_SOCKET 2>/dev/null || true

MODE="${1:-}"

step() { printf '\n==> %s\n' "$1"; }
fail() { printf '\nStopped: %s\n' "$1" >&2; exit 1; }

[[ "$MODE" == "deploy" || "$MODE" == "clear" ]] || fail "usage: preview-deploy.sh deploy|clear"
[[ "$(id -u)" -ne 0 ]] || fail "do not run as root; the queue worker must run as the site's system user"

# ---------------------------------------------------------------- the target folder
# This folder is emptied, so it is checked hard: an existing absolute path without
# symlinks, named httpdocs, inside a folder whose name starts with "preview.", and
# not Jebbari itself or anything around it.
TARGET="${PREVIEW_PATH:-}"
[[ -n "$TARGET" ]] || fail "PREVIEW_PATH is not set"
[[ "$TARGET" == /* ]] || fail "PREVIEW_PATH must be an absolute path"
TARGET="${TARGET%/}"
[[ -d "$TARGET" ]] || fail "PREVIEW_PATH does not exist: $TARGET"
[[ "$(realpath -- "$TARGET")" == "$TARGET" ]] || fail "PREVIEW_PATH may not contain symlinks or .. : $TARGET"
[[ "$(basename -- "$TARGET")" == "httpdocs" ]] || fail "PREVIEW_PATH must end in /httpdocs"
PARENT="$(dirname -- "$TARGET")"
[[ "$(basename -- "$PARENT")" == preview.* ]] || fail "PREVIEW_PATH must be <...>/preview.<domain>/httpdocs"
if [[ -n "${JEBBARI_ROOT:-}" ]]; then
    JEBBARI="$(realpath -- "$JEBBARI_ROOT")"
    [[ "$JEBBARI/" != "$TARGET/"* ]] || fail "PREVIEW_PATH contains Jebbari itself"
    [[ "$TARGET/" != "$JEBBARI/"* ]] || fail "PREVIEW_PATH lies inside Jebbari"
fi
[[ -w "$TARGET" ]] || fail "no write access to $TARGET (run the queue worker as the site's system user)"
[[ "$MODE" == "clear" || -w "$PARENT" ]] || fail "no write access to $PARENT (the build folder is made there, next to httpdocs)"

# Kept when the folder is emptied: Plesk's own files and certificate checks.
FRAME="${PREVIEW_FRAME_ORIGIN:-}"
[[ -z "$FRAME" || "$FRAME" =~ ^https?://[A-Za-z0-9.-]+(:[0-9]{1,5})?$ ]] || fail "invalid PREVIEW_FRAME_ORIGIN"

is_kept() { [[ "$1" == ".htaccess" || "$1" == ".well-known" ]]; }

empty_target() {
    local entry name
    shopt -s dotglob nullglob
    for entry in "$TARGET"/*; do
        name="$(basename -- "$entry")"
        is_kept "$name" && continue
        rm -rf -- "$entry"
    done
    shopt -u dotglob nullglob
}

# Headers for every preview response: never indexed, only framed by Jebbari.
headers_block() {
    cat <<EOF
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow"
    Header always unset X-Frame-Options
    Header unset X-Frame-Options
    Header always unset Content-Security-Policy
    Header unset Content-Security-Policy
    Header always set Content-Security-Policy "frame-ancestors ${FRAME:-'none'}"
</IfModule>
EOF
}

write_robots() {
    printf 'User-agent: *\nDisallow: /\n' > "$1/robots.txt"
}

# ---------------------------------------------------------------- clear
if [[ "$MODE" == "clear" ]]; then
    step "Clearing $TARGET"
    empty_target
    mkdir -p "$TARGET/public"
    cat > "$TARGET/public/index.html" <<'EOF'
<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Geen preview</title></head>
<body><p>Er staat op dit moment geen preview klaar.</p></body>
</html>
EOF
    { headers_block; printf 'DirectoryIndex index.html\n'; } > "$TARGET/public/.htaccess"
    write_robots "$TARGET/public"
    echo "Cleared."
    exit 0
fi

# ---------------------------------------------------------------- deploy: checks
REPO="${PREVIEW_REPO:-}"
BRANCH="${PREVIEW_BRANCH:-}"
[[ "$REPO" =~ ^https://github\.com/[A-Za-z0-9][A-Za-z0-9-]{0,38}/[A-Za-z0-9._-]{1,100}\.git$ ]] || fail "repository URL is not a GitHub https URL"
[[ "$BRANCH" =~ ^[A-Za-z0-9._/-]{1,100}$ && "$BRANCH" != -* && "$BRANCH" != *..* ]] || fail "invalid branch name"
[[ "${PREVIEW_ACCESS_KEY:-}" =~ ^[A-Za-z0-9]{32,}$ ]] || fail "invalid access key"
[[ "${PREVIEW_COOKIE:-}" =~ ^[A-Za-z0-9_]{1,64}$ ]] || fail "invalid cookie name"
[[ -n "${PREVIEW_ENV:-}" ]] || fail "PREVIEW_ENV is empty"

PHP="${PREVIEW_PHP:-php}"
command -v "$PHP" >/dev/null || fail "PHP not found: $PHP (set PREVIEW_PHP, e.g. /opt/plesk/php/8.3/bin/php)"
command -v git >/dev/null || fail "git not found"

COMPOSER="${PREVIEW_COMPOSER:-$(command -v composer || true)}"
composer_run() {
    # A composer.phar (or a PHP script) runs with the chosen PHP; a wrapper script runs as is.
    if [[ "$COMPOSER" == *.phar ]] || [[ "$(head -n1 -- "$COMPOSER")" == *php* ]]; then "$PHP" "$COMPOSER" "$@"; else "$COMPOSER" "$@"; fi
}

# npm: the setting wins; otherwise the newest Node on the PATH or in Plesk's Node.js extension.
NPM="${PREVIEW_NPM:-}"
if [[ -z "$NPM" ]]; then
    best=""
    for candidate in $(command -v npm || true) /opt/plesk/node/*/bin/npm /usr/local/bin/npm; do
        [[ -x "$candidate" ]] || continue
        version="$("$(dirname -- "$candidate")/node" -v 2>/dev/null | tr -d v || true)"
        [[ -n "$version" ]] || continue
        if [[ -z "$best" || "$(printf '%s\n%s\n' "$best" "$version" | sort -V | tail -n1)" == "$version" && "$version" != "$best" ]]; then
            best="$version"
            NPM="$candidate"
        fi
    done
fi
[[ "$NPM" == */* ]] && export PATH="$(dirname -- "$NPM"):$PATH"

echo "PHP $("$PHP" -r 'echo PHP_VERSION;'), git $(git --version | awk '{print $3}')"

# ---------------------------------------------------------------- build next to the site
# Cloned and built in a temporary folder beside httpdocs, so the old preview stays
# up until the new one is ready to move in.
BUILD="$(mktemp -d -- "$PARENT/.preview-build.XXXXXX")"
cleanup() { rm -rf -- "$BUILD"; }
trap cleanup EXIT

step "Cloning $REPO ($BRANCH)"
GIT_TERMINAL_PROMPT=0 git clone --depth 1 --single-branch --branch "$BRANCH" --no-tags -- "$REPO" "$BUILD/src"
cd "$BUILD/src"
echo "Commit $(git rev-parse --short HEAD): $(git log -1 --format=%s)"
rm -rf -- .git

[[ -d public ]] || fail "the repository has no public/ folder (the document root is httpdocs/public)"

if [[ -f composer.json ]]; then
    [[ -n "$COMPOSER" ]] || fail "composer not found (set PREVIEW_COMPOSER=/path/to/composer.phar)"
    if [[ "${PREVIEW_DEV:-0}" == "1" ]]; then
        step "composer install (with dev packages)"
        composer_run install --no-interaction --prefer-dist --optimize-autoloader --no-progress
    else
        step "composer install --no-dev"
        composer_run install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
    fi
else
    echo "No composer.json, skipping composer."
fi

if [[ -f package.json ]]; then
    [[ -n "$NPM" ]] && command -v "$NPM" >/dev/null || fail "npm not found (set PREVIEW_NPM, see ls /opt/plesk/node/)"
    step "npm ci && npm run build (Node $(node -v))"
    if [[ -f package-lock.json ]]; then "$NPM" ci --no-audit --no-fund; else "$NPM" install --no-audit --no-fund; fi
    "$NPM" run build
    rm -rf -- node_modules
else
    echo "No package.json, skipping npm."
fi

# ---------------------------------------------------------------- move in
step "Replacing the preview"
empty_target
shopt -s dotglob nullglob
for entry in "$BUILD/src"/*; do
    name="$(basename -- "$entry")"
    if is_kept "$name" && [[ -e "$TARGET/$name" ]]; then
        rm -rf -- "$entry"
        continue
    fi
    mv -- "$entry" "$TARGET/"
done
shopt -u dotglob nullglob
cd "$TARGET"

printf '%s\n' "$PREVIEW_ENV" > .env
chmod 600 .env

# Only visitors with the cookie from the client's link get in. Headers for every response.
HTACCESS="public/.htaccess"
ORIGINAL=""
[[ -f "$HTACCESS" ]] && ORIGINAL="$(cat -- "$HTACCESS")"
{
    echo "# --- Jebbari preview: access and headers (written by preview-deploy.sh) ---"
    cat <<EOF
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/robots\.txt$
    RewriteCond %{REQUEST_URI} !^/\.well-known/
    RewriteCond %{HTTP_COOKIE} !(^|;\s*)${PREVIEW_COOKIE}=${PREVIEW_ACCESS_KEY}(;|$)
    RewriteRule ^ - [F,L]
</IfModule>
<IfModule !mod_rewrite.c>
    Require all denied
</IfModule>
EOF
    headers_block
    echo "# --- end Jebbari preview ---"
    echo
    printf '%s\n' "$ORIGINAL"
} > "$HTACCESS.tmp"
mv -- "$HTACCESS.tmp" "$HTACCESS"
write_robots public

if [[ -f artisan ]]; then
    step "Laravel: key, database, storage"
    "$PHP" artisan key:generate --force --no-interaction
    "$PHP" artisan migrate:fresh --seed --force --no-interaction
    "$PHP" artisan storage:link --force --no-interaction || "$PHP" artisan storage:link --no-interaction
    "$PHP" artisan optimize:clear --no-interaction >/dev/null 2>&1 || true
else
    echo "No artisan file, skipping Laravel steps."
fi

step "Done"
echo "The preview is ready."
