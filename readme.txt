=== Pressbooks ===
Contributors: Pressbooks <code@pressbooks.com>
Donate link: https://opencollective.com/pressbooks
Tags: ebooks, publishing, webbooks
Requires at least: 5.1.1
Tested up to: 5.1.1
Requires PHP: 7.1
Stable tag: 5.7.1
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
= 5.7.1 =

* Pressbooks 5.7.1 requires PHP >= 7.1
* Pressbooks 5.7.1 requires [WordPress 5.1.1](https://wordpress.org/news/2019/03/wordpress-5-1-1-security-and-maintenance-release/)
* Pressbooks 5.7.1 requires [McLuhan >= 2.8.2](https://github.com/pressbooks/pressbooks-book/)

**Patches**

* Fix user locale: 91663c5, f3e046d, b1df762
* Fix progress bar in Microsoft Edge ([#1663](https://github.com/pressbooks/pressbooks/issues/1663)): [#1664](https://github.com/pressbooks/pressbooks/pull/1664)
* Fix a frequently seen SCSS Compile Errror: [#1655](https://github.com/pressbooks/pressbooks/pull/1655)
* Fix fully qualified URLs are not converted to internal links when exporting epubs on subdomain installs: [#1644](https://github.com/pressbooks/pressbooks/pull/1644)
* Fix session id is too long or contains illegal characters: [#1643](https://github.com/pressbooks/pressbooks/pull/1643)

= 5.7.0 =

* Pressbooks 5.7.0 requires PHP >= 7.1
* Pressbooks 5.7.0 requires [WordPress 5.1.1](https://wordpress.org/news/2019/03/wordpress-5-1-1-security-and-maintenance-release/)
* Pressbooks 5.7.0 requires [McLuhan >= 2.8.0](https://github.com/pressbooks/pressbooks-book/)

**Minor Changes**

See [5.7.0 Milestone](https://github.com/pressbooks/pressbooks/milestone/60?closed=1) for detailed GitHub activity log.

#### Obvious:

* New export page with bulk actions, pinned exports, improved accessibility ([#1525](https://github.com/pressbooks/pressbooks/issues/1525))
* Display a progress bar on Import, Export, Cloning and Cover Generator pages ([#1282](https://github.com/pressbooks/pressbooks/issues/1282)) ([#1599](https://github.com/pressbooks/pressbooks/issues/1599))
* Support cloning of H5P activities ([#1529](https://github.com/pressbooks/pressbooks/issues/1529))
  * Requires latest version of [H5P plugin](https://wordpress.org/plugins/h5p/), currently 1.13.1
* Improved Glossary Tooltips ([#1526](https://github.com/pressbooks/pressbooks/issues/1526)) ([#1528](https://github.com/pressbooks/pressbooks/issues/1528)) ([#1546](https://github.com/pressbooks/pressbooks/issues/1546))
* Improved Source Comparison Tool ([#1484](https://github.com/pressbooks/pressbooks/issues/1484))

#### Not so obvious:

* Rename Austen theme (pressbooks-austentwo → pressbooks-austen) for PressbooksEDU customers: [#1607](https://github.com/pressbooks/pressbooks/pull/1607)
* Fix stripped anchors in xhtml/epub ([#1602](https://github.com/pressbooks/pressbooks/issues/1602)): [#1626](https://github.com/pressbooks/pressbooks/pull/1626)
* Fix cloning empty raw content ie. auto-generated glossary ([#1622](https://github.com/pressbooks/pressbooks/issues/1622)): [#1625](https://github.com/pressbooks/pressbooks/pull/1625)
* Network level "Enable Cloning" setting should affect import types: [#1619](https://github.com/pressbooks/pressbooks/pull/1619)
* Support localization of Blade templates: [#1616](https://github.com/pressbooks/pressbooks/pull/1616)
* Fix E_NOTICE in user catalog: [#1614](https://github.com/pressbooks/pressbooks/pull/1614)
* Add support for WordPress 5.1: [#1608](https://github.com/pressbooks/pressbooks/pull/1608)
* Add Google Maps to iframe whitelist ([#1570](https://github.com/pressbooks/pressbooks/issues/1570))
* Diagnostics: add method for refreshing webbook stylesheet ([#1565](https://github.com/pressbooks/pressbooks/issues/1565))
* Adjust Pressbooks credit text ([#1554](https://github.com/pressbooks/pressbooks/issues/1554))
* Support Knight Labs OEmbed Endpoint ([#1514](https://github.com/pressbooks/pressbooks/issues/1514))
* Unless chapter is licensed differently, it should display the book license statement ([#1508](https://github.com/pressbooks/pressbooks/issues/1508))
* Network managers should be able to manage Google Analytics ([#1485](https://github.com/pressbooks/pressbooks/issues/1485))

**Patches**

* Update dependencies to [latest versions](https://github.com/pressbooks/pressbooks/pulls?utf8=%E2%9C%93&q=is%3Apr+author%3Aapp%2Fdependabot+milestone%3A5.7.0):
  * Update [pagedjs](https://pagedmedia.org/) to 0.1.31: [#1620](https://github.com/pressbooks/pressbooks/pull/1620)
  * Update [wp-admin-colors](https://www.npmjs.com/package/wp-admin-colors) to to 5.1.0: [#1620](https://github.com/pressbooks/pressbooks/pull/1620)
  * Update [YahnisElsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) to 4.5.1: [#1618](https://github.com/pressbooks/pressbooks/pull/1618)
  * Update [pressbooks-build-tools](https://github.com/pressbooks/pressbooks-build-tools) to 1.3.3: [#1603](https://github.com/pressbooks/pressbooks/pull/1603)
  * Update [popper.js](https://github.com/FezVrasta/popper.js) to 1.14.7: [#1583](https://github.com/pressbooks/pressbooks/pull/1583)
  * Update [TinyMCE](https://www.npmjs.com/package/tinymce) to 4.9.3: [#1582](https://github.com/pressbooks/pressbooks/pull/1582)
  * Update [wpapi](https://github.com/wp-api/node-wpapi) to 1.2.1: [#1568](https://github.com/pressbooks/pressbooks/pull/1568)
  * Update [jenssegers/imagehash](https://github.com/jenssegers/imagehash) to 0.6.0: [#1550](https://github.com/pressbooks/pressbooks/pull/1550)
  * Update [masterminds/html5](https://github.com/Masterminds/html5-php) to 2.5.0: [#1536](https://github.com/pressbooks/pressbooks/pull/1536)
  * Update [sshpk](https://github.com/joyent/node-sshpk) to 1.16.0: [#1533](https://github.com/pressbooks/pressbooks/pull/1533)
  * Update [debug](https://github.com/visionmedia/debug) to 2.6.9: [#1531](https://github.com/pressbooks/pressbooks/pull/1531)
*
= 5.6.5 =

* Pressbooks 5.6.5 requires PHP >= 7.1.
* Pressbooks 5.6.5 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.5 requires [McLuhan >= 2.7.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Prevent unnecessary alert while saving parts ([#1555](pressbooks/pressbooks/issues/1555), props [@josieg](https://github.com/josieg) for the bug report): [#1562](https://github.com/pressbooks/pressbooks/pull/1562)
* Add support for EPUBCheck 4.1 (props [@bdolor](https://github.com/bdolor)): [#1524](https://github.com/pressbooks/pressbooks/pull/1524), [#1556](https://github.com/pressbooks/pressbooks/pull/1556)
* Prevent SCSS compilation during AJAX calls: [#1483](https://github.com/pressbooks/pressbooks/pull/1483)

= 5.6.4 =

* Pressbooks 5.6.4 requires PHP >= 7.1.
* Pressbooks 5.6.4 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.4 requires [McLuhan >= 2.6.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Handle missing legacy images: [#1542](https://github.com/pressbooks/pressbooks/pull/1542)

= 5.6.3 =

* Pressbooks 5.6.3 requires PHP >= 7.1.
* Pressbooks 5.6.3 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.3 requires [McLuhan >= 2.6.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Ensure that uninstalled themes are excluded from allowed theme list: [#1507](https://github.com/pressbooks/pressbooks/pull/1507)
* Clarify copy on import content selection screen ([#1504](https://github.com/pressbooks/pressbooks/issues/1504)): [#1506](https://github.com/pressbooks/pressbooks/pull/1506)
* Delete hidden anchor from Word imports ([#1473](https://github.com/pressbooks/pressbooks/issues/1473)): [#1502](https://github.com/pressbooks/pressbooks/pull/1502)
* Ensure that book language is used for part/chapter labels ([#1486](https://github.com/pressbooks/pressbooks/issues/1486)): [#1501](https://github.com/pressbooks/pressbooks/pull/1501)
* Update [johnbillion/extended-cpts](https://packagist.org/packages/johnbillion/extended-cpts) to 4.2.3: [#1499](https://github.com/pressbooks/pressbooks/pull/1499), [#1503](https://github.com/pressbooks/pressbooks/pull/1503)
* Remove fancy quotes around media shortcode attributes ([#1493](https://github.com/pressbooks/pressbooks/issues/1493)): [#1498](https://github.com/pressbooks/pressbooks/pull/1498)
* Fix conflict between footnote and media shortcodes ([#1472](https://github.com/pressbooks/pressbooks/issues/1472)): [#1497](https://github.com/pressbooks/pressbooks/pull/1497)
* Fix import of chapters with more than one URL from Word ([#1475](https://github.com/pressbooks/pressbooks/issues/1475)): [#1495](https://github.com/pressbooks/pressbooks/pull/1495)
* Remove HTML tags from running content title strings ([#1491](https://github.com/pressbooks/pressbooks/issues/1491)): [#1492](https://github.com/pressbooks/pressbooks/pull/1492), [#1496](https://github.com/pressbooks/pressbooks/pull/1496)
* Update [pagedjs](https://gitlab.pagedmedia.org/tools/pagedjs) to 0.1.28: [#1490](https://github.com/pressbooks/pressbooks/pull/1490)
* Handle locked themes in `Styles::updateWebBookStylesheet()` ([#1487](https://github.com/pressbooks/pressbooks/issues/1487)): [#1488](https://github.com/pressbooks/pressbooks/pull/1488)
* Use PNG to JPG conversion as failsafe in cover generator (props [@bdolor](https://github.com/bdolor)): [#1474](https://github.com/pressbooks/pressbooks/pull/1474)

= 5.6.2 =

* Pressbooks 5.6.2 requires PHP >= 7.1.
* Pressbooks 5.6.2 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.2 requires [McLuhan >= 2.6.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Escape quotes in glossary term content ([#1481](https://github.com/pressbooks/pressbooks/issues/1481), props [@pbstudent](https://github.com/pbstudent) for the bug report): [#1482](https://github.com/pressbooks/pressbooks/pull/1482)

= 5.6.1 =

* Pressbooks 5.6.1 requires PHP >= 7.1.
* Pressbooks 5.6.1 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.1 requires [McLuhan >= 2.6.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Improve sanitization of glossary term content: [#1480](https://github.com/pressbooks/pressbooks/pull/1480)
* Ignore deleted posts when importing from WXR: [#1471](https://github.com/pressbooks/pressbooks/pull/1471)

= 5.6.0 =

* Pressbooks 5.6.0 requires PHP >= 7.1.
* Pressbooks 5.6.0 requires [WordPress 4.9.8](https://wordpress.org/news/2018/08/wordpress-4-9-8-maintenance-release/).
* Pressbooks 5.6.0 requires [McLuhan >= 2.6.0](https://github.com/pressbooks/pressbooks-book/).

**Minor Changes**

* Update [pagedjs](https://gitlab.pagedmedia.org/tools/pagedjs) to 0.1.25: [#1469](https://github.com/pressbooks/pressbooks/pull/1469)
* Update [masterminds/html5](https://packagist.org/packages/masterminds/html5) to 2.4.0: [#1468](https://github.com/pressbooks/pressbooks/pull/1468)
* Add filters for default contact and help links: [#1464](https://github.com/pressbooks/pressbooks/pull/1464)
* Add subsection caching to `\Pressbooks\Book` class: [#1453](https://github.com/pressbooks/pressbooks/issues/1453)
* Add `print` class to (X)HTML source for print PDF ([#1437](https://github.com/pressbooks/pressbooks/issues/1437)): [#1454](https://github.com/pressbooks/pressbooks/pull/1454)
* Update path to Buckram components: [#1452](https://github.com/pressbooks/pressbooks/pull/1452)
* Add title attribute to section `<div>` elements: [#1441](https://github.com/pressbooks/pressbooks/pull/1441)
* Allow customization of part and chapter labels in exports: [#1440](https://github.com/pressbooks/pressbooks/pull/1440), [#1455](https://github.com/pressbooks/pressbooks/pull/1455)
* Update the [TinyMCE](https://www.npmjs.com/package/tinymce) table editor to 4.8.5: [#1439](https://github.com/pressbooks/pressbooks/pull/1439)
* Increase `max_execution_time` from 5 to 10 minutes for import, export, and clone operations: [#1431](https://github.com/pressbooks/pressbooks/pull/1431)
* Add Digital Object Identifier (DOI) support at book and section level, exposed via metadata API: [#1429](https://github.com/pressbooks/pressbooks/pull/1429), [#1436](https://github.com/pressbooks/pressbooks/pull/1436)
* Add `the_export_content` filter hook and `sanitize_webbook_content()` function: [#1422](https://github.com/pressbooks/pressbooks/pull/1422), [#1462](https://github.com/pressbooks/pressbooks/pull/1462)
* Update [johnbillion/extended-cpts](https://packagist.org/packages/johnbillion/extended-cpts) to 4.2.1: [#1410](https://github.com/pressbooks/pressbooks/pull/1410)
* Update [composer/installers](https://packagist.org/packages/composer/installers) to 1.6.0: [#1408](https://github.com/pressbooks/pressbooks/pull/1408)
* Update [jenssegers/imagehash](https://packagist.org/packages/jenssegers/imagehash) to 0.5.0: [#1407](https://github.com/pressbooks/pressbooks/pull/1407)
* Update [leafo/scssphp](https://packagist.org/packages/leafo/scssphp) to 0.7.7: [#1406](https://github.com/pressbooks/pressbooks/pull/1406)
* Add support for embedding [Knight Lab timelines](https://timeline.knightlab.com/): [#1400](https://github.com/pressbooks/pressbooks/pull/1400)
* Update [wp-admin-colors](https://www.npmjs.com/package/wp-admin-colors) to to 4.9.8: [#1398](https://github.com/pressbooks/pressbooks/pull/1398)
* Differentiate CC0 and public domain licenses ([#1331](https://github.com/pressbooks/pressbooks/issues/1331), props [@philbarker](https://github.com/philbarker) for the suggestion): [#1392](https://github.com/pressbooks/pressbooks/pull/1392), [#1399](https://github.com/pressbooks/pressbooks/pull/1399)
* Add Bengali, Kannada, Malayalam, Odia, and Telugu languages (props [@johnpeterm](https://github.com/johnpeterm) for the suggestion): [#1390](https://github.com/pressbooks/pressbooks/pull/1390)
* Add a link to the XHTML source preview to the diagnostics page: [#1378](https://github.com/pressbooks/pressbooks/pull/1378)
* Add a unique class to `<hr>` before footnotes: [#1377](https://github.com/pressbooks/pressbooks/pull/1377)
* Add support for testing [Gutenberg](https://wordpress.org/gutenberg) with Pressbooks, disabled by default: [#1373](https://github.com/pressbooks/pressbooks/pull/1373), [#1401](https://github.com/pressbooks/pressbooks/pull/1401), [#1451](https://github.com/pressbooks/pressbooks/pull/1451)
* Show the advanced editor toolbars by default: [#1352](https://github.com/pressbooks/pressbooks/pull/1352)
* Move the Contributors page under the Book Info menu: [#1351](https://github.com/pressbooks/pressbooks/pull/1351)
* Allow a new title to be entered when cloning a book: [#1348](https://github.com/pressbooks/pressbooks/pull/1348)
* Add support for glossary term management and display (props [@alex-418](https://github.com/alex-418) and [@bdolor](https://github.com/bdolor) of [BCcampus](https://github.com/BCcampus) for contributing the first version of this feature): [#1320](https://github.com/pressbooks/pressbooks/pull/1320), [#1350](https://github.com/pressbooks/pressbooks/pull/1350), [#1370](https://github.com/pressbooks/pressbooks/pull/1370), [#1382](https://github.com/pressbooks/pressbooks/pull/1382), [#1385](https://github.com/pressbooks/pressbooks/pull/1385), [#1420](https://github.com/pressbooks/pressbooks/pull/1420), [#1423](https://github.com/pressbooks/pressbooks/pull/1423), [#1426](https://github.com/pressbooks/pressbooks/pull/1426), [#1427](https://github.com/pressbooks/pressbooks/pull/1427), [#1428](https://github.com/pressbooks/pressbooks/pull/1428), [#1442](https://github.com/pressbooks/pressbooks/pull/1442), [#1449](https://github.com/pressbooks/pressbooks/pull/1449), [#1450](https://github.com/pressbooks/pressbooks/pull/1450), [#1477](https://github.com/pressbooks/pressbooks/pull/1477)

**Patches**

* Lower version numbers for cover generator dependencies to improve RHEL compatibility (props [@bdolor](https://github.com/bdolor)): [#1467](https://github.com/pressbooks/pressbooks/pull/1467)
* Redirect users to Organize page after clicking "Move to Trash": [#1466](https://github.com/pressbooks/pressbooks/pull/1466)
* Hide the "Part" prefix in EPUB exports when part and chapter numbering is disabled ([#1459](https://github.com/pressbooks/pressbooks/issues/1459)): [#1461](https://github.com/pressbooks/pressbooks/pull/1461)
* Fix issue where a numberless chapter at the beginning of a book can cause chapter numbering errors in subsequent chapters: [#1460](https://github.com/pressbooks/pressbooks/pull/1460)
* Preserve `menu_order` when cloning: [#1430](https://github.com/pressbooks/pressbooks/pull/1430)
* Remove deprecated border attribute from tables: [#1422](https://github.com/pressbooks/pressbooks/pull/1422)
* Improve unit testing for export modules: [#1414](https://github.com/pressbooks/pressbooks/pull/1414)
* Process `[heading]` shortcode in `\Pressbooks\Book::getSubsections()` ([#1403](https://github.com/pressbooks/pressbooks/issues/1403)): [#1404](https://github.com/pressbooks/pressbooks/pull/1404)
* Return self-closing image tags in license attributions ([#1395](https://github.com/pressbooks/pressbooks/issues/1395), props [@thomasdumm](https://github.com/thomasdumm) for the bug report): [#1397](https://github.com/pressbooks/pressbooks/pull/1397)
* Check if user is spammy before displaying user catalog: [#1394](https://github.com/pressbooks/pressbooks/pull/1394)
* Don't strip custom `<h1>` class attributes when building two-level table of contents ([#1386](https://github.com/pressbooks/pressbooks/issues/1386), props [@thomasdumm](https://github.com/thomasdumm) for the bug report): [#1393](https://github.com/pressbooks/pressbooks/pull/1393)
* Fix HTML sanitization to ensure that srcset attributes are removed from EPUB source ([#1379](https://github.com/pressbooks/pressbooks/issues/1379)): [#1381](https://github.com/pressbooks/pressbooks/pull/1381)
* When `pb_permissive_webbooks` filter returns true, only hide the book privacy setting: [#1371](https://github.com/pressbooks/pressbooks/pull/1371)
* Only pre-process part contents once ([#1367](https://github.com/pressbooks/pressbooks/issues/1367), props [@thomasdumm](https://github.com/thomasdumm) for the bug report): [#1368](https://github.com/pressbooks/pressbooks/pull/1368)
* Assign original alt tag, title, description, and caption to cloned attachments ([#1344](https://github.com/pressbooks/pressbooks/issues/1344)): [#1362](https://github.com/pressbooks/pressbooks/pull/1362)
* Hide the "View" link when editing taxonomies (props [@colomet](https://github.com/colomet) for the suggestion): [#1351](https://github.com/pressbooks/pressbooks/issues/1351), [#1356](https://github.com/pressbooks/pressbooks/issues/1356), [#1360](https://github.com/pressbooks/pressbooks/issues/1360)

== Upgrade Notice ==
= 5.7.1 =

* Pressbooks 5.7.1 requires PHP >= 7.1.
* Pressbooks 5.7.1 requires [WordPress 5.1.1](https://wordpress.org/news/2019/03/wordpress-5-1-1-security-and-maintenance-release/)
* Pressbooks 5.7.1 requires [McLuhan >= 2.8.2](https://github.com/pressbooks/pressbooks-book/)
