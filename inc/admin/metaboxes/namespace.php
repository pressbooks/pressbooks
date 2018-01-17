<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Contributors;
use Pressbooks\Licensing;
use PressbooksMix\Assets;

/**
 * If the user updates the book's title, then also update the blog name
 *
 * @param string|int $meta_id
 * @param int $post_id
 * @param string $meta_key
 * @param string $meta_value
 */
function title_update( $meta_id, $post_id, $meta_key, $meta_value ) {
	if ( 'pb_title' !== $meta_key ) {
		return;
	} else {
		update_option( 'blogname', $meta_value );
	}
}

/**
 * If the user leaves certain meta info blank, forcefully fill it with our own
 *
 * @param int $pid
 * @param \WP_Post $post
 */
function add_required_data( $pid, $post ) {
	$pb_authors = get_post_meta( $pid, 'pb_authors', true );
	if ( ! $pb_authors ) {
		// if pb_authors is missing, set it to the primary book user
		if ( 0 !== get_current_user_id() ) {
			$user_info = get_userdata( get_current_user_id() );
			$contributors = new Contributors();
			$term = $contributors->addBlogUser( $user_info->ID );
			if ( $term !== false ) {
				$contributors->link( $term['term_id'], $pid, 'pb_authors' );
			}
		}
	}
}


/**
 * Process uploaded cover image
 *
 * @param $pid
 * @param $post
 */
function upload_cover_image( $pid, $post ) {

	if ( ! isset( $_FILES['pb_cover_image']['name'] ) || empty( $_FILES['pb_cover_image']['name'] ) ) {
		return; // Bail
	}

	if ( ! current_user_can_for_blog( get_current_blog_id(), 'upload_files' ) ) {
		return; // Bail
	}

	$allowed_file_types = [
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
	];
	$overrides = [
		'test_form' => false,
		'mimes' => $allowed_file_types,
	];
	$image = wp_handle_upload( $_FILES['pb_cover_image'], $overrides );

	if ( ! empty( $image['error'] ) ) {
		wp_die( $image['error'] );
	}

	list( $width, $height ) = getimagesize( $image['file'] );
	if ( $width < 625 || $height < 625 ) {
		$_SESSION['pb_notices'][] = sprintf( __( 'Your cover image (%1$s x %1$s) is too small. It should be 625px on the shortest side.', 'pressbooks' ), $width, $height );
	}

	$filesize = filesize( $image['file'] );
	if ( $filesize > 2000000 ) {
		$filesize_in_mb = \Pressbooks\Utility\format_bytes( $filesize );
		$_SESSION['pb_notices'][] = sprintf( __( 'Your cover image (%s) is too big. It should be no more than 2MB.', 'pressbooks' ), $filesize_in_mb );
	}

	$old = get_post_meta( $pid, 'pb_cover_image', false );
	update_post_meta( $pid, 'pb_cover_image', $image['url'] );

	// Delete old images
	foreach ( $old as $old_url ) {
		$old_id = \Pressbooks\Image\attachment_id_from_url( $old_url );
		if ( $old_id ) {
			wp_delete_attachment( $old_id, true );
		}
	}

	// Insert new image, create thumbnails
	$args = [
		'post_mime_type' => $image['type'],
		'post_title' => __( 'Cover Image', 'pressbooks' ),
		'post_content' => '',
		'post_status' => 'inherit',
		'post_name' => 'pb-cover-image',
	];
	$id = wp_insert_attachment( $args, $image['file'], $pid );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $image['file'] ) );
}


/**
 * Force a stylesheet onto our Book Information edit page
 *
 * @param string $hook
 */
function add_metadata_styles( $hook ) {

	if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
		$post_type = get_post_type();
		if ( 'metadata' === $post_type ) {
			$assets = new Assets( 'pressbooks', 'plugin' );
			wp_enqueue_style( 'metadata', $assets->getPath( 'styles/metadata.css' ) );
		} elseif ( 'part' === $post_type ) {
			add_filter(
				'page_attributes_dropdown_pages_args', function () {
					return [
						'post_type' => '__GARBAGE__',
					];
				}
			); // Hide this dropdown by querying for garbage
		}
	}
}


/**
 * Register all metadata groups and fields
 */
function add_meta_boxes() {
	$show_expanded_metadata = \Pressbooks\Metadata\show_expanded_metadata();

	// Override WordPress' parent_id

	add_meta_box( 'chapter-parent', __( 'Part', 'pressbooks' ), __NAMESPACE__ . '\override_parent_id', 'chapter', 'side', 'high' );

	// Save Buttons

	add_meta_box( 'part-save', __( 'Save Part', 'pressbooks' ), __NAMESPACE__ . '\part_save_box', 'part', 'side', 'high' );
	add_meta_box( 'metadata-save', __( 'Save Book Information', 'pressbooks' ), __NAMESPACE__ . '\metadata_save_box', 'metadata', 'side', 'high' );

	// Custom Image Upload

	add_meta_box( 'covers', __( 'Cover Image', 'pressbooks' ), '\Pressbooks\Image\cover_image_box', 'metadata', 'normal', 'low' );

	// Book Metadata

	x_add_metadata_group(
		'general-book-information', 'metadata', [
			'label' => __( 'General Book Information', 'pressbooks' ),
			'priority' => 'high',
		]
	);

	x_add_metadata_field(
		'pb_title', 'metadata', [
			'group' => 'general-book-information',
			'label' => 'Title',
		]
	);

	x_add_metadata_field(
		'pb_short_title', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Short Title', 'pressbooks' ),
			'description' => __( 'In case of long titles that might be truncated in running heads in the PDF export.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_subtitle', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Subtitle', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_authors', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Author(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_editors', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Editor(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_translators', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Translator(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_proofreaders', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Proofreader(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_reviewers', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Reviewer(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_illustrators', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Illustrator(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_contributors', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Contributor(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_publisher', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Publisher', 'pressbooks' ),
			'description' => __( 'This text appears on the title page of your book.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_publisher_city', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Publisher City', 'pressbooks' ),
			'description' => __( 'This text appears on the title page of your book.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_publication_date', 'metadata', [
			'field_type' => 'datepicker',
			'group' => 'general-book-information',
			'label' => __( 'Publication Date', 'pressbooks' ),
			'description' => __( 'This is added to the metadata in your ebook.', 'pressbooks' ),
		]
	);

	if ( $show_expanded_metadata ) {
		x_add_metadata_field(
			'pb_onsale_date', 'metadata', [
				'field_type' => 'datepicker',
				'group' => 'general-book-information',
				'label' => __( 'On-Sale Date', 'pressbooks' ),
				'description' => __( 'This is added to the metadata in your ebook.', 'pressbooks' ),
			]
		);
	}

	x_add_metadata_field(
		'pb_ebook_isbn', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Ebook ISBN', 'pressbooks' ),
			'description' => __( 'ISBN is the International Standard Book Number, and you\'ll need one if you want to sell your book in some online ebook stores. This is added to the metadata in your ebook.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_print_isbn', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Print ISBN', 'pressbooks' ),
			'description' => __( 'ISBN is the International Standard Book Number, and you\'ll need one if you want to sell your book in online and physical book stores.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_language', 'metadata', [
			'group' => 'general-book-information',
			'field_type' => 'select',
			'values' => \Pressbooks\L10n\supported_languages(),
			'label' => __( 'Language', 'pressbooks' ),
			'description' => __( 'This sets metadata in your ebook, making it easier to find in some stores. It also changes some system generated content for supported languages, such as the "Contents" header.', 'pressbooks' ) . '<br />' . sprintf( '<a href="https://www.transifex.com/pressbooks/pressbooks/">%s</a>', __( 'Help translate Pressbooks into your language!', 'pressbooks' ) ),
			'select2' => true,
		]
	);

	x_add_metadata_group(
		'copyright', 'metadata', [
			'label' => __( 'Copyright', 'pressbooks' ),
			'priority' => 'low',
		]
	);

	if ( $show_expanded_metadata ) {
		x_add_metadata_field(
			'pb_copyright_year', 'metadata', [
				'group' => 'copyright',
				'label' => __( 'Copyright Year', 'pressbooks' ),
				'description' => __( 'Year that the book is/was published.', 'pressbooks' ),
			]
		);
	}

	x_add_metadata_field(
		'pb_copyright_holder', 'metadata', [
			'group' => 'copyright',
			'label' => __( 'Copyright Holder', 'pressbooks' ),
			'description' => __( 'Name of the copyright holder.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_book_license', 'metadata', [
			'group' => 'copyright',
			'field_type' => 'taxonomy_select',
			'taxonomy' => Licensing::TAXONOMY,
			'label' => __( 'Copyright License', 'pressbooks' ),
			'description' => __( 'You can select various licenses including Creative Commons.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_custom_copyright', 'metadata', [
			'field_type' => 'wysiwyg',
			'group' => 'copyright',
			'label' => __( 'Copyright Notice', 'pressbooks' ),
			'description' => __( 'Enter a custom copyright notice, with whatever information you like. This will override the auto-generated copyright notice if All Rights Reserved or no license is selected, and will be inserted after the title page. If you select a Creative Commons license, the custom notice will appear after the license text in both the webbook and your exports.', 'pressbooks' ),
		]
	);

	x_add_metadata_group(
		'about-the-book', 'metadata', [
			'label' => 'About the Book',
			'priority' => 'low',
		]
	);

	x_add_metadata_field(
		'pb_about_140', 'metadata', [
			'group' => 'about-the-book',
			'label' => __( 'Book Tagline', 'pressbooks' ),
			'description' => __( 'A very short description of your book. It should fit in a Twitter post, and encapsulate your book in the briefest sentence.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_about_50', 'metadata', [
			'field_type' => 'textarea',
			'group' => 'about-the-book',
			'label' => __( 'Short Description', 'pressbooks' ),
			'description' => __( 'A short paragraph about your book, for catalogs, reviewers etc. to quote.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_about_unlimited', 'metadata', [
			'field_type' => 'wysiwyg',
			'group' => 'about-the-book',
			'label' => __( 'Long Description', 'pressbooks' ),
			'description' => __( 'The full description of your book.', 'pressbooks' ),
		]
	);

	add_meta_box( 'subject', __( 'Subject(s)', 'pressbooks' ), __NAMESPACE__ . '\metadata_subject_box', 'metadata', 'normal', 'low' );

	if ( $show_expanded_metadata ) {
		x_add_metadata_group(
			'additional-catalog-information', 'metadata', [
				'label' => __( 'Additional Catalog Information', 'pressbooks' ),
				'priority' => 'low',
			]
		);

		x_add_metadata_field(
			'pb_series_title', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'Series Title', 'pressbooks' ),
				'description' => __( 'Add if your book is part of a series.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_series_number', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'Series Number', 'pressbooks' ),
				'description' => __( 'Add if your book is part of a series.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_keywords_tags', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'Keywords', 'pressbooks' ),
				'multiple' => true,
				'description' => __( 'These are added to your webbook cover page, and in your ebook metadata. Keywords are used by online book stores and search engines.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_hashtag', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'Hashtag', 'pressbooks' ),
				'description' => __( 'These are added to your webbook cover page. For those of you who like Twitter.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_list_price_print', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'List Price (Print)', 'pressbooks' ),
				'description' => __( 'The list price of your book in print.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_list_price_pdf', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'List Price (PDF)', 'pressbooks' ),
				'description' => __( 'The list price of your book in PDF format.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_list_price_epub', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'List Price (ebook)', 'pressbooks' ),
				'description' => __( 'The list price of your book in Ebook formats.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_list_price_web', 'metadata', [
				'group' => 'additional-catalog-information',
				'label' => __( 'List Price (Web)', 'pressbooks' ),
				'description' => __( 'The list price of your webbook.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_audience', 'metadata', [
				'group' => 'additional-catalog-information',
				'field_type' => 'select',
				'values' => [
					'' => __( 'Choose an audience&hellip;', 'pressbooks' ),
					'juvenile' => __( 'Juvenile', 'pressbooks' ),
					'young-adult' => __( 'Young Adult', 'pressbooks' ),
					'adult' => __( 'Adult', 'pressbooks' ),
				],
				'label' => __( 'Audience', 'pressbooks' ),
				'description' => __( 'The target audience for your book.', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			/**
			 * Filter metadata field arguments for BISAC Subject(s).
			 *
			 * @since 4.0.0
			 */
			'pb_bisac_subject', 'metadata', apply_filters(
				'pb_bisac_subject_field_args', [
					'group' => 'additional-catalog-information',
					'label' => __( 'BISAC Subject(s)', 'pressbooks' ),
					'multiple' => true,
					'description' => __( 'BISAC Subject Headings help libraries and (e)book stores properly classify your book.', 'pressbooks' ),
				]
			)
		);

		x_add_metadata_field(
			/**
			 * Filter metadata field arguments for BISAC Regional Theme.
			 *
			 * @since 4.0.0
			 */
			'pb_bisac_regional_theme', 'metadata', apply_filters(
				'pb_bisac_regional_theme_field_args', [
					'group' => 'additional-catalog-information',
					'label' => __( 'BISAC Regional Theme', 'pressbooks' ),
					'description' => __( 'BISAC Regional Themes help libraries and (e)book stores properly classify your book.', 'pressbooks' ),
				]
			)
		);
	}

	// Chapter Metadata

	x_add_metadata_group(
		'chapter-metadata', 'chapter', [
			'label' => __( 'Chapter Metadata', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_short_title', 'chapter', [
			'group' => 'chapter-metadata',
			'label' => __( 'Chapter Short Title (appears in the PDF running header)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_subtitle', 'chapter', [
			'group' => 'chapter-metadata',
			'label' => __( 'Chapter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_authors', 'chapter', [
			'group' => 'chapter-metadata',
			'label' => __( 'Author(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_section_license', 'chapter', [
			'group' => 'chapter-metadata',
			'field_type' => 'taxonomy_select',
			'taxonomy' => Licensing::TAXONOMY,
			'label' => __( 'Chapter Copyright License (overrides book license on this page)', 'pressbooks' ),
		]
	);

	// Chapter Parent

	x_add_metadata_group(
		'chapter-parent', 'chapter', [
			'label' => __( 'Part', 'pressbooks' ),
			'context' => 'side',
			'priority' => 'high',
		]
	);

	// Export

	x_add_metadata_group(
		'export', [ 'chapter', 'front-matter', 'back-matter' ], [
			'label' => __( 'Export Settings', 'pressbooks' ),
			'context' => 'side',
			'priority' => 'high',
		]
	);

	x_add_metadata_field(
		'pb_export', [ 'chapter', 'front-matter', 'back-matter' ], [
			'group' => 'export',
			'field_type' => 'checkbox',
			'label' => __( 'Include in exports', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_show_title', [ 'chapter', 'front-matter', 'back-matter' ], [
			'group' => 'export',
			'field_type' => 'checkbox',
			'label' => __( 'Show title in exports', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_ebook_start', [ 'chapter', 'front-matter', 'back-matter' ], [
			'group' => 'export',
			'field_type' => 'checkbox',
			'label' => __( 'Set as ebook start-point', 'pressbooks' ),
		]
	);

	// Front Matter Metadata

	x_add_metadata_group(
		'front-matter-metadata', 'front-matter', [
			'label' => __( 'Front Matter Metadata', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_short_title', 'front-matter', [
			'group' => 'front-matter-metadata',
			'label' => __( 'Front Matter Short Title (appears in the PDF running header)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_subtitle', 'front-matter', [
			'group' => 'front-matter-metadata',
			'label' => __( 'Front Matter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_authors', 'front-matter', [
			'group' => 'front-matter-metadata-metadata',
			'label' => __( 'Author(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_section_license', 'front-matter', [
			'group' => 'front-matter-metadata',
			'field_type' => 'taxonomy_select',
			'taxonomy' => Licensing::TAXONOMY,
			'label' => __( 'Front Matter Copyright License (overrides book license on this page)', 'pressbooks' ),
		]
	);

	// Back Matter Metadata

	x_add_metadata_group(
		'back-matter-metadata', 'back-matter', [
			'label' => __( 'Back Matter Metadata', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_short_title', 'back-matter', [
			'group' => 'back-matter-metadata',
			'label' => __( 'Back Matter Short Title (appears in the PDF running header)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_subtitle', 'back-matter', [
			'group' => 'back-matter-metadata',
			'label' => __( 'Back Matter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_authors', 'back-matter', [
			'group' => 'back-matter-metadata-metadata',
			'label' => __( 'Author(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
		]
	);

	x_add_metadata_field(
		'pb_section_license', 'back-matter', [
			'group' => 'back-matter-metadata',
			'field_type' => 'taxonomy_select',
			'taxonomy' => Licensing::TAXONOMY,
			'label' => __( 'Back Matter Copyright License (overrides book license on this page)', 'pressbooks' ),
		]
	);

	// Part Metadata

	add_action(
		'edit_form_after_editor', function ( $post ) {
			if ( 'part' === $post->post_type ) {
				$tip = __( 'Appears on part page. Parts will not appear if a book has only one part.', 'pressbooks' );
				echo '<p><span class="description">' . $tip . '</span></p>';
			}
		}
	);

	x_add_metadata_group(
		'part-metadata-visibility', 'part', [
			'label' => __( 'Part Visibility', 'pressbooks' ),
			'context' => 'side',
			'priority' => 'low',
		]
	);

	x_add_metadata_field(
		'pb_part_invisible', 'part', [
			'field_type' => 'checkbox',
			'group' => 'part-metadata-visibility',
			'label' => 'Invisible',
			'description' => __( 'Hide from table of contents and part numbering.', 'pressbooks' ),
		]
	);
}


/**
 * Render "Part" meta box
 *
 * @param |WP_Post $post
 */
function override_parent_id( $post ) {

	if ( 'chapter' !== $post->post_type ) {
		return; // Do nothing
	}

	if ( $post->post_parent ) {
		$selected = $post->post_parent;
	} elseif ( isset( $_GET['startparent'] ) ) {
		$selected = absint( $_GET['startparent'] );
	} else {
		$selected = 0;
	}

	$pages = wp_dropdown_pages(
		[
			'post_type' => 'part',
			'selected' => $selected,
			'name' => 'parent_id',
			'sort_column' => 'menu_order',
			'echo' => 0,
		]
	);

	if ( ! empty( $pages ) ) {
		echo $pages;
	}
}

/**
 * WP_Ajax hook for pb_delete_cover_image
 */
function delete_cover_image() {

	if ( current_user_can_for_blog( get_current_blog_id(), 'upload_files' ) && check_ajax_referer( 'pb-delete-cover-image' ) ) {

		$image_url = $_POST['filename'];
		$pid = $_POST['pid'];

		// Delete old images
		$old_id = \Pressbooks\Image\attachment_id_from_url( $image_url );
		if ( $old_id ) {
			wp_delete_attachment( $old_id, true );
		}

		update_post_meta( $pid, 'pb_cover_image', \Pressbooks\Image\default_cover_url() );
		\Pressbooks\Book::deleteBookObjectCache();
	}

	// @see http://codex.wordpress.org/AJAX_in_Plugins#Error_Return_Values
	// Will append 0 to returned json string if we don't die()
	die();
}


/**
 * Override save dialogue for Parts
 *
 * @param \WP_Post $post
 */
function part_save_box( $post ) {
	if ( 'publish' === $post->post_status ) { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Update"/>
		<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="Save"/>
	<?php } else { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Publish"/>
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" value="Save" tabindex="5" accesskey="p"/>
	<?php
}
}


/**
 *  Override save dialogue for Book Information
 *
 * @param \WP_Post $post
 */
function metadata_save_box( $post ) {
	if ( 'publish' === $post->post_status ) {
	?>
		<input name="original_publish" type="hidden" id="original_publish" value="Update"/>
		<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="Save"/>
	<?php } else { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Publish"/>
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" value="Save" tabindex="5" accesskey="p"/>
	<?php
}
}

/**
 * Display subjects meta box
 *
 * @since 4.4.0
 *
 * @param \WP_Post $post
 */
function metadata_subject_box( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'subject_meta_nonce' );
	$pb_primary_subject = get_post_meta( $post->ID, 'pb_primary_subject', true );
	$pb_additional_subjects = get_post_meta( $post->ID, 'pb_additional_subjects' );
	if ( ! $pb_additional_subjects ) {
		$pb_additional_subjects = [];
	}
	?>
	<div class="custom-metadata-field select">
		<label for="pb_primary_subject"><?php _e( 'Primary Subject', 'pressbooks' ); ?></label>
		<select id="primary-subject" name="pb_primary_subject">
			<option value=""></option>
			<?php foreach ( \Pressbooks\Metadata\get_thema_subjects() as $subject_group ) { ?>
			<optgroup label="<?php echo $subject_group['label']; ?>">
				<?php foreach ( $subject_group['children'] as $key => $value ) { ?>
				<option value="<?php echo $key; ?>" <?php selected( $pb_primary_subject, $key ); ?>><?php echo $value; ?></option>
				<?php } ?>
			</optgroup>
			<?php } ?>
		</select>
		<span class="description"><?php printf( __( 'This appears on the web homepage of your book and helps categorize it in your network catalog (if applicable). Use %s to determine which subject category is best for your book.', 'pressbooks' ), sprintf( '<a href="%1$s">%2$s</a>', 'http://www.editeur.org/files/Thema/20160601%20Thema%20v1.2%20Basic%20instructions.pdf', __( 'these instructions', 'pressbooks' ) ) ); ?></span>
	</div>
	<div class="custom-metadata-field select">
		<label for="pb_additional_subjects"><?php _e( 'Additional Subject(s)', 'pressbooks' ); ?></label>
		<select id="additional-subjects" name="pb_additional_subjects[]" multiple>
			<option value=""></option>
			<?php foreach ( \Pressbooks\Metadata\get_thema_subjects( true ) as $subject_group ) { ?>
			<optgroup label="<?php echo $subject_group['label']; ?>">
				<?php foreach ( $subject_group['children'] as $key => $value ) { ?>
				<option value="<?php echo $key; ?>" <?php selected( in_array( $key, $pb_additional_subjects, true ), true ); ?>><?php echo $value; ?></option>
				<?php } ?>
			</optgroup>
			<?php } ?>
		</select>
		<span class="description"><?php printf( __( 'This appears on the web homepage of your book. Use %s to determine which additional subject categories are appropriate for your book.', 'pressbooks' ), sprintf( '<a href="%1$s">%2$s</a>', 'http://www.editeur.org/files/Thema/20160601%20Thema%20v1.2%20Basic%20instructions.pdf', __( 'these instructions', 'pressbooks' ) ) ); ?></span>
	</div>
<?php
}

/**
 * Save subject metadata
 *
 * @since 4.4.0
 *
 * @param int $post_id The post ID.
 */
function save_subject_metadata( $post_id ) {
	if ( ! isset( $_POST['subject_meta_nonce'] ) || ! wp_verify_nonce( $_POST['subject_meta_nonce'], basename( __FILE__ ) ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_REQUEST['pb_primary_subject'] ) && ! empty( $_REQUEST['pb_primary_subject'] ) ) {
		update_post_meta( $post_id, 'pb_primary_subject', sanitize_text_field( $_POST['pb_primary_subject'] ) );
	} else {
		delete_post_meta( $post_id, 'pb_primary_subject' );
	}

	if ( isset( $_REQUEST['pb_additional_subjects'] ) && ! empty( $_REQUEST['pb_additional_subjects'] ) ) {
		$value = ( is_array( $_POST['pb_additional_subjects'] ) ) ? $_POST['pb_additional_subjects'] : [ $_POST['pb_additional_subjects'] ];
		delete_post_meta( $post_id, 'pb_additional_subjects' );
		foreach ( $value as $v ) {
			add_post_meta( $post_id, 'pb_additional_subjects', sanitize_text_field( $v ) );
		}
	} else {
		delete_post_meta( $post_id, 'pb_additional_subjects' );
	}
}

/**
 * @since 5.0.0
 */
function contributor_add_form() {
	wp_nonce_field( 'contributor-meta', 'contributor_meta_nonce' );
?>
	<div class="form-field contributor-first-name-wrap">
		<label for="contributor_first_name"><?php _e( 'First Name', 'pressbooks' ); ?></label>
		<input type="text" name="contributor_first_name" id="contributor-first-name" value="" class="contributor-first-name-field" />
		<p>
	</div>
	<div class="form-field contributor-last-name-wrap">
		<label for="contributor_last_name"><?php _e( 'Last Name', 'pressbooks' ); ?></label>
		<input type="text" name="contributor_last_name" id="contributor-last-name" value="" class="contributor-last-name-field" />
	</div>
	<?php
}

function contributor_edit_form( $term ) {
	$firstname = get_term_meta( $term->term_id, 'contributor_first_name', true );
	$lastname = get_term_meta( $term->term_id, 'contributor_last_name', true );
	if ( ! $firstname ) {
		$firstname = '';
	}
	if ( ! $lastname ) {
		$lastname = '';
	}
?>
	<tr class="form-field contributor-first-name-wrap">
		<th scope="row"><label for="contributor_first_name"><?php _e( 'First Name', 'pressbooks' ); ?></label></th>
		<td>
			<?php wp_nonce_field( 'contributor-meta', 'contributor_meta_nonce' ); ?>
			<input type="text" name="contributor_first_name" id="contributor-first-name" value="<?php echo esc_attr( $firstname ); ?>" class="contributor-first-name-field"  />
		</td>
	</tr>
	<tr class="form-field contributor-last-name-wrap">
		<th scope="row"><label for="contributor_last_name"><?php _e( 'First Name', 'pressbooks' ); ?></label></th>
		<td>
			<input type="text" name="contributor_last_name" id="contributor-last-name" value="<?php echo esc_attr( $lastname ); ?>" class="contributor-last-name-field"  />
		</td>
	</tr>
<?php
}

/**
 * @since 5.0.0
 */
function save_contributor_meta( $term_id, $tt_id, $taxonomy ) {
	if ( $taxonomy !== 'contributor' ) {
		return;
	}
	if ( ! isset( $_POST['contributor_meta_nonce'] ) || ! wp_verify_nonce( $_POST['contributor_meta_nonce'], 'contributor-meta' ) ) {
		return;
	}
	$old_first_name  = get_term_meta( $term_id, 'contributor_first_name', true );
	$old_last_name  = get_term_meta( $term_id, 'contributor_last_name', true );
	$new_first_name = isset( $_POST['contributor_first_name'] ) ? sanitize_text_field( $_POST['contributor_first_name'] ) : '';
	$new_last_name = isset( $_POST['contributor_last_name'] ) ? sanitize_text_field( $_POST['contributor_last_name'] ) : '';
	if ( $new_first_name === '' ) {
		delete_term_meta( $term_id, 'contributor_first_name' );
	} elseif ( $old_first_name !== $new_first_name ) {
		update_term_meta( $term_id, 'contributor_first_name', $new_first_name );
	}
	if ( $new_last_name === '' ) {
		delete_term_meta( $term_id, 'contributor_last_name' );
	} elseif ( $old_last_name !== $new_last_name ) {
		update_term_meta( $term_id, 'contributor_last_name', $new_last_name );
	}
}
