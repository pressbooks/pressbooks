=== Pressbooks ===

Contributors: Pressbooks <code@pressbooks.com>
Version: 3.9.0
Tags: ebooks, publishing, webbooks
Requires at least: 4.6.1
Tested up to: 4.6.1
Version: 3.9.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Pressbooks is an open source book publishing tool built on a WordPress multisite platform. Pressbooks outputs books in
multiple formats, including PDF, EPUB, MOBI, web, and a variety of XML flavours, using a theming/templating system,
driven by CSS. For more information, visit http://pressbooks.com.

== Maintenance and Support ==

Contact us about maintenance and support contracts if you are installing Pressbooks on your own servers, or if you would
like Pressbooks to run a dedicated instance for you on our servers. You can reach us at: sales@pressbooks.com.

== Communication ==

Our main communication channel for the Pressbooks plugin is [GitHub](https://github.com/pressbooks/pressbooks/issues). You can post issues or ask questions there.

== Contributors ==

All Pressbooks code is copyright Book Oven Inc. Contributors are acknowledged in the "docs/contributors.txt" file, not
in source code headers.

== Installation ==

IMPORTANT!

 * Do not install Pressbooks on an existing WordPress blog -- create a new WordPress install instead.
 * Pressbooks works with PHP 5.6.x and WordPress 4.6.1. Lower versions are not supported. If you wish to run Pressbooks in an environment where PHP < 5.6, you can add a line to wp-config.php as follows:

	$pb_minimum_php = '5.4';

However, we encourage you to upgrade your environment instead as [PHP 5.4 is no longer supported](http://php.net/supported-versions.php).

*Part 1, WordPress generic:*

 1. Install WordPress using the [Famous 5-Minute Install](http://codex.wordpress.org/Installing_WordPress).

 2. [Create a Network](http://codex.wordpress.org/Create_A_Network) of WordPress sites, i.e.:

 3. Edit the wp-config.php file and add the following:

    `define('WP_ALLOW_MULTISITE', true);`

 4. Login to the WordPress admin area. Navigate to Tools → Network Setup, click Install.

 5. Complete the steps printed on the screen (i.e. edit your `wp-config.php` and `.htaccess files` with the information
    provided.)

*Part 2, Pressbooks specific:*

 1. Copy/move Pressbooks plugin files to: __PATH_TO_YOUR_SITE__/wp-content/plugins/pressbooks/*.

 2. Log out, log in, navigate to: My Sites → Network Admin → Dashboard.

 3. Navigate to: Plugins → Installed Plugins.

 4. Network Enable "Pressbooks."

 5. Navigate to: Themes → Installed Themes.

 6. Network Enable "Luther", "Clarke", "Donham", "Fitzgerald", "Austen", "Pressbooks Publisher One", and any other
    Pressbooks theme you want to use.

 7. Navigate to: Settings → Network Settings.

 8. Pick the most appropriate Registration Setting:
    + User accounts may be registered. (do not use this setting, since it will not allow users to create new books)
    + Logged in users may register new sites. (if you are a publisher using Pressbooks as a production tool, this is the
      best setting: it allows network administrators to add new users, who can then create books/sites. However,
      registration is not available to the public.)
    + Both sites and user accounts can be registered. (use this setting if you intend on offering a publishing-platform
      open to the public, such as Pressbooks.com)

 9. Navigate to: My Books → __YOUR_SITE__ → Dashboard

 10. Navigate to: Appearance. Activate "Pressbooks Publisher One"

 11. Navigate to: My Books → Network Admin → Sites

 12. Add a new site (this will be your first book).

 13. Navigate to: My Books → __YOUR_FIRST_BOOK__

 14. Navigate to: Book Information. Make sure to fill out Title, Author and Publication Date.

 15. Navigate to: Text → Organize. Make sure some content is selected for export.

*Part 3, Pressbooks dependencies:*

 * For PDF export install [Prince](http://pressbooks.com/prince) (note: this is not free software) - Version 10r7
 * For PDF export via mPDF install the [Pressbooks mPDF plugin](https://wordpress.org/plugins/pressbooks-mpdf). You will also need to ensure that the following folders have write access and/or they are owned by the appropriate user. See http://codex.wordpress.org/Changing_File_Permissions for more details on adjusting file permissions.
   + wp-content/plugins/pressbooks-mpdf/symbionts/mpdf/ttfontdata
   + wp-content/plugins/pressbooks-mpdf/symbionts/mpdf/tmp
   + wp-content/plugins/pressbooks-mpdf/symbionts/mpdf/graph_cache
 * For MOBI export install [KindleGen](http://www.amazon.com/gp/feature.html?docId=1000765211) - Version 2.9
 * For EPUB validation install [EpubCheck](https://github.com/idpf/epubcheck) - Version 4.0
 * For XML validation install [xmllint](http://xmlsoft.org/xmllint.html) - Version 20800
 * It is recommended that you install [sassphp](https://github.com/sensational/sassphp) for SASS compilation; however, Pressbooks includes a bundled compiler, [scssphp](https://github.com/leafo/scssphp/), and will fall back to this if sassphp is absent.
 * Certain Linux installations do not ship with the php5-xsl library enabled by default.  If you attempt to export an ePub and get a either a white screen with minimal text, or a "Fatal error: Class 'XSLTProcessor' not found" error, you may need to run a command like "apt-get install php5-xsl"

Unlisted versions are not supported. Upgrade/downgrade accordingly.

Once installed, define the following wp-config.php variables. The defaults are:

	define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
	define( 'PB_KINDLEGEN_COMMAND', '/opt/kindlegen/kindlegen' );
	define( 'PB_EPUBCHECK_COMMAND', '/usr/bin/java -jar /opt/epubcheck/epubcheck.jar' );
	define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );


Example config files for a dev site hosted at http://localhost/~dac514/textopress/

### wp-config.php file [snippet]: ###

	/**
	 * For developers: WordPress debugging mode.
	 *
	 * Change this to true to enable the display of notices during development.
	 * It is strongly recommended that plugin and theme developers use WP_DEBUG
	 * in their development environments.
	 */
	define('WP_DEBUG', true);
	define('WP_DEBUG_LOG', true);

	/**
	 * Multi-site support, Part 1
	 */
	define('WP_ALLOW_MULTISITE', true);

	/**
	 * Multi-site support, Part 2
	 */
	define('MULTISITE', true);
	define('SUBDOMAIN_INSTALL', false);
	$base = '/~dac514/textopress/';
	define('DOMAIN_CURRENT_SITE', 'localhost');
	define('PATH_CURRENT_SITE', '/~dac514/textopress/');
	define('SITE_ID_CURRENT_SITE', 1);
	define('BLOG_ID_CURRENT_SITE', 1);

	/**
	 * Pressbooks
	 */
	define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
	define( 'PB_KINDLEGEN_COMMAND', '/home/dac514/bin/kindlegen' );
	define( 'PB_EPUBCHECK_COMMAND', '/usr/bin/java -jar /home/dac514/bin/epubcheck-4.0/epubcheck-4.0.jar' );
	define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );

	/**
	 * Optional definitions
	 */
	// define( 'WP_POST_REVISIONS', 5 ); // Limit post revisions: int or false
	// define( 'EMPTY_TRASH_DAYS', 1 ); // Purge trash interval
	// define( 'AUTOSAVE_INTERVAL', 60 ); // Autosave every N seconds

	/* That's all, stop editing! Happy blogging. */


### .htaccess file: ###

	RewriteEngine On
	RewriteBase /~dac514/textopress/
	RewriteRule ^index\.php$ - [L]

	# add a trailing slash to /wp-admin
	RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

	RewriteCond %{REQUEST_FILENAME} -f [OR]
	RewriteCond %{REQUEST_FILENAME} -d
	RewriteRule ^ - [L]
	RewriteRule  ^[_0-9a-zA-Z-]+/(wp-(content|admin|includes).*) $1 [L]
	RewriteRule  ^[_0-9a-zA-Z-]+/(.*\.php)$ $1 [L]
	RewriteRule . index.php [L]

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

Please note that Pressbooks 3.8.0 will be our last release on WordPress.org. Future updates will be delivered via GitHub [releases](https://github.com/pressbooks/pressbooks/releases). To facilitate this process, you can install the [GitHub Updater](https://github.com/afragen/github-updater) plugin.

== Changelog ==

= 3.9.0 =
* **Feature:** Added a web theme option to display the title of the current part in the webbook (props to @bdolor).
* **Feature:** Noto CJK fonts (required for Chinese, Japanese and Korean PDF output) are now downloaded only when needed from within Pressbooks, reducing the overall size of the Pressbooks download.
* **Feature:** Added a recompile routine for webbook stylesheets to allow more straightforward development (only enabled when `WP_ENV` is defined and set to `development`).
* **Enhancement:** Applied our [coding standards](https://github.com/pressbooks/pressbooks/blob/master/docs/coding-standards.md) across the board and added PHP_CodeSniffer to our CI routines.
* **Enhancement:** Added some unit tests.
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
* **Fix:** Added a call to `wp_flush_cache()` to fix an error during book creation.

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
