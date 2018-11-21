<?php

namespace Pressbooks\Covergenerator;

class Covergenerator {

	/**
	 * @var Covergenerator
	 */
	private static $instance = null;

	/**
	 * @return Covergenerator
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Covergenerator $obj
	 */
	static public function hooks( Covergenerator $obj ) {
		if ( is_admin() ) {
			// Look & feel of admin interface and Dashboard
			add_action( 'admin_menu', '\Pressbooks\Admin\Covergenerator\generator_menu' );
			add_action( 'admin_init', '\Pressbooks\Admin\Covergenerator\generator_css_js' );
			add_action( 'admin_init', '\Pressbooks\Admin\Covergenerator\cg_options_init' );

			// "Catch-all" routines, must come after taxonomies and friends
			add_action( 'admin_post_pb_generate_cover', [ '\Pressbooks\Covergenerator\Generator', 'formSubmit' ] );
			add_action( 'admin_post_pb_delete_cover', [ '\Pressbooks\Covergenerator\Generator', 'formDelete' ] );
			add_action( 'admin_post_pb_delete_all_covers', [ '\Pressbooks\Covergenerator\Generator', 'formDeleteAll' ] );
			add_action( 'admin_post_pb_download_cover', [ '\Pressbooks\Covergenerator\Generator', 'formDownload' ] );

			// Handle image uploads
			add_filter( 'wp_handle_upload_prefilter', '\Pressbooks\Admin\Covergenerator\validate_image_size', 10, 1 );
		}
	}

	/**
	 * Set defaults for command line utilities
	 */
	static public function commandLineDefaults() {
		if ( ! defined( 'PB_CONVERT_COMMAND' ) ) {
			define( 'PB_CONVERT_COMMAND', '/usr/bin/convert' );
		}
		if ( ! defined( 'PB_GS_COMMAND' ) ) {
			define( 'PB_GS_COMMAND', '/usr/bin/gs' );
		}
		if ( ! defined( 'PB_PDFINFO_COMMAND' ) ) {
			define( 'PB_PDFINFO_COMMAND', '/usr/bin/pdfinfo' );
		}
		if ( ! defined( 'PB_PDFTOPPM_COMMAND' ) ) {
			define( 'PB_PDFTOPPM_COMMAND', '/usr/bin/pdftoppm' );
		}
		if ( ! defined( 'PB_PRINCE_COMMAND' ) ) {
			define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
		}
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::commandLineDefaults();
	}


	/**
	 * @return bool
	 */
	function hasDependencies() {
		$commands = [
			PB_CONVERT_COMMAND . ' --version' => '6.7',
			PB_GS_COMMAND . ' --version' => '8.7',
			PB_PDFINFO_COMMAND . ' -v' => '0.12.4',
			PB_PDFTOPPM_COMMAND . ' -v' => '0.12.4',
		];

		$not_found = [];
		foreach ( $commands as $command => $version ) {
			$output = [];
			$return_val = 0;

			exec( $command . ' 2>&1', $output, $return_val );
			if ( empty( $output ) ) {
				$not_found[] = $command;
				continue;
			}

			preg_match( '/[0-9]+(\.[0-9]+)+/', $output[0], $matches );
			if ( empty( $matches ) ) {
				$not_found[] = $command;
				continue;
			}

			if ( version_compare( $matches[0], $version ) < 0 ) {
				return false;
			}
		}

		if ( ! \Pressbooks\Modules\Export\Prince\Filters::hasDependencies() ) {
			return false;
		}

		return true;
	}

}
