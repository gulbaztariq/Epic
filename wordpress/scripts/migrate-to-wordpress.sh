#!/usr/bin/env bash
#
# Move the EPIC website from Laravel to WordPress on a Hostinger account, over SSH.
#
# Run this ON THE SERVER (after `ssh -p 65002 <user>@<host>`):
#
#   bash migrate-to-wordpress.sh
#       Looks at the server and reports what it would do. Changes NOTHING.
#
#   DEPLOY=yes bash migrate-to-wordpress.sh
#       Does it: exports the live content, backs the database up, builds the WordPress
#       site in a separate folder, then swaps it in for the old one and checks it.
#
#   bash migrate-to-wordpress.sh rollback ~/epic-wp-migration-<date>
#       Puts the old Laravel site back (also done automatically if the check fails).
#
# What it does, in order
#   1. finds the Laravel site and the folder the domain is served from
#   2. exports every page, picture, setting, menu and form message to a folder
#   3. backs up the Laravel database
#   4. downloads WordPress, Rank Math SEO and the EPIC theme/plugin
#   5. builds the new site in a STAGING folder next to the old one: installs WordPress,
#      imports the content, configures Rank Math. The live site is untouched meanwhile.
#   6. swaps the folders (two `mv` commands, a fraction of a second) and tests the new site
#   7. if a test fails, swaps back and tells you; otherwise prints how to sign in
#
# Safe by construction: nothing is deleted. The old site is kept, whole, in
# <folder>.laravel-<date> and the Laravel database is never modified. WordPress uses its own
# tables (prefix epicwp_) in the same database, so nothing is needed from hPanel.
#
# Options (environment variables)
#   DEPLOY=yes        actually do it (default: report only)
#   SITE              the Laravel folder (the one holding `artisan`); searched for if unset
#   DOCROOT           the folder the domain is served from; worked out if unset
#   DOMAIN            e.g. epic.org.pk (default: from the Laravel site's APP_URL)
#   SITE_URL          the full address, e.g. https://epic.org.pk
#   ADMIN_EMAIL       the WordPress administrator's email (default: the Laravel super admin's)
#   DB_HOST DB_PORT DB_NAME DB_USER DB_PASS
#                     the MySQL database to use, only needed when there is no Laravel site to read it from
#   REPO / REF        where the EPIC theme and plugin come from (default below)
#   PHP_BIN           a specific php binary, if the default one is too old
#   SKIP_DB_BACKUP    yes = carry on even if the database could not be dumped (not advised)
#   NO_ROLLBACK       yes = keep the new site even if the post-swap check fails
#
# Sources can be overridden (used by the tests): WP_TARBALL, RANKMATH_ZIP, EPIC_SRC,
# WP_CLI, SMOKE_BASE, SKIP_SMOKE.

set -eEuo pipefail

REPO="${REPO:-gulbaztariq/Epic}"
REF="${REF:-claude/epic-ritchie-o47wjd}"
DEPLOY="${DEPLOY:-no}"
SKIP_DB_BACKUP="${SKIP_DB_BACKUP:-no}"
NO_ROLLBACK="${NO_ROLLBACK:-no}"
WP_TARBALL="${WP_TARBALL:-https://wordpress.org/latest.tar.gz}"
RANKMATH_ZIP="${RANKMATH_ZIP:-https://downloads.wordpress.org/plugin/seo-by-rank-math.latest.zip}"
EPIC_TARBALL="${EPIC_TARBALL:-https://codeload.github.com/${REPO}/tar.gz/${REF}}"
EPIC_SRC="${EPIC_SRC:-}"
WP_CLI_URL="https://github.com/wp-cli/wp-cli/releases/download/v2.11.0/wp-cli-2.11.0.phar"
WP_CLI_SHA512="adb12146bab8d829621efed41124dcd0012f9027f47e0228be7080296167566070e4a026a09c3989907840b21de94b7a35f3bfbd5f827c12f27c5803546d1bba"
TABLE_PREFIX="epicwp_"
STAMP="$(date +%Y%m%d-%H%M%S)"

say()  { printf '%s\n' "$*"; }
step() { printf '\n== %s\n' "$*"; }
stop() { printf '\nSTOP: %s\n' "$*" >&2; exit 1; }

# ------------------------------------------------------------------ rollback command
rollback_swap() { # rollback_swap <web folder> <old copy>   (the new site is moved aside, never deleted)
    local docroot="$1" old="$2"
    [ -d "$old" ] || stop "The old site folder $old does not exist."
    if [ -e "$docroot" ] || [ -L "$docroot" ]; then mv "$docroot" "${docroot}.wordpress-$STAMP"; fi
    mv "$old" "$docroot"
    say "Put the old Laravel site back at $docroot (the WordPress attempt is in ${docroot}.wordpress-$STAMP)."
}

if [ "${1:-}" = "rollback" ]; then
    dir="${2:-}"
    [ -f "$dir/swap.env" ] || stop "Usage: bash migrate-to-wordpress.sh rollback <migration folder, e.g. ~/epic-wp-migration-20260101-120000>"
    # shellcheck disable=SC1091
    . "$dir/swap.env"
    rollback_swap "$SWAP_DOCROOT" "$SWAP_OLD"
    exit 0
fi

MIG=""
SWAPPED=""
trap 'rc=$?; printf "\nSTOPPED at line %s (exit %s). " "$LINENO" "$rc" >&2; if [ -n "$SWAPPED" ]; then printf "The new site had already been swapped in: run  bash %s rollback %s  to put the old one back.\n" "$0" "$MIG" >&2; elif [ -n "$MIG" ]; then printf "The live site was not changed. Work files are in %s\n" "$MIG" >&2; else printf "Nothing had been changed.\n" >&2; fi' ERR

# ------------------------------------------------------------------ PHP and tools
step "PHP and tools"
PHP=""
for candidate in "${PHP_BIN:-}" php /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php /opt/alt/php81/usr/bin/php php8.4 php8.3 php8.2 php8.1; do
    [ -n "$candidate" ] || continue
    command -v "$candidate" >/dev/null 2>&1 || continue
    if "$candidate" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' 2>/dev/null; then PHP="$(command -v "$candidate")"; break; fi
done
[ -n "$PHP" ] || stop "No PHP 8.1 or newer found for the command line. In hPanel set the PHP version to 8.2 or newer (Advanced > PHP Configuration), or run again with PHP_BIN=/path/to/php."
say "PHP $("$PHP" -r 'echo PHP_VERSION;') at $PHP"
missing=""; for ext in mysqli mbstring gd zip xml curl fileinfo json; do "$PHP" -m | grep -qix "$ext" || missing="$missing $ext"; done
[ -z "$missing" ] && say "extensions: all present" || say "WARNING: command-line PHP lacks:$missing (the web PHP may differ)"
for tool in curl tar unzip gzip sha1sum sha512sum; do command -v "$tool" >/dev/null 2>&1 || stop "$tool is required but is not installed."; done
HAVE_MYSQL="no"; command -v mysql >/dev/null 2>&1 && command -v mysqldump >/dev/null 2>&1 && HAVE_MYSQL="yes"
[ "$HAVE_MYSQL" = "yes" ] && say "mysql client: present" || say "WARNING: mysql / mysqldump are not installed, so the database cannot be backed up with them."

# ------------------------------------------------------------------ the Laravel site
step "The Laravel site"
is_epic() { [ -f "$1/artisan" ] && [ -f "$1/config/epic.php" ]; }
SITE="${SITE:-}"
if [ -z "$SITE" ]; then
    found=()
    for dir in "$HOME"/domains/*/public_html "$HOME"/domains/*/epic "$HOME"/public_html "$HOME"/epic "$HOME"/laravel; do
        [ -d "$dir" ] && is_epic "$dir" && found+=("$dir")
    done
    if [ "${#found[@]}" -eq 1 ]; then SITE="${found[0]}"; say "found it: $SITE"
    elif [ "${#found[@]}" -gt 1 ]; then printf 'More than one EPIC site found:\n'; printf '  %s\n' "${found[@]}"; stop "Run again with SITE= set to the one you want."
    else say "No installed EPIC (Laravel) site found under $HOME."; fi
fi
SITE="${SITE%/}"
if [ -n "$SITE" ]; then is_epic "$SITE" || stop "$SITE is not an EPIC site (no artisan and config/epic.php). Nothing was touched."; fi

# ------------------------------------------------------------------ work folder and downloads
MIG="$HOME/epic-wp-migration-$STAMP"
SHOWN="$MIG"   # what the plan prints: the real folder, even on a dry run that works in a temporary one
if [ "$DEPLOY" = "yes" ]; then mkdir -p "$MIG"/{backup,export,logs,dl}
else MIG="$(mktemp -d "${TMPDIR:-/tmp}/epic-wp-dryrun.XXXXXX")"; mkdir -p "$MIG/dl"; trap 'rm -rf "$MIG"' EXIT; fi

fetch() { # fetch <url or path> <destination>
    case "$1" in http://*|https://*) curl -fsSL --retry 3 --connect-timeout 20 -o "$2" "$1" ;; *) cp "$1" "$2" ;; esac
}
reachable() { case "$1" in http://*|https://*) curl -fsSIL --connect-timeout 15 -o /dev/null "$1" ;; *) [ -e "$1" ] ;; esac; }

EPIC_ROOT=""
ensure_epic_src() { # unpack the EPIC theme, plugin, tools and starter content once
    [ -z "$EPIC_ROOT" ] || return 0
    if [ -n "$EPIC_SRC" ]; then EPIC_ROOT="$EPIC_SRC/wordpress"
    else
        fetch "$EPIC_TARBALL" "$MIG/dl/epic.tar.gz"
        mkdir -p "$MIG/dl/epic" && tar -xzf "$MIG/dl/epic.tar.gz" -C "$MIG/dl/epic" --strip-components=1
        EPIC_ROOT="$MIG/dl/epic/wordpress"
    fi
    [ -f "$EPIC_ROOT/plugins/epic-core/epic-core.php" ] && [ -f "$EPIC_ROOT/themes/epic/style.css" ] && [ -f "$EPIC_ROOT/tools/export-laravel.php" ] \
        || stop "The EPIC download has no theme, plugin or tools (is REF=$REF right?)."
}

step "Sources"
for src in "$WP_TARBALL" "$RANKMATH_ZIP"; do reachable "$src" && say "ok   $src" || stop "Cannot reach $src from this server."; done
if [ -z "$EPIC_SRC" ]; then reachable "$EPIC_TARBALL" && say "ok   $EPIC_TARBALL" || stop "Cannot reach $EPIC_TARBALL from this server."; fi
ensure_epic_src
say "ok   EPIC theme and plugin"

# ------------------------------------------------------------------ WP-CLI
step "WP-CLI"
WPCLI_PHAR="${WP_CLI:-}"
if [ -n "$WPCLI_PHAR" ]; then say "using $WPCLI_PHAR"
elif [ "$DEPLOY" = "yes" ]; then
    fetch "$WP_CLI_URL" "$MIG/dl/wp-cli.phar"
    [ "$(sha512sum "$MIG/dl/wp-cli.phar" | cut -d' ' -f1)" = "$WP_CLI_SHA512" ] || stop "The downloaded WP-CLI does not match the expected checksum. Nothing was changed."
    WPCLI_PHAR="$MIG/dl/wp-cli.phar"; say "downloaded WP-CLI 2.11.0 (checksum verified)"
else reachable "$WP_CLI_URL" && say "ok   $WP_CLI_URL" || stop "Cannot reach $WP_CLI_URL from this server."; fi
wp() {
    local extra=(); [ "$(id -u)" = "0" ] && extra+=(--allow-root)
    "$PHP" -d memory_limit=768M -d max_execution_time=0 -d error_reporting=24575 "$WPCLI_PHAR" "${extra[@]}" --path="$MIG/site" "$@"
}
wpq() { wp "$@" 2>&1 | { grep -v '^Deprecated' || true; }; }   # quiet about PHP-version chatter, still fails if wp fails

# ------------------------------------------------------------------ the database
LARAVEL_DB_DRIVER="" LARAVEL_DB_HOST="" LARAVEL_DB_PORT="" LARAVEL_DB_NAME="" LARAVEL_DB_USER="" LARAVEL_DB_PASS="" LARAVEL_DB_SOCKET="" LARAVEL_DB_SOURCE="" LARAVEL_DB_URL=""
EXPORT_TOOL="$EPIC_ROOT/tools/export-laravel.php"
if [ -n "$SITE" ]; then
    eval "$("$PHP" "$EXPORT_TOOL" --app="$SITE" --print-db-env)"
    say "database: $LARAVEL_DB_DRIVER / $LARAVEL_DB_NAME on ${LARAVEL_DB_HOST:-local file} (from $LARAVEL_DB_SOURCE)"
    say "address : $LARAVEL_DB_URL"
    case "$LARAVEL_DB_DRIVER" in mysql|mariadb) ;; *) stop "The Laravel site uses $LARAVEL_DB_DRIVER, but WordPress needs MySQL. Create a MySQL database in hPanel and re-run with DB_HOST, DB_NAME, DB_USER and DB_PASS set." ;; esac
else
    LARAVEL_DB_DRIVER=mysql
fi
# Explicit settings win.
LARAVEL_DB_HOST="${DB_HOST:-$LARAVEL_DB_HOST}"; LARAVEL_DB_PORT="${DB_PORT:-$LARAVEL_DB_PORT}"; LARAVEL_DB_NAME="${DB_NAME:-$LARAVEL_DB_NAME}"
LARAVEL_DB_USER="${DB_USER:-$LARAVEL_DB_USER}"; LARAVEL_DB_PASS="${DB_PASS:-$LARAVEL_DB_PASS}"
if [ -z "$SITE" ] && { [ -z "$LARAVEL_DB_NAME" ] || [ -z "$LARAVEL_DB_USER" ]; }; then
    stop "There is no Laravel site to read a database from. Create a MySQL database in hPanel, then run again with  DB_HOST=localhost DB_NAME=... DB_USER=... DB_PASS=... DOMAIN=example.com  (the new site starts from the starter content)."
fi
mysql_run() { MYSQL_PWD="$LARAVEL_DB_PASS" mysql -h "${LARAVEL_DB_HOST:-localhost}" -P "${LARAVEL_DB_PORT:-3306}" -u "$LARAVEL_DB_USER" -N -B "$LARAVEL_DB_NAME" "$@"; }
if [ "$HAVE_MYSQL" = "yes" ]; then
    mysql_run -e "SELECT 1" >/dev/null 2>&1 || stop "Cannot connect to the database $LARAVEL_DB_NAME as $LARAVEL_DB_USER. Nothing was changed."
    say "database connection: ok"
fi

# ------------------------------------------------------------------ the folder the domain is served from
step "Folder the domain is served from"
DOMAIN="${DOMAIN:-}"
if [ -z "$DOMAIN" ] && [ -n "$LARAVEL_DB_URL" ]; then DOMAIN="$(printf '%s' "$LARAVEL_DB_URL" | sed -E 's#^[a-z]+://##; s#[/:].*$##; s#^www\.##')"; fi
[ -n "$DOMAIN" ] || stop "Could not work out the domain. Run again with DOMAIN=example.com"
SITE_URL="${SITE_URL:-}"
if [ -z "$SITE_URL" ]; then
    if printf '%s' "$LARAVEL_DB_URL" | grep -qE '^https://'; then SITE_URL="${LARAVEL_DB_URL%/}"; else SITE_URL="https://$DOMAIN"; fi
fi
SITE_URL="${SITE_URL%/}"

DOCROOT="${DOCROOT:-}"
if [ -z "$DOCROOT" ]; then
    # Laravel uploaded straight into a web folder: that folder is the one to swap.
    case "$SITE" in "$HOME"/domains/*/public_html|"$HOME"/public_html) DOCROOT="$SITE" ;; esac
fi
if [ -z "$DOCROOT" ]; then
    for candidate in "$HOME/domains/$DOMAIN/public_html" "$HOME/public_html"; do [ -d "$candidate" ] && { DOCROOT="$candidate"; break; }; done
    [ -n "$DOCROOT" ] || stop "Could not find the folder $DOMAIN is served from. Run again with DOCROOT=/full/path"
fi
DOCROOT="${DOCROOT%/}"
[ -d "$DOCROOT" ] || stop "$DOCROOT is not a folder."
say "domain    : $DOMAIN  ($SITE_URL)"
say "web folder: $DOCROOT$([ -L "$DOCROOT" ] && printf ' -> %s' "$(readlink -f "$DOCROOT")")"
if [ -f "$DOCROOT/wp-config.php" ] && [ -d "$DOCROOT/wp-includes" ]; then stop "$DOCROOT is already a WordPress site. Nothing to migrate. (To refresh the content from the old site, use: wp epic import <export folder>)"; fi
PARENT="$(dirname "$DOCROOT")"
[ -w "$PARENT" ] || stop "$PARENT is not writable, so the folders cannot be swapped."

OLD_PUBLIC=""
if [ -d "$DOCROOT/uploads" ]; then OLD_PUBLIC="$DOCROOT"; elif [ -n "$SITE" ] && [ -d "$SITE/public/uploads" ]; then OLD_PUBLIC="$SITE/public"; fi

step "Disk space"
uploads_mb=0; [ -n "$OLD_PUBLIC" ] && uploads_mb="$(du -sm "$OLD_PUBLIC/uploads" 2>/dev/null | cut -f1 || echo 0)"
need_mb=$(( uploads_mb * 3 + 500 ))
free_mb="$(df -Pm "$PARENT" | awk 'NR==2 {print $4}')"
say "free: ${free_mb} MB, need about ${need_mb} MB (the old site is moved, not copied)"
[ "${free_mb:-0}" -ge "$need_mb" ] || stop "Not enough free disk space ($free_mb MB free, about $need_mb MB needed). Free some space in hPanel and retry."

step "Plan"
cat <<EOF
  export the live content      -> $SHOWN/export
  back up the database         -> $SHOWN/backup/database.sql.gz
  build WordPress (staging)    -> $SHOWN/site          (tables ${TABLE_PREFIX}* in database ${LARAVEL_DB_NAME})
  swap:  $DOCROOT  ->  ${DOCROOT}.laravel-$STAMP   (kept whole)
         $SHOWN/site ->  $DOCROOT
  then test the live site at   ${SMOKE_BASE:-$SITE_URL}  and roll back automatically if it fails
EOF

if [ "$DEPLOY" != "yes" ]; then
    printf '\nNothing was changed. To go ahead, run again with:  DEPLOY=yes bash %s\n' "$0"
    exit 0
fi

# =================================================================== from here on, it does it
# ------------------------------------------------------------------ 1. export
step "1/7 Exporting the live content"
if [ -n "$SITE" ]; then
    "$PHP" -d memory_limit=768M "$EXPORT_TOOL" --app="$SITE" --out="$MIG/export" | tee "$MIG/logs/export.log"
else
    cp "$EPIC_ROOT/starter/export.json" "$MIG/export/export.json"
    say "no Laravel site: using the starter content"
fi
[ -s "$MIG/export/export.json" ] || stop "The export is empty."
ADMIN_EMAIL="${ADMIN_EMAIL:-$("$PHP" -r '$d = json_decode(file_get_contents($argv[1]), true); echo $d["admins"][0]["email"] ?? "";' "$MIG/export/export.json")}"
ADMIN_EMAIL="${ADMIN_EMAIL:-info@$DOMAIN}"
SITE_TITLE="$("$PHP" -r '$d = json_decode(file_get_contents($argv[1]), true); foreach ($d["tables"]["settings"] ?? [] as $r) { if ($r["key"] === "site_name" && $r["value"] !== "") { echo $r["value"]; exit; } } echo "EPIC";' "$MIG/export/export.json")"

# ------------------------------------------------------------------ 2. backup
step "2/7 Backing up the database"
if [ -n "$SITE" ]; then
    umask 077
    if [ "$HAVE_MYSQL" = "yes" ] \
        && MYSQL_PWD="$LARAVEL_DB_PASS" mysqldump --single-transaction --no-tablespaces -h "${LARAVEL_DB_HOST:-localhost}" -P "${LARAVEL_DB_PORT:-3306}" -u "$LARAVEL_DB_USER" "$LARAVEL_DB_NAME" 2>"$MIG/logs/mysqldump.log" | gzip >"$MIG/backup/database.sql.gz" \
        && [ -s "$MIG/backup/database.sql.gz" ] && gzip -t "$MIG/backup/database.sql.gz" \
        && [ "$( { gzip -dc "$MIG/backup/database.sql.gz" | grep -c 'CREATE TABLE'; } || true )" -gt 0 ]; then
        say "saved $(du -h "$MIG/backup/database.sql.gz" | cut -f1) to $MIG/backup/database.sql.gz"
    else
        rm -f "$MIG/backup/database.sql.gz"
        [ "$SKIP_DB_BACKUP" = "yes" ] || stop "The database could not be backed up ($(tail -n1 "$MIG/logs/mysqldump.log" 2>/dev/null || echo 'mysqldump is not available')). Nothing was changed. (SKIP_DB_BACKUP=yes overrides, which is not advised.)"
        say "WARNING: continuing without a database backup, as asked."
    fi
    cp "$SITE/.env" "$MIG/backup/laravel.env" 2>/dev/null || true
    umask 022
else
    say "(no existing site: nothing to back up)"
fi

# ------------------------------------------------------------------ 3. downloads
step "3/7 Downloading WordPress and Rank Math"
fetch "$WP_TARBALL" "$MIG/dl/wordpress.tar.gz"
case "$WP_TARBALL" in https://wordpress.org/*)
    expected="$(curl -fsSL "$WP_TARBALL.sha1" | tr -d '[:space:]')"
    [ "$(sha1sum "$MIG/dl/wordpress.tar.gz" | cut -d' ' -f1)" = "$expected" ] || stop "The WordPress download does not match WordPress.org's checksum. Nothing was changed."
    say "WordPress checksum verified" ;;
esac
tar -xzf "$MIG/dl/wordpress.tar.gz" -C "$MIG/dl"
[ -f "$MIG/dl/wordpress/wp-load.php" ] || stop "The WordPress download did not unpack as expected."
fetch "$RANKMATH_ZIP" "$MIG/dl/rankmath.zip"
unzip -q "$MIG/dl/rankmath.zip" -d "$MIG/dl/rankmath"
[ -f "$MIG/dl/rankmath/seo-by-rank-math/rank-math.php" ] || stop "The Rank Math download did not unpack as expected."

# ------------------------------------------------------------------ 4. staging site
step "4/7 Building the new site in a staging folder"
mv "$MIG/dl/wordpress" "$MIG/site"
cp -R "$MIG/dl/rankmath/seo-by-rank-math" "$MIG/site/wp-content/plugins/seo-by-rank-math"
cp -R "$EPIC_ROOT/plugins/epic-core" "$MIG/site/wp-content/plugins/epic-core"
cp -R "$EPIC_ROOT/themes/epic" "$MIG/site/wp-content/themes/epic"
rm -rf "$MIG/site/wp-content/plugins/hello.php" "$MIG/site/wp-content/plugins/akismet"

DB_HOST_FULL="${LARAVEL_DB_HOST:-localhost}"
if [ -n "${LARAVEL_DB_PORT:-}" ] && [ "$LARAVEL_DB_PORT" != "3306" ]; then DB_HOST_FULL="$DB_HOST_FULL:$LARAVEL_DB_PORT"; fi

# wp-config.php, written here so the database password never appears on a command line.
EPIC_CFG_HOST="$DB_HOST_FULL" EPIC_CFG_NAME="$LARAVEL_DB_NAME" EPIC_CFG_USER="$LARAVEL_DB_USER" EPIC_CFG_PASS="$LARAVEL_DB_PASS" EPIC_CFG_PREFIX="$TABLE_PREFIX" \
"$PHP" -r '
    $q = static fn ($v) => var_export((string) $v, true);
    $salt = static fn () => bin2hex(random_bytes(32));
    $out  = "<?php\n// Written by migrate-to-wordpress.sh\n";
    $out .= "define( \"DB_NAME\", " . $q(getenv("EPIC_CFG_NAME")) . " );\n";
    $out .= "define( \"DB_USER\", " . $q(getenv("EPIC_CFG_USER")) . " );\n";
    $out .= "define( \"DB_PASSWORD\", " . $q(getenv("EPIC_CFG_PASS")) . " );\n";
    $out .= "define( \"DB_HOST\", " . $q(getenv("EPIC_CFG_HOST")) . " );\n";
    $out .= "define( \"DB_CHARSET\", \"utf8mb4\" );\ndefine( \"DB_COLLATE\", \"\" );\n";
    foreach (["AUTH_KEY","SECURE_AUTH_KEY","LOGGED_IN_KEY","NONCE_KEY","AUTH_SALT","SECURE_AUTH_SALT","LOGGED_IN_SALT","NONCE_SALT"] as $k) { $out .= "define( \"$k\", " . $q($salt()) . " );\n"; }
    $out .= "\$table_prefix = " . $q(getenv("EPIC_CFG_PREFIX")) . ";\n";
    $out .= "define( \"WP_DEBUG\", false );\ndefine( \"DISALLOW_FILE_EDIT\", true );\ndefine( \"WP_MEMORY_LIMIT\", \"256M\" );\ndefine( \"FS_METHOD\", \"direct\" );\ndefine( \"WP_AUTO_UPDATE_CORE\", \"minor\" );\n";
    $out .= "// Behind the host\x27s proxy the connection is HTTPS even when PHP cannot see it.\n";
    $out .= "if ( isset( \$_SERVER[\"HTTP_X_FORWARDED_PROTO\"] ) && \"https\" === \$_SERVER[\"HTTP_X_FORWARDED_PROTO\"] ) { \$_SERVER[\"HTTPS\"] = \"on\"; }\n";
    $out .= "if ( ! defined( \"ABSPATH\" ) ) { define( \"ABSPATH\", __DIR__ . \"/\" ); }\nrequire_once ABSPATH . \"wp-settings.php\";\n";
    file_put_contents($argv[1], $out);
' "$MIG/site/wp-config.php"
chmod 640 "$MIG/site/wp-config.php"

# Tables from an earlier, abandoned attempt would block the install. Only ours (epicwp_) are ever dropped.
if [ "$HAVE_MYSQL" = "yes" ]; then
    leftovers="$(mysql_run -e "SHOW TABLES LIKE '${TABLE_PREFIX}%'" 2>/dev/null || true)"
    if [ -n "$leftovers" ]; then
        say "removing $(printf '%s\n' "$leftovers" | wc -l | tr -d ' ') leftover ${TABLE_PREFIX}* tables from an earlier attempt"
        printf '%s\n' "$leftovers" | while read -r t; do mysql_run -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE \`$t\`"; done
    fi
fi

install_log="$MIG/logs/wp-install.log"
wp core install --url="$SITE_URL" --title="$SITE_TITLE" --admin_user=epicadmin --admin_email="$ADMIN_EMAIL" --skip-email >"$install_log" 2>&1 || true
ADMIN_PASS="$(grep -oE 'Admin password: .*' "$install_log" | head -n1 | sed 's/^Admin password: //' || true)"
[ -n "$ADMIN_PASS" ] || stop "WordPress did not install (see $install_log)."
rm -f "$install_log"

wpq plugin activate epic-core
wpq theme activate epic
wpq plugin activate seo-by-rank-math
wpq epic setup
wp epic import "$MIG/export" 2>&1 | { grep -v '^Deprecated' || true; } | tee "$MIG/logs/import.log"
wpq epic seo
wpq option update blog_public 1
wpq option update default_comment_status closed
wpq option update default_ping_status closed
wpq option update admin_email "$ADMIN_EMAIL"
wpq rewrite structure '/%postname%'
wpq cache flush || true
say "WordPress is installed and the content imported."

# The pretty-address rules for Apache/LiteSpeed, written directly: WP-CLI cannot see the web server's modules.
cat >"$MIG/site/.htaccess" <<'HTACCESS'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress

# Keep private files private.
<FilesMatch "^(wp-config\.php|readme\.html|license\.txt)$">
    Require all denied
</FilesMatch>
<Files "xmlrpc.php">
    Require all denied
</Files>

# Long browser caching for pictures, styles and scripts.
<IfModule mod_expires.c>
ExpiresActive On
ExpiresByType image/jpeg "access plus 1 year"
ExpiresByType image/png "access plus 1 year"
ExpiresByType image/webp "access plus 1 year"
ExpiresByType image/svg+xml "access plus 1 year"
ExpiresByType text/css "access plus 1 month"
ExpiresByType application/javascript "access plus 1 month"
</IfModule>
<IfModule mod_deflate.c>
AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json image/svg+xml
</IfModule>
HTACCESS

# ------------------------------------------------------------------ 5. keep old addresses working
step "5/7 Keeping the old picture and file addresses working"
if [ -n "$OLD_PUBLIC" ]; then
    for d in uploads images; do
        if [ -d "$OLD_PUBLIC/$d" ]; then cp -R "$OLD_PUBLIC/$d" "$MIG/site/$d" && say "copied /$d (old links to it keep working)"; fi
    done
    # Search Console verification files and the .well-known folder, if the old site had them.
    for f in "$OLD_PUBLIC"/google*.html "$OLD_PUBLIC"/BingSiteAuth.xml "$OLD_PUBLIC"/ads.txt; do
        if [ -f "$f" ]; then cp "$f" "$MIG/site/" && say "kept $(basename "$f")"; fi
    done
    if [ -d "$OLD_PUBLIC/.well-known" ]; then cp -R "$OLD_PUBLIC/.well-known" "$MIG/site/.well-known" && say "kept .well-known"; fi
fi
find "$MIG/site" -type d -exec chmod 755 {} + 2>/dev/null || true
find "$MIG/site" -type f -exec chmod 644 {} + 2>/dev/null || true
chmod 640 "$MIG/site/wp-config.php"

# ------------------------------------------------------------------ 6. swap
step "6/7 Swapping the new site in"
BASE="${SMOKE_BASE:-$SITE_URL}"
curl_site() { curl -sS -L --max-redirs 3 --connect-timeout 20 --max-time 60 ${SMOKE_RESOLVE:+--resolve "$SMOKE_RESOLVE"} "$@"; }

# Can this server reach its own website at all? If not, an automatic check would be meaningless
# (and would wrongly undo a good site), so the check is skipped and you are asked to look instead.
CAN_CHECK="yes"
if [ "${SKIP_SMOKE:-no}" = "yes" ]; then CAN_CHECK="no"
else
    base_code="$(curl_site -o /dev/null -w '%{http_code}' "$BASE/" 2>/dev/null || echo 000)"
    if [ "$base_code" != "200" ]; then CAN_CHECK="no"; say "WARNING: this server could not open $BASE/ itself (got $base_code), so the automatic check after the swap is skipped. Please open the site yourself right after."; fi
fi

OLD="${DOCROOT}.laravel-$STAMP"
printf 'SWAP_DOCROOT=%q\nSWAP_OLD=%q\n' "$DOCROOT" "$OLD" >"$MIG/swap.env"
mv "$DOCROOT" "$OLD"
SWAPPED="yes"
mv "$MIG/site" "$DOCROOT"
say "swapped: the old site is at $OLD"

# ------------------------------------------------------------------ 7. check
step "7/7 Checking the live site"
failures=0
# Web servers keep compiled copies of the old site's PHP files for a short while (OPcache), so
# straight after the swap an address can briefly answer from the old site. Each address is
# therefore tried again for a while before it counts as failed.
check_url() { # check_url <path> <expected status> [text that must appear] [attempts]
    local code body i tries="${4:-4}"
    for i in $(seq 1 "$tries"); do
        body="$(mktemp)"
        code="$(curl_site -o "$body" -w '%{http_code}' "$BASE$1" 2>/dev/null || echo 000)"
        if [ "$code" = "$2" ] && { [ -z "${3:-}" ] || grep -qF -- "$3" "$body"; }; then
            printf '  ok    %s  (%s)\n' "$1" "$code"; rm -f "$body"; return 0
        fi
        rm -f "$body"
        [ "$i" -lt "$tries" ] && sleep "${CHECK_WAIT:-5}"
    done
    printf '  FAIL  %s  (got %s, wanted %s%s)\n' "$1" "$code" "$2" "${3:+ containing \"$3\"}"
    failures=$((failures + 1))
}
if [ "$CAN_CHECK" = "yes" ]; then
    check_url "/" 200 "wp-content/themes/epic" 14
    check_url "/who-we-are" 200
    check_url "/who-we-are/board-of-directors" 200 "Board of Directors"
    check_url "/what-we-do/projects" 200
    check_url "/events" 200
    check_url "/publications" 200
    check_url "/publications/journal" 200
    check_url "/get-involved/careers" 200
    check_url "/contact" 200 "Send us a message"
    check_url "/media/gallery" 200
    check_url "/search?q=growth" 200
    check_url "/sitemap_index.xml" 200 "<loc>"
    check_url "/robots.txt" 200 "Sitemap:"
    check_url "/p/privacy-policy" 200
    check_url "/no-such-page-here" 404
else
    say "(automatic check skipped)"
fi

if [ "$failures" -gt 0 ] && [ "$NO_ROLLBACK" != "yes" ]; then
    printf '\n%s check(s) failed, so the old site is being put back.\n' "$failures"
    rollback_swap "$DOCROOT" "$OLD"
    SWAPPED=""
    stop "The new site did not pass its checks and was NOT left live. Work files: $MIG  (the failed copy is at ${DOCROOT}.wordpress-$STAMP). Nothing was lost."
fi

# ------------------------------------------------------------------ report
step "Done"
cat <<EOF

  The website is now running on WordPress:   $SITE_URL
  Sign in:                                   $SITE_URL/wp-admin
    user      epicadmin
    password  $ADMIN_PASS
    email     $ADMIN_EMAIL
  >> Sign in, then change the password (Users > Profile). This is the only time it is shown.

  Next
    1. Rank Math SEO is configured. Optionally connect a free Rank Math account (Rank Math > Dashboard)
       to add Google Search Console numbers.
    2. In Google Search Console submit  $SITE_URL/sitemap_index.xml
    3. Edit the menus under Appearance > Menus, content under the EPIC menu, pages under Pages.

  Kept safe
    old Laravel site   $OLD
    database backup    $MIG/backup/database.sql.gz
    put the old site back at any time:   bash $0 rollback $MIG
    (delete $OLD and $MIG when you are happy, to free the space)
EOF
