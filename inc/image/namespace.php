<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Image;

use Jenssegers\ImageHash\ImageHash;

/**
 * URL to default cover image
 *
 * @since 5.4.0
 * @param $size The size of the cover image to output.
 *
 * @return string
 */
function default_cover_url( $size = 'full' ) {

	switch ( $size ) {
		case 'thumbnail':
			$suffix = '-100x100';
			break;
		case 'small':
			$suffix = '-65x0';
			break;
		case 'medium':
			$suffix = '-225x0';
			break;
		case 'large':
		case 'full':
		default:
			$suffix = '';
			break;
	}

	/**
	 * Filter the URL of the default cover image.
	 *
	 * @since 5.4.0
	 *
	 */
	return apply_filters( 'pb_default_cover_url', PB_PLUGIN_URL . "assets/dist/images/default-book-cover${suffix}.jpg", $suffix );
}


/**
 * Full path to default cover image
 *
 * @since 5.4.0
 * @param $size The size of the cover image to output.
 *
 * @return string
 */
function default_cover_path( $size = 'full' ) {

	switch ( $size ) {
		case 'thumbnail':
			$suffix = '-100x100';
			break;
		case 'small':
			$suffix = '-65x0';
			break;
		case 'medium':
			$suffix = '-225x0';
			break;
		case 'large':
		case 'full':
		default:
			$suffix = '';
			break;
	}

	/**
	 * Filter the path of the default cover image.
	 *
	 * @since 5.4.0
	 *
	 */
	return apply_filters( 'pb_default_cover_path', PB_PLUGIN_DIR . "assets/dist/images/default-book-cover${suffix}.jpg", $suffix );
}


/**
 * Determine if string is default cover image
 *
 * @param string $compare
 *
 * @return bool
 */
function is_default_cover( $compare ) {
	$pattern = '~' . str_replace( plugins_url( '/' ), '', default_cover_url() ) . '$~';
	$found = preg_match( $pattern, $compare );

	return ( $found ) ? true : false;
}


/**
 * Check if a file (or stream) is a valid image type
 *
 * @param string $file Can be a temporary filename (think $_FILE before saving to $filename) or a stream (string). Default is temporary filename
 * @param string $filename
 * @param bool $is_stream (optional)
 *
 * @return bool
 */
function is_valid_image( $file, $filename, $is_stream = false ) {

	$format = explode( '.', $filename );
	$format = strtolower( end( $format ) ); // Extension
	if ( ! ( 'jpg' === $format || 'jpeg' === $format || 'gif' === $format || 'png' === $format ) ) {

		/**
		 * @since 5.5.0
		 *
		 * Filters if a image has a valid extension
		 *
		 * @param boolean $valid if is valid
		 * @param string $extension the extension of the file
		 */
		if ( ! apply_filters( 'pb_is_valid_image_extension', false, $format ) ) {
			return false;
		}
	}

	if ( $is_stream ) {
		$tmpfile = tmpfile();
		$tmpfile_meta = stream_get_meta_data( $tmpfile );
		\Pressbooks\Utility\put_contents( $tmpfile_meta['uri'], $file );
		$file = $tmpfile_meta['uri'];
	}

	$type = @exif_imagetype( $file ); // @codingStandardsIgnoreLine
	if ( IMAGETYPE_JPEG === $type || IMAGETYPE_GIF === $type || IMAGETYPE_PNG === $type ) {
		return true;
	} else {
		/**
		 * @since 5.5.0
		 *
		 * Filters if a image is a valid image type
		 *
		 * @param boolean $valid if is valid
		 * @param string $type the type of the image
		 * @param file $file the file
		 */
		return apply_filters( 'pb_is_valid_image_type', false, $type, $file );
	}
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
 * Returns the upload path and basename from attachment URL (ie. 2017/08/foo-bar.png), or unchanged if no match is found.
 *
 * @param string $url
 *
 * @return string
 */
function strip_baseurl( $url ) {

	$preg = '#(19|20)\d\d/(0[1-9]|1[012])/.+(\.jpe?g|\.gif|\.png)$#i'; # YYYY/MM/foo-Bar.png
	if ( preg_match( $preg, $url, $matches ) ) {
		$url = $matches[0];
	}

	return $url;
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

	$attached_file = strip_baseurl( $url );

	// Get the attachment ID from the modified attachment URL
	$id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
			WHERE {$wpdb->posts}.post_type = 'attachment' AND {$wpdb->postmeta}.meta_key = '_wp_attached_file' AND {$wpdb->postmeta}.meta_value = %s ", $attached_file
		)
	);

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

	if ( $image_thumb ) {
		return $image_thumb[0]; // URL
	} else {
		return $url; // Couldn't find anything, return original
	}
}


/**
 * Get a list of possible intermediate image sizes.
 * If $image_sizes is not empty, then use as WP hook
 *
 * @param array $image_sizes (optional)
 *
 * @return array
 */
function intermediate_image_sizes( array $image_sizes = [] ) {

	$our_sizes = [
		'pb_cover_small' => [
			'width' => 65,
			'height' => 0,
			'crop' => false,
		],
		'pb_cover_medium' => [
			'width' => 225,
			'height' => 0,
			'crop' => false,
		],
		'pb_cover_large' => [
			'width' => 350,
			'height' => 0,
			'crop' => false,
		],
	];

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
		add_filter(
			"pre_option_{$key}_size_w", function () use ( $val ) {
				return $val['width'];
			}
		);
		add_filter(
			"pre_option_{$key}_size_h", function () use ( $val ) {
				return $val['height'];
			}
		);
		add_filter(
			"pre_option_{$key}_crop", function () use ( $val ) {
				return $val['crop'];
			}
		);
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
	$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();

	if ( $meta_post && $post && $post->post_parent === $meta_post->ID ) {

		// Reset pb_cover_image to default
		update_post_meta( $meta_post->ID, 'pb_cover_image', \Pressbooks\Image\default_cover_url() );
		\Pressbooks\Book::deleteBookObjectCache();

	} elseif ( $post && strpos( $post->post_name, 'pb-catalog-logo' ) === 0 ) {

		// Reset pb_catalog_logo to default

		/** @var $wpdb \wpdb */
		global $wpdb;

		$attached_file = strip_baseurl( wp_get_attachment_url( $post->ID ) );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'pb_catalog_logo' AND meta_value REGEXP %s ",
				$post->post_author,
				"{$attached_file}$" // End of string regex for URL
			)
		);

		if ( $id ) {
			update_user_meta( $post->post_author, 'pb_catalog_logo', \Pressbooks\Image\default_cover_url() );
		}
	}
}


/**
 * WP Hook for action 'wp_update_attachment_metadata'. Deal with user editing cover image from Media Library.
 *
 * @param array $data
 * @param int $post_id
 *
 * @return array
 */
function save_attachment( $data, $post_id ) {

	if ( empty( $data['file'] ) ) {
		return $data; // Bail
	}

	$post = get_post( $post_id );
	$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
	$upload_dir = wp_upload_dir();
	$url = untrailingslashit( $upload_dir['baseurl'] ) . "/{$data['file']}";

	if ( $meta_post && $post && $post->post_parent === $meta_post->ID ) {

		// Update pb_cover_image to point to edited file
		update_post_meta( $meta_post->ID, 'pb_cover_image', $url );
		\Pressbooks\Book::deleteBookObjectCache();

	} elseif ( $post && strpos( $post->post_name, 'pb-catalog-logo' ) === 0 ) {

		// Update pb_catalog_logo to point to edited file
		update_user_meta( $post->post_author, 'pb_catalog_logo', $url );
	}

	return $data;
}


/**
 * Render "Cover Image" meta box
 *
 * @param |WP_Post $post
 */
function cover_image_box( $post ) {

	$meta_key = 'pb_cover_image';
	$pid = ( isset( $_GET['post'] ) ) ? (int) $_GET['post'] : 0;
	$image_url = thumbnail_from_url( get_post_meta( $post->ID, $meta_key, true ), 'pb_cover_medium' );
	$action = 'pb_delete_cover_image';
	$nonce = wp_create_nonce( 'pb-delete-cover-image' );
	$description = __( 'Cover Image should be 1:1.5 aspect ratio. Recommended dimensions are 2500px × 3750px, maximum size is 2MB.<br />NOTE: This cover will be included in your ebook files but not your PDF export. Read more <a href="https://guide.pressbooks.com/chapter/how-to-design-your-book-cover/">here</a>.', 'pressbooks' );

	render_cover_image_box( $meta_key, $pid, $image_url, $action, $nonce, $description );
}


/**
 * Render "Catalog Logo" meta box
 *
 * @param int $user_id
 */
function catalog_logo_box( $user_id ) {

	$meta_key = 'pb_catalog_logo';
	$image_url = \Pressbooks\Catalog::thumbnailFromUserId( $user_id, 'pb_cover_medium' );
	$action = 'pb_delete_catalog_logo';
	$nonce = wp_create_nonce( 'pb-delete-catalog-logo' );

	render_cover_image_box( $meta_key, absint( $user_id ), $image_url, $action, $nonce );
}


/**
 * Render cover image widget
 *
 * @param $form_id
 * @param $cover_pid
 * @param $image_url
 * @param $ajax_action
 * @param $nonce
 * @param string $description (optional)
 */
function render_cover_image_box( $form_id, $cover_pid, $image_url, $ajax_action, $nonce, $description = '' ) {
	?>
	<div class="custom-metadata-field image">
		<script type="text/javascript">
			// <![CDATA[
			jQuery.noConflict();
			jQuery(document).ready(function ($) {
				jQuery('#delete_cover_button').click(function (e) {
					if (!confirm('<?php esc_attr_e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>')) {
						e.preventDefault();
						return false;
					}
					var image_file = jQuery(this).attr('name');
					var pid = jQuery('#cover_pid').attr('value');
					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: '<?php echo $ajax_action; ?>',
							filename: image_file,
							pid: pid,
							_ajax_nonce: '<?php echo $nonce; ?>'
						},
						success: function (data) {
							jQuery('#delete_cover_button').remove();
							jQuery("#cover_image_preview").fadeOut("slow", function () {
								jQuery("#cover_image_preview").load(function () { //avoiding blinking, wait until loaded
									jQuery("#cover_image_preview").fadeIn();
								})
									.attr('src', '<?php echo \Pressbooks\Image\default_cover_url(); ?>');
							});
						}
					});
				});
			});
			// ]]>
		</script>
		<div class="<?php echo $form_id; ?>" id="<?php echo $form_id; ?>-1">
			<?php if ( $image_url && ! \Pressbooks\Image\is_default_cover( $image_url ) ) { ?>
				<p><img id="cover_image_preview" src="<?php echo $image_url; ?>" style="width:auto;height:100px;" alt="cover_image"/><br/>
					<button id="delete_cover_button" name="<?php echo $image_url; ?>" type="button" class="button-secondary"><?php _e( 'Delete', 'pressbooks' ); ?></button>
				</p>
				<p><input type="file" name="<?php echo $form_id; ?>" value="" id="<?php echo $form_id; ?>"/></p>
				<input type="hidden" id="cover_pid" name="cover_pid" value="<?php echo $cover_pid; ?>"/>
			<?php } else { ?>
				<p><img id="cover_image_preview" src="<?php echo \Pressbooks\Image\default_cover_url(); ?>" style="width:auto;height:100px;" alt="cover_image"/></p>
				<p><input type="file" name="<?php echo $form_id; ?>" value="<?php echo $image_url; ?>" id="<?php echo $form_id; ?>"/></p>
			<?php } ?>
			<?php
			if ( $description ) :
				?>
<span class="description"><?php echo $description; ?></span><?php endif; ?>
		</div>
	</div>
	<?php
}


/**
 * Proportionally resize an image file
 *
 * @param string $format
 * @param string $fullpath
 * @param int $max_w (optional)
 * @param int $max_h (optional)
 *
 * @throws \Exception
 */
function resize_down( $format, $fullpath, $max_w = 1024, $max_h = 1024 ) {

	if ( 'jpg' === $format ) {
		$format = 'jpeg'; // fix stupid mistake
	}
	if ( ! ( 'jpeg' === $format || 'gif' === $format || 'png' === $format ) ) {
		throw new \Exception( 'Invalid image format' );
	}

	/* Try to avoid problems with memory limit */
	fudge_factor( $format, $fullpath );

	/* Proceed with resizing */

	$func = 'imagecreatefrom' . $format;
	$src = $func( $fullpath );

	// try again, but ignore warnings for jpeg only
	if ( ! $src && 0 === strcmp( 'jpeg', $format ) ) {
		ini_set( 'gd.jpeg_ignore_warning', 1 );
		$src = '@' . $func( $fullpath );
	}

	// try again, but replace with placeholder image
	if ( ! $src ) {
		$src = imagecreatetruecolor( 150, 100 );
		$bkgd_color = imagecolorallocate( $src, 255, 255, 255 );
		$font_color = imagecolorallocate( $src, 0, 0, 0 );

		imagefilledrectangle( $src, 0, 0, 150, 100, $bkgd_color );
		imagestring( $src, 3, 5, 5, 'Error loading image', $font_color );

	}

	list( $orig_w, $orig_h ) = getimagesize( $fullpath );
	$ratio = $orig_w * 1.0 / $orig_h;
	$w_oversized = ( $orig_w > $max_w );
	$h_oversized = ( $orig_h > $max_h );

	if ( $w_oversized || $h_oversized ) {
		$new_w = round( min( $max_w, $ratio * $max_h ) );
		$new_h = round( min( $max_h, $max_w / $ratio ) );
	} else {
		return; // Do nothing, image is small enough already
	}

	$dst = imagecreatetruecolor( $new_w, $new_h );
	imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h );
	imagedestroy( $src );

	$func = 'image' . $format;
	$func( $dst, $fullpath );
	imagedestroy( $dst );
}


/**
 * Adjust memory for large images
 *
 * @param string $format expect jpg, jpeg, gif, or png
 * @param string $fullpath path to read image file
 */
function fudge_factor( $format, $fullpath ) {

	$size = @getimagesize( $fullpath ); // @codingStandardsIgnoreLine
	if ( false === $size ) {
		return;
	}

	if ( 'jpeg' === $format ) {
		// Jpeg
		$fudge = 1.65; // This is a guestimate, your mileage may very
		$memory_needed = round( ( $size[0] * $size[1] * $size['bits'] * $size['channels'] / 8 + pow( 2, 16 ) ) * $fudge );
	} else {
		// Not Sure
		$memory_needed = $size[0] * $size[1];
		if ( isset( $size['bits'] ) ) {
			$memory_needed = $memory_needed * $size['bits'];
		}
		$memory_needed = round( $memory_needed );
	}

	if ( memory_get_usage() + $memory_needed > (int) ini_get( 'memory_limit' ) * pow( 1024, 2 ) ) {
		$memory_limit = (int) ini_get( 'memory_limit' ) + ceil( ( ( memory_get_usage() + $memory_needed ) - (int) ini_get( 'memory_limit' ) * pow( 1024, 2 ) ) / pow( 1024, 2 ) ) . 'M';
		trigger_error( "Image is too big, attempting to compensate by setting memory_limit to {$memory_limit} ...", E_USER_WARNING );
		ini_set( 'memory_limit', $memory_limit );
	}

}

/**
 * Checks if the file extension matches its mimetype, returns a modified
 * filename if they don't match.
 *
 * @param string $path_to_file
 * @param string $filename
 *
 * @return string - modified filename if the extension did not match the mimetype,
 * otherwise returns the filename that was passed to it
 */
function proper_image_extension( $path_to_file, $filename ) {
	$mimes = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
	];

	// Attempt to determine the real file type of a file.
	$validate = wp_check_filetype_and_ext( $path_to_file, $filename, $mimes );

	// change filename to the extension that matches its mimetype
	if ( false !== $validate['proper_filename'] ) {
		return $validate['proper_filename'];
	} else {
		return $filename;
	}
}

/**
 * Get image DPI
 *
 * @param $path_to_file
 * @param bool $force_exif (optional)
 *
 * @return float|false DPI. On failure, false is returned.
 */
function get_dpi( $path_to_file, $force_exif = false ) {

	if ( extension_loaded( 'imagick' ) && $force_exif === false ) {
		try {
			$image = new \Imagick( $path_to_file );
			$res = $image->getImageResolution();
			if ( isset( $res['x'], $res['y'] ) && $res['x'] === $res['y'] ) {
				$dpi = (float) $res['x'];
			}
		} catch ( \Exception $e ) {
			return false;
		}
	} else {
		$exif = @exif_read_data( $path_to_file ); // @codingStandardsIgnoreLine
		if ( isset( $exif['XResolution'], $exif['YResolution'] ) && $exif['XResolution'] === $exif['YResolution'] ) {
			$dpi = (float) $exif['XResolution'];
		}
	}

	return ! empty( $dpi ) ? $dpi : false;
}

/**
 * Greatest common divisor
 *
 * @param int $a
 * @param int $b
 *
 * @return int
 */
function gcd( $a, $b ) {
	return ( $a % $b ) ? gcd( $b, $a % $b ) : $b;
}

/**
 * Get image aspect ratio
 *
 * @param string $path_to_file
 *
 * @return string|false Aspect ratio. On failure, false is returned.
 */
function get_aspect_ratio( $path_to_file ) {
	list( $x, $y ) = @getimagesize( $path_to_file ); // @codingStandardsIgnoreLine
	if ( empty( $x ) || empty( $y ) ) {
		return false;
	}
	$gcd = gcd( $x, $y );
	return ( $x / $gcd ) . ':' . ( $y / $gcd );
}

/**
 * Check if two images have the same aspect ratio
 *
 * @param string $path_to_file_1
 * @param string $path_to_file_2
 *
 * @return bool
 */
function same_aspect_ratio( $path_to_file_1, $path_to_file_2 ) {

	$a = get_aspect_ratio( $path_to_file_1 );
	$b = get_aspect_ratio( $path_to_file_2 );

	if ( $a === false || $b === false ) {
		return false;
	}

	if ( $a === $b ) {
		return true;
	}

	// Plan b, test against what would happen on resize
	// (big height / big width) x small width = small height

	list( $width1, $height1 ) = @getimagesize( $path_to_file_1 ); // @codingStandardsIgnoreLine
	list( $width2, $height2 ) = @getimagesize( $path_to_file_2 ); // @codingStandardsIgnoreLine

	if ( $width1 && $width2 ) {
		if ( $width1 > $width2 ) {
			$x = ( $height1 / $width1 ) * $width2;
			if ( round( $x ) === round( $height2 ) ) {
				return true;
			}
		} else {
			$x = ( $height2 / $width2 ) * $width1;
			if ( round( $x ) === round( $height1 ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Return a number representing the differences between two images.
 * Low distance values will indicate that the images are similar or the same, high distance values indicate that the images are different.
 *
 * @param string $path_to_file_1
 * @param string $path_to_file_2
 *
 * @return int|false Distance. On failure, false is returned.
 */
function differences( $path_to_file_1, $path_to_file_2 ) {

	try {
		$hasher = new ImageHash();
		$distance = $hasher->compare( $path_to_file_1, $path_to_file_2 );
	} catch ( \Exception $e ) {
		return false;
	}

	return $distance;
}

/**
 * Check if $smaller and $bigger are the same image, but $bigger is bigger
 *
 * @param string $smaller path to smaller image file
 * @param string $bigger path to bigger image file
 *
 * @return bool
 */
function is_bigger_version( $smaller, $bigger ) {
	if (
		same_aspect_ratio( $smaller, $bigger ) &&
		differences( $smaller, $bigger ) <= 5
	) {
		// Check if the image is, in fact, bigger.
		list( $x1, $y1 ) = getimagesize( $smaller );
		list( $x2, $y2 ) = getimagesize( $bigger );
		if ( $x1 < $x2 && $y1 < $y2 ) {
			return true;
		}
	}
	return false;
}

/**
 * Change image URL to bigger original version (if we can find it)
 *
 * @param string $url
 *
 * @return string
 */
function maybe_swap_with_bigger( $url ) {

	if ( ! preg_match( '/-\d+x\d+(?=\.(jp?g|png|gif)$)/i', $url ) ) {
		// Does not look like resized image, return unchanged
		return $url;
	}

	$id = attachment_id_from_url( $url );
	if ( ! $id ) {
		// Could not find in database, return unchanged
		return $url;
	}

	$upload_dir = dirname( get_attached_file( $id ) );
	$attached_file = strip_baseurl( $url );
	$src_file = basename( $attached_file );

	$meta = wp_get_attachment_metadata( $id, true );
	if ( $meta['file'] === $attached_file ) {
		// This is the original image, return unchanged
		return $url;
	}

	$original_file = basename( $meta['file'] );
	foreach ( $meta['sizes'] as $size ) {
		$resized_file = basename( $size['file'] );
		if ( $resized_file === $src_file ) {
			// Check if original image is a good replacement
			$a = "{$upload_dir}/{$resized_file}";
			$b = "{$upload_dir}/{$original_file}";
			if ( is_bigger_version( $a, $b ) ) {
				$url = \Pressbooks\Utility\str_lreplace( $src_file, $original_file, $url );
			}
		}
	}

	return $url;
}
