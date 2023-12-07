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

	public ?string $screen = null;

	public string $context = 'advanced';

	public string $priority = 'default';

	public function __construct() {
		$this->title = $this->getTitle();
		$this->slug = $this->getSlug();
		$this->fields = $this->getFields();
		$this->callback = [ $this, 'render' ];
	}

	abstract public function getTitle();

	abstract public function getSlug();

	abstract public function getFields();

	public function register() {
		add_meta_box( $this->slug, $this->title, $this->callback, null, $this->context, $this->priority );
	}

	public function render() {
		$nonce = wp_nonce_field( $this->slug, "{$this->slug}_nonce", true, false );

		echo Container::get( 'Blade' )->render('metaboxes.metabox', [
			'nonce' => $nonce,
			'fields' => array_map( function ( $field ) {
				return $field->render();
			}, $this->fields ),
		]);
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST[ "{$this->slug}_nonce" ] ) || ! wp_verify_nonce( $_POST[ "{$this->slug}_nonce" ], $this->slug ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			if ( isset( $_POST[ $field->name ] ) && ! empty( $_POST[ $field->name ] ) ) {
				$field->save( $post_id );
			} else {
				$field->delete( $post_id );
			}
		}
	}
}
