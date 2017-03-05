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
<p><?php _e( 'In order to prevent any changes to your book&rsquo;s appearance and page count when themes are updated, you can lock your theme at its current version.', 'pressbooks' ); ?></p>
<p class="status">
	<strong>
<?php if ( $locked ) {
	printf(
		__( 'Your book&rsquo;s theme, %1$s, was locked in its current state on %2$s at %3$s.', 'pressbooks' ),
		$data['name'],
		strftime( '%x', $data['timestamp'] ),
		strftime( '%X', $data['timestamp'] )
	);
} else {
	printf(
		__( 'Your book&rsquo;s theme, %s, is unlocked.', 'pressbooks' ),
		$theme->get( 'Name' )
	);
} ?>
	</strong>
</p>

<p>
	<span class="ajax-loading list-ajax-loading spinner"></span>
	<button class="button <?php echo ( $locked ) ? 'unlock' : 'lock'; ?>">
		<?php echo ( $locked ) ? __( 'Unlock Theme', 'pressbooks' ) : __( 'Lock Theme', 'pressbooks' ); ?>
	</button>
</p>
</div>
