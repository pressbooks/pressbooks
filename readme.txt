=== Pressbooks ===
Contributors: Pressbooks <code@pressbooks.com>
Donate link: https://opencollective.com/pressbooks
Tags: ebooks, publishing, webbooks
Requires at least: 4.9.5
Tested up to: 4.9.5
Requires PHP: 7.0
Stable tag: 5.2.1
License: GPL v3.0 or later
License URI: https://github.com/pressbooks/pressbooks/blob/master/LICENSE.md

Pressbooks is an open source book publishing tool built on a WordPress multisite platform.

== Description ==
Pressbooks is an open source book publishing tool built on a WordPress multisite platform. Pressbooks outputs books in multiple formats, including PDF, EPUB, MOBI, web, and a variety of XML flavours, using a theming/templating system, driven by CSS. For more information, visit https://pressbooks.org.

== Installation ==
For installation instructions, visit [docs.pressbooks.org/installation](https://docs.pressbooks.org/installation).

== Frequently Asked Questions ==
TK.

== Changelog ==
= 5.3.0 =
**Minor Changes**
- Disallow certain taxonomies based on a blacklist instead of a whitelist (#1095): #1172
- Update Isotope to 3.0.6: #1177
- Update TinyMCE Table plugin to 4.7.10: #1173

**Patches**
- Prevent slug collisions in XHTML and HTMLBook outputs (#1174): #1175

= 5.2.1 =
**Patches**

- Patch [select2](https://github.com/select2/select2) and [Custom Metadata Manager](https://github.com/Automattic/custom-metadata) to save multiselect data in the specified order: [#1167](https://github.com/pressbooks/pressbooks/pull/1167)
- Fix an edge case where invalid author data would persist following upgrade to Pressbooks 5: [#1168](https://github.com/pressbooks/pressbooks/pull/1168)
- Fix focus style for admin menu icons: [#1169](https://github.com/pressbooks/pressbooks/pull/1169)
- Allow super admins to access network theme and plugin menus directly: [#1169](https://github.com/pressbooks/pressbooks/pull/1169)
- Remove unit test that was failing due to inaccessible Creative Commons API: [#1171](https://github.com/pressbooks/pressbooks/pull/1171)

== Upgrade Notice ==
= 5.3.0 =

Pressbooks 5.3.0 requires [WordPress 4.9.4](https://wordpress.org/news/2018/04/wordpress-4-9-5-security-and-maintenance-release/).
Pressbooks 5.3.0 requires [McLuhan >= 2.3.0](https://github.com/pressbooks/pressbooks-book/).
