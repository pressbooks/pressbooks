<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\Select;
use Pressbooks\Admin\Fields\Text;

class AdditionalCatalogInformation extends Metabox {

	public function getSlug(): string {
		return 'additional-catalog-information';
	}

	public function getTitle(): string {
		return __( 'Additional Catalog Information', 'pressbooks' );
	}

	public function getFields(): array {
		return [
			new Text(
				name: 'pb_series_title',
				label: __( 'Series Title', 'pressbooks' ),
				description: __( 'Add if your book is part of a series.', 'pressbooks' ),
			),
			new Text(
				name: 'pb_series_number',
				label: __( 'Series Number', 'pressbooks' ),
				description: __( 'Add if your book is part of a series.', 'pressbooks' ),
			),
			new Text(
				name: 'pb_keyworks_tags',
				label:  __( 'Keywords', 'pressbooks' ),
				description: __( 'These are added to your webbook cover page, and in your ebook metadata. Keywords are used by online book stores and search engines.', 'pressbooks' ),
				multiple: true
			),
			new Text(
				name: 'pb_hashtag',
				label: __( 'Hashtag', 'pressbooks' ),
				description: __( 'These are added to your webbook cover page. For those of you who like Twitter.', 'pressbooks' ),
			),
			new Text(
				name: 'pb_list_price_print',
				label: __( 'List Price (Print)', 'pressbooks' ),
				description: __( 'The list price of your book in print.', 'pressbooks' ),
			),
			new Text(
				name: 'pb_list_price_pdf',
				label: __( 'List Price (PDF)', 'pressbooks' ),
				description: __( 'The list price of your book in PDF format.', 'pressbooks' ),
			),
			new Text(
				name: 'pb_list_price_epub',
				label: __( 'List Price (ebook)', 'pressbooks' ),
				description: __( 'The list price of your book in Ebook formats.', 'pressbooks' ),
			),
			new Text(
				name: 'pb_list_price_web',
				label: __( 'List Price (Web)', 'pressbooks' ),
				description: __( 'The list price of your webbook.', 'pressbooks' ),
			),
			new Select(
				name: 'pb_audience',
				label: __( 'Audience', 'pressbooks' ),
				description:  __( 'The target audience for your book.', 'pressbooks' ),
				options: [
					'' => __( 'Choose an audience&hellip;', 'pressbooks' ),
					'juvenile' => __( 'Juvenile', 'pressbooks' ),
					'young-adult' => __( 'Young Adult', 'pressbooks' ),
					'adult' => __( 'Adult', 'pressbooks' ),
				]
			),
			apply_filters( 'bisac_subject_field', new Text(
				name: 'pb_bisac_subject',
				label: __( 'BISAC Subject(s)', 'pressbooks' ),
				description: sprintf( __( 'BISAC Subject Headings help libraries and bookstores properly classify your book. To select the appropriate subject heading for your book, consult %s.', 'pressbooks' ), sprintf( '<a href="https://bisg.org/page/BISACEdition">%s</a>', __( 'the BISAC Subject Headings list', 'pressbooks' ) ) ),
				multiple: true
			) ),
			apply_filters( 'bisac_regional_theme_field', new Text(
				name: 'pb_bisac_regional_theme',
				label: __( 'BISAC Regional Theme', 'pressbooks' ),
				description:  __( 'BISAC Regional Themes help libraries and bookstores properly classify your book.', 'pressbooks' ),
			) ),
		];
	}
}
