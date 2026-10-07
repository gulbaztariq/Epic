#!/usr/bin/env bash
# Builds a throw-away WordPress with the EPIC plugin and theme, loads the starter content, then
# fetches every page and checks the redirects and the Rank Math output. No browser needed.
#
#   DB_HOST=127.0.0.1 DB_USER=root DB_PASS= DB_NAME=epic_smoke bash wordpress/tests/smoke.sh
#
# Optional: WP_SRC=<an unpacked WordPress>, RANKMATH_SRC=<an unpacked Rank Math plugin> to avoid
# downloading, WP_CLI="php /path/wp-cli.phar", PORT=8099, KEEP=1 to leave the site in place.
# The database named DB_NAME is EMPTIED. Never point this at a real site's database.
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/.." && pwd)"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS-}"
DB_NAME="${DB_NAME:-epic_smoke}"
PORT="${PORT:-8099}"
WP_CLI="${WP_CLI:-wp}"
URL="http://127.0.0.1:$PORT"
SITE="$(mktemp -d "${TMPDIR:-/tmp}/epic-smoke.XXXXXX")"
SERVER_PID=""
failed=0

cleanup() {
    [ -n "$SERVER_PID" ] && kill "$SERVER_PID" 2>/dev/null || true
    [ "${KEEP:-}" = "1" ] && { echo "kept: $SITE ($URL)"; return; }
    rm -rf "$SITE"
}
trap cleanup EXIT

# Every WP-CLI call is announced (its subcommand only, never its arguments, which can hold
# passwords) and capped, so a stall shows where it is instead of hanging the job silently.
wp() {
    printf '   wp %s %s\n' "${1:-}" "${2:-}" | sed 's/ -.*//' >&2
    timeout "${WP_TIMEOUT:-300}" $WP_CLI --path="$SITE" --allow-root "$@" </dev/null
}
CURL=(curl --max-time 60)
ok()   { printf ' ok   %s\n' "$*"; }
fail() { printf 'FAIL  %s\n' "$*"; failed=$((failed + 1)); }
check() { # check <name> <command...>
    local name="$1"; shift
    if "$@" >/dev/null 2>&1; then ok "$name"; else fail "$name"; fi
}

echo "== Building a test site in $SITE"
if [ -n "${WP_SRC:-}" ]; then cp -a "$WP_SRC/." "$SITE/"; else wp core download --quiet; fi

wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="$DB_HOST" --skip-check --quiet
wp db reset --yes --quiet 2>/dev/null || { wp db create --quiet && true; }
ADMIN_PASSWORD="$(head -c 18 /dev/urandom | base64 | tr -d '/+=')"
wp core install --url="$URL" --title="EPIC" --admin_user=smoke --admin_password="$ADMIN_PASSWORD" --admin_email=smoke@example.com --skip-email --quiet

ln -s "$ROOT/plugins/epic-core" "$SITE/wp-content/plugins/epic-core"
ln -s "$ROOT/themes/epic" "$SITE/wp-content/themes/epic"
if [ -n "${RANKMATH_SRC:-}" ]; then ln -s "$RANKMATH_SRC" "$SITE/wp-content/plugins/seo-by-rank-math"; else wp plugin install seo-by-rank-math --quiet; fi

wp theme activate epic --quiet
wp plugin activate epic-core seo-by-rank-math --quiet
wp epic setup >/dev/null
wp epic import "$ROOT/starter" >/dev/null
wp epic seo >/dev/null
wp rewrite flush --hard >/dev/null 2>&1 || wp rewrite flush >/dev/null

wp config set DISABLE_WP_CRON true --raw --quiet
PHP_CLI_SERVER_WORKERS=4 php -S "127.0.0.1:$PORT" -t "$SITE" "$HERE/router.php" >"$SITE/server.log" 2>&1 &
SERVER_PID=$!
echo "== Starting the test server at $URL"
for _ in $(seq 1 30); do "${CURL[@]}" -fs -o /dev/null "$URL/" 2>/dev/null && break; sleep 0.5; done

status() { "${CURL[@]}" -s -o /dev/null -w '%{http_code}' "$1"; }
body()   { "${CURL[@]}" -s "$1"; }
redirect() { "${CURL[@]}" -s -o /dev/null -w '%{http_code} %{redirect_url}' "$1"; }

echo "== Every page and item answers"
# Container pages only pass the visitor on to their first real page.
CONTAINERS=" /what-we-do /get-involved /media "
urls="$(wp post list --post_type=page,epic_publication,epic_event,epic_project,epic_post,epic_podcast,epic_album,epic_career --post_status=publish --field=url)"
total=0
while IFS= read -r u; do
    [ -n "$u" ] || continue
    path="${u#"$URL"}"; [ -n "$path" ] || path="/"
    total=$((total + 1))
    code="$(status "$u")"
    if [[ "$CONTAINERS" == *" $path "* ]]; then want=301; else want=200; fi
    if [ "$code" != "$want" ]; then fail "$path -> $code (wanted $want)"; continue; fi
    if [ "$want" = 200 ]; then
        html="$(body "$u")"
        if grep -qE '(Warning|Notice|Fatal error|Deprecated)(</b>)?:[[:space:]].*\.php' <<<"$html"; then fail "$path prints a PHP error"; fi
        grep -q '<h1' <<<"$html" || fail "$path has no heading"
    fi
done <<<"$urls"
[ "$total" -ge 40 ] && ok "$total pages and items checked" || fail "only $total pages and items found"

echo "== Old addresses"
check "/p/{slug} redirects to the page"  bash -c "[[ \"\$(curl --max-time 60 -s -o /dev/null -w '%{http_code} %{redirect_url}' $URL/p/privacy-policy)\" == \"301 $URL/privacy-policy\" ]]"
check "board-of-governance redirects"    bash -c "[[ \"\$(curl --max-time 60 -s -o /dev/null -w '%{http_code} %{redirect_url}' $URL/who-we-are/board-of-governance)\" == \"301 $URL/who-we-are/board-of-directors\" ]]"
check "sitemap.xml redirects"            bash -c "[[ \"\$(curl --max-time 60 -s -o /dev/null -w '%{http_code}' $URL/sitemap.xml)\" == 301 ]]"
check "/?s= reaches the search page"     bash -c "[[ \"\$(curl --max-time 60 -s -o /dev/null -w '%{redirect_url}' '$URL/?s=growth')\" == \"$URL/search?q=growth\" ]]"
check "unknown address is a 404"         bash -c "[[ \"\$(curl --max-time 60 -s -o /dev/null -w '%{http_code}' $URL/no-such-page-here)\" == 404 ]]"

echo "== Rank Math output"
home="$(body "$URL/")"
check "title tag"            grep -q '<title>[^<]\+</title>' <<<"$home"
check "meta description"     grep -q '<meta name="description" content="[^"]\+"' <<<"$home"
check "canonical link"       grep -q '<link rel="canonical"' <<<"$home"
check "Open Graph title"     grep -q 'property="og:title"' <<<"$home"
check "Twitter card"         grep -q 'name="twitter:card"' <<<"$home"
check "structured data"      grep -q 'application/ld+json' <<<"$home"
check "sitemap index"        bash -c "curl --max-time 60 -s $URL/sitemap_index.xml | grep -q '<sitemap>'"
check "robots.txt sitemap"   bash -c "curl --max-time 60 -s $URL/robots.txt | grep -q '^Sitemap: $URL/sitemap_index.xml'"
check "search is noindex"    bash -c "curl --max-time 60 -s '$URL/search?q=growth' | grep -qi 'noindex'"
event="$(wp post list --post_type=epic_event --post_status=publish --field=url --posts_per_page=1)"
check "event has Event schema" bash -c "curl --max-time 60 -s '$event' | grep -q '\"@type\":\"Event\"'"

echo
if [ "$failed" -gt 0 ]; then
    echo "$failed check(s) FAILED. What the site said, for diagnosis:"
    echo "-- php $(php -r 'echo PHP_VERSION;'), wordpress $(wp core version 2>/dev/null), permalinks '$(wp option get permalink_structure 2>/dev/null)'"
    wp plugin list --fields=name,status,version 2>/dev/null || true
    wp rewrite list --format=csv 2>/dev/null | grep -i sitemap | head -5 || true
    for path in /sitemap.xml /sitemap_index.xml /robots.txt; do
        echo "-- GET $path"
        "${CURL[@]}" -si "$URL$path" | head -12 | cut -c1-200
    done
    echo "-- server log tail"; tail -n 8 "$SITE/server.log" | cut -c1-200
    KEEP=1
    exit 1
fi
echo "all passed"
