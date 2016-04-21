# Contributing to Pressbooks

The following is a set of guidelines for contributing to Pressbooks (thanks to the [Atom](https://github.com/atom/atom/blob/master/CONTRIBUTING.md) project for their excellent contributing guidelines, on which these are based). If you plan on opening issues or submitting pull requests, we ask that you first take a moment to familiarize yourself with it. Thanks for your interest! :books:

#### Table Of Contents

[Code of Conduct](#code-of-conduct)

[How To Contribute?](#how-to-contribute)

  * [Installing for Development](#installing-for-development)
  * [Reporting Bugs](#reporting-bugs)
  * [Suggesting Enhancements](#suggesting-enhancements)
  * [Your First Code Contribution](#your-first-code-contribution)
  * [Pull Requests](#pull-requests)

[Styleguides](#styleguides)

  * [Code Styleguide](#code-styleguide)
  * [Documentation Styleguide](#documentation-styleguide)
  * [Git Commit Messages](#git-commit-messages)

[Additional Notes](#additional-notes)
  * [Issue and Pull Request Labels](#issue-and-pull-request-labels)

## Code of Conduct

This project adheres to the Contributor Covenant [code of conduct](CODE_OF_CONDUCT.md).
By participating, you are expected to uphold this code.
Please report unacceptable behavior to [code@pressbooks.com](mailto:code@pressbooks.com).

## How To Contribute

### Installing for Development

Pressbooks uses [Composer](https://getcomposer.org) for dependency management and [gulp](http://gulpjs.com) for asset compilation. If you are cloning this repository for local development, you will need to install dependencies and compile assets as follows:

#### Install dependencies

1. [Install](https://getcomposer.org/doc/00-intro.md) Composer.
2. From the Pressbooks plugin directory, e.g. `/wp-content/plugins/pressbooks`, run the Composer install command: `php composer.phar install` or `composer install`

#### Compile assets

1. Install [Node.js](https://nodejs.org/) 0.12.x and npm.
2. Install [gulp](http://gulpjs.com) and [Bower](http://bower.io): `npm install -g gulp && npm install -g bower`
3. Install asset compilation tools: `npm install`
4. Install dependencies via Bower: `bower install`.
5. From the Pressbooks plugin directory, e.g. `/wp-content/plugins/pressbooks`, run gulp to generate assets: `gulp` (or `gulp --production`)

### Reporting Bugs

#### Before Submitting A Bug Report

* **Check the [debugging guide](https://github.com/pressbooks/pressbooks/wiki/debugging).** You might be able to find the cause of the problem and fix things yourself. Most importantly, check if you can reproduce the problem [in the latest version of Pressbooks](http://wordpress.org/plugins/pressbooks/) running on the [latest version of WordPress](http://codex.wordpress.org/Upgrading_WordPress) and if the problem happens with [all other plugins deactivated at the network level](http://codex.wordpress.org/Multisite_Network_Administration#Plugins).
* **Check the [FAQs on the wiki](https://github.com/pressbooks/pressbooks/wiki/FAQ)** for a list of common questions and problems.
* **Perform a [cursory search](https://github.com/issues?q=+is%3Aissue+repo%3Apressbooks%2Fpressbooks)** to see if the problem has already been reported. If it has, add a comment to the existing issue instead of opening a new one.

#### How Do I Submit A (Good) Bug Report?

Bugs are tracked as [GitHub issues](https://guides.github.com/features/issues/). Create an issue and provide the following information.

Explain the problem and include additional details to help maintainers reproduce the problem:

* **Use a clear and descriptive title** for the issue to identify the problem.
* **Describe the exact steps which reproduce the problem** in as many details as possible.
* **Provide specific examples to demonstrate the steps**. Include links to files or GitHub projects, or copy/pasteable snippets, which you use in those examples. If you're providing snippets in the issue, use [Markdown code blocks](https://help.github.com/articles/markdown-basics/#multiple-lines).
* **Describe the behavior you observed after following the steps** and point out what exactly is the problem with that behavior.
* **Explain which behavior you expected to see instead and why.**
* **If the problem is related to exporting**, attach (if possible) an export file which demonstrates the problem.

Provide more context by answering these questions:

* **Can you reproduce the problem with [all other plugins deactivated](http://codex.wordpress.org/Multisite_Network_Administration#Plugins)?**
* Are you experiencing this issue with one of our built-in root or book themes, or with your own custom root or book theme?
* **Did the problem start happening recently** (e.g. after updating to a new version of Pressbooks) or was this always a problem?
* If the problem started happening recently, **can you reproduce the problem in an older version of Pressbooks?** What's the most recent version in which the problem doesn't happen? You can download older versions of Pressbooks on [the plugin page](https://wordpress.org/plugins/pressbooks/developers/).
* **Can you reliably reproduce the issue?** If not, provide details about how often the problem happens and under which conditions it normally happens.

Include details about your configuration and environment:

* **Which versions of Pressbooks and WordPress are you using?** You can get the version of Pressbooks from the readme.txt file in the Pressbooks plugin (usually located at `http://<yourdomain.tld>/wp-content/plugins/pressbooks/readme.txt`) and the version of WordPress from the readme.html file in the WordPress root directory (usually located at `http://<yourdomain.tld>/readme.html`).
* **Which root and/or book theme(s) are you using?**
* **What server software (e.g. Apache, Nginx) and PHP version are being used to host your Pressbooks instance**? You can usually get your PHP version from your web host, or by using the [phpversion()](http://php.net/manual/en/function.phpversion.php) function.

### Suggesting Enhancements

This section guides you through submitting an enhancement suggestion for Pressbooks, including completely new features and minor improvements to existing functionality. Following these guidelines helps maintainers and the community understand your suggestion and find related suggestions.

Before creating enhancement suggestions, please check [this list](#before-submitting-an-enhancement-suggestion) as you might find out that you don't need to create one. When you are creating an enhancement suggestion, please [include as many details as possible](#how-do-i-submit-a-good-enhancement-suggestion).

#### Before Submitting An Enhancement Suggestion

* **Perform a [search](https://wordpress.org/plugins/search.php?q=Pressbooks)** for Pressbooks-specific WordPress plugins. Your feature may already be available in one of these.
* **Perform a [search](https://wordpress.org/plugins/)** for general-purpose WordPress plugins. Your feature may already be available in one of these.
* **Perform a [cursory search](https://github.com/issues?q=+is%3Aissue+repo%3Apressbooks%2Fpressbooks)** to see if the enhancement has already been suggested. If it has, add a comment to the existing issue instead of opening a new one.

#### How Do I Submit A (Good) Enhancement Suggestion?

Enhancement suggestions are tracked as [GitHub issues](https://guides.github.com/features/issues/). Create an issue and provide the following information:

* **Use a clear and descriptive title** for the issue to identify the suggestion.
* **Provide a step-by-step description of the suggested enhancement** in as many details as possible.
* **Provide specific examples to demonstrate the steps**. Include copy/pasteable snippets which you use in those examples, as [Markdown code blocks](https://help.github.com/articles/markdown-basics/#multiple-lines).
* **Describe the current behavior** and **explain which behavior you expected to see instead** and why.
* **Explain why this enhancement would be useful** to most Pressbooks users and isn't something that can or should be implemented as a standalone plugin.
* **Specify which versions of Pressbooks and WordPress you're using.** You can get the version of Pressbooks from the readme.txt file in the Pressbooks plugin (usually located at `http://<yourdomain.tld>/wp-content/plugins/pressbooks/readme.txt`) and the version of WordPress from the readme.html file in the WordPress root directory (usually located at `http://<yourdomain.tld>/readme.html`).
* **Specify which root and/or book theme(s) you're using.**

### Your First Code Contribution

If you'd like to get involved, we suggest you take a look at `beginner` or `help-wanted` issues:

* [Beginner issues][beginner] - issues which should only require a few lines of code, and a test or two.
* [Help wanted issues][help-wanted] - issues which should be a bit more involved than `beginner` issues.

Both issue lists are sorted by total number of comments. While not perfect, number of comments is a reasonable proxy for impact a given change will have.

### Pull Requests

* Where relevant, please include screenshots in your pull request.
* Please follow our [Code Styleguide](#code-styleguide) in writing your new code.
* Please document your new code as stipulated in our [Documentation Styleguide](#documentation-styleguide).

**NB:** We are making an effort to expand [unit testing](https://github.com/pressbooks/pressbooks/wiki/Unit-Testing) in Pressbooks. As such, we ask that any pull requests that add new functions include corresponding tests for those functions. If you submit a pull request which does not do this, you *will* be asked to revise the pull requests to add tests.

## Styleguides

We are in the process of creating comprehensive style guides for [code](#code-styleguide), [documentation](#documentation-styleguide) and [Git commit messages](#git-commit-messages).

### Code Styleguide

Our code styleguide can be found [here][docs/coding-standards.txt].

### Documentation Styleguide

Documentation of PHP functions should adhere to the [PHPDoc](https://en.wikipedia.org/wiki/PHPDoc) format.

### Git Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally
* When only changing documentation, include `[ci skip]` in the commit description

## Additional Notes

### Issue and Pull Request Labels

This section lists the labels we use to help us track and manage issues and pull requests.

[GitHub search](https://help.github.com/articles/searching-issues/) makes it easy to use labels for finding groups of issues or pull requests you're interested in. For example, you might be interested in [open issues across `pressbooks/pressbooks` which are labeled as bugs, but still need to be reliably reproduced](https://github.com/issues?utf8=%E2%9C%93&q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Abug+label%3Aneeds-reproduction) or perhaps [open pull requests in `pressbooks/pressbooks` which haven't been reviewed yet](https://github.com/issues?utf8=%E2%9C%93&q=is%3Aopen+is%3Apr+repo%3Apressbooks%2Fpressbooks+label%3Aneeds-review). To help you find issues and pull requests, each label is listed with search links for finding open items with that label in `pressbooks/pressbooks`. We  encourage you to read about [other search filters](https://help.github.com/articles/searching-issues/) which will help you write more focused queries.

The labels are loosely grouped by their purpose, but it's not required that every issue have a label from every group or that an issue can't have more than one label from the same group.

Please open an issue on `pressbooks/pressbooks` if you have suggestions for new labels.

#### Type of Issue and Issue State

| Label name | `pressbooks/pressbooks` | Description |
| --- | --- | --- |
| `enhancement` | [search][search-pressbooks-repo-label-enhancement] | Feature requests. |
| `bug` | [search][search-pressbooks-repo-label-bug] | Confirmed bugs or reports that are very likely to be bugs. |
| `priority` | [search][search-pressbooks-repo-label-priority] | Issues which the Pressbooks team has identified as priorities. |
| `question` | [search][search-pressbooks-repo-label-question] | Questions more than bug reports or feature requests (e.g. how do I do X). |
| `feedback` | [search][search-pressbooks-repo-label-feedback] | General feedback more than bug reports or feature requests. |
| `help-wanted` | [search][search-pressbooks-repo-label-help-wanted] | The Pressbooks team would appreciate help from the community in resolving these issues. |
| `beginner` | [search][search-pressbooks-repo-label-beginner] | Less complex issues which would be good first issues to work on for users who want to contribute to Pressbooks. |
| `more-information-needed` | [search][search-pressbooks-repo-label-more-information-needed] | More information needs to be collected about these problems or feature requests (e.g. steps to reproduce). |
| `needs-reproduction` | [search][search-pressbooks-repo-label-needs-reproduction] | Likely bugs, but haven't been reliably reproduced. |
| `blocked` | [search][search-pressbooks-repo-label-blocked] | Issues blocked on other issues. |
| `duplicate` | [search][search-pressbooks-repo-label-duplicate] | Issues which are duplicates of other issues, i.e. they have been reported before. |
| `wontfix` | [search][search-pressbooks-repo-label-wontfix] | The Pressbooks team has decided not to fix these issues for now, either because they're working as intended or for some other reason. |
| `invalid` | [search][search-pressbooks-repo-label-invalid] | Issues which aren't valid (e.g. user errors). |

#### Pull Request Labels

| Label name | `pressbooks/pressbooks` | Description
| --- | --- | --- | --- |
| `work-in-progress` | [search][search-pressbooks-repo-label-work-in-progress] | Pull requests which are still being worked on, more changes will follow. |
| `needs-review` | [search][search-pressbooks-repo-label-needs-review] | Pull requests which need code review, and approval from maintainers or the Pressbooks team. |
| `under-review` | [search][search-pressbooks-repo-label-under-review] | Pull requests being reviewed by maintainers or the Pressbooks team. |
| `requires-changes` | [search][search-pressbooks-repo-label-requires-changes] | Pull requests which need to be updated based on review comments and then reviewed again. |
| `needs-testing` | [search][search-pressbooks-repo-label-needs-testing] | Pull requests which need manual testing. |

[search-pressbooks-repo-label-enhancement]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Aenhancement
[search-pressbooks-repo-label-bug]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Abug
[search-pressbooks-repo-label-question]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Aquestion
[search-pressbooks-repo-label-priority]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Apriority
[search-pressbooks-repo-label-feedback]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Afeedback
[search-pressbooks-repo-label-help-wanted]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Ahelp-wanted
[search-pressbooks-repo-label-beginner]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Abeginner
[search-pressbooks-repo-label-more-information-needed]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Amore-information-needed
[search-pressbooks-repo-label-needs-reproduction]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Aneeds-reproduction
[search-pressbooks-repo-label-blocked]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Ablocked
[search-pressbooks-repo-label-duplicate]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Aduplicate
[search-pressbooks-repo-label-wontfix]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Awontfix
[search-pressbooks-repo-label-invalid]: https://github.com/issues?q=is%3Aopen+is%3Aissue+repo%3Apressbooks%2Fpressbooks+label%3Ainvalid
[search-pressbooks-repo-label-work-in-progress]: https://github.com/pulls?q=is%3Aopen+is%3Apr+repo%3Apressbooks%2Fpressbooks+label%3Awork-in-progress
[search-pressbooks-repo-label-needs-review]: https://github.com/pulls?q=is%3Aopen+is%3Apr+repo%3Apressbooks%2Fpressbooks+label%3Aneeds-review
[search-pressbooks-repo-label-under-review]: https://github.com/pulls?q=is%3Aopen+is%3Apr+repo%3Apressbooks%2Fpressbooks+label%3Aunder-review
[search-pressbooks-repo-label-requires-changes]: https://github.com/pulls?q=is%3Aopen+is%3Apr+repo%3Apressbooks%2Fpressbooks+label%3Arequires-changes
[search-pressbooks-repo-label-needs-testing]: https://github.com/pulls?q=is%3Aopen+is%3Apr+repo%3Apressbooks%2Fpressbooks+label%3Aneeds-testing

[beginner]:https://github.com/issues?utf8=%E2%9C%93&q=is%3Aopen+is%3Aissue+label%3Abeginner+label%3Ahelp-wanted+user%3Apressbooks+sort%3Acomments-desc
[help-wanted]:https://github.com/issues?q=is%3Aopen+is%3Aissue+label%3Ahelp-wanted+user%3Apressbooks+sort%3Acomments-desc
