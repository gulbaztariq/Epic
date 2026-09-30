#!/usr/bin/env bash
#
# Publish an update of the EPIC website onto a Hostinger account, over SSH.
#
# Run this ON THE SERVER (after `ssh -p 65002 <user>@<host>`). It updates a site that
# is already installed; it does not do a first install (that needs a .env and a
# database: see DEPLOYMENT.md).
#
#   SITE=/home/u123/domains/example.com/public_html bash deploy-hostinger.sh
#       Looks at the server and reports. Changes NOTHING.
#
#   DEPLOY=yes SITE=/home/u123/domains/example.com/public_html bash deploy-hostinger.sh
#       Backs up, publishes, migrates the database and rebuilds the caches.
#
# Options (environment variables):
#   SITE            the site folder: the one that contains `artisan` (searched for if unset)
#   REF             branch or commit to publish          (default: the branch below)
#   REPO            GitHub owner/name                    (default: gulbaztariq/Epic)
#   SKIP_DB_BACKUP  yes = carry on even if the database could not be dumped (not advised)
#   PHP_BIN         a specific php binary, if the default one is older than 8.3
#
# Safe by construction: it refuses to touch a folder that is not an EPIC install, it
# never deletes files, it never touches .env, storage/ or public/uploads/, and it stops
# at the first problem, telling you where the backup is.

set -eEuo pipefail

REPO="${REPO:-gulbaztariq/Epic}"
REF="${REF:-claude/epic-ritchie-o47wjd}"
DEPLOY="${DEPLOY:-no}"
SKIP_DB_BACKUP="${SKIP_DB_BACKUP:-no}"
TARBALL_URL="${TARBALL_URL:-https://codeload.github.com/${REPO}/tar.gz/${REF}}"   # overridable for testing

say()  { printf '%s\n' "$*"; }
step() { printf '\n== %s\n' "$*"; }
stop() { printf '\nSTOP: %s\n' "$*" >&2; exit 1; }

BACKUP=""
WORK="$(mktemp -d "${TMPDIR:-/tmp}/epic-deploy.XXXXXX")"
trap 'rm -rf "$WORK"' EXIT
trap 'printf "\nSTOPPED at line %s. " "$LINENO" >&2; [ -n "$BACKUP" ] && printf "Your backup is in %s\n" "$BACKUP" >&2 || printf "Nothing had been changed yet.\n" >&2' ERR

# ---------------------------------------------------------------- PHP (8.3 or newer)
step "PHP"
PHP=""
for candidate in "${PHP_BIN:-}" php /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php php8.4 php8.3; do
    [ -n "$candidate" ] || continue
    command -v "$candidate" >/dev/null 2>&1 || continue
    if "$candidate" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>/dev/null; then PHP="$(command -v "$candidate")"; break; fi
done
[ -n "$PHP" ] || stop "No PHP 8.3+ found for the command line. In hPanel set the PHP version to 8.3 or newer (Advanced > PHP Configuration), or run again with PHP_BIN=/path/to/php."
# Make every tool below (artisan, composer) use this PHP, whatever the default is.
mkdir -p "$WORK/bin" && ln -s "$PHP" "$WORK/bin/php" && export PATH="$WORK/bin:$PATH"
say "using $("$PHP" -r 'echo PHP_VERSION;') at $PHP"
missing=""; for ext in pdo_mysql mbstring gd zip xml curl fileinfo; do "$PHP" -m | grep -qix "$ext" || missing="$missing $ext"; done
[ -z "$missing" ] && say "extensions: all present" || say "WARNING: command-line PHP lacks:$missing (the web PHP may differ)"

# ---------------------------------------------------------------- which folder
step "Site folder"
is_epic() { [ -f "$1/artisan" ] && [ -f "$1/config/epic.php" ]; }
SITE="${SITE:-}"
if [ -z "$SITE" ]; then
    found=()
    for dir in "$HOME"/domains/*/public_html "$HOME"/domains/*/epic "$HOME"/public_html; do
        [ -d "$dir" ] && is_epic "$dir" && found+=("$dir")
    done
    if [ "${#found[@]}" -eq 1 ]; then SITE="${found[0]}"; say "found it: $SITE"
    elif [ "${#found[@]}" -eq 0 ]; then stop "Could not find an installed EPIC site under $HOME. Run again with SITE=/full/path/to/the/folder/that/contains/artisan"
    else printf 'More than one EPIC site found:\n'; printf '  %s\n' "${found[@]}"; stop "Run again with SITE= set to the one you want."; fi
fi
SITE="${SITE%/}"
[ -d "$SITE" ] || stop "$SITE is not a folder."
is_epic "$SITE" || stop "$SITE is not an installed EPIC site (no artisan and config/epic.php). Nothing was touched. For a first install follow DEPLOYMENT.md."
[ -f "$SITE/.env" ] || stop "$SITE has no .env file, so it is not set up yet. Follow DEPLOYMENT.md for a first install."
say "site: $SITE"

# ---------------------------------------------------------------- the site's own settings
# Read through the application itself, so this is what the running site really uses. That
# is its cached configuration, which can differ from .env after .env has been edited.
declare -A CONF
load_settings() {
    local out line k
    out="$(cd "$SITE" && "$PHP" -r '
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $c = config("database.connections." . config("database.default"));
        foreach (["db_connection" => config("database.default"), "db_host" => $c["host"] ?? "", "db_port" => $c["port"] ?? "",
                  "db_database" => $c["database"] ?? "", "db_username" => $c["username"] ?? "", "db_password" => $c["password"] ?? "",
                  "app_url" => config("app.url")] as $k => $v) { echo $k, " ", base64_encode((string) $v), "\n"; }
    ' 2>/dev/null)" || return 1
    while read -r k line; do [ -n "$k" ] && CONF[$k]="$(printf '%s' "$line" | base64 -d)"; done <<<"$out"
    [ -n "${CONF[db_connection]:-}" ]
}

# ---------------------------------------------------------------- composer
step "Composer"
COMPOSER="$(command -v composer || true)"
[ -n "$COMPOSER" ] || COMPOSER="$(command -v composer.phar || true)"
[ -n "$COMPOSER" ] || [ ! -f "$HOME/composer.phar" ] || COMPOSER="$HOME/composer.phar"
[ -n "$COMPOSER" ] || stop "Composer was not found. It ships with Hostinger SSH; if this account lacks it, use the upload bundle instead (see DEPLOYMENT.md)."
say "composer: $(COMPOSER_ALLOW_SUPERUSER=1 "$COMPOSER" --version 2>/dev/null | head -n1)"

# ---------------------------------------------------------------- what would change
step "The site as it is now"
envval() { # envval KEY -> the value written in .env, quotes stripped (used only to spot disagreements)
    local v; v="$(grep -E "^$1=" "$SITE/.env" | tail -n1 | cut -d= -f2- || true)"
    v="${v%\"}"; v="${v#\"}"; v="${v%\'}"; v="${v#\'}"; printf '%s' "$v"
}
load_settings || stop "Could not start the site's code to read its settings. Is vendor/ complete and is $SITE/.env valid?"
say "database: ${CONF[db_connection]} / ${CONF[db_database]} on ${CONF[db_host]:-local file}"
say "address : ${CONF[app_url]}"
# The site runs from saved (cached) settings. Publishing rebuilds them from .env, so if .env
# now says something different the site would silently switch to it.
if [ -f "$SITE/bootstrap/cache/config.php" ] && [ "${CONF[db_connection]}" != "sqlite" ] && [ "${IGNORE_ENV_MISMATCH:-no}" != "yes" ]; then
    for pair in DB_HOST:db_host DB_DATABASE:db_database DB_USERNAME:db_username DB_PASSWORD:db_password; do
        in_env="$(envval "${pair%%:*}")"
        if [ -n "$in_env" ] && [ "$in_env" != "${CONF[${pair##*:}]}" ]; then
            stop "${pair%%:*} in .env differs from the value the running site is using (its saved settings are out of date). Publishing would switch the site to the .env value. Check .env; if it is right, use Dashboard > Housekeeping > Refresh caches first, then run this again. (IGNORE_ENV_MISMATCH=yes overrides.)"
        fi
    done
fi
free_mb="$(df -Pm "$SITE" | awk 'NR==2 {print $4}')"; say "disk free: ${free_mb} MB"
[ "${free_mb:-0}" -ge 400 ] || stop "Less than 400 MB free; the backup and dependencies need room."
if out="$(cd "$SITE" && "$PHP" artisan migrate:status --no-interaction 2>&1)"; then
    say "database connection: OK ($(printf '%s\n' "$out" | grep -c 'Ran') updates already applied)"
else
    printf '%s\n' "$out" | tail -n 5 | sed 's/^/  /' >&2
    stop "Could not read the database. Check DB_* in $SITE/.env before publishing."
fi

# ---------------------------------------------------------------- fetch the new version
step "Fetching $REF from $REPO"
curl -fsSL "$TARBALL_URL" -o "$WORK/source.tgz" || stop "Could not download $TARBALL_URL"
mkdir "$WORK/source" && tar -xzf "$WORK/source.tgz" -C "$WORK/source" --strip-components=1
[ -f "$WORK/source/artisan" ] && [ -f "$WORK/source/config/epic.php" ] || stop "The download does not look like the EPIC website."
say "downloaded $(du -h "$WORK/source.tgz" | cut -f1)"
new_updates="$(comm -13 <(ls "$SITE/database/migrations" | sort) <(ls "$WORK/source/database/migrations" | sort) || true)"
if [ -n "$new_updates" ]; then say "database updates this release will apply:"; printf '%s\n' "$new_updates" | sed 's/^/  /'; else say "no database updates in this release"; fi

if [ "$DEPLOY" != "yes" ]; then
    step "Report only: nothing was changed"
    say "To publish, run:"
    say "  DEPLOY=yes SITE=$SITE bash $0"
    exit 0
fi

# ---------------------------------------------------------------- back up
step "Backing up"
BACKUP="$HOME/epic-backups/$(date +%Y%m%d-%H%M%S)"
umask 077   # the backup holds .env and the whole database: readable by you only
mkdir -p "$BACKUP" && chmod 700 "$HOME/epic-backups" "$BACKUP"
tar -czf "$BACKUP/code.tgz" -C "$SITE" --exclude=./vendor --exclude=./public/uploads --exclude=./storage/logs --exclude=./storage/framework .
say "code (with .env, without vendor and uploads): $(du -h "$BACKUP/code.tgz" | cut -f1)"

give_up_backup() { # a backup that could not be completed is removed, so it cannot be mistaken for a good one
    local why="$1"; rm -rf "$BACKUP"; BACKUP=""
    stop "$why Nothing was published. Fix that, or repeat with SKIP_DB_BACKUP=yes if you accept the risk."
}
if [ "${CONF[db_connection]}" = "sqlite" ]; then
    cp "${CONF[db_database]}" "$BACKUP/database.sqlite" && say "database (sqlite file) copied"
elif command -v mysqldump >/dev/null 2>&1; then
    export MYSQL_PWD="${CONF[db_password]}"   # in the environment, never on a command line
    if mysqldump --single-transaction --no-tablespaces -h "${CONF[db_host]}" -P "${CONF[db_port]:-3306}" -u "${CONF[db_username]}" "${CONF[db_database]}" 2>"$BACKUP/dump.err" | gzip > "$BACKUP/database.sql.gz" \
       && [ "${PIPESTATUS[0]}" -eq 0 ] && [ "$(stat -c %s "$BACKUP/database.sql.gz")" -gt 200 ]; then
        say "database dump: $(du -h "$BACKUP/database.sql.gz" | cut -f1)"
    elif [ "$SKIP_DB_BACKUP" = "yes" ]; then say "WARNING: database backup failed; continuing because SKIP_DB_BACKUP=yes"
    else give_up_backup "The database could not be backed up ($(head -c 200 "$BACKUP/dump.err"))."; fi
    unset MYSQL_PWD
elif [ "$SKIP_DB_BACKUP" = "yes" ]; then say "WARNING: mysqldump is not available; continuing because SKIP_DB_BACKUP=yes"
else give_up_backup "mysqldump is not available, so the database cannot be backed up first."; fi

umask 022   # back to normal so the site's own files stay readable by the web server

# ---------------------------------------------------------------- publish the files
step "Copying files (nothing is deleted; .env, storage/ and uploads are left alone)"
tar -C "$WORK/source" -cf - \
    --exclude=./.git --exclude=./.github --exclude=./tests --exclude=./docker --exclude=./Dockerfile \
    --exclude=./.dockerignore --exclude=./RAILWAY.md --exclude=./AGENTS.md --exclude=./CLAUDE.md --exclude=./phpunit.xml \
    --exclude=./.env --exclude=./storage --exclude=./public/uploads --exclude=./bootstrap/cache --exclude=./vendor \
    --exclude='./database/*.sqlite' . \
  | tar -C "$SITE" -xf -
say "files published"

# ---------------------------------------------------------------- dependencies
step "Installing dependencies"
( cd "$SITE" && COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1 "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction --no-progress 2>&1 | tail -n 5 )

# ---------------------------------------------------------------- database + caches
step "Database updates and caches"
( cd "$SITE" && "$PHP" artisan epic:install --optimize --no-interaction )
mkdir -p "$SITE/storage/logs" "$SITE/public/uploads"
chmod -R ug+rwX "$SITE/storage" "$SITE/bootstrap/cache" "$SITE/public/uploads" 2>/dev/null || true
# The browser installer is only for hosting without SSH; take it off the site.
rm -f "$SITE/public/install.php"

# ---------------------------------------------------------------- check
step "Checking the live site"
url="${CONF[app_url]:-}"; url="${url%/}"
if [ -n "$url" ]; then
    for path in / /who-we-are/board-of-directors /contact /sitemap.xml /admin/login; do
        code="$(curl -sS -o /dev/null -m 30 -w '%{http_code}' "$url$path" 2>/dev/null || echo 'no answer')"
        printf '  %-34s %s\n' "$path" "$code"
    done
fi

step "Done"
say "Published $REF to $SITE"
say "Backup: $BACKUP"
say "To undo the files:  tar -xzf $BACKUP/code.tgz -C $SITE"
[ -f "$BACKUP/database.sql.gz" ] && say "To undo the database: gunzip < $BACKUP/database.sql.gz | mysql -h HOST -u USER -p DBNAME"
say "Hard-refresh the browser (Cmd+Shift+R) to see the new styling."
