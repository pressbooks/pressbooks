<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

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

	$pb_author = get_post_meta( $pid, 'pb_author', true );
	if ( ! $pb_author ) {
		// if the pb_author metadata value is not set, set it to the primary book user's name
		if ( 0 !== get_current_user_id() ) {
			/** @var $user_info \WP_User */
			$user_info = get_userdata( get_current_user_id() );
			$name = $user_info->display_name;
			update_post_meta( $pid, 'pb_author', $name );
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

	$allowed_file_types = [ 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png' ];
	$overrides = [ 'test_form' => false, 'mimes' => $allowed_file_types ];
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
			$assets = new Assets( 'pressbooks', 'plugin' );
			wp_enqueue_style( 'part', $assets->getPath( 'styles/part.css' ) );
			add_filter(
				'page_attributes_dropdown_pages_args', function () {
					return [ 'post_type' => '__GARBAGE__' ];
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

	$licenses = [];
	foreach ( ( new \Pressbooks\Licensing() )->getSupportedTypes() as $key => $val ) {
		$licenses[ $key ] = $val['desc'];
	}

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
		'pb_author', 'metadata', [
		'group' => 'general-book-information',
		'label' => __( 'Author', 'pressbooks' ),
		]
	);

	if ( $show_expanded_metadata ) {
		x_add_metadata_field(
			'pb_author_file_as', 'metadata', [
			'group' => 'general-book-information',
			'label' => __( 'Author, file as', 'pressbooks' ),
			'description' => __( 'This ensures that your ebook will sort properly in ebook stores, by the author\'s last name.', 'pressbooks' ),
			]
		);
	}

	x_add_metadata_field(
		'pb_contributing_authors', 'metadata', [
		'group' => 'general-book-information',
		'label' => __( 'Contributing Author(s)', 'pressbooks' ),
		'multiple' => true,
		'description' => __( 'This may be used when more than one person shares the responsibility for the intellectual content of a book.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_editor', 'metadata', [
		'group' => 'general-book-information',
		'label' => __( 'Editor(s)', 'pressbooks' ),
		'multiple' => true,
		]
	);

	x_add_metadata_field(
		'pb_translator', 'metadata', [
		'group' => 'general-book-information',
		'label' => __( 'Translator(s)', 'pressbooks' ),
		'multiple' => true,
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
		'select2' => true,
		'label' => __( 'Language', 'pressbooks' ),
		'description' => __( 'This sets metadata in your ebook, making it easier to find in some stores. It also changes some system generated content for supported languages, such as the "Contents" header.', 'pressbooks' ) . '<br />' . sprintf( '<a href="https://www.transifex.com/pressbooks/pressbooks/">%s</a>', __( 'Help translate Pressbooks into your language!', 'pressbooks' ) ),
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
		'field_type' => 'select',
		'values' => [ '' => __( 'Select a License', 'pressbooks' ) ] + $licenses,
		'label' => __( 'Copyright License', 'pressbooks' ),
		'description' => __( 'You can select various licenses including Creative Commons.', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_custom_copyright', 'metadata', [
		'field_type' => 'wysiwyg',
		'group' => 'copyright',
		'label' => __( 'Copyright Notice', 'pressbooks' ),
		'description' => __( 'Enter a custom copyright notice, with whatever infomation you like. This will override the auto-generated copyright notice, and be inserted after the title page.', 'pressbooks' ),
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
			'pb_bisac_subject', 'metadata', apply_filters( 'pb_bisac_subject_field_args', [
				'group' => 'additional-catalog-information',
				'label' => __( 'BISAC Subject(s)', 'pressbooks' ),
				'multiple' => true,
				'description' => __( 'BISAC Subject Headings help libraries and (e)book stores properly classify your book.', 'pressbooks' ),
			] )
		);

		x_add_metadata_field(
			/**
			 * Filter metadata field arguments for BISAC Regional Theme.
			 *
			 * @since 4.0.0
			 */
			'pb_bisac_regional_theme', 'metadata', apply_filters( 'pb_bisac_regional_theme_field_args', [
				'group' => 'additional-catalog-information',
				'label' => __( 'BISAC Regional Theme', 'pressbooks' ),
				'description' => __( 'BISAC Regional Themes help libraries and (e)book stores properly classify your book.', 'pressbooks' ),
			] )
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
		'pb_section_author', 'chapter', [
		'group' => 'chapter-metadata',
		'label' => __( 'Chapter Author (appears in Web/ebook/PDF output)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_section_license', 'chapter', [
		'group' => 'chapter-metadata',
		'field_type' => 'select',
		'values' => [ '' => __( 'Select a License', 'pressbooks' ) ] + $licenses,
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
		'pb_section_author', 'front-matter', [
		'group' => 'front-matter-metadata',
		'label' => __( 'Front Matter Author (appears in Web/ebook/PDF output)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_section_license', 'front-matter', [
		'group' => 'front-matter-metadata',
		'field_type' => 'select',
		'values' => [ '' => __( 'Select a License', 'pressbooks' ) ] + $licenses,
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
		'pb_section_author', 'back-matter', [
		'group' => 'back-matter-metadata',
		'label' => __( 'Back Matter Author (appears in Web/ebook/PDF output)', 'pressbooks' ),
		]
	);

	x_add_metadata_field(
		'pb_section_license', 'back-matter', [
		'group' => 'back-matter-metadata',
		'field_type' => 'select',
		'values' => [ '' => __( 'Select a License', 'pressbooks' ) ] + $licenses,
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
	<?php }
}


/**
 *  Override save dialogue for Book Information
 *
 * @param \WP_Post $post
 */
function metadata_save_box( $post ) {
	if ( 'publish' === $post->post_status ) { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Update"/>
		<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="Save"/>
	<?php } else { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Publish"/>
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" value="Save" tabindex="5" accesskey="p"/>
	<?php }
}
