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

$dependency_errors = [];

if ( false == get_site_transient( 'pb_pdf_compatible' ) && false == \Pressbooks\Modules\Export\Prince\Filters::hasDependencies() ) {
	$dependency_errors['pdf'] = 'PDF';
} else {
	set_site_transient( 'pb_pdf_compatible', true );
}

if ( false == get_site_transient( 'pb_print_pdf_compatible' ) && false == \Pressbooks\Modules\Export\Prince\Filters::hasDependencies() ) {
	$dependency_errors['print_pdf'] = 'Print PDF';
} else {
	set_site_transient( 'pb_print_pdf_compatible', true );
}

if ( false == get_site_transient( 'pb_epub_compatible' ) && false == \Pressbooks\Modules\Export\Epub\Epub201::hasDependencies() ) {
	$dependency_errors['epub'] = 'EPUB';
} else {
	set_site_transient( 'pb_epub_compatible', true );
}

if ( false == get_site_transient( 'pb_mobi_compatible' ) && false == \Pressbooks\Modules\Export\Mobi\Kindlegen::hasDependencies() ) {
	$dependency_errors['mobi'] = 'MOBI';
} else {
	set_site_transient( 'pb_mobi_compatible', true );
}

if ( false == get_site_transient( 'pb_epub3_compatible' ) && false == \Pressbooks\Modules\Export\Epub\Epub3::hasDependencies() ) {
	$dependency_errors['epub3'] = 'EPUB3';
} else {
	set_site_transient( 'pb_epub3_compatible', true );
}

if ( false == get_site_transient( 'pb_xhtml_compatible' ) && false == \Pressbooks\Modules\Export\Xhtml\Xhtml11::hasDependencies() ) {
	$dependency_errors['xhtml'] = 'XHTML';
} else {
	set_site_transient( 'pb_xhtml_compatible', true );
}

if ( false == get_site_transient( 'pb_icml_compatible' ) && false == \Pressbooks\Modules\Export\InDesign\Icml::hasDependencies() ) {
	$dependency_errors['icml'] = 'ICML';
} else {
	set_site_transient( 'pb_icml_compatible', true );
}

if ( false == get_site_transient( 'pb_odt_compatible' ) && false == \Pressbooks\Modules\Export\Odt\Odt::hasDependencies() ) {
	$dependency_errors['odt'] = 'OpenDocument';
} else {
	set_site_transient( 'pb_odt_compatible', true );
}

if ( false == get_site_transient( 'pb_htmlbook_compatible' ) && false == \Pressbooks\Modules\Export\HTMLBook\HTMLBook::hasDependencies() ) {
	$dependency_errors['htmlbook'] = 'HTMLBook';
} else {
	set_site_transient( 'pb_htmlbook_compatible', true );
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
			'<div class="error"><p>%s</p></div>',
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

<?php
/**
 * @since 4.3.0
 */
do_action( 'pb_top_of_export_page' );

/**
 * @deprecated 4.3.0 Use pb_top_of_export_page instead.
 */
do_action( 'pressbooks_top_of_export_page' );
?>

<div id="icon-pressbooks-export" class="icon32"></div>
<h2><?php _e( 'Export', 'pressbooks' ); ?> &ldquo;<?php bloginfo( 'name' ); ?>&rdquo;</h2>
<p><?php printf( __( 'You can export multiple file formats by selecting your Export Format Options below. Pressbooks saves your last %s batches of exported files.', 'pressbooks' ), '5' );?></p>

<div class="export-page">

<div class="export-config">
	<h3 class="export-config__header"><?php _e( 'Your Export Format Options', 'pressbooks' ); ?></h3>
	<p><?php _e( 'Select which formats you want to export', 'pressbooks' ); ?>.</p>

<?php
/**
 * @since 3.9.8
 * Add custom export formats to the export page format list.
 *
 * For example, here's how one might add a hypothetical Word export format:
 *
 * add_filter( 'pb_export_formats', function ( $formats ) {
 * 	$formats['exotic']['docx'] = __( 'Word (Beta)', 'pressbooks' );
 *	return $formats;
 * } );
 *
 */

$formats = apply_filters( 'pb_export_formats', [
	'standard' => [
		'print_pdf' => __( 'PDF (for print)', 'pressbooks' ),
		'pdf' => __( 'PDF (for digital distribution)', 'pressbooks' ),
		'epub' => __( 'EPUB (for Nook, iBooks, Kobo etc.)', 'pressbooks' ),
		'mobi' => __( 'MOBI (for Kindle)', 'pressbooks' ),
	],
	'exotic' => [
		'epub3' => __( 'EPUB 3', 'pressbooks' ),
		'xhtml' => __( 'XHTML', 'pressbooks' ),
		'htmlbook' => __( 'HTMLBook', 'pressbooks' ),
		'odt' => __( 'OpenDocument', 'pressbooks' ),
		'wxr' => __( 'Pressbooks XML', 'pressbooks' ),
		'vanillawxr' => __( 'WordPress XML', 'pressbooks' ),
	],
] ); ?>

	<form id="pb-export-form" action="<?php echo $export_form_url ?>" method="POST">
		<fieldset class="standard">
				<legend><?php _e( 'Supported formats', 'pressbooks' ); ?>:</legend>
<?php foreach ( $formats['standard'] as $key => $value ) {
	printf(
		'<input type="checkbox" id="%1$s" name="export_formats[%1$s]" value="1" %2$s/><label for="%1$s"> %3$s</label><br />',
		$key,
		isset( $dependency_errors[ $key ] ) ? 'disabled' : '',
		$value
	);
} ?>
			</fieldset>

		<fieldset class="exotic">
		<legend><?php _e( 'Other formats', 'pressbooks' ); ?>:</legend>
<?php foreach ( $formats['exotic'] as $key => $value ) {
	printf(
		'<input type="checkbox" id="%1$s" name="export_formats[%1$s]" value="1" %2$s/><label for="%1$s"> %3$s</label><br />',
		$key,
		isset( $dependency_errors[ $key ] ) ? 'disabled' : '',
		$value
	);
} ?>
		</fieldset>

		<?php
			/**
			 * @since 5.3.0
			 *
			 * Fires just before the export html form ends
			 * Use this hook to add additional input UI to the Pressbooks export admin page.
			 */
			do_action( 'pb_export_form_end' );
		?>
	</form>
	<div class="clear"></div>
	<h3><?php _e( 'Your Theme Options', 'pressbooks' ); ?></h3>
	<div class="theme">
		<div class="theme-screenshot">
			<img src="<?php echo apply_filters( 'pb_stylesheet_directory_uri', get_stylesheet_directory_uri() ); ?>/screenshot.png" alt="">
		</div>
		<h3 class="theme-name"><?php echo wp_get_theme();?><?php if ( \Pressbooks\Theme\Lock::init()->isLocked() ) { echo ' <span class="dashicons dashicons-lock" style="vertical-align: text-bottom;"></span>'; } ?></h3>
		<div class="theme-actions">
			<a class="button button-primary" href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/themes.php"><?php _e( 'Change Theme', 'pressbooks' ); ?></a>
			<a class="button button-secondary" href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/themes.php?page=pressbooks_theme_options"><?php _e( 'Options', 'pressbooks' ); ?></a>
		</div>
	</div>

</div> <!-- .export-config -->

<div class="export-control">
	<p><input id="pb-export-button" type="button" class="button button-hero button-primary generate" value="<?php esc_attr_e( 'Export Your Book', 'pressbooks' ); ?>" /></p>
	<div id="pb-sse-progressbar"></div>
	<p id="pb-sse-info"></p>
	<?php
		/**
		 * @since 5.3.0
		 *
		 * Filters whether to show the default export file list.
		 * Use this hook to disable the default export file list and add your own.
		 *
		 * @param bool $value Whether to show the default export file list.
		 *                    Returning false to the filter will disable the output. Default true.
		 */
		if ( apply_filters( 'pb_export_show_files', true ) ) :
	?>
	<?php
	$c = 0; // start counter
	$files = \Pressbooks\Utility\group_exports();
	foreach ( $files as $date => $exports ) {
		// Echo files to screen
		if ( 0 == $c ) { ?>
		<h2><?php _e( 'Latest Export', 'pressbooks' ); ?>: <?php printf( _x( '%1$s at %2$s', 'Date and time string, e.g. "January 1, 2016 at 12:00pm', 'pressbooks' ), date( $date_format, $date ), date( $time_format, $date ) ); ?></h2>
		<div class="export-files latest">
	<?php } elseif ( $c > 0 ) { ?>
		<h3 class="export-control__header"><?php _e( 'Exported', 'pressbooks' ); ?> <?php printf( _x( '%1$s at %2$s', 'Date and time string, e.g. "January 1, 2016 at 12:00pm', 'pressbooks' ), date( $date_format, $date ), date( $time_format, $date ) ); ?></h3>
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
		case 'html':
			$pre_suffix = strstr( $file, '.-htmlbook.html' );
			break;
		case 'xml':
			$pre_suffix = strstr( $file, '._vanilla.xml' );
			break;
		default:
			$pre_suffix = false;
	}
	if ( 'html' === $file_extension && '.-htmlbook.html' === $pre_suffix ) {
		$file_class = 'htmlbook';
	} elseif ( 'html' === $file_extension && false === $pre_suffix ) {
		$file_class = 'xhtml';
	} elseif ( 'xml' === $file_extension && '._vanilla.xml' === $pre_suffix ) {
		$file_class = 'vanillawxr';
	} elseif ( 'xml' === $file_extension && false === $pre_suffix ) {
		$file_class = 'wxr';
	} elseif ( 'epub' === $file_extension && '._3.epub' === $pre_suffix ) {
		$file_class = 'epub3';
	} elseif ( 'pdf' === $file_extension && '._print.pdf' === $pre_suffix ) {
		$file_class = 'print-pdf';
	} else {
		/**
		 * Map custom export format file extensions to their CSS class.
		 *
		 * For example, here's how one might set the CSS class for a .docx file:
		 *
		 * add_filter( 'pb_get_export_file_class', function ( $file_extension ) {
		 * 	if ( 'docx' == $file_extension ) {
		 *		return 'word';
		 * 	}
		 *	return $file_extension;
		 * } );
		 *
		 * @since 3.9.8
		 *
		 * @param string $file_extension
		 */
		$file_class = apply_filters( 'pb_get_export_file_class', $file_extension );
	}
?>
	<form class="export-file" action="<?php echo $export_delete_url; ?>" method="post">
		<input type="hidden" name="filename" value="<?php echo $file; ?>" />
		<input type="hidden" name="delete_export_file" value="true" />
		<div class="export-file-container">
	<a class="export-file__link" href="<?php echo ( $download_url_prefix . $file ); ?>"><span class="export-file-icon <?php echo ( 0 == $c ? 'large' : 'small' ); ?> <?php echo $file_class; ?>" title="<?php echo esc_attr( $file ); ?>"></span></a>
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
	<?php endif; ?>
</div> <!-- .export-control -->

<div class="clear"></div>

</div>
<?php date_default_timezone_set( 'UTC' ); // Set back to UTC. @see wp-settings.php ?>
