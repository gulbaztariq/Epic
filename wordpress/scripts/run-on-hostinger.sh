#!/usr/bin/env bash
#
# Runs migrate-to-wordpress.sh on the Hostinger account over SSH. The "Deploy WordPress" GitHub
# workflow calls this, because a GitHub runner can reach the server when other places cannot.
# It can also be run from any machine that has an SSH key for the account.
#
# Everything comes from environment variables, so nothing secret is ever on a command line:
#
#   HOSTINGER_HOST        server address
#   HOSTINGER_PORT        SSH port (Hostinger uses 65002)
#   HOSTINGER_USER        SSH user name
#   HOSTINGER_SSH_KEY     the PRIVATE key, whole text, including the BEGIN and END lines
#   HOSTINGER_KNOWN_HOSTS optional: the server's host key line(s), from  ssh-keyscan -p PORT HOST
#                         (without it the key is fetched now and trusted on first use, with a warning)
#   MODE                  report (default: looks only, changes nothing) or deploy
#   ADMIN_PASSWORD        required for MODE=deploy: the password for the new WordPress user epicadmin.
#                         Sent to the server on standard input, never in an argument or a log.
#   REF                   the commit of the EPIC theme and plugin to install (default: this checkout's HEAD)
#   SCRIPT_PATH           the migration script to upload (default: the one next to this file)
#
# The migration script's output is printed as it runs. That output can be public (a public
# repository's CI log), so the script is told PRIVATE_LOG=yes: it prints no password and no email.

set -euo pipefail

die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPT_PATH="${SCRIPT_PATH:-$HERE/migrate-to-wordpress.sh}"
MODE="${MODE:-report}"

for v in HOSTINGER_HOST HOSTINGER_PORT HOSTINGER_USER HOSTINGER_SSH_KEY; do
    [ -n "${!v:-}" ] || die "$v is not set. In GitHub: Settings > Secrets and variables > Actions > New repository secret."
done
[[ "$HOSTINGER_HOST" =~ ^[A-Za-z0-9._-]+$ ]] || die "HOSTINGER_HOST does not look like a host name or address."
[[ "$HOSTINGER_PORT" =~ ^[0-9]{1,5}$ ]] || die "HOSTINGER_PORT must be a number."
[[ "$HOSTINGER_USER" =~ ^[A-Za-z0-9._-]+$ ]] || die "HOSTINGER_USER does not look like a user name."
case "$MODE" in report|deploy) ;; *) die "MODE must be report or deploy." ;; esac
[ -f "$SCRIPT_PATH" ] || die "Cannot find the migration script at $SCRIPT_PATH."

REF="${REF:-$(git -C "$HERE" rev-parse HEAD 2>/dev/null || true)}"
[[ "$REF" =~ ^[A-Za-z0-9._/-]+$ ]] || die "REF is missing or not a plain commit or branch name."

if [ "$MODE" = "deploy" ]; then
    [ -n "${ADMIN_PASSWORD:-}" ] || die "A deploy needs ADMIN_PASSWORD (the secret EPIC_WP_ADMIN_PASSWORD): the password you will sign in to WordPress with."
    [ "${#ADMIN_PASSWORD}" -ge 12 ] || die "ADMIN_PASSWORD is too short. Use at least 12 characters."
fi

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
umask 077

printf '%s\n' "$HOSTINGER_SSH_KEY" > "$work/key"
ssh-keygen -y -f "$work/key" >/dev/null 2>&1 || die "HOSTINGER_SSH_KEY is not a usable private key. Paste the whole file, including the -----BEGIN and -----END lines, and no passphrase."

if [ -n "${HOSTINGER_KNOWN_HOSTS:-}" ]; then
    printf '%s\n' "$HOSTINGER_KNOWN_HOSTS" > "$work/known_hosts"
else
    ssh-keyscan -T 15 -p "$HOSTINGER_PORT" "$HOSTINGER_HOST" > "$work/known_hosts" 2>/dev/null || true
    [ -s "$work/known_hosts" ] || die "Could not reach the server on port $HOSTINGER_PORT to read its host key. Is SSH enabled in hPanel, and is the port right?"
    echo "WARNING: no HOSTINGER_KNOWN_HOSTS secret, so the server's identity was trusted on first use:"
    ssh-keygen -lf "$work/known_hosts" | sed 's/^/         /'
    echo "         Add that line as the secret to pin it from now on."
fi

common=(-i "$work/key" -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=yes
        -o "UserKnownHostsFile=$work/known_hosts" -o ConnectTimeout=20
        -o ServerAliveInterval=30 -o ServerAliveCountMax=20)
target="$HOSTINGER_USER@$HOSTINGER_HOST"

echo "== Uploading the migration script"
scp -P "$HOSTINGER_PORT" "${common[@]}" "$SCRIPT_PATH" "$target:migrate-to-wordpress.sh"

echo "== Running it ($MODE) with the theme and plugin from $REF"
if [ "$MODE" = "deploy" ]; then
    # The password is read from standard input by the remote shell, so it is in no argument list.
    printf '%s\n' "$ADMIN_PASSWORD" | ssh -p "$HOSTINGER_PORT" "${common[@]}" "$target" \
        "IFS= read -r ADMIN_PASSWORD; export ADMIN_PASSWORD; DEPLOY=yes PRIVATE_LOG=yes REF='$REF' bash migrate-to-wordpress.sh"
else
    ssh -p "$HOSTINGER_PORT" "${common[@]}" "$target" \
        "DEPLOY=no PRIVATE_LOG=yes REF='$REF' bash migrate-to-wordpress.sh" </dev/null
fi
