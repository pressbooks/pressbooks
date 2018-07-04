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
	 * Constructor.
	 */
	public function __construct() {

	}

}
