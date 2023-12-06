<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\TaxonomySelect;
use Pressbooks\Admin\Fields\Text;
use Pressbooks\Admin\Fields\Url;
use Pressbooks\Admin\Fields\Wysiwyg;
use Pressbooks\Licensing;
use Pressbooks\Metadata;

class Copyright extends Metabox {

	public bool $expanded = false;

	public function __construct( bool $expanded = false ) {
		parent::__construct( $expanded );

		$this->expanded = $expanded;
	}

	public function getSlug(): string {
		return 'copyright';
	}

	public function getTitle(): string {
		return __( 'Copyright', 'pressbooks' );
	}

	public function getFields(): array {
		$metadata = ( new Metadata() )->getMetaPostMetadata();
		$pb_is_based_on = $metadata['pb_is_based_on'] ?? false;

		return array_filter( [
			$pb_is_based_on ? new Url(
				name: 'pb_is_based_on',
				label: __( 'Source Book URL', 'pressbooks' ),
				description: __( 'This book was cloned from a pre-existing book at the above URL. This information will be displayed on the webbook homepage.', 'pressbooks' ),
				readonly: true
			) : null,
			$this->expanded ?
			new Text(
				name: 'pb_copyright_year',
				label: __( 'Copyright Year', 'pressbooks' ),
				description: __( 'Year that the book is/was published.', 'pressbooks' )
			)
			: null,
			new Text(
				name: 'pb_copyright_holder',
				label: __( 'Copyright Holder', 'pressbooks' ),
				description: __( 'Name of the copyright holder.', 'pressbooks' ),
			),
			new TaxonomySelect(
				name: 'pb_book_license',
				label: __( 'Copyright License', 'pressbooks' ),
				description: __( 'You can select various licenses including Creative Commons.', 'pressbooks' ),
				taxonomy: Licensing::TAXONOMY,
			),
			new Wysiwyg(
				name: 'pb_custom_copyright',
				label: __( 'Copyright Notice', 'pressbooks' ),
				description: __( 'Enter a custom copyright notice, with whatever information you like. This will override the auto-generated copyright notice if All Rights Reserved or no license is selected, and will be inserted after the title page. If you select a Creative Commons license, the custom notice will appear after the license text in both the webbook and your exports.', 'pressbooks' ),
				rows: 4
			),
		] );
	}
}
