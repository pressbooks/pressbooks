<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

class CustomStyles {

	/**
	 * @var \Pressbooks\Sass
	 */
	protected $sass;

	/**
	 * @param \Pressbooks\Sass $sass
	 */
	public function __construct( $sass ) {
		$this->sass = $sass;
	}

	/**
	 * Set filters & hooks for editor UI
	 */
	public function init() {

		if ( ! Book::isBook() ) {
			return;
		}
		if ( class_exists( '\Pressbooks\CustomCss' ) && CustomCss::isCustomCss() ) {
			return;
		}

		add_action( 'admin_menu', function () {
			add_theme_page( __( 'Custom Styles', 'pressbooks' ), __( 'Custom Styles', 'pressbooks' ), 'edit_others_posts', 'pb_custom_styles', [ $this, 'editor' ] );
		}, 11 );
	}

	/**
	 *
	 */
	public function editor() {
		echo 'TODO';
		// require( PB_PLUGIN_DIR . 'templates/admin/custom-styles.php' );
	}


}
