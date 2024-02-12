<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\Date as DateField;
use Pressbooks\Admin\Fields\Select;
use Pressbooks\Admin\Fields\TaxonomyReorderableMultiselect;
use Pressbooks\Admin\Fields\Text;
use Pressbooks\Contributors;

class GeneralInformation extends Metabox {

	public bool $expanded = false;

	public function __construct( bool $expanded = false ) {
		$this->expanded = $expanded;
		$this->priority = 'high';

		parent::__construct();

	}

	public function getSlug(): string {
		return 'general-information';
	}

	public function getTitle(): string {
		return __( 'General Book Information', 'pressbooks' );
	}

	public function getFields(): array {
		return array_filter( [
			new Text(
				name: 'pb_title',
				label: __( 'Title', 'pressbooks' ),
			),

			new Text(
				name: 'pb_short_title',
				label: __( 'Short Title', 'pressbooks' ),
				description: __( 'In case of long titles that might be truncated in running heads in the PDF export.', 'pressbooks' )
			),

			new Text(
				name: 'pb_subtitle',
				label: __( 'Subtitle', 'pressbooks' )
			),

			new TaxonomyReorderableMultiselect(
				name: 'pb_authors',
				label: __( 'Author(s)', 'pressbooks' ),
				taxonomy: Contributors::TAXONOMY,
				description: sprintf( '<a class="button" href="%s">%s</a>', 'edit-tags.php?taxonomy=contributor', __( 'Create New Contributor', 'pressbooks' ) )
			),

			new TaxonomyReorderableMultiselect(
				name: 'pb_editors',
				label: __( 'Editor(s)', 'pressbooks' ),
				taxonomy: Contributors::TAXONOMY,
				description: sprintf( '<a class="button" href="%s">%s</a>', 'edit-tags.php?taxonomy=contributor', __( 'Create New Contributor', 'pressbooks' ) )
			),

			new TaxonomyReorderableMultiselect(
				name: 'pb_translators',
				label: __( 'Translator(s)', 'pressbooks' ),
				taxonomy: Contributors::TAXONOMY,
				description: sprintf( '<a class="button" href="%s">%s</a>', 'edit-tags.php?taxonomy=contributor', __( 'Create New Contributor', 'pressbooks' ) )
			),

			new TaxonomyReorderableMultiselect(
				name: 'pb_reviewers',
				label: __( 'Reviewer(s)', 'pressbooks' ),
				taxonomy: Contributors::TAXONOMY,
				description: sprintf( '<a class="button" href="%s">%s</a>', 'edit-tags.php?taxonomy=contributor', __( 'Create New Contributor', 'pressbooks' ) )
			),

			new TaxonomyReorderableMultiselect(
				name: 'pb_illustrators',
				label: __( 'Illustrator(s)', 'pressbooks' ),
				taxonomy: Contributors::TAXONOMY,
				description: sprintf( '<a class="button" href="%s">%s</a>', 'edit-tags.php?taxonomy=contributor', __( 'Create New Contributor', 'pressbooks' ) )
			),

			new TaxonomyReorderableMultiselect(
				name: 'pb_contributors',
				label: __( 'Contributor(s)', 'pressbooks' ),
				taxonomy: Contributors::TAXONOMY,
				description: sprintf( '<a class="button" href="%s">%s</a>', 'edit-tags.php?taxonomy=contributor', __( 'Create New Contributor', 'pressbooks' ) )
			),

			new Text(
				name: 'pb_publisher',
				label: __( 'Publisher', 'pressbooks' ),
				description: __( 'This text appears on the title page of your book.', 'pressbooks' )
			),

			new Text(
				name: 'pb_publisher_city',
				label: __( 'Publisher City', 'pressbooks' ),
				description: __( 'This text appears on the title page of your book.', 'pressbooks' )
			),

			new DateField(
				name: 'pb_publication_date',
				label: __( 'Publication Date', 'pressbooks' ),
				description: __( 'This is added to the metadata in your ebook.', 'pressbooks' ),
			),

			$this->expanded ? new DateField(
				name: 'pb_onsale_date',
				label: __( 'On-Sale Date', 'pressbooks' ),
				description: __( 'This is added to the metadata in your ebook.', 'pressbooks' ),
			) : null,

			new Text(
				name: 'pb_ebook_isbn',
				label: __( 'Ebook ISBN', 'pressbooks' ),
				description: __( "ISBN is the International Standard Book Number, and you'll need one if you want to sell your book in some online ebook stores. This is added to the metadata in your ebook.", 'pressbooks' )
			),

			new Text(
				name: 'pb_print_isbn',
				label: __( 'Print ISBN', 'pressbooks' ),
				description: __( "ISBN is the International Standard Book Number, and you'll need one if you want to sell your book in online and physical book stores.", 'pressbooks' )
			),

			new Text(
				name: 'pb_book_doi',
				label: __( 'Digital Object Identifier (DOI)', 'pressbooks' ),
			),

			new Select(
				name: 'pb_language',
				label: __( 'Language', 'pressbooks' ),
				description: __( 'This sets metadata in your ebook, making it easier to find in some stores. It also changes some system generated content for supported languages, such as the "Contents" header.', 'pressbooks' ) . '<br />' . sprintf( '<a href="https://www.transifex.com/pressbooks/pressbooks/">%s</a>', __( 'Help translate Pressbooks into your language!', 'pressbooks' ) ),
				options: \Pressbooks\L10n\supported_languages(),
				default: 'en'
			),
		] );
	}
}
