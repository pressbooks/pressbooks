=== Pressbooks ===

Contributors: Pressbooks <code@pressbooks.com>
Version: 2.5
Tags: ebooks, publishing, webbooks
Requires at least: 4.2.2
Tested up to: 4.2.2
Stable tag: trunk
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

Our main communication channel for the Pressbooks plugin is [Google Groups](http://groups.google.com/group/pressbooks).

== Contributors ==

All Pressbooks code is copyright Book Oven Inc. Contributors are acknowledged in the "docs/contributors.txt" file, not
in source code headers.

== Installation ==

IMPORTANT!

 * Do not install Pressbooks on an existing WordPress blog -- create a new WordPress install instead.
 * Pressbooks works with PHP 5.4.x and WordPress 4.2.2. Lower versions are not supported.

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

 * For PDF export install [Prince](http://pressbooks.com/prince) (note: this is not free software) - Version 9.0
 * For PDF export via mPDF ensure that the following folders have write access and/or they are owned by the appropriate user.See http://codex.wordpress.org/Changing_File_Permissions for more details on adjusting file permissions.
   + wp-content/plugins/pressbooks/symbionts/mpdf/ttfontdata
   + wp-content/plugins/pressbooks/symbionts/mpdf/tmp
   + wp-content/plugins/pressbooks/symbionts/mpdf/graph_cache
 * For MOBI export install [KindleGen](http://www.amazon.com/gp/feature.html?docId=1000765211) - Version 2.9
 * For EPUB validation install [EpubCheck](http://code.google.com/p/epubcheck/) - Version 3.0.1
 * For XML validation install [xmllint](http://xmlsoft.org/xmllint.html) - Version 20800

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
	define( 'PB_EPUBCHECK_COMMAND', '/usr/bin/java -jar /home/dac514/bin/epubcheck-3.0-RC-1/epubcheck-3.0-RC-1.jar' );
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

TK.

== Changelog ==

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
