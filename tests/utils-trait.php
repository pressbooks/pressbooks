<?php

trait utilsTrait {

	/**
	 * Create and switch to a new test book
	 *
	 * @param string $theme (optional)
	 * @param bool $with_media
	 */
	private function _book( $theme = 'pressbooks-book', $with_media = false ) {
		add_filter( 'pb_redirect_to_new_book', '__return_false' );
		$blog_id = $this->factory()->blog->create();
		switch_to_blog( $blog_id );
		switch_theme( $theme );
		if ( ! post_type_exists( 'chapter' ) ) {
			\Pressbooks\PostType\register_post_types();
		}

		// Front Matter, First Part & Chapters, Back Matter
		$book = \Pressbooks\Book::getInstance();
		$pid = $this->_createChapter( 0, $with_media );
		$last_part_menu_order = 0;
		foreach ( $book::getBookStructure() as $key => $section ) {
			if ( $key === 'front-matter' || $key === 'back-matter' ) {
				foreach ( $section as $val ) {
					update_post_meta( $val['ID'], 'pb_export', 'on' );
					wp_update_post( [ 'ID' => $val['ID'], 'post_status' => 'publish' ] );
				}
			}
			if ( $key === 'part' ) {
				foreach ( $section as $part ) {
					if ( $part['menu_order'] > $last_part_menu_order ) {
						$last_part_menu_order = $part['menu_order'];
					}
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

		// Second Part & Chapter
		$new_post = [
			'post_title' => 'Test Part: ' . rand(),
			'post_type' => 'part',
			'post_status' => 'publish',
			'post_content' => 'Part content...',
			'menu_order' => ( $last_part_menu_order + 1 ),
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$this->_createChapter( $pid, $with_media );

		$book::deleteBookObjectCache();
	}

	/**
	 * Creates chapter
	 *
	 * @param int $post_parent
	 * @param bool $with_media
	 *
	 * @return int
	 */
	private function _createChapter( $post_parent = 0, $with_media = false ) {

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

<em>Ka kite ano!</em><b></b>

<p><a href="/back-matter/appendix/">Link to another post.</a></p>

<p><a href="https://github.com/pressbooks/pressbooks#hello-world">External link.</a></p>
';

		if ( $with_media ) {
			$temp = tempnam( sys_get_temp_dir(), 'TMP_' );
			file_put_contents( $temp, file_get_contents( __DIR__ . '/data/monkey.mp4' ) );
			$pid = media_handle_sideload(
				[
					'name' => 'monkey.mp4',
					'tmp_name' => $temp,
				], 0
			);
			$video_url = wp_get_attachment_url( $pid );

			$temp = tempnam( sys_get_temp_dir(), 'TMP_' );
			file_put_contents( $temp, file_get_contents( __DIR__ . '/data/mountains.jpg' ) );
			$pid = media_handle_sideload(
				[
					'name' => 'mountains.jpg',
					'tmp_name' => $temp,
				], 0
			);
			$thumbnail_html = wp_get_attachment_image( $pid, 'thumbnail' );

			$content .= "
[video]{$video_url}[/video]

{$thumbnail_html}
";
		}

		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'chapter',
			'post_status' => 'publish',
			'post_content' => trim( $content ),
			'post_parent' => $post_parent,
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
	 * @return int
	 */
	private function _fakeAjax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', '__return_false', 1, 1 ); // Override die()
		return error_reporting( error_reporting() & ~E_WARNING ); // Suppress warnings
	}

	/**
	 * @param int $old_error_reporting
	 */
	private function _fakeAjaxDone( $old_error_reporting ) {
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', '__return_false', 1 );
		error_reporting( $old_error_reporting );
	}

	/**
	 * Create CC-BY book
	 *
	 * @param bool $with_media
	 */
	private function _openTextbook( $with_media = false ) {
		$this->_book( 'pressbooks-book', $with_media );
		update_option( 'blog_public', 1 );
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		update_post_meta( $meta_post->ID, 'pb_book_license', 'cc-by' );
		wp_set_object_terms( $meta_post->ID, 'cc-by', \Pressbooks\Licensing::TAXONOMY ); // Link
		\Pressbooks\Book::deleteBookObjectCache();
	}

	/**
	 * @return Spy_REST_Server
	 */
	public function _setupBookApi() {

		global $wp_rest_server;
		$server = $wp_rest_server = new \Spy_REST_Server();
		$this->_book();

		// PHPUnit is initialized as main site, $is_book hooks are never loaded...
		if ( ! post_type_exists( 'chapter' ) ) {
			\Pressbooks\PostType\register_post_types();
		}
		if ( ! registered_meta_key_exists( 'post', 'pb_media_attribution_author', 'attachment' ) ) {
			\Pressbooks\PostType\register_meta();
		}
		\Pressbooks\Metadata\init_book_data_models();
		remove_action( 'rest_api_init', '\Pressbooks\Api\init_root' );
		add_action( 'rest_api_init', '\Pressbooks\Api\init_book' );
		add_filter( 'rest_endpoints', 'Pressbooks\Api\hide_endpoints_from_book' );
		add_filter( 'rest_url', '\Pressbooks\Api\fix_book_urls', 10, 2 );
		add_filter( 'rest_prepare_attachment', '\Pressbooks\Api\fix_attachment', 10, 3 );

		do_action( 'rest_api_init' );
		return $server;
	}

	/**
	 * @return Spy_REST_Server
	 */
	public function _setupRootApi() {

		global $wp_rest_server;
		$server = $wp_rest_server = new \Spy_REST_Server;
		do_action( 'rest_api_init' );
		return $server;
	}

	/**
	 * Let us add some iframes.
	 */
	public function _allowIframes( $allowed, $context ) {
		if ( $context !== 'post' ) {
			return $allowed;
		}
		$allowed['iframe'] = [
			'src' => true,
			'width' => true,
			'height' => true,
			'frameborder' => true,
			'marginwidth' => true,
			'marginheight' => true,
			'scrolling' => true,
			'title' => true,
		];

		return $allowed;
	}
}
