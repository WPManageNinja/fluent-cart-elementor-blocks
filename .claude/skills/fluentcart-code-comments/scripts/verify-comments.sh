#!/bin/bash
# Prove a comment cleanup changed no code: strip comments from each file and
# its git HEAD version, then compare. Run from inside the repo, under bash.
# usage: verify-comments.sh file...
cd "$(git rev-parse --show-toplevel)" || exit 2
T=$(mktemp -d); rc=0
for f in "$@"; do
  ext="${f##*.}"
  git show "HEAD:$f" > "$T/old.$ext" 2>/dev/null || { echo "SKIP (new file) $f"; continue; }
  case "$ext" in
    php)
      php -l "$f" >/dev/null 2>&1 || { echo "SYNTAX FAIL $f"; rc=1; continue; }
      php -w "$T/old.$ext" > "$T/a"; php -w "$f" > "$T/b" ;;
    js|mjs|cjs|jsx|ts|tsx)
      npx --yes esbuild "$T/old.$ext" --minify-whitespace --legal-comments=none --log-level=error --loader:.js=jsx > "$T/a" || { echo "PARSE FAIL (HEAD) $f"; rc=1; continue; }
      npx --yes esbuild "$f" --minify-whitespace --legal-comments=none --log-level=error --loader:.js=jsx > "$T/b" || { echo "PARSE FAIL $f"; rc=1; continue; } ;;
    *) echo "CHECK BY HAND $f (only comment lines may differ)"; continue ;;
  esac
  if cmp -s "$T/a" "$T/b"; then echo "OK $f"; else echo "CODE CHANGED $f"; rc=1; fi
done
rm -rf "$T"; exit $rc
