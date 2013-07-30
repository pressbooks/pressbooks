<div id="share">
			<div id="twitter" data-url="<?php the_permalink(); ?>" data-text="Check out this great book on PressBooks." data-title="Tweet"></div>
			<div id="facebook" data-url="<?php the_permalink(); ?>" data-text="Check out this great book on PressBooks." data-title="Like"></div>
			<div id="googleplus" data-url="<?php the_permalink(); ?>" data-text="Check out this great book on PressBooks." data-title="+1"></div>
			
			<?php if( !is_front_page()):?>
				<?php if ( @array_filter( get_option( 'pressbooks_ecommerce_links' ) ) ) : ?>
					<div class="sharrre" id="buy">
						<a class="share" href="<?php echo get_option('home'); ?>/buy"><span class="buy-icon"></span><?php _e('Buy', 'pressbooks'); ?></a>
					</div>
				<?php endif; ?>
			<?php endif; ?>
</div>	
