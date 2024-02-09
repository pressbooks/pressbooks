<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class TaxonomyReorderableMultiselect extends Field {
	public array $options = [];

	public string $taxonomy = '';

	public string $view = 'reorderable-multiselect';

	public function __construct( string $name, string $label, ?string $description = null, ?string $id = null, string $taxonomy = null ) {
		parent::__construct( $name, $label, $description, $id );

		$this->taxonomy = $taxonomy;
		$this->options = $this->getOptions();
	}

	public function getValue() {
		global $post;

		$value = get_post_meta( $post->ID, $this->name, false );

		return is_array($value) ? implode(',', array_filter($value)) : $value;
	}

	public function getOptions(): array {
		$terms = get_terms( $this->taxonomy, [ 'hide_empty' => false ] );

		$options = [];

		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}

	public function save( int $post_id, mixed $value ): void {
		$values = explode(',', implode('', $value));

		wp_set_object_terms( $post_id, $values, $this->taxonomy );

		$this->delete( $post_id );
		foreach ( $values as $v ) {
			$v = trim( $this->sanitize( $v ) );
			if ( $v ) {
				add_post_meta( $post_id, $this->name, $v, false );
			}
		}

	}
}

