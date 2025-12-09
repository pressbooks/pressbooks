<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\RelatedLink;

class RelatedMaterials extends Metabox {

	public function getSlug(): string {
		return 'supplemental-materials';
	}

	public function getTitle(): string {
		return __( 'Supplemental Materials', 'pressbooks' );
	}

	public function getFields(): array {
		return [
			new RelatedLink(
				name: 'pb_related_material',
				label: __( 'Supplemental Materials', 'pressbooks' ),
				description: __( 'Links to these supplemental materials will be added to your webbook\'s home page.', 'pressbooks' )
			),
		];
	}
}

