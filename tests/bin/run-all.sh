#!/usr/bin/env bash
#
# Test runner, modelled on FluentCart core's regression gate (tests/bin/run-all.sh).
#
# Usage:
#   tests/bin/run-all.sh                    # static + smoke + integration
#   tests/bin/run-all.sh static             # php -l and consistency checks (no WordPress)
#   tests/bin/run-all.sh smoke              # every widget renders on a real product
#   tests/bin/run-all.sh integration        # template library self-heal
#   tests/bin/run-all.sh smoke render       # only test files whose name contains "render"
#
# The live tiers load Elementor and this add-on for the test process only
# (tests/bin/lib/load-plugins.php), so they run even when both are inactive.
# Override the WordPress root only when auto-detection fails:
#   export WP_PLUGIN_TEST_ROOT=/path/to/wordpress

set -uo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SUITE="${1:-all}"
FILTER="${2:-}"

RED=$'\033[31m'
GREEN=$'\033[32m'
BOLD=$'\033[1m'
OFF=$'\033[0m'

FAILED=0
declare -a RESULTS=()

hr()
{
  printf '%s\n' "------------------------------------------------------------------------"
}

record()
{
  if [ "$2" -eq 0 ]; then
    RESULTS+=("${GREEN}PASS${OFF}  $1")
  else
    RESULTS+=("${RED}FAIL${OFF}  $1")
    FAILED=1
  fi
}

# Test files of a tier, narrowed by the optional filter.
tier_files()
{
  local file
  for file in "$PLUGIN_DIR/tests/$1"/*.php; do
    [ -f "$file" ] || continue
    if [ -z "$FILTER" ] || [[ "$(basename "$file")" == *"$FILTER"* ]]; then
      printf '%s\n' "$file"
    fi
  done
}

run_static()
{
  echo "${BOLD}static — no WordPress needed${OFF}"
  hr

  local lint_out code
  lint_out="$(cd "$PLUGIN_DIR" && find app boot config tests fluent-cart-elementor-blocks.php -name '*.php' -print0 2>/dev/null \
    | xargs -0 -n1 php -l 2>&1 | grep -v '^No syntax errors detected')"
  if [ -n "$lint_out" ]; then
    echo "$lint_out"
    record "static: php -l" 1
  else
    echo "  ok   every PHP file parses"
    record "static: php -l" 0
  fi

  local file
  while IFS= read -r file; do
    php "$file"
    code=$?
    record "static: $(basename "$file" .php)" "$code"
  done < <(tier_files static)
  echo
}

run_live()
{
  local tier="$1"
  echo "${BOLD}${tier} — live site, read-mostly${OFF}"
  hr

  local wp_root
  # shellcheck source=lib/resolve-wp-root.sh
  . "$PLUGIN_DIR/tests/bin/lib/resolve-wp-root.sh"
  wp_root="$(wp_plugin_test_resolve_wp_root "$PLUGIN_DIR")" || { record "${tier}: WordPress root" 2; return; }

  local file code found=0
  while IFS= read -r file; do
    found=1
    (cd "$wp_root" && wp --require="$PLUGIN_DIR/tests/bin/lib/load-plugins.php" eval-file "$file")
    code=$?
    record "${tier}: $(basename "$file" .php)" "$code"
  done < <(tier_files "$tier")

  if [ "$found" -eq 0 ]; then
    echo "  (no ${tier} tests match '${FILTER}')"
  fi
  echo
}

case "$SUITE" in
  all)
    run_static
    run_live smoke
    run_live integration
    ;;
  static)
    run_static
    ;;
  smoke|integration)
    run_live "$SUITE"
    ;;
  *)
    echo "Unknown tier '$SUITE'. Use: all | static | smoke | integration"
    exit 2
    ;;
esac

hr
echo "${BOLD}SUMMARY${OFF}"
for line in "${RESULTS[@]}"; do
  echo "  $line"
done
hr

exit "$FAILED"
