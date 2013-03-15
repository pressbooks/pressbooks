<?php

add_action( 'admin_init', 'publisherroot_options_init' );
add_action( 'admin_menu', 'publisherroot_options_add_page' );

/**
 * Init plugin options to white list our options
 */
function publisherroot_options_init(){
	register_setting( 'publisherroot_options', 'publisherroot_theme_options', 'publisherroot_options_validate' );
}

/**
 * Load up the menu page
 */
function publisherroot_options_add_page() {
	add_theme_page( __( 'Theme Options', 'pressbooks' ), __( 'Theme Options', 'pressbooks' ), 'edit_theme_options', 'theme_options', 'publisherroot_options_do_page' );
}

/**
 * Create the options page
 */
function publisherroot_options_do_page() {

	if ( ! isset( $_REQUEST['settings-updated'] ) )
		$_REQUEST['settings-updated'] = false;
	?>
	<div class="wrap">
		<?php screen_icon(); echo "<h2>" . __( ' Theme Options', 'pressbooks' ) . "</h2>"; ?>
		<p><?php _e( 'These options will let you setup the social icons for your site. You can enter the URLs of your profiles to have the icons show up.', 'pressbooks' ); ?></p>
		<?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
		<div class="updated fade"><p><strong><?php _e( 'Options saved', 'pressbooks' ); ?></strong></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'publisherroot_options' ); ?>
			<?php $options = get_option( 'publisherroot_theme_options' ); ?>

			<table class="form-table">
	

				<?php
				/**
				 * RSS Icon
				 */
				?>
				<tr valign="top"><th scope="row"><?php _e( 'Hide RSS Icon?', 'pressbooks' ); ?></th>
					<td>
						<input id="publisherroot_theme_options[hiderss]" name="publisherroot_theme_options[hiderss]" type="checkbox" value="1" <?php checked( '1', $options['hiderss'] ); ?> />
						<label class="description" for="publisherroot_theme_options[hiderss]"><?php _e( 'Hide the RSS feed icon?', 'pressbooks' ); ?></label>
					</td>
				</tr>

				<?php
				/**
				 * Facebook Icon
				 */
				?>
				<tr valign="top"><th scope="row"><?php _e( 'Enter your Facebook URL', 'pressbooks' ); ?></th>
					<td>
						<input id="publisherroot_theme_options[facebookurl]" class="regular-text" type="text" name="publisherroot_theme_options[facebookurl]" value="<?php esc_attr_e( $options['facebookurl'], 'pressbooks' ); ?>" />
						<label class="description" for="publisherroot_theme_options[facebookurl]"><?php _e( 'Leave blank to hide Facebook Icon', 'pressbooks' ); ?></label>
					</td>
				</tr>
				
				<?php
				/**
				 * Twitter URL
				 */
				?>
				<tr valign="top"><th scope="row"><?php _e( 'Enter your Twitter URL', 'pressbooks' ); ?></th>
					<td>
						<input id="publisherroot_theme_options[twitterurl]" class="regular-text" type="text" name="publisherroot_theme_options[twitterurl]" value="<?php esc_attr_e( $options['twitterurl'], 'pressbooks' ); ?>" />
						<label class="description" for="publisherroot_theme_options[twitterurl]"><?php _e( 'Leave blank to hide Twitter Icon', 'pressbooks' ); ?></label>
					</td>
				</tr>
				
				<?php
				/**
				 * Tumblr
				 */
				?>
				<tr valign="top"><th scope="row"><?php _e( 'Enter your Tumblr URL', 'pressbooks' ); ?></th>
					<td>
						<input id="publisherroot_theme_options[tumblrurl]" class="regular-text" type="text" name="publisherroot_theme_options[tumblrurl]" value="<?php esc_attr_e( $options['tumblrurl'], 'pressbooks' ); ?>" />
						<label class="description" for="publisherroot_theme_options[tumblrurl]"><?php _e( 'Leave blank to hide Tumblr Icon', 'pressbooks' ); ?></label>
					</td>
				</tr>
				
				<?php
				/**
				 * Youtube
				 */
				?>
				<tr valign="top"><th scope="row"><?php _e( 'Enter your Youtube Channel URL', 'pressbooks' ); ?></th>
					<td>
						<input id="publisherroot_theme_options[youtubeurl]" class="regular-text" type="text" name="publisherroot_theme_options[youtubeurl]" value="<?php esc_attr_e( $options['youtubeurl'], 'pressbooks' ); ?>" />
						<label class="description" for="publisherroot_theme_options[youtubeurl]"><?php _e( 'Leave blank to hide Youtube Icon', 'pressbooks' ); ?></label>
					</td>
				</tr>
				
			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'pressbooks' ); ?>" />
			</p>
		</form>
	</div>
	<?php
}


/**
 * Sanitize and validate input. Accepts an array, return a sanitized array.
 */
function publisherroot_options_validate( $input ) {

	// Our checkbox value is either 0 or 1
	if ( ! isset( $input['hiderss'] ) )
		$input['hiderss'] = null;
		$input['hiderss'] = ( $input['hiderss'] == 1 ? 1 : 0 );

	// Our text option must be safe text with no HTML tags
	$input['twitterurl'] = wp_filter_nohtml_kses( $input['twitterurl'] );
	$input['facebookurl'] = wp_filter_nohtml_kses( $input['facebookurl'] );
	$input['youtubeurl'] = wp_filter_nohtml_kses( $input['youtubeurl'] );
	$input['tumblrurl'] = wp_filter_nohtml_kses( $input['tumblrurl'] );
	
	// Encode URLs
	$input['twitterurl'] = esc_url_raw( $input['twitterurl'] );
	$input['facebookurl'] = esc_url_raw( $input['facebookurl'] );
	$input['youtubeurl'] = esc_url_raw( $input['youtubeurl'] );
	$input['tumblrurl'] = esc_url_raw( $input['tumblrurl'] );
	
	return $input;
}