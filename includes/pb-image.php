<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Image;


/**
 * URL to default cover image
 *
 * @return string
 */
function default_cover_url() {

	return PB_PLUGIN_URL . 'assets/images/default-book-cover.jpg';
}


/**
 * Full path to default cover image
 *
 * @return string
 */
function default_cover_path() {

	return PB_PLUGIN_DIR . 'assets/images/default-book-cover.jpg';
}


/**
 * Determine if string is default cover image
 *
 * @param string $compare
 *
 * @return bool
 */
function is_default_cover( $compare ) {

	$found = preg_match( '~assets/images/default-book-cover.jpg$~', $compare ) ? true : false;

	return ( $found ) ? true : false;
}


/**
 * Check if a file (or stream) is a valid image type
 *
 * @param string $data
 * @param string $filename
 * @param bool $is_stream (optional)
 *
 * @return bool
 */
function is_valid_image( $data, $filename, $is_stream = false ) {

	$format = explode( '.', $filename );
	$format = strtolower( end( $format ) ); // Extension
	if ( $format == 'jpg' ) $format = 'jpeg'; // Fix stupid mistake
	if ( ! ( $format == 'jpeg' || $format == 'gif' || $format == 'png' ) ) {
		return false;
	}

	if ( $is_stream ) {

		$image = @imagecreatefromstring( $data );
		if ( $image === false ) {
			return false;
		}

	} else {

		$func = 'imagecreatefrom' . $format;
		$image = @$func( $data );
		if ( $image === false ) {
			return false;
		}
	}

	imagedestroy( $image );
	unset( $image );

	return true;
}


/**
 * Rename image with arbitrary suffix (before extension)
 *
 * @param string $thumb
 * @param string $path
 *
 * @return string
 */
function thumbify( $thumb, $path ) {

	$thumb = preg_quote( $thumb, '/' );

	return stripslashes( preg_replace( '/(\.jpe?g|\.gif|\.png)/i', "$thumb$1", $path ) );
}


/**
 * Get the attachment id from an image url.
 *
 * @param string $url
 *
 * @return int
 */
function attachment_id_from_url( $url ) {

	/** @var $wpdb \wpdb */
	global $wpdb;

	// If this is the URL of an auto-generated thumbnail, get the URL of the original image
	$url = preg_replace( '/-\d+x\d+(?=\.(jp?g|png|gif)$)/i', '', $url );

	// Remove the upload path base directory from the attachment URL
	$preg = '#(19|20)\d\d/(0[1-9]|1[012])/.+(\.jpe?g|\.gif|\.png)$#i'; # YYYY/MM/foo-Bar.png
	if ( preg_match( $preg, $url, $matches ) ) {
		$url = $matches[0];
	}

	// Get the attachment ID from the modified attachment URL
	$sql = "SELECT wposts.ID FROM {$wpdb->posts} wposts, {$wpdb->postmeta} wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment' ";
	$sql = $wpdb->prepare( $sql, $url );
	$id = $wpdb->get_var( $sql );

	return absint( $id );
}

/**
 * Try to get a thumbnail url from an image url.
 *
 * @param string $url
 * @param string $size
 *
 * @return string
 *
 */
function thumbnail_from_url( $url, $size ) {

	$id = attachment_id_from_url( $url );
	$image_thumb = wp_get_attachment_image_src( $id, $size );

	if ( $image_thumb ) return $image_thumb[0]; // URL
	else return $url; // Couldn't find anything, return original
}


/**
 * Get a list of possible intermediate image sizes.
 * If $image_sizes is not empty, then use as WP hook
 *
 * @param array $image_sizes (optional)
 *
 * @return array
 */
function intermediate_image_sizes( array $image_sizes = array() ) {

	$our_sizes = array(
		'pb_cover_small' => array( 'width' => 65, 'height' => 0, 'crop' => false ),
		'pb_cover_medium' => array( 'width' => 225, 'height' => 0, 'crop' => false ),
		'pb_cover_large' => array( 'width' => 350, 'height' => 0, 'crop' => false ),
	);

	if ( empty( $image_sizes ) ) {
		return $our_sizes;
	} else {
		// Hook for filter 'intermediate_image_sizes'
		return array_merge( $image_sizes, array_keys( $our_sizes ) );
	}
}


/**
 * Fix wp_save_image() for our custom sizes, possibly other places.
 *
 * @see wp_save_image
 */
function fix_intermediate_image_size_options() {

	$our_sizes = intermediate_image_sizes();

	foreach ( $our_sizes as $key => $val ) {
		add_filter( "pre_option_{$key}_size_w", function () use ( $val ) { return $val['width']; } );
		add_filter( "pre_option_{$key}_size_h", function () use ( $val ) { return $val['height']; } );
		add_filter( "pre_option_{$key}_crop", function () use ( $val ) { return $val['crop']; } );
	}
}


/**
 * WP Hook for filter 'intermediate_image_sizes_advanced'
 *
 * @param $image_sizes
 *
 * @return array
 */
function intermediate_image_sizes_advanced( array $image_sizes ) {

	$image_sizes = array_merge( $image_sizes, intermediate_image_sizes() );

	return $image_sizes;
}


/**
 * WP Hook for action 'delete_attachment'. Deal with user deleting cover image from Media Library.
 *
 * @param $post_id
 */
function delete_attachment( $post_id ) {

	$post = get_post( $post_id );
	$meta_post = ( new \PressBooks\Metadata() )->getMetaPost(); // PHP 5.4+

	if ( $meta_post && $post && $post->post_parent == $meta_post->ID ) {
		// Reset pb_cover_image to default
		update_post_meta( $meta_post->ID, 'pb_cover_image', \PressBooks\Image\default_cover_url() );
		\PressBooks\Book::deleteBookObjectCache();
	}
}


/**
 * WP Hook for action 'wp_update_attachment_metadata'. Deal with user editing cover image from Media Library.
 */
function save_attachment( $data, $post_id ) {

	if ( empty( $data['file'] ) )
		return $data; // Bail

	$post = get_post( $post_id );
	$meta_post = ( new \PressBooks\Metadata() )->getMetaPost(); // PHP 5.4+
	if ( ! $meta_post || ! $post || $post->post_parent != $meta_post->ID )
		return $data; // Bail

	$upload_dir = wp_upload_dir();
	$url = untrailingslashit( $upload_dir['baseurl'] ) . "/{$data['file']}";
	$pid = $meta_post->ID;

	update_post_meta( $pid, 'pb_cover_image', $url );
	\PressBooks\Book::deleteBookObjectCache();

	return $data;
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
								jQuery('#cover_image_preview').attr('src', '<?php echo \PressBooks\Image\default_cover_url(); ?>');
							});
						}
					});
				});
			});
		</script>
		<label for="pb_cover_image"><?php _e( 'Cover Image', 'pressbooks' ); ?></label>
		<div class="pb_cover_image" id="pb_cover_image-1">
			<?php if ( $pb_cover_image && ! \PressBooks\Image\is_default_cover( $pb_cover_image ) ) { ?>
				<p><img id="cover_image_preview" src="<?php echo $pb_cover_image; ?>" style="width: auto; height: 100px" alt="cover_image" /><br />
					<button id="delete_cover_button" name="<?php echo $pb_cover_image; ?>" type="button">Delete</button></p>
				<p><input type="file" name="pb_cover_image" value="" id="pb_cover_image" /></p>
				<input type="hidden" id="cover_pid" name="cover_pid" value="<?php echo $_GET['post']; ?>" />
			<?php } else { ?>
				<p><img id="cover_image_preview" src="<?php echo \PressBooks\Image\default_cover_url(); ?>" style="width: auto; height: 100px" alt="cover_image" /></p>
				<p><input type="file" name="pb_cover_image" value="<?php echo $pb_cover_image; ?>" id="pb_cover_image" /></p>
			<?php } ?>
			<span class="description"><?php _e( 'Your cover image should be 625px on the shortest side.', 'pressbooks' ); ?></span>
		</div>
	</div>
<?php
}
