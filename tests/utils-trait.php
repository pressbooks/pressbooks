<?php

trait utilsTrait {

	/**
	 * Create and switch to a new test book
	 *
	 * @param string $theme (optional)
	 */
	private function _book( $theme = 'pressbooks-book' ) {

		$blog_id = $this->factory()->blog->create();
		switch_to_blog( $blog_id );
		switch_theme( $theme );

		// Export = on
		$book = \Pressbooks\Book::getInstance();
		foreach ( $book::getBookStructure() as $key => $section ) {
			if ( $key === 'front-matter' || $key === 'back-matter' ) {
				foreach ( $section as $val ) {
					update_post_meta( $val['ID'], 'pb_export', 'on' );
				}
			}
			if ( $key === 'part' ) {
				foreach ( $section as $part ) {
					foreach ( $part['chapters'] as $val ) {
						update_post_meta( $val['ID'], 'pb_export', 'on' );
					}
				}
			}
		}

		$book::deleteBookObjectCache();
	}

	/**
	 * Creates chapter (no associated part, is orphan)
	 *
	 * @return int
	 */
	private function _createChapter() {
		$new_post = [
			'post_title' => 'test chapter',
			'post_type' => 'chapter',
			'post_status' => 'publish',
			'post_content' => 'some content',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		update_post_meta( $pid, 'pb_export', 'on' );

		return $pid;
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

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
		add_filter( 'wp_die_ajax_handler', '__return_false', 1, 1 ); // Override die()
		error_reporting( error_reporting() & ~E_WARNING ); // Suppress warnings
	}


}
