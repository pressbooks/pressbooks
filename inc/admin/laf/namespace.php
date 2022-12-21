<?php
/**
 * Administration interface look and feel.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.NonceVerification.Missing
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized

namespace Pressbooks\Admin\Laf;

use function Pressbooks\Admin\NetworkManagers\is_restricted;
use function Pressbooks\PostType\get_post_type_label;
use function Pressbooks\Sanitize\sanitize_string;
use function Pressbooks\Utility\str_starts_with;
use PressbooksMix\Assets;
use Pressbooks\Admin\ExportOptions;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;
use Pressbooks\Admin\PublishOptions;
use Pressbooks\Book;
use Pressbooks\BookDirectory;
use Pressbooks\Cloner\Cloner;
use Pressbooks\DataCollector\Book as DataCollector;
use Pressbooks\Metadata;
use WP_Error;

/**
 * @return bool
 */
function can_create_new_books() {
	// True if site registration is enabled
	// The value can be 'all', 'none', 'blog', or 'user', @see wp-signup.php
	$active_signup = apply_filters( 'wpmu_active_signup', get_site_option( 'registration', 'none' ) );
	if ( in_array( $active_signup, [ 'blog', 'all' ], true ) ) {
		return true;
	}
	return false;
}

/**
 * Add a custom message in admin footer
 */
function add_footer_link() {
	/**
	 * Add checks to determine what contact link returns
	 */
	$pb_network_contact_form = get_blog_option( get_main_site_id(), 'pb_network_contact_form' );
	$pb_network_contact_link = get_blog_option( get_main_site_id(), 'pb_network_contact_link' );

	if ( $pb_network_contact_form ) {
		$contact_link = network_home_url( '/#contact' );
	} else {
		if ( ! empty( $pb_network_contact_link ) ) {
			$contact_link = $pb_network_contact_link;
		} else {
			/**
			 * Filter the "Contact" link.
			 *
			 * @since 5.6.0
			 */
			$contact_link = apply_filters( 'pb_contact_link', '' );
		}
	}

	if ( $contact_link ) {
		$contact_link_href = sprintf(
			'&bull; <a href="%1$s">%2$s</a>',
			$contact_link,
			__( 'Contact', 'pressbooks' )
		);
	} else {
		$contact_link_href = '';
	}

	printf(
		'<span id="footer-thankyou">%1$s</span> &bull; %2$s &bull; %3$s &bull; %4$s &bull; %5$s %6$s <br/>',
		sprintf(
			__( 'Powered by %s', 'pressbooks' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				'https://pressbooks.com/',
				'Pressbooks'
			)
		),
		sprintf(
			'<a href="%1$s">%2$s</a>',
			'https://pressbooks.com/about/',
			__( 'About', 'pressbooks' )
		),
		sprintf(
			'<a href="%1$s">%2$s</a>',
			/**
			 * Filter the "Help" link.
			 *
			 * @since 5.6.0
			 */
			apply_filters( 'pb_help_link', 'https://pressbooks.com/support/' ),
			__( 'Guides and Tutorials', 'pressbooks' )
		),
		sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'options.php?page=pressbooks_diagnostics' ),
			__( 'Diagnostics', 'pressbooks' )
		),
		sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'options.php?page=pressbooks_sitemap' ),
			__( 'Site Map', 'pressbooks' )
		),
		$contact_link_href
	);
}

/**
 * Removes some default WordPress Admin Sidebar items and adds our own
 */
function replace_book_admin_menu() {

	// Note:
	// If $menu_slug is a URL and the URL has an ampersand in it make sure you use &amp; (and not simply &) so that aria-current works

	// Remove items we don't want the user to see.
	remove_submenu_page( 'index.php', 'my-sites.php' );
	remove_submenu_page( 'options-general.php', 'options-general.php' );
	remove_submenu_page( 'options-general.php', 'options-writing.php' );
	remove_submenu_page( 'options-general.php', 'options-reading.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	remove_submenu_page( 'options-general.php', 'options-media.php' );
	remove_submenu_page( 'options-general.php', 'options-permalink.php' );
	remove_submenu_page( 'options-general.php', 'privacy.php' );

	remove_menu_page( 'edit.php' );
	remove_menu_page( 'link-manager.php' );
	remove_menu_page( 'edit.php?post_type=page' );

	remove_submenu_page( 'tools.php', 'tools.php' );
	remove_submenu_page( 'tools.php', 'import.php' );
	remove_submenu_page( 'tools.php', 'export.php' );
	remove_submenu_page( 'tools.php', 'tools.php' );
	remove_submenu_page( 'tools.php', 'site-health.php' );

	// Organize
	$organize_page = add_menu_page( __( 'Organize', 'pressbooks' ), __( 'Organize', 'pressbooks' ), 'edit_posts', 'pb_organize', __NAMESPACE__ . '\display_organize', 'dashicons-book' );
	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $organize_page ) {
			if ( $hook === $organize_page ) {
				wp_enqueue_style( 'pb-organize' );
				wp_enqueue_script( 'jquery-blockui' );
				wp_enqueue_script( 'pb-organize' );
				wp_localize_script(
					'pb-organize', 'PB_OrganizeToken', [
						// Ajax nonces
						'reorderNonce' => wp_create_nonce( 'pb-organize-reorder' ),
						'showTitleNonce' => wp_create_nonce( 'pb-organize-showtitle' ),
						'postVisibilityNonce' => wp_create_nonce( 'pb-organize-visibility' ),
						'privacyNonce' => wp_create_nonce( 'pb-organize-book-privacy' ),
						'wordCountNonce' => wp_create_nonce( 'pb-update-word-count-for-export' ),
						'bookPrivate' => __( 'private', 'pressbooks' ),
						'bookPublic' => __( 'public', 'pressbooks' ),
						'updating' => [
							'book' => __( 'Updating book.', 'pressbooks' ),
							'chapter' => __( 'Updating chapters.', 'pressbooks' ),
							'part' => __( 'Updating part.', 'pressbooks' ),
							'frontmatter' => __( 'Updating front matter.', 'pressbooks' ),
							'backmatter' => __( 'Updating back matter.', 'pressbooks' ),
						],
						'success' => [
							'book' => __( 'The book has been successfully updated!', 'pressbooks' ),
							'chapter' => __( 'The chapters has been successfully updated!', 'pressbooks' ),
							'part' => __( 'The part has been successfully updated!', 'pressbooks' ),
							'frontmatter' => __( 'The front matter has been successfully updated!', 'pressbooks' ),
							'backmatter' => __( 'The back matter has been successfully updated!', 'pressbooks' ),
						],
						'failure' => [
							'book' => __( 'Sorry, the book could not be updated.!', 'pressbooks' ),
							'chapter' => __( 'Sorry, the chapters could not be updated.', 'pressbooks' ),
							'part' => __( 'Sorry, the part could not be updated.', 'pressbooks' ),
							'frontmatter' => __( 'Sorry, the front matter could not be updated.', 'pressbooks' ),
							'backmatter' => __( 'Sorry, the back matter could not be updated.', 'pressbooks' ),
						],
					]
				);
			}
		}
	);

	add_submenu_page(
		'pb_organize',
		__( 'Add Part', 'pressbooks' ),
		__( 'Add Part', 'pressbooks' ),
		'edit_pages',
		'post-new.php?post_type=part'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Add Chapter', 'pressbooks' ),
		__( 'Add Chapter', 'pressbooks' ),
		'edit_posts',
		'post-new.php?post_type=chapter'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Add Front Matter', 'pressbooks' ),
		__( 'Add Front Matter', 'pressbooks' ),
		'edit_posts',
		'post-new.php?post_type=front-matter'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Add Back Matter', 'pressbooks' ),
		__( 'Add Back Matter', 'pressbooks' ),
		'edit_posts',
		'post-new.php?post_type=back-matter'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Glossary Terms', 'pressbooks' ),
		__( 'Glossary Terms', 'pressbooks' ),
		'edit_posts',
		'edit.php?post_type=glossary'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Chapter Types', 'pressbooks' ),
		__( 'Chapter Types', 'pressbooks' ),
		'manage_network',
		'edit-tags.php?taxonomy=chapter-type&amp;post_type=chapter'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Front Matter Types', 'pressbooks' ),
		__( 'Front Matter Types', 'pressbooks' ),
		'manage_network',
		'edit-tags.php?taxonomy=front-matter-type&amp;post_type=front-matter'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Back Matter Types', 'pressbooks' ),
		__( 'Back Matter Types', 'pressbooks' ),
		'manage_network',
		'edit-tags.php?taxonomy=back-matter-type&amp;post_type=back-matter'
	);

	add_submenu_page(
		'pb_organize',
		__( 'Glossary Types', 'pressbooks' ),
		__( 'Glossary Types', 'pressbooks' ),
		'manage_network',
		'edit-tags.php?taxonomy=glossary-type&amp;post_type=glossary'
	);

	add_submenu_page( 'pb_organize', __( 'Trash' ), __( 'Trash' ), 'delete_posts', 'pb_trash', __NAMESPACE__ . '\display_trash' );

	add_action(
		'admin_enqueue_scripts', function ( $hook ) {
			if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
				$post_type = get_post_type();
				if ( in_array( $post_type, [ 'front-matter', 'chapter', 'back-matter' ], true ) ) {
					// post-visibility.js
					wp_enqueue_script( 'pb-post-visibility' );
				}
				if ( in_array( $post_type, [ 'back-matter' ], true ) ) {
					// post-back-matter.js
					wp_enqueue_script( 'pb-post-back-matter' );
				}
				wp_enqueue_script( 'pb-post-mathjax' );
			}
		}
	);

	// Book Information
	$book_info_url = book_info_slug();
	$bookinfo_page = add_menu_page( __( 'Book Info', 'pressbooks' ), __( 'Book Info', 'pressbooks' ), 'manage_options', $book_info_url, '', 'dashicons-info', 12 );
	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $bookinfo_page ) {
			if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
				$post_type = get_post_type();
				if ( 'metadata' === $post_type ) {
					wp_enqueue_script( 'pb-metadata' );
					wp_localize_script(
						'pb-metadata', 'PB_BookInfoToken', [
							'ajaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=pb_get_thema_subjects' ), 'pb-metadata' ),
							'institutionsAPI' => wp_nonce_url( admin_url( 'admin-ajax.php?action=pb_get_institutions' ), 'pb-metadata' ),
							'bookInfoMenuId' => preg_replace( '|[^a-zA-Z0-9_:.]|', '-', $bookinfo_page ),
							'selectInstitutionsText' => __( 'Choose institution(s)... ', 'pressbooks' ),
							'selectSubjectText' => __( 'Choose a subject…', 'pressbooks' ),
							'selectSubjectsText' => __( 'Choose some subject(s)…', 'pressbooks' ),
						]
					);
				}
			}
		}
	);

	add_submenu_page(
		$book_info_url,
		__( 'Contributors', 'pressbooks' ),
		__( 'Contributors', 'pressbooks' ),
		'manage_options',
		'edit-tags.php?taxonomy=contributor'
	);

	// Export
	$export_page = add_menu_page( __( 'Export', 'pressbooks' ), __( 'Export', 'pressbooks' ), 'edit_posts', 'pb_export', __NAMESPACE__ . '\display_export', 'dashicons-migrate', 14 );
	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $export_page ) {
			if ( $hook === $export_page ) {
				add_screen_option(
					'per_page', [
						'default' => 50,
						'option' => 'pb_export_per_page',
					]
				);
				wp_localize_script(
					'pb-export', 'PB_ExportToken', [
						'ajaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=export-book' ), 'pb-export' ),
						'bulkDeleteWarning' => __( 'Are you sure you want to delete these export files?', 'pressbooks' ),
						'maximumFilesWarning' => __( 'Up to 5 files can be pinned at once.', 'pressbooks' ),
						'maximumFileTypeWarning' => __( 'Cannot pin more than 3 of the same file type.', 'pressbooks' ),
						'pinsNonce' => wp_create_nonce( 'pb-export-pins' ),
						'redirectUrl' => admin_url( 'options.php?page=pb_export' ),
						'reloadSnippet' => '<em>(<a href="javascript:window.location.reload(true)">' . __( 'Reload', 'pressbooks' ) . '</a>)</em>',
						'tooManyExportsWarning' => __( 'Too many pinned files. Deselect one of the pinned files before attempting to export.', 'pressbooks' ),
						'unloadWarning' => __( 'Exports are not done. Leaving this page, now, will cause problems. Are you sure?', 'pressbooks' ),
					]
				);
				wp_enqueue_style( 'pb-export' );
				wp_enqueue_script( 'pb-export' );
				wp_deregister_script( 'heartbeat' );

			}
		}
	);

	// Import
	$import_page = add_menu_page( __( 'Import', 'pressbooks' ), __( 'Import', 'pressbooks' ), 'edit_posts', 'pb_import', __NAMESPACE__ . '\display_import', 'dashicons-migrate', 16 );
	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $import_page ) {
			if ( $hook === $import_page ) {
				wp_localize_script(
					'pb-import', 'PB_ImportToken', [
						'ajaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=import-book' ), 'pb-import' ),
						'redirectUrl' => admin_url( 'admin.php?page=pb_organize' ),
						'unloadWarning' => __( 'Imports are not done. Leaving this page, now, will cause problems. Are you sure?', 'pressbooks' ),
						'reloadSnippet' => '<em>(<a href="javascript:window.location.reload(true)">' . __( 'Reload', 'pressbooks' ) . '</a>)</em>',
						'ajaxSubmitMsg' => __( 'Saving settings', 'pressbooks' ),
					]
				);
				wp_enqueue_script( 'pb-import' );
				wp_deregister_script( 'heartbeat' );
			}
		}
	);

	// Publish
	$option = get_option( 'pressbooks_ecommerce_links', PublishOptions::getDefaults() );
	$page = new PublishOptions( $option );
	$page->init();
	wp_cache_delete( 'pressbooks_ecommerce_links_version', 'options' ); // WordPress Core caches this key in the "options" group
	$version = get_option( 'pressbooks_ecommerce_links_version', 0 );
	if ( $version < $page::VERSION ) {
		$page->upgrade( $version );
		update_option( 'pressbooks_ecommerce_links_version', $page::VERSION, false );
	}

	add_menu_page( __( 'Publish', 'pressbooks' ), __( 'Publish', 'pressbooks' ), 'edit_posts', 'pb_publish', [ $page, 'render' ], 'dashicons-products', 17 );

	// Privacy
	add_options_page( __( 'Sharing and Privacy Settings', 'pressbooks' ), __( 'Sharing &amp; Privacy', 'pressbooks' ), 'manage_options', 'pressbooks_sharingandprivacy_options', __NAMESPACE__ . '\display_privacy_settings' );

	// Export
	$option = get_option( 'pressbooks_export_options', ExportOptions::getDefaults() );
	$page = new ExportOptions( $option );
	$page->init();
	wp_cache_delete( 'pressbooks_export_options_version', 'options' );  // WordPress Core caches this key in the "options" group
	$version = get_option( 'pressbooks_export_options_version', 0 );
	if ( $version < $page::VERSION ) {
		$page->upgrade( $version );
		update_option( 'pressbooks_export_options_version', $page::VERSION, false );
	}

	add_options_page( __( 'Export Settings', 'pressbooks' ), __( 'Export', 'pressbooks' ), 'manage_options', 'pressbooks_export_options', [ $page, 'render' ] );

	// QuickLaTeX
	if ( function_exists( 'quicklatex_options_do_page' ) ) {
		add_options_page(
			__( 'QuickLaTeX', 'pressbooks' ),
			__( 'QuickLaTeX', 'pressbooks' ),
			'manage_options',
			'quicklatex-settings',
			'quicklatex_options_do_page'
		);
	}

	// Clone a Book
	if ( Cloner::isEnabled() && ( can_create_new_books() || is_super_admin() ) ) {
		$cloner_page = add_submenu_page( 'admin.php', __( 'Clone a Book', 'pressbooks' ), __( 'Clone a Book', 'pressbooks' ), 'read', 'pb_cloner', __NAMESPACE__ . '\display_cloner' );
		add_action(
			'admin_enqueue_scripts', function ( $hook ) use ( $cloner_page ) {
				if ( $hook === $cloner_page ) {
					wp_localize_script(
						'pb-cloner', 'PB_ClonerToken', [
							'ajaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=clone-book' ), 'pb-cloner' ),
							'redirectUrl' => admin_url( 'options.php?page=pb_cloner' ),
							'unloadWarning' => __( 'Cloning is not done. Leaving this page, now, will cause problems. Are you sure?', 'pressbooks' ),
							'reloadSnippet' => '<em>(<a href="javascript:window.location.reload(true)">' . __( 'Reload', 'pressbooks' ) . '</a>)</em>',
						]
					);
					wp_enqueue_script( 'pb-cloner' );
					wp_deregister_script( 'heartbeat' );
				}
			}
		);
	}

	// Catalog
	add_submenu_page( 'index.php', __( 'My Catalog', 'pressbooks' ), __( 'My Catalog', 'pressbooks' ), 'read', 'pb_catalog', '\Pressbooks\Catalog::addMenu' );
}

/**
 * Filters a screen option value before it is set.
 * Returning false to the filter will skip saving the current option.
 *
 * @param bool $default
 * @param $option
 * @param $value
 *
 * @return mixed
 */
function custom_screen_options( $default, $option, $value ) {
	if ( 'pb_export_per_page' === $option ) {
		return (int) $value;
	}
	return $default;
}

/**
 * Reorder the book administration menu.
 *
 * @since 5.0.0
 *
 * @param  array $menu_order An ordered array of menu items
 * @return array
 */
function reorder_book_admin_menu( $menu_order = [] ) {
	return [
		'index.php',
		'separator1',
		'pb_organize',
		'post.php',
		book_info_slug(),
		'themes.php',
		'pb_export',
		'pb_import',
		'pb_publish',
		'separator2',
		'plugins.php',
		'upload.php',
		'edit-comments.php',
		'users.php',
		'tools.php',
		'options-general.php',
	];
}

/**
 * The slug we use for Book Info menu is a variable URL
 *
 * @return string
 */
function book_info_slug() {
	$metadata_post_id = ( new Metadata )->getMetaPostId();
	$book_info_slug = ( ! empty( $metadata_post_id ) ) ?
		'post.php?post=' . $metadata_post_id . '&amp;action=edit' :
		'post-new.php?post_type=metadata';
	return $book_info_slug;
}

/**
 * Menu output hacks (fixes selected menus)
 *
 * @see \_wp_menu_output
 * @see wp-admin/menu-header.php
 *
 * @param string $file
 *
 * @return string
 */
function fix_parent_file( $file ) {
	global $submenu_file;

	// Move these sub menus under Organize
	$haystack = [
		'edit-tags.php?taxonomy=front-matter-type',
		'edit-tags.php?taxonomy=chapter-type',
		'edit-tags.php?taxonomy=back-matter-type',
		'edit-tags.php?taxonomy=glossary-type',
		'edit.php?post_type=front-matter',
		'edit.php?post_type=part',
		'edit.php?post_type=chapter',
		'edit.php?post_type=back-matter',
		'edit.php?post_type=glossary',
	];
	foreach ( $haystack as $i ) {
		if ( str_starts_with( $submenu_file, $i ) ) {
			return 'pb_organize';
		}
	}

	// Move these sub menus under Book Info
	$book_info_slug = book_info_slug();
	$haystack = [
		'edit-tags.php?taxonomy=contributor',
	];
	foreach ( $haystack as $i ) {
		if ( str_starts_with( $submenu_file, $i ) ) {
			return $book_info_slug;
		}
	}

	// wp-admin/post.php changes Book Info menu, put it back to how it was
	if ( $file === 'edit.php?post_type=metadata' ) {
		$submenu_file = $book_info_slug;
		return $book_info_slug;
	}

	return $file;
}

/**
 * More menu output hacks  (fixes selected menus)
 *
 * @param string $submenu_file The submenu file.
 * @param string $parent_file The submenu item's parent file.
 *
 * @return string
 */
function fix_submenu_file( $submenu_file, $parent_file ) {
	$haystack = [
		'edit.php?post_type=front-matter',
		'edit.php?post_type=part',
		'edit.php?post_type=chapter',
		'edit.php?post_type=back-matter',
	];
	foreach ( $haystack as $i ) {
		if ( str_starts_with( $submenu_file, $i ) ) {
			return 'pb_organize';
		}
	}

	return $submenu_file;
}

function network_admin_menu() {
	$option = get_site_option( 'pressbooks_sharingandprivacy_options', SharingAndPrivacyOptions::getDefaults() );
	$page = new SharingAndPrivacyOptions( $option );
	$page->init();
	$version = get_site_option( 'pressbooks_sharingandprivacy_options_version', 0 );
	if ( $version < $page::VERSION ) {
		$page->upgrade( $version );
		update_site_option( 'pressbooks_sharingandprivacy_options_version', $page::VERSION );
	}

	add_submenu_page(
		'settings.php', __( 'Sharing and Privacy Settings', 'pressbooks' ), __( 'Sharing &amp; Privacy', 'pressbooks' ), 'manage_network', 'pressbooks_sharingandprivacy_options', [
			$page,
			'render',
		]
	);
}

/**
 * Fix extraneous menus on WordPress Admin sidebar
 */
function fix_root_admin_menu() {

	remove_menu_page( 'edit.php?post_type=part' );
	remove_menu_page( 'edit.php?post_type=chapter' );
	remove_menu_page( 'edit.php?post_type=front-matter' );
	remove_menu_page( 'edit.php?post_type=back-matter' );
	remove_menu_page( 'edit.php?post_type=metadata' );
	remove_menu_page( 'edit.php?post_type=glossary' );
	remove_submenu_page( 'tools.php', 'site-health.php' );

	$user = wp_get_current_user();
	if (
		$user->roles &&
		count( $user->roles ) === 1 &&
		$user->roles[0] === 'subscriber'
	) {
		remove_submenu_page( 'index.php', 'my-sites.php' );
		if ( class_exists( '\H5P_Plugin_Admin' ) ) {
			remove_action( 'admin_menu', [ \H5P_Plugin_Admin::get_instance(), 'add_plugin_admin_menu' ] );
		}
	}
	// Catalog
	add_submenu_page( 'index.php', __( 'My Catalog', 'pressbooks' ), __( 'My Catalog', 'pressbooks' ), 'read', 'pb_catalog', '\Pressbooks\Catalog::addMenu' );
}

function add_pb_cloner_page() {
	add_submenu_page(
		null,
		__( 'Clone a Book', 'pressbooks' ),
		__( 'Clone a Book', 'pressbooks' ),
		'read',
		'pb_cloner',
		__NAMESPACE__ . '\display_cloner'
	);
	add_action(
		'admin_enqueue_scripts',
		function () {
			wp_localize_script(
				'pb-cloner', 'PB_ClonerToken', [
					'ajaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=clone-book' ), 'pb-cloner' ),
					'redirectUrl' => admin_url( 'admin.php?page=pb_cloner' ),
					'unloadWarning' => __( 'Cloning is not done. Leaving this page, now, will cause problems. Are you sure?', 'pressbooks' ),
					'reloadSnippet' => '<em>(<a href="javascript:window.location.reload(true)">' . __( 'Reload', 'pressbooks' ) . '</a>)</em>',
				]
			);
			global $wp_scripts;
			wp_enqueue_script( 'pb-cloner' );
			wp_deregister_script( 'heartbeat' );
		}
	);
}

/**
 * Displays the Organize page.
 */
function display_organize() {
	$blade = \Pressbooks\Container::get( 'Blade' );
	$book_structure = \Pressbooks\Book::getBookStructure();
	$ebook_options = get_option( 'pressbooks_theme_options_ebook' );
	$structure = [];

	$structure['front-matter'] = [
		'name' => __( 'Front Matter', 'pressbooks' ),
		'abbreviation' => 'fm',
		'index' => null,
		'items' => $book_structure['front-matter'],
	];

	foreach($book_structure['part'] as $key => $part) {
		$structure['part_'. $part['ID']] = [
			'name' => __( 'Chapter', 'pressbooks' ),
			'abbreviation' => 'chapter',
			'title' => $part['post_title'],
			'id' => $part['ID'],
			'index' => $key + 1,
			'items' => $part['chapters'],
		];
	}

	$structure['back-matter'] = [
		'name' => __( 'Back Matter', 'pressbooks' ),
		'abbreviation' => 'bm',
		'index' => null,
		'items' => $book_structure['back-matter'],
	];

	echo $blade->render(
		'admin.organize',
		[
			'statuses' => get_post_stati( [], 'objects' ),
			'parts' => count( $book_structure['part'] ),
			'meta_post' => ( new \Pressbooks\Metadata() )->getMetaPost(),
			'book_is_public' => ( ! empty( get_option( 'blog_public' ) ) ) ? 1 : 0,
			'disable_comments' => \Pressbooks\Utility\disable_comments(),
			'wc' => \Pressbooks\Book::wordCount(),
			'wc_selected_for_export' => \Pressbooks\Book::wordCount( true ),
			'can_manage_options' => current_user_can( 'manage_options' ),
			'can_edit_posts' => current_user_can( 'edit_posts' ),
			'can_edit_others_posts' => current_user_can( 'edit_others_posts' ),
			'contributors' => new \Pressbooks\Contributors(),
			'ebook_options' => $ebook_options,
			'start_point' => ( isset( $ebook_options['ebook_start_point'] ) && ! empty( $ebook_options['ebook_start_point'] ) )
				? (int) $ebook_options['ebook_start_point']
				: false,
			'structure' => $structure,
		]
	);
}

/**
 * Displays the trash page.
 */
function display_trash() {
	require( PB_PLUGIN_DIR . 'templates/admin/trash.php' );
}

/**
 * Displays the Export Admin Page
 */
function display_export() {
	$blade = \Pressbooks\Container::get( 'Blade' );
	echo $blade->render(
		'admin.export',
		\Pressbooks\Modules\Export\template_data()
	);
}

/**
 * Displays the Clone a Book Page
 */
function display_cloner() {
	$blade = \Pressbooks\Container::get( 'Blade' );
	echo $blade->render('admin.cloner.page',
		[
			'base_url' => network_home_url(),
			'domain' => wp_parse_url( network_home_url(), PHP_URL_HOST ),
		]
	);
}

/**
 * Displays the Import Admin Page
 */
function display_import() {
	require( PB_PLUGIN_DIR . 'templates/admin/import.php' );
}

/**
 * Replace WP logo in menu bar with PB one and add links to About page, Contact page, and forums
 *
 * @param \WP_Admin_Bar $wp_admin_bar The admin bar object as it currently exists
 */
function replace_menu_bar_branding( $wp_admin_bar ) {

	$wp_admin_bar->remove_menu( 'wp-logo' );
	$wp_admin_bar->remove_menu( 'documentation' );
	$wp_admin_bar->remove_menu( 'feedback' );
	$wp_admin_bar->add_menu(
		[
			'id' => 'wp-logo',
			'title' => '<span class="ab-icon"></span><span class="screen-reader-text">' . __( 'About Pressbooks', 'pressbooks' ) . '</span>',
			'href' => ( 'https://pressbooks.com/about/' ),
		]
	);

	if ( is_user_logged_in() ) {
		// Add "About WordPress" link
		$wp_admin_bar->add_menu(
			[
				'parent' => 'wp-logo',
				'id' => 'about',
				'title' => __( 'About Pressbooks', 'pressbooks' ),
				'href' => 'https://pressbooks.com/about/',
			]
		);
	}

	// Add WordPress.org link
	$wp_admin_bar->add_menu(
		[
			'parent' => 'wp-logo-external',
			'id' => 'wporg',
			'title' => __( 'Pressbooks.com', 'pressbooks' ),
			'href' => 'https://pressbooks.com/',
		]
	);

	// Add forums link
	$wp_admin_bar->add_menu(
		[
			'parent' => 'wp-logo-external',
			'id' => 'support-forums',
			'title' => __( 'Help', 'pressbooks' ),
			/**
			 * Filter the "Help" link.
			 *
			 * @since 5.6.0
			 */
			'href' => apply_filters( 'pb_help_link', 'https://pressbooks.community/' ),
		]
	);

	// Add feedback link
	$wp_admin_bar->add_menu(
		[
			'parent' => 'wp-logo-external',
			'id' => 'contact',
			'title' => __( 'Contact', 'pressbooks' ),
			/**
			 * Filter the "Contact" link.
			 *
			 * @since 5.6.0
			 */
			'href' => apply_filters( 'pb_contact_link', 'https://pressbooks.org/support-pressbooks/' ),
		]
	);

}

/**
 * Rearrange ordering of Admin bar menu elements for our purposes
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function replace_menu_bar_my_sites( $wp_admin_bar ) {

	$wp_admin_bar->remove_menu( 'my-sites' );
	if ( ! show_menu_bar( $wp_admin_bar ) ) {
		return;
	}

	// Network Admin
	if ( is_super_admin() ) {
		$wp_admin_bar->add_menu(
			[
				'id' => 'pb-network-admin',
				'title' => __( 'Network Admin', 'pressbooks' ),
				'href' => network_admin_url(),
				'meta' => is_network_admin() ? [
					'class' => 'you-are-here',
				] : [],
			]
		);
		$wp_admin_bar->add_node(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-admin-d',
				'title' => __( 'Dashboard', 'pressbooks' ),
				'href' => network_admin_url(),
			]
		);
		$wp_admin_bar->add_node(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-admin-s',
				'title' => __( 'Books', 'pressbooks' ),
				'href' => network_admin_url( 'sites.php' ),
			]
		);
		$wp_admin_bar->add_node(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-admin-u',
				'title' => __( 'Users', 'pressbooks' ),
				'href' => network_admin_url( 'users.php' ),
			]
		);
		if ( ! is_restricted() ) {
			$wp_admin_bar->add_node(
				[
					'parent' => 'pb-network-admin',
					'id' => 'pb-network-admin-t',
					'title' => __( 'Themes', 'pressbooks' ),
					'href' => network_admin_url( 'themes.php' ),
				]
			);
			$wp_admin_bar->add_node(
				[
					'parent' => 'pb-network-admin',
					'id' => 'pb-network-admin-p',
					'title' => __( 'Plugins', 'pressbooks' ),
					'href' => network_admin_url( 'plugins.php' ),
				]
			);
		}
	}

	// Website Admin
	$show_website_admin = false;
	$user = wp_get_current_user();
	if (
		$user->roles &&
		count( $user->roles ) === 1 &&
		$user->roles[0] === 'subscriber'
	) {
		foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {
			if ( is_main_site( $blog->userblog_id ) ) {
				$show_website_admin = true;
				break;
			}
		}
	}

	if ( $show_website_admin || is_super_admin() ) {
		$website_id = get_main_site_id();
		$menu_id = 'blog-' . $website_id;
		$admin_url = get_admin_url( $website_id );

		$wp_admin_bar->add_menu(
			[
				'id' => 'pb-site-admin',
				'title' => get_blog_option( $website_id, 'blogname' ),
				'href' => $admin_url,
				'meta' => ( is_main_site() && ! is_network_admin() ) ? [
					'class' => 'you-are-here',
				] : [],
			]
		);
		$wp_admin_bar->add_menu(
			[
				'parent' => 'pb-site-admin',
				'id' => $menu_id . '-d',
				'title' => __( 'Dashboard', 'pressbooks' ),
				'href' => $admin_url,
			]
		);
		$wp_admin_bar->add_menu(
			[
				'parent' => 'pb-site-admin',
				'id' => $menu_id . '-v',
				'title' => __( 'Visit Website', 'pressbooks' ),
				'href' => get_home_url( $website_id, '/' ),
			]
		);
	}

	// Books Admin
	$wp_admin_bar->add_menu(
		[
			'id' => 'my-books',
			'title' => __( 'My Books', 'pressbooks' ),
			'href' => last_book( $wp_admin_bar, 'wp-admin/index.php?page=pb_catalog' ),
		]
	);

	// My Catalog
	$wp_admin_bar->add_node(
		[
			'parent' => 'my-books',
			'id' => 'my-catalog',
			'title' => __( 'My Catalog', 'pressbooks' ),
			'href' => last_book( $wp_admin_bar, 'wp-admin/index.php?page=pb_catalog' ),
		]
	);

	if ( can_create_new_books() ) {
		$wp_admin_bar->add_node(
			[
				'parent' => 'my-books',
				'id' => 'add-new-book',
				'title' => __( 'Create A New Book', 'pressbooks' ),
				'href' => network_home_url( 'wp-signup.php' ),
			]
		);
	}

	// Cloner
	if ( Cloner::isEnabled() && ( can_create_new_books() || is_super_admin() ) ) {
		$wp_admin_bar->add_node(
			[
				'parent' => 'my-books',
				'id' => 'clone-a-book',
				'title' => __( 'Clone A Book', 'pressbooks' ),
				'href' => admin_url( 'admin.php?page=pb_cloner' ),
			]
		);
	}

	// Add book links
	$wp_admin_bar->add_group(
		[
			'parent' => 'my-books',
			'id' => 'my-books-list',
			'meta' => [
				'class' => is_super_admin() ? 'ab-sub-secondary' : '',
			],
		]
	);
	foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {
		if ( is_main_site( $blog->userblog_id ) ) {
			continue; // Skip main website
		}
		$blavatar = '<span class="blavatar"></span>';
		$blogname = empty( $blog->blogname ) ? $blog->domain : $blog->blogname;
		$menu_id = 'books-' . $blog->userblog_id;
		$admin_url = get_admin_url( $blog->userblog_id );

		$wp_admin_bar->add_node(
			[
				'parent' => 'my-books-list',
				'id' => $menu_id,
				'title' => $blavatar . $blogname,
				'href' => $admin_url,
			]
		);
	}

	if ( is_main_site() ) {
		// Remove book menu, @see \WP_Admin_Bar::add_menus
		remove_action( 'admin_bar_menu', 'wp_admin_bar_site_menu', 30 );
	} else {
		// Set defaults so book menu appears highlighted, @see $maybe_defaults in \WP_Admin_Bar::add_node
		$wp_admin_bar->add_node(
			[
				'id' => 'site-name',
				'meta' => [
					'class' => 'you-are-here',
				],
			]
		);
	}
}

/**
 * @param \WP_Admin_Bar $wp_admin_bar
 *
 * @return bool
 */
function show_menu_bar( $wp_admin_bar ) {
	// Don't show for logged out users or single site mode.
	if ( ! is_user_logged_in() || ! is_multisite() ) {
		return false;
	}
	$show_menu = false;
	if ( is_super_admin() ) {
		// Always show menu for super admin
		$show_menu = true;
	} else {
		if ( count( $wp_admin_bar->user->blogs ) >= 1 ) {
			// Show menu for a user that has books
			$show_menu = true;
		} else {
			if ( can_create_new_books() ) {
				$show_menu = true;
			}
		}
	}
	return $show_menu;
}

/**
 * @param \WP_Admin_Bar $wp_admin_bar
 * @param string $suffix
 *
 * @return string Full URL of the blog if found. Empty string if not.
 */
function last_book( $wp_admin_bar, $suffix = '' ) {
	$href = '';
	if ( isset( $_SESSION['pb_admin_last_book_id'] ) && ( is_super_admin() || is_user_member_of_blog( 0, $_SESSION['pb_admin_last_book_id'] ) ) ) {
		$href = get_blogaddress_by_id( $_SESSION['pb_admin_last_book_id'] );
	}
	if ( ! $href ) {
		foreach ( $wp_admin_bar->user->blogs as $blog ) {
			if ( ! is_main_site( $blog->userblog_id ) ) {
				$href = get_blogaddress_by_id( $blog->userblog_id );
				break;
			}
		}
	}
	if ( ! $href && is_super_admin() ) {
		// If no book was found, but is a super admin, then return any book
		global $wpdb;
		$book = $wpdb->get_results( $wpdb->prepare( "SELECT blog_id AS ID FROM {$wpdb->blogs} WHERE archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d LIMIT 1", get_network()->site_id ) );
		if ( ! empty( $book ) ) {
			$href = get_blogaddress_by_id( $book[0]->ID );
		}
	}
	if ( ! empty( $href ) ) {
		$href .= $suffix;
	}
	if ( Book::isBook() ) {
		$_SESSION['pb_admin_last_book_id'] = get_current_blog_id();
	}
	return $href;
}

/**
 * Remove Updates item from admin menu
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function remove_menu_bar_update( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'updates' );
}

/**
 * Remove New Content item from admin menu
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function remove_menu_bar_new_content( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'new-content' );
}

/**
 * @param \WP_Customize_Manager $wp_customize
 *
 * @see http://codex.wordpress.org/Plugin_API/Action_Reference/customize_register
 */
function customize_register( $wp_customize ) {
	$wp_customize->remove_section( 'static_front_page' );
}

/**
 * @return string
 */
function disable_customizer() {
	return 'no-customize-support';
}

/**
 * Init event called at admin_init
 * Instantiates various sub-classes, remove meta boxes from post pages & registers custom post status.
 */
function init_css_js() {
	$assets = new Assets( 'pressbooks', 'plugin' );

	wp_admin_css_color(
		'pb_colors', 'Pressbooks', $assets->getPath( 'styles/colors-pb.css' ), apply_filters(
			'pressbooks_admin_colors', [
				'#b40026',
				'#d4002d',
				'#e9e9e9',
				'#dfdfdf',
			]
		)
	);

	wp_admin_css_color(
		'pb_colors_a11y', 'Pressbooks a11y', $assets->getPath( 'styles/colors-pb-a11y.css' ), apply_filters(
			'pressbooks_admin_colors_ally', [
				'#2D2D2D',
				'#B40026',
				'#E9E9E9',
				'#dfdfdf',
			]
		)
	);

	wp_deregister_style( 'pressbooks-book' ); // Theme's CSS

	wp_enqueue_style( 'pressbooks-admin', $assets->getPath( 'styles/pressbooks.css' ) );

	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pb_catalog' ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'select2', $assets->getPath( 'styles/select2.css' ) );
		wp_enqueue_style( 'pressbooks-catalog', $assets->getPath( 'styles/catalog.css' ) );
		wp_enqueue_script( 'color-picker', $assets->getPath( 'scripts/color-picker.js' ), [ 'wp-color-picker' ] );
		wp_enqueue_script( 'select2-js', $assets->getPath( 'scripts/select2.js' ), [ 'jquery' ] );
	}

	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pressbooks_theme_options' ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'select2', $assets->getPath( 'styles/select2.css' ) );
		wp_enqueue_style( 'theme-options', $assets->getPath( 'styles/theme-options.css' ) );
		wp_enqueue_script( 'select2-js', $assets->getPath( 'scripts/select2.js' ), [ 'jquery' ] );
		wp_enqueue_script( 'theme-options-js', $assets->getPath( 'scripts/theme-options.js' ), [ 'jquery', 'wp-color-picker' ] );
	}

	// Upgrade Select2 in Custom Metadata Manager
	add_filter(
		'custom_metadata_manager_select2_js',
		function ( $path ) use ( $assets ) {
			return $assets->getPath( 'scripts/select2.js' );
		}
	);
	add_filter(
		'custom_metadata_manager_select2_js_version',
		function ( $version ) {
			return get_bloginfo( 'version' );
		}
	);
	add_filter(
		'custom_metadata_manager_select2_css',
		function ( $path ) use ( $assets ) {
			return $assets->getPath( 'styles/select2.css' );
		}
	);

	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pressbooks_export_options' ) {
		wp_enqueue_script( 'pressbooks/theme-lock', $assets->getPath( 'scripts/theme-lock.js' ), [ 'jquery' ] );
		wp_localize_script(
			'pressbooks/theme-lock', 'PB_ThemeLockToken', [
				// Strings
				'confirmation' => __( 'Are you sure you want to unlock your theme? This will update your book to the most recent version of your selected theme, which may change your book&rsquo;s appearance and page count. Once you save your settings on this page, this action will NOT be reversable!', 'pressbooks' ),
			]
		);
	}

	// Don't let other plugins override our scripts
	$bad_scripts = [ 'jquery-blockui', 'jquery-bootstrap', 'pb-organize', 'pb-feedback', 'pb-cloner', 'pb-export', 'pb-metadata', 'pb-import' ];
	array_walk(
		$bad_scripts, function ( $value, $key ) {
			wp_deregister_script( $value );
		}
	);

	// Polyfills
	wp_register_script( 'eventsource-polyfill', $assets->getPath( 'scripts/eventsource.polyfill.js' ) );

	// Register scripts for later, on-the-fly, using action: admin_print_scripts- (or other tricks of the shade)
	wp_register_script( 'jquery-blockui', $assets->getPath( 'scripts/blockui.js' ), [ 'jquery', 'jquery-ui-core' ] );
	wp_register_script( 'pb-cloner', $assets->getPath( 'scripts/cloner.js' ), [ 'jquery', 'eventsource-polyfill' ] );
	wp_register_script( 'pb-export', $assets->getPath( 'scripts/export.js' ), [ 'jquery', 'eventsource-polyfill' ] );
	wp_register_script( 'pb-import', $assets->getPath( 'scripts/import.js' ), [ 'jquery', 'jquery-form', 'eventsource-polyfill' ] );
	wp_register_script( 'pb-organize', $assets->getPath( 'scripts/organize.js' ), [ 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-blockui' ] );
	wp_register_script( 'pb-metadata', $assets->getPath( 'scripts/book-information.js' ), [ 'jquery' ], false, true );
	wp_register_script( 'pb-post-visibility', $assets->getPath( 'scripts/post-visibility.js' ), [ 'jquery' ], false, true );
	wp_register_script( 'pb-post-mathjax', $assets->getPath( 'scripts/post-mathjax.js' ), [ 'jquery' ], false, true );
	wp_register_script( 'pb-post-back-matter', $assets->getPath( 'scripts/post-back-matter.js' ), [ 'jquery', 'editor' ], false, true );

	// Register styles for later, on-the-fly, using action: admin_print_scripts- (or other tricks of the shade)
	wp_register_style( 'pb-export', $assets->getPath( 'styles/export.css' ) );
	wp_register_style( 'pb-organize', $assets->getPath( 'styles/organize.css' ) );

	// Always enqueue jquery and jquery-ui-core.
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );

	// Always enqueue AlpineJS.
	wp_register_script( 'alpinejs', $assets->getPath( 'scripts/alpine.min.js' ), [], false, true );
	wp_enqueue_script( 'alpinejs' );

	// Enqueue styles for cloner page
	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pb_cloner' ) {
		wp_register_style( 'cloner-page', $assets->getPath( 'styles/cloner.css' ) );
		wp_enqueue_style( 'cloner-page' );

		$blade = \Pressbooks\Container::get( 'Blade' );

		// Enqueue Algolia & Instantsearch scripts only if required env values are present.
		if ( \Pressbooks\Utility\is_algolia_search_enabled() ) {
			// Algolia
			wp_register_script( 'algolia', $assets->getPath( 'scripts/algoliasearch-lite.umd.js' ), [], false, true );
			wp_enqueue_script( 'algolia' );

			// InstantSearch
			wp_register_script( 'instantsearch', $assets->getPath( 'scripts/instantsearch.production.min.js' ), [ 'algolia' ], false, true );
			wp_enqueue_script( 'instantsearch' );

			wp_register_script( 'cloner-page', $assets->getPath( 'scripts/algolia-search.js' ), [], false, true );
			wp_enqueue_script( 'cloner-page' );

			wp_localize_script('cloner-page', 'PBAlgolia', [
				'applicationId' => env( 'ALGOLIA_APP_ID' ),
				'apiKey' => env( 'ALGOLIA_API_KEY' ),
				'indexName' => env( 'ALGOLIA_INDEX_NAME' ),
				'hitsTemplate' => $blade->render( 'admin.cloner.book-card' ),
				'resultsTemplate' => $blade->render( 'admin.cloner.results' ),
			]);
		}
	}

	// A11y
	wp_register_script( 'pb-a11y', $assets->getPath( 'scripts/a11y.js' ), [ 'jquery', 'wp-i18n' ], false, true );
	wp_enqueue_script( 'pb-a11y' );
}

/* ------------------------------------------------------------------------ *
 * Privacy
 * ------------------------------------------------------------------------ */

/**
 * Privacy settings initialization
 */
function privacy_settings_init() {
	add_settings_section(
		'privacy_settings_section',
		'',
		__NAMESPACE__ . '\privacy_settings_section_callback',
		'privacy_settings'
	);

	/**
	 * Filter the ability to manage webbook privacy and related settings (default true).
	 *
	 * @since 5.4.0
	 */
	if ( apply_filters( 'pb_permissive_webbooks', true ) ) {
		add_settings_field(
			'blog_public',
			__( 'Book Visibility', 'pressbooks' ),
			__NAMESPACE__ . '\privacy_blog_public_callback',
			'privacy_settings',
			'privacy_settings_section'
		);
		register_setting(
			'privacy_settings',
			'blog_public',
			__NAMESPACE__ . '\privacy_blog_public_sanitize'
		);
	}

	add_settings_field(
		'permissive_private_content',
		__( 'Private Content', 'pressbooks' ),
		__NAMESPACE__ . '\privacy_permissive_private_content_callback',
		'privacy_settings',
		'privacy_settings_section'
	);
	register_setting(
		'privacy_settings',
		'permissive_private_content',
		__NAMESPACE__ . '\privacy_permissive_private_content_sanitize'
	);

	if ( apply_filters( 'pb_comments_management', true ) ) {
		add_settings_field(
			'disable_comments',
			__( 'Disable Comments', 'pressbooks' ),
			__NAMESPACE__ . '\privacy_disable_comments_callback',
			'privacy_settings',
			'privacy_settings_section'
		);
		register_setting(
			'privacy_settings',
			'pressbooks_sharingandprivacy_options',
			__NAMESPACE__ . '\privacy_disable_comments_sanitize'
		);
	}

	$sharingandprivacy = get_site_option( 'pressbooks_sharingandprivacy_options' );
	$allows_downloads = apply_filters( 'pb_allows_downloads', true );

	if ( ! empty( $sharingandprivacy['allow_redistribution'] ) && $allows_downloads ) {
		add_settings_field(
			'latest_files_public',
			__( 'Share Latest Export Files', 'pressbooks' ),
			__NAMESPACE__ . '\privacy_latest_files_public_callback',
			'privacy_settings',
			'privacy_settings_section'
		);
		register_setting(
			'privacy_settings',
			'pbt_redistribute_settings',
			__NAMESPACE__ . '\privacy_pbt_redistribute_settings_sanitize'
		);
	}

	if ( ! isset( $sharingandprivacy['network_directory_excluded'] ) || 0 === $sharingandprivacy['network_directory_excluded'] ) {
		add_action(
			'update_option_pb_book_directory_excluded', function( $old_value, $updated_value ) {
				$current_book_id = get_current_blog_id();

				if ( update_site_meta( $current_book_id, DataCollector::BOOK_DIRECTORY_EXCLUDED, $updated_value ) ) {
					update_blog_details( $current_book_id, [ 'last_updated' => current_time( 'mysql', true ) ] );
				}

				if ( $updated_value === 1 ) {
					BookDirectory::init()->deleteBookFromDirectory( [ $current_book_id ] );
				}

			}, 10, 2
		);

		add_settings_field(
			'pb_book_directory_excluded',
			__( 'Pressbooks Directory', 'pressbooks' ),
			__NAMESPACE__ . '\book_directory_excluded_callback',
			'privacy_settings',
			'privacy_settings_section'
		);
		register_setting(
			'privacy_settings',
			'pb_book_directory_excluded',
			__NAMESPACE__ . '\book_directory_excluded_sanitize'
		);
	}
}

/**
 * Privacy settings section callback
 */
function privacy_settings_section_callback() {
	echo '<p>' . __( 'Sharing and Privacy settings', 'pressbooks' ) . '.</p>'; // TK
}

/**
 * Privacy settings, blog_public field callback
 *
 * @param $args
 */
function privacy_blog_public_callback( $args ) {
	$blog_public = get_option( 'blog_public' );
	$html = '<input type="radio" id="blog-public" name="blog_public" value="1" ';
	if ( $blog_public ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="blog-public"> ' . __( 'Public. I would like this book to be visible to everyone.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="blog-norobots" name="blog_public" value="0" ';
	if ( ! $blog_public ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="blog-norobots"> ' . __( 'Private. I would like this book to be accessible only to people I invite.', 'pressbooks' ) . '</label>';
	echo $html;
}

/**
 * Privacy settings, permissive_private_content field callback
 *
 * @param $args
 */
function privacy_permissive_private_content_callback( $args ) {
	$permissive_private_content = absint( get_option( 'permissive_private_content' ) );
	$subscriber = get_role( 'subscriber' );
	$contributor = get_role( 'contributor' );
	$author = get_role( 'author' );
	if ( ! empty( $permissive_private_content ) ) { // If permissive private content is set to true, adjust capabilities
		$subscriber->add_cap( 'read_private_posts' );
		$contributor->add_cap( 'read_private_posts' );
		$author->add_cap( 'read_private_posts' );
	} else {
		$subscriber->remove_cap( 'read_private_posts' );
		$contributor->remove_cap( 'read_private_posts' );
		$author->remove_cap( 'read_private_posts' );
	}
	?>
	<p><?php _e( 'Who can see private front matter, chapters and back matter?', 'pressbooks' ); ?></p>
	<fieldgroup>
		<input type="radio" id="standard-private-content" name="permissive_private_content" value="0" <?php checked( $permissive_private_content, 0 ); ?>/>
		<label for="standard-private-content"><?php _e( 'Only logged in editors and administrators.', 'pressbooks' ); ?></label><br/>
		<input type="radio" id="permissive-private-content" name="permissive_private_content" value="1" <?php checked( $permissive_private_content, 1 ); ?>/>
		<label for="permissive-private-content"><?php _e( 'All logged in users including subscribers.', 'pressbooks' ); ?></label>
	</fieldgroup>
	<?php
}

/**
 * Privacy settings, disable_comments field callback
 *
 * @param $args
 */
function privacy_disable_comments_callback( $args ) {
	$options = get_option(
		'pressbooks_sharingandprivacy_options', [
			'disable_comments' => 1,
		]
	);
	$html = '<input type="radio" id="disable-comments" name="pressbooks_sharingandprivacy_options[disable_comments]" value="1" ';
	if ( $options['disable_comments'] ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="disable-comments"> ' . __( 'Yes. I want to automatically disable comments, trackbacks and pingbacks on all front matter, chapters and back matter.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="enable-comments" name="pressbooks_sharingandprivacy_options[disable_comments]" value="0" ';
	if ( ! $options['disable_comments'] ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="enable-comments"> ' . __( 'No. I want to leave comments, trackbacks and pingbacks enabled on all front matter, chapters and back matter unless I disable them manually.', 'pressbooks' ) . '</label>';
	echo $html;
}

/**
 * Sharing settings, latest_files_public field callback
 *
 * @param $args
 */
function privacy_latest_files_public_callback( $args ) {
	$blog_public = get_option( 'pbt_redistribute_settings', [] );
	$html = '<input type="radio" id="latest_files_public" name="pbt_redistribute_settings[latest_files_public]" value="1" ';
	if ( isset( $blog_public['latest_files_public'] ) && $blog_public['latest_files_public'] === 1 ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="latest_files_public"> ' . __( 'Yes. I would like the latest export files to be available on the homepage for free, to everyone.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="latest_files_private" name="pbt_redistribute_settings[latest_files_public]" value="0" ';
	if ( ! isset( $blog_public['latest_files_public'] ) || $blog_public['latest_files_public'] === 0 ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="latest_files_private"> ' . __( 'No. I would like the latest export files to only be available to administrators.', 'pressbooks' ) . '</label>';
	echo $html;
}

/**
 * Sharing settings, exclude book from directory field callback
 *
 * @param $args
 */
function book_directory_excluded_callback( $args ) {
	if ( ! get_option( 'pb_book_directory_excluded' ) ) {
		add_option( 'pb_book_directory_excluded', 0 );
	}
	$exclude_book = get_option( 'pb_book_directory_excluded' );
	$html = '<input type="radio" id="include-in-directory" name="pb_book_directory_excluded" value="0" ';
	if ( ! $exclude_book ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="include-in-directory"> ' . __( 'Yes. I want this book to be listed in the Pressbooks directory.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="exclude-from-directory" name="pb_book_directory_excluded" value="1" ';
	if ( $exclude_book ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="exclude-from-directory"> ' . __( 'No. Exclude this book from the Pressbooks directory.', 'pressbooks' ) . '</label>';
	echo $html;
}

/**
 * Privacy settings, blog_public field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_blog_public_sanitize( $input ) {
	return absint( $input );
}

/**
 * Privacy settings, permissive_private_content field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_permissive_private_content_sanitize( $input ) {
	return absint( $input );
}

/**
 * Privacy settings, disable_comments field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_disable_comments_sanitize( $input ) {
	$output['disable_comments'] = absint( $input['disable_comments'] );
	return $output;
}

/**
 * Privacy settings, pbt_redistribute_settings field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_pbt_redistribute_settings_sanitize( $input ) {
	$output['latest_files_public'] = absint( $input['latest_files_public'] );
	return $output;
}

/**
 * Privacy settings, pb_book_directory_excluded field sanitization
 *
 * @param $input
 *
 * @return string
 */
function book_directory_excluded_sanitize( $input ) {
	return absint( $input );
}

/**
 * Display Privacy settings
 */
function display_privacy_settings() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Sharing and Privacy Settings', 'pressbooks' ); ?></h2>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'privacy_settings' );
			do_settings_sections( 'privacy_settings' );
			?>
			<?php submit_button(); ?>
		</form>
	</div>

	<?php
}

/* ------------------------------------------------------------------------ *
 * Misc
 * ------------------------------------------------------------------------ */

/**
 * Hook for add_action( 'admin_notices', ... ) Echo $_SESSION['pb_notices'] if any.
 *
 * @global array $_SESSION ['pb_errors'] *
 * @global array $_SESSION ['pb_notices']
 */
function admin_notices() {
	// Print the error(s).
	$errors_to_print = \Pressbooks\get_all_errors();
	foreach ( $errors_to_print as $msg ) {
		echo '<div class="error" role="alert"><p>' . $msg . '</p></div>';
	}
	\Pressbooks\flush_all_errors();

	// Print the notice(s).
	$notices_to_print = \Pressbooks\get_all_notices();
	foreach ( $notices_to_print as $msg ) {
		echo '<div class="updated" role="status"><p>' . $msg . '</p></div>';
	}
	\Pressbooks\flush_all_notices();
}

/**
 * Replace 'site|sites' with 'book|books' throughout the interface.
 *
 * @since 4.5.0
 *
 * @param string $translated_text The translated string.
 * @param string $untranslated_text The source string.
 * @param string $domain The textdomain.
 *
 * @return string The modified translated string.
 */
function sites_to_books( $translated_text, $untranslated_text, $domain ) {
	global $pagenow;

	switch ( $untranslated_text ) {
		case 'Sites':
			$translated_text = __( 'Books', 'pressbooks' );
			break;
		case 'All Sites':
			$translated_text = __( 'All Books', 'pressbooks' );
			break;
	}

	if ( $pagenow === 'sites.php' ) {
		switch ( $untranslated_text ) {
			case 'Sites':
				$translated_text = __( 'Books', 'pressbooks' );
				break;
			case 'Search Sites':
				$translated_text = __( 'Search Books', 'pressbooks' );
				break;
		}
	} elseif ( $pagenow === 'site-info.php' ) {
		switch ( $untranslated_text ) {
			case 'Edit Site: %s':
				$translated_text = __( 'Edit Book: %s', 'pressbooks' );
				break;
			case 'Site Address (URL)':
				$translated_text = __( 'Book Address (URL)', 'pressbooks' );
				break;
		}
	} elseif ( $pagenow === 'site-new.php' ) {
		switch ( $untranslated_text ) {
			case 'Add New Site':
				$translated_text = __( 'Add New Book', 'pressbooks' );
				break;
			case 'Site Address (URL)':
				$translated_text = __( 'Book Address (URL)', 'pressbooks' );
				break;
			case 'Site Title':
				$translated_text = __( 'Book Title', 'pressbooks' );
				break;
			case 'Site Language':
				$translated_text = __( 'Book Language', 'pressbooks' );
				break;
			case 'Add Site':
				$translated_text = __( 'Add Book', 'pressbooks' );
				break;
		}
	}

	return $translated_text;
}

/**
 * @since 5.2.0
 *
 * @param \WP_Post $post Post object.
 */
function edit_screen_navigation( $post ) {
	global $pagenow;
	if ( 'post.php' === $pagenow && in_array( $post->post_type, [ 'front-matter', 'part', 'chapter', 'back-matter' ], true ) ) {
		// We're in the edit screen (not the new post screen because we don't know the position of a new post)
		echo sprintf( '<nav id="pb-edit-screen-navigation" role="navigation" aria-label="%s">', __( 'Edit previous or next item', 'pressbooks' ) );

		$prev_id = Book::get( 'prev', true, true );
		if ( $prev_id ) {
			$prev_url = admin_url( 'post.php?post=' . $prev_id . '&action=edit' );
			echo "<a href='{$prev_url}' rel='previous'><span aria-hidden='true'>&larr;</span> " . sprintf( __( 'Edit Previous (%s)', 'pressbooks' ), get_post_type_label( get_post_type( $prev_id ) ) ) . '</a>';
		}
		$next_id = Book::get( 'next', true, true );
		if ( $next_id ) {
			$next_url = admin_url( 'post.php?post=' . $next_id . '&action=edit' );
			echo "<a href='{$next_url}' rel='next'>" . sprintf( __( 'Edit Next (%s)', 'pressbooks' ), get_post_type_label( get_post_type( $next_id ) ) ) . ' <span aria-hidden="true">&rarr;</span></a>';
		}

		echo '</nav>';
	}
}

/**
 *
 * $since 5.27.0
 *
 * @return array
 */
function get_user_contact_fields() {
	$methods = [];
	$methods['twitter'] = __( 'Twitter URL', 'pressbooks' );
	$methods['linkedin'] = __( 'LinkedIn URL', 'pressbooks' );
	$methods['github'] = __( 'GitHub URL', 'pressbooks' );
	return $methods;
}

/**
 *
 * $since 5.27.0
 *
 * @param array $methods
 * @return array
 */
function modify_user_contact_fields( $methods ) {
	return get_user_contact_fields();
}

/**
 *
 * $since 5.27.0
 *
 * @param WP_Error $errors
 * @param bool $update
 * @param stdClass $user
 */
function sanitize_user_profile( WP_Error $errors, $update, $user ) {

	$additional_urls_to_check = [ 'url' => 'Website' ];

	foreach ( array_merge( get_user_contact_fields(), $additional_urls_to_check ) as $key => $value ) {
		$field = wp_kses( $_POST[ $key ], false );
		if ( ! empty( $field ) ) {
			if ( ! \Pressbooks\Sanitize\validate_url_field( $field ) ) {
				$errors->add( $key, "The $value field is not a valid URL." );
			}
		}
	}
}

/**
 *
 * @since 5.27.0
 * @param \WP_User $user
 */
function add_user_profile_fields( \WP_User $user ) {

	$institution = __( 'Institution' );
	$value = esc_attr( get_the_author_meta( 'institution', $user->ID ) );
	$helper = __( 'Your institutional affiliation, e.g. Rebus Foundation, Open University, Amnesty International.', 'pressbooks' );

	$row = <<<HTML
	<tr class="institution">
		<th><label for="institution"> $institution </label></th>
		<td>
			<input type="text" name="institution" id="institution" value="$value" class="regular-text" /><br />
			<p class="description"> $helper </p>
		</td>
	</tr>
HTML;
	?>
	<script>
		const element = document.querySelector(".user-description-wrap").parentNode; //Biographical Info input as reference
		element.insertAdjacentHTML('afterbegin', '<?php echo str_replace( [ "\r\n", "\r", "\n", "\t" ], '', $row ); ?>');
	</script>
	<?php
}

/**
 *
 * @since 5.27.0
 * @param int $user_id
 */
function update_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	update_user_meta( $user_id, 'institution', sanitize_string( $_REQUEST['institution'] ) );
}

/**
 *
 * @since 5.35.0
 * @param object $wp_admin_bar
 */
function replace_wordpress_howdy( $wp_admin_bar ) {
	$my_account = $wp_admin_bar->get_node( 'my-account' );
	$newtext = str_replace( 'Howdy,', 'Hello,', $my_account->title );
	$wp_admin_bar->add_node( [
		'id' => 'my-account',
		'title' => $newtext,
	] );
}

/**
 *
 * @since 5.35.0
 */
function remove_emoji() {
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	add_filter( 'emoji_svg_url', '__return_false' );
	add_filter('tiny_mce_plugins', function ( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, [ 'wpemoji' ] );
		} else {
			return [];
		}
	});
}
