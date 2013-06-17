<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

// TODO: __() all the strings

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
	<h2>Tags For <?php echo $title; ?></h2>

	<form method="post" action="<?php echo $user_catalog_form_url; ?>" >
		<input type="hidden" name="action" value="edit_tags" />
		<input type="hidden" name="ID" value="<?php echo "$user_id:$blog_id"; ?>" />
		<table class="form-table">
			<!-- Featured -->
			<tr>
				<th><label for="featured">Featured</label></th>
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
				$name = ! empty( $profile["pressbooks_catalog_tag_{$i}_name"] ) ? $profile["pressbooks_catalog_tag_{$i}_name"] : "Tags $i";
				?>
				<tr>
					<th><label for="tags_<?php echo $i; ?>"> <?php echo $name; ?><br /><em>Comma delimited</em></em></label></th>
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
	<h2>My Catalog Profile</h2>

	<form method="post" action="<?php echo $user_catalog_form_url; ?>" enctype="multipart/form-data" >
		<input type="hidden" name="action" value="edit_profile" />
		<input type="hidden" name="user_id" value="<?php echo $user_id ?>" />
		<table class="form-table">
			<tr>
				<th><label for="pressbooks_catalog_about">About</label></th>
				<td><textarea id="pressbooks_catalog_about" name="pressbooks_catalog_about"><?php echo esc_textarea( $p['pressbooks_catalog_about'] ); ?></textarea></td>
			</tr>
			<?php for ( $i = 1; $i <= $catalog::$maxTagsGroup; ++$i ) { ?>
				<?php $name = "Tags Name $i"; ?>
				<tr>
					<th><label for="pressbooks_catalog_tag_<?php echo $i; ?>_name"><?php echo $name; ?></label></th>
					<td>
						<input type="text" name="pressbooks_catalog_tag_<?php echo $i; ?>_name" id="pressbooks_catalog_tag_<?php echo $i; ?>_name" value="<?php echo esc_attr( $p["pressbooks_catalog_tag_{$i}_name"] ) ?>" class="regular-text" />
					</td>
				</tr>
			<?php } ?>
			<tr>
				<th><label for="pressbooks_catalog_logo">Logo Or Image</label></th>
				<td>Todo</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
<?php

endif;
echo '</div>';