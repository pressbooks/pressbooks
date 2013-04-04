  <?php $options = get_option( 'publisherroot_theme_options' ); ?>

	<div id="social-icons">
		<?php if ( $options['twitterurl'] != '' ) : ?>
			<a href="<?php echo $options['twitterurl']; ?>" class="twitter" title="Twitter"><?php _e( 'Twitter', 'pressbooks' ); ?></a>
		<?php endif; ?>

		<?php if ( $options['facebookurl'] != '' ) : ?>
			<a href="<?php echo $options['facebookurl']; ?>" class="facebook" title="Facebook"><?php _e( 'Facebook', 'pressbooks' ); ?></a>
		<?php endif; ?>
		
		<?php if ( $options['youtubeurl'] != '' ) : ?>
			<a href="<?php echo $options['youtubeurl']; ?>" class="youtube" title="Youtube"><?php _e( 'YouTube', 'pressbooks' ); ?></a>
		<?php endif; ?>
		
		<?php if ( $options['tumblrurl'] != '' ) : ?>
			<a href="<?php echo $options['tumblrurl']; ?>" class="tumblr" title="Tumblr"><?php _e( 'Tumblr', 'pressbooks' ); ?></a>
		<?php endif; ?>

		<?php if ( ! $options['hiderss'] ) : ?>
			<a href="<?php bloginfo( 'rss2_url' ); ?>" class="rss" title="RSS"><?php _e( 'RSS Feed', 'pressbooks' ); ?></a>
		<?php endif; ?>
	</div><!-- #social-icons-->