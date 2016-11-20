---
layout: page
title: Development Workflow
permalink: /development-workflow/
---

These are our branches:

*   **[master][1]** is our stable branch, and you'll find it running on Pressbooks.com (and reflected in our [latest release][2]).
*   **[dev][3]** is a general-purpose work-in-progress branch. It is merged with `master` when a new release is forthcoming.
*   **[hotfix][4]** is for emergency patches. It exists solely to avoid conflicts with `dev`.
*   Any other branches you find are for feature development prior to merging into `dev`. Use at your own risk.
*   [Tags][5] represent releases, but if you are downloading a release for installation, you should download the package from [releases][6] as opposed to the source code.

 [1]: https://github.com/pressbooks/pressbooks/tree/master
 [2]: https://github.com/pressbooks/pressbooks/releases/latest/
 [3]: https://github.com/pressbooks/pressbooks/tree/dev
 [4]: https://github.com/pressbooks/pressbooks/tree/hotfix
 [5]: https://github.com/pressbooks/pressbooks/tags
 [6]: https://github.com/pressbooks/pressbooks/releases/
