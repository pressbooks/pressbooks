<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

$user_catalog_form_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/index.php?page=pb_catalog', 'pb-user-catalog' );

// TODO: Move logic out of the template

@list( $user_id, $blog_id ) = explode( ':', @$_REQUEST['ID'] );
$user_id = absint( $user_id );
$blog_id = absint( $blog_id );

if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
	wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
}

$tag_groups = 2;
$title =  get_blog_option( $blog_id, 'blogname' );
$catalog = new \PressBooks\Catalog( $user_id );
$book = $catalog->getBook( $blog_id );

?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2>Tags For <?php echo $title; ?></h2>

	<form method="post" action="<?php echo $user_catalog_form_url; ?>" >
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
			<?php for ( $i = 1; $i <= $tag_groups; ++$i ) { ?>
				<?php $name = "Tags $i"; ?>
				<tr>
					<th><label for="tags_<?php echo $i; ?>"> <?php echo $name; ?><br /><em>Comma delimited</em></em></label></th>
					<td><textarea id="tags_<?php echo $i; ?>" name="tags_<?php echo $i; ?>"><?php echo esc_textarea( $catalog::tagsToString( $catalog->getTagsByBook( $blog_id, $i ) ) ); ?></textarea></td>
				</tr>
			<?php } ?>
		</table>
		<?php submit_button(); ?>
	</form>

	<?php /*

	<div id="icon-options-general" class="icon32"></div>
	<h2>My Catalog Profile</h2>

	<form method="post" action="<?php echo $user_catalog_form_url; ?>" enctype="multipart/form-data" >
		<input type="hidden" name="catalog_id" value="<?php echo $ID ?>" />
		<table class="form-table">
			<!-- Featured -->
			<tr>
				<th><label for="about">About</label></th>
				<td><textarea id="about" name="about"></textarea></td>
			</tr>
			<tr>
				<th><label for="logo">Logo Or Image</label></th>
				<td>Todo</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>

 	*/ ?>

</div>
