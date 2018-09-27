=== Pressbooks ===
Contributors: Pressbooks <code@pressbooks.com>
Donate link: https://opencollective.com/pressbooks
Tags: ebooks, publishing, webbooks
Requires at least: 4.9.8
Tested up to: 4.9.8
Requires PHP: 7.1
Stable tag: 5.5.2
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
= 5.6.0 =

* Pressbooks 5.6.0 requires PHP >= 7.1.
* Pressbooks 5.6.0 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.0 requires [McLuhan >= 2.6.0](https://github.com/pressbooks/pressbooks-book/).

**Minor Changes**

* Update TinyMCE table editor plugin to 4.8.3: [#1354](https://github.com/pressbooks/pressbooks/pull/1354)
* Show advanced editor toolbars by default: [#1352](https://github.com/pressbooks/pressbooks/pull/1352)
* Move the Contributors page under the Book Info menu: [#1351](https://github.com/pressbooks/pressbooks/pull/1351)
* Allow a new title to be entered when cloning a book: [#1348](https://github.com/pressbooks/pressbooks/pull/1348)
* Add support for glossary term management and display (props [@alex-418](https://github.com/alex-418) and [@bdolor](https://github.com/bdolor)): [#1320](https://github.com/pressbooks/pressbooks/pull/1320), [#1350](https://github.com/pressbooks/pressbooks/pull/1350)

**Patches**

* Hide the "View" link when editing taxonomies (props [@colomet](https://github.com/colomet) for the idea): [#1351](https://github.com/pressbooks/pressbooks/pull/1351)

= 5.5.2 =

* Pressbooks 5.5.2 requires PHP >= 7.1.
* Pressbooks 5.5.2 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.5.2 requires [McLuhan >= 2.5.1](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Scan full book contents for anchors in link insertion tool: [#1345](https://github.com/pressbooks/pressbooks/pull/1345)
* Fix duplication of anchors in link insertion tool: [#1345](https://github.com/pressbooks/pressbooks/pull/#1345)
* Ensure processing of absolute internal links in XHTML and HTMLBook modules ([#1347](https://github.com/pressbooks/pressbooks/issues/1347)): [#1353](https://github.com/pressbooks/pressbooks/pull/1353)
* Eliminate race condition when rebuilding webbook stylesheets: [#1355](https://github.com/pressbooks/pressbooks/pull/1355)
* Disable related videos in YouTube OEmbeds ([#1358](https://github.com/pressbooks/pressbooks/issues/1358)): [#1359](https://github.com/pressbooks/pressbooks/issues/1359)
* Handle Dillard (Plain) 1.x to 2.0 upgrade: [#1361](https://github.com/pressbooks/pressbooks/pull/1361)
* Hide "Welcome to WordPress" everywhere ([#1364](https://github.com/pressbooks/pressbooks/issues/#1364)): [#1365](https://github.com/pressbooks/pressbooks/pull/1365)
* Use a file that is guaranteed to remain available for HTMLBook validation: [#1366](https://github.com/pressbooks/pressbooks/issues/1366)
* Always use the filtered stylesheet directory in `\Pressbooks\Styles->customize()`: [#1372](https://github.com/pressbooks/pressbooks/pull/1372)

= 5.5.1 =

* Pressbooks 5.5.1 requires PHP >= 7.1.
* Pressbooks 5.5.1 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.5.1 requires [McLuhan >= 2.5.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Fixed an issue where cover generator stylesheets were missing from the release package: [#1349](https://github.com/pressbooks/pressbooks/pull/1349)

= 5.5.0 =

* Pressbooks 5.5.0 requires PHP >= 7.1.
* Pressbooks 5.5.0 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.5.0 requires [McLuhan >= 2.5.0](https://github.com/pressbooks/pressbooks-book/).

**Minor Changes**

* Increase minimum PHP to 7.1 ([#1231](https://github.com/pressbooks/pressbooks/issues/1231)): [15f946b](https://github.com/pressbooks/pressbooks/commit/15f946b1a976bf0082d50bbcc33047f2a9be0679)
* Add Cover Generator to core: [#1257](https://github.com/pressbooks/pressbooks/pull/1257)
* Update Thema codes to 1.3.0, use localized labels ([#1265](https://github.com/pressbooks/pressbooks/issues/1265), [#1302](https://github.com/pressbooks/pressbooks/issues/1302)): [#1266](https://github.com/pressbooks/pressbooks/pull/1266)
* Improve license output markup (props [@bdolor](https://github.com/bdolor)): [#1268](https://github.com/pressbooks/pressbooks/pull/1268)
* Add support for cloning registered media metadata ([#1280](https://github.com/pressbooks/pressbooks/issues/1280)): [#1337](https://github.com/pressbooks/pressbooks/issues/1337)
* Add support for all media attachments in cloning operations ([#1281](https://github.com/pressbooks/pressbooks/issues/1281)): [#1334](https://github.com/pressbooks/pressbooks/issues/1334), [#1339](https://github.com/pressbooks/pressbooks/issues/1339)
* Improve cloner accuracy ([#1283](https://github.com/pressbooks/pressbooks/issues/1283)): [#1312](https://github.com/pressbooks/pressbooks/issues/1312)
* Add attribution to images (props [@alex-418](https://github.com/alex-418) and [@bdolor](https://github.com/bdolor)): [#1287](https://github.com/pressbooks/pressbooks/issues/1287), [#1299](https://github.com/pressbooks/pressbooks/issues/1299), [#1321](https://github.com/pressbooks/pressbooks/issues/1321), [#1343](https://github.com/pressbooks/pressbooks/issues/1343)
* Enable TablePress for EPUB/MOBI: [#1293](https://github.com/pressbooks/pressbooks/pull/1293)
* Remove the "Try Gutenberg" panel ([#1296](https://github.com/pressbooks/pressbooks/issues/1296)): [#1308](https://github.com/pressbooks/pressbooks/issues/1308)
* Add new shortcodes to facilitate authoring and import ([#1297](https://github.com/pressbooks/pressbooks/issues/1297)): [#1301](https://github.com/pressbooks/pressbooks/issues/1301), [#1325](https://github.com/pressbooks/pressbooks/issues/1325), [#1336](https://github.com/pressbooks/pressbooks/issues/1336)
* Add the [PagedJS polyfill](https://gitlab.pagedmedia.org/tools/pagedjs) to the PDF debug view: [#1307](https://github.com/pressbooks/pressbooks/issues/1307)
* Improve `register_meta()` usage with object subtypes: [#1309](https://github.com/pressbooks/pressbooks/issues/1309)
* Update TinyMCE to 4.8.2: [#1319](https://github.com/pressbooks/pressbooks/issues/1319)
* Add filters to support SVG in EPUB (props [@lukaiser](https://github.com/lukaiser)): [#1322](https://github.com/pressbooks/pressbooks/issues/1322)
* Add support for QuickLaTeX rendering within TablePress tables (props [@steelwagstaff](https://github.com/steelwagstaff)): [#1340](https://github.com/pressbooks/pressbooks/issues/1340)

**Patches**

* Fix internal links when cloning ([#1279](https://github.com/pressbooks/pressbooks/issues/1279)): [#1310](https://github.com/pressbooks/pressbooks/issues/1310), [#1324](https://github.com/pressbooks/pressbooks/issues/1324)
* Ensure that contributor taxonomies are always registered when needed: [#1300](https://github.com/pressbooks/pressbooks/pull/1300)
* Fix CSS overwriting during simultaneous digital/print PDF export ([#1313](https://github.com/pressbooks/pressbooks/issues/1313)): [#1314](https://github.com/pressbooks/pressbooks/issues/1314)
* Remove multilevel TOC processing for parts: [#1315](https://github.com/pressbooks/pressbooks/issues/1315)
* Fix undefined index in `inc/shortcodes/attributions/class-attachments.php` ([#1316](https://github.com/pressbooks/pressbooks/issues/1316)): [a304925](https://github.com/pressbooks/pressbooks/commit/a30492540e9abdb90a99c2fdf91139969c660166)
* Fix broken internal links in EPUB/MOBI when front matter is automatically reordered ([#1327](https://github.com/pressbooks/pressbooks/issues/1327)): [#1328](https://github.com/pressbooks/pressbooks/issues/1328)
* Fix confusing interaction between browser navigation and Custom Styles page: [#1329](https://github.com/pressbooks/pressbooks/issues/1329)
* Improve session handling to prevent session locking: [#1335](https://github.com/pressbooks/pressbooks/issues/1335)

== Upgrade Notice ==
= 5.6.0 =

* Pressbooks 5.6.0 requires PHP >= 7.1.
* Pressbooks 5.6.0 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.0 requires [McLuhan >= 2.6.0](https://github.com/pressbooks/pressbooks-book/).
