<!-- Share buttons -->
	<div class="share-wrap-single">
		<ul class="share share-single">
			<?php $_mailto = 'mailto:?subject=' . rawurlencode( __( 'I wanted to share this post with you from', 'pressbooks' ) . ' ' . get_bloginfo( 'name' ) ) . '&amp;body=' . rawurlencode( get_the_title() . ' - ' . get_permalink() ); ?>
			<li class="email"><a href="<?php echo $_mailto; ?>" title="<?php _e('Email to a friend', 'pressbooks'); ?>" target="_blank"><?php _e('Share via Email', 'pressbooks'); ?></a>
			</li>
			<li class="twitter"><a href="https://twitter.com/share" class="twitter-share-button" data-count="horizontal" data-width="97px">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></li>
			<li class="facebook"><div class="fb-like" data-send="false" data-layout="button_count" data-width="60" data-show-faces="false"></div></li>
									
		</ul>
	</div><!-- end .share-wrap-single -->