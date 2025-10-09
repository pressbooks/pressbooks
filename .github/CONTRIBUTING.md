# Contributing to Pressbooks

The following is a set of guidelines for contributing to Pressbooks (thanks to the [Atom](https://github.com/atom/atom/blob/master/CONTRIBUTING.md) project for their excellent contributing guidelines, on which these are based). If you plan on opening issues or submitting pull requests, we ask that you first take a moment to familiarize yourself with it. Thanks for your interest! :books:

## Table Of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Filing Bugs and Enhancement Suggestions](#filing-bugs-and-enhancement-suggestions)
	* [Reporting Bugs](#reporting-bugs)
	* [Suggesting Enhancements](#suggesting-enhancements)
3. [Contributing Code][#contributing-code]
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

* Please follow our [Code Styleguide](https://pressbooks.org/dev-docs/coding-standards/) in writing your new code.
* Documentation of PHP functions should adhere to the [PHPDoc](https://en.wikipedia.org/wiki/PHPDoc) format.
* Add relevant [unit tests](https://pressbooks.org/dev-docs/unit-testing/) for new functions/code you add. If you submit a pull request which reduces overall code coverage, you *will* be asked to revise the pull requests to add tests.
* Include a description of how to test your changes. Where relevant, please include screenshots or brief screen recordings in your pull request.
* PR branch names should follow the pattern: `feat/add-feature`, `fix/bug-fix`, `chore/perform-chore`, etc.
* We use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0-beta.2/) to express what kind of change a commit or PR represents. This also helps us use Semantic Versioning properly for our releases. Commit messages and PR titles should follow the pattern: `feat: Add Feature`, `fix: Bug Fix`, `chore: Perform Chore`, etc.
