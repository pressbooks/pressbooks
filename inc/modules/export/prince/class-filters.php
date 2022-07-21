<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

class Filters {

	private static ?\Pressbooks\Modules\Export\Prince\Filters $instance = null;

	/**
	 * @return Filters
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	public static function hooks( Filters $obj ) {
		if ( $obj->overridePrince() ) {
			add_filter( 'pb_export_formats', [ $obj, 'addToFormats' ] );
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
				'docraptor_print' => __( 'PDF (for print)', 'pressbooks' ),
				'docraptor' => __( 'PDF (for digital distribution)', 'pressbooks' ),
			] + $formats['standard'];

		unset( $formats['standard']['pdf'] );
		unset( $formats['standard']['print_pdf'] );

		return $formats;
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

	/**
	 * @return bool
	 */
	public static function hasDependencies() {
		if ( false === \Pressbooks\Utility\check_xmllint_install() ) {
			return false;
		}
		if ( ! defined( 'DOCRAPTOR_API_KEY' ) && false === \Pressbooks\Utility\check_prince_install() ) {
			return false;
		}
		return true;
	}

}
