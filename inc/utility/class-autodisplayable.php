<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Utility;

/**
 * This trait is used to override any back-matter or front-matter with a custom data callback
 */
trait AutoDisplayable {
	/**
	 * @param string $content The post content
	 * @param callable $override The custom render callback
	 * @param string $taxonomy_query The current taxonomy to compare
	 * @param string $post_type The post-type like back-matter or front-matter
	 * @return mixed
	 */
	public function display( $content, $override, $taxonomy_query = 'glossary', $post_type = 'back-matter' ) {

		$post = get_post();
		if ( ! $post ) {
			// Try to find using deprecated means
			global $id;
			$post = get_post( $id );
		}
		if ( ! $post ) {
			// Unknown post
			return $content;
		}

		$valid_post_types = [ 'back-matter', 'front-matter' ];
		if ( ! in_array( $post_type, $valid_post_types, true ) ) {
			return $content;
		}

		$taxonomy = \Pressbooks\Taxonomy::init();

		if ( 'back-matter' === $post_type ) {
			$type = $taxonomy->getBackMatterType( $post->ID );
		} elseif ( 'front-matter' === $post_type ) {
			$type = $taxonomy->getFrontMatterType( $post->ID );
		}

		if ( $type !== $taxonomy_query ) {
			// Post is not overriding the view
			return $content;
		}

		if ( ! \Pressbooks\Utility\empty_space( \Pressbooks\Sanitize\decode( str_replace( '&nbsp;', '', $content ) ) ) ) {
			// Content is not empty
			return $content;
		}
			// Return overridden view
		return $override();
	}
}
