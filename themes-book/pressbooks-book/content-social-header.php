		<!-- Share buttons -->
		<div class="share-wrap">
			<div class="share-btn"><?php _e('Share', 'pressbooks'); ?></div>
			<ul class="share share-header">
				<li class="email">
				<a href="mailto:?subject=<?php echo rawurlencode( __('I wanted to share this post with you from', 'pressbooks') ); ?> <?php bloginfo('name'); ?>&body=<?php the_title(); ?> - <?php the_permalink(); ?>" title="<?php _e('Email to a friend', 'pressbooks'); ?>" target="_blank"><?php _e('Share via Email', 'pressbooks'); ?></a>
				</li>
				<li class="twitter"><a href="https://twitter.com/share" class="twitter-share-button" data-count="vertical">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></li>

				<li class="facebook"><div class="fb-like" data-send="false" data-layout="box_count" data-width="60" data-show-faces="false"></div></li>
	
			</ul>
		</div><!-- end .share-wrap -->