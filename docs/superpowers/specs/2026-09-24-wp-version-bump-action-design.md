# Automated WordPress Version Bump — Design

Date: 2026-09-24
Repo: `pressbooks/pressbooks`
Status: Approved

## Problem

Every WordPress release requires a manual PR that updates the supported WP version in
several places. The last bumps (`chore: bump WP 7.1.2`, `chore: bump WP 7.0.6`) each
touched 4 files and 7 occurrences across:

- `.github/workflows/tests.yml` — 2 CI matrix entries
- `README.md` — `Requires at least:`, `Tested up to:`, and a prose sentence
- `compatibility.php` — `$pb_minimum_wp`
- `pressbooks.php` — plugin header `Requires at least:`

This is mechanical, easy to miss a spot, and happens often enough to automate.

## Scope

- Repository: `pressbooks/pressbooks` only. A reusable org-wide workflow is out of scope;
  the script is kept portable so it can be lifted later if desired.
- WordPress version only. PHP version bumps are out of scope.
- The automation opens a PR; it does not merge it.
- Fixing code/test breakage caused by a new WP release is out of scope. The PR exists to
  surface that breakage via the existing test suite.

## Decisions

| Decision | Choice |
|---|---|
| Detection | Daily cron + `workflow_dispatch` |
| Target version | Latest stable WordPress release; all occurrences move together |
| PR behavior | Open PR for human review; no auto-merge |
| Bot authentication | PAT secret so `tests.yml` runs on the bot PR |
| Approach | Committed shell script + workflow (not inline YAML, not Renovate) |
| Automated shell tests | Not required; manual verification |

## Components

### `bin/bump-wp-version.sh`

Single source of truth for the bump logic. Runnable locally and in CI.

Interface:

- `bin/bump-wp-version.sh --print-current`
  Prints the version currently recorded in the repo (read from `$pb_minimum_wp` in
  `compatibility.php`). Also asserts the other target files agree; if they disagree, it
  fails non-zero with a message naming the mismatched files, so partial drift is never
  masked by an early no-op exit. Exit 0.
- `bin/bump-wp-version.sh <x.y.z>`
  Rewrites every occurrence to `<x.y.z>`, then self-verifies. Exit 0 on success
  (including the no-op case), non-zero with a clear message on any failure.

Behavior:

1. Validates the argument against `^[0-9]+\.[0-9]+(\.[0-9]+)?$` and normalizes to three
   components (`7.2` becomes `7.2.0`).
2. Applies these replacements:

   | File | Pattern | Expected count |
   |---|---|---|
   | `pressbooks.php` | `Requires at least: WordPress X.Y.Z` | 1 |
   | `compatibility.php` | `$pb_minimum_wp = 'X.Y.Z';` | 1 |
   | `README.md` | `Requires at least: X.Y.Z` | 1 |
   | `README.md` | `Tested up to: X.Y.Z` | 1 |
   | `README.md` | `Pressbooks works with PHP 8.3 and WordPress X.Y.Z.` | 1 |
   | `.github/workflows/tests.yml` | `wordpress: X.Y.Z` | 2 |

   The CI matrix replacement must not touch the `wordpress: latest` entry; the pattern
   matches a numeric version only.
3. Asserts every expected occurrence is found. If any file has drifted (renamed field,
   restructured YAML, moved README text), the script fails and writes nothing — edits are
   staged in temp files and moved into place only after all replacements succeed.
4. Idempotent: when the file contents already contain the target version, exits 0 without
   changes. `--print-current` catches cross-file disagreement beforehand, so this exit is
   only reached when all files already agree.
5. Portable across macOS (BSD tooling) and Linux (GNU tooling), since the workflow runs on
   Ubuntu and developers run macOS.

A `--dry-run` mode is intentionally not included (YAGNI — the workflow only invokes the
script when versions differ).

No dedicated shell test file: verification is manual (see Verification).

### `.github/workflows/bump-wp-version.yml`

```yaml
name: Bump WordPress Version

on:
  schedule:
    - cron: '0 13 * * *'
  workflow_dispatch:
    inputs:
      version:
        description: 'WordPress version to bump to (defaults to latest stable)'
        required: false
```

Single job:

1. `actions/checkout@v6` on `dev`.
2. Resolve the target version:
   - the `workflow_dispatch` input when provided, otherwise
   - `curl -sf https://api.wordpress.org/core/version-check/1.7/ | jq -r '.offers[0].version'`.
   - Fail the job if the result is empty or contains `-` (RC/beta fail-safe).
3. Compare with `bin/bump-wp-version.sh --print-current`. If equal, stop — no PR.
4. Run `bin/bump-wp-version.sh <version>`.
5. Create or update the PR with `peter-evans/create-pull-request@v8`:
   - `token: ${{ secrets.WORKFLOW_TOKEN }}` — a bot PAT. This is required because PRs
     opened with the default `GITHUB_TOKEN` do not trigger other workflows, so
     `tests.yml` would not run on the PR.
   - `base: dev`
   - fixed `branch: chore/bump-wp-version`, `delete-branch: true`
   - `title` and `commit-message`: `chore: bump WP to <version>` (matches the existing
     `chore: bump WP …` convention and release-please's `chore` changelog section)
   - body: current version, target version, the list of changed files, and a note that
     failing checks may require a follow-up code/test fix.

The workflow declares `permissions: contents: read`; all writes are performed with the
PAT. A fixed branch name means a newer WP release updates the open PR in place instead
of creating a second stale PR.

### Setup prerequisite

Create a PAT on a bot account (`repo` scope) and store it as the repository secret
`WORKFLOW_TOKEN`. Without it, PR creation fails at that step. This is a one-time manual
step and must be documented in the PR introducing the workflow.

## Edge cases

- **New WP release breaks tests.** The workflow still succeeds; the PR shows failing
  checks and a human fixes the branch. Expected, not a bug.
- **WP API returns a lower version than recorded** (e.g. mirror lag): the workflow still
  opens a PR, with both versions in the body, so a human can judge.
- **Workflow re-run while a bump PR is open:** `create-pull-request` updates the existing
  PR; no duplicates.
- **PAT missing or expired:** the job fails at the PR step; no orphan branches are pushed
  because `create-pull-request` performs the push with the same token.
- **README or workflow refactor breaks a pattern:** the script fails before writing, so the
  repo is never left half-bumped.
- **WP releases a beta/RC to the default channel:** the `-` fail-safe skips it until a
  stable release appears.

## Non-goals

- Auto-merging the PR.
- Fixing test/code regressions caused by a WP release.
- PHP version bumps.
- Reusable workflow for other Pressbooks repositories (script portability only).
- Renovate/Dependabot based version management.

## Verification

1. `bin/bump-wp-version.sh --print-current` prints the version currently in
   `compatibility.php`.
2. On a scratch branch: `bin/bump-wp-version.sh 7.0.6`, then `git diff` shows exactly the
   7 expected occurrences in 4 files; running the script again is a no-op; then
   `git checkout -- .`.
3. Malformed input (e.g. `7.x`) exits non-zero without modifying files.
4. Simulate drift (rename a field) and confirm the script fails without writing.
5. End-to-end: run the workflow via `workflow_dispatch` with a version older than current
   (e.g. `7.0.6`) on a scratch branch and confirm a PR opens and `tests.yml` runs on it.
