# Contributing to Pressbooks

The following is a set of guidelines for contributing to Pressbooks (thanks to the [Atom](https://github.com/atom/atom/blob/master/CONTRIBUTING.md) project for their excellent contributing guidelines, on which these are based). If you plan on opening issues or submitting pull requests, we ask that you first take a moment to familiarize yourself with it. Thanks for your interest! :books:

## Table Of Contents

1. [Code of Conduct](#code-of-conduct)
2. [How To Contribute?](#how-to-contribute)
	* [Installing for Development](#installing-for-development)
	* [Reporting Bugs](#reporting-bugs)
	* [Suggesting Enhancements](#suggesting-enhancements)
	* [Your First Code Contribution](#your-first-code-contribution)
	* [Pull Requests](#pull-requests)
3. [Styleguides](#styleguides)
	* [Code Styleguide](#code-styleguide)
	* [Documentation Styleguide](#documentation-styleguide)
	* [Git Commit Messages](#git-commit-messages)
4. [Additional Notes](#additional-notes)
	* [Issue and Pull Request Labels](#issue-and-pull-request-labels)

## Code of Conduct

This project adheres to the Contributor Covenant [code of conduct](https://github.com/pressbooks/pressbooks?tab=coc-ov-file#readme).
By participating, you are expected to uphold this code.
Please report unacceptable behavior to [code@pressbooks.com](mailto:code@pressbooks.com).

## How To Contribute

### Installing for Development

Pressbooks uses [Composer](https://getcomposer.org) for dependency management and [Webpack](https://webpack.github.io/) wrapped in Laravel Mix for asset compilation. To set up a local instance of Pressbooks, use the setup steps in our [local-dev-environment](https://github.com/pressbooks/local-dev-environment?tab=readme-ov-file#setup-steps) repo. This approach uses Lando/Docker to provision a local instance of Pressbooks for testing and development by open source contributors. Please read our [local development guide](https://pressbooks.org/dev-docs/local-development/).

### Reporting Bugs

#### Before Submitting A Bug Report

* **Check the [debugging docs with PHPStorm](https://pressbooks.org/dev-docs/phpstorm/).** You might be able to find the cause of the problem and fix things yourself. Most importantly, check if you can reproduce the problem [in the latest version of Pressbooks](http://github.com/pressbooks/pressbooks/releases/latest/) running on the [latest version of WordPress](http://codex.wordpress.org/Upgrading_WordPress) and if the problem happens with [all other plugins deactivated at the network level](http://codex.wordpress.org/Multisite_Network_Administration#Plugins).
* **Perform a [cursory search](https://github.com/issues?q=+is%3Aissue+repo%3Apressbooks%2Fpressbooks)** to see if the problem has already been reported. If it has, add a comment to the existing issue instead of opening a new one.

#### How Do I Submit A (Good) Bug Report?

Bugs are tracked as [GitHub issues](https://guides.github.com/features/issues/). Create an issue and provide the following information.

Explain the problem and include additional details to help maintainers reproduce the problem:

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

This section guides you through submitting a suggested enhancement for Pressbooks, including completely new features and minor improvements to existing functionality. Following these guidelines helps maintainers and the community understand your suggestion and find related suggestions.

Before creating enhancement suggestions, please check [this list](#before-submitting-an-enhancement-suggestion) as you might find out that you don't need to create one. When you are creating an enhancement suggestion, please [include as many details as possible](#how-do-i-submit-a-good-enhancement-suggestion).

#### Before Submitting An Enhancement Suggestion

* **Perform a [search](https://wordpress.org/plugins/)** for general-purpose WordPress plugins. Your feature may already be available in one of these.
* **Perform a cursory search of [Pressbooks](https://github.com/pressbooks/pressbooks/issues) and our [ideas repo](https://github.com/pressbooks/ideas/issues)** to see if the enhancement has already been suggested. If it has, add a comment to the existing issue instead of opening a new one.

#### How Do I Submit A (Good) Enhancement Suggestion?

Enhancement suggestions are tracked as [GitHub issues](https://guides.github.com/features/issues/). Create an issue in our [ideas repo](https://github.com/pressbooks/ideas/issues) and provide the following information:

* **Use a clear and descriptive title** for the issue to identify the suggestion.
* **Provide a clear, simple [user story](https://www.atlassian.com/agile/project-management/user-stories)** that follows this basic pattern: As a [type of user], I want to [some action or goal], in order to [solve a problem or achieve some benefit].
* **Explain why this enhancement would be useful** to other Pressbooks users.
* **Provide a step-by-step description of the suggested enhancement**.

### Your First Code Contribution

If you'd like to get involved, we suggest you take a look at issues tagged with the `Hacktoberfest` label in the Pressbooks, pressbooks-book, or Aldine repos. These issues are generally considered 'good first issues'. If an issue looks like something you'd be interested in working on, provide a comment in the issue using the `@pressbooks/developers` to notify our team of your interest and any questions you might have.

### Pull Requests

* Please follow our [Code Styleguide](#code-styleguide) in writing your new code.
* Please document your new code as stipulated in our [Documentation Styleguide](#documentation-styleguide).
* Please include a description of how to test your changes. Where relevant, please include screenshots or brief screen recordings in your pull request.
* We use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0-beta.2/) to express what kind of change a commit or PR represents. This also helps us use Semantic Versioning properly for our releases. Commit messages and PR titles should follow the pattern: `feat: Add Feature`, `fix: Bug Fix`, `chore: Perform Chore`, etc.
* PR branch names should follow the pattern: `feat/add-feature`, `fix/bug-fix`, `chore/perform-chore`, etc.

**NB:** We are making an effort to expand [unit testing](https://pressbooks.org/dev-docs/unit-testing/) in Pressbooks. Any pull requests that add new functions should include corresponding tests for those functions. If you submit a pull request which does not do this, you *will* be asked to revise the pull requests to add tests.

## Styleguides

We are in the process of creating comprehensive style guides for [code](#code-styleguide), [documentation](#documentation-styleguide) and [Git commit messages](#git-commit-messages).

### Code Styleguide

Our code styleguide can be found [here](https://pressbooks.org/dev-docs/coding-standards/).

### Documentation Styleguide

Documentation of PHP functions should adhere to the [PHPDoc](https://en.wikipedia.org/wiki/PHPDoc) format.

### Git Commit Messages

* Use the present tense ("Add feature" not "Added feature").
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...").
* Limit the first line to 72 characters or less.
* Reference issues and pull requests liberally.
* When only changing documentation, include `[ci skip]` in the commit description to avoid running automated tests.

## Additional Notes

### Issue and Pull Request Labels

This section lists the labels we use to help us track and manage issues and pull requests.

[GitHub search](https://help.github.com/articles/searching-issues/) makes it easy to use labels for finding groups of issues or pull requests you're interested in. To help you find issues and pull requests, each label is listed with search links for finding open items with that label in `pressbooks/pressbooks`. We  encourage you to read about [other search filters](https://help.github.com/articles/searching-issues/) which will help you write more focused queries.
