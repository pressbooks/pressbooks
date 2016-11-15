<?php

namespace Roots\Sage\Customizer;

use Roots\Sage\Assets;

/**
 * Add postMessage support
 */
function customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
	$wp_customize->add_section(
		'pressbooks_publisher_intro_box',
		array(
			'title' => __( 'About Us', 'pressbooks' ),
			'description' => __( 'Add a description of your collection or institution below.', 'pressbooks' ),
			'priority' => 35,
		)
	);

	$wp_customize->add_setting(
		'pressbooks_publisher_intro_textbox',
		array(
			'default' => '',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_text',
		)
	);

	$wp_customize->add_control(
		'pressbooks_publisher_intro_textbox',
		array(
			'label' => __( 'About Us', 'pressbooks' ),
			'section' => 'pressbooks_publisher_intro_box',
			'type' => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'pressbooks_publisher_intro_text_col',
		array(
			'default' => 'one-column',
		)
	);

	$wp_customize->add_control(
		'pressbooks_publisher_intro_text_col',
		array(
			'label' => __( 'Columns', 'pressbooks' ),
			'description' => __( 'Display your intro text in one or two columns.', 'pressbooks' ),
			'section' => 'pressbooks_publisher_intro_box',
			'type' => 'radio',
			'choices' => array(
				'one-column' => __( 'One column', 'pressbooks' ),
				'two-column' => __( 'Two columns', 'pressbooks' ),
			),
		)
	);

	$wp_customize->remove_section( 'static_front_page' );
}

add_action( 'customize_register', __NAMESPACE__ . '\\customize_register' );

/**
* Sanitize callback
*/
function sanitize_text( $input ) {
	return wp_kses_post( force_balance_tags( $input ) );
}

/**
* Customizer JS
*/
function customize_preview_js() {
	wp_enqueue_script( 'pressbooks-publisher/customizer', Assets\asset_path( 'scripts/customizer.js' ), [ 'customize-preview' ], null, true );
}
add_action( 'customize_preview_init', __NAMESPACE__ . '\\customize_preview_js' );
