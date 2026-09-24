#!/usr/bin/env bash
#
# Bump the supported WordPress version across the repository.
#
# Usage:
#   bin/bump-wp-version.sh --print-current
#   bin/bump-wp-version.sh <x.y.z>

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

PLUGIN_FILE="$REPO_ROOT/pressbooks.php"
COMPAT_FILE="$REPO_ROOT/compatibility.php"
README_FILE="$REPO_ROOT/README.md"
WORKFLOW_FILE="$REPO_ROOT/.github/workflows/tests.yml"

die() {
	printf 'Error: %s\n' "$*" >&2
	exit 1
}

# version_in <file> <perl-regex-with-one-capture-group> <label>
# Prints the first captured version found in the file.
version_in() {
	local file="$1" pattern="$2" label="$3" value
	value=$(PATTERN="$pattern" perl -ne 'if (/$ENV{PATTERN}/) { print $1; exit }' "$file")
	[ -n "$value" ] || die "$label: WordPress version not found in ${file#"$REPO_ROOT"/}"
	printf '%s' "$value"
}

# current_version
# Prints the version recorded in the repo. Fails when the target files
# disagree with each other, so drift is never silently ignored.
current_version() {
	set -e
	local compat plugin readme_requires readme_tested readme_prose workflow found

	compat=$(version_in "$COMPAT_FILE" "[$]pb_minimum_wp = '([0-9.]+)';" 'compatibility.php')
	plugin=$(version_in "$PLUGIN_FILE" 'Requires at least: WordPress ([0-9.]+)' 'pressbooks.php')
	readme_requires=$(version_in "$README_FILE" '^Requires at least: ([0-9.]+)' 'README.md (Requires at least)')
	readme_tested=$(version_in "$README_FILE" '^Tested up to: ([0-9.]+)' 'README.md (Tested up to)')
	readme_prose=$(version_in "$README_FILE" 'Pressbooks works with PHP [0-9.]+ and WordPress ([0-9]+\.[0-9]+\.[0-9]+)\.' 'README.md (Requirements)')
	workflow=$(version_in "$WORKFLOW_FILE" '^[[:space:]]*wordpress: ([0-9]+\.[0-9]+\.[0-9]+)' '.github/workflows/tests.yml')

	for found in "$plugin" "$readme_requires" "$readme_tested" "$readme_prose" "$workflow"; do
		[ "$found" = "$compat" ] || die "version mismatch: '$found' found alongside '$compat' in compatibility.php — fix the drift manually before bumping"
	done

	printf '%s' "$compat"
}

# replace_all <file> <perl-regex-ending-with-version> <new-version> <expected-count> <label>
# Replaces the trailing version in every match, asserting the expected count.
replace_all() {
	local file="$1" pattern="$2" replacement="$3" expected="$4" label="$5"
	PATTERN="$pattern" NEW_VALUE="$replacement" EXPECTED="$expected" LABEL="$label" \
		perl -0777 -i -pe '
			my $count = 0;
			$count += s{$ENV{PATTERN}}{ my $m = $&; $m =~ s/[0-9.]+$/$ENV{NEW_VALUE}/; $m }gme;
			die "$ENV{LABEL}: expected $ENV{EXPECTED} occurrence(s), found $count\n" unless $count == $ENV{EXPECTED};
		' "$file"
}

# bump_to <new-version> <current-version>
bump_to() {
	local new="$1" old="$2"

	if [ "$new" = "$old" ]; then
		printf 'Already at WordPress %s\n' "$new"
		return 0
	fi

	tmp=$(mktemp -d)
	trap 'rm -rf "$tmp"' EXIT

	cp "$PLUGIN_FILE" "$tmp/pressbooks.php"
	cp "$COMPAT_FILE" "$tmp/compatibility.php"
	cp "$README_FILE" "$tmp/README.md"
	cp "$WORKFLOW_FILE" "$tmp/tests.yml"

	replace_all "$tmp/pressbooks.php" 'Requires at least: WordPress [0-9.]+' "$new" 1 'pressbooks.php'
	replace_all "$tmp/compatibility.php" "[$]pb_minimum_wp = '[0-9.]+" "$new" 1 'compatibility.php'
	replace_all "$tmp/README.md" '^Requires at least: [0-9.]+' "$new" 1 'README.md (Requires at least)'
	replace_all "$tmp/README.md" '^Tested up to: [0-9.]+' "$new" 1 'README.md (Tested up to)'
	replace_all "$tmp/README.md" 'Pressbooks works with PHP [0-9.]+ and WordPress [0-9]+\.[0-9]+\.[0-9]+' "$new" 1 'README.md (Requirements)'
	replace_all "$tmp/tests.yml" '^[[:space:]]*wordpress: [0-9]+\.[0-9]+\.[0-9]+' "$new" 2 '.github/workflows/tests.yml'

	mv "$tmp/pressbooks.php" "$PLUGIN_FILE"
	mv "$tmp/compatibility.php" "$COMPAT_FILE"
	mv "$tmp/README.md" "$README_FILE"
	mv "$tmp/tests.yml" "$WORKFLOW_FILE"

	printf 'Bumped WordPress version from %s to %s\n' "$old" "$new"
}

case "${1:-}" in
	--print-current)
		current_version
		printf '\n'
		;;
	'')
		die 'usage: bin/bump-wp-version.sh --print-current | <x.y.z>'
		;;
	*)
		target="$1"
		case "$target" in
			*[!0-9.]*) die "invalid version: $target" ;;
		esac
		if [[ "$target" =~ ^[0-9]+\.[0-9]+$ ]]; then
			target="$target.0"
		fi
		[[ "$target" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || die "invalid version: $target"
		current="$(current_version)"
		bump_to "$target" "$current"
		;;
esac
