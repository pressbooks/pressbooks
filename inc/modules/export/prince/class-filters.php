<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

use function Pressbooks\Utility\check_prince_install;
use function Pressbooks\Utility\check_xmllint_install;

class Filters {

	private static ?Filters $instance = null;

	/**
	 * @return Filters|null
	 */
	public static function init(): ?Filters {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Filters $obj
	 */
	public static function hooks( Filters $obj ): void {
		if ( $obj->overridePrince() ) {
			add_filter( 'pb_export_formats', [ $obj, 'addToFormats' ] );
			add_filter( 'pb_active_export_modules', [ $obj, 'addToModules' ] );
			add_filter( 'pb_available_export_module_slug_to_classname_map', function( $map ) {
				$map['docraptor'] = '\Pressbooks\Modules\Export\Prince\Docraptor';
				$map['docraptor_print'] = '\Pressbooks\Modules\Export\Prince\DocraptorPrint';
				return $map;
			} );
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
	public function overridePrince(): bool {
		$use_hooks = true;
		if ( ! defined( 'DOCRAPTOR_API_KEY' ) ) {
			// No API key
			$use_hooks = false;
		}
		$plugin = 'pressbooks-docraptor/pressbooks-docraptor.php';
		if ( file_exists( WP_PLUGIN_DIR . "/$plugin" ) && is_plugin_active( $plugin ) ) {
			// The old, deprecated plugin is active
			$use_hooks = false;
		}
		return $use_hooks;
	}

	/**
	 * @param array $formats a multidimensional array of standard and exotic formats
	 *
	 * @return array $formats
	 *@since 5.4.0
	 *
	 * Add this format to the export page formats list.
	 *
	 */
	public function addToFormats( array $formats ): array {
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
	 * @param array $modules an array of active export module classnames
	 *
	 * @return array $modules
	 * @since 5.4.0
	 *
	 * Add this module to the export batch currently in progress.
	 *
	 */
	public function addToModules( array $modules ): array {
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
	public static function hasDependencies(): bool {
		if ( ! check_xmllint_install() ) {
			return false;
		}
		return defined( 'DOCRAPTOR_API_KEY' ) || check_prince_install();
	}

}
