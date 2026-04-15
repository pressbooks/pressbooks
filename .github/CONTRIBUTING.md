# Contributing to Pressbooks

The following is a set of guidelines for contributing to Pressbooks (thanks to the [Atom](https://github.com/atom/atom/blob/master/CONTRIBUTING.md) project for their excellent contributing guidelines, on which these are based). If you plan on opening issues or submitting pull requests, we ask that you first take a moment to familiarize yourself with it. Thanks for your interest! :books:

## Table Of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Filing Bugs and Enhancement Suggestions](#filing-bugs-and-enhancement-suggestions)
	* [Reporting Bugs](#reporting-bugs)
	* [Suggesting Enhancements](#suggesting-enhancements)
3. [GitHub Copilot Setup](#github-copilot-setup)
4. [Contributing Code](#contributing-code)
    * [Installing for Development](#installing-for-development)
	* [Git Commit Messages](#git-commit-messages)
    * [Pull Requests](#pull-requests)
    
## Code of Conduct

This project adheres to the Contributor Covenant [code of conduct](https://github.com/pressbooks/pressbooks?tab=coc-ov-file#readme). By participating, you are expected to uphold this code. Please report unacceptable behaviour to [code@pressbooks.com](mailto:code@pressbooks.com).

## Filing Bugs and Enhancement Suggestions

Pressbooks uses [GitHub issues](https://guides.github.com/features/issues/) to track bugs and enhancements/feature ideas. Before submitting a bug report or enhancement suggestion: 
* **Perform a [search](https://wordpress.org/plugins/)** for general-purpose WordPress plugins. Your feature idea may already be available in one of these.
* **Perform a cursory search of [Pressbooks](https://github.com/pressbooks/pressbooks/issues) and our [ideas repo](https://github.com/pressbooks/ideas/issues)** to see if the problem has already been reported or the enhancement has already been suggested. We encourage you to read about [other search filters](https://help.github.com/articles/searching-issues/) which will help you write more focused queries. If an issue already exists, add a comment to the existing issue instead of opening a new one.
* **Watch this [3 minute YouTube video](https://youtu.be/PSFq6fAYuhw)** about creating enhancement suggestions or reporting bugs in Pressbooks.

### Reporting Bugs

Bugs are tracked as issues in the relevant GitHub repository. To create a good bug report, please create a new issue explaining the problem and including additional details to help maintainers reproduce the problem:

* **Use a clear and descriptive title** for the issue to identify the problem.
* **Describe the exact steps which reproduce the problem** with as many details as possible.
* **Provide specific examples to demonstrate the steps**. Include links to files or GitHub projects, or copy/pasteable snippets, which you use in those examples. If you're providing snippets in the issue, use [Markdown code blocks](https://help.github.com/articles/markdown-basics/#multiple-lines).
* **Describe the behavior you observed after following the steps** and point out what exactly is the problem with that behavior.
* **Explain which behavior you expected to see instead and why.**
* **If the problem is related to exporting**, attach (if possible) an export file which demonstrates the problem.
* **Include details about your configuration and environment.** Please provide the contents of your network's diagnostics page, available at `https://YOURNETWORK.URL/wp-admin/options.php?page=pressbooks_diagnostics`.

Provide more context by answering these questions:

* **Can you reproduce the problem with [all other plugins deactivated](http://codex.wordpress.org/Multisite_Network_Administration#Plugins)?**
* Are you experiencing this issue with one of our built-in root or book themes, or with your own custom root or book theme?
* **Did the problem start happening recently** (e.g. after updating to a new version of Pressbooks) or was this always a problem?
* If the problem started happening recently, **can you reproduce the problem in an older version of Pressbooks?** What's the most recent version in which the problem doesn't happen? You can download older versions of Pressbooks on [the releases page](https://github.com/pressbooks/pressbooks/releases/).
* **Can you reliably reproduce the issue?** If not, provide details about how often the problem happens and under which conditions it normally happens.

### Suggesting Enhancements

Enhancement suggestions, encompassing both minor improvements to existing functionality and completely new features, are tracked in our [ideas repo](https://github.com/pressbooks/ideas/issues). When suggesting an enhancement, create a new issue in our ideas repo:

* **Use a clear and descriptive title** for the issue to identify the suggestion.
* **Provide a clear, simple [user story](https://www.atlassian.com/agile/project-management/user-stories)** that follows this basic pattern: As a [type of user], I want to [some action or goal], in order to [solve a problem or achieve some benefit].
* **Explain why this enhancement would be useful** to other Pressbooks users.
* **Provide a step-by-step description of the suggested enhancement**.

## GitHub Copilot Setup

Pressbooks includes custom GitHub Copilot agents, skills, and file-specific instructions in `.github/` to help developers follow project conventions. These provide role-based AI assistance for planning, implementation, code review, and testing.

### Agents

Use `@` in the Copilot chat to select an agent. All agents are prefixed with `pb_` for easy identification:

| Agent | Purpose |
|-------|---------|
| `@pb_architect` | Research the codebase and produce implementation plans (read-only) |
| `@pb_developer` | Implement features/fixes following Pressbooks conventions |
| `@pb_reviewer` | Review code against standards, security, accessibility, and i18n (read-only) |
| `@pb_tester` | Write and run tests, analyze coverage, verify accessibility in the browser |

Each agent writes a structured report to `.github/reports/` (gitignored) for the human developer to review.

### Skill

Type `/pressbooks-development` in chat to load comprehensive Pressbooks development context on demand — architecture patterns, coding conventions, testing guides, and workflows.

### File Instructions

Coding standards are automatically loaded when you work on specific file types:

- `**/*.php` — PHP naming, i18n, a11y, security, multisite rules
- `**/*.js`, `**/*.css`, `**/*.scss` — ES6+, Alpine.js, vanilla JS, CSS conventions
- `**/*.blade.php` — Blade template escaping, semantic HTML, data passing
- `tests/**` — Codeception, WP_UnitTestCase, coverage requirements

### IDE Workspace Configuration

GitHub Copilot discovers agents and skills from `.github/` directories **at workspace root level only**. When you open the entire bedrock/development environment, the pressbooks plugin's `.github/` is nested too deep to be discovered automatically.

#### VS Code Setup

**Option 1: Multi-root workspace (recommended)**

Use the provided workspace file when opening from the bedrock root:

```bash
code pressbooks-dev.code-workspace
```

Or create your own `.code-workspace` file with this structure:

```json
{
  "folders": [
    {
      "path": ".",
      "name": "Pressbooks (bedrock)"
    },
    {
      "path": "web/app/plugins/pressbooks",
      "name": "pressbooks"
    }
  ],
  "settings": {
    "files.exclude": {
      "web/app/plugins/pressbooks": true
    }
  }
}
```

This creates a multi-root workspace where:
- **Pressbooks (bedrock)** — full environment (config, plugins, themes, WordPress)
- **pressbooks** — core plugin as a separate root (enables `.github/` discovery)

The `files.exclude` setting hides `web/app/plugins/pressbooks` from the bedrock tree to avoid duplicate file listings.

**Option 2: Open plugin directly**

If you only work on the core plugin, open it directly:

```bash
code web/app/plugins/pressbooks
```

All agents, skills, and instructions work automatically since `.github/` is at root level.

**Adding other plugins**

To add Copilot config for other Pressbooks plugins (that have their own `.github/` setup), add them as workspace folders:

1. `File → Add Folder to Workspace`
2. Select `web/app/plugins/pressbooks-{plugin-name}`
3. Add to `files.exclude` in workspace settings to avoid duplicates

#### PhpStorm / JetBrains IDEs

PhpStorm with GitHub Copilot plugin does not automatically discover agents from `.github/` in the same way as VS Code. However, the workspace instructions and coding standards still apply.

**Option 1: Open plugin as separate project**

Open `web/app/plugins/pressbooks` as a standalone project in PhpStorm. This makes `.github/` available at the project root.

**Option 2: Attach as additional content root**

1. Open your bedrock project normally
2. Go to `File → Settings → Project Structure`
3. Click `+ Add Content Root`
4. Add `web/app/plugins/pressbooks`
5. Mark this content root so PhpStorm treats it with priority

**Option 3: Use Directory mappings**

In PhpStorm settings, you can configure IDE to look for specific directories:

1. `File → Settings → Project → Directories`
2. Mark `web/app/plugins/pressbooks/.github` as a Resource Root

> **Note**: JetBrains Copilot integration may evolve. Check [JetBrains documentation](https://www.jetbrains.com/help/idea/github-copilot.html) for the latest on custom agent support.

#### Cursor / Other VS Code Forks

Cursor and similar VS Code forks typically support the same workspace file format. Use `pressbooks-dev.code-workspace` or the multi-root configuration above.

## Contributing Code

If you'd like to get involved, we suggest you take a look at issues tagged with the `Hacktoberfest` label in the [Pressbooks](https://github.com/pressbooks/pressbooks/issues?q=is%3Aissue%20state%3Aopen%20label%3Ahacktoberfest), [pressbooks-book](https://github.com/pressbooks/pressbooks-book/issues?q=is%3Aissue%20state%3Aopen%20label%3Ahacktoberfest), or [Aldine](https://github.com/pressbooks/pressbooks-aldine/issues?q=is%3Aissue%20state%3Aopen%20label%3Ahacktoberfest) repos. These issues are generally considered 'good first issues'. If an issue looks like something you'd be interested in working on, comment in the issue, using `@pressbooks/developers` to notify our team of your interest and any questions you might have.

### Installing for Development

Pressbooks uses [Composer](https://getcomposer.org) for dependency management and [Webpack](https://webpack.github.io/) wrapped in Laravel Mix for asset compilation. To set up a local instance of Pressbooks, use the setup steps in our [local-dev-environment](https://github.com/pressbooks/local-dev-environment?tab=readme-ov-file#setup-steps) repo. This approach uses Lando/Docker to provision a local instance of Pressbooks for testing and development by open source contributors. Please read our [local development guide](https://pressbooks.org/dev-docs/local-development/).

### Git Commit Messages

* Use the present tense ("Add feature" not "Added feature").
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...").
* Limit the first line to 72 characters or less.
* Reference issues and pull requests liberally.
* When only changing documentation, include `[ci skip]` in the commit description to avoid running automated tests.

### Pull Requests

* Please follow our [Code Styleguide](https://pressbooks.org/dev-guides/coding-standards/) in writing your new code.
* Documentation of PHP functions should adhere to the [PHPDoc](https://en.wikipedia.org/wiki/PHPDoc) format.
* Add relevant [unit tests](https://pressbooks.org/dev-guides/unit-testing/) for new functions/code you add. If you submit a pull request which reduces overall code coverage, you *will* be asked to revise the pull requests to add tests.
* Include a description of how to test your changes. Where relevant, please include screenshots or brief screen recordings in your pull request.
* PR branch names should follow the pattern: `feat/add-feature`, `fix/bug-fix`, `chore/perform-chore`, etc.
* We use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0-beta.2/) to express what kind of change a commit or PR represents. This also helps us use Semantic Versioning properly for our releases. Commit messages and PR titles should follow the pattern: `feat: Add Feature`, `fix: Bug Fix`, `chore: Perform Chore`, etc.
