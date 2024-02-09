<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class ReorderableMultiselect extends Field {
	public array $options = [];

	public string $view = 'reorderable-multiselect';

	public function __construct( string $name, string $label, ?string $description = null, ?string $id = null, array $options = [] ) {
		parent::__construct( $name, $label, $description, $id );

		$this->options = $options;
	}

	public function getValue() {
		global $post;

		$value = get_post_meta( $post->ID, $this->name, false );

		return is_array($value) ? implode(',', array_filter($value)) : $value;
	}

	public function save( int $post_id, mixed $value ): void {
		$values = explode(',', implode('', $value));

		$this->delete( $post_id );
		foreach ( $values as $v ) {
			$v = trim( $this->sanitize( $v ) );
			if ( $v ) {
				add_post_meta( $post_id, $this->name, $v, false );
			}
		}
	}
}
