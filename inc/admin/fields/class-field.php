<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

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

	public function __construct( string $name, string $label, ?string $description = null, ?string $id = null, bool $multiple = false )
	{
		$this->name = $name;
		$this->label = $label;
		$this->description = $description;
		$this->id = $id ?? $this->name;
		$this->multiple = $multiple;
		$this->value = $this->getValue();
	}

	public function getValue()
	{
		global $post;

		return get_post_meta( $post->ID, $this->name, !$this->multiple );
	}

	public function sanitize( mixed $value ): mixed
	{
		return sanitize_text_field( $value );
	}

	public function save( int $post_id ): void
	{
		if ( $this->multiple ) {
			$this->delete( $post_id );
			foreach ( $_POST[$this->name] as $value ) {
				$value = trim($this->sanitize($value));
				if ( $value ) {
					add_post_meta( $post_id, $this->name, $value, false );
				}
			}
		} else {
			update_post_meta( $post_id, $this->name, $this->sanitize( $_POST[$this->name] ) );
		}
	}

	public function delete( int $post_id ): void
	{
		delete_post_meta( $post_id, $this->name );
	}
}
