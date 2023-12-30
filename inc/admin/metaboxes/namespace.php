<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.NonceVerification.Missing
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
namespace Pressbooks\Admin\Metaboxes;

use PressbooksMix\Assets;
use Pressbooks\Contributors;

// phpcs:ignore
define( 'METADATA_CALLBACK_INDEX', 4 );
// TODO: Security audit

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
 * @param $image
 */
function upload_cover_image( $pid, $post, $image = null ) {
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
	$image = $image ?? wp_handle_upload( $_FILES['pb_cover_image'], $overrides );

	if ( ! empty( $image['error'] ) ) {
		wp_die( $image['error'] );
	}

	list( $width, $height ) = getimagesize( $image['file'] );
	if ( $height < 800 ) {
		$_SESSION['pb_errors'][] = sprintf( __( 'Your cover image was not saved because it is too small (%1$s x %2$s). It should be at least 800px in height.', 'pressbooks' ), $width, $height );
		return; // Do not save uploaded image if it triggers a size-related error
	}

	$ratio = number_format( $width / $height, 2 );
	if ( $ratio > 1 || $ratio < .66 ) {
		$_SESSION['pb_errors'][] = sprintf( __( 'Your cover image was not saved because the aspect ratio (%s) is outside the permitted range (.66 to 1). We recommend an aspect ratio of .75.', 'pressbooks' ), $ratio );
		return; // Do not save uploaded image if it triggers a size-related error
	}

	// resize image without cropping or altering aspect ratio
	$img = wp_get_image_editor( $image['file'] );
	if ( ! is_wp_error( $img ) ) {
		$img->resize( 1500, 1500, false );
		$img->save( $image['file'] );
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
		if ( in_array( $post_type, [ 'metadata', 'front-matter', 'chapter', 'back-matter', 'part' ], true ) ) {
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

function add_meta_boxes_metadata() {
	$expanded = \Pressbooks\Metadata\show_expanded_metadata();

	( new GeneralInformation( $expanded ) )->register();
	add_meta_box( 'covers', __( 'Cover Image', 'pressbooks' ), '\Pressbooks\Image\cover_image_box', 'metadata', 'normal', 'low' );
	( new Subjects() )->register();
	( new Institutions() )->register();
	( new Copyright( $expanded ) )->register();
	( new About() )->register();

	if ( $expanded ) {
		( new AdditionalCatalogInformation( $expanded ) )->register();
	}
}

function add_meta_boxes_front_matter() {
	( new SectionMetadata( __( 'Front Matter', 'pressbooks' ) ) )->register();
}

function add_meta_boxes_chapter() {
	( new SectionMetadata( __( 'Chapter', 'pressbooks' ) ) )->register();
}

function add_meta_boxes_back_matter() {
	( new SectionMetadata( __( 'Back Matter', 'pressbooks' ) ) )->register();
}

function add_meta_boxes_part() {
	( new PartVisibility() )->register();
}

function save_metadata( $post_id ) {
	$expanded = \Pressbooks\Metadata\show_expanded_metadata();

	( new GeneralInformation( $expanded ) )->save( $post_id );
	( new Subjects() )->save( $post_id );
	( new Institutions() )->save( $post_id );
	( new Copyright( $expanded ) )->save( $post_id );
	( new About() )->save( $post_id );

	if ( $expanded ) {
		( new AdditionalCatalogInformation() )->save( $post_id );
	}
}

function save_section_metadata( $post_id ) {
	( new SectionMetadata() )->save( $post_id );
}

function save_part_metadata( $post_id ) {
	( new PartVisibility() )->save( $post_id );
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
	add_meta_box( 'status-visibility', __( 'Status & Visibility', 'pressbooks' ), __NAMESPACE__ . '\status_visibility_box', [ 'chapter', 'front-matter', 'back-matter', 'glossary' ], 'side', 'high' );

	// Book info: slug should be not available

	remove_meta_box( 'slugdiv', 'metadata', 'normal' );

	// Part Metadata

	add_action(
		'edit_form_after_editor', function ( $post ) {
			if ( 'part' === $post->post_type ) {
				$tip = __( 'Appears on part page. Parts will not appear if a book has only one part.', 'pressbooks' );
				echo '<p><span class="description">' . $tip . '</span></p>';
			}
		}
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

	$aria_label = esc_attr( __( 'Part', 'pressbooks' ) );
	$output = "<select name='parent_id' id='parent_id' aria-label='{$aria_label}'>\n";
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
		<input name="publish" id="publish" type="submit" class="button button-primary button-large" accesskey="p" value="Save"/>
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

		$show_in_glossary_lists = ( isset( $_POST['glossary_visibility'] ) && (int) $_POST['glossary_visibility'] === 1 ) ? true : false;
		$show_in_web = ( isset( $_POST['web_visibility'] ) && (int) $_POST['web_visibility'] === 1 ) ? true : false;
		$require_password = ( isset( $_POST['require_password'] ) && (int) $_POST['require_password'] === 1 ) ? true : false;
		$show_in_exports = ( isset( $_POST['export_visibility'] ) && (int) $_POST['export_visibility'] === 1 ) ? true : false;
		$show_title = ( isset( $_POST['pb_show_title'] ) && $_POST['pb_show_title'] === 'on' ) ? 'on' : false;

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
			$post_password = ! empty( $post->post_password ) ? $post->post_password : $_POST['post_password'] ?? ''; // @codingStandardsIgnoreLine
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
 * @since 5.0.0
 */
function contributor_add_form() {
	wp_nonce_field( 'contributor-meta', 'contributor_meta_nonce' );

	$contributors_fields = Contributors::getContributorFields();

	foreach ( $contributors_fields as $term => $meta_tags ) {
		switch ( $meta_tags['input_type'] ) {
			case 'tinymce':
				?>
				<div class="form-field <?php echo $meta_tags['tag']; ?>-wrap">
					<label for="<?php echo $meta_tags['tag']; ?>"><?php echo $meta_tags['label']; ?></label>
					<?php wp_editor( null, $term, get_editor_settings() ); ?>
				</div>
				<?php
				break;
			case 'picture':
				?>
				<img style="display: none" src="" id="<?php echo $meta_tags['tag']; ?>-thumbnail" width="120" /> <br />
				<div class="form-field <?php echo $meta_tags['tag']; ?>-wrap">
					<label for="<?php echo $meta_tags['tag']; ?>"><?php echo $meta_tags['label']; ?></label>
					<button name="dispatch-media-picture" id="btn-media" aria-describedby="picture-description">Upload Picture</button>
					<input type="hidden" name="<?php echo $term; ?>" id="<?php echo $meta_tags['tag']; ?>">
					<p id="picture-description">
						<?php echo __( 'Images should be square (400px x 400px). You will be allowed to crop images after upload.', 'pressbooks' ); ?>
					</p>
				</div>
				<?php
				break;
			default:
				?>
				<div class="form-field <?php echo $meta_tags['tag']; ?>-wrap">
					<label for="<?php echo $meta_tags['tag']; ?>"><?php echo $meta_tags['label']; ?></label>
					<input type="<?php echo $meta_tags['input_type'] ?>" name="<?php echo $term; ?>" id="<?php echo $meta_tags['tag']; ?>" value="" class="<?php echo $meta_tags['tag']; ?>" />
				</div>
				<?php
				break;
		}
	}
}

function contributor_add_form_picture() {
	?>
		<div id="contributor-media-picture">
			<?php wp_media_upload_handler(); ?>
		</div>
	<?php
}

function contributor_edit_form( $term ) {
	$terms_meta = get_term_meta( $term->term_id );
	$contributors_fields = Contributors::getContributorFields();

	foreach ( $contributors_fields as $term => $meta_tags ) {
		$value = $terms_meta[ $term ][0] ?? '';
		switch ( $meta_tags['input_type'] ) {
			case 'tinymce':
				?>
					<tr class="form-field <?php echo $meta_tags['tag']; ?>-wrap">
						<th scope="row"><label for="<?php echo $meta_tags['tag']; ?>"><?php echo $meta_tags['label']; ?></label></th>
						<td>
							<?php wp_nonce_field( 'contributor-meta', 'contributor_meta_nonce' ); ?>
							<?php wp_editor( html_entity_decode( $value ), $term, get_editor_settings() ); ?>
						</td>
					</tr>
				<?php
				break;
			case 'picture':
				?>
					<tr class="form-field <?php echo $meta_tags['tag']; ?>-wrap">
						<th scope="row"><label for="<?php echo $meta_tags['tag']; ?>"><?php echo $meta_tags['label']; ?></label></th>
						<td>
							<?php if ( $value ) : ?>
								<img src="<?php echo $value; ?>" id="<?php echo $meta_tags['tag']; ?>-thumbnail" width="120" /> <br />
							<?php else : ?>
								<img style="display: none" id="<?php echo $meta_tags['tag']; ?>-thumbnail" width="120" />
							<?php endif; ?>
							<br />
							<button name="dispatch-media-picture" id="btn-media" aria-describedby="picture-description">Upload Picture</button>
							<p class="description" id="picture-description">
								<?php echo __( 'Images should be square (400px x 400px). You will be allowed to crop images after upload.', 'pressbooks' ); ?>
							</p>
							<input
								type="hidden"
								name="<?php echo $term; ?>"
								id="<?php echo $meta_tags['tag']; ?>"
								value="<?php echo $value ? $value : ''; ?>"
							>
						</td>
					</tr>
							<?php
				break;
			default:
				?>
				<tr class="form-field <?php echo $meta_tags['tag']; ?>-wrap">
					<th scope="row"><label for="<?php echo $meta_tags['tag']; ?>"><?php echo $meta_tags['label']; ?></label></th>
					<td>
						<?php wp_nonce_field( 'contributor-meta', 'contributor_meta_nonce' ); ?>
						<input type="<?php echo $meta_tags['input_type'] ?>" name="<?php echo $term; ?>" id="<?php echo $meta_tags['tag']; ?>" value="<?php echo esc_attr( $value ); ?>" class="<?php echo $meta_tags['tag']; ?>-field"  />
					</td>
				</tr>
								<?php
				break;
		}
	}
}

function get_editor_settings() {
	return [
		'textarea_name' => 'contributor_description',
		'tabfocus_elements' => 'content-html,save-post',
		'editor_height' => 300,
		'wpautop' => true,
		'media_buttons' => false,
		'quicktags' => [
			'buttons' => 'strong,em,link,close',
		],
		'tinymce' => [
			'toolbar1' => 'bold,italic,|,link,unlink,|,undo,redo',
			'toolbar2' => '',
			'toolbar3' => '',
		],
	];
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

	$contributors_fields = Contributors::getContributorFields();
	foreach ( $contributors_fields as $term => $meta_tags ) {
		$value = false;
		if ( isset( $_POST[ $term ] ) ) {
			$value = $_POST[ $term ];
			if ( array_key_exists( 'sanitization_method', $meta_tags ) ) {
				$value = $meta_tags['sanitization_method']( $value );
			}
		}
		$value ? update_term_meta( $term_id, $term, $value ) : delete_term_meta( $term_id, $term );
	}
}

/**
 * Get and display custom columns in the Contributors list
 *
 * @param $string
 * @param $columns
 * @param $term_id
 */
function contributor_custom_columns( $string, $columns, $term_id ) {
	switch ( $columns ) {
		case Contributors::TAXONOMY . '_institution':
			echo esc_html( get_term_meta( $term_id, Contributors::TAXONOMY . '_institution', true ) );
			break;
		case Contributors::TAXONOMY . '_description':
			$description = wp_filter_nohtml_kses( get_term_meta( $term_id, Contributors::TAXONOMY . '_description', true ) );
			$limit_description_characters = 180;
			echo strlen( $description ) > $limit_description_characters ?
					substr( $description, 0, $limit_description_characters ) . '...' :
					$description;
			break;
		case Contributors::TAXONOMY . '_picture':
			echo '<img src=\'' .
				esc_html( get_term_meta( $term_id, Contributors::TAXONOMY . '_picture', true ) ) . '\' />';
			break;
	}
}

/**
 * Add custom columns to the Contributors list
 *
 * @param $columns
 * @return array
 */
function contributor_table_columns( $columns ) {
	// Unset default columns but keep checkbox, so we can handle bulk actions.
	$remove_keys = [ 'name', 'description', 'slug', 'posts' ];

	$columns = array_diff_key( $columns, array_flip( $remove_keys ) );

	$new_columns = [
		Contributors::TAXONOMY . '_picture' => Contributors::getContributorFields( 'picture' )['label'],
		'name' => __( 'Name', 'pressbooks' ),
		Contributors::TAXONOMY . '_institution' => Contributors::getContributorFields( 'institution' )['label'],
		Contributors::TAXONOMY . '_description' => Contributors::getContributorFields( 'description' )['label'],
	];

	return array_merge( $columns, $new_columns );
}

/**
 * Specify sortable columns in the Contributors list
 *
 * @param $columns
 * @return string[]
 */
function contributor_sortable_columns( $columns ) {
	$columns = [
		'name' => 'name',
	];
	return $columns;
}

/**
 * @see https://core.trac.wordpress.org/ticket/47018
 */
function a11y_contributor_tweaks() {
	// Are we in <term.php> or <edit-tags.php> ?
	$current_screen = get_current_screen();
	$form_name = $current_screen->base === 'term' ? 'edittag' : 'addtag';
	$tag_name = $current_screen->base === 'term' ? 'name' : 'tag-name';
	$error_msg = esc_attr( __( 'Name cannot be left blank.', 'pressbooks' ) );
	?>
	<script type='text/javascript'>
		jQuery( function ( $ ) {
			var form = document.getElementById( '<?php echo $form_name; ?>' );
			var tag = document.getElementById( '<?php echo $tag_name; ?>' );
			var button = $( '#<?php echo $form_name; ?> :submit' );
			button[0].addEventListener( 'click', function() {
				if ( tag.value ) {
					tag.setCustomValidity( '' );
				} else {
					tag.setCustomValidity( '<?php echo $error_msg; ?>' );
					form.reportValidity();
				}
			} );
		} );
	</script>
	<?php
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
