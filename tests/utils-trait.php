<?php

trait utilsTrait {

	/**
	 * Create and switch to a new test book
	 *
	 * @param string $theme (optional)
	 */
	private function _book( $theme = 'pressbooks-book' ) {

		$blog_id = $this->factory->blog->create();
		switch_to_blog( $blog_id );
		switch_theme( $theme );
	}


	/**
	 * Create a temporary directory, no trailing slash!
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function _createTmpDir() {

		$temp_file = tempnam( sys_get_temp_dir(), '' );
		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}
		mkdir( $temp_file );
		if ( ! is_dir( $temp_file ) ) {
			throw new \Exception( 'Could not create temporary directory.' );

		}

		return untrailingslashit( $temp_file );
	}


	/**
	 * Fake ajax
	 */
	private function _fakeAjax() {

		if ( ! defined( 'DOING_AJAX' ) ) define( 'DOING_AJAX', true );
		add_filter( 'wp_die_ajax_handler', '__return_false', 1, 1 ); // Override die()
		error_reporting( error_reporting() & ~E_WARNING ); // Suppress warnings
	}


}
