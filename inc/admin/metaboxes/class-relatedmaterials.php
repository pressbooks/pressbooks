<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\RelatedLink;

class RelatedMaterials extends Metabox {

	public function getSlug(): string {
		return 'related-materials';
	}

	public function getTitle(): string {
		return __( 'Related Material', 'pressbooks' );
	}

	public function getFields(): array {
		return [
			new RelatedLink(
				name: 'pb_related_material',
				label: __( 'Ancillaries and related materials', 'pressbooks' ),
				description: __( 'Links to these ancillaries and related materials will be added to your webbook\'s home page. The URL field is required and must contain a valid URL. The description field is optional and can include a textual description of the related resource.', 'pressbooks' )
			),
		];
	}
}

