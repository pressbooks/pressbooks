<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content for the Export page for a book */

// -------------------------------------------------------------------------------------------------------------------
// Reusables
// -------------------------------------------------------------------------------------------------------------------

if ( isset( $_POST['delete_all_exports'] ) && check_admin_referer( 'pb-delete-all-exports' ) ) {
	$max_batches = 0; // If the user has asked to delete all batches
} else {
	$max_batches = 5; // How many batches we save
}

\Pressbooks\Utility\truncate_exports( $max_batches );

$export_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export&export=yes' ), 'pb-export' );
$export_delete_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export' ), 'pb-delete-export' );
$export_delete_all_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export' ), 'pb-delete-all-exports' );
$download_url_prefix = get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export&download_export_file=' );

$timezone_string = get_blog_option( 1, 'timezone_string' );
$date_format = get_blog_option( 1, 'date_format', 'F j, Y' );
$time_format = get_blog_option( 1, 'time_format', 'g:i a' );

if ( $timezone_string ) {
	date_default_timezone_set( $timezone_string );
} else {
	date_default_timezone_set( 'America/Montreal' );
}

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
<p><?php printf( __( 'You can export multiple file formats by selecting your Export Format Options below. Pressbooks saves your last %s batches of exported files.', 'pressbooks' ), $max_batches  );?></p>

<div class="export-page">

<div class="export-config">
	<h3><?php _e( 'Your Export Format Options', 'pressbooks' ); ?></h3>
	<p><?php _e( 'Select which formats you want to export', 'pressbooks' ); ?>.</p>

    <form id="pb-export-form" action="<?php echo $export_form_url ?>" method="POST">
	    <fieldset>
		    <legend><?php _e( 'Standard book formats', 'pressbooks' ); ?>:</legend>
		    <?php if ( true == \Pressbooks\Utility\check_prince_install() ) { ?>
		  	<input type="checkbox" id="pdf" name="export_formats[pdf]" value="1" /><label for="pdf"> <?php _e( 'PDF (for printing)', 'pressbooks' ); ?></label><br />
		    <?php } ;?>
				<?php if ( \Pressbooks\Modules\Export\Mpdf\Pdf::isInstalled() ) { ?>
				<input type="checkbox" id="mpdf" name="export_formats[mpdf]" value="1" /><label for="mpdf"> <?php _e( 'PDF (mPDF)', 'pressbooks' ); ?></label><br />
				<?php } ?>
				<input type="checkbox" id="epub" name="export_formats[epub]" value="1" /><label for="epub"> <?php _e( 'EPUB (for Nook, iBooks, Kobo etc.)', 'pressbooks' ); ?></label><br />
		  	<input type="checkbox" id="mobi" name="export_formats[mobi]" value="1" /><label for="mobi"> <?php _e( 'MOBI (for Kindle)', 'pressbooks' ); ?></label>
	    </fieldset>

	    <fieldset>
	    <legend><?php _e( 'Exotic formats', 'pressbooks' ); ?>:</legend>
		    <input type="checkbox" id="epub3" name="export_formats[epub3]" value="1" /><label for="epub3"> <?php _e( 'EPUB 3 (beta)', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="xhtml" name="export_formats[xhtml]" value="1" /><label for="xhtml"> <?php _e( 'XHTML', 'pressbooks' ); ?></label><br />
				<?php if ( true == \Pressbooks\Utility\show_experimental_features() ) { ?>
				<input type="checkbox" id="icml" name="export_formats[icml]" value="1" /><label for="icml"> <?php _e( 'ICML (for InDesign)', 'pressbooks' ); ?></label><br />
				<?php } ?>
				<input type="checkbox" id="odt" name="export_formats[odt]" value="1" /><label for="odt"> <?php _e( 'OpenDocument (beta)', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="wxr" name="export_formats[wxr]" value="1" /><label for="wxr"> <?php _e( 'Pressbooks XML', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="vanillawxr" name="export_formats[vanillawxr]" value="1" /><label for="vanillawxr"> <?php _e( 'WordPress XML', 'pressbooks' ); ?></label>
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

</div> <!-- .export-config -->

<div class="export-control">
	<p><input id="pb-export-button" type="button" class="button button-hero button-primary generate" value="<?php esc_attr_e( 'Export Your Book', 'pressbooks' ); ?>" /></p>
	<p id="loader"><img src="<?php echo PB_PLUGIN_URL; ?>assets/dist/images/loader.gif" alt="Exporting..." width="128" height="15" /></p>
	<?php
	$c = 0; // start counter
	$files = \Pressbooks\Utility\group_exports();
	foreach ( $files as $date => $exports ) {
		// Echo files to screen
		if ( $c == 0 ) { ?>
		<h2><?php _e( 'Latest Export', 'pressbooks' ); ?>: <?php printf( _x( '%s at %s', 'Date and time string, e.g. "January 1, 2016 at 12:00pm', 'pressbooks' ), date( $date_format, $date ), date( $time_format, $date ) ); ?></h2>
		<div class="export-files latest">
	<?php } elseif ( $c > 0 ) { ?>
		<h3><?php _e( 'Exported', 'pressbooks' ); ?> <?php printf( _x( '%s at %s', 'Date and time string, e.g. "January 1, 2016 at 12:00pm', 'pressbooks' ), date( $date_format, $date ), date( $time_format, $date ) ); ?></h3>
		<div class="export-files">
	<?php }
		foreach ( $exports as $file ) {
			$file_extension = substr( strrchr( $file, '.' ), 1 );
			$pre_suffix = (false == strstr( $file, '._3.epub' )) ? strstr( $file, '._vanilla.xml') : strstr( $file, '._3.epub' );

		if ( 'html' == $file_extension )
				$file_class = 'xhtml';
			elseif ( 'xml' == $file_extension && '._vanilla.xml' == $pre_suffix )
				$file_class = 'vanillawxr';
			elseif ( 'xml' == $file_extension && false == $pre_suffix )
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
						<a href="<?php echo ( $download_url_prefix . $file ); ?>"><span class="dashicons dashicons-download"></span></a>
						<button class="delete" type="submit" name="submit" src="" value="Delete" onclick="if ( !confirm('<?php esc_attr_e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><span class="dashicons dashicons-trash"></span></button>
					</div>
				</div>
			</form>
	<?php } ?>
	</div>
	<?php
		++$c;
	} ?>
	<?php if ( !empty( $files ) && current_user_can('manage_network' ) ) : ?>
	<form class="delete-all" action="<?php echo $export_delete_all_url; ?>" method="post">
		<input type="hidden" name="delete_all_exports" value="true" />
		<button class="button" type="submit" name="submit" src="" value="Delete All Exports" onclick="if ( !confirm('<?php esc_attr_e( 'Are you sure you want to delete ALL your current exports?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e('Delete All Exports', 'pressbooks'); ?></button>
	</form>
	<?php endif; ?>
</div> <!-- .export-control -->

<div class="clear"></div>

</div>
