<?php	if ( ! current_user_can( 'manage_network' ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

$nonce = ( @$_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
if ( !empty( $_POST ) ) {
  if ( !wp_verify_nonce( $nonce, 'pb_dashboard-options' ) ) {
      die( 'Security check' );
  } else {
    $options = get_site_option( 'pressbooks_dashboard_feed', [
  		'display_feed' => 1,
  		'url' => 'https://pressbooks.com/feed/',
  		'title' => 'Pressbooks News'
  	] );
    if ( @$_REQUEST['pressbooks_dashboard_feed']['display_feed'] == 1 ) {
      $options['display_feed'] = '1';
    } else {
      $options['display_feed'] = '0';
    }
    if ( isset( $_REQUEST['pressbooks_dashboard_feed']['url'] ) ) {
      $options['url'] = $_REQUEST['pressbooks_dashboard_feed']['url'];
    }
    if ( isset( $_REQUEST['pressbooks_dashboard_feed']['title'] ) ) {
      $options['title'] = $_REQUEST['pressbooks_dashboard_feed']['title'];
    }

    update_site_option( 'pressbooks_dashboard_feed', $options ); ?>
    <div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks-oauth' ); ?></strong></div>
  <?php }
} ?>

<div class="wrap">

	<h1><?php _e( 'Dashboard Settings', 'pressbooks' ); ?></h1>

	<p><?php _e( 'Customize your Pressbooks dashboard below.', 'pressbooks' ); ?></p>

	<form method="POST" action="">
		<?php settings_fields( 'pb_dashboard' );
		do_settings_sections( 'pb_dashboard' ); ?>
		<?php submit_button(); ?>
	</form>
</div>
