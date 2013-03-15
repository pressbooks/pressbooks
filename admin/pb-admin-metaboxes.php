<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Metaboxes;


/**
 * If the user updates the book's title, then also update the blog name
 *
 * @param int      $pid
 * @param \WP_Post $post
 */
function title_update( $pid, $post ) {
	if ( 'metadata' == $post->post_type ) {
		$pb_title = get_post_meta( $post->ID, 'pb_title', true );
		if ( $pb_title ) { // if the pb_title metadata value is set, update the blogname to match it
			update_option( 'blogname', $pb_title );
		} else { // if the pb_title metadata value is not set, update it to match the blogname
			$pb_title = get_option( 'blogname' );
			update_post_meta( $post->ID, 'pb_title', $pb_title );
		}
	}
}


/**
 * If the user leaves certain meta info blank, forcefully fill it with our own
 *
 * @param int      $pid
 * @param \WP_Post $post
 */
function add_required_data( $pid, $post ) {

	$pb_author = get_post_meta( $post->ID, 'pb_author', true );
	if ( ! $pb_author ) {
		// if the pb_author metadata value is not set, set it to the primary book user's name
		if ( 0 !== get_current_user_id() ) {
			/** @var $user_info \WP_User */
			$user_info = get_userdata( get_current_user_id() );
			$name = $user_info->display_name;
			update_post_meta( $post->ID, 'pb_author', $name );
		}
	}

	$pb_language = get_post_meta( $post->ID, 'pb_language', true );
	if ( ! $pb_language ) {
		// if the pb_language metadata value is not set, set it to 'en'
		update_post_meta( $post->ID, 'pb_language', 'en' );
	}

	$pb_cover_image = get_post_meta( $post->ID, 'pb_cover_image', true );
	if ( ! $pb_cover_image ) {
		// if the pb_cover_image metadata value is not set, set it to the default image
		update_post_meta( $post->ID, 'pb_cover_image', PB_PLUGIN_URL . 'assets/images/default-book-cover.png' );
	}
}


/**
 * Process uploaded cover image
 *
 * @param $pid
 * @param $post
 */
function upload_cover_image( $pid, $post ) {

	if ( ! @empty( $_FILES['pb_cover_image']['name'] ) ) {

		$allowed_file_types = array( 'jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png' );
		$overrides = array( 'test_form' => false, 'mimes' => $allowed_file_types );
		$image = wp_handle_upload( $_FILES['pb_cover_image'], $overrides );

		if ( ! empty( $image['error'] ) ) {
			wp_die( $image['error'] );
		} else {
			// TODO: delete old image
			update_post_meta( $pid, 'pb_cover_image', $image['url'] );
		}
	}
}


/**
 * Force a stylesheet onto our Book Information edit page
 *
 * @param string $hook
 */
function add_metadata_styles( $hook ) {

	if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
		if ( 'metadata' == get_post_type() ) {
			wp_enqueue_style( 'metadata', PB_PLUGIN_URL . 'assets/css/metadata.css' );
		}
	}
}


/**
 * Register all metadata groups and fields
 */
function add_meta_boxes() {

	// Override WordPress' parent_id

	add_meta_box( 'chapter-parent', __( 'Part', 'pressbooks' ), __NAMESPACE__ . '\override_parent_id', 'chapter', 'side', 'high' );

	// Save Buttons

	add_meta_box( 'part-save', __( 'Save Part', 'pressbooks' ), __NAMESPACE__ . '\part_save_box', 'part', 'side', 'high' );
	add_meta_box( 'metadata-save', __( 'Save Book Information', 'pressbooks' ), __NAMESPACE__ . '\metadata_save_box', 'metadata', 'side', 'high' );

	// Custom Image Upload

	add_meta_box( 'covers', __( 'Covers', 'pressbooks' ), __NAMESPACE__ . '\cover_image_box', 'metadata', 'normal', 'low' );

	// Book Metadata

	x_add_metadata_group( 'general-book-information', 'metadata', array(
		'label' => __( 'General Book Information', 'pressbooks' ),
		'priority' => 'high'
	) );

	x_add_metadata_field( 'pb_title', 'metadata', array(
		'group' => 'general-book-information',
		'label' => 'Title'
	) );

	x_add_metadata_field( 'pb_short_title', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Short Title', 'pressbooks' ),
		'description' => __( 'In case of long titles that might be truncated in running heads in the PDF export.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_subtitle', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Subtitle', 'pressbooks' ),
	) );

	x_add_metadata_field( 'pb_author', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Author', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_author_file_as', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Author, file as', 'pressbooks' ),
		'description' => __( 'This ensures that your ebook will sort properly in ebook stores, by the author\'s last name.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_publisher', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Publisher', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_publisher_city', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Publisher City', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_publication_date', 'metadata', array(
		'field_type' => 'datepicker',
		'group' => 'general-book-information',
		'label' => __( 'Publication Date', 'pressbooks' ),
	) );

	x_add_metadata_field( 'pb_onsale_date', 'metadata', array(
		'field_type' => 'datepicker',
		'group' => 'general-book-information',
		'label' => __( 'On-Sale Date', 'pressbooks' ),
		'description' => __( 'The date you want your book to start selling in stores.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_ebook_isbn', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Ebook ISBN', 'pressbooks' ),
		'description' => __( 'ISBN is the International Standard Book Number, and you\'ll need one if you want to sell your book in online ebook stores.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_print_isbn', 'metadata', array(
		'group' => 'general-book-information',
		'label' => __( 'Print ISBN', 'pressbooks' ),
		'description' => __( 'ISBN is the International Standard Book Number, and you\'ll need one if you want to sell your book in online and physical book stores.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_language', 'metadata', array(
		'group' => 'general-book-information',
		'field_type' => 'select',
		'values' => \PressBooks\L10n\get_languages(),
		'label' => __( 'Language', 'pressbooks' )
	) );

	x_add_metadata_group( 'copyright', 'metadata', array(
		'label' => __( 'Copyright', 'pressbooks' ),
		'priority' => 'low'
	) );

	x_add_metadata_field( 'pb_copyright_year', 'metadata', array(
		'group' => 'copyright',
		'label' => __( 'Copyright Year', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_copyright_holder', 'metadata', array(
		'group' => 'copyright',
		'label' => __( 'Copyright Holder', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_custom_copyright', 'metadata', array(
		'field_type' => 'wysiwyg',
		'group' => 'copyright',
		'label' => __( 'Copyright Notice', 'pressbooks' )
	) );

	x_add_metadata_group( 'about-the-book', 'metadata', array(
		'label' => 'About the Book',
		'priority' => 'low'
	) );

	x_add_metadata_field( 'pb_about_140', 'metadata', array(
		'group' => 'about-the-book',
		'label' => __( 'Book Tagline', 'pressbooks' ),
		'description' => __( 'A very short description of your book. It should fit in a Twitter post, and encapsulate your book in the briefest sentence.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_about_50', 'metadata', array(
		'field_type' => 'textarea',
		'group' => 'about-the-book',
		'label' => __( 'Short Description', 'pressbooks' ),
		'description' => __( 'A short paragraph about your book, for catalogs, reviewers etc. to quote.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_about_unlimited', 'metadata', array(
		'field_type' => 'wysiwyg',
		'group' => 'about-the-book',
		'label' => __( 'Long Description', 'pressbooks' ),
		'description' => __( 'The full description of your book.', 'pressbooks' )
	) );

	x_add_metadata_group( 'additional-catalogue-information', 'metadata', array(
		'label' => __( 'Additional Catalogue Information', 'pressbooks' ),
		'priority' => 'low'
	) );

	x_add_metadata_field( 'pb_series_title', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Series Title', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_series_number', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Series Number', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_editor', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Editor', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_translator', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Translator', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_keywords_tags', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Keywords', 'pressbooks' ),
		'multiple' => true,
		'description' => __( 'To make it easier to find your book in online book stores and search engines.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_hashtag', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Hashtag', 'pressbooks' ),
		'description' => __( 'For those of you who like Twitter.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_list_price_print', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'List Price (Print)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_list_price_pdf', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'List Price (PDF)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_list_price_epub', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'List Price (ebook)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_list_price_web', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'List Price (Web)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_bisac_subject', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Bisac Subject', 'pressbooks' ),
		'multiple' => true,
		'description' => __( 'BISAC subject headings help your book get properly classified in (e)book stores.', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_bisac_regional_theme', 'metadata', array(
		'group' => 'additional-catalogue-information',
		'label' => __( 'Bisac Regional Theme', 'pressbooks' )
	) );

	// Only display Catalogue Order metadata field if site is running a root theme other than PressBooks Root.

	switch_to_blog(1);
	$root_theme = wp_get_theme();
	if ( 'pressbooks-root' !== $root_theme->Template ) {
		x_add_metadata_field( 'pb_catalogue_order', 'metadata', array(
			'group' => 'additional-catalogue-information',
			'label' => __( 'Catalogue Order', 'pressbooks' )
		) );
	}
	restore_current_blog();

	// Chapter Metadata

	x_add_metadata_group( 'chapter-metadata', 'chapter', array(
		'label' => __( 'Chapter Metadata', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_short_title', 'chapter', array(
		'group' => 'chapter-metadata',
		'label' => __( 'Chapter Short Title (appears in the PDF running header)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_subtitle', 'chapter', array(
		'group' => 'chapter-metadata',
		'label' => __( 'Chapter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_section_author', 'chapter', array(
		'group' => 'chapter-metadata',
		'label' => __( 'Chapter Author (appears in Web/ebook/PDF output)', 'pressbooks' )
	) );

	// Chapter Parent

	x_add_metadata_group( 'chapter-parent', 'chapter', array(
		'label' => __( 'Part', 'pressbooks' ),
		'context' => 'side',
		'priority' => 'high'
	) );

	// Export

	x_add_metadata_group( 'export', array( 'chapter', 'front-matter', 'back-matter' ), array(
		'label' => __( 'Export Settings', 'pressbooks' ),
		'context' => 'side',
		'priority' => 'high'
	) );

	x_add_metadata_field( 'pb_export', array( 'chapter', 'front-matter', 'back-matter' ), array(
		'group' => 'export',
		'field_type' => 'checkbox',
		'label' => __( 'Include in exports', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_show_title', array( 'chapter', 'front-matter', 'back-matter' ), array(
		'group' => 'export',
		'field_type' => 'checkbox',
		'label' => 'Show title in exports'
	) );

	// Front Matter Metadata

	x_add_metadata_group( 'front-matter-metadata', 'front-matter', array(
		'label' => __( 'Front Matter Metadata', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_short_title', 'front-matter', array(
		'group' => 'front-matter-metadata',
		'label' => __( 'Front Matter Short Title (appears in the PDF running header)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_subtitle', 'front-matter', array(
		'group' => 'front-matter-metadata',
		'label' => __( 'Front Matter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' )
	) );

	x_add_metadata_field( 'pb_section_author', 'front-matter', array(
		'group' => 'front-matter-metadata',
		'label' => __( 'Front Matter Author (appears in Web/ebook/PDF output)', 'pressbooks' )
	) );

}


/**
 * Render "Part" meta box
 *
 * @param |WP_Post $post
 */
function override_parent_id( $post ) {

	if ( 'chapter' != $post->post_type )
		return; // Do nothing

	if ( $post->post_parent ) {
		$selected = $post->post_parent;
	} elseif ( isset( $_GET['startparent'] ) ) {
		$selected = absint( $_GET['startparent'] );
	} else {
		$selected = 0;
	}

	$pages = wp_dropdown_pages(
		array(
			'post_type' => 'part',
			'selected' => $selected,
			'name' => 'parent_id',
			'sort_column' => 'menu_order',
			'echo' => 0
		)
	);

	if ( ! empty( $pages ) ) {
		echo $pages;
	}
}

/**
 * Render "Covers" meta box
 *
 * @param |WP_Post $post
 */
function cover_image_box( $post ) {
	$ajax_nonce = wp_create_nonce( 'pb-delete-cover-image' );
	$pb_cover_image = get_post_meta( $post->ID, 'pb_cover_image', true );
	?>
		<div class="custom-metadata-field image">
		<script type="text/javascript">
			jQuery.noConflict();
			jQuery(document).ready(function($){
				jQuery('#delete_cover_button').click(function(e) {
					if (!confirm('Are you sure you want to delete this?')){
						e.preventDefault();
						return false;
					}
					image_file = jQuery(this).attr('name');
					pid = jQuery('#cover_pid').attr('value');
					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'pb_delete_cover_image',
							filename: image_file,
							pid: pid,
							_ajax_nonce: '<?php echo $ajax_nonce ?>'
						},
						success: function(data) {
							jQuery('#delete_cover_button').remove();
							jQuery("#cover_image_preview").fadeOut("slow", function () {
								jQuery("#cover_image_preview").load(function () { //avoiding blinking, wait until loaded
									jQuery("#cover_image_preview").fadeIn();
								});
								jQuery('#cover_image_preview').attr('src', '<?php echo PB_PLUGIN_URL; ?>assets/images/default-book-cover.png');
							});
						}
					});
				});
			});
	  </script>
			<label for="pb_cover_image">Cover Image</label>
				<div class="pb_cover_image" id="pb_cover_image-1">
	<?php if ( $pb_cover_image && ! preg_match( '~assets/images/default-book-cover.png$~', $pb_cover_image ) ) { ?>
		<p><img id="cover_image_preview" src="<?php echo $pb_cover_image; ?>" style="width: auto; height: 100px" alt="cover_image" /><br />
		<button id="delete_cover_button" name="<?php echo $pb_cover_image; ?>" type="button">Delete</button></p>
		<p><input type="file" name="pb_cover_image" value="" id="pb_cover_image" /></p>
		<input type="hidden" id="cover_pid" name="cover_pid" value="<?php echo $_GET['post']; ?>" />
	<?php } else { ?>
		 <p><img id="cover_image_preview" src="<?php echo PB_PLUGIN_URL; ?>assets/images/default-book-cover.png" style="width: auto; height: 100px" alt="cover_image" /></p>
		<p><input type="file" name="pb_cover_image" value="<?php echo $pb_cover_image; ?>" id="pb_cover_image" /></p>
	<?php } ?>
				</div>
		</div>
<?php
}


/**
 * WP_Ajax hook for delete_cover_image
 */
function delete_cover_image() {

	if ( current_user_can_for_blog( get_current_blog_id(), 'delete_posts' ) && check_ajax_referer( 'pb-delete-cover-image' ) ) {

		$image_file = $_POST['filename'];
		$pid = $_POST['pid'];
		$image_path = \PressBooks\Utility\get_media_path( $image_file );

		if ( file_exists( $image_path ) ) {
			unlink( $image_path );
		}

		update_post_meta( $pid, 'pb_cover_image', PB_PLUGIN_URL . 'assets/images/default-book-cover.png' );
		\PressBooks\Book::deleteBookObjectCache();
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
	if ( $post->post_status == 'publish' ) { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Update" />
		<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="Save" />
	<?php } else { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Publish" />
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" value="Save" tabindex="5" accesskey="p" />
	<?php }
}


/**
 *  Override save dialogue for Book Information
 *
 * @param \WP_Post $post
 */
function metadata_save_box( $post ) {
	if ( $post->post_status == 'publish' ) { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Update" />
		<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="Save" />
	<?php } else { ?>
		<input name="original_publish" type="hidden" id="original_publish" value="Publish" />
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" value="Save" tabindex="5" accesskey="p" />
	<?php }
}
