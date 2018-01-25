=== Pressbooks ===

Contributors: Pressbooks <code@pressbooks.com>
Version: 4.5.1
Stable Tag: 4.5.1
Tags: ebooks, publishing, webbooks
Requires PHP: 7.0
Requires at least: 4.9.2
Tested up to: 4.9.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Pressbooks is an open source book publishing tool built on a WordPress multisite platform. Pressbooks outputs books in
multiple formats, including PDF, EPUB, MOBI, web, and a variety of XML flavours, using a theming/templating system,
driven by CSS. For more information, visit https://pressbooks.com.

== Maintenance and Support ==

Contact us about maintenance and support contracts if you are installing Pressbooks on your own servers, or if you would
like Pressbooks to run a dedicated instance for you on our servers. You can reach us at: sales@pressbooks.com.

== Communication ==

Our main communication channel for Pressbooks is [Discourse](https://discourse.pressbooks.org). You can ask questions there. Reproducible issues or clearly spec'ed feature requests go on [Github](https://github.com/pressbooks/pressbooks).

== Contributors ==

All Pressbooks code is copyright Book Oven Inc. Contributors are acknowledged at [docs.pressbooks.org/contributors/](https://docs.pressbooks.org/contributors).

== Installation ==

For installation instructions, visit [docs.pressbooks.org/installation](https://docs.pressbooks.org/installation).

== Frequently Asked Questions ==

TK.

== Screenshots ==

1. Your Book
2. Book Information
3. Themes
4. Theme Options
5. Export
6. Catalog

== Upgrade Notice ==
= 4.5.1 =

Pressbooks >= 4.5.1 requires [WordPress 4.9.2](https://wordpress.org/news/2018/01/wordpress-4-9-2-security-and-maintenance-release/).

== Changelog ==
= 4.5.1=
**NOTICE:** Pressbooks >= 4.5.1 requires [WordPress 4.9.2](https://wordpress.org/news/2018/01/wordpress-4-9-2-security-and-maintenance-release/).

* **[FIX]**: Disallow dangerous file types from upload via the Import form ([9bcebf1](https://github.com/pressbooks/pressbooks/commit/9bcebf1))
* **[FIX]**: Handle `author` objects containing multiple authors during clone operations for forward-compatibility with Pressbooks 5 ([9231ffa](https://github.com/pressbooks/pressbooks/commit/9231ffa), [b0e03c4](https://github.com/pressbooks/pressbooks/commit/b0e03c4), [035c1b9](https://github.com/pressbooks/pressbooks/commit/035c1b9))

= 4.5.0 =
**NOTICE:** Pressbooks >= 4.5 requires [PHP 7.0](https://secure.php.net/supported-versions.php) or greater.
**NOTICE:** Pressbooks >= 4.5 requires [WordPress 4.9.1](https://wordpress.org/news/2017/11/wordpress-4-9-1-security-and-maintenance-release/).

* **[FEATURE]** Switching to a new theme will now update some PDF theme options to match the theme's values (see #456, #984).
* **[FEATURE]** Add initial support to PDF and Ebook theme options for themes which skip lines between paragraphs by default (see #985).
* **[CORE ENHANCEMENT]** Use standard build tools package [pressbooks-build-tools](https://www.npmjs.com/package/pressbooks-build-tools) for asset handling, upgrade Stylelint (see #1000).
* **[CORE ENHANCEMENT]** Require PHP 7.0 or greater (see #935, #987).
* **[CORE ENHANCEMENT]** Add exclude and include support to `Pressbooks\Utility\rcopy()` (see #990).
* **[CORE ENHANCEMENT]** Improve Theme Lock feature in preparation for Pressbooks 5.0 (see #995).
* **[CORE ENHANCEMENT]** Add Prettier for SCSS and JS formatting (see #991).
* **[CORE ENHANCEMENT]** Optimize subsection parsing (see #992).
* **[CORE ENHANCEMENT]** Optimize `__UNSET__` style (see #999).
* **[CORE ENHANCEMENT]** Optimize unit testing (see #997).
* **[FIX]** Replace 'Sites' with 'Books' throughout Pressbooks interface (see #993).
* **[FIX]** Replace 'Exotic formats' with 'Other formats' on the Export page (see #996).
* **[FIX]** Fix bug related to [#42574](https://core.trac.wordpress.org/ticket/42574) which prevented widget editing on the root blog (#998).
* **[FIX]** Fix type mismatch in `\Pressbooks\Licensing` class (see [23ee4ff](https://github.com/pressbooks/pressbooks/commit/23ee4ffee60b585d1390690af627c455bf969883))
* **[FIX]** Fix typo in EPUB exporter — the acronym is OEBPS (Open eBook Publication Structure) (props @bdolor; see #988).

= 4.4.0 =
**NOTICE:** Pressbooks >= 4.4 requires [WordPress 4.9](https://wordpress.org/news/2017/11/tipton/).

* **[FEATURE]** You can now assign [Thema](https://ns.editeur.org/thema/en) subject categories to your book on the Book Information page (see #978).
* **[FEATURE]** Part slugs are now editable (props to @colomet for the suggestion; see 3f5eca2).
* **[CORE ENHANCEMENT]** Pressbooks now uses WordPress’ included [CodeMirror](https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/) scripts and styles for our Custom Styles editor (see #980).
* **[CORE ENHANCEMENT]** Added the `pb_global_components_path` filter which lets book themes override the global components path to point to their own bundled components libraries (see #982).
* **[CORE ENHANCEMENT]** Added the `pb_pre_export` action to allow tweaks prior to an export routine (see 5302eea).
* **[CORE ENHANCEMENT]** Our `app()` function now matches Laravel 5.4’s function signature (see cdcb9e8).
* **[FIX]** Importing a Word document with multiple images now works properly (props to @rootl for the bug report; see #288 and #977).
* **[FIX]** Chapters will now correctly inherit their book’s license in the API (see #979).
* **[FIX]** Chapters will no longer show raw content in the API if they are password-protected (see #975).
* **[FIX]** Uploading an image to the user catalog no longer causes an error (props to @emasters for the bug report; see #983).

= 4.3.5 =
**NOTICE:** Pressbooks >= 4.3.3 requires WordPress 4.8.2.
**NOTICE:** Users of the Pressbooks Custom CSS theme must upgrade to Pressbooks Custom CSS 1.0 for compatibility with Pressbooks >= 4.3.0.

* **[CORE ENHANCEMENT]** Use Laravel Container instead of Pimple as our service container; add Laravel Blade support for future templated outputs (see #831, #962, and #970).
* **[FIX]** Content imported from EPUB is now ordered by spine order instead of manifest order (props to @hakkim-pits; see #442 and #968).
* **[FIX]** Custom styles are no longer sanitized in ways that improperly encode characters (see #972).
* **[FIX]** Sanitize body font size PDF theme option as a float instead of an integer to allow more size options (see #969).
* **[FIX]** `home_url` is now used instead of `site_url` when linking to front-end content (see #971; reference: https://github.com/roots/bedrock/pull/316).
* **[FIX]** Shortcodes will now be cloned as is to preserve more footnote and LaTeX data (see #973).
* **[FIX]** Special characters in a book title will no longer lead to filename issues under certain circumstances (see #974).

= 4.3.4 =
**NOTICE:** Pressbooks >= 4.3.3 requires WordPress 4.8.2.
**NOTICE:** Users of the Pressbooks Custom CSS theme must upgrade to Pressbooks Custom CSS 1.0 for compatibility with Pressbooks >= 4.3.0.

* **[CORE ENHANCEMENT]** The user catalog title can now be changed via the `pb_catalog_title` filter (props to @monkecheese; see #961).
* **[CORE ENHANCEMENT]** SCSS variables from theme options will now be passed to the SCSS compiler as key/value pairs rather than by building SCSS in PHP (see #782 and #963).
* **[FIX]** Fixed an issue where the PDF margins theme option was not being applied properly.
* **[FIX]** Fixed a conflict between the updated Pressbooks LaTeX module and third-party renderers (props to @monkecheese; see #958 and #959).
* **[FIX]** The publication date should now save properly, regardless of book language (thanks to @thomasdumm for the bug report; see #965 and #966).

= 4.3.3 =
**NOTICE:** Pressbooks 4.3.3 requires WordPress 4.8.2.
**NOTICE:** Users of the Pressbooks Custom CSS theme must upgrade to Pressbooks Custom CSS 1.0 for compatibility with Pressbooks 4.3.3.

* **[CORE ENHANCEMENT]** The Pressbooks plugin is now self-updating — GitHub Updater is no longer required (see #897 and #954).
* **[CORE ENHANCEMENT]** Error logs from export routines can be emailed to an array of email addresses supplied via the `pb_error_log_emails` filter (see #956).
* **[CORE ENHANCEMENT]** Images in cloned or imported books can now be properly edited using the WordPress image editor (see #920 and #949).
* **[FIX]** We’ve implemented a better solution for the PDF profile bug (see #951, #952).
* **[FIX]** URLs like `/catalog/page/1` will no longer attempt to load user catalogs (see #953).

= 4.3.2 =
**NOTICE:** Pressbooks 4.3.2 requires WordPress 4.8.1.
**NOTICE:** Users of the Pressbooks Custom CSS theme must upgrade to Pressbooks Custom CSS 1.0 for compatibility with Pressbooks 4.3.2.

* **[FIX]** Fixed an issue which prevented the Print PDF profile from loading properly (see #951).

= 4.3.1 =
**NOTICE:** Pressbooks 4.3.1 requires WordPress 4.8.1.
**NOTICE:** Users of the Pressbooks Custom CSS theme must upgrade to Pressbooks Custom CSS 1.0 for compatibility with Pressbooks 4.3.1.

* **[CORE ENHANCEMENT]** Added a debuggin switch to Custom Styles (see #946).
* **[FIX]** Resolved an issue where some fonts would not be loaded properly during the PDF export routine (see #944 and #945).
* **[FIX]** Updated routines that use XPath for compatibility with HTML5, resolving some issues with multi-level TOC and EpubCheck validation (see #947).

= 4.3 =
**NOTICE:** Pressbooks 4.3 requires WordPress 4.8.1.
**NOTICE:** Users of the Pressbooks Custom CSS theme must upgrade to Pressbooks Custom CSS 1.0 for compatibility with Pressbooks 4.3.

* **[FEATURE]** Custom Styles: Navigate to _Appearance_ &rarr; _Custom Styles_ on your book's dashboard to add custom CSS or SCSS to any book theme (see #658, #912, #925, #937, #938, #940, #941, and #942).
* **[ENHANCEMENT]** Expanded the `license` property of the `/metadata` endpoint to include a human-readable license name and custom license text (if present) (see #934 and #936).
* **[ENHANCEMENT]** Added the book's short description to the `/metadata` endpoint as a [`disambiguatingDescription`](http://schema.org/disambiguatingDescription) (see #930 and #932).
* **[ENHANCEMENT]** Clarified errors when trying to clone a book from Pressbooks < 4.1 (see #914 and #931).
* **[ENHANCEMENT]** Renamed several action and filter hooks and deprecated the old versions (see #926).
* **[FIX]** Fixed an issue which would prevent super administrators without any books on a network from accessing the cloning page (see #913 and #933).
* **[FIX]** Fixed a regression which blocked the use of custom LaTeX renderers (props to @monkecheese; see #928).

= 4.2 =
**NOTICE:** Pressbooks 4.2 requires WordPress 4.8.1.

* **Feature:** Full-sized images will be used where possible in Print PDF exports to ensure that exported PDFs meet image resolution requirements (see #894, #898 and #900).
* **Feature:** WXR import and clone operations will now attempt to fetch original images from the source book in addition to the scaled/cropped version in the book content (see #895 and #902).
* **Feature:** Content on the organize page now has a View link as will as Edit and Trash (see #840 and #893).
* **Enhancement:** The [Masterminds HTML5 parser](https://github.com/Masterminds/html5-php) is now used instead of `\DOMDocument` where possible for improved error handling and compatibility with HTML5 elements (see #889 and #896).
* **Enhancement:** Unnecessary HTTP calls have been removed from export routines (see #899).
* **Enhancement:** Installation instructions are now linked from the readme file instead of being included (see #891 and #892).
* **Fix:** Resolved some inconsistencies with custom copyright notice and copyright year display (see #922).
* **Fix:** Clone operations now have a 5-minute time limit which should reduce the occurrence of timeouts (props to @bdolor for the bug report; see #903 and #904).
* **Fix:** Visiting `/catalog` on the root site no longer causes an error (see #905).
* **Fix:** Pressbooks LaTeX settings no longer appear on the root site's dashboard (see #910 and #911).
* **Fix:** The Organize page now supports all post statuses (see #915).
* **Fix:** Fixed an issue where the Pressbooks News dashboard widget would be cached in the wrong language (see #918 and #921).
* **Fix:** Removed some unused code from the PB LaTeX symbiont (props to @jeremyfelt; see #923).

= 4.1 =
**NOTICE:** Pressbooks 4.1 requires [WordPress 4.8.1](https://wordpress.org/news/2017/08/wordpress-4-8-1-maintenance-release/).

* **Feature:** Cloning! Clone any public, properly-licensed book from any Pressbooks 4.1 network including your own (super admins can clone any book from their own network, regardless of license) (see #841, #857, #881, #885).
* **Feature:** Granular display controls for content licenses at the book and section level (see #805, #867, #873, #883, #884).
* **Feature:** Word count for the entire book and for content marked for export is now displayed on the Organize page (see #842, #878, #880).
* **Feature:** Users can now delete their own books from the book menu (see #845, #864).
* **Feature:** Custom taxonomies are now available in the Pressbooks REST API v2 (see #851, #853).
* **Feature:** The Schema.org [isBasedOn](http://bib.schema.org/isBasedOn) property is now saved in metadata and displayed in the Pressbooks REST API v2 (see #850, #852).
* **Feature:** Search & Replace with RegEx is now available for super admins without additional configuration (see #870, #871, #879).
* **Feature:** Punjabi Gurmukhi support (props to @alexpflores) (see #877).
* **Enhancement:** Book editors can now modify theme options and custom CSS (see #862, #863).
* **Enhancement:** The Pressbooks News feed is now cached across all sites to reduce unnecessary network access (see #882).
* **Fix:** The TOC endpoint in the Pressbooks REST API v2 now uses `chapters` and `parts` for consistency with the endpoints for these post types.
* **Fix:** The EPUB importer now properly detects and handles optional whitespaces (see #554, #874).
* **Fix:** Users without super admin privileges can no longer access the trash when they shouldn't be able to do so (see #865).

= 4.0.2 =
* **Fix:** Fixed an issue where locating a PDF output intent file would fail on certain systems (see #866).

= 4.0.1 =
* **Fix:** Fixed an issue where the template root for book themes was not properly updated (see #854, #859).
* **Fix:** Fixed an issue where ampersands were not being sanitized in XML outputs (see #860).
* **Fix:** Fixed an issue where the Disable Comments setting was not being saved properly (see #861).
* **Fix:** Fixed an incorrect link in upgrade notices (see #848, #849).

= 4.0.0 =
**NOTICE:** Upon upgrading to Pressbooks 4.0, you will need to install the [Pressbooks Book](https://github.com/pressbooks/pressbooks-book) and [Pressbooks Publisher](https://github.com/pressbooks/pressbooks-publisher) themes along with any of our other open source [book themes](https://github.com/search?q=topic%3Abook-theme+org%3Apressbooks&type=Repositories) that were bundled with earlier versions of Pressbooks. For more information, see the [upgrading instructions](https://docs.pressbooks.org/upgrading).
**NOTICE:** Pressbooks 4.0 requires [WordPress 4.8.0](https://wordpress.org/news/2017/06/evans/).

#### Pressbooks 4.0 "Slate"

##### Next-Generation REST API

Building on [Brad Payne's](http://bradpayne.ca) original REST API for Pressbooks, we've introduced an improved and expanded REST API based on the WordPress Core [REST API](https://developer.wordpress.org/rest-api/) infrastructure. The Pressbooks REST API v2 supports authenticated CRUD (Create, Read, Update, Destroy) access to all Pressbooks content types (front and back matter, parts, and chapters) as well as read-only access to book structure and metadata. For more information, see our [REST API documentation](http://docs.pressbooks.org/api). We're excited to see what the Pressbooks Open Source community will do with these new API capabilities! Share your projects with us: [code@pressbooks.com](mailto:code@pressbooks.com).

##### Enhanced LaTeX Rendering

Pressbooks' core LaTeX renderer now produces high resolution output suitable for print! More improvements to come, and thanks for your patience as we've worked to improve this important feature.

##### Better Content Management

Want to mark all chapters for export on the Organize screen? You can do that now! Trashed something that you want back? Just navigate to Text &rarr; Trash and you can restore it. Many more improvements to come!

##### Pressbooks CLI

The Pressbooks command line interface is now part of Pressbooks. Want to make your own book theme? Run `wp scaffold book-theme` from the root of your Pressbooks install and take advantage of our intuitive SCSS-based theme structure. More commands to come -- [submit your ideas](https://github.com/pressbooks/pb-cli/issues)!

#### Detailed Changelog

* **Feature:** REST API v2 (see #472, #763, #770, #771, #774, #778, #780, #781, #783, #785, #788, #798, #803, #804, #806, #807, #810, #812, #815, #816, #823, #832, and our [API Docs](http://docs.pressbooks.org/api)
* **Feature:** LaTeX outputs are now at a sufficient resolution for print applications (see #819).
* **Feature:** You can now change statuses in bulk on the **Organize** page (see #249 and #822).
* **Feature:** Deleted content can now be restored from **Text &rarr; Trash** (see [9283c26](https://github.com/pressbooks/pressbooks/tree/9283c26504007ba55259672c5cb9efc8ee07b3c0)).
* **Enhancement:** The Pressbooks CLI is now bundled in Pressbooks (see #464 and #826).
* **Enhancement:** `new \Pressbooks\Metadata()` now returns book metadata as an implementation of [JsonSerializeable](https://secure.php.net/manual/en/class.jsonserializable.php) (see #804 and #832).
* **Enhancement:** Expanded metadata is now hidden on the **Book Information** page unless needed (see #804 and #832).
* **Enhancement:** We now use the [Human Made coding standards](https://engineering.hmn.md/how-we-work/style/php/) for PHP. [Check your code](http://docs.pressbooks.org/coding-standards/#validating-with-php-code-sniffer) before submitting a PR 👍.
* **Enhancement:** We now use [Laravel Mix](https://github.com/jeffreyway/laravel-mix) to handle all plugin assets (see #769 and #795). Making a change in `/assets/src/`? With [Yarn](https://yarnpkg.com) installed in your development environment, run `yarn && yarn run build` to build assets for distribution.
* **Enhancement:** SCSS files can now be checked against our coding standards using [stylelint](https://stylelint.io) with the command `yarn run lint` (see #743 and #817).
* **Enhancement:** JS files have been updated to ES6 and can now be checked against our coding standards using [eslint](http://eslint.org) with the command `yarn run lint` (see #829).
* **Enhancement:** Root and book themes are now distributed separately from the Pressbooks plugin -- make sure you install the required themes when you [upgrade](http://docs.pressbooks.org/upgrading)! (See #756 and #799.)
* **Enhancement:** Part content has been migrated to the standard content editor instead of a custom field (see #486 and #764).
* **Enhancement:** The Search and Replace module has been heavily optimized, reducing memory usage by ~85% (see #759 and #793).
* **Enhancement:** Additional post types can be added to the list of permitted post types for editing using the [`pb_supported_post_types` filter](https://github.com/pressbooks/pressbooks/blob/4.0.0/inc/posttype/namespace.php#L16-L29) (props to @steelwagstaff, see #758).
* **Enhancement:** We now use [vanilla/htmlawed](https://packagist.org/packages/vanilla/htmlawed) as our htmLawed provider (see #767).
* **Enhancement:** Developers can now add new import types via the `pb_import_table_cell` and `pb_initialize_import` filter hooks (props @bdolor; see #802 and #811).
* **Enhancement:** Releases are now packaged automatically via Travis (see #730 and #821).
* **Fix:** DOCX and ODT files exported from Google Docs (which lack standard metadata) can now be imported without issue via the import module (see #837 and #838).
* **Fix:** Images are now set to a `prince-image-resolution` of `auto, normal` rather than 300dpi for more reliably high-quality print PDF output (see #744 and #776).
* **Fix:** Text suggesting that we offer printing services has been removed from the Publish page (see #784).
* **Fix:** Export downloads from the webbook include the proper file extensions (props to @bdolor; see #808).
* **Fix:** Current privacy settings are now displayed properly when updating book privacy from the Organize page (see #711 and #801).
* **Fix:** The editor style is now enqueued with a version for cache busting (see #813 and #814).
* **Fix:** The Search and Replace module no longer searches items in the trash (see [6978734](https://github.com/pressbooks/pressbooks/commit/697873425439be829abaeb077fbc3f6a8391b17e)).
* **Fix:** Miscellaneous improvements to improve performance and reduce unnecessary error output.

= 3.9.10 =
* **Feature:** Strikethrough text (the `<del>` tag) can now be used in front matter, part, chapter and back matter titles (see #790).

= 3.9.9 =
**NOTICE:** Pressbooks now requires [WordPress 4.7.5](https://wordpress.org/news/2017/05/wordpress-4-7-5/).

* **Feature:** A curated listed of recommended plugins is now displayed within the plugin installer (see #729).
* **Feature:** Search and Replace now supports regular expressions (props to @stepmuel; see #754). This feature can be enabled by adding: `define( 'PB_ENABLE_REGEX_SEARCHREPLACE', true );` to `wp-config.php`.
* **Enhancement:** Updating a book theme will now trigger the regeneration of the webbook stylesheet (see #727 and #762).
* **Enhancement:** There is now a specific template part, `private.php`, for the page that is displayed when a book is private (props to @stepmuel; see #755).
* **Enhancement:** "Part" is now properly localized in the PDF Table of Contents (see #742).
* **Enhancement:** Improved blank page rules in theme components.
* **Enhancement:** The Ebook theme options tab is now hidden when Ebook dependencies are missing (props to @masaka222; see #745).
* **Enhancement:** Dependency check results are now stored in site transients to reduce unnecessary function calls (see #749, #750).
* **Enhancement:** Replaced variables with constants where appropriate (see #751).
* **Enhancement:** Replaced uses of `PATH_CURRENT_SITE` with `network_home_url()` (props to @jeremyfelt; see #734).
* **Enhancement:** Current `$post` is now included with `wp_enqueue_media()` in `symbionts/custom-metadata` (props to @jeremyfelt; see #735).
* **Fix:** Removed the sticky navigation bar that overlapped webbook content (props to @pbstudent for the bug report; see #747 and #760).
* **Fix:** Fixed an issue where running content strings would not be populated when a custom title page was used (see #496 and #761).
* **Fix:** Fixed an issue where the book title would not update properly (see #542 and #746).
* **Fix:** Fixed issues that arose when `pb_language` or `user_interface_lang` were not set (props to @monkecheese for initial bug report and testing; see #738, #739, #740).
* **Fix:** Fixed an issue where a database error would be thrown when installing on a utf8mb4 MySQL instance (props to @jeremyfelt; see #733).

= 3.9.8.2 =
**NOTICE:** Pressbooks' PHP version requirement (>= 5.6) and WordPress version requirement (>= 4.7.3) can no longer be overridden. Before installing Pressbooks 3.9.8, please ensure that your system has been upgraded accordingly.

* **Fix:** Switched to an unmodified version of htmLawed to fix a regression in [vanilla/htmlawed](https://github.com/vanilla/htmlawed/) which was stripping paragraph tags from blockquotes (see #723).
* **Fix:** Fixed an issue where users would be informed that their theme had been unlocked when saving Export options even thought it was already unlocked (see #722).
* **Fix:** Fixed an issue where wp-cli would give a permissions error because of the `\Pressbooks\ThemeLock::isLocked()` check (see #721).

= 3.9.8.1 =
**NOTICE:** Pressbooks' PHP version requirement (>= 5.6) and WordPress version requirement (>= 4.7.3) can no longer be overridden. Before installing Pressbooks 3.9.8, please ensure that your system has been upgraded accordingly.

* **Fix:** Restored some webbook styles that were being omitted in older book themes.

= 3.9.8 =
**NOTICE:** Pressbooks' PHP version requirement (>= 5.6) and WordPress version requirement (>= 4.7.3) can no longer be overridden. Before installing Pressbooks 3.9.8, please ensure that your system has been upgraded accordingly.

* **Feature:** Themes can now be locked a particular version. The theme's stylesheets and other assets will be copied into the book's media directory and used for future exports (see #657, #704).
* **Feature:** The paragraph separation option is now available for webbooks (see #655, #696).
* **Feature:** The section openings PDF theme option now supports additional options (see #450, #691).
* **Feature:** When export sharing is enabled, the download links are now stable, e.g. `/open/download?type=pdf` (props to @rootl for the suggestion; see #684, #699).
* **Enhancement:** Pressbooks now supports third-party export formats (see #385 and #674).
* **Enhancement:** `\Pressbooks\Options` field display functions have been refactored to use an array of arguments instead of a list of parameters (see #648, #697) [BREAKING CHANGE].
* **Enhancement:** SCSS overrides have been moved into their respective theme options classes (see #452, #701).
* **Enhancement:** Webbook interface styles have been separated from the Luther book theme's content styles (see #656, #708).
* **Enhancement:** Webbook stylesheet and script enqueuing has been clarified and simplified (see #396).
* **Enhancement:** Searching now excludes non-Pressbooks post types (props to @colomet for the report; see #706, #707).
* **Enhancement:** Front-end scripts are now loaded asynchronously (props to @bdolor; see #681).
* **Enhancement:** htmLawed is now a Composer dependency (see #702).
* **Enhancement:** The sassphp dependency is no longer required (see #693).
* **Enhancement:** The SaxonHE dependency check can now be overridden (see https://github.com/pressbooks/pressbooks/commit/7ea32fe).
* **Enhancement:** [perchten/rmrdir](https://packagist.org/packages/perchten/rmrdir) is now used for recursive directory removal (see [37ab804](https://github.com/pressbooks/pressbooks/commit/37ab804489c580ad1d1121c0a07144f37772c7d0)).
* **Enhancement:** Added \Pressbooks\Utility\rcopy() function for recursive directory copying (props to @blobaugh for the [example code](http://ben.lobaugh.net/blog/864/php-5-recursively-move-or-copy-files); see [52b087b](https://github.com/pressbooks/pressbooks/commit/52b087b5e2185ea08c6f67c24111ad9ef0ee1fa0)).
* **Enhancement:** Added `pb_dependency_errors` filter hook for suppression of dependency errors (see #719).
* **Fix:** Images on custom title pages are now exported as expected in EPUB and Kindle (see #690, #698).
* **Fix:** The diagnostics page now functions as expected on the root blog (props to @colomet for the report; see #688, #695);
* **Fix:** Print PDF exports are now available for download when export sharing is enabled (props to @bdolor; see #677).
* **Fix:** Numberless chapters no longer display a lonely period in PDF outputs from SCSS v2 themes (props to @thomasdumm for the report; see #670).
* **Fix:** Importing as a draft now works for EPUB imports (props to @thomasdumm for the report; see #668).

= 3.9.7.2 =
**NOTICE:** Pressbooks now requires [WordPress 4.7.3](https://wordpress.org/news/2017/03/wordpress-4-7-3-security-and-maintenance-release/).

* **Enhancement:** Streamlined and refactored the running content SCSS partials for SCSS-based themes (see #675 and #686).

= 3.9.7.1 =
* **Fix:** Fixed an issue where the custom CSS file for webbooks would not be loaded on subdirectory installs.

= 3.9.7 =
**NOTICE:** Pressbooks now requires [WordPress 4.7.2](https://wordpress.org/news/2017/01/wordpress-4-7-2-security-release/).

* **Feature:** Added support for Canadian Indigenous syllabics, which are used for the Chipewyan, Inuktitut, Plains Cree, Cree, Moose Cree, Slave, Northern Cree, Naskapi, Swampy Cree, Southern East Cree, and Ojibwa languages (props to @bdolor; see #635).
* **Feature:** Part numbers are now displayed consistently across all formats (see #341).
* **Enhancement:** SCSS maps are now used to provide variables for different export formats.
* **Enhancement:** The global `_titles.scss` partial for SCSS v2 themes has been split into `_pages.scss` and `_section-titles.scss` for better separation of concerns.
* **Enhancement:** Added the `pb_add_latex_renderer_option`, `pb_require_latex`, `pb_latex_renderers`, and `pb_add_latex_config_scripts` filters and the `pb_enqueue_latex_scripts` action to support custom LaTeX renderers (props to @monkecheese; see #583).
* **Enhancement:** Added the `pb_root_description` filter to allow the default root blog description to be changed.
* **Enhancement:** Custom theme options can now be registered, either on an existing tab or on a new tab (see #470 and #618).
* **Enhancement:** Added the `pb_publisher_catalog_query_args` filter to allow customizing the query for books on the front page of Pressbooks Publisher (see #619).
* **Enhancement:** Added the `\Pressbooks\Metadata::getJsonMetadata()` function and the `pb_json_metadata` filter to support returning book information as JSON data for posting to an API endpoint (see #637).
* **Enhancement:** Added the `pb_add_bisac_subjects_field` filter, which allows those with a licensed copy of the BISAC subject headers to display a multiple select instead of Pressbooks' default text input (see #637).
* **Enhancement:** Added the `pb_audience` field to the Book Information page to allow setting a book's target audience (see #638).
* **Enhancement:** The export metadata settings for all book contents are now fetched in a single query within `\Pressbooks\Book::getBookStructure()` (props to @monkecheese; see #633).
* **Enhancement:** The book language will now be set to the language selected when the book is registered (see #595).
* **Enhancement:** The Comments column on the Organize page will now be hidden if comments are disabled (see #644).
* **Enhancement:** Core textbox styles now apply to the equivalent `.bcc-*` selectors for improved compatibility with Pressbooks Textbook.
* **Enhancement:** Imported content can optionally be set to `published` status instead of `draft` (see #593).
* **Enhancement:** Front matter, chapter, and back matter types will now be imported from Pressbooks WXR files (see #601).
* **Enhancement:** Empty front matter, chapters, and back matter will now be imported from Pressbooks WXR files (see #592).
* **Enhancement:** Title display and export metadata will now be imported from Pressbooks WXR files (see #606).
* **Enhancement:** Completing an import from a Pressbooks WXR file will now correctly enumerate different content types instead of labelling all as chapters.
* **Enhancement:** Bold, italic, superscript, and subscript text is now properly imported from Word documents (props to @crism; see #629).
* **Enhancement:** Inline language attributes are now properly imported from Word documents (props to @crism; see #630 and #639).
* **Enhancement:** Removed the Postmark-specific `wp_mail()` override (see #587).
* **Enhancement:** Export dependency errors are now grouped intelligently into a single alert (see #646).
* **Enhancement:** Javascript and SCSS files are now validated on pull requests using [Hound](https://houndci.com) (see #617).
* **Enhancement:** The sender address and name used for emails from a Pressbooks instance can now be customized by defining constants for `WP_MAIL_FROM` and `WP_MAIL_FROM_NAME` (see #663).
* **Fix:** To prevent an erroneous reversion to the WordPress < 3.5 uploads directory structure, `\Pressbooks\Utility\get_media_prefix()` now checks for the `ms_files_rewriting` site option instead of for the `blogs.dir` directory.
* **Fix:** The custom CSS file URL scheme is now relative, which should prevent mixed content errors under some circumstances (see #599).
* **Fix:** Fixed an undefined index error in mPDF theme options (props to @monkecheese; see #613).
* **Fix:** Fixed a database max key length error when creating the catalog tables (see #589).
* **Fix:** Removed the Pressbooks plugin installer tab, which was preventing plugin searching from being conducted (see #596).
* **Fix:** Deleted books will now be removed from user catalogs (see #412).
* **Fix:** Fixed an issue where hyphenation would be enabled in Prince exports even if it was disabled in theme options (see #645).
* **Fix:** Fixed an issue where custom running content was being displayed in the wrong place (see #623).
* **Fix:** Fixed an issue where OpenOffice files would not be properly exposed for download (see #649).
* **Fix:** The time allowed for an mPDF export to complete has been conditionally increased to account for certain edge cases (props to @bdolor; see #652).
* **Fix:** Added between section numbers and titles in the mPDF TOC (props to @bdolor; see #653).
* **Fix:** We now use the https endpoint for the Automattic LaTeX server to avoid mixed content errors (props to @bdolor; see #651).
* **Fix:** Publisher logos inserted via `add_theme_support( 'pressbooks_publisher_logo' )` hook are now properly copied into EPUB outputs (see #666).

= 3.9.6 =
**NOTICE:** Pressbooks now requires [WordPress 4.7 "Vaughan"](https://wordpress.org/news/2016/12/vaughan/).
**NOTICE:** Pressbooks now requires [PrinceXML 11](http://www.princexml.com/download/) for PDF exports.

* **Feature:** If you select a language on the book information page and the WordPress language pack for that language is available but not installed, Pressbooks will try to install it for you (and politely inform you if it can't).
* **Feature:** Added Hindi language support using [Noto Sans Devanagari](https://www.google.com/get/noto/#sans-deva) and [Noto Serif Devanagari](https://www.google.com/get/noto/#serif-deva).
* **Enhancement:** The whitelist-based theme filtering behaviour of Pressbooks =< 3.9.5.1 has been removed; all book themes are now available for use in all books (if network enabled), and all non-book themes are now available for use on the root blog (if network enabled). If you wish to restrict theme availability further, you can do so by adding additional filters to the `allowed_themes` filter hook.
* **Enhancement:** Added the ability to retry asset fetching during EPUB export in the event of server errors (props to @nathanielks, see [7344674](https://github.com/pressbooks/pressbooks/commit/7344674f823517ed7eb2fef462a4795f7182ce56))
* **Enhancement:** Added filter and action hooks to support the addition of import modules via third-party plugins (props to @monkecheese, see [4d7ca64](https://github.com/pressbooks/pressbooks/commit/4d7ca649ec3b6c05c40e1c5bb8f92beb1de5ea30)).
* **Enhancement:** Added the `pb_disable_root_comments` filter hook for control over comment display on the root blog (defaults to `true` -- disable comments -- as Pressbooks Publisher does not support comments).
* **Enhancement:** Added a link from the user's catalog logo or profile image to their profile URL, if set.
* **Enhancement:** Added variables for textbox header font size and text alignment to book theme partials.
* **Enhancement:** Removed our custom `user_interface_lang` setting in favour of WordPress 4.7's user locale.
* **Enhancement:** Removed `\Pressbooks\utility\multi_sort()` in favour of WordPress 4.7's shiny new `wp_list_sort()`.
* **Enhancement:** Removed our last remaining use of `get_blog_details`, which will be deprecated in a forthcoming WordPress release.
* **Fix:** Fixed an issue which prevented the Pressbooks admin color scheme from being applied upon manual plugin activation.
* **Fix:** Fixed an issue which prevented the book name from properly updating under some circumstances.
* **Fix:** Fixed some styles on the registration screen in the Pressbooks Publisher theme (now at v3.0.1).

= 3.9.5.1 =
* **Enhancement:** Added [`pb_cover_image`](https://github.com/pressbooks/pressbooks/pull/540/) filter to improve support for networks which host uploaded content on a third-party server (props to @monkecheese).
* **Fix:** Fixed a discrepancy in the line height of PrinceXML PDF exports of books using Cardo as the body font which resulted from an invalid descender value.
* **Fix:** Fixed an issue where the Network Sharing & Privacy page would not update the associated site option value.
* **Fix:** Fixed the vertical alignment of the Facebook share button in the webbook theme (props to @colomet).

= 3.9.5 =
* **Enhancement:** The Pressbooks Publisher theme has been streamlined and refreshed.
* **Fix:** The version requirement for xmllint has been downgraded to 20706 to maintain RHEL 6 compatibility (props to @bdolor for the PR).

= 3.9.4.2 =
* **Feature:** It is now possibled to modify the default session configuration via the `pressbooks_session_configuration` filter hook (props to @monkecheese).
* **Feature:** The `pb_append_chapter_content` is now available in the mPDF exporter (props to @monkecheese).
* **Enhancement:** The `generator` meta property has been added to XHTML exports.
* **Fix:** A bug which resulted in anchors being added to internal links twice in EPUB exports has been resolved.

= 3.9.4.1 =
* **Feature:** The copyright string in the Pressbooks Publisher theme footer can now be customized via the `pressbooks_publisher_content_info` filter.
* **Feature:** The text that is displayed when there are no books in a Pressbooks Publisher catalog can now be customized via the `pressbooks_publisher_empty_catalog` filter.
* **Fix:** Updated a component of the Diagnostics page to remove a deprecation notice (props to @thomasdumm for the report).
* **Fix:** Fixed a glitch in the Pressbooks colour scheme.

= 3.9.4 =
* **Feature:** Pressbooks + Hypothesis: Version 0.4.8 of the [Hypothesis](https://hypothes.is) WordPress plugin now supports custom post types, and Pressbooks 3.9.4 adds Hypothesis support to all of ours (parts, chapters, front and back matter).
* **Feature:** Having a problem with Pressbooks? We've added a diagnostics page which is accessible from the 'Diagnostics' link in the footer of every dashboard screen. If you need to report a bug, copy your system configuration info from your Diagnostics page to help us help you resolve the issue more efficiently.
* **Enhancement:** `check_epubcheck_install` can now be overridden using the `pb_epub_has_dependencies` hook for use cases where EPUB validation is not required (props to @monkecheese for the PR).
* **Enhancement:** Some adjustments were made to the PDF output stylesheets for running headers and footers.
* **Fix:** Fixed a visual glitch by hiding the TinyMCE table editor's inline toolbar.

= 3.9.3 =
* **NOTE:** [Saxon-HE 9.7.0-10](https://sourceforge.net/projects/saxon/files/Saxon-HE/) is no longer bundled with Pressbooks and must be installed separately for ODT export support (see [Installation](http://docs.pressbooks.org/installation)).
* **Feature:** The copy on the publish page can now be replaced by adding a filter to the `pressbooks_publish_page` filter hook.
* **Feature:** If registration is enabled, a 'Register' button now appears on the front page of the Pressbooks Publisher theme.
* **Enhancement:** A URL sanitization routine has been added to the `\Pressbooks\Options` class.
* **Enhancement:** The methods of `\Pressbooks\Options` which list the options of various types (bool, string, float, etc.) are now optional, and the sanitize function now checks for each type before trying to sanitize it.
* **Enhancement:** The publish page has been refactored using the `\Pressbooks\Options` class.
* **Fix:** Unwanted validation warning emails will no longer be sent.

= 3.9.2.1 =
* **NOTE:** Pressbooks >= 3.9.2 requires [PrinceXML 20160929](http://www.princexml.com/latest/) or later.
* **Fix:** Fixed an issue where user actions on the Organize page would fail to update certain properties.

= 3.9.2 =
* **NOTE:** Pressbooks 3.9.2 requires [PrinceXML 20160929](http://www.princexml.com/latest/) or later.
* **Feature:** Added an export format for print-ready PDF, compatible with the [CreateSpace PDF Submission Specification](https://www.createspace.com/ServicesWorkflow/ResourceDownload.do?id=1583) (**Requires [PrinceXML 20160929](http://www.princexml.com/latest/) or later**).
* **Feature:** Added a button to the editor which lets you assign a custom class to any element.
* **Feature:** Simplified the Disable Comments feature, which can now be found under Sharing & Privacy settings.
* **Enhancement:** Added version-based dependency checks for all Pressbooks dependencies.
* **Enhancement:** Updated the TinyMCE Table Editor plugin to the latest version.
* **Enhancement:** Custom styles, table classes, row classes and cell classes are now filterable.
* **Fix:** Fixed an issue where email validation logs would not be sent.

= 3.9.1 =
* **Fix:** Fixed an issue where the htmLawed and PrinceXMLPHP dependencies were not being loaded properly.

= 3.9.0 =
* **Feature:** Added a web theme option to display the title of the current part in the webbook (props to @bdolor).
* **Feature:** Noto CJK fonts (required for Chinese, Japanese and Korean PDF output) are now downloaded only when needed from within Pressbooks, reducing the overall size of the Pressbooks download.
* **Feature:** Added a recompile routine for webbook stylesheets to allow more straightforward development (only enabled when `WP_ENV` is defined and set to `development`).
* **Enhancement:** Applied our [coding standards](https://github.com/pressbooks/pressbooks/blob/master/docs/coding-standards.md) across the board and added PHP_CodeSniffer to our CI routines.
* **Enhancement:** Added some unit tests.
* **Enhancement:** Moved the Pressbooks API to /vendor.
* **Enhancement:** Changed some colour variables for clarity.
* **Enhancement:** Added initial support for SVG LaTeX images in PDF exports (requires [QuickLaTex](https://wordpress.org/plugins/wp-quicklatex/)).
* **Enhancement:** Added some scaffolding to allow option defaults to be filtered in pages built using the new options class.
* **Enhancement:** The book information post is now created when a book is registered.
* **Fix:** Added missing methods which were triggering fatal errors in the Export Options page (props to @bdolor).
* **Fix:** Fixed in issue which prevented the Ebook paragraph separation theme option from being applied in Clarke.
* **Fix:** Fixed an issue where internal links from within part content were broken in EPUB.
* **Fix:** Fixed an issue where backslashes would be erroneously stripped when replacements were applied in the Search and Replace utility (props to @rootl for the bug report).
* **Fix:** Fixed an issue where the book title would not be updated on the first save.

= 3.8.1 =
* **Fix:** Internal links are now _actually_ fixed in EPUB exports.

= 3.8.0 =
* **Feature:** The redistribution option from [Pressbooks Textbook](https://github.com/BCcampus/pressbooks-textbook/), which allows a book administrator to share the latest export files of their book on the webbook cover page, has been migrated into Pressbooks and can be found under (Network) Settings -> Sharing and Privacy. Many thanks to @bdolor for developing this feature (and fixing a display bug in our implementation of it).
* **Feature:** Luther and all child themes now support searching within webbooks.
* **Feature:** The Pressbooks.com promotion on book covers can now be hidden using the `PB_HIDE_COVER_PROMO` constant.
* **Enhancement:** [Hypothesis](https://wordpress.org/plugins/hypothesis/) has been added to the supported plugins list, and the supported plugins list is now built more intelligently.
* **Enhancement:** The hard-coded default theme for new books has been replaced by the following logic: 1. Use `PB_BOOK_THEME` (if set); 2. Use `WP_DEFAULT_THEME` (if set); 3. Use Luther.
* **Enhancement:** Added the `pressbooks_register_theme_directory` action to support the registration of custom theme directores by third-party developers (props to @bdolor).
* **Enhancement:** Added support for testing PrinceXML's built-in [PDF profile](http://www.princexml.com/doc/properties/prince-pdf-profile/) support by setting the `PB_PDF_PROFILE` constant to the desired profile.
* **Enhancement:** Refactored generic shortcodes to allow testing and test were written for them.
* **Enhancement:** Switched from internal fork to dev-master of gridonic/princexmlphp and switched to versioned copy of pressbooks/saxonhe.
* **Enhancement:** The `\Pressbooks\Modules\ThemeOptions` class now supports the registration of custom tags by third-party developers.
* **Fix:** Removed a leftover conditional check for the `accessibility_fontsize` option in webbooks (props to @bdolor for the bug report).
* **Fix:** Internal links to parts now work in XHTML, PDF and EPUB exports.
* **Fix:** Fixed some faulty logic in the TOC collapse Javascript (props to @bdolor).
* **Fix:** Fixed some incorrect color values in the mobile admin bar.
* **Fix:** Fixed a misplaced comment in the conditional check for IE 9 in Pressbooks Book (props to @chrillep).
* **Fix:** Fixed a bug where protocol-relative images would not be exported properly in EPUB (props to @bdolor).

= 3.7.1 =
* **Fix:** Fixed a bug where increased font size would be applied to all PDF exports.

= 3.7.0 =
* **Feature:** Introduced `\Pressbooks\Options` class and rebuilt theme options using on this class.
* **Feature:** Introduced `\Pressbooks\Taxonomy` class and rebuilt front matter, chapter and back matter types using this class.
* **Feature:** Added support for custom base font size, line height, page margins, image resolution and running content in SCSS v2 themes for PDF.
* **Feature:** Enabled webbook collapsible TOC by default (as needed).
* **Feature:** Enabled webbook font size control by default.
* **Feature:** Added custom sidebar color for catalog (props to @monkecheese).
* **Enhancement:** Prince will now ignore self-signed certificates in a development environment.
* **Fix:** Fixed an admin style inconsistency introduced with WordPress 4.6.
* **Fix:** Fixed an error where SCSS v2 themes could not be imported into the Custom CSS editor.
* **Fix:** Added user feedback to allow recovery from JPEG errors (props to @bdolor).
* **Fix:** Added a call to `wp_cache_flush()` to fix an error during book creation.

= 3.6.3 =
* **Fix:** Fixed an error caused by the change to get_sites().

= 3.6.2 =
* Requires WordPress 4.6.
* **Fix:** Replaced deprecated wp_get_sites() function with get_sites() (props to @bdolor for the bug report).

= 3.6.1 =
* **Fix:** An issue where footnotes would not display in endnote mode has been resolved.
* **Fix:** An SCSS error in Luther has been resolved (props to @bearkrust for the bug report).

= 3.6.0 =
* Requires WordPress 4.5.3.
* **Feature:** Structural SCSS and supports are in place for the new book theme model (see http://pressbooks.org/core/2016/05/16/rethinking-book-themes/).
* **Feature:** Clarke 2.0 has been rebuilt on the new book theme model (see https://pressbooks.com/themes/clarke).
* **Feature:** Themes built on the new book theme model can display publisher logos on the title page via `add_theme_support( 'pressbooks_publisher_logo', [ 'logo_uri' => $logo_uri ] )`.
* **Feature:** Themes built on the new book theme model define support for global typography using `add_theme_support( 'pressbooks_global_typography', [ $language_codes ] )`.
* **Feature:** Custom post types, built-in taxonomies and custom taxonomies can now be imported from a Pressbooks or WordPress XML file using the filters `pb_import_custom_post_types` and `pb_import_custom_taxonomies` (props to @monkecheese).
* **Feature:** Filter hooks have been added which allow content to be appended to front matter, chapters and back matter via `pb_append_front_matter_content`, `pb_append_chapter_content` and `pb_append_back_matter_content` (props to @monkecheese).
* **Feature:** Network administrators can now clear all of a book's exports (this is useful for testing).
* **Enhancement:** The Export page is now responsive.
* **Enhancement:** `script.js` is no longer required for Prince exports (if the the file is not there it will no longer trigger an error).
* **Enhancement:** The `<base href="">` tag has been removed from XHTML outputs, which should make these files more functional in some cases (props to @bdolor).
* **Fix:** Search and Replace is now accessible to book administrators, not just network administrators.
* **Fix:** The broken Forum link in the Pressbooks menu has been replaced with a link to our Help page.

= 3.5.2 =
* **Feature:** Login screen logo and color scheme can now be changed via filters (see https://github.com/pressbooks/pressbooks/commit/d09a104bfbbe3ad00a108004d0375ad1f7057ae0).
* **Enhancement:** Google Fonts are now requested over https under all circumstances.
* **Enhancement:** Added some functionality to the Disable Comments plugin (props to @bdolor).
* **Fix:** Imports will no longer fail in certain environments (props to @monkecheese for the bug fix).
* **Fix:** Subsection titles are now properly sanitized for XHTML output.

= 3.5.1 =
* Requires WordPress 4.5.2.
* **Fixed:** Resolved a formatting issue on the Export page (props to @bdolor).
* **Under the Hood:** Added anchor, superscript and subscript buttons to core MCE routines (eliminating dependencies).

= 3.5.0 =
* FEATURE: Search and Replace functionality has been rebuilt and more closely integrated with Pressbooks core.
* FEATURE: Pressbooks plugins (specifications forthcoming) can now be activated at the book level by book administrators.
* FIXED: Some image asset paths were updated.
* FIXED: Default mPDF options were updated.
* UNDER THE HOOD: Pressbooks now bundles the WordPress API feature plugin (more to come on this front).
* UNDER THE HOOD: Our namespace is now \Pressbooks.

= 3.4.0 =
* Requires WordPress 4.5.1.
* FEATURE: OpenDocument (beta) is now available as an export format.
* ENHANCED: Plugin assets are now managed using Bower and compiled using gulp. Your Pressbooks dashboard will now load more efficiently (thanks to the @rootswp team for their development of this workflow).
* ENHANCED: All symbionts except for that weird ICML one are now managed using Composer.
* ENHANCED: `check_prince_install()` now tries to run `prince --version` instead of looking for the executable file.
* FIXED: The Tweet button had stopped working, so we replaced our previous sharing script with @ellisonleao's excellent [sharer.js](https://github.com/ellisonleao/sharer.js/).
* FIXED: Our fork of @johngodley's Search Regex plugin has been updated for PHP 7.0 compatibility (props to @r66r for the bug report).

= 3.3.2 =
* FIXED: Themes were not appearing to be network enabled due to changes introduced in https://core.trac.wordpress.org/ticket/28436.

= 3.3.1 =
* FIXED: The custom logo feature introduced in v3.3.0 now displays logos at a more reasonable size.
* FIXED: Some extraneous files were bundled in v3.3.0. They are gone now.
* FIXED: An extra line break was introduced to the Export screen in v3.3.0. It is gone now too.

= 3.3.0 =
* Requires WordPress 4.5.
* ICML is now an experimental export format (see http://pressbooks.com/blog/discontinuing-support-for-icml-exports-on-april-12/).
* Added support for WordPress core's custom logo in Pressbooks Publisher.
* Added the TinyMCE background color button.
* Allow a user to choose their password when registering.
* Allow a network administrator to replace the Pressbooks News dashboard feed with their own RSS feed or disable the dashboard feed entirely.
* Fixed an issue where the "Show Title" checkbox on the "Organize" page had no effect (props to @sswettenham for the bug report).
* Fixed an issue where uploaded media were not attached to their parent Front Matter, Chapter or Back Matter.
* Internal dependencies are now managed using [Composer](https://getcomposer.org).

= 3.2.0 =
* Requires WordPress 4.4.2.
* Added Google Analytics support at the network level (subdomain and subdirectory installs) and the book level (subdomain installs only).
* Added support for installs that use SSL (props to @bdolor for contributions).
* Added localization support for strings (currently, "Chapter" and "Part") in book stylesheets.
* Added localization support for the Pressbooks "freebie" notice.
* Clarified new user and book registration text.
* Set timezone on export page based on root site settings (props to @chrillep for the bug report).
* Enhanced image display in exports.
* Expanded code coverage.
* Fixed an issue where footnote anchors would not be properly created when importing a Word document (thanks to @crism for the report and the contribution).
* Fixed an issue where clicking 'Show in Catalog' would not work (props to @colomet for the bug report).
* Fixed an issue where the "My Books" button would appear in Pressbooks Publisher for logged-in users with no books.
* Fixed the way the PB_PLUGIN_DIR and PB_PLUGIN_URL constants are defined to support installations of Pressbooks where plugins and themes are symlinked.

= 3.1.2 =
* Requires WordPress 4.4.1.
* Added internal links (anchors) to the built in 'Insert/edit Link' dialog.
* Added admin notices to indicate the success or failure of some AJAX actions which do not produce a visible result.
* Fixed an issue with EPUB validation introduced by WordPress 4.4's implementation of the srcset attribute.
* Fixed an issue where a dynamically-generated webBook stylesheet would be erroneously loaded.
* Fixed an issue with image paths in Luther webBook stylesheet (props to @bdolor for the bug report).
* Fixed an issue that caused ODT exports to fail in a particularly undignified manner.
* Fixed an issue where PDF themes would not be imported for editing properly when using the Pressbooks Custom CSS theme.
* Expanded test suites.

= 3.1.1 =
* Fixed an issue where custom web book themes would not be properly loaded.
* Updated the PB_PLUGIN_VERSION constant, which slipped under our radar when we released Pressbooks 3.1.

= 3.1 =
* Requires WordPress 4.4.
* Added a new Textboxes menu in TinyMCE which supports some new types of textboxes in addition to standard and shaded.
* Added support for assigning classes to tables within the TinyMCE Table Editor and removed some unnecessary features from it.
* Added a new Greek language font.
* Moved the mPDF library to an external plugin, [Pressbooks mPDF](https://wordpress.org/plugins/pressbooks-mpdf).
* Localized strings within some of our TinyMCE plugins. More to come.
* Improved SCSS theme structure and SCSS compilation routines.
* Improved XSL file for ODT export.
* Improved some TinyMCE styles.
* Fixed an issue where activating a non-SCSS theme would cause an error.
* Fixed an issue where loading the Search and Replace tool would cause an error (props to @rootl for the bug report).
* Updated some assets.

= 3.0 =
* SASS-y themes: book themes are now built with SASS (specifically the SCSS variant) and compiled for export or web display using either the bundled scssphp compiler (https://github.com/leafo/scssphp/) or the SASS PHP extension if installed (https://github.com/sensational/sassphp). See `/docs/themes-book.txt` for details if you are developing your own themes.
* Global Typography: users can add fonts to display Ancient Greek, Arabic, Biblical Hebrew, Chinese (Simplified or Traditional), Coptic, Gujarati, Japanese, Korean, Syriac, Tamil or Tibetan in any theme across all standard export formats via the Theme Options page.
* EPUB 3: the current version of the EPUB standard is now fully supported and will soon become Pressbooks' default EPUB export format.
* Added support for importing book information from a Pressbooks XML file.
* Added support for persistent export format selections on the Export page.
* Added the ability to show or hide front matter, chapter and back matter titles on the Organize page.
* Added initial support for unit testing.
* Requires PHP 5.6 (this can be overridden by setting `$pb_minimum_php` in wp-config.php, but we do not encourage this).
* Updated the Prince command line wrapper to support Prince 10r5.
* Updated export icons to support Retina screens.
* Fixed an issue where Norwegian localization files were not being properly loaded.
* Fixed an issue where the xml:lang attribute would set to `en` regardless of the book language.
* Fixed an issue that prevented Prince from loading its built-in hyphenation dictionaries.
* Fixed an issue with Kindle exports in bundled book themes.
* Fixed an issue with multi-level TOC styling in bundled book themes.
* Fixed an issue with EPUB images.
* Fixed some PHP warnings.
* Refactored some code for consistent namespacing and other improvements.
* Various localization updates.
* Various performance enhancements.

= 2.7.2 =
* Requires WordPress 4.3.1.
* Added MCE Anchor Button (migrated from Pressbooks Textbook, props to @bdolor).
* Fixed an issue where the book language could be incorrectly set to Afrikaans if the network language was undefined.
* Fixed an issue where loading a user's catalog would call memory-intensive functions repeatedly (props to @connerbw).
* Suppressed unhelpful errors when calling getSubsections() function (props to @connerbw).

= 2.7.1 =
* Fixed an issue where changes made with the Search & Replace tool would not be saved (props to @connerbw).
* Fixed an issue where users without super admin privileges would be incorrectly prevented from using the Import or Search & Replace tools.
* Fixed a display bug in recent builds of Google Chrome (props to @connerbw).

= 2.7 =
* Major cleanup of the administration interface.

= 2.6.7 =
* Added the ability to edit a table's class in the MCE Table Button's properties editor.
* Fixed an issue where Chinese would appear as the default user interface language.
* Fixed an issue where disabling social media sharing buttons would only disable Facebook (props to @colomet for the bug report).
* Updated localizations.

= 2.6.6 =
* Exporting a MOBI file no longer requires you to export an EPUB file also.

= 2.6.5 =
* Fixed a number of issues with multi-level TOC parsing.
* Fixed an issue where internal links on subdirectory installs were not being properly modified for PDF output (props to @bdolor).

= 2.6.4 =
* Added support for audio shortcodes in EPUB3 (props to @jflowers45).
* Modified login buttons to redirect users to the page they were viewing after login rather than force redirecting them to their dashboard (props to @marcusschiesser for the feature request).
* Fixed an issue where PDF exports were not respecting user-defined widow and orphan settings.
* Fixed an issue where unsupported @font-face declarations where being used in mPDF exports (props to @jflowers45 for the bug report and @bdolor for fixing it).
* Fixed an issue where updating a book's URL would break permalinks to front matter, back matter and parts (props to @programmieraffe for the bug report).
* Removed the WordPress contextual help button to avoid confusion on the dashboard (props to @colomet for noting its presence).

= 2.6.3 =
* Fixed issue with self-closing tags introduced in 2.6.1.

= 2.6.2 =
* Fixed issues with character encoding and improperly formed <br /> tags introduced in 2.6.1.

= 2.6.1 =
* Fixed issues with subsection parsing where <h1> tags had inline styles or were wrapped in other block elements.
* Fixed an issue where changing a book's language to "English" as opposed to "English (United States)" would fail to override the network's language setting.
* Updated documentation.

= 2.6 =
* Requires WordPress 4.3.
* The language selected on the book info page now applies to the book's webbook display.
* The language selected on the network settings page now applies automatically to new books and users.
* The language selected on a user's profile now overrides the network and book languages when they view the Pressbooks dashboard.

= 2.5.4 =
* Requires WordPress 4.2.4.
* Added Disable Comments (migrated from Pressbooks Textbook, props to @bdolor and the plugin's creators).
* Added a warning message when users upload a cover image above the recommended size.
* Optimized \Pressbooks\Book::getBookStructure() so as to only fetch export status during export routines (props to @bracken).
* Fixed a conflict with Jetpack (props to @programmieraffe for the bug report).
* Fixed an issue where chapters were being number in mPDF TOCs regardless of user preference (props to @bdolor for the fix and to @sswettenham for the bug report).
* Fixed an issue where sections would be parsed unnecessarily in webbooks (props to @bracken).
* Fixed two issues related to permissive private content (props to @marcusschiesser for the bug reports).
* Fixed an issue that caused a recursion during PDF export (props to @bseeger for the bug report).

= 2.5.3 =
* Added option to allow logged-in subscribers, contributors and authors to view a book's private content (props to @marcusschiesser for the feature request).
* Fixed an issue where the webbook TOC would not be displayed for any user who was not logged in (props to @sswettenham for the bug report).
* Fixed an issue where the media folder was not being deleted after ODT exports without a cover image.

= 2.5.2 =
* Added MCE Superscript & Subscript Buttons (migrated from Pressbooks Textbook, props to @bdolor and the plugin's creators).
* Improved ODT export: temporary files are now deleted when export fails (props to @sswettenham for the bug report).
* Improved user catalog: book covers are now clickable links (props to @kdv24).
* Improved user catalog: sidebars are sized to fit content instead of being restricted to window height (props to @changemachine).
* Fixed an issue where private chapters would appear in webbook TOC for logged-in users without the permissions to actually view them (props to @marcusschiesser for the bug report).

= 2.5.1 =
* Added MCE Table Editor (migrated from Pressbooks Textbook, props to @bdolor and the plugin's creators).
* Added support for excluding root domains _and_ subdomains in `show_experimental_features()` function.
* Added the ability to toggle social media integration on or off in webbooks (props to @bdolor).
* Added the ability to restrict specific network administrators' access to some network administration pages.
* Added a note in readme.txt indicating that `php5-xsl` is a required extension for certain exports (props to @jflowers45).
* Added a function to intelligently load plugins in `/symbionts` so as to avoid conflicts (props to @bdolor and the Pressbooks Textbook team for providing the basis for this).
* Forced Google webfonts to load via SSL (props to @bdolor).
* Improved editor style so that large images fit the editing window (props to @hughmcguire).
* Improved Javascript related to the sidebar table of contents in webbooks (props to @changemachine and @kdv24).
* Improved logic related to maximum import size reporting (props to @jflowers45).
* Improved styles associated with the accessibility plugin (props to @bdolor).
* Improved XSL for ODT export.
* Restored login screen branding in Pressbooks Publisher 2.0.
* Restored user catalog links in Pressbooks Publisher 2.0.
* Fixed a database error in user catalogs (props to @bdolor for the bug report).
* Fixed an issue where books would overlap on the user catalog page (props to @bracken and @changemachine).
* Fixed an issue where cover images and LaTex images would be omitted from ODT exports (props to @bdolor for the bug report and for assistance in solving this).
* Fixed an issue where embedded audio files would be hidden in exports because of an inline style (props to @bdolor).
* Fixed an issue where the `introduction` class would not be applied in certain exports.
* Fixed an issue where exports would fail because the `get_user_by` function was being improperly namespaced (props to @borayeris for the bug report).

= 2.5 =
* Requires WordPress 4.2.2.
* New root theme, Pressbooks Publisher 2.0. Pressbooks Publisher One has been deprecated and is now available (unsupported) [here](https://github.com/pressbooks/pressbooks-publisher-one/).
* Added centralized `show_experimental_features()` function to control where such things appear.
* Added experimental PDF export via mPDF as an open source alternative to Prince (props to @bdolor).
* Added fallbacks for title, author and cover image fetching in `getBookInformation()` function.
* Improved image fetching in ODT export (props to @bdolor).
* Improved import of Pressbooks XML files (props to @bdolor).
* Fixed issue where the API could show chapters as appearing in the wrong part (props to @bdolor).
* Fixed issue where entities would be improperly loaded in XML document in ODT export (props to @bdolor).
* Fixed issue with the network administration menu in the admin bar.
* Fixed issue with spacing and punctuation in webbook license module output.

= 2.4.5 =
* Requires WordPress 4.2.1.

= 2.4.4 =
* Requires WordPress 4.2.
* Added experimental ODT export capability.
* Fixed issue where useful backslashes were stripped on import (props to @lukaiser for identifying this issue).

= 2.4.3 =
* Requires WordPress 4.1.2.
* Removed Hpub export routines.
* Made links inside the `[footnote]` shortcode clickable (props to @bdolor).
* Added accessibility plugin to allow font size increases in webbook and PDF exports (props to @BakingSoda and @bdolor).
* Added some instructional text to Book Info page.
* Fixed character encoding issue with the TOC display of subsection titles.
* Fixed internal links for subdirectory installs within PDF exports (props to @bdolor).
* Fixed issue with catalog page in WebKit browsers (props to @bdolor).
* Fixed potential XSS attack via `remove_query_arg` (see: https://blog.sucuri.net/2015/04/security-advisory-xss-vulnerability-affecting-multiple-wordpress-plugins.html; props to @bdolor).
* Fixed variable-related warnings on RESTful API when debugging mode is enabled (props to @julienCXX).
* Fixed XHTML export issue with respect to determining the introduction part or chapter for page numbering.
* Updated included custom-metadata plugin to fix `array_reverse` bug (@props to bdolor).
* Swedish translation (props to @chrillep).

= 2.4.2 =
* Fixed licenses.
* Added child theme support to collapsible TOC functionality (props to @bdolor).

= 2.4.1 =
* Fixed issue with improperly parsed sections in chapters and back matter.

= 2.4 =
* Requires WordPress 4.1.
* Refined export logic to ensure that parts are handled properly under all circumstances.
* Refined parsing of chapter subsections; this feature no longer requires the use of the `<section>` tag.
* Subsections are now parsed in front- and back-matter as well.
* Support for a centralized fonts folder in the themes directory.
* Fixed bug that broke the running head in PDF exports.
* Fixed bug that broke internal links in PDF exports.
* Fixed bug that caused the Chapter Types menu item to be displayed twice for certain users.
* Beta Pressbooks API (props to @bdolor; see http://pressbooks.com/api/v1/docs).
* Collapsible TOCs for webbooks (props to @drlippman).
* Import enhancements (props to @bdolor).
* EPUB export enhancements (props to @bdolor).

= 2.3.3 =
* Compatibility with WordPress 4.0.
* Fixed some issues with our experimental EPUB3 export (props to @bdolor).
* Enhancements to WXR and EPUB import (props to @bdolor and @drlippman).
* Added support for contributing authors in webbooks and exports (props to @bdolor).
* Added some new translation files.

= 2.3.2 =
* Cleaner print output from webbooks.
* Ebook theme option to skip line between paragraphs is now honored in all themes.
