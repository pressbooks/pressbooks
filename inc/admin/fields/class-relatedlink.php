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

		$urls = get_post_meta( $post->ID, $this->name . '_url', false );
		$descriptions = get_post_meta( $post->ID, $this->name . '_description', false );
		$privacy = get_post_meta( $post->ID, $this->name . '_privacy', false );

		$values = [];
		foreach ( $urls as $index => $url ) {
			if ( ! empty( $url ) ) {
				$values[] = [
					'url' => $url,
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
		delete_post_meta( $post_id, $this->name . '_url' );
		delete_post_meta( $post_id, $this->name . '_description' );
		delete_post_meta( $post_id, $this->name . '_privacy' );

		// Handle the data structure from $_POST
		if ( isset( $value['url'] ) && is_array( $value['url'] ) ) {
			$urls = $value['url'];
			$descriptions = $value['description'] ?? [];
			$privacy = $value['privacy'] ?? [];

			foreach ( $urls as $index => $url ) {
				$url = trim( sanitize_text_field( $url ) );
				
				// Validate URL
				if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
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

