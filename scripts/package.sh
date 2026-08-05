#!/usr/bin/env bash
#
# Build the installable plugin zip — and refuse to build one that is broken.
#
# The same discipline as the Broadside theme's packager, and for the same reason:
# an artefact assembled by hand, from memory, is how a bad build reaches a user.
# So the zip is built, then INSPECTED — against the exact bytes that will be
# uploaded, not against the working tree they were copied from.
#
# Usage:
#   ./scripts/package.sh           build + verify -> build/broadside-blocks-<v>.zip
#   ./scripts/package.sh --check   verify only, write nothing (CI uses this)

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="broadside-blocks"
OUT="$ROOT/build"

red() { printf '\033[31m%s\033[0m\n' "$*"; }
grn() { printf '\033[32m%s\033[0m\n' "$*"; }

CHECK_ONLY=0
[ "${1:-}" = "--check" ] && CHECK_ONLY=1

VERSION=$(grep -oP '^\s*\*\s*Version:\s*\K[0-9.]+' "$ROOT/broadside-blocks.php")
[ -n "$VERSION" ] || { red "cannot read Version from broadside-blocks.php"; exit 1; }

echo "════ packaging $SLUG $VERSION"

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
DIR="$STAGE/$SLUG"
mkdir -p "$DIR"

# Copy, then delete what must not ship. A .distignore is only as good as the tool
# that reads it, and most tools do not.
rsync -a --exclude='.git' "$ROOT/" "$DIR/"
rm -rf "$DIR/.github" "$DIR/.wordpress-org" "$DIR/scripts" "$DIR/build" \
       "$DIR/node_modules" "$DIR/vendor" \
       "$DIR/README.md" "$DIR/.gitignore" "$DIR/.gitattributes" "$DIR/.distignore" \
       "$DIR/.editorconfig" "$DIR/composer.json" "$DIR/composer.lock" \
       "$DIR/phpcs.xml.dist" "$DIR/phpstan.neon" "$DIR/phpunit.xml.dist" \
       "$DIR/package.json" "$DIR/package-lock.json" 2>/dev/null || true

fail=0
note() { red "   ✗ $*"; fail=1; }
okay() { echo "   ✓ $*"; }

echo
echo "── required files"
for f in broadside-blocks.php readme.txt LICENSE; do
	[ -f "$DIR/$f" ] && okay "$f" || note "missing: $f"
done
[ -d "$DIR/blocks" ] && okay "blocks/ ($(find "$DIR/blocks" -name block.json | wc -l) blocks)" \
	|| note "missing: blocks/"

echo
echo "── no dev tooling survived into the package"
strays=$(find "$DIR" -name '.*' -not -name '.' -not -path "$DIR" 2>/dev/null || true)
[ -z "$strays" ] && okay "no dotfiles" || while IFS= read -r s; do note "dotfile: ${s#"$DIR"/}"; done <<< "$strays"

echo
echo "── version is in lockstep"
v_readme=$(grep -oP '^Stable tag:\s*\K[0-9.]+' "$DIR/readme.txt")
if [ "$VERSION" = "$v_readme" ]; then
	okay "broadside-blocks.php = readme.txt = $VERSION"
else
	note "version mismatch: plugin=$VERSION readme.txt=$v_readme"
fi

echo
echo "── licence declared"
grep -q 'GPL' "$DIR/broadside-blocks.php" && okay "plugin header declares GPL" || note "no GPL in the plugin header"
grep -q '^License: *GPLv2' "$DIR/readme.txt" && okay "readme.txt declares GPLv2" || note "readme.txt must declare GPLv2"
[ -f "$DIR/LICENSE" ] && okay "GPL text ships" || note "LICENSE is missing"

echo
echo "── the blocks register, and the plugin lints"
if find "$DIR" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null 2>&1; then
	okay "PHP lints clean"
else
	note "PHP syntax error in the package"
fi
grep -q 'register_block_type' "$DIR/inc-blocks.php" && okay "register_block_type() is HERE, where it belongs" \
	|| note "the plugin does not register any block — that is its entire job"

echo
if [ "$fail" -ne 0 ]; then
	red "════ NOT SHIPPABLE — fix the above."
	exit 1
fi
grn "════ package is clean"

[ "$CHECK_ONLY" -eq 1 ] && { echo "   (--check: no zip written)"; exit 0; }

mkdir -p "$OUT"
ZIP="$OUT/$SLUG-$VERSION.zip"
rm -f "$ZIP"
( cd "$STAGE" && zip -qr "$ZIP" "$SLUG" -x '*.DS_Store' )

echo
echo "════ $ZIP"
echo "   $(du -h "$ZIP" | cut -f1)  ·  $(unzip -l "$ZIP" | tail -1 | awk '{print $2}') files"
echo "   The zip's top-level directory is '$SLUG' — the slug WordPress installs under."
