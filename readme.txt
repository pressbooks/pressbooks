=== Pressbooks ===
Contributors: Pressbooks <code@pressbooks.com>
Donate link: https://opencollective.com/pressbooks
Tags: ebooks, publishing, webbooks
Requires at least: 4.9.7
Tested up to: 4.9.7
Requires PHP: 7.0
Stable tag: 5.4.5
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
= 5.4.5 =

* Pressbooks 5.4.5 requires [WordPress 4.9.7](https://wordpress.org/news/2018/07/wordpress-4-9-7-security-and-maintenance-release/).
* Pressbooks 5.4.5 requires [McLuhan >= 2.4.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Ensure that default paragraph separation is respected in webbooks ([#1303](https://github.com/pressbooks/pressbooks/issues/1303)): [742e8b9](https://github.com/pressbooks/pressbooks/commit/742e8b973aae5088dfe6b674e6a76ad34ff8b462)

= 5.4.4 =

* Pressbooks 5.4.4 requires [WordPress 4.9.7](https://wordpress.org/news/2018/07/wordpress-4-9-7-security-and-maintenance-release/).
* Pressbooks 5.4.4 requires [McLuhan >= 2.4.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Restore missing ICC profile file: [#1304](https://github.com/pressbooks/pressbooks/pull/1304)
* Fix links to Thema guides ([#1302](https://github.com/pressbooks/pressbooks/issues/1302)): [#1305](https://github.com/pressbooks/pressbooks/pull/1305)

= 5.4.3 =

* Pressbooks 5.4.3 requires [WordPress 4.9.7](https://wordpress.org/news/2018/07/wordpress-4-9-7-security-and-maintenance-release/).
* Pressbooks 5.4.3 requires [McLuhan >= 2.4.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Ensure that contributor taxonomy is always registered when needed, fixing an issue where empty author data would be cached: [#1300](https://github.com/pressbooks/pressbooks/pull/1300)

= 5.4.2 =

* Pressbooks 5.4.2 requires [WordPress 4.9.7](https://wordpress.org/news/2018/07/wordpress-4-9-7-security-and-maintenance-release/).
* Pressbooks 5.4.2 requires [McLuhan >= 2.4.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Update the TinyMCE table plugin to apply some [upstream bugfixes](https://github.com/tinymce/tinymce-dist/blob/master/changelog.txt#L3-L4): [#1262](https://github.com/pressbooks/pressbooks/pull/1262)
* Improve colour contrast contrast for some TinyMCE buttons and menus ([#1250](https://github.com/pressbooks/pressbooks/issues/1250)): [#1273](https://github.com/pressbooks/pressbooks/pull/1273)
* Restore correct chapter subtitle and author order in EPUB/MOBI exports ([#1271](https://github.com/pressbooks/pressbooks/issues/1271)): [#1274](https://github.com/pressbooks/pressbooks/pull/1274)
* Grant access to the style debugging tool to super admins regardless of `WP_DEBUG` status ([#1272](https://github.com/pressbooks/pressbooks/issues/1272)): [#1275](https://github.com/pressbooks/pressbooks/pull/1275)
* Retain original slug of chapters with differing title and slug during WXR import ([#1276](https://github.com/pressbooks/pressbooks/issues/1276)): [#1277](https://github.com/pressbooks/pressbooks/pull/1277)
* Only force DocRaptor into test mode when in development environments ([#1289](https://github.com/pressbooks/pressbooks/issues/1289)): [#1290](https://github.com/pressbooks/pressbooks/pull/1290)
* Correct the label for the contributor last name field ([#1291](https://github.com/pressbooks/pressbooks/issues/1291)): [#1292](https://github.com/pressbooks/pressbooks/pull/1292)
* Rebuild editor stylesheet on Buckram updates ([#1278](https://github.com/pressbooks/pressbooks/issues/1278), props [@beckej13820](https://github.com/beckej13820) for the bug report): [#1294](https://github.com/pressbooks/pressbooks/pull/1294)

= 5.4.1 =

* Pressbooks 5.4.1 requires [WordPress 4.9.7](https://wordpress.org/news/2018/07/wordpress-4-9-7-security-and-maintenance-release/).
* Pressbooks 5.4.1 requires [McLuhan >= 2.4.0](https://github.com/pressbooks/pressbooks-book/).

**Patches**

* Convert iframes to `[embed]` shortcodes rather than deleting them if the user lacks permissions: [#1247](https://github.com/pressbooks/pressbooks/pull/1247)
* Resolve issue where PDF dependencies were incorrectly reported as missing ([#1253](https://github.com/pressbooks/pressbooks/issues/1253)): [#1254](https://github.com/pressbooks/pressbooks/pull/1254)
* Ensure that themes which don't support the new textbox markup use the old textbox markup: [#1252](https://github.com/pressbooks/pressbooks/pull/1252)
* Remove hotfix for WordPress core vulnerability that was patched in WordPress 4.9.7 ([#1255](https://github.com/pressbooks/pressbooks/issues/1255)): [#1258](https://github.com/pressbooks/pressbooks/pull/1258)
* Resolve issue where part content would not be imported from Pressbooks XML files ([#1259](https://github.com/pressbooks/pressbooks/issues/1259)): [#1260](https://github.com/pressbooks/pressbooks/pull/1260)
* Resolve issue where visiting a user catalog would return a 404 status code instead of the correct 200 status code: [#1261](https://github.com/pressbooks/pressbooks/pull/1261)

= 5.4.0 =

* Pressbooks 5.4.0 requires [WordPress 4.9.6](https://wordpress.org/news/2018/05/wordpress-4-9-6-privacy-and-maintenance-release/).
* Pressbooks 5.4.0 requires [McLuhan >= 2.4.0](https://github.com/pressbooks/pressbooks-book/).

**Minor Changes**

* Add support for PDF exports via [DocRaptor](https://docraptor.com) to Pressbooks core ([#1238](https://github.com/pressbooks/pressbooks/issues/1238)): [#1240](https://github.com/pressbooks/pressbooks/pull/1240)
* Bump minimum supported WordPress to 4.9.6: [#1237](https://github.com/pressbooks/pressbooks/pull/1237)
* Add support for WordPress 4.9.6 privacy policy management: [#1236](https://github.com/pressbooks/pressbooks/pull/1236)
* Improve admin bar for network administrators and network managers: [#1226](https://github.com/pressbooks/pressbooks/pull/1226), [#1232](https://github.com/pressbooks/pressbooks/pull/1232), [#1234](https://github.com/pressbooks/pressbooks/pull/1234)
* Improve educational textbox markup, add sidebar textboxes: [#1210](https://github.com/pressbooks/pressbooks/pull/1210)
* Allow default book cover image to be filtered, add size parameter: [#1214](https://github.com/pressbooks/pressbooks/pull/1214)
* Allow access to webbook sharing & visibility settings to be restricted by a filter: [#1239](https://github.com/pressbooks/pressbooks/pull/1239)
* Bump tinymce from 4.7.12 to 4.7.13: [#1213](https://github.com/pressbooks/pressbooks/pull/1213)
* Bump leafo/scssphp from 0.7.5 to 0.7.6: [#1223](https://github.com/pressbooks/pressbooks/pull/1223)

**Patches**

* Allow usernames to be an email address in the catalog rewrite rule (props [@lukaiser](https://github.com/lukaiser)): [#1216](https://github.com/pressbooks/pressbooks/pull/1216)
* Ensure that classnames are output properly for EPUB table of contents: [#1224](https://github.com/pressbooks/pressbooks/pull/1224)
* Improve `reverse_wpautop()` function to avoid stripping newlines from within `<pre>` tags during import and clone operations ([#1225](https://github.com/pressbooks/pressbooks/issues/1225), props [@SteelWagstaff](https://github.com/SteelWagstaff) for reporting): [#1227](https://github.com/pressbooks/pressbooks/pull/1227)
* Enable `$concatenate_scripts` and remove unused admin JS dependency: [#1233](https://github.com/pressbooks/pressbooks/pull/1233)
* Use polyfills to avoid warnings in PHP 7.2 environment: [#1237](https://github.com/pressbooks/pressbooks/pull/1237)
* Fix an issue where PDF internal links would not function as expected: [#1245](https://github.com/pressbooks/pressbooks/pull/1245)
* Lower personal data export cron frequency to twicedaily ([#1242](https://github.com/pressbooks/pressbooks/issues/1242)): [#1246](https://github.com/pressbooks/pressbooks/pull/1246)
* Increase HTTP timeout for the Prince and DocRaptor PDF export modules to PHP `max_execution_time` (props [@rootl](https://github.com/rootl) for the bug report): [#1248](https://github.com/pressbooks/pressbooks/pull/1248)
* Fix the suppression of TOC part and chapter numbers for Buckram 1.0 themes: [#1249](https://github.com/pressbooks/pressbooks/pull/1249)

= 5.3.4 =
**Patches**

- Hotfix for [WordPress core security issue](https://blog.ripstech.com/2018/wordpress-file-delete-to-code-execution/): [a462e0d](https://github.com/pressbooks/pressbooks/commit/a462e0d/)

= 5.3.3 =
**Patches**

- Fix EPUB internal links by stripping trailing slash before `#`: [#1222](https://github.com/pressbooks/pressbooks/pull/1222)

= 5.3.2 =
**Patches**

- Fix a bug with `fetchAndSaveUniqueImage` and `fetchAndSaveUniqueFont` methods introduced in [0c84a5d](https://github.com/pressbooks/pressbooks/commit/0c84a5deb5d603d97ddf9745fcf4792275c36bc5): [#1220](https://github.com/pressbooks/pressbooks/pull/1220)
- Use `wp dist-archive` to build release asset [#1219](https://github.com/pressbooks/pressbooks/issues/1219): [#1221](https://github.com/pressbooks/pressbooks/pull/1221)

= 5.3.1 =
**Patches**

- Fix ODT export errors caused by images with percentage width or height attributes: [#1215](https://github.com/pressbooks/pressbooks/pull/1215)
- Improve `$_SESSION` handling: [#1217](https://github.com/pressbooks/pressbooks/pull/1217)
- Fix display issue when login screen buttons require multiple lines: [283e570](https://github.com/pressbooks/pressbooks/commit/283e5707b35fd0ad28e3f155b65d9df7de6927d6)
- Fix display issue with mobile content editor: [1efb2c7](https://github.com/pressbooks/pressbooks/commit/1efb2c73b2de9c67a21304fd6013524c37159cdc)

= 5.3.0 =
**Minor Changes**

- Add theme option for comparison with source ([pressbooks/pressbooks-book#152](https://github.com/pressbooks/pressbooks-book/issues/152)): [#1203](https://github.com/pressbooks/pressbooks/pull/1203)
- Add global theme option for educational textbox colors: [#1189](https://github.com/pressbooks/pressbooks/pull/1189), [#1194](https://github.com/pressbooks/pressbooks/pull/1194)
- Add web theme option for collapsing sections: [#1181](https://github.com/pressbooks/pressbooks/pull/1181)
- Add network integrations menu: [#1195](https://github.com/pressbooks/pressbooks/pull/1195)
- Add programmatic login function for SSO extensions: [#1196](https://github.com/pressbooks/pressbooks/pull/1196)
- Add export page hooks for customization (props [@lukaiser](https://github.com/lukaiser)): [#1205](https://github.com/pressbooks/pressbooks/pull/1205) (no notes for testing)
- Restore some default TinyMCE table controls: [#1193](https://github.com/pressbooks/pressbooks/pull/1193)
- Replace Creative Commons API usage with built-in license string generation ([#1170](https://github.com/pressbooks/pressbooks/issues/1170)): [#1201](https://github.com/pressbooks/pressbooks/pull/1201), [#1202](https://github.com/pressbooks/pressbooks/pull/1202)
- Improve support for WP QuickLaTeX: [#1200](https://github.com/pressbooks/pressbooks/pull/1200)
- Add a debug parameter to XHTML and HTMLBook URLs for export CSS previewing: [#1183](https://github.com/pressbooks/pressbooks/pull/1183)
- Add a new `Styles::hasBuckram()` helper method with optional version parameter: [#1187](https://github.com/pressbooks/pressbooks/pull/1187)
- Disallow certain taxonomies based on a blacklist instead of a whitelist ([#1095](https://github.com/pressbooks/pressbooks/issues/1095)): [#1172](https://github.com/pressbooks/pressbooks/pull/1172)
- Disable SSL verification for self-signed certificates in development environments: [#1191](https://github.com/pressbooks/pressbooks/pull/1191)
- Improve requires so that other plugins can efficiently use them: [#1179](https://github.com/pressbooks/pressbooks/pull/1179)
- Ignore SASS variables prefixed with `_` in the `parseVariables()` method: [#1188](https://github.com/pressbooks/pressbooks/pull/1188)
- Use less memory in createFileFromUrl (props [@bdolor](https://github.com/bdolor)): [#1211](https://github.com/pressbooks/pressbooks/pull/1211)
- Update minimum WordPress version to 4.9.5 ([#1176](https://github.com/pressbooks/pressbooks/issues/1176)): [#1178](https://github.com/pressbooks/pressbooks/pull/1178)
- Update Pressbooks CLI to 1.8.2: [fd6d2b1](https://github.com/pressbooks/pressbooks/commit/fd6d2b15b7e4387435a498e6e2a007b83e47712d)
- Update Isotope to 3.0.6: [#1177](https://github.com/pressbooks/pressbooks/pull/1177)
- Update TinyMCE Table plugin to 4.7.12: [#1208](https://github.com/pressbooks/pressbooks/pull/1208)

**Patches**

- Hide link to book info on the front end: [#1207](https://github.com/pressbooks/pressbooks/pull/1207)
- Fix bug with internal links in EPUB with content not marked for export: ([#1209](https://github.com/pressbooks/pressbooks/issues/1209)): [#1212](https://github.com/pressbooks/pressbooks/pull/1212)
- Fix paragraph separation web theme option for Custom CSS theme: [#1180](https://github.com/pressbooks/pressbooks/pull/1180)
- Prepare fix for numbering issue in front matter that follow the introduction ([#1197](https://github.com/pressbooks/pressbooks/issues/1197)): [#1198](https://github.com/pressbooks/pressbooks/pull/1198)
- Prevent slug collisions in XHTML and HTMLBook outputs ([#1174](https://github.com/pressbooks/pressbooks/issues/1174)): [#1175](https://github.com/pressbooks/pressbooks/pull/1175)

== Upgrade Notice ==
= 5.4.5 =

* Pressbooks 5.4.5 requires [WordPress 4.9.7](https://wordpress.org/news/2018/07/wordpress-4-9-7-security-and-maintenance-release/).
* Pressbooks 5.4.5 requires [McLuhan >= 2.4.0](https://github.com/pressbooks/pressbooks-book/).
