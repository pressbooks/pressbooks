<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

use function Pressbooks\Sanitize\sanitize_string;
use Pressbooks\Container;

abstract class Field {
	/* The name attribute of the field. */
	public string $name;

	/* The label which will be associated with the field. */
	public string $label;

	/* The current value of the field. */
	public mixed $value;

	/* The description for the field. */
	public ?string $description;

	/* The id attribute of the field; defaults to the name. */
	public string $id;

	/* The view used to render the field. */
	public string $view;

	/* Whether the field should save multiple values. */
	public bool $multiple = false;

	/* Whether the field should allow HTML. */
	public bool $allowHtml = false;

	/* Whether the field is disabled. */
	public bool $disabled = false;

	/* Whether the field is read only. */
	public bool $readonly = false;

	public function __construct( string $name, string $label, ?string $description = null, ?string $id = null, bool $multiple = false, bool $disabled = false, bool $readonly = false ) {
		$this->name = $name;
		$this->label = $label;
		$this->description = $description;
		$this->id = $id ?? $this->name;
		$this->multiple = $multiple;
		$this->disabled = $disabled;
		$this->readonly = $readonly;
		$this->value = $this->getValue();
	}

	public function getValue() {
		global $post;

		return get_post_meta( $post->ID, $this->name, ! $this->multiple );
	}

	public function sanitize( mixed $value ): mixed {
		return $this->allowHtml ?
			sanitize_string( $value, $this->allowHtml ) :
			sanitize_text_field( $value );
	}

	public function save( int $post_id, mixed $value ): void {
		if ( $this->multiple ) {
			$this->delete( $post_id );
			foreach ( $value as $v ) {
				$v = trim( $this->sanitize( $v ) );
				if ( $v ) {
					add_post_meta( $post_id, $this->name, $v, false );
				}
			}
		} else {
			update_post_meta( $post_id, $this->name, $this->sanitize( $value ) );
		}
	}

	public function delete( int $post_id ): void {
		delete_post_meta( $post_id, $this->name );
	}

	public function render(): string {
		return Container::get( 'Blade' )->render( "metaboxes.fields.{$this->view}", [ 'field' => $this ] );
	}
}
