# Automated WordPress Version Bump Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A scheduled GitHub Action that detects new stable WordPress releases and opens a PR bumping every WP version reference in `pressbooks/pressbooks`.

**Architecture:** One shell script (`bin/bump-wp-version.sh`) owns the bump logic and its validation; one workflow (`.github/workflows/bump-wp-version.yml`) detects the latest stable version, runs the script, and opens/updates a PR with a bot PAT.

**Tech Stack:** Bash + Perl (portable macOS/Linux), GitHub Actions, `peter-evans/create-pull-request@v8`, `curl` + `jq` (preinstalled on GitHub-hosted runners).

**Spec:** `docs/superpowers/specs/2026-09-24-wp-version-bump-action-design.md`

## Global Constraints

- Version references and expected occurrence counts, exactly:

  | File | Pattern | Count |
  |---|---|---|
  | `pressbooks.php` | `Requires at least: WordPress X.Y.Z` | 1 |
  | `compatibility.php` | `$pb_minimum_wp = 'X.Y.Z';` | 1 |
  | `README.md` | `Requires at least: X.Y.Z` | 1 |
  | `README.md` | `Tested up to: X.Y.Z` | 1 |
  | `README.md` | `Pressbooks works with PHP 8.3 and WordPress X.Y.Z.` | 1 |
  | `.github/workflows/tests.yml` | `wordpress: X.Y.Z` (must not touch `wordpress: latest`) | 2 |

- All occurrences move together to the same version. No split `Requires at least` / `Tested up to` policy.
- Input must match `^[0-9]+\.[0-9]+(\.[0-9]+)?$` and is normalized to three components (`7.2` → `7.2.0`).
- The script stages edits in temp files and only moves them into place after all replacement + count assertions pass. It never leaves a partial bump.
- Script must run on macOS (BSD tooling) and Ubuntu (GNU tooling). Use Perl, not `sed -i`.
- No automated shell test file. Verification is manual and is part of the tasks below.
- Workflow opens a PR for human review, never merges.
- PR creation uses `secrets.WORKFLOW_TOKEN` (bot PAT), because PRs opened with the default `GITHUB_TOKEN` do not trigger `tests.yml`.
- Fixed branch name `chore/bump-wp-version`, `delete-branch: true`, base `dev`.
- Commit/title convention: `chore: bump WP to <version>`.
- Workflow declares `permissions: contents: read`; all writes go through the PAT.

---

### Task 1: `bin/bump-wp-version.sh`

**Files:**
- Create: `bin/bump-wp-version.sh`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `bin/bump-wp-version.sh --print-current` → prints the version currently recorded in the repo (no trailing newline), exit 0; exits non-zero with a message naming the drifted file when the target files disagree or a pattern is missing.
  - `bin/bump-wp-version.sh <x.y.z>` → bumps all 7 occurrences, exit 0; no-op message when already at `<x.y.z>`; exit non-zero on invalid input or pattern drift without modifying files.

- [ ] **Step 1: Create a feature branch**

```bash
git checkout -b chore/automate-wp-version-bump
```

- [ ] **Step 2: Write the script**

Create `bin/bump-wp-version.sh` with exactly this content:

```bash
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
	local compat plugin readme_requires readme_tested readme_prose workflow pair

	compat=$(version_in "$COMPAT_FILE" "[$]pb_minimum_wp = '([0-9.]+)';" 'compatibility.php')
	plugin=$(version_in "$PLUGIN_FILE" 'Requires at least: WordPress ([0-9.]+)' 'pressbooks.php')
	readme_requires=$(version_in "$README_FILE" '^Requires at least: ([0-9.]+)' 'README.md (Requires at least)')
	readme_tested=$(version_in "$README_FILE" '^Tested up to: ([0-9.]+)' 'README.md (Tested up to)')
	readme_prose=$(version_in "$README_FILE" 'Pressbooks works with PHP [0-9.]+ and WordPress ([0-9]+\.[0-9]+\.[0-9]+)\.' 'README.md (Requirements)')
	workflow=$(version_in "$WORKFLOW_FILE" '^[[:space:]]*wordpress: ([0-9]+\.[0-9]+\.[0-9]+)' '.github/workflows/tests.yml')

	for pair in \
		"pressbooks.php=$plugin" \
		"README.md (Requires at least)=$readme_requires" \
		"README.md (Tested up to)=$readme_tested" \
		"README.md (Requirements)=$readme_prose" \
		".github/workflows/tests.yml=$workflow"; do
		[ "${pair#*=}" = "$compat" ] || die "version mismatch: ${pair%%=*} has '${pair#*=}' but compatibility.php has '$compat' — fix the drift manually before bumping"
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
```

- [ ] **Step 3: Make it executable**

```bash
chmod +x bin/bump-wp-version.sh
```

- [ ] **Step 4: Verify `--print-current`**

Run: `bin/bump-wp-version.sh --print-current`
Expected: prints `7.1.2` and exits 0.

- [ ] **Step 5: Verify malformed input is rejected without writes**

```bash
bin/bump-wp-version.sh 7.x; echo "exit=$?"
```

Expected: `Error: invalid version: 7.x` on stderr and `exit=1`.

```bash
bin/bump-wp-version.sh 7.1.2.3; echo "exit=$?"; git diff --quiet && echo "no changes"
```

Expected: `Error: invalid version: 7.1.2.3`, `exit=1`, then `no changes`.

- [ ] **Step 6: Verify a real bump on a scratch working tree**

```bash
bin/bump-wp-version.sh 7.0.6
git diff --stat
git diff
```

Expected: `Bumped WordPress version from 7.1.2 to 7.0.6`, exactly 4 files changed, and the diff shows exactly 7 version substitutions (2 in `tests.yml`, 3 in `README.md`, 1 in `compatibility.php`, 1 in `pressbooks.php`) with no other changes.

```bash
bin/bump-wp-version.sh --print-current
bin/bump-wp-version.sh 7.0.6
```

Expected: `7.0.6`, then `Already at WordPress 7.0.6` (idempotent, exit 0).

```bash
git checkout -- pressbooks.php compatibility.php README.md .github/workflows/tests.yml
bin/bump-wp-version.sh --print-current
```

Expected: `7.1.2` restored.

- [ ] **Step 7: Verify drift detection fails loudly without writing**

```bash
perl -pi -e 's/^Tested up to:/Tested:/' README.md
bin/bump-wp-version.sh 7.0.6; echo "exit=$?"
git diff --stat
```

Expected: `Error: README.md (Tested up to): WordPress version not found in README.md`, `exit=1`, and `git diff --stat` shows only the intentional README drift (no version changes written).

```bash
git checkout -- README.md
```

- [ ] **Step 8: Commit**

```bash
git add bin/bump-wp-version.sh
git commit -m "chore: add WordPress version bump script"
```

---

### Task 2: `.github/workflows/bump-wp-version.yml`

**Files:**
- Create: `.github/workflows/bump-wp-version.yml`

**Interfaces:**
- Consumes: `bin/bump-wp-version.sh` from Task 1 (both `--print-current` and `<x.y.z>` modes).
- Produces: a workflow named `Bump WordPress Version` dispatchable via `workflow_dispatch` with an optional `version` input; opens/updates the PR branch `chore/bump-wp-version` against `dev`.

- [ ] **Step 1: Write the workflow**

Create `.github/workflows/bump-wp-version.yml` with exactly this content:

```yaml
name: Bump WordPress Version

on:
  schedule:
    # Daily at 13:00 UTC
    - cron: '0 13 * * *'
  workflow_dispatch:
    inputs:
      version:
        description: 'WordPress version to bump to (defaults to the latest stable release)'
        required: false
        type: string

permissions:
  contents: read

jobs:
  bump:
    runs-on: ubuntu-24.04
    steps:
      - name: Checkout code
        uses: actions/checkout@v6
        with:
          ref: dev

      - name: Resolve target WordPress version
        id: target
        env:
          INPUT_VERSION: ${{ inputs.version }}
        run: |
          version="$INPUT_VERSION"
          if [ -z "$version" ]; then
            version=$(curl -sf https://api.wordpress.org/core/version-check/1.7/ | jq -r '.offers[0].version')
          fi
          if ! printf '%s' "$version" | grep -qE '^[0-9]+(\.[0-9]+){1,2}$'; then
            echo "Could not resolve a stable WordPress version (got: '$version')" >&2
            exit 1
          fi
          echo "version=$version" >> "$GITHUB_OUTPUT"

      - name: Check current WordPress version
        id: current
        run: echo "version=$(bin/bump-wp-version.sh --print-current)" >> "$GITHUB_OUTPUT"

      - name: Bump WordPress version
        if: steps.target.outputs.version != steps.current.outputs.version
        env:
          TARGET_VERSION: ${{ steps.target.outputs.version }}
        run: bin/bump-wp-version.sh "$TARGET_VERSION"

      - name: Create or update pull request
        if: steps.target.outputs.version != steps.current.outputs.version
        uses: peter-evans/create-pull-request@v8
        with:
          # Classic PAT with `repo` + `workflow` scopes (the bump branch modifies a workflow file)
          token: ${{ secrets.WORKFLOW_TOKEN }}
          base: dev
          branch: chore/bump-wp-version
          delete-branch: true
          title: 'chore: bump WP to ${{ steps.target.outputs.version }}'
          commit-message: 'chore: bump WP to ${{ steps.target.outputs.version }}'
          body: |
            Bumps the supported WordPress version from `${{ steps.current.outputs.version }}` to `${{ steps.target.outputs.version }}`.

            Updated files:
            - `pressbooks.php` — plugin header
            - `compatibility.php` — `$pb_minimum_wp`
            - `README.md` — `Requires at least`, `Tested up to`, Requirements section
            - `.github/workflows/tests.yml` — test matrix

            If the test suite fails, a follow-up code or test fix is required before merging.
```

- [ ] **Step 2: Validate the YAML parses**

Run: `ruby -ryaml -e 'YAML.load_file(ARGV[0]); puts "YAML OK"' .github/workflows/bump-wp-version.yml`
Expected: `YAML OK`

- [ ] **Step 3: Verify the workflow references the committed script**

```bash
test -x bin/bump-wp-version.sh && echo "script executable"
grep -n "bin/bump-wp-version.sh" .github/workflows/bump-wp-version.yml
```

Expected: `script executable` and 2 matching lines (`--print-current` and the bump invocation).

- [ ] **Step 4: Verify the API resolution command used by the workflow**

Run: `curl -sf https://api.wordpress.org/core/version-check/1.7/ | jq -r '.offers[0].version'`
Expected: a stable version like `7.1.2` (no `-` suffix). If `jq` is not installed locally, skip this step — it runs on the runner, where `jq` is preinstalled.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/bump-wp-version.yml
git commit -m "chore: add scheduled WordPress version bump workflow"
```

---

### Task 3: Push and open the PR (documents the `WORKFLOW_TOKEN` prerequisite)

**Files:**
- None (git/PR operations only).

**Interfaces:**
- Consumes: commits from Tasks 1–2 on branch `chore/automate-wp-version-bump`.
- Produces: PR against `dev` whose body documents the one-time `WORKFLOW_TOKEN` secret setup.

- [ ] **Step 1: Push the branch**

```bash
git push -u origin chore/automate-wp-version-bump
```

- [ ] **Step 2: Open the PR**

```bash
gh pr create --base dev \
  --title "chore: automate WordPress version bumps" \
  --body "$(cat <<'EOF'
## Summary

- Adds `bin/bump-wp-version.sh`, which bumps all 7 WP version occurrences across `pressbooks.php`, `compatibility.php`, `README.md`, and `.github/workflows/tests.yml`, with occurrence assertions so a refactor fails loudly instead of half-bumping.
- Adds `.github/workflows/bump-wp-version.yml`: daily cron + manual dispatch, resolves the latest stable WP version from the WordPress.org API, and opens/updates a PR via `peter-evans/create-pull-request`.

## One-time setup (required)

Create a classic PAT on a bot account with `repo` and `workflow` scopes (the `workflow` scope is required because the bump branch modifies `.github/workflows/tests.yml`; a fine-grained PAT needs Contents: write, Pull requests: write, and Workflows: write), and store it as the repository secret `WORKFLOW_TOKEN`. A PAT is required because PRs opened with the default `GITHUB_TOKEN` do not trigger `tests.yml`, so checks would not run on the bump PR.

## How to test

1. Merge this PR.
2. Run the workflow manually: `gh workflow run bump-wp-version.yml -f version=7.0.6`.
3. Confirm a PR titled `chore: bump WP to 7.0.6` is opened against `dev` and that `Run Tests` starts on it.
4. Close that test PR and delete its branch.
5. Run `gh workflow run bump-wp-version.yml` with no input; it should resolve the latest stable version, find no diff, and finish green without opening a PR.
EOF
)"
```

- [ ] **Step 3: Verify the PR and its checks**

Run: `gh pr view --web` (or `gh pr checks --watch`).
Expected: PR targets `dev`, `Run Tests` runs on it.

Note: a failure in `Run Tests` on this PR would indicate the bump script produced something unexpected; report before merging.

---

### Task 4: Post-merge end-to-end verification (after the PR is merged)

**Files:**
- None.

**Interfaces:**
- Consumes: the merged workflow and the `WORKFLOW_TOKEN` secret.
- Produces: confirmation that a real dispatch opens a PR with `tests.yml` running on it.

- [ ] **Step 1: Dispatch a forced bump**

```bash
gh workflow run bump-wp-version.yml --repo pressbooks/pressbooks -f version=7.0.6
gh run watch --repo pressbooks/pressbooks
```

Expected: run completes green.

- [ ] **Step 2: Confirm the PR and its checks**

```bash
gh pr list --repo pressbooks/pressbooks --head chore/bump-wp-version
gh pr checks --repo pressbooks/pressbooks chore/bump-wp-version
```

Expected: an open PR titled `chore: bump WP to 7.0.6` with `Run Tests` (or its jobs) running/completed. This proves the PAT makes `tests.yml` trigger on the bot PR.

- [ ] **Step 3: Clean up the test PR**

```bash
gh pr close --repo pressbooks/pressbooks chore/bump-wp-version --delete-branch
```

- [ ] **Step 4: Confirm the no-op path**

```bash
gh workflow run bump-wp-version.yml --repo pressbooks/pressbooks
gh run watch --repo pressbooks/pressbooks
```

Expected: run completes green, no new PR (latest stable equals the recorded version).
