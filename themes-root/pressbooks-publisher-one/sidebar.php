				<div id="secondaryContent">

					
						<?php if (is_active_sidebar( 'sidebar_1')): ?>
						<div class="secondary-box">
				 	    	<?php dynamic_sidebar( 'sidebar_1' ); ?>
				 	    </div>	
				 	      <?php endif; ?>
					
					
					
						<?php if (is_active_sidebar( 'sidebar_2')): ?>
						<div class="secondary-box">
				 	    	<?php dynamic_sidebar( 'sidebar_2' ); ?>
				 	    </div>
				 	    <?php else: ?>
						<div class="secondary-box">
							<h3 class="widget-title">Connect with Us</h3>
							<?php get_template_part( 'widget', 'social-media' ); ?> 
				 	    </div>	
				 	    <?php endif; ?>
							      			 	
						<?php if (is_active_sidebar( 'sidebar_3')): ?>
						<div class="secondary-box">
				 	    	<?php dynamic_sidebar( 'sidebar_3' ); ?>
				 	    </div>
				 	    <?php else: ?>
						<div class="secondary-box">
							<h3 class="widget-title">Recent Books</h3>
								<?php get_template_part( 'widget', 'books' ); ?>
							<?php get_template_part( 'widget', 'authors' ); ?>
							<h3 class="widget-title">Recent Posts</h3>
							<ul>
								<?php $recent_posts = wp_get_recent_posts( array( 'numberposts' => 5 ) );
								foreach( $recent_posts as $recent ) {
									echo '<li><a href="' . get_permalink($recent["ID"]) . '" title="Look '.esc_attr($recent["post_title"]).'" >' .   $recent["post_title"].'</a> </li> ';
								} ?>
							</ul>
							<?php if ( !is_user_logged_in() ) { ?>
								<h3 class="widget-title">Login</h3>
								<p><a href="<?php if ( is_home() ) { echo wp_login_url( home_url() ); } else { echo wp_login_url( get_permalink() ); } ?>" title="Login">Login to <?php bloginfo('name'); ?></a></p>
							<?php } ?>
				 	    </div>	
				 	    <?php endif; ?>
				 	    	      			 	
					
				</div><!-- end #secondaryContent -->