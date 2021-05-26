=== Pressbooks ===
Contributors: Pressbooks <code@pressbooks.com>
Tags: ebooks, publishing, webbooks
Requires at least: 5.7.2
Tested up to: 5.7.2
Requires PHP: 7.3
Stable tag: 5.21.0
License: GPL v3.0 or later
License URI: https://github.com/pressbooks/pressbooks/blob/master/LICENSE.md

Pressbooks is an open source book publishing tool built on a WordPress multisite platform.

== Description ==
Pressbooks is an open source book publishing tool built on a WordPress multisite platform. Pressbooks outputs books in multiple formats, including PDF, EPUB, MOBI, web, and a variety of XML flavours, using a theming/templating system, driven by CSS. For more information, visit https://pressbooks.org.

== Installation ==
For installation instructions, visit [docs.pressbooks.org/installation](https://docs.pressbooks.org/installation).

== Changelog ==

= 5.21.0 =

* See: https://github.com/pressbooks/pressbooks/releases/tag/5.21.0
* Full release history available at: https://github.com/pressbooks/pressbooks/releases

== Upgrade Notice ==

= 5.21.0=
* Pressbooks 5.21.0 requires [WordPress 5.7.2](https://wordpress.org/support/wordpress-version/version-5-7-2/)

= 5.20.1=
* Pressbooks 5.20.1 requires [WordPress 5.6.2](https://wordpress.org/support/wordpress-version/version-5-6-2/)

= 5.18.0=

* Pressbooks 5.18.0 requires PHP >= 7.3
* Pressbooks 5.18.0 requires [WordPress 5.5.3](https://wordpress.org/support/wordpress-version/version-5-5-3/)

= 5.16.0 =

* If you are using the plugin (Lord of the Files)[https://wordpress.org/plugins/blob-mimes/] version <=1.0.0, this upgrade will break your application.
To fix this, you would need to update Lord of the files plugin to at least 1.1.0.

= 5.15.1 =

* Pressbooks 5.15.1 requires PHP >= 7.1.
* Pressbooks 5.15.1 requires [WordPress 5.4](https://wordpress.org/support/wordpress-version/version-5-4/)
* Pressbooks 5.15.1 requires [McLuhan >= 2.10.2](https://github.com/pressbooks/pressbooks-book/)
* Pressbooks 5.15.1 supports integration with [Sentry](https://sentry.io/)
  * [OPTIONAL] If you wish to integrate [Sentry](https://sentry.io/), add the following keys and its value in your environment variable: SENTRY_KEY, SENTRY_ORGANIZATION, SENTRY_PROJECT, WP_ENV
  * The SENTRY_* values can be found in your sentry account
  * The WP_ENV can be any value. ex: development, staging, production
