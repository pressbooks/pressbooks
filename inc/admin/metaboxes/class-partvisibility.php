<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\Checkbox;

class PartVisibility extends Metabox {

	public function __construct() {
		parent::__construct();

		$this->context = 'side';
		$this->priority = 'low';
	}

	public function getSlug(): string {
		return 'part-visibility';
	}

	public function getTitle(): string {
		return __( 'Part Visibility', 'pressbooks' );
	}

	public function getFields(): array {
		return [
			new Checkbox(
				name: 'pb_part_invisible',
				label: __( 'Invisible', 'pressbooks' ),
				description: __( 'Hide from table of contents and part numbering.', 'pressbooks' )
			),
		];
	}
}
