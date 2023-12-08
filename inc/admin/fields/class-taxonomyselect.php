<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class TaxonomySelect extends Field {
	public array $options = [];

	public string $taxonomy = '';

	public string $default = '';

	public string $view = 'select';

	public function __construct( string $name, string $label, ?string $description = null, ?string $id = null, bool $multiple = false, string $taxonomy = null, string $default = '' ) {
		parent::__construct( $name, $label, $description, $id, $multiple );

		$this->taxonomy = $taxonomy;
		$this->options = $this->getOptions();
		$this->default = $default;
	}

	public function getOptions(): array {
		$terms = get_terms( $this->taxonomy, [ 'hide_empty' => false ] );

		$options = [];

		if ( ! $this->multiple ) {
			$options[''] = '';
		}

		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}

	public function save( int $post_id ): void {
		if ( $this->multiple ) {
			wp_set_object_terms( $post_id, $_POST[ $this->name ], $this->taxonomy );
		} else {
			wp_set_object_terms(
				$post_id,
				[ $_POST[ $this->name ] ],
				$this->taxonomy
			);
		}

		parent::save( $post_id );
	}
}
