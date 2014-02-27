<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content for the Export page for a book */

// -------------------------------------------------------------------------------------------------------------------
// Reusables
// -------------------------------------------------------------------------------------------------------------------

$max_batches = 5; // How many batches we save
\PressBooks\Utility\truncate_exports( $max_batches );

$export_form_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_export&export=yes', 'pb-export' );
$export_delete_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_export', 'pb-delete-export' );
$download_url_prefix = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_export&download_export_file=';

date_default_timezone_set( 'America/Montreal' );

// -------------------------------------------------------------------------------------------------------------------
// Warnings and errors
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $_GET['export_error'] ) ) {
	// Conversion failed
	printf( '<div class="error">%s</div>', __( 'Error: The export failed. See logs for more details.', 'pressbooks' ) );
}
if ( ! empty( $_GET['export_warning'] ) && ( get_option( 'pressbooks_email_validation_logs' ) || is_super_admin() ) ) {
	// Validation warnings
	printf( '<div class="error">%s %s</div>',
		__( 'Warning: The export has validation errors. See logs for more details.', 'pressbooks' ),
		get_option( 'pressbooks_email_validation_logs' ) ? __( 'Emailed to:', 'pressbooks' ) . ' ' . wp_get_current_user()->user_email : ''
	);
}

?>
<div class="wrap">

<?php do_action( 'pressbooks_top_of_export_page' ); ?>

<div id="icon-pressbooks-export" class="icon32"></div>
<h2><?php _e( 'Export', 'pressbooks' ); ?> &ldquo;<?php bloginfo( 'name' ); ?>&rdquo;</h2>
<p><?php printf( __( 'You can export multiple file formats by selecting your Export Format Options below. PressBooks saves your last %s batches of exported files.', 'pressbooks' ), $max_batches  );?></p>

<div class="export-page">

<div class="export-left">
	<p><input id="pb-export-button" type="button" class="button button-hero button-primary generate" value="<?php esc_attr_e( 'Export Your Book', 'pressbooks' ); ?>" /></p>
	<p id="loader"><img src="<?php echo PB_PLUGIN_URL; ?>assets/images/loader.gif" alt="Exporting..." width="128" height="15" /></p>
	<?php
	$c = 0; // start counter
	$files = \PressBooks\Utility\group_exports();
	foreach ( $files as $date => $exports ) {
		// Echo files to screen
		if ( $c == 0 ) { ?>
		<h2><?php _e( 'Latest Export', 'pressbooks' ); ?>: <?php echo strftime( '%B %e, %Y at %l:%M %p', $date ); ?></h2>
		<div class="export-files latest">
	<?php } elseif ( $c > 0 ) { ?>
		<h3><?php _e( 'Exported', 'pressbooks' ); ?> <?php echo strftime( '%B %e, %Y at %l:%M %p', $date ); ?></h3>
		<div class="export-files">
	<?php }
		foreach ( $exports as $file ) {
			$file_extension = substr( strrchr( $file, '.' ), 1 );
			$pre_suffix = strstr( $file, '._3.epub' );

		if ( 'html' == $file_extension )
				$file_class = 'xhtml';
			elseif ( 'xml' == $file_extension )
				$file_class = 'wxr';
			elseif ( 'epub' == $file_extension && '._3.epub' == $pre_suffix )
				$file_class = 'epub3';
		else
				$file_class = $file_extension;

			 ?>
			<form class="export-file" action="<?php echo $export_delete_url; ?>" method="post">
				<input type="hidden" name="filename" value="<?php echo $file; ?>" />
				<input type="hidden" name="delete_export_file" value="true" />
				<div class="export-file-container">
					<a class="export-file" href="<?php echo ( $download_url_prefix . $file ); ?>"><span class="export-file-icon <?php echo ( $c == 0 ? 'large' : 'small' ); ?> <?php echo $file_class; ?>" title="<?php echo esc_attr( $file ); ?>"></span></a>
					<div class="file-actions">
						<a href="<?php echo ( $download_url_prefix . $file ); ?>"><span class="icon download"></span></a>
						<button class="delete" type="submit" name="submit" src="" value="Delete" onclick="if (!confirm('<?php esc_attr_e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>')){ return false }"><span class="icon trash"></span></button>
					</div>
				</div>
			</form>
	<?php } ?>
	</div>
	<?php
		++$c;
	} ?>
</div> <!-- .export-left -->

<div class="export-right">
	<h3><?php _e( 'Your Export Format Options', 'pressbooks' ); ?></h3>
	<p><?php _e( 'Select which formats you want to export', 'pressbooks' ); ?>.</p>
    
	<?php
	$options = get_option('export_formats');
	if ( ! isset( $options['pdf'] ) ) { $options['pdf'] = 1; }
	if ( ! isset( $options['epub'] ) ) { $options['epub'] = 1; }
	if ( ! isset( $options['epub3'] ) ) { $options['epub3'] = 1; }
	if ( ! isset( $options['mobi'] ) ) { $options['mobi'] = 1; }
	if ( ! isset( $options['hpub'] ) ) { $options['hpub'] = 0; }
	if ( ! isset( $options['icml'] ) ) { $options['icml'] = 0; }
	if ( ! isset( $options['xhtml'] ) ) { $options['xhtml'] = 0; }
	if ( ! isset( $options['wxr'] ) ) { $options['wxr'] = 0; }
	?>
    <form id="pb-export-form" action="<?php echo $export_form_url ?>" method="POST">
	    <fieldset>
	       <legend><?php _e( 'Standard book formats', 'pressbooks' ); ?>:</legend>
	    	<input type="checkbox" id="pdf" name="export_formats[pdf]" value="1" <?php checked(1, $options['pdf'], true); ?>/><label for="pdf"> <?php _e( 'PDF (for printing)', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="epub" name="export_formats[epub]" value="1" <?php checked(1, $options['epub'], true); ?> onclick="fixMobi();" /><label for="epub"> <?php _e( 'EPUB (for Nook, iBooks, Kobo etc.)', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="epub3" name="export_formats[epub3]" value="1" <?php checked(1, $options['epub3'], true); ?> onclick="fixMobi();" /><label for="epub3"> <?php _e( 'EPUB3 (Experimental.)', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="mobi" name="export_formats[mobi]" value="1" <?php checked(1, $options['mobi'], true); ?> onclick="fixMobi();" /><label for="mobi"> <?php _e( 'MOBI (for Kindle)', 'pressbooks' ); ?></label>
	    </fieldset>
	    
	    <fieldset>
	    <legend>Exotic formats:</legend>
	    	<input type="checkbox" id="hpub" name="export_formats[hpub]" value="1" <?php checked(1, $options['hpub'], false); ?>/><label for="hpub"> <?php _e( 'Hpub', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="icml" name="export_formats[icml]" value="1" <?php checked(1, $options['icml'], false); ?>/><label for="icml"> <?php _e( 'ICML (for InDesign)', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="xhtml" name="export_formats[xhtml]" value="1" <?php checked(1, $options['xhtml'], false); ?>/><label for="xhtml"> <?php _e( 'XHTML', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="wxr" name="export_formats[wxr]" value="1" <?php checked(1, $options['wxr'], false); ?>/><label for="wxr"> <?php _e( 'WordPress XML', 'pressbooks' ); ?></label>
	    </fieldset>
    </form>
    <div class="clear"></div>
    <h3><?php _e( 'Your Theme Options', 'pressbooks' ); ?></h3>
<div class="theme">
	<div class="theme-screenshot">
		<img src="<?php echo get_stylesheet_directory_uri(); ?>/screenshot.png" alt="">
	</div>
	<h3 class="theme-name"><?php echo wp_get_theme();?></h3>
	<div class="theme-actions">
		<a class="button button-primary" href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/themes.php"><?php _e( 'Change Theme', 'pressbooks' ); ?></a>
		<a class="button button-secondary" href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/themes.php?page=pressbooks_theme_options"><?php _e( 'Options', 'pressbooks' ); ?></a>
	</div>
</div>    
    
</div>

<div class="clear"></div>

</div>