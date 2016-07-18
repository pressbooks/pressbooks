<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

$user_catalog_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog' ), 'pb-user-catalog' );

echo '<div class="wrap">';
if ( 'edit_tags' == $_REQUEST['action'] ) :

	// TODO: Move logic out of the template

	@list( $user_id, $blog_id ) = explode( ':', @$_REQUEST['ID'] );
	$user_id = absint( $user_id );
	$blog_id = absint( $blog_id );

	if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
		wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
	}

	$catalog = new \Pressbooks\Catalog( $user_id );
	$book = $catalog->getBook( $blog_id );
	$profile = $catalog->getProfile();

	?>
	<h2><?php _e( 'Tags For', 'pressbooks' ); echo ' ' . get_blog_option( $blog_id, 'blogname' ); ?></h2>

	<form method="post" action="<?php echo $user_catalog_form_url; ?>" >
		<input type="hidden" name="action" value="edit_tags" />
		<input type="hidden" name="ID" value="<?php echo "$user_id:$blog_id"; ?>" />
		<table class="form-table">
			<!-- Featured -->
			<tr>
				<th><label for="featured"><?php _e( 'Featured', 'pressbooks' ); ?></label></th>
				<td><select id="featured" name="featured">
						<?php
						for ( $i = 0; $i <= 9; ++$i ) {
							echo "<option value='$i' ";
							selected( $i == $book['featured'] );
							echo ">$i</option>";
						}
						?>
					</select></td>
			</tr>
			<!-- Tags -->
			<?php for ( $i = 1; $i <= $catalog::$maxTagsGroup; ++$i ) { ?>
				<?php
				$name = ! empty( $profile["pb_catalog_tag_{$i}_name"] ) ? $profile["pb_catalog_tag_{$i}_name"] : __( 'Tags', 'pressbooks' ) . " $i";
				?>
				<tr>

					<pre><?php  ?></pre>
					<th><label for="tags_<?php echo $i; ?>"> <?php echo $name; ?><br /></em></label></th>
					<td>
						<select id="tags_<?php echo $i; ?>" name="tags_<?php echo $i; ?>[]" multiple style="width: 75%">
							<?php $tags = $catalog->getTagsByBook( $blog_id, $i );
							foreach( $catalog->getTags( $i ) as $tag ) {
								$selected = ( in_array( $tag, $tags ) ) ? ' selected' : '';
								echo '<option value="' . $tag['tag'] . '"' . $selected . '>' . $tag['tag'] . '</option>';
							} ?>
						</select>
				</td>
				</tr>
			<?php } ?>
		</table>
		<?php submit_button(); ?>
	</form>
	<script>
		jQuery(function ($) {
			<?php for ( $i = 1; $i <= $catalog::$maxTagsGroup; ++$i ) { ?>
			$("#tags_<?php echo $i; ?>").select2({
				tags: true,
				tokenSeparators: [","]
			});
			<?php } ?>
		});
	</script>
<?php

else:

	// TODO: Move logic out of the template

	$catalog = new \Pressbooks\Catalog();
	$user_id = $catalog->getUserId();
	$p = $catalog->getProfile();

	?>
	<h2><?php echo ( get_current_user_id() != $user_id ) ? ucfirst( get_userdata( absint( $user_id ) )->user_login ) : __( 'My Catalog Profile', 'pressbooks' ); ?></h2>

	<form method="post" action="<?php echo $user_catalog_form_url; ?>" enctype="multipart/form-data" >
		<input type="hidden" name="action" value="edit_profile" />
		<input type="hidden" name="user_id" value="<?php echo $user_id ?>" />
		<table class="form-table">
			<tr>
				<th><label for="pb_catalog_about"><?php _e( 'About', 'pressbooks' ); ?></label></th>
				<td><textarea id="pb_catalog_about" name="pb_catalog_about" rows="5" cols="30"><?php echo esc_textarea( $p['pb_catalog_about'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="pb_catalog_url"><?php _e( 'URL', 'pressbooks' ); ?></label></th>
				<td><input type="text" id="pb_catalog_url" name="pb_catalog_url" value="<?php echo esc_attr( $p['pb_catalog_url'] ); ?>" class="regular-text" /></td>
			</tr>
			<?php for ( $i = 1; $i <= $catalog::$maxTagsGroup; ++$i ) { ?>
				<?php $name = __( 'Tags Name', 'pressbooks' ) . " $i" ?>
				<tr>
					<th><label for="pb_catalog_tag_<?php echo $i; ?>_name"><?php echo $name; ?></label></th>
					<td>
						<input type="text" name="pb_catalog_tag_<?php echo $i; ?>_name" id="pb_catalog_tag_<?php echo $i; ?>_name" value="<?php echo esc_attr( $p["pb_catalog_tag_{$i}_name"] ) ?>" class="regular-text" />
					</td>
				</tr>
			<?php } ?>
			<tr>
				<th><label for="pb_catalog_color"><?php _e( 'Sidebar Color', 'pressbooks' ); ?></label></th>
				<td><input type="text" name="pb_catalog_color" id="pb_catalog_color" class="pb_catalog_color" value="<?php echo esc_attr( $p['pb_catalog_color'] ) ?>" /></td>
			<tr>
				<th><label for="pb_catalog_logo"><?php _e( 'Logo Or Image', 'pressbooks' ); ?></label></th>
				<td><?php \Pressbooks\Image\catalog_logo_box( $user_id ); ?></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
<?php

endif;
echo '</div>';
