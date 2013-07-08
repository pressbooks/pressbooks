<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

$user_catalog_form_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/index.php?page=pb_catalog', 'pb-user-catalog' );

echo '<div class="wrap">';
if ( 'edit_tags' == $_REQUEST['action'] ) :

	// TODO: Move logic out of the template

	@list( $user_id, $blog_id ) = explode( ':', @$_REQUEST['ID'] );
	$user_id = absint( $user_id );
	$blog_id = absint( $blog_id );

	if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
		wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
	}

	$title = get_blog_option( $blog_id, 'blogname' );
	$catalog = new \PressBooks\Catalog( $user_id );
	$book = $catalog->getBook( $blog_id );
	$profile = $catalog->getProfile();
	?>
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e( 'Tags For', 'pressbooks' ); echo " $title"; ?></h2>

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
					<th><label for="tags_<?php echo $i; ?>"> <?php echo $name; ?><br /><em><?php _e( 'Comma delimited', 'pressbooks' ); ?></em></em></label></th>
					<td><textarea id="tags_<?php echo $i; ?>" name="tags_<?php echo $i; ?>"><?php echo esc_textarea( $catalog::tagsToString( $catalog->getTagsByBook( $blog_id, $i ) ) ); ?></textarea></td>
				</tr>
			<?php } ?>
		</table>
		<?php submit_button(); ?>
	</form>

<?php

else:

	// TODO: Move logic out of the template

	$catalog = new \PressBooks\Catalog();
	$user_id = $catalog->getUserId();
	$p = $catalog->getProfile();
?>
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e( 'My Catalog Profile', 'pressbooks' ); ?></h2>

	<form method="post" action="<?php echo $user_catalog_form_url; ?>" enctype="multipart/form-data" >
		<input type="hidden" name="action" value="edit_profile" />
		<input type="hidden" name="user_id" value="<?php echo $user_id ?>" />
		<table class="form-table">
			<tr>
				<th><label for="pb_catalog_about"><?php _e( 'About', 'pressbooks' ); ?></label></th>
				<td><textarea id="pb_catalog_about" name="pb_catalog_about"><?php echo esc_textarea( $p['pb_catalog_about'] ); ?></textarea></td>
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
				<th><label for="pb_catalog_logo"><?php _e( 'Logo Or Image', 'pressbooks' ); ?></label></th>
				<td><?php \PressBooks\Image\catalog_logo_box( $user_id ); ?></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
<?php

endif;
echo '</div>';