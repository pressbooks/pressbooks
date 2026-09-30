<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.NonceVerification.Missing
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated

namespace Pressbooks\Modules\Export;

use Pressbooks\Admin\Network\SharingAndPrivacyOptions;
use Pressbooks\Container;
use Pressbooks\Contributors;
use Pressbooks\Modules\BackgroundProcessing\BackgroundJob;
use Pressbooks\Modules\Export\Epub\Epub;
use Pressbooks\Modules\Export\Prince\Filters;
use Pressbooks\Modules\Export\Xhtml\Xhtml11;
use Pressbooks\Theme\Lock;
use function Pressbooks\Sanitize\fix_audio_shortcode;

/**
 * @return array
 */
function dependency_errors(): array {
	$dependency_errors = [];

	if ( false === (bool) get_site_transient( 'pb_pdf_compatible' ) && false === (bool) Filters::hasDependencies() ) {
		$dependency_errors['pdf'] = 'PDF';
	} else {
		set_site_transient( 'pb_pdf_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_print_pdf_compatible' ) && false === (bool) Filters::hasDependencies() ) {
		$dependency_errors['print_pdf'] = 'Print PDF';
	} else {
		set_site_transient( 'pb_print_pdf_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_epub_compatible' ) && false === (bool) Epub::hasDependencies() ) {
		$dependency_errors['epub'] = 'EPUB';
	} else {
		set_site_transient( 'pb_epub_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_xhtml_compatible' ) && false === (bool) Xhtml11::hasDependencies() ) {
		$dependency_errors['xhtml'] = 'XHTML';
	} else {
		set_site_transient( 'pb_xhtml_compatible', true );
	}
	/**
	 * Filter the array of dependency errors, remove unwanted formats.
	 *
	 * @since 3.9.8
	 *
	 * @param array $dependency_errors
	 */
	return apply_filters( 'pb_dependency_errors', $dependency_errors );
}

/**
 * @return string
 */
function dependency_errors_msg(): string {
	$dependency_errors = dependency_errors();
	if ( empty( $dependency_errors ) ) {
		return '';
	}

	$formats = implode( ', ', $dependency_errors );
	$pos = strrpos( $formats, ', ' );
	return sprintf(
		'<div class="error" role="alert"><p>%s</p></div>',
		sprintf(
			__( 'Some dependencies for %1$s exports could not be found. Please verify that you have completed the <a href="%2$s">installation instructions</a>.', 'pressbooks' ),
			( $pos ) ? substr_replace( $formats, ', ' . __( 'and', 'pressbooks' ) . ' ', $pos, strlen( ', ' ) ) : $formats,
			'https://pressbooks.org/user-docs/installation/'
		)
	);
}

/**
 * @return array
 */
function formats(): array {
	$formats = [
		'standard' => [
			'print_pdf' => __( 'PDF (for print)', 'pressbooks' ),
			'pdf' => __( 'PDF (for digital distribution)', 'pressbooks' ),
			'epub' => __( 'EPUB', 'pressbooks' ),
			'wxr' => __( 'Pressbooks XML', 'pressbooks' ),
		],
		'exotic' => [
			'xhtml' => __( 'XHTML', 'pressbooks' ),
			'vanillawxr' => __( 'WordPress XML', 'pressbooks' ),
		],
	];

	// Common Cartridge 1.1 (Web Links)

	$enable_thincc_weblinks = SharingAndPrivacyOptions::getOption( 'enable_thincc_weblinks' );
	if ( $enable_thincc_weblinks ) {
		$formats['standard']['weblinks'] = __( 'Common Cartridge with Web Links', 'pressbooks' );
	}

	/**
	 * @since 3.9.8
	 * Add custom export formats to the export page format list.
	 *
	 * For example, here's how one might add a hypothetical Word export format:
	 *
	 * add_filter( 'pb_export_formats', function ( $formats ) {
	 *    $formats['exotic']['docx'] = __( 'Word (Beta)', 'pressbooks' );
	 *    return $formats;
	 * } );
	 */
	return apply_filters( 'pb_export_formats', $formats );
}

/**
 * @return array
 */
function filetypes(): array {
	/**
	 * Add custom export formats to the latest exports filetype mapping array.
	 *
	 * For example, here's how one might add a hypothetical Word export format:
	 *
	 * add_filter( 'pb_latest_export_filetypes', function ( $filetypes ) {
	 *    $filetypes['word'] = '.docx';
	 *    return $filetypes;
	 * } );
	 *
	 * @since 3.9.8
	 *
	 * @param array $value
	 */
	return apply_filters(
		'pb_latest_export_filetypes', [
			'epub3' => '._3.epub',
			'epub' => '.epub',
			'pdf' => '.pdf',
			'print_pdf' => '._print.pdf',
			'mobi' => '.mobi',
			'icml' => '.icml',
			'xhtml' => '.html',
			'wxr' => '.xml',
			'vanillawxr' => '._vanilla.xml',
			'mpdf' => '._oss.pdf',
			'weblinks' => '._1_1_weblinks.imscc',
			'thincc11' => '._1_1.imscc',
			'thincc12' => '._1_2.imscc',
			'thincc13' => '._1_3.imscc',
		]
	);
}

/**
 * Return a human-readable filetype for a given filetype slug.
 *
 * @since 5.7.0
 *
 * @param string $filetype The filetype slug.
 *
 * @return string A human-readable filetype.
 */
function get_name_from_filetype_slug( $filetype ): string {
	/**
	 * Add custom export file type slugs to the array of file type slugs and corresponding human-readable filetypes.
	 *
	 * @since 5.7.0
	 */
	$formats = apply_filters(
		'pb_export_filetype_names', [
			'print_pdf' => __( 'Print PDF', 'pressbooks' ),
			'pdf' => __( 'Digital PDF', 'pressbooks' ),
			'mpdf' => __( 'Digital PDF', 'pressbooks' ),
			'epub' => __( 'EPUB', 'pressbooks' ),
			'mobi' => __( 'MOBI', 'pressbooks' ),
			'epub3' => __( 'EPUB3', 'pressbooks' ),
			'xhtml' => __( 'XHTML', 'presbooks' ),
			'wxr' => __( 'Pressbooks XML', 'pressbooks' ),
			'vanillawxr' => __( 'WordPress XML', 'pressbooks' ),
			'weblinks' => __( 'Common Cartridge (Web Links)', 'pressbooks' ),
			'thincc11' => __( 'Common Cartridge (LTI Links)', 'pressbooks' ),
			'thincc12' => __( 'Common Cartridge (LTI Links)', 'pressbooks' ),
			'thincc13' => __( 'Common Cartridge (LTI Links)', 'pressbooks' ),
		]
	);
	return $formats[ $filetype ] ?? ucfirst( $filetype );
}

/**
 * Return a human-readable short filetype for a given filetype slug. Used on icon badges.
 *
 * @since 6.1.0
 *
 * @param string $filetype The filetype slug.
 *
 * @return string A human-readable short filetype.
 */
function get_shortname_from_filetype_slug( $filetype ): string {
	/**
	 * Add custom export file type slugs to the array of file type slugs and corresponding human-readable short filetypes.
	 *
	 * @since 6.1.0
	 */
	$formats = apply_filters(
		'pb_export_filetype_shortnames', [
			'print_pdf' => __( 'Print PDF', 'pressbooks' ),
			'pdf' => __( 'PDF', 'pressbooks' ),
			'mpdf' => __( 'PDF', 'pressbooks' ),
			'epub' => __( 'EPUB', 'pressbooks' ),
			'mobi' => __( 'MOBI', 'pressbooks' ),
			'epub3' => __( 'EPUB3', 'pressbooks' ),
			'xhtml' => __( 'XHTML', 'presbooks' ),
			'wxr' => __( 'WXR', 'pressbooks' ),
			'vanillawxr' => __( 'WXR', 'pressbooks' ),
			'weblinks' => __( 'IMSCC', 'pressbooks' ),
			'thincc11' => __( 'IMSCC', 'pressbooks' ),
			'thincc12' => __( 'IMSCC', 'pressbooks' ),
			'thincc13' => __( 'IMSCC', 'pressbooks' ),
		]
	);
	return $formats[ $filetype ] ?? ucfirst( $filetype );
}

/**
 * Return a human-readable filetype for a given export module classname.
 *
 * @since 5.7.0
 *
 * @param string $classname The export module classname.
 *
 * @return string A human-readable filetype.
 */
function get_name_from_module_classname( $classname ): string {

	/**
	 * Add custom export module classnames to the array of export module classnames and corresponding human-readable filetypes.
	 *
	 * @since 5.7.0
	 */
	$formats = apply_filters(
		'pb_export_module_classnames', [
			'\Pressbooks\Modules\Export\Prince\DocraptorPrint' => __( 'Print PDF', 'pressbooks' ),
			'\Pressbooks\Modules\Export\Prince\Docraptor' => __( 'Digital PDF', 'pressbooks' ),
			'\Pressbooks\Modules\Export\Prince\PrintPdf' => __( 'Print PDF', 'pressbooks' ),
			'\Pressbooks\Modules\Export\Prince\Pdf' => __( 'Digital PDF', 'pressbooks' ),
			'\Pressbooks\Modules\Export\Epub\Epub' => __( 'EPUB', 'pressbooks' ),
			'\Pressbooks\Modules\Export\Xhtml\Xhtml11' => __( 'XHTML', 'presbooks' ),
			'\Pressbooks\Modules\Export\WordPress\Wxr' => __( 'Pressbooks XML', 'pressbooks' ),
			'\Pressbooks\Modules\Export\WordPress\VanillaWxr' => __( 'WordPress XML', 'pressbooks' ),
			'\Pressbooks\Modules\Export\ThinCC\WebLinks' => __( 'Common Cartridge (Web Links)', 'pressbooks' ),
		]
	);
	$parts = explode( '\\', $classname );
	return $formats[ $classname ] ?? end( $parts );
}

/**
 * @return array
 */
function template_data(): array {

	$pdf_preview_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin-post.php?action=pdf_preview' ), 'pdf-preview' );
	$export_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export&export=yes' ), 'pb-export' );

	$theme_name = wp_get_theme()->display( 'Name' ) . ' ' . wp_get_theme()->display( 'Version' );
	if ( Lock::init()->isLocked() ) {
		$theme_name .= '<span class="dashicons dashicons-lock" style="vertical-align: text-bottom;"></span>';
	}

	return [
		'pdf_preview_url' => $pdf_preview_url,
		'export_form_url' => $export_form_url,
		'dependency_errors' => dependency_errors(),
		'dependency_errors_msg' => dependency_errors_msg(),
		'formats' => formats(),
		'theme_name' => $theme_name,
	];
}

/**
 * Check if a given export module classname has reached the pinned export limit.
 *
 * @param string $module_classname The export module classname.
 * @param int $max_per_format The maximum number of pins allowed per format (default 3).
 *
 * @return bool True if the format has reached its pin limit.
 */
function is_format_pin_limited( string $module_classname, int $max_per_format = 3 ): bool {
	$pins = get_option( Table::PIN, [] );
	if ( ! is_array( $pins ) || empty( $pins ) ) {
		return false;
	}
	$format_hash = hash( 'crc32b', get_name_from_module_classname( $module_classname ) );
	$count = count( array_filter( $pins, fn( $v ) => $v === $format_hash ) );
	return $count >= $max_per_format;
}

/**
 * WP_Ajax
 */
function update_pins(): void {
	check_ajax_referer( 'pb-export-pins' );
	$pins = json_decode( stripcslashes( $_POST['pins'] ), true );
	if ( is_array( $pins ) ) {
		update_option( Table::PIN, $pins );
		$data = [
			'message' => sprintf(
				__( 'The file %1$s has been %2$s successfully.', 'pressbooks' ),
				$_POST['file'],
				$_POST['pinned'] ? __( 'pinned', 'pressbooks' ) : __( 'unpinned', 'pressbooks' )
			),
		];
		wp_send_json_success( $data );
	}
}

/**
 * Get the HTML for "About the Authors" section given a chapter ID.
 *
 * @param $post_id Integer
 * @return string
 */
function get_contributors_section( $post_id ): string {
	$contributors = new Contributors();
	$chapter_contributors = $contributors->getContributorsWithMeta( $post_id, 'authors' );
	if ( empty( $chapter_contributors ) ) {
		return '';
	}
	$title = _n( 'About the author', 'About the authors', count( $chapter_contributors ), 'pressbooks' );
	$print = '<div class="contributors">';
	$print .= "<h3 class=\"about-authors\">{$title}</h3>";
	$blade_engine = Container::get( 'Blade' );
	foreach ( $chapter_contributors as $contributor ) {
		$print .= $blade_engine->render(
			'posttypes.contributor', [
				'contributor' => $contributor,
				'exporting' => true,
			]
		);
	}
	$print .= '</div>';
	return $print;
}

function get_friendly_name_for_module( string $module_classname ): string {
	return get_name_from_module_classname( $module_classname );
}

/**
 * AJAX handler for submitting export jobs.
 * This method now uses the centralized processAndQueueJobRequests method.
 */
function handle_exports_submit(): void {

	check_ajax_referer( 'pb-export-book', 'pb_export_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'pressbooks' ) ], 403 );
		return;
	}

	$export_formats_from_post = isset( $_POST['export_formats'] ) && is_array( $_POST['export_formats'] ) ? $_POST['export_formats'] : [];

	$sanitized_export_formats = [];
	foreach ( $export_formats_from_post as $key => $value ) {
		$sanitized_export_formats[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
	}

	if ( empty( $sanitized_export_formats ) ) {
		wp_send_json_error( [ 'message' => __( 'No export formats selected.', 'pressbooks' ) ], 400 );
		return;
	}

	$export_options_from_post = isset( $_POST['export_options'] ) && is_array( $_POST['export_options'] ) ? $_POST['export_options'] : [];

	$results = process_and_queue_job_requests( $sanitized_export_formats, $export_options_from_post );

	$has_successful_queues = false;
	$successfully_queued_count = 0;
	foreach ( $results as $result ) {
		if ( isset( $result['status'] ) && $result['status'] === 'success' && $result['event_type'] === 'job_queued' ) {
			$has_successful_queues = true;
			++$successfully_queued_count;
		}
	}

	if ( $has_successful_queues ) {
		wp_send_json_success( [
			'message' => sprintf( _n( '%d export job queued.', '%d export jobs processed.', $successfully_queued_count, 'pressbooks' ), $successfully_queued_count ),
			'results' => $results,
			'reload_on_complete' => true,
			'total_jobs' => $successfully_queued_count,
		] );
	} else {
		// If all failed or were skipped
		$specific_errors = [];
		foreach ( $results as $result ) {
			if ( isset( $result['status'] ) && $result['status'] === 'error' && isset( $result['message'] ) ) {
				$specific_errors[] = $result['message'];
			}
		}
		$error_message = ! empty( $specific_errors )
			? implode( '; ', $specific_errors )
			: __( 'No export jobs were successfully queued.', 'pressbooks' );
		wp_send_json_error( [
			'message' => $error_message,
			'results' => $results,
			'reload_on_complete' => false,
		], 400 );
	}
	// Ensure exit after sending JSON response.
	exit;
}

/**
 * Processes export format requests, queues background jobs, and schedules them.
 * This method is designed to be reusable by different AJAX handlers or processes.
 *
 * @param array $export_formats_input Associative array of export formats to process, e.g., ['pdf' => 'pdf', 'epub' => 'epub'].
 * @param array $export_options_input Associative array of export options.
 * @return array An array of results, with each item detailing the outcome for a format.
 */

function process_and_queue_job_requests( array $export_formats_input, array $export_options_input ): array {

	BackgroundJob::ensureExportsTable();

	$results = [];
	$available_modules = Export::modules();
	$locale_switched = switch_to_locale( Export::locale() );

	foreach ( array_keys( $export_formats_input ) as $format_slug_key ) {
		$format_slug = sanitize_text_field( $format_slug_key ); // Sanitize the key itself if it comes from user input.
		$module_classname = $available_modules[ $format_slug ] ?? null;

		if ( ! $module_classname || ! class_exists( $module_classname ) ) {
			$results[] = [
				'event_type' => 'job_queue_failed', // Consistent event_type for easier parsing
				'message' => sprintf( __( 'Invalid or unsupported export format: %s', 'pressbooks' ), esc_html( $format_slug ) ),
				'module_slug' => $format_slug,
				'status' => 'error',
			];
			continue;
		}

		// Only proceed if this module is available and valid.
		if ( in_array( $module_classname, $available_modules, true ) ) {

			// Check if the format has reached the pinned export limit.
			if ( is_format_pin_limited( $module_classname ) ) {
				$friendly_name = get_friendly_name_for_module( $module_classname );
				$results[] = [
					'event_type' => 'job_queue_failed',
					'message' => sprintf(
						/* translators: %s: human-readable export format name (e.g. "EPUB", "Digital PDF") */
						__( 'You already have 3 pinned %s exports. Please unpin one before generating a new export in this format.', 'pressbooks' ),
						$friendly_name
					),
					'module_slug' => $format_slug,
					'module_classname' => $module_classname,
					'format_name' => $friendly_name,
					'status' => 'error',
				];
				continue;
			}

			$constructor_args = [];

			$book_id = get_current_blog_id();
			$user_id = get_current_user_id();

			/** @var Manager $db */
			$db = app( 'db' );
			$insert_data = [
				'book_id' => $book_id,
				'user_id' => $user_id,
				'export_format' => $format_slug,
				'export_module_classname' => $module_classname,
				'export_options' => wp_json_encode( $constructor_args ),
				'status' => 'pending',
				'created_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			];
			$job_id = $db->table( BackgroundJob::JOBS_TABLE_NAME )->insertGetId( $insert_data );

			$friendly_name = get_friendly_name_for_module( $module_classname );

			if ( $job_id ) {
				// TODO: (bg) Review if this is the best way to schedule the job.
				wp_schedule_single_event( time(), 'pressbooks_process_export_job', [ 'job_id' => $job_id ] );

				$results[] = [
					'event_type' => 'job_queued',
					'book_id' => $book_id,
					'job_id' => $job_id,
					'message' => sprintf( __( '%s export has been queued (Job ID: %d).', 'pressbooks' ), $friendly_name, $job_id ),
					'module_slug' => $format_slug,
					'module_classname' => $module_classname,
					'format_name' => $friendly_name,
					'status' => 'success',
				];
			} else {
				$results[] = [
					'event_type' => 'job_queue_failed',
					'message' => sprintf( __( 'Failed to queue %s export.', 'pressbooks' ), $friendly_name ),
					'module_slug' => $format_slug,
					'module_classname' => $module_classname,
					'format_name' => $friendly_name,
					'status' => 'error',
					'error_details' => 'Failed to insert job into the database.',
				];
			}
		}
	}

	if ( $locale_switched ) {
		restore_previous_locale();
	}

	return $results;
}

/**
 * Catch form submissions
 *
 * @see pressbooks/templates/admin/export.blade.php
 */
function handle_downloads(): void {

	if ( false === is_form_submission() || false === current_user_can( 'edit_posts' ) ) {
		return;
	}

	// Override some WP behaviours when exporting
	fix_audio_shortcode();

	// Download
	if ( ! empty( $_GET['download_export_file'] ) ) {
		$filename = sanitize_file_name( $_GET['download_export_file'] );
		Export::downloadExportFile( $filename, false );
		exit;
	}
}
/**
 * Check if a user submitted something to admin.php?page=pb_export
 *
 * @return bool
 */
function is_form_submission(): bool {

	if ( wp_doing_ajax() ) {
		if ( empty( $_REQUEST['action'] ) ) {
			return false;
		}

		if ( 'export-book' !== $_REQUEST['action'] ) {
			return false;
		}

		return true;
	}

	// Delete, Download, Etc.
	if ( empty( $_REQUEST['page'] ) ) {
		return false;
	}

	if ( 'pb_export' !== $_REQUEST['page'] ) {
		return false;
	}

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		return true;
	}

	if ( count( $_GET ) > 1 ) {
		return true;
	}

	return false;
}

function pb_xhtml_after_content_processed(): void {

	$upload_dir = Container::get( 'Sass' )->pathToUserGeneratedCss();
	$filename = 'scopedstyles.css';
	$css_path = $upload_dir . '/' . $filename;
	$css_content = apply_filters( 'pb_process_scoped_styles', '' );

	if ( empty( $css_content ) ) {
		if ( file_exists( $css_path ) ) {
			app( 'ScopedStyles' )->h5p_css_url = Container::get( 'Sass' )->urlToUserGeneratedCss( true ) . "/{$filename}";
		}
		return;
	}

	$optimized_css_path = $upload_dir . '/optimized-' . $filename;

	if ( file_exists( $css_path ) ) {
		unlink( $css_path );
	}

	$write_success = file_put_contents( $css_path, $css_content );

	$node_modules = WP_PLUGIN_DIR . '/pressbooks/node_modules/.bin';
	$purge_script = $node_modules . '/css-purge';

	if ( file_exists( $purge_script ) ) {
		$output = [];
		$return_var = 0;
		exec( "$purge_script -i $css_path -o $optimized_css_path", $output, $return_var );

		if ( $return_var !== 0 ) {
			error_log( 'Pressbooks Custom XHTML CSS: Failed to run css-purge' );
		}
	}

	$url = Container::get( 'Sass' )->urlToUserGeneratedCss( true ) . "/optimized-{$filename}";

	if ( $write_success ) {
		app( 'ScopedStyles' )->h5p_css_url = $url;
		return;
	}

	app( 'ScopedStyles' )->h5p_css_url = '';
}

/**
 * AJAX handler to cancel an export job.
 */
function handle_cancel_export_job(): void {
	check_ajax_referer( 'pb-export-book', 'pb_cancel_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'pressbooks' ) ], 403 );
		return;
	}

	$job_id = isset( $_POST['job_id'] ) ? intval( $_POST['job_id'] ) : 0;

	if ( $job_id <= 0 ) {
		wp_send_json_error( [ 'message' => __( 'Invalid job ID.', 'pressbooks' ) ], 400 );
		return;
	}

	$job = app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
		->where( 'id', $job_id )
		->first();

	if ( ! $job ) {
		wp_send_json_error( [ 'message' => __( 'Job not found or already processed.', 'pressbooks' ) ], 404 );
		return;
	}

	app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
		->where( 'id', $job_id )
		->delete();

	wp_send_json_success( [ 'message' => __( 'Export job canceled successfully.', 'pressbooks' ) ] );
}
