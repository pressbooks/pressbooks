<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

use function Pressbooks\Sanitize\sanitize_string;

class RelatedLink extends Field {
	public string $view = 'related-link';

	public function getValue() {
		global $post;

		if ( ! $post ) {
			return [];
		}

		$titles = get_post_meta( $post->ID, $this->name . '_title', false );
		$urls = get_post_meta( $post->ID, $this->name . '_url', false );
		$descriptions = get_post_meta( $post->ID, $this->name . '_description', false );
		$privacy = get_post_meta( $post->ID, $this->name . '_privacy', false );

		$values = [];
		foreach ( $titles as $index => $title ) {
			if ( ! empty( $title ) && ! empty( $urls[ $index ] ) ) {
				$values[] = [
					'title' => $title,
					'url' => $urls[ $index ],
					'description' => $descriptions[ $index ] ?? '',
					'privacy' => isset( $privacy[ $index ] ) && $privacy[ $index ] === 'private' ? 'private' : 'public',
				];
			}
		}

		return $values;
	}

	public function sanitize( mixed $value ): mixed {
		// Sanitize is handled in save method for complex fields
		return $value;
	}

	public function save( int $post_id, mixed $value ): void {
		// Delete existing values
		delete_post_meta( $post_id, $this->name . '_title' );
		delete_post_meta( $post_id, $this->name . '_url' );
		delete_post_meta( $post_id, $this->name . '_description' );
		delete_post_meta( $post_id, $this->name . '_privacy' );

		// Handle the data structure from $_POST
		if ( isset( $value['title'] ) && is_array( $value['title'] ) ) {
			$titles = $value['title'];
			$urls = $value['url'] ?? [];
			$descriptions = $value['description'] ?? [];
			$privacy = $value['privacy'] ?? [];

			foreach ( $titles as $index => $title ) {
				$title = trim( sanitize_text_field( $title ) );
				$url = isset( $urls[ $index ] ) ? trim( sanitize_text_field( $urls[ $index ] ) ) : '';
				
				// Both title and URL are required
				if ( ! empty( $title ) && ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
					add_post_meta( $post_id, $this->name . '_title', $title, false );
					add_post_meta( $post_id, $this->name . '_url', esc_url_raw( $url ), false );
					
					$desc = isset( $descriptions[ $index ] ) ? trim( sanitize_text_field( $descriptions[ $index ] ) ) : '';
					add_post_meta( $post_id, $this->name . '_description', $desc, false );
					
					$priv = isset( $privacy[ $index ] ) && $privacy[ $index ] === 'private' ? 'private' : 'public';
					add_post_meta( $post_id, $this->name . '_privacy', $priv, false );
				}
			}
		}
	}
}

