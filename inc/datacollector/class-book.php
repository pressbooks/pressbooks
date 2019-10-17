<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\DataCollector;

use function \Pressbooks\Metadata\get_in_catalog_option;

class Book {

	// Meta Key Constants:

	const COVER = 'pb_cover_image';

	const TITLE = 'pb_title';

	const LAST_EDITED = 'pb_last_edited';

	const CREATED = 'pb_created';

	const WORD_COUNT = 'pb_word_count';

	const TOTAL_AUTHORS = 'pb_total_authors';

	const TOTAL_READERS = 'pb_total_readers';

	const STORAGE_SIZE = 'pb_storage_size';

	const LANGUAGE = 'pb_language';

	const SUBJECT = 'pb_subject';

	const THEME = 'pb_theme';

	const LICENSE = 'pb_book_license';

	const PUBLIC = 'pb_is_public';

	const IN_CATALOG = 'pb_in_catalog';

	const IS_CLONE = 'pb_is_clone';

	const HAS_EXPORTS = 'pb_has_exports';

	const LAST_EXPORT = 'pb_last_export';

	const ALLOWS_DOWNLOADS = 'pb_latest_files_public';

	const EXPORTS_BY_FORMAT = 'pb_exports_by_format';

	const TOTAL_REVISIONS = 'pb_total_revisions';

	const TIMESTAMP = 'pb_book_sync_timestamp';

	const MEDIA_LIBRARY_URL = 'pb_admin_url';

	const AKISMET_ACTIVATED = 'pb_akismet_activated';

	const PARSEDOWN_PARTY_ACTIVATED = 'pb_parsedown_party_activated';

	const WP_QUICK_LATEX_ACTIVATED = 'pb_wp_quick_latex_activated';

	const GLOSSARY_TERMS = 'pb_glossary_terms';

	const H5P_ACTIVITIES = 'pb_h5p_activities';

	const TABLEPRESS_TABLES = 'pb_tablepress_tables';

	const BOOK_URL = 'pb_book_url';

	const DEACTIVATED = 'pb_deactivated';

	/**
	 * @var Book
	 */
	private static $instance = null;

	/**
	 * @return Book
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Book $obj
	 */
	static public function hooks( Book $obj ) {
		add_action( 'wp_update_site', [ $obj, 'updateSite' ], 999, 2 );
		add_action( 'wp_insert_post', [ $obj, 'updateMetaData' ], 10, 3 ); // Trigger after deleteBookObjectCache
		add_action( 'wp_delete_site', [ $obj, 'deleteSite' ], 999 );
	}

	/**
	 *
	 */
	public function __construct() {

	}

	/**
	 * Hooked into wp_update_site
	 *
	 * @param \WP_Site $new_site New site object.
	 * @param \WP_Site $old_site Old site object.
	 */
	public function updateSite( $new_site, $old_site ) {
		$this->copyBookMetaIntoSiteTable( $new_site->id );
		if ( $old_site->id && $old_site->id !== $new_site->id ) {
			$this->copyBookMetaIntoSiteTable( $old_site->id );
		}
	}

	/**
	 * Hooked into save_post
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 * @param bool $update
	 */
	public function updateMetaData( $post_id, $post, $update ) {
		if ( $post->post_type === 'metadata' && $update ) {
			$this->copyBookMetaIntoSiteTable( get_current_blog_id() );
		}
	}

	/**
	 * Hooked into wp_delete_site
	 *
	 * @param \WP_Site $old_site Old site object.
	 */
	public function deleteSite( $old_site ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->blogmeta} WHERE blog_id = %d ", $old_site->id ) );
	}

	/**
	 * Copy (sync) book meta into wp_blogmeta table.
	 * Add a timestamp to indicate when this was done.
	 *
	 * @param int $book_id
	 */
	public function copyBookMetaIntoSiteTable( $book_id ) {

		// TODO:
		//  Override \Pressbooks\L10n\get_book_language() so that all info collected appears in Admin language

		switch_to_blog( $book_id );

		// --------------------------------------------------------------------
		// Network Analytic Columns
		// --------------------------------------------------------------------

		$metadata = \Pressbooks\Book::getBookInformation();

		// pb_cover_image
		if ( empty( $metadata['pb_cover_image'] ) ) {
			$metadata['pb_cover_image'] = \Pressbooks\Image\default_cover_url();
		}
		$cover = \Pressbooks\Image\thumbnail_from_url( $metadata['pb_cover_image'], 'pb_cover_medium' );
		update_site_meta( $book_id, self::COVER, $cover );

		// pb_title
		update_site_meta( $book_id, self::TITLE, $metadata['pb_title'] ?? '' );

		// pb_last_edited
		// pb_created
		// pb_deactivated
		$blog_info = get_blog_details( null, false );
		update_site_meta( $book_id, self::LAST_EDITED, $blog_info->last_updated );
		update_site_meta( $book_id, self::CREATED, $blog_info->registered );
		update_site_meta( $book_id, self::DEACTIVATED, $blog_info->deleted );

		// pb_word_count
		$word_count = \Pressbooks\Book::wordCount();
		update_site_meta( $book_id, self::WORD_COUNT, $word_count );

		// pb_total_authors
		// pb_total_readers
		$count_users = count_users();
		$total_readers = 0;
		if ( isset( $count_users['avail_roles'], $count_users['avail_roles']['none'] ) ) {
			$total_readers += $count_users['avail_roles']['none'];
		}
		if ( isset( $count_users['avail_roles'], $count_users['avail_roles']['subscriber'] ) ) {
			$total_readers += $count_users['avail_roles']['subscriber'];
		}
		$total_authors = $count_users['total_users'] - $total_readers;
		update_site_meta( $book_id, self::TOTAL_AUTHORS, $total_authors );
		update_site_meta( $book_id, self::TOTAL_READERS, $total_readers );

		// pb_storage_size
		$space_used = get_space_used() * MB_IN_BYTES;
		update_site_meta( $book_id, self::STORAGE_SIZE, $space_used );

		// pb_language
		update_site_meta( $book_id, self::LANGUAGE, $metadata['pb_language'] ?? 'en' );

		// pb_subject
		if ( ! empty( $metadata['pb_primary_subject'] ) ) {
			add_filter( 'pb_thema_subjects_locale', [ $this, 'themaSubjectsLocale' ] );
			$subject = \Pressbooks\Metadata\get_subject_from_thema( $metadata['pb_primary_subject'] );
			remove_filter( 'pb_thema_subjects_locale', [ $this, 'themaSubjectsLocale' ] );
		}
		update_site_meta( $book_id, self::SUBJECT, $subject ?? $metadata['pb_subject'] ?? null );

		// pb_theme
		$theme_name = wp_get_theme()->display( 'Name' );
		update_site_meta( $book_id, self::THEME, $theme_name );

		// pb_book_license
		update_site_meta( $book_id, self::LICENSE, $metadata['pb_book_license'] ?? 'all-rights-reserved' );

		// pb_is_public
		$is_public = empty( get_option( 'blog_public' ) ) ? 0 : 1;
		update_site_meta( $book_id, self::PUBLIC, $is_public );

		// pb_in_catalog
		// @see \Aldine\Admin\BLOG_OPTION, Not using constant because Aldine is optional
		$in_catalog = empty( get_option( get_in_catalog_option() ) ) ? 0 : 1;
		update_site_meta( $book_id, self::IN_CATALOG, $in_catalog );

		// --------------------------------------------------------------------
		// Network Analytic Filters
		// --------------------------------------------------------------------

		// pb_is_based_on
		update_site_meta( $book_id, self::IS_CLONE, empty( $metadata['pb_is_based_on'] ) ? 0 : 1 );

		// pb_total_revisions
		$revisions = $this->revisions();
		update_site_meta( $book_id, self::TOTAL_REVISIONS, $revisions );

		// pb_last_export
		$last_export_unix_timestamp = get_option( 'pressbooks_last_export' );
		update_site_meta( $book_id, self::LAST_EXPORT, $last_export_unix_timestamp ? gmdate( 'Y-m-d H:i:s', $last_export_unix_timestamp ) : null );

		// pb_latest_files_public
		$downloads_allowed = 0;
		$sharingandprivacy = get_site_option( 'pressbooks_sharingandprivacy_options' );
		if ( ! empty( $sharingandprivacy['allow_redistribution'] ) ) {
			$redistribute_settings = get_option( 'pbt_redistribute_settings', [] );
			if ( ! empty( $redistribute_settings['latest_files_public'] ) ) {
				$downloads_allowed = 1;
			}
		}
		update_site_meta( $book_id, self::ALLOWS_DOWNLOADS, $downloads_allowed );

		// pb_exports_by_format
		$exports_by_format = '';
		$latest_exports = \Pressbooks\Utility\latest_exports();
		foreach ( $latest_exports as $filetype => $filename ) {
			$filetype = str_replace( '_', '-', $filetype );
			$name = \Pressbooks\Modules\Export\get_name_from_filetype_slug( $filetype );
			$exports_by_format .= "{$name},";
		}
		update_site_meta( $book_id, self::EXPORTS_BY_FORMAT, $exports_by_format );

		// pb_has_exports
		$has_exports = ( empty( $exports_by_format ) ) ? 0 : 1;
		update_site_meta( $book_id, self::HAS_EXPORTS, $has_exports );

		// FEATURE Filters

		$akismet_activated = is_plugin_active_for_network( 'akismet/akismet.php' ) || is_plugin_active( 'akismet/akismet.php' );
		update_site_meta( $book_id, self::AKISMET_ACTIVATED, $akismet_activated ? 1 : 0 );

		$parsedown_party_activated = is_plugin_active_for_network( 'parsedown-party/parsedown-party.php' ) || is_plugin_active( 'parsedown-party/parsedown-party.php' );
		update_site_meta( $book_id, self::PARSEDOWN_PARTY_ACTIVATED, $parsedown_party_activated ? 1 : 0 );

		$wp_quicklatex_activated = is_plugin_active_for_network( 'wp-quicklatex/wp-quicklatex.php' ) || is_plugin_active( 'wp-quicklatex/wp-quicklatex.php' );
		update_site_meta( $book_id, self::WP_QUICK_LATEX_ACTIVATED, $wp_quicklatex_activated ? 1 : 0 );

		update_site_meta( $book_id, self::GLOSSARY_TERMS, $this->glossaryTerms() );

		$h5p_activated = is_plugin_active_for_network( 'h5p/h5p.php' ) || is_plugin_active( 'h5p/h5p.php' );
		if ( $h5p_activated ) {
			update_site_meta( $book_id, self::H5P_ACTIVITIES, $this->h5pActivities() );
		} else {
			update_site_meta( $book_id, self::H5P_ACTIVITIES, 0 );
		}

		$tablepress_activated = is_plugin_active( 'tablepress/tablepress.php' ) || is_plugin_active_for_network( 'tablepress/tablepress.php' );
		if ( $tablepress_activated ) {
			update_site_meta( $book_id, self::TABLEPRESS_TABLES, $this->tablepressTables() );
		} else {
			update_site_meta( $book_id, self::TABLEPRESS_TABLES, 0 );
		}

		// --------------------------------------------------------------------
		// Other data we need
		// --------------------------------------------------------------------

		// Media Library URL
		update_site_meta( $book_id, self::MEDIA_LIBRARY_URL, get_admin_url( $book_id, 'upload.php' ) );

		// Book URL
		update_site_meta( $book_id, self::BOOK_URL, get_home_url( $book_id ) );

		// Timestamp for this book
		update_site_meta( $book_id, self::TIMESTAMP, gmdate( 'Y-m-d H:i:s' ) );

		restore_current_blog();
	}

	/**
	 * Hooked into pb_thema_subjects_locale
	 *
	 * @param string $locale
	 *
	 * @return string
	 */
	public function themaSubjectsLocale( $locale ) {
		// TODO Use main site locale
		return 'en';
	}

	/**
	 * @return \Generator
	 */
	public function copyAllBooksIntoSiteTable(): \Generator {
		// Try to stop a Cache Stampede, Dog-Pile, Cascading Failure...
		$in_progress_transient = 'pb_book_sync_cron_in_progress';
		if ( ! get_transient( $in_progress_transient ) ) {
			set_transient( $in_progress_transient, 1, 15 * MINUTE_IN_SECONDS );

			set_time_limit( 0 );
			ini_set( 'memory_limit', -1 );
			ignore_user_abort( true );

			global $wpdb;
			$main_site_id = get_network()->site_id;
			$books = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE archived = 0 AND spam = 0 AND blog_id != %d ", $main_site_id ) );

			// Purging books that no longer exist (from wp_blogmeta)...
			if ( count( $books ) ) {
				$sql = "DELETE FROM {$wpdb->blogmeta} WHERE ";
				$sql .= 'blog_id NOT IN (' . implode( ',', $books ) . ')';
				$wpdb->query( $sql ); // WPCS: unprepared SQL OK

				// Syncing book metadata (into wp_blogmeta)...
				foreach ( $books as $id ) {
					$this->copyBookMetaIntoSiteTable( $id );
					yield;
				}
			}

			// Timestamp
			update_site_option( 'pb_book_sync_cron_timestamp', gmdate( 'Y-m-d H:i:s' ) );
			delete_transient( $in_progress_transient );
		}
	}

	/**
	 * @param string $meta_key
	 *
	 * @return array
	 */
	public function getPossibleValuesFor( $meta_key ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->blogmeta} WHERE meta_key = %s AND meta_value <> '' GROUP BY meta_value ORDER BY meta_value ", $meta_key ) );
	}

	/**
	 * @param string $meta_key
	 *
	 * @return array
	 */
	public function getPossibleCommaDelimitedValuesFor( $meta_key ) {
		$exports_by_format = [];
		$hot_mess = $this->getPossibleValuesFor( $meta_key );
		foreach ( $hot_mess as $comma_delimited_string ) {
			$types = explode( ',', $comma_delimited_string );
			foreach ( $types as $type ) {
				$type = trim( $type );
				$exports_by_format[ $type ] = true;
			}
		}
		unset( $exports_by_format[''] );
		$exports_by_format = array_keys( $exports_by_format );
		return $exports_by_format;
	}

	/**
	 * @return int
	 */
	public function getTotalNetworkStorageBytes() {
		global $wpdb;
		$total = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(meta_value) FROM {$wpdb->blogmeta} WHERE meta_key = %s ", self::STORAGE_SIZE ) );
		return (int) $total;
	}

	/**
	 * @return int
	 */
	public function getTotalBooks() {
		global $wpdb;
		$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT blog_id) FROM {$wpdb->blogmeta} WHERE meta_key = %s ", self::TIMESTAMP ) );
		return (int) $total;
	}


	// ------------------------------------------------------------------------
	// Private
	// ------------------------------------------------------------------------

	/**
	 * @return int
	 */
	private function revisions() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} " );
	}

	/**
	 * @return int
	 */
	private function h5pActivities() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}h5p_contents " );
	}

	/**
	 * @return int
	 */
	private function glossaryTerms() {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND (post_status != 'trash' AND post_status != 'inherit') ", 'glossary' ) );
	}

	/**
	 * @return int
	 */
	private function tablepressTables() {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND (post_status != 'trash' AND post_status != 'inherit') ", 'tablepress_table' ) );
	}

}
