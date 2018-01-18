<?php

trait utilsTrait {

	/**
	 * Create and switch to a new test book
	 *
	 * @param string $theme (optional)
	 */
	private function _book( $theme = 'pressbooks-book' ) {
		add_filter( 'pb_redirect_to_new_book', '__return_false' );
		$blog_id = $this->factory()->blog->create();
		switch_to_blog( $blog_id );
		switch_theme( $theme );

		// Export = on
		$book = \Pressbooks\Book::getInstance();
		$pid = $this->_createChapter();
		foreach ( $book::getBookStructure() as $key => $section ) {
			if ( $key === 'front-matter' || $key === 'back-matter' ) {
				foreach ( $section as $val ) {
					update_post_meta( $val['ID'], 'pb_export', 'on' );
				}
			}
			if ( $key === 'part' ) {
				foreach ( $section as $part ) {
					wp_update_post( [ 'ID' => $part['ID'], 'post_content' => 'Part content...', 'post_status' => 'publish' ] );
					if ( $pid ) {
						wp_update_post( [ 'ID' => $pid, 'post_parent' => $part['ID'] ] );
						wp_update_post( [ 'ID' => $pid, 'post_status' => 'publish' ] );
						$pid = false;
					}
					foreach ( $part['chapters'] as $val ) {
						update_post_meta( $val['ID'], 'pb_export', 'on' );
						wp_update_post( [ 'ID' => $val['ID'], 'post_status' => 'publish' ] );
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

		$content = '<em>Kia ora tatou!</em>

<h1>Footnote</h1>

Footnotes are cool. [footnote]but endnotes are cooler?[/footnote]

<h1>Math</h1>

This is my math:

$latex \displaystyle P_\nu^{-\mu}(z)=\frac{\left(z^2-1\right)^{\frac{\mu}{2}}}{2^\mu \sqrt{\pi}\Gamma\left(\mu+\frac{1}{2}\right)}\int_{-1}^1\frac{\left(1-t^2\right)^{\mu -\frac{1}{2}}}{\left(z+t\sqrt{z^2-1}\right)^{\mu-\nu}}dt$$

Also my math:

[latex]e^{\i \pi} + 1 = 0[/latex]

There are many maths like it but these ones are mine.

<h1>Image</h1>

[caption id="attachment_1" align="aligncenter" width="225"]<img class="wp-image-1 size-full" src="https://pressbooks.com/wp-content/uploads/2015/04/open-book-7-225x161.png" alt="Open Book" width="225" height="161" /> Like an open book.[/caption]

<em>Ka kite ano!</em>

<p><a href="/back-matter/appendix/">Link to another post.</a></p>

<p><a href="https://github.com/pressbooks/pressbooks#hello-world">External link.</a></p>
';

		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'chapter',
			'post_status' => 'publish',
			'post_content' => trim( $content ),
		];
		$pid = $this->factory()->post->create_object( $new_post );
		update_post_meta( $pid, 'pb_export', 'on' );
		update_post_meta( $pid, 'pb_subtitle', 'Or, A Chapter to Test' );

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
