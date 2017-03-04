<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme = wp_get_theme();
$locked = \Pressbooks\Modules\ThemeLock\ThemeLock::isLocked();
if ( $locked ) {
	$data = \Pressbooks\Modules\ThemeLock\ThemeLock::getLockData();
} ?>

<div class="wrap">
<h1><?php _e( 'Theme Lock', 'pressbooks' ); ?></h1>
<p><?php _e( 'In order to prevent changes to your book&rsquo;s page count when themes are updated, you can lock your theme&rsquo;s stylesheets at their current version.', 'pressbooks' ); ?></p>
<p>
	<strong>
		<?php printf(
			__( 'Your current theme, %1$s, %2$s.', 'pressbooks' ),
			$theme->get( 'Name' ),
			( $locked ) ? __( 'was locked in its current state on', 'pressbooks' ) . ' ' . strftime( '%x', $data['timestamp'] ) . ' @ ' . strftime( '%X', $data['timestamp'] ) : __( 'is unlocked', 'pressbooks' )
		); ?>
	</strong>
</p>

<p>
	<span class="ajax-loading list-ajax-loading spinner"></span>
	<button class="button <?php echo ( $locked ) ? 'unlock' : 'lock'; ?>">
		<?php echo ( $locked ) ? __( 'Unlock Theme', 'pressbooks' ) : __( 'Lock Theme', 'pressbooks' ); ?>
		<?php /* <span class="dashicons dashicons-<?php echo ( ! $locked ) ? 'unlock' : 'lock'; ?>"></span> */ ?>
	</button>
</p>
</div>
