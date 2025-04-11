<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\TaxonomyReorderableMultiselect;
use Pressbooks\Admin\Fields\TaxonomySelect;
use Pressbooks\Admin\Fields\Text;
use Pressbooks\Contributors;
use Pressbooks\Licensing;

class SectionMetadata extends Metabox {
	public string $sectionType = '';

	public function __construct( string $section_type = '' ) {
		$this->sectionType = $section_type;
		$this->priority = 'high';

		parent::__construct();
	}

	public function getSlug(): string {
		return 'section-metadata';
	}

	public function getTitle(): string {
		return sprintf( __( '%s Metadata', 'pressbooks' ), $this->sectionType );
	}

	public function getFields(): array {
		return [
			new Text(
				name: 'pb_short_title',
				label: sprintf( __( '%s Short Title', 'pressbooks' ), $this->sectionType ),
				description: __( 'Appears in the PDF running header and webbook navigation.', 'pressbooks' )
			),

			new Text(
				name: 'pb_subtitle',
				label: sprintf( __( '%s Subtitle', 'pressbooks' ), $this->sectionType ),
				description: __( 'Appears in the Web/ebook/PDF output.', 'pressbooks' )
			),

			new TaxonomyReorderableMultiselect(
				name: 'pb_authors',
				label: sprintf( __( '%s Author(s)', 'pressbooks' ), $this->sectionType ),
				taxonomy: Contributors::TAXONOMY,
				description: sprintf( '<a class="button" href="%s">%s</a>', 'edit-tags.php?taxonomy=contributor', __( 'Create New Contributor', 'pressbooks' ) )
			),

			new TaxonomySelect(
				name: 'pb_section_license',
				label: sprintf( __( '%s Copyright License', 'pressbooks' ), $this->sectionType ),
				description: __( 'Overrides book license on this page.', 'pressbooks' ),
				taxonomy: Licensing::TAXONOMY,
			),

			new Text(
				name: 'pb_section_doi',
				label: sprintf( __( '%s Digital Object Identifier (DOI)', 'pressbooks' ), $this->sectionType ),
			),
		];
	}
}
