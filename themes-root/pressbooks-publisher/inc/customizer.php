<?php
/**
 * Pressbooks Publisher Theme Customizer
 *
 * @package Pressbooks Publisher
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function pressbooks_publisher_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	
	$wp_customize->add_section( 
		'pressbooks_publisher_intro_box', 
		array(
    		'title'       => __('About Us', 'pressbooks'),
			'description' => __('Add a description of your collection or institution below.', 'pressbooks'),
			'priority'    => 35,
	)); 
	
	$wp_customize->add_setting(
	    'pressbooks_publisher_intro_textbox',
	    array(
	        'default'           => '',
			'sanitize_callback' => 'pressbooks_publisher_sanitize_text',	        
	    )
	);
	
	$wp_customize->add_control(
	    'pressbooks_publisher_intro_textbox',
	    array(
	        'label'   => __('About Us', 'pressbooks'),
	        'section' => 'pressbooks_publisher_intro_box',
	        'type'    => 'textarea',
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
	        'label'       => __('Columns', 'pressbooks'),
	        'description' => __('Display your intro text in one or two columns.', 'pressbooks'),
	        'section'     => 'pressbooks_publisher_intro_box',
	        'type'        => 'radio',
			'choices'     => array(
			                'one-column'   => __('One column', 'pressbooks'),
			                'two-column' => __('Two columns', 'pressbooks'),		            
			            ),	        
	    )
	);
	
}
add_action( 'customize_register', 'pressbooks_publisher_customize_register' );



/**
 * Sanitize header text box
 */
function pressbooks_publisher_sanitize_text( $input ) {
    return wp_kses_post( force_balance_tags( $input ) );
}


/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function pressbooks_publisher_customize_preview_js() {
	wp_enqueue_script( 'pressbooks_publisher_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20130508', true );
}
add_action( 'customize_preview_init', 'pressbooks_publisher_customize_preview_js' );
