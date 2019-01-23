<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use PressbooksMix\Assets;
use Pressbooks\Contributors;
use Pressbooks\Licensing;
use Pressbooks\Metadata;

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
 * If the user leaves certain metadata blank, forcefully fill it with our own
 *
 * @param int $pid
 * @param \WP_Post $post
 */
function add_required_data( $pid, $post ) {
	if ( $post->post_type !== 'metadata' ) {
		return; // Do nothing
	}
	$pb_authors = get_post_meta( $pid, 'pb_authors', true );
	if ( ! $pb_authors ) {
		// if pb_authors is missing, set it to the primary book user
		$user_id = get_current_user_id();
		if ( $user_id && is_user_member_of_blog( $user_id ) ) {
			$user_info = get_userdata( $user_id );
			$contributors = new Contributors();
			$term = get_term_by( 'slug', $user_info->user_nicename, Contributors::TAXONOMY, ARRAY_A );
			if ( ! $term ) {
				$term = $contributors->addBlogUser( $user_info->ID );
			}
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
	add_meta_box( 'status-visibility', __( 'Status & Visibility', 'pressbooks' ), __NAMESPACE__ . '\status_visibility_box', [ 'chapter', 'front-matter', 'back-matter', 'glossary' ], 'side', 'high' );

	// Book info: slug should be not available

	remove_meta_box( 'slugdiv', 'metadata', 'normal' );

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
			'description' => '<a class="button" href="edit-tags.php?taxonomy=contributor">' . __( 'Create New Contributor', 'pressbooks' ) . '</a>',
			'placeholder' => __( 'Choose author(s)...', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_editors', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Editor(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
			'description' => '<a class="button" href="edit-tags.php?taxonomy=contributor">' . __( 'Create New Contributor', 'pressbooks' ) . '</a>',
			'placeholder' => __( 'Choose editor(s)...', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_translators', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Translator(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
			'description' => '<a class="button" href="edit-tags.php?taxonomy=contributor">' . __( 'Create New Contributor', 'pressbooks' ) . '</a>',
			'placeholder' => __( 'Choose translator(s)...', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_reviewers', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Reviewer(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
			'description' => '<a class="button" href="edit-tags.php?taxonomy=contributor">' . __( 'Create New Contributor', 'pressbooks' ) . '</a>',
			'placeholder' => __( 'Choose reviewer(s)...', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_illustrators', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Illustrator(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
			'description' => '<a class="button" href="edit-tags.php?taxonomy=contributor">' . __( 'Create New Contributor', 'pressbooks' ) . '</a>',
			'placeholder' => __( 'Choose illustrator(s)...', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_contributors', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Contributor(s)', 'pressbooks' ),
			'field_type' => 'taxonomy_multi_select',
			'taxonomy' => Contributors::TAXONOMY,
			'select2' => true,
			'description' => '<a class="button" href="edit-tags.php?taxonomy=contributor">' . __( 'Create New Contributor', 'pressbooks' ) . '</a>',
			'placeholder' => __( 'Choose contributor(s)...', 'pressbooks' ),
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
		'pb_book_doi', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Digital Object Identifier (DOI)', 'pressbooks' ),
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

	$meta = new Metadata();
	$data = $meta->getMetaPostMetadata();
	$source_url = $data['pb_is_based_on'] ?? false;

	if ( $source_url ) {
		x_add_metadata_field(
			'pb_is_based_on', 'metadata', [
				'group' => 'copyright',
				'label' => __( 'Source Book URL', 'pressbooks' ),
				'readonly' => true,
				'description' => __( 'This book was cloned from a pre-existing book at the above URL. This information will be displayed on the webbook homepage.', 'pressbooks' ),
			]
		);

	}

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

	// Front Matter, Back Matter, and Chapter Metadata

	foreach ( [
		'front-matter' => __( 'Front Matter', 'pressbooks' ),
		'chapter' => __( 'Chapter', 'pressbooks' ),
		'back-matter' => __( 'Back Matter', 'pressbooks' ),
	] as $slug => $label ) {
		x_add_metadata_group(
			'section-metadata', $slug, [
				'label' => sprintf( __( '%s Metadata', 'pressbooks' ), $label ),
			]
		);

		x_add_metadata_field(
			'pb_short_title', $slug, [
				'group' => 'section-metadata',
				'label' => sprintf( __( '%s Short Title (appears in the PDF running header and webbook navigation)', 'pressbooks' ), $label ),
			]
		);

		x_add_metadata_field(
			'pb_subtitle', $slug, [
				'group' => 'section-metadata',
				'label' => sprintf( __( '%s Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' ), $label ),
			]
		);

		x_add_metadata_field(
			'pb_authors', $slug, [
				'group' => 'section-metadata',
				'label' => sprintf( __( '%s Author(s)', 'pressbooks' ), $label ),
				'field_type' => 'taxonomy_multi_select',
				'taxonomy' => Contributors::TAXONOMY,
				'select2' => true,
				'description' => '<a class="button" href="edit-tags.php?taxonomy=contributor">' . __( 'Create New Contributor', 'pressbooks' ) . '</a>',
				'placeholder' => __( 'Choose author(s)...', 'pressbooks' ),
			]
		);

		x_add_metadata_field(
			'pb_section_license', $slug, [
				'group' => 'section-metadata',
				'field_type' => 'taxonomy_select',
				'taxonomy' => Licensing::TAXONOMY,
				'label' => sprintf( __( '%s Copyright License (overrides book license on this page)', 'pressbooks' ), $label ),
			]
		);

		x_add_metadata_field(
			'pb_section_doi', $slug, [
				'group' => 'section-metadata',
				'label' => sprintf( __( '%s Digital Object Identifier (DOI)', 'pressbooks' ), $label ),
			]
		);
	}

	// Chapter Parent

	x_add_metadata_group(
		'chapter-parent', 'chapter', [
			'label' => __( 'Part', 'pressbooks' ),
			'context' => 'side',
			'priority' => 'high',
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

	global $wpdb;
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'part' AND post_status IN (%s, %s, %s, %s) ORDER BY menu_order ASC ",
			[ 'draft', 'web-only', 'private', 'publish' ]
		)
	);

	$output = "<select name='parent_id' id='parent_id'>\n";
	foreach ( $results as $val ) {
		$selected_html = ( (int) $selected === (int) $val->ID ) ? "selected='selected'" : '';
		$output .= '<option value="' . esc_attr( $val->ID ) . '" ' . $selected_html . ' >' . esc_attr( $val->post_title ) . "</option>\n";
	}
	$output .= "</select>\n";

	echo $output;
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
	echo '<div class="submitbox" id="submitpost">';
	if ( 'publish' === $post->post_status ) { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ); ?>"/>
		<input name="save" id="publish" type="submit" class="button button-primary button-large" accesskey="p" value="Save"/>
		<?php
	} else {
		?>
		<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ); ?>"/>
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" value="Save" tabindex="5" accesskey="p"/>
		<?php
	}
	echo '</div>';
}


/**
 *  Override save dialogue for Book Information
 *
 * @param \WP_Post $post
 */
function metadata_save_box( $post ) {
	echo '<div class="submitbox" id="submitpost">';
	if ( 'publish' === $post->post_status ) {
		?>
		<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ); ?>"/>
		<input name="save" id="publish" type="submit" class="button button-primary button-large" accesskey="p" value="Save"/>
		<?php
	} else {
		?>
		<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ); ?>"/>
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" value="Save" tabindex="5" accesskey="p"/>
		<?php
	}
	echo '</div>';
}

/**
 * Replace Publish panel for chapters, front matter, and back matter.
 *
 * @since 5.0.0
 *
 * @see post_submit_meta_box()
 *
 * @param \WP_Post $post
 */
function status_visibility_box( $post ) {
	$action = get_current_screen()->action;
	$post_type = $post->post_type;
	$post_type_object = get_post_type_object( $post_type );
	$can_publish = current_user_can( $post_type_object->cap->publish_posts );
	$revisions = wp_get_post_revisions( $post->id, [ 'fields' => 'ids' ] );
	$revs = count( $revisions );
	$latest_rev = array_shift( $revisions );

	if ( in_array( $post->post_status, [ 'web-only', 'publish' ], true ) || $action === 'add' && $can_publish ) {
		$show_in_web = 1;
		$show_in_glossary_lists = 1;
	} else {
		$show_in_web = 0;
		$show_in_glossary_lists = 0;
	}
	if ( in_array( $post->post_status, [ 'private', 'publish' ], true ) || $action === 'add' && $can_publish ) {
		$show_in_exports = 1;
	} else {
		$show_in_exports = 0;
	}

	$require_password = empty( trim( $post->post_password ) ) === false;

	$pb_show_title = ( get_post_meta( $post->ID, 'pb_show_title', true ) ) ? 'on' : '';
	$show_title = ( $action === 'add' ) ? 'on' : $pb_show_title;
	?>
	<div class="submitbox" id="submitpost">
		<div id="minor-publishing">
			<div id="minor-publishing-actions">
				<?php
				// <!-- Glossary -->
				if ( $post_type === 'glossary' ) {
					?>
					<p>
						<input type="checkbox" name="glossary_visibility" id="glossary_visibility" value="1" <?php checked( $show_in_glossary_lists, 1 ); ?><?php echo ( $can_publish ) ? '' : ' disabled'; ?>>
						<label for="glossary_visibility"><?php _e( 'Show in Glossary Lists', 'pressbooks' ); ?></label>
					</p>
					<?php
					// <!-- Every other post_type -->
				} else {
					?>
					<div id="preview-action">
						<?php
						$preview_link = esc_url( get_preview_post_link( $post ) );
						$preview_button = sprintf(
							'%1$s<span class="screen-reader-text"> %2$s</span>',
							__( 'Preview', 'pressbooks' ),
							/* translators: accessibility text */
							__( '(opens in a new window)', 'pressbooks' )
						);
						?>
						<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview-<?php echo (int) $post->ID; ?>" id="post-preview"><?php echo $preview_button; ?></a>
						<input type="hidden" name="wp-preview" id="wp-preview" value=""/>
					</div>
					<div class="clear"></div>
					<p>
						<input type="checkbox" name="web_visibility" id="web_visibility" value="1" <?php checked( $show_in_web, 1 ); ?><?php echo ( $can_publish ) ? '' : ' disabled'; ?>>
						<label for="web_visibility"><?php _e( 'Show in Web', 'pressbooks' ); ?></label>
					</p>
					<p id="pb-password-protected">
						<input type="checkbox" name="require_password" id="require_password" value="1" <?php checked( $require_password, 1 ); ?><?php echo ( $can_publish ) ? '' : ' disabled'; ?>>
						<label for="require_password"><?php _e( 'Require a Password', 'pressbooks' ); ?></label><br/>
						<input type="text" name="post_password" id="post_password" style="text-align:left" value="<?php echo esc_attr( $post->post_password ); ?>" placeholder="<?php esc_attr_e( 'Password...', 'pressbooks' ); ?>" maxlength="255"/>
					</p>
					<p>
						<input type="checkbox" name="export_visibility" id="export_visibility" value="1" <?php checked( $show_in_exports, 1 ); ?><?php echo ( $can_publish ) ? '' : ' disabled'; ?>>
						<label for="export_visibility"><?php _e( 'Show in Exports', 'pressbooks' ); ?></label>
					</p>
					<p>
						<input type="checkbox" name="pb_show_title" id="show_title" value="on" <?php checked( $show_title, 'on' ); ?>>
						<label for="show_title"><?php _e( 'Show Title', 'pressbooks' ); ?></label>
					</p>
					<?php
				}
				?>
			</div><!-- #minor-publishing-actions -->
		</div><!-- #minor-publishing -->
		<div id="misc-publishing-actions">
	<?php
	/* translators: Publish box date format, see https://secure.php.net/date */
	$datef = __( 'M j, Y @ H:i' );
	if ( $action !== 'add' ) {
		$stamp = __( 'Created: <b>%1$s</b>' );
		$date = date_i18n( $datef, strtotime( $post->post_date ) );
	}
	if ( ! empty( $revs ) ) :
		?>
		<div class="misc-pub-section misc-pub-revisions">
			<?php
				/* translators: Post revisions heading. 1: The number of available revisions */
				printf( __( 'Revisions: %s' ), '<b>' . number_format_i18n( $revs ) . '</b>' );
			?>
			<a class="hide-if-no-js" href="<?php echo esc_url( get_edit_post_link( $latest_rev ) ); ?>"><span aria-hidden="true"><?php _ex( 'Browse', 'revisions' ); ?></span> <span class="screen-reader-text"><?php _e( 'Browse revisions' ); ?></span></a>
		</div>
		<?php
	endif;
	if ( $action !== 'add' ) :
		?>
	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="timestamp"><?php printf( $stamp, $date ); ?></span>
	</div>
		<?php
	endif;
	do_action( 'post_submitbox_misc_actions', $post );
	?>

	</div><!-- #misc-publishing-actions -->
	<div class="clear"></div>


	<div id="major-publishing-actions">
		<div id="delete-action">
		<?php
		if ( current_user_can( 'delete_post', $post->ID ) ) {
			if ( ! EMPTY_TRASH_DAYS ) {
				$delete_text = __( 'Delete Permanently' );
			} else {
				$delete_text = __( 'Move to Trash' );
			}
			?>
		<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo $delete_text; ?></a>
														<?php
		}
		?>
		</div>

		<div id="publishing-action">
		<span class="spinner"></span>
		<?php
		if ( $action === 'add' ) {
			?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ); ?>" />
				<?php submit_button( __( 'Create' ), 'primary large', 'publish', false ); ?>
			<?php
		} else {
			?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ); ?>" />
				<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Save' ); ?>" />
			<?php
		}
		?>
		</div>
		<div class="clear"></div>
	</div><!-- #major-publishing-actions -->
	</div><!-- #misc-publishing-actions -->
	<?php
}

/**
 * Save custom data from Pressbooks' Status & Visibility panel.
 *
 * @since 5.0.0
 *
 * @param int $post_id Post ID.
 * @param \WP_Post $post Post object.
 * @param bool $update Whether this is an existing post being updated or not.
 */
function publish_fields_save( $post_id, $post, $update ) {
	// Sanity checks
	if ( empty( $_POST ) ) { // @codingStandardsIgnoreLine
		return;
	}
	global $pagenow;
	if ( ! in_array( $pagenow, [ 'post.php', 'post-new.php' ], true ) ) {
		return;
	}
	if ( ! in_array(
		$post->post_type, [
			'front-matter',
			'back-matter',
			'chapter',
			'glossary',
		], true
	) ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( $post->post_status === 'trash' ) {
		return;
	}

	// Set a static variable to fix infinite hook loop
	static $recursion = false;
	if ( ! $recursion ) {
		$recursion = true;

		// @codingStandardsIgnoreStart
		$show_in_glossary_lists = ( isset( $_POST['glossary_visibility'] ) && (int) $_POST['glossary_visibility'] === 1 ) ? true : false;
		$show_in_web = ( isset( $_POST['web_visibility'] ) && (int) $_POST['web_visibility'] === 1 ) ? true : false;
		$require_password = ( isset( $_POST['require_password'] ) && (int) $_POST['require_password'] === 1 ) ? true : false;
		$show_in_exports = ( isset( $_POST['export_visibility'] ) && (int) $_POST['export_visibility'] === 1 ) ? true : false;
		$show_title = ( isset( $_POST['pb_show_title'] ) && $_POST['pb_show_title'] === 'on' ) ? 'on' : false;
		// @codingStandardsIgnoreEnd

		// Content Visibility
		if ( $post->post_type === 'glossary' ) {
			// Glossary
			$post_status = $show_in_glossary_lists ? 'publish' : 'private';
		} else {
			// Every other post_type
			if ( $show_in_web === false && $show_in_exports === false ) {
				$post_status = 'draft';
			} elseif ( $show_in_web === true && $show_in_exports === false ) {
				$post_status = 'web-only';
			} elseif ( $show_in_web === false && $show_in_exports === true ) {
				$post_status = 'private';
			} elseif ( $show_in_web === true && $show_in_exports === true ) {
				$post_status = 'publish';
			}
		}

		// Title
		if ( $show_title ) {
			update_post_meta( $post_id, 'pb_show_title', 'on' );
		} else {
			delete_post_meta( $post_id, 'pb_show_title' );
		}

		// Password
		if ( $show_in_web === false || $require_password === false ) {
			$post_password = null; // Clear the password
		} else {
			$post_password = $post->post_password;
		}

		wp_update_post(
			[
				'ID' => $post_id,
				'post_status' => $post_status ?? 'draft',
				'post_password' => $post_password,
			]
		);
		$recursion = false;
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
		<span class="description"><?php printf( __( 'This appears on the web homepage of your book and helps categorize it in your network catalog (if applicable). Use %s to determine which subject category is best for your book.', 'pressbooks' ), sprintf( '<a href="%1$s">%2$s</a>', 'https://www.editeur.org/151/Thema', __( 'these instructions', 'pressbooks' ) ) ); ?></span>
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
		<span class="description"><?php printf( __( 'This appears on the web homepage of your book. Use %s to determine which additional subject categories are appropriate for your book.', 'pressbooks' ), sprintf( '<a href="%1$s">%2$s</a>', 'https://www.editeur.org/151/Thema', __( 'these instructions', 'pressbooks' ) ) ); ?></span>
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
		<th scope="row"><label for="contributor_last_name"><?php _e( 'Last Name', 'pressbooks' ); ?></label></th>
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

/**
 * Distinguish between front matter/chapter/back matter authors and WP author
 *
 * @param string $post_type Post type.
 */
function replace_authordiv( $post_type ) {
	// See: wp-admin/edit-form-advanced.php
	$post_type_object = get_post_type_object( $post_type );
	if ( post_type_supports( $post_type, 'author' ) && current_user_can( $post_type_object->cap->edit_others_posts ) ) {

		remove_meta_box( 'authordiv', $post_type, 'normal' );
		remove_meta_box( 'authordiv', $post_type, 'side' );
		remove_meta_box( 'authordiv', $post_type, 'advanced' );

		add_meta_box( 'authordiv', __( 'Owner', 'pressbooks' ), 'post_author_meta_box', $post_type );
	}
}
