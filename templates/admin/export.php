<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

$dependency_errors = array();

if ( false == \Pressbooks\Modules\Export\Prince\Pdf::hasDependencies() ) {
	$prince = false;
	$dependency_errors['pdf'] = 'PDF';
} else {
	$prince = true;
}

if ( false == \Pressbooks\Modules\Export\Epub\Epub201::hasDependencies() ) {
	$epub = false;
	$dependency_errors['epub'] = 'EPUB';
} else {
	$epub = true;
}

if ( false == \Pressbooks\Modules\Export\Mobi\Kindlegen::hasDependencies() ) {
	$mobi = false;
	$dependency_errors['mobi'] = 'MOBI';
} else {
	$mobi = true;
}

if ( false == \Pressbooks\Modules\Export\Xhtml\Xhtml11::hasDependencies() ) {
	$xhtml = false;
	$dependency_errors['xhtml'] = 'XHTML';
} else {
	$xhtml = true;
}

if ( false == \Pressbooks\Modules\Export\InDesign\Icml::hasDependencies() ) {
	$icml = false;
	$dependency_errors['icml'] = 'ICML';
} else {
	$icml = true;
}

if ( false == \Pressbooks\Modules\Export\Odt\Odt::hasDependencies() ) {
	$odt = false;
	$dependency_errors['odt'] = 'OpenDocument';
} else {
	$odt = true;
}

foreach ( $dependency_errors as $key => $format ) {
	printf( '<div class="error"><p>%s</p></div>', sprintf( __( 'Some dependencies for %1$s export could not be found. Please verify that you have completed the <a href="%2$s">installation instructions</a>.', 'pressbooks' ), $format, 'https://pressbooks.org/installation' ) );
}

if ( ! empty( $_GET['export_error'] ) ) {
	// Conversion failed
	printf( '<div class="error"><p>%s</p></div>', __( 'Error: The export failed. See logs for more details.', 'pressbooks' ) );
}
$exportoptions = get_option( 'pressbooks_export_options' );
if ( ! empty( $_GET['export_warning'] ) && ( 1 == $exportoptions['email_validation_logs']  || is_super_admin() ) ) {
	// Validation warnings
	printf( '<div class="error"><p>%s</p>%s</div>',
		__( 'Warning: The export has validation errors. See logs for more details.', 'pressbooks' ),
		( isset( $exportoptions['email_validation_logs'] ) && 1 == $exportoptions['email_validation_logs'] ) ? '<p>' . __( 'Emailed to:', 'pressbooks' ) . ' ' . wp_get_current_user()->user_email . '</p>' : ''
	);
}

?>
<div class="wrap">

<?php do_action( 'pressbooks_top_of_export_page' ); ?>

<div id="icon-pressbooks-export" class="icon32"></div>
<h2><?php _e( 'Export', 'pressbooks' ); ?> &ldquo;<?php bloginfo( 'name' ); ?>&rdquo;</h2>
<p><?php printf( __( 'You can export multiple file formats by selecting your Export Format Options below. Pressbooks saves your last %s batches of exported files.', 'pressbooks' ), '5' );?></p>

<div class="export-page">

<div class="export-config">
	<h3><?php _e( 'Your Export Format Options', 'pressbooks' ); ?></h3>
	<p><?php _e( 'Select which formats you want to export', 'pressbooks' ); ?>.</p>

	<form id="pb-export-form" action="<?php echo $export_form_url ?>" method="POST">
	    <fieldset class="standard">
				<legend><?php _e( 'Standard book formats', 'pressbooks' ); ?>:</legend>
	  		<input type="checkbox" id="print_pdf" name="export_formats[print_pdf]" value="1" <?php if ( false == $prince ) { ?>disabled <?php } ?>/><label for="print_pdf"> <?php _e( 'PDF (for print)', 'pressbooks' ); ?></label><br />
				<input type="checkbox" id="pdf" name="export_formats[pdf]" value="1" <?php if ( false == $prince ) { ?>disabled <?php } ?>/><label for="pdf"> <?php _e( 'PDF (for digital distribution)', 'pressbooks' ); ?></label><br />
				<?php if ( true == \Pressbooks\Modules\Export\Mpdf\Pdf::hasDependencies() ) { ?>
					<input type="checkbox" id="mpdf" name="export_formats[mpdf]" value="1" /><label for="mpdf"> <?php _e( 'PDF (mPDF)', 'pressbooks' ); ?></label><br />
				<?php } ?>
				<input type="checkbox" id="epub" name="export_formats[epub]" value="1" <?php if ( false == $epub ) { ?>disabled <?php } ?>/><label for="epub"> <?php _e( 'EPUB (for Nook, iBooks, Kobo etc.)', 'pressbooks' ); ?></label><br />
		  	<input type="checkbox" id="mobi" name="export_formats[mobi]" value="1" <?php if ( false == $mobi ) { ?>disabled <?php } ?>/><label for="mobi"> <?php _e( 'MOBI (for Kindle)', 'pressbooks' ); ?></label>
	    </fieldset>

	    <fieldset class="exotic">
	    <legend><?php _e( 'Exotic formats', 'pressbooks' ); ?>:</legend>
		    <input type="checkbox" id="epub3" name="export_formats[epub3]" value="1" <?php if ( false == $epub ) { ?>disabled <?php } ?>/><label for="epub3"> <?php _e( 'EPUB 3 (beta)', 'pressbooks' ); ?></label><br />
	    	<input type="checkbox" id="xhtml" name="export_formats[xhtml]" value="1" <?php if ( false == $xhtml ) { ?>disabled <?php } ?>/><label for="xhtml"> <?php _e( 'XHTML', 'pressbooks' ); ?></label><br />
				<?php if ( true == \Pressbooks\Utility\show_experimental_features() ) { ?>
				<input type="checkbox" id="icml" name="export_formats[icml]" value="1" <?php if ( false == $icml ) { ?>disabled <?php } ?>/><label for="icml"> <?php _e( 'ICML (for InDesign)', 'pressbooks' ); ?></label><br />
				<?php } ?>
				<input type="checkbox" id="odt" name="export_formats[odt]" value="1" <?php if ( false == $odt ) { ?>disabled <?php } ?>/><label for="odt"> <?php _e( 'OpenDocument (beta)', 'pressbooks' ); ?></label><br />
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
		if ( 0 == $c ) { ?>
		<h2><?php _e( 'Latest Export', 'pressbooks' ); ?>: <?php printf( _x( '%1$s at %2$s', 'Date and time string, e.g. "January 1, 2016 at 12:00pm', 'pressbooks' ), date( $date_format, $date ), date( $time_format, $date ) ); ?></h2>
		<div class="export-files latest">
	<?php } elseif ( $c > 0 ) { ?>
		<h3><?php _e( 'Exported', 'pressbooks' ); ?> <?php printf( _x( '%1$s at %2$s', 'Date and time string, e.g. "January 1, 2016 at 12:00pm', 'pressbooks' ), date( $date_format, $date ), date( $time_format, $date ) ); ?></h3>
		<div class="export-files">
	<?php }
foreach ( $exports as $file ) {
	$file_extension = substr( strrchr( $file, '.' ), 1 );
	switch ( $file_extension ) {
		case 'epub':
			$pre_suffix = strstr( $file, '._3.epub' );
			break;
		case 'pdf':
			$pre_suffix = strstr( $file, '._print.pdf' );
			break;
		case 'xml':
			$pre_suffix = strstr( $file, '._vanilla.xml' );
			break;
		default:
			$pre_suffix = false;
	}
	if ( 'html' == $file_extension ) {
		$file_class = 'xhtml';
	} elseif ( 'xml' == $file_extension && '._vanilla.xml' == $pre_suffix ) {
		$file_class = 'vanillawxr';
	} elseif ( 'xml' == $file_extension && false == $pre_suffix ) {
		$file_class = 'wxr';
	} elseif ( 'epub' == $file_extension && '._3.epub' == $pre_suffix ) {
		$file_class = 'epub3';
	} elseif ( 'pdf' == $file_extension && '._print.pdf' == $pre_suffix ) {
		$file_class = 'print-pdf';
	} else {
		$file_class = $file_extension;
	}

?>
	<form class="export-file" action="<?php echo $export_delete_url; ?>" method="post">
		<input type="hidden" name="filename" value="<?php echo $file; ?>" />
		<input type="hidden" name="delete_export_file" value="true" />
		<div class="export-file-container">
	<a class="export-file" href="<?php echo ( $download_url_prefix . $file ); ?>"><span class="export-file-icon <?php echo ( 0 == $c ? 'large' : 'small' ); ?> <?php echo $file_class; ?>" title="<?php echo esc_attr( $file ); ?>"></span></a>
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
	<?php if ( ! empty( $files ) && current_user_can( 'manage_network' ) ) : ?>
	<form class="delete-all" action="<?php echo $export_delete_all_url; ?>" method="post">
		<input type="hidden" name="delete_all_exports" value="true" />
		<button class="button" type="submit" name="submit" src="" value="Delete All Exports" onclick="if ( !confirm('<?php esc_attr_e( 'Are you sure you want to delete ALL your current exports?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Delete All Exports', 'pressbooks' ); ?></button>
	</form>
	<?php endif; ?>
</div> <!-- .export-control -->

<div class="clear"></div>

</div>
