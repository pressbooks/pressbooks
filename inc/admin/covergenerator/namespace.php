<?php

namespace Pressbooks\Admin\Covergenerator;

// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash

use PressbooksMix\Assets;

/**
 * Add menu to pb_export
 */
function generator_menu() {
	add_submenu_page( 'pb_export', __( 'Cover Generator', 'pressbooks' ), __( 'Cover Generator', 'pressbooks' ), 'manage_options', 'pressbooks_cg', __NAMESPACE__ . '\display_generator' );
}

/**
 *
 */
function display_generator() {
	require( PB_PLUGIN_DIR . 'templates/admin/covergenerator.php' );
}

/**
 * @param string $hooks_suffix
 */
function generator_css_js( $hooks_suffix ) {
	if ( $hooks_suffix === get_plugin_page_hookname( 'pressbooks_cg', 'pb_export' ) ) {
		$assets = new Assets( 'pressbooks', 'plugin' );
		wp_enqueue_media();
		wp_enqueue_style( 'cg/css', $assets->getPath( 'styles/covergenerator.css' ), [ 'wp-color-picker' ], null );
		wp_enqueue_script(
			'cg/js', $assets->getPath( 'scripts/covergenerator.js' ), [
				'jquery',
				'jquery-form',
				'wp-color-picker',
				'eventsource-polyfill',
			], null
		);
		wp_localize_script(
			'cg/js', 'PB_CoverGeneratorToken', [
				'ajaxSubmitMsg' => __( 'Saving settings', 'pressbooks' ),
				'ajaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=cover-generator' ), 'pb-generate-cover' ),
				'redirectUrl' => admin_url( 'admin.php?page=pressbooks_cg' ),
				'unloadWarning' => __( 'Cover generation is not done. Leaving this page, now, will cause problems. Are you sure?', 'pressbooks' ),
				'reloadSnippet' => '<em>(<a href="javascript:window.location.reload(true)">' . __( 'Reload', 'pressbooks' ) . '</a>)</em>',
			]
		);
		wp_deregister_script( 'heartbeat' );
	}
}

function cg_options_init() {

	$_page = 'pressbooks_cg';
	$_option = 'pressbooks_cg_options';
	$defaults = []; // TODO

	if ( empty( get_option( $_option ) ) ) {
		add_option( $_option, $defaults );
	}

	add_settings_section(
		'pressbooks_cg_text',
		__( 'Text', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_text_callback',
		$_page
	);

	add_settings_field(
		'pb_title',
		__( 'Title', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_title_callback',
		$_page,
		'pressbooks_cg_text',
		[
			'label_for' => 'pb_title',
		]
	);

	add_settings_field(
		'pb_title_spine',
		__( 'Title (Spine)', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_title_spine_callback',
		$_page,
		'pressbooks_cg_text',
		[
			'label_for' => 'pb_title_spine',
		]
	);

	add_settings_field(
		'pb_subtitle',
		__( 'Subtitle', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_subtitle_callback',
		$_page,
		'pressbooks_cg_text',
		[
			'label_for' => 'pb_subtitle',
		]
	);

	add_settings_field(
		'pb_author',
		__( 'Author', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_author_callback',
		$_page,
		'pressbooks_cg_text',
		[
			'label_for' => 'pb_author',
		]
	);

	add_settings_field(
		'pb_author_spine',
		__( 'Author (Spine)', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_author_spine_callback',
		$_page,
		'pressbooks_cg_text',
		[
			'label_for' => 'pb_author_spine',
		]
	);

	add_settings_field(
		'pb_about_unlimited',
		__( 'About', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_about_callback',
		$_page,
		'pressbooks_cg_text',
		[
			'label_for' => 'pb_about_unlimited',
		]
	);

	add_settings_field(
		'pb_print_isbn',
		__( 'ISBN', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_isbn_callback',
		$_page,
		'pressbooks_cg_text',
		[
			__( 'If you have an ISBN, this will generate a barcode on the back of your print cover. If you do not have an ISBN, you should leave this field blank.', 'pressbooks' ),
			'label_for' => 'pb_print_isbn',
		]
	);

	add_settings_field(
		'pb_print_sku',
		__( 'or SKU', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_sku_callback',
		$_page,
		'pressbooks_cg_text',
		[
			__( 'If you have a 13-digit identifier that is not an ISBN, entering it in this field will generate a barcode with that number on the back of your print cover. If you do not have a non-ISBN identifier, you should leave this field blank.', 'pressbooks' ),
			'label_for' => 'pb_print_sku',
		]
	);

	add_settings_section(
		'pressbooks_cg_design',
		__( 'Design', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_design_callback',
		$_page
	);

	add_settings_field(
		'front_background_image',
		__( 'Front Cover Background Image', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_front_background_image_callback',
		$_page,
		'pressbooks_cg_design'
	);

	add_settings_field(
		'text_transform',
		__( 'Book Title Case', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_text_transform_callback',
		$_page,
		'pressbooks_cg_design',
		[
			'uppercase' => 'UPPERCASE',
			'titlecase' => 'Title Case',
			'label_for' => 'text_transform',
		]
	);

	add_settings_section(
		'pressbooks_cg_spine_size',
		__( 'Spine Size', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_spine_size_callback',
		$_page
	);

	add_settings_field(
		'pdf_pagecount',
		__( 'Page Count', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_pdf_pagecount_callback',
		$_page,
		'pressbooks_cg_spine_size',
		[
			'label_for' => 'pdf_pagecount',
		]
	);

	add_settings_field(
		'ppi',
		__( 'Paper Type', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_ppi_callback',
		$_page,
		'pressbooks_cg_spine_size',
		[
			'444' => 'Black & white interior, white paper',
			'400' => 'Black & white interior, creme paper',
			'426' => 'Color interior',
			'label_for' => 'ppi',
		]
	);

	add_settings_field(
		'custom_ppi',
		__( 'Custom PPI', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_custom_ppi_callback',
		$_page,
		'pressbooks_cg_spine_size',
		[
			'label_for' => 'custom_ppi',
		]
	);

	add_settings_section(
		'pressbooks_cg_colors',
		__( 'Text and Background Colors', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_colors_callback',
		$_page
	);

	add_settings_field(
		'front_cover_text',
		__( 'Front Cover Text', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_color_callback',
		$_page,
		'pressbooks_cg_colors',
		[
			'front_cover_text',
		]
	);

	add_settings_field(
		'front_cover_background',
		__( 'Front Cover Background', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_color_callback',
		$_page,
		'pressbooks_cg_colors',
		[
			'front_cover_background',
		]
	);

	add_settings_field(
		'spine_text',
		__( 'Spine Text', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_color_callback',
		$_page,
		'pressbooks_cg_colors',
		[
			'spine_text',
		]
	);

	add_settings_field(
		'spine_background',
		__( 'Spine Background', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_color_callback',
		$_page,
		'pressbooks_cg_colors',
		[
			'spine_background',
		]
	);

	add_settings_field(
		'back_cover_text',
		__( 'Back Cover Text', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_color_callback',
		$_page,
		'pressbooks_cg_colors',
		[
			'back_cover_text',
		]
	);

	add_settings_field(
		'back_cover_background',
		__( 'Back Cover Background', 'pressbooks' ),
		__NAMESPACE__ . '\pressbooks_cg_color_callback',
		$_page,
		'pressbooks_cg_colors',
		[
			'back_cover_background',
		]
	);

	register_setting(
		$_page,
		$_option,
		__NAMESPACE__ . '\pressbooks_cg_options_sanitize'
	);
}

// ----------------------------------------------------------------------------
// Callbacks
// ----------------------------------------------------------------------------

function pressbooks_cg_text_callback() {
	?>
	<p>
	<?php
	$post = ( new \Pressbooks\Metadata() )->getMetaPost();
	if ( $post ) {
		$post = get_edit_post_link( $post->ID );
	} else {
		$post = admin_url( 'post-new.php?post_type=metadata' );
	}
		/* translators: %s: URL to Book Information page */
		printf( __( 'The text below is pulled from your <a href="%s">Book Information</a> page, and is used to generate your cover(s). You can reformat the text below as needed. (<strong>IMPORTANT NOTE:</strong> Changes you make here will <strong>NOT</strong> be reflected on the Book Information page.)', 'pressbooks' ), $post );
	?>
		</p>
	<?php
}

function pressbooks_cg_title_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_title'] ) ) {
		$option['pb_title'] = $metadata['pb_title'];
	}

	$html = '<textarea id="pb_title" name="pressbooks_cg_options[pb_title]" rows="5" cols="30">' . $option['pb_title'] . '</textarea>';
	echo $html;
}

function pressbooks_cg_title_spine_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_title_spine'] ) ) {
		$option['pb_title_spine'] = $metadata['pb_title'];
	}

	$html = '<input id="pb_title_spine" name="pressbooks_cg_options[pb_title_spine]" type="text" value="' . $option['pb_title_spine'] . '" />';
	echo $html;
}

function pressbooks_cg_subtitle_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_subtitle'] ) ) {
		$option['pb_subtitle'] = ! empty( $metadata['pb_subtitle'] ) ? $metadata['pb_subtitle'] : '';
	}

	$html = '<textarea id="pb_subtitle" name="pressbooks_cg_options[pb_subtitle]" rows="5" cols="30">' . $option['pb_subtitle'] . '</textarea>';
	echo $html;
}

function pressbooks_cg_author_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_author'] ) ) {
		$option['pb_author'] = $metadata['pb_authors'];
	}

	$html = '<textarea id="pb_author" name="pressbooks_cg_options[pb_author]" rows="5" cols="30">' . $option['pb_author'] . '</textarea>';
	echo $html;
}

function pressbooks_cg_author_spine_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_author_spine'] ) ) {
		$option['pb_author_spine'] = $metadata['pb_authors'];
	}

	$html = '<input id="pb_author_spine" name="pressbooks_cg_options[pb_author_spine]" type="text" value="' . $option['pb_author_spine'] . '" />';
	echo $html;
}

function pressbooks_cg_about_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_about_unlimited'] ) ) {
		$option['pb_about_unlimited'] = ! empty( $metadata['pb_about_unlimited'] ) ? $metadata['pb_about_unlimited'] : '';
	}

	wp_editor(
		$option['pb_about_unlimited'], 'pb_about_unlimited', [
			'media_buttons' => false,
			'textarea_name' => 'pressbooks_cg_options[pb_about_unlimited]',
		]
	);
}

function pressbooks_cg_isbn_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_print_isbn'] ) ) {
		$option['pb_print_isbn'] = ! empty( $metadata['pb_print_isbn'] ) ? $metadata['pb_print_isbn'] : '';
	}

	$html = '<input id="pb_print_isbn" name="pressbooks_cg_options[pb_print_isbn]" type="text" value="' . $option['pb_print_isbn'] . '" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

function pressbooks_cg_sku_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$metadata = \Pressbooks\Book::getBookInformation();
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['pb_print_sku'] ) ) {
		$option['pb_print_sku'] = ! empty( $metadata['pb_print_sku'] ) ? $metadata['pb_print_sku'] : '';
	}

	$html = '<input id="pb_print_sku" name="pressbooks_cg_options[pb_print_sku]" type="text" value="' . $option['pb_print_sku'] . '" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

function pressbooks_cg_design_callback() {
	?>
	<p>
	<?php
		/* translators: %s: URL to Appearance menu */
		printf( __( 'Below you can make small adjustments to the design of your cover. The look and feel of the cover will echo the theme you have chosen for your book. You can change your book theme in the <a href="%s">Appearance</a> menu.', 'pressbooks' ), admin_url( 'themes.php' ) );
	?>
		</p>
	<p>
	<?php
		$options = get_option( 'pressbooks_theme_options_pdf' );
		/* translators: 1: pdf page width, 2: pdf page height, 3: URL to Theme Options page */
		printf( __( 'Your PDF page size is set to %1$s &times; %2$s, and your PDF cover will be generated with the same dimensions. You can change the PDF page size in the <a href="%3$s">Theme Options</a> menu.', 'pressbooks' ), $options['pdf_page_width'], $options['pdf_page_height'], admin_url( 'themes.php?page=pressbooks_theme_options&tab=pdf' ) );
	?>
		</p>
	<div class="theme">
		<div class="theme-screenshot">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/screenshot.png" alt="">
		</div>
		<h3 class="theme-name"><?php echo wp_get_theme(); ?></h3>
		<div class="theme-actions">
			<a class="button button-primary" href="<?php echo get_admin_url( get_current_blog_id(), '/themes.php' ); ?>"><?php _e( 'Change Theme', 'pressbooks' ); ?></a>
			<a class="button button-secondary" href="<?php echo get_admin_url( get_current_blog_id(), '/themes.php?page=pressbooks_theme_options' ); ?>"><?php _e( 'Options', 'pressbooks' ); ?></a>
		</div>
	</div>
	<p><?php _e( 'You can upload a background image here.', 'pressbooks' ); ?></p>
	<?php
}

function pressbooks_cg_front_background_image_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$option = get_option( 'pressbooks_cg_options' );
	$pdf_options = get_option( 'pressbooks_theme_options_pdf' );

	if ( strpos( $pdf_options['pdf_page_width'], 'in' ) ) {
		$width = ( str_replace( 'in', '', $pdf_options['pdf_page_width'] ) + 0.125 ) * 300;
		$numeric_width = str_replace( 'in', '', $pdf_options['pdf_page_width'] ) + 0.125;
	} elseif ( strpos( $pdf_options['pdf_page_width'], 'cm' ) ) {
		$width = ( ( str_replace( 'cm', '', $pdf_options['pdf_page_width'] ) * 0.3937007874 ) + 0.125 ) * 300;
		$numeric_width = str_replace( 'cm', '', $pdf_options['pdf_page_width'] ) + 0.3175;
	} else {
		$width = 0;
		$numeric_width = 0;
	}

	if ( strpos( $pdf_options['pdf_page_height'], 'in' ) ) {
		$height = ( str_replace( 'in', '', $pdf_options['pdf_page_height'] ) + 0.25 ) * 300;
		$numeric_height = str_replace( 'in', '', $pdf_options['pdf_page_height'] ) + 0.25;
	} elseif ( strpos( $pdf_options['pdf_page_height'], 'cm' ) ) {
		$height = ( ( str_replace( 'cm', '', $pdf_options['pdf_page_height'] ) * 0.3937007874 ) + 0.25 ) * 300;
		$numeric_height = str_replace( 'cm', '', $pdf_options['pdf_page_height'] ) + 0.635;
	} else {
		$height = 0;
		$numeric_height = 0;
	}

	$html = '<input id="front_background_image" name="pressbooks_cg_options[front_background_image]" type="hidden" value="' . ( isset( $option['front_background_image'] ) ? $option['front_background_image'] : '' ) . '" />';
	$html .= '<button class="button front-background-image-upload-button' . ( isset( $option['front_background_image'] ) ? ' hidden' : '' ) . '">Upload Image</button>';
	/* translators: 1: minimum width, 2: minimum height, 3: aspect ratio width, 4: aspect ratio height */
	$html .= '<p class="description front-background-image-description' . ( isset( $option['front_background_image'] ) ? ' hidden' : '' ) . '">' . sprintf( __( 'Your image must be at least %1$s pixels in width by %2$s pixels in height, with an aspect ratio of %3$s to %4$s.', 'pressbooks' ), round( $width ), round( $height ), $numeric_width, $numeric_height ) . '</p>';
	$html .= '<div class="front-background-image-preview-wrap' . ( isset( $option['front_background_image'] ) ? '' : ' hidden' ) . '">';
	$html .= '<p><img class="front-background-image" alt="Front Cover Background Image" src="' . ( isset( $option['front_background_image'] ) ? $option['front_background_image'] : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' ) . '" /></p>'; // Fallback to Tiny 26 bytes GIF
	$html .= '<p><input type="button" class="button button-primary delete-front-background-image" value="' . __( 'Delete Background Image', 'pressbooks' ) . '" /></p>';
	$html .= '</div>';
	echo $html;
}

function pressbooks_cg_text_transform_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['text_transform'] ) ) {
		$option['text_transform'] = 'uppercase';
	}

	$html = "<select name='pressbooks_cg_options[text_transform]' id='text_transform' >";
	foreach ( $args as $key => $val ) {
		$html .= "<option value='" . $key . "' " . selected( $key, $option['text_transform'], false ) . ">$val</option>";
	}
	$html .= '</select>';
	echo $html;
}

function pressbooks_cg_spine_size_callback() {
	?>
	<p><?php _e( 'Spine size is calculated based on the number of pages in your book, and the weight of the paper used in printing.', 'pressbooks' ); ?></p>
	<p>
	<?php
	$spine = new \Pressbooks\Covergenerator\Spine;
		$pages = $spine->countPagesInMostRecentPdf();
	if ( ! $pages ) {
		_e( 'You haven\'t exported any PDF copies of your book, so you will need to enter a page count below.', 'pressbooks' );
	} else {
		/* translators: %s: number of pages in pdf */
		printf( __( 'The last PDF you exported has %s pages (you can edit this below if you like).', 'pressbooks' ), $pages );
	}
	?>
	</p>
	<p><?php _e( 'We can calculate the spine size based on CreateSpace and Ingram specifications, or you can enter your own custom pages per inch (PPI) defined by your printer.', 'pressbooks' ); ?></p>
	<?php
}

function pressbooks_cg_pdf_pagecount_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$option = get_option( 'pressbooks_cg_options' );
	$spine = new \Pressbooks\Covergenerator\Spine;
	$pages = $spine->countPagesInMostRecentPdf();

	if ( empty( $option['pdf_pagecount'] ) ) {
		$option['pdf_pagecount'] = $pages;
	}

	$html = '<input id="pdf_pagecount" name="pressbooks_cg_options[pdf_pagecount]" type="text" value="' . $option['pdf_pagecount'] . '" />';
	echo $html;
}

function pressbooks_cg_ppi_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$option = get_option( 'pressbooks_cg_options' );
	if ( empty( $option['ppi'] ) ) {
		$option['ppi'] = '444';
	}

	$html = "<select name='pressbooks_cg_options[ppi]' id='ppi' >";
	foreach ( $args as $key => $val ) {
		$html .= "<option value='" . $key . "' " . selected( $key, $option['ppi'], false ) . ">$val</option>";
	}
	$custom = '';
	if ( ! array_key_exists( $option['ppi'], $args ) ) {
		$custom .= ' selected';
	}
	$html .= "<option value=''" . $custom . '>' . __( 'Custom&hellip;', 'pressbooks' ) . '</option>';
	$html .= '</select>';
	echo $html;
}

function pressbooks_cg_custom_ppi_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$option = get_option( 'pressbooks_cg_options' );

	if ( empty( $option['ppi'] ) ) {
		$option['ppi'] = '444';
	}
	$html = '<input id="custom_ppi" name="pressbooks_cg_options[ppi]" type="text" value="' . $option['ppi'] . '" />';
	echo $html;
}

function pressbooks_cg_colors_callback() {
	?>
	<p><?php _e( 'Choose text color and background colors below.', 'pressbooks' ); ?></p>
	<?php
}

function pressbooks_cg_color_callback( $args ) {
	unset( $args['label_for'], $args['class'] );
	$option = get_option( 'pressbooks_cg_options' );
	$val = ! empty( $option[ $args[0] ] ) ? $option[ $args[0] ] : '';
	$html = '<input class="colorpicker" id="' . $args[0] . '" name="pressbooks_cg_options[' . $args[0] . ']" value="' . $val . '" />';
	echo $html;
}

// ----------------------------------------------------------------------------
// Sanitation & Validation
// ----------------------------------------------------------------------------

function pressbooks_cg_options_sanitize( $input ) {
	$metadata = \Pressbooks\Book::getBookInformation();

	if ( empty( $input['pb_title'] ) ) {
		$input['pb_title'] = wp_kses_post( $metadata['pb_title'] );
	} else {
		$input['pb_title'] = wp_kses_post( $input['pb_title'] );
	}

	if ( empty( $input['pb_title_spine'] ) ) {
		$input['pb_title_spine'] = wp_kses_post( $metadata['pb_title'] );
	} else {
		$input['pb_title_spine'] = wp_kses_post( $input['pb_title_spine'] );
	}

	if ( empty( $input['pb_subtitle'] ) ) {
		if ( ! empty( $metadata['pb_subtitle'] ) ) {
			$input['pb_subtitle'] = wp_kses_post( $metadata['pb_subtitle'] );
		} else {
			unset( $input['pb_subtitle'] );
		}
	} else {
		$input['pb_subtitle'] = wp_kses_post( $input['pb_subtitle'] );
	}

	if ( empty( $input['pb_author'] ) ) {
		$input['pb_author'] = wp_kses_post( $metadata['pb_authors'] );
	} else {
		$input['pb_author'] = wp_kses_post( $input['pb_author'] );
	}

	if ( empty( $input['pb_author_spine'] ) ) {
		$input['pb_author_spine'] = wp_kses_post( $metadata['pb_authors'] );
	} else {
		$input['pb_author_spine'] = wp_kses_post( $input['pb_author_spine'] );
	}

	if ( empty( $input['pb_about_unlimited'] ) ) {
		$input['pb_about_unlimited'] = ! empty( $metadata['pb_about_unlimited'] ) ? $metadata['pb_about_unlimited'] : '';
	} else {
		$input['pb_about_unlimited'] = $input['pb_about_unlimited']; // TODO: Not sanitized, is this on purpose?
	}

	if ( empty( $input['pb_print_isbn'] ) ) {
		if ( ! empty( $metadata['pb_print_isbn'] ) ) {
			$input['pb_print_isbn'] = sanitize_text_field( $metadata['pb_print_isbn'] );
		} else {
			unset( $input['pb_print_isbn'] );
		}
	}

	if ( empty( $input['pb_print_sku'] ) ) {
		if ( ! empty( $metadata['pb_print_sku'] ) ) {
			$input['pb_print_sku'] = sanitize_text_field( $metadata['pb_print_sku'] );
		} else {
			unset( $input['pb_print_sku'] );
		}
	}

	if ( empty( $input['front_background_image'] ) ) {
		unset( $input['front_background_image'] );
	} else {
		$input['front_background_image'] = esc_url( $input['front_background_image'] );
	}

	if ( empty( $input['front_cover_text'] ) ) {
		unset( $input['front_cover_text'] );
	} else {
		$input['front_cover_text'] = sanitize_text_field( $input['front_cover_text'] );
	}

	if ( empty( $input['front_cover_background'] ) ) {
		unset( $input['front_cover_background'] );
	} else {
		$input['front_cover_background'] = sanitize_text_field( $input['front_cover_background'] );
	}

	if ( empty( $input['spine_text'] ) ) {
		unset( $input['spine_text'] );
	} else {
		$input['spine_text'] = sanitize_text_field( $input['spine_text'] );
	}

	if ( empty( $input['spine_background'] ) ) {
		unset( $input['spine_background'] );
	} else {
		$input['spine_background'] = sanitize_text_field( $input['spine_background'] );
	}

	if ( empty( $input['back_cover_text'] ) ) {
		unset( $input['back_cover_text'] );
	} else {
		$input['back_cover_text'] = sanitize_text_field( $input['back_cover_text'] );
	}

	if ( empty( $input['back_cover_background'] ) ) {
		unset( $input['back_cover_background'] );
	} else {
		$input['back_cover_background'] = sanitize_text_field( $input['back_cover_background'] );
	}

	return $input;
}

/**
 * Validate image size.
 *
 * @param array $file
 *
 * @return array
 */
function validate_image_size( $file ) {
	if ( 'page=pressbooks_cg' !== wp_parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_QUERY ) ) {
		return $file;
	}

	$pdf_options = get_option( 'pressbooks_theme_options_pdf' );

	if ( strpos( $pdf_options['pdf_page_width'], 'in' ) ) {
		$width = ( str_replace( 'in', '', $pdf_options['pdf_page_width'] ) + 0.125 ) * 300;
		$numeric_width = str_replace( 'in', '', $pdf_options['pdf_page_width'] ) + 0.125;
	} elseif ( strpos( $pdf_options['pdf_page_width'], 'cm' ) ) {
		$width = ( ( str_replace( 'cm', '', $pdf_options['pdf_page_width'] ) * 0.3937007874 ) + 0.125 ) * 300;
		$numeric_width = str_replace( 'cm', '', $pdf_options['pdf_page_width'] ) + 0.3175;
	} else {
		$width = 0;
		$numeric_width = 0;
	}

	if ( strpos( $pdf_options['pdf_page_height'], 'in' ) ) {
		$height = ( str_replace( 'in', '', $pdf_options['pdf_page_height'] ) + 0.25 ) * 300;
		$numeric_height = str_replace( 'in', '', $pdf_options['pdf_page_height'] ) + 0.25;
	} elseif ( strpos( $pdf_options['pdf_page_height'], 'cm' ) ) {
		$height = ( ( str_replace( 'cm', '', $pdf_options['pdf_page_height'] ) * 0.3937007874 ) + 0.25 ) * 300;
		$numeric_height = str_replace( 'cm', '', $pdf_options['pdf_page_height'] ) + 0.635;
	} else {
		$height = 0;
		$numeric_height = 0;
	}

	$image = getimagesize( $file['tmp_name'] );
	$minimum = [
		'width' => round( $width ),
		'height' => round( $height ),
	];
	$image_width = $image[0];
	$image_height = $image[1];

	/* translators: 1: minimum width, 2: minimum height, 3: image width 4: image height */
	$too_small_txt = __( 'Your image is too small. The image must be %1$d by %2$d pixels. Your image is %3$d by %4$d pixels.', 'pressbooks' );
	$too_small = sprintf(
		$too_small_txt,
		$minimum['width'],
		$minimum['height'],
		$image_width,
		$image_height
	);

	/* translators: 1: pdf page width, 2: pdf page height */
	$aspect_ratio_txt = __( 'Your image is the wrong aspect ratio. The image must have an aspect ratio of %1$s to %2$s.', 'pressbooks' );
	$aspect_ratio = sprintf(
		$aspect_ratio_txt,
		$numeric_width,
		$numeric_height
	);

	if ( $image_width < $minimum['width'] || $image_height < $minimum['height'] ) {
		$file['error'] = $too_small;

		return $file;
	} elseif ( $image_width === $minimum['width'] && $image_height === $minimum['height'] ) {
		return $file;
	} elseif ( round( $image_width / $image_height * 100 ) !== round( $numeric_width / $numeric_height * 100 ) ) {
		$file['error'] = $aspect_ratio;

		return $file;
	} else {
		return $file;
	}
}
