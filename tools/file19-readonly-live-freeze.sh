#!/usr/bin/env bash
# File 19 exact deployed-state evidence collector — safe read operations only.
# Usage: bash tools/file19-readonly-live-freeze.sh /absolute/path/to/wordpress
# Does not install, activate, upgrade, migrate, send, erase or mutate WordPress.
# REPORT IS PRIVATE: do not put this report in a public web directory or post raw secrets.
set -euo pipefail
umask 077

WP_ROOT="$1"
if [[ -z "$WP_ROOT" || "$WP_ROOT" != /* || ! -d "$WP_ROOT" ]]; then
  echo "An existing absolute WordPress root directory is required." >&2
  exit 2
fi
if ! command -v wp >/dev/null 2>&1; then
  echo "WP-CLI is unavailable; do not substitute database write commands." >&2
  exit 2
fi
if ! command -v sha256sum >/dev/null 2>&1; then
  echo "sha256sum is required for deployed artifact verification." >&2
  exit 2
fi

OUT="$(mktemp /tmp/file19-readonly-evidence-XXXXXXXX.txt)"
chmod 600 "$OUT"
ro_wp() { wp --path="$WP_ROOT" --skip-plugins --skip-themes "$@"; }
ro_db() { ro_wp db query "$1" --skip-column-names; }

{
  printf 'FILE 19: DEPLOYED REALITY FREEZE — READ-ONLY EVIDENCE\n'
  printf 'Status: SOURCE-AND-DATABASE-INVENTORY ONLY; application logs/versions still require operator attestation\n'
  date -u '+UTC snapshot: %Y-%m-%dT%H:%M:%SZ'

  echo '=== WordPress core / PHP CLI versions ==='
  ro_wp core version || echo 'WP_CORE_VERSION_UNAVAILABLE'
  php -r 'echo "PHP_CLI=",PHP_VERSION,"\n";' || echo 'PHP_CLI_UNAVAILABLE'
  echo 'WARNING: PHP CLI does not prove web-server PHP-FPM runtime.'

  echo '=== Active WordPress plugin metadata (name/status/version only) ==='
  ro_wp plugin list --status=active --fields=name,status,version --format=csv || echo 'ACTIVE_PLUGIN_LIST_UNAVAILABLE'

  echo '=== Active theme metadata (name/status/version only) ==='
  ro_wp theme list --status=active --fields=name,status,version --format=csv || echo 'ACTIVE_THEME_UNAVAILABLE'

  echo '=== File 19 known installation slugs (metadata only) ==='
  for slug in unified-notifications-19 19-unified-notifications; do
    printf 'slug=%s\n' "$slug"
    ro_wp plugin get "$slug" --fields=name,status,version --format=csv || echo "SLUG_NOT_FOUND_OR_INACCESSIBLE"
  done

  echo '=== Whitelisted File 19 option versions; never print arbitrary option values ==='
  for opt in sun_plugin_version sun_db_version; do
    printf 'option=%s : ' "$opt"
    ro_wp option get "$opt" || echo 'UNAVAILABLE'
  done

  echo '=== File19 scheduled hook metadata; no job execution ==='
  ro_wp cron event list --fields=hook,next_run_gmt,schedule --format=csv 2>/dev/null | awk -F, 'NR==1 || $1 ~ /^sun_/' || echo 'CRON_INVENTORY_UNAVAILABLE'

  echo '=== File19 database table names, aggregated row counts and column metadata only ==='
  prefix="$(ro_wp db prefix 2>/dev/null || true)"
  if [[ "$prefix" =~ ^[A-Za-z0-9_]+$ ]]; then
    tables="$(ro_db 'SHOW TABLES' 2>/dev/null || true)"
    any=0
    while IFS= read -r table; do
      [[ -n "$table" ]] || continue
      [[ "$table" =~ ^[A-Za-z0-9_]+$ ]] || continue
      case "$table" in "$prefix"sun_*) ;; *) continue ;; esac
      any=1
      echo "table_name=$table"
      printf 'row_count='
      ro_db "SELECT COUNT(*) FROM $table" || echo 'UNAVAILABLE'
      echo 'column_metadata='
      ro_db "SHOW COLUMNS FROM $table" || echo 'UNAVAILABLE'
    done <<< "$tables"
    [[ "$any" -eq 1 ]] || echo 'NO_FILE19_TABLES_IDENTIFIED'
  else
    echo 'TABLE_PREFIX_UNAVAILABLE_OR_INVALID'
  fi

  echo '=== File19 versioned deployed file checksums (file names + SHA256, no contents) ==='
  plugin_parent="$WP_ROOT/wp-content/plugins"
  for slug in unified-notifications-19 19-unified-notifications; do
    path="$plugin_parent/$slug"
    if [[ ! -d "$path" ]]; then
      echo "plugin_folder_absent=$slug"
      continue
    fi
    echo "plugin_folder_present=$slug"
    (
      cd "$path"
      find . -type f -print0 | LC_ALL=C sort -z | xargs -0 -r sha256sum
    ) || echo 'FILE_HASH_INVENTORY_INCOMPLETE'
    if [[ -f "$path/MANIFEST.sha256" ]]; then
      echo "packaged_manifest_check=$slug"
      (
        cd "$plugin_parent"
        sha256sum -c "$slug/MANIFEST.sha256"
      ) || echo 'PACKAGED_MANIFEST_MISMATCH_OR_INCOMPLETE'
    else
      echo 'PACKAGE_MANIFEST_NOT_PRESENT'
    fi
  done

  echo '=== Evidence limits ==='
  echo 'No database row data, patient details, messages, credentials, option payloads, or raw logs collected.'
  echo 'No WordPress data mutation, plugin write, deployment, migration or cron execution was requested.'
  echo 'WP-CLI may load must-use plugins during ordinary WordPress bootstrap; operator should review installed mu-plugins.'
  echo 'Migration state, deployment parity and Live functional verification remain UNVERIFIED until separate owner evidence.'
} > "$OUT" 2>&1

echo "Private read-only snapshot: $OUT"
echo 'Keep this file outside the web root. Share only a reviewed/redacted report with an authorized project administrator.'
