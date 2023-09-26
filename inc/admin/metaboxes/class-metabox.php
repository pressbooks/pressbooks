<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;
use Pressbooks\Container;

abstract class Metabox {
	public string $slug = '';

	public string $title = '';

	public array $fields = [];

	public string|array $callback = [];

	public bool $expanded = false;

	public function __construct( bool $expanded = false )
	{
		$this->fields = $this->getFields();
		$this->callback = [$this, 'render'];
		$this->expanded = $expanded;
	}

	abstract public function getFields();

	public function register()
	{
		add_meta_box( $this->slug, $this->title, $this->callback );
	}

	public function render()
	{
		wp_nonce_field( $this->slug, "{$this->slug}_nonce" );

		echo Container::get( 'Blade' )->render("metaboxes.metabox", ['fields' => $this->fields]);
	}

	public function save( $post_id )
	{
		if (! isset($_POST["{$this->slug}_nonce"]) || ! wp_verify_nonce($_POST["{$this->slug}_nonce"], $this->slug)) {
			return;
		}

		if (! current_user_can('edit_post', $post_id)) {
			return;
		}

		foreach($this->fields as $field) {
			if (isset($_POST[$field->name]) && ! empty($_POST[$field->name])) {
				$field->save( $post_id );
			} else {
				$field->delete( $post_id );
			}
		}
	}
}
