=== Pressbooks ===
Contributors: Pressbooks <code@pressbooks.com>
Donate link: https://opencollective.com/pressbooks
Tags: ebooks, publishing, webbooks
Requires at least: 4.9.5
Tested up to: 4.9.5
Requires PHP: 7.0
Stable tag: 5.3.3
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
= 5.3.0 =

Pressbooks 5.3.0 requires [WordPress 4.9.5](https://wordpress.org/news/2018/04/wordpress-4-9-5-security-and-maintenance-release/).
Pressbooks 5.3.0 requires [McLuhan >= 2.3.0](https://github.com/pressbooks/pressbooks-book/).
