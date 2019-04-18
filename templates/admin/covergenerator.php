<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------------------------------------------------
// Reusables
// -------------------------------------------------------------------------------------------------------------------

$timezone = get_blog_option( 1, 'timezone_string' );
date_default_timezone_set( ! empty( $timezone ) ? $timezone : 'America/Montreal' ); // @codingStandardsIgnoreLine

$is_custom_css = \Pressbooks\CustomCss::isCustomCss();
$covers_folder = \Pressbooks\Covergenerator\Generator::getCoversFolder();

$generate_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin-post.php?action=pb_generate_cover' ), 'pb-generate-cover' );
$delete_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin-post.php?action=pb_delete_cover' ), 'pb-delete-cover' );
$delete_all_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin-post.php?action=pb_delete_all_covers' ), 'pb-delete-all-covers' );
$download_form_url = get_admin_url( get_current_blog_id(), '/admin-post.php?action=pb_download_cover&file=' );

$max_batches = 5; // How many batches we save
\Pressbooks\Utility\truncate_exports( $max_batches, $covers_folder );

// -------------------------------------------------------------------------------------------------------------------
// Warnings and errors
// -------------------------------------------------------------------------------------------------------------------

$dependency_errors = [];


if ( false == get_site_transient( 'pb_covergenerator_compatible' ) && false == \Pressbooks\Covergenerator\Covergenerator::init()->hasDependencies() ) {
	$dependency_errors['covergenerator'] = 'Cover Generator';
} else {
	set_site_transient( 'pb_covergenerator_compatible', true );
}

if ( $dependency_errors ) {
	/**
	 * Filter the array of dependency errors, remove unwanted formats.
	 *
	 * @since 3.9.8
	 *
	 * @param array $dependency_errors
	 */
	$dependency_messages = apply_filters( 'pb_dependency_errors', $dependency_errors );
	if ( ! empty( $dependency_messages ) ) {
		$formats = implode( ', ', $dependency_messages );
		printf(
			'<div class="error" role="alert"><p>%s</p></div>',
			sprintf(
				__( 'Some dependencies for %1$s exports could not be found. Please verify that you have completed the <a href="%2$s">installation instructions</a>.', 'pressbooks' ),
				( $pos = strrpos( $formats, ', ' ) ) ? substr_replace( $formats, ', ' . __( 'and', 'pressbooks' ) . ' ', $pos, strlen( ', ' ) ) : $formats,
				'http://docs.pressbooks.org/installation'
			)
		);
	}
}

?>

<div class="wrap">
	<h1><?php _e( 'Cover Generator', 'pressbooks' ); ?></h1>
	<?php if ( $is_custom_css ) { ?>
		<?php printf( '<div class="notice notice-warning"><p>%s</p></div>', __( 'You are currently using the Custom CSS theme. To generate a cover, you will need to switch back to a base theme temporarily. You can then reapply your Custom CSS theme.', 'pressbooks' ) ); ?>
	<?php } ?>


	<p><?php _e( 'Using our cover generator, you can easily create a beautiful cover for your <strong>print</strong> book that will work with most Print on Demand services, as well as an <strong>ebook</strong> cover that will meet the specifications of Kindle, Apple and other ebook retailers.', 'pressbooks' ); ?></p>

	<!-- Create the form that will be used to render our options -->
	<form class="settings-form" method="post" enctype="multipart/form-data" action="options.php">
		<?php
		settings_fields( 'pressbooks_cg' );
		do_settings_sections( 'pressbooks_cg' );
		?>
	</form>

	<h3><?php _e( 'Make Your Cover', 'pressbooks' ); ?></h3>

	<progress id="pb-sse-progressbar" max="100"></progress>
	<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info" aria-live="polite"></span></p>

	<?php if ( $is_custom_css ) { ?>
		<?php printf( '<p><em>%s</em></p>', __( 'You are currently using the Custom CSS theme. To generate a cover, you will need to switch back to a base theme temporarily. You can then reapply your Custom CSS theme.', 'pressbooks' ) ); ?>
	<?php } ?>

	<form class="generate-file pdf" action="<?php echo $generate_form_url; ?>" method="post">
		<input type="hidden" name="format" value="pdf"/>
	</form>
	<?php $disabled = ( $is_custom_css || $dependency_errors ) ? true : false; ?>
	<input type="button" id="generate-pdf" class="button<?php if ( ! $disabled ) { ?> button-primary<?php } ?>" value="<?php _e( 'Make PDF Cover', 'pressbooks' ); ?>"
			<?php if ( $disabled) { ?>disabled <?php } ?>/>

	<form class="generate-file jpg" action="<?php echo $generate_form_url; ?>" method="post">
		<input type="hidden" name="format" value="jpg"/>
	</form>
	<input type="button" id="generate-jpg" class="button<?php if ( ! $disabled ) { ?> button-primary<?php } ?>" value="<?php _e( 'Make Ebook Cover', 'pressbooks' ); ?>"
			<?php if ( $disabled ) { ?>disabled <?php } ?>/>
	<h3><?php _e( 'Download', 'pressbooks' ); ?></h3>
	<?php
	$covers = \Pressbooks\Utility\group_exports( $covers_folder );
	if ( empty( $covers ) ) {
		echo '<p>' . _e( 'Sorry! You have to generate some covers first.', 'pressbooks' ) . '</p>';
	} else {
		/* translators: %s date/time */
		printf( '<p>' . __( 'Your latest covers were generated on %s. Hover to download them.', 'pressbooks' ) . '</p>', strftime( '%B %e, %Y at %l:%M %p', array_keys( $covers )[0] ) );
		?>
		<div class="cover-files">
			<?php
			foreach ( $covers as $date => $files ) {
				foreach ( $files as $file ) {
					$icon_type = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
					?>
					<form class="cover-file" action="<?php echo $delete_form_url; ?>" method="post">
						<input type="hidden" name="filename" value="<?php echo $file; ?>"/>
						<div class="cover-file-container">
							<a class="cover-file" href="<?php echo( $download_form_url . $file ); ?>"><span class="cover-file-icon large <?php echo $icon_type; ?>"
																											title="<?php echo esc_attr( $file ); ?>"></span></a>
							<div class="file-actions">
								<a href="<?php echo( $download_form_url . $file ); ?>"><span class="dashicons dashicons-download"></span></a>
								<button class="delete" type="submit" name="submit" value="Delete"
										onclick="if ( !confirm('<?php esc_attr_e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><span
											class="dashicons dashicons-trash"></span></button>
							</div>
						</div>
					</form>
					<?php
				}
				?>
				<br/>
				<?php
			}
			?>
			<?php if ( ! empty( $covers ) && current_user_can( 'manage_network' ) ) : ?>
				<form class="delete-all" action="<?php echo $delete_all_form_url; ?>" method="post">
					<input type="hidden" name="delete_all_covers" value="1"/>
					<button class="button" type="submit" name="submit" value="Delete All Covers"
							onclick="if ( !confirm('<?php esc_attr_e( 'Are you sure you want to delete ALL your current covers?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Delete All Covers', 'pressbooks' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	} ?>
</div>
<?php date_default_timezone_set( 'UTC' ); // Set back to UTC. @see wp-settings.php ?>
