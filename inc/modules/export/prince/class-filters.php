<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

class Filters {

	/**
	 * @var Filters
	 */
	private static $instance = null;

	/**
	 * @return Filters
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Filters $obj
	 */
	static public function hooks( Filters $obj ) {
		if ( $obj->overridePrince() ) {
			add_filter( 'pb_export_formats', [ $obj, 'addToFormats' ] );
			add_filter( 'pb_dependency_errors', [ $obj, 'hidePrinceErrors' ] );
			add_filter( 'pb_theme_options_tabs', [ $obj, 'registerPdfOptionsTab' ] );
			add_filter( 'pb_active_export_modules', [ $obj, 'addToModules' ] );
		}
	}

	/**
	 * @since 5.4.0
	 */
	public function __construct() {
	}

	/**
	 * @since 5.4.0
	 *
	 * @return bool
	 */
	public function overridePrince() {
		$use_hooks = true;
		if ( ! defined( 'DOCRAPTOR_API_KEY' ) ) {
			// No API key
			$use_hooks = false;
		}
		$plugin = 'pressbooks-docraptor/pressbooks-docraptor.php';
		if ( file_exists( WP_PLUGIN_DIR . "/{$plugin}" ) && is_plugin_active( $plugin ) ) {
			// The old, deprecated plugin is active
			$use_hooks = false;
		}
		return $use_hooks;
	}

	/**
	 * @since 5.4.0
	 *
	 * Add this format to the export page formats list.
	 *
	 * @param array $formats a multidimensional array of standard and exotic formats
	 *
	 * @return array $formats
	 */
	public function addToFormats( $formats ) {
		$formats['standard'] =
			[
				'docraptor_print' => __( 'PDF (for print)', 'pressbooks-docraptor' ),
				'docraptor' => __( 'PDF (for digital distribution)', 'pressbooks-docraptor' ),
			] + $formats['standard'];

		unset( $formats['standard']['pdf'] );
		unset( $formats['standard']['print_pdf'] );

		return $formats;
	}

	/**
	 * @since 5.4.0
	 *
	 * Hide Prince dependency errors if DocRaptor is enabled.
	 *
	 * @param array $dependency_errors an array of formats
	 *
	 * @return array $dependency_errors
	 */
	public function hidePrinceErrors( $dependency_errors ) {
		unset( $dependency_errors['pdf'] );
		unset( $dependency_errors['print_pdf'] );
		return $dependency_errors;
	}

	/**
	 * @since 5.4.0
	 *
	 * Make sure the PDF options tab is shown even if Prince is not installed.
	 *
	 * @param array $tabs And array of tabs, e.g. 'format' => '\Pressbooks\Modules\ThemeOptions\FormatOptions'
	 *
	 * @return array $tabs
	 */
	public function registerPdfOptionsTab( $tabs ) {
		$tmp = [
			'global' => '\Pressbooks\Modules\ThemeOptions\GlobalOptions',
			'web' => '\Pressbooks\Modules\ThemeOptions\WebOptions',
			'pdf' => '\Pressbooks\Modules\ThemeOptions\PDFOptions',
		];
		return array_merge( $tmp, $tabs );
	}

	/**
	 * @since 5.4.0
	 *
	 * Add this module to the export batch currently in progress.
	 *
	 * @param array $modules an array of active export module classnames
	 *
	 * @return array $modules
	 */
	public function addToModules( $modules ) {
		if ( isset( $_POST['export_formats']['docraptor'] ) && check_admin_referer( 'pb-export' ) ) {
			$modules[] = '\Pressbooks\Modules\Export\Prince\Docraptor';
		}
		if ( isset( $_POST['export_formats']['docraptor_print'] ) && check_admin_referer( 'pb-export' ) ) {
			$modules[] = '\Pressbooks\Modules\Export\Prince\DocraptorPrint';
		}
		return $modules;
	}

}
