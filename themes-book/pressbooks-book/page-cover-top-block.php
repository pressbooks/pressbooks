<section id="post-<?php the_ID(); ?>" <?php post_class( array( 'top-block', 'clearfix', 'home-post' ) ); ?>>

	<?php pb_get_links(false); ?>
	<?php $metadata = pb_get_book_information();?>
	<div class="log-wrap">	<!-- Login/Logout -->
	   <?php if (! is_single()): ?>
	    	<?php if (!is_user_logged_in()): ?>
				<a href="<?php echo wp_login_url( get_permalink() ); ?>" class=""><?php _e('login', 'pressbooks'); ?></a>
	   	 	<?php else: ?>
				<a href="<?php echo  wp_logout_url(); ?>" class=""><?php _e('logout', 'pressbooks'); ?></a>
				<?php if (is_super_admin() || is_user_member_of_blog()): ?>
				<a href="<?php echo get_option('home'); ?>/wp-admin"><?php _e('Admin', 'pressbooks'); ?></a>
				<?php endif; ?>
	    	<?php endif; ?>
	    <?php endif; ?>
	</div>
	<div class="right-block">
		<?php do_action( 'pb_cover_promo' ); ?>
	</div>

			<div class="book-info">
				<!-- Book Title -->
				<h1><a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>


				<?php if ( ! empty( $metadata['pb_author'] ) ): ?>
			     	<p class="book-author"><?php echo $metadata['pb_author']; ?></p>
			     	<span class="stroke"></span>
		     	<?php endif; ?>


				<?php if ( ! empty( $metadata['pb_about_140'] ) ) : ?>
					<p class="sub-title"><?php echo $metadata['pb_about_140']; ?></p>
					<span class="detail"></span>
				<?php endif; ?>

				<?php if ( ! empty( $metadata['pb_about_50'] ) ): ?>
					<p><?php echo pb_decode( $metadata['pb_about_50'] ); ?></p>
				<?php endif; ?>

			</div> <!-- end .book-info -->

				<?php if ( ! empty( $metadata['pb_cover_image'] ) ): ?>
				<div class="book-cover">

						<img src="<?php echo $metadata['pb_cover_image']; ?>" alt="book-cover" title="<?php bloginfo( 'name' ); ?> book cover" />

				</div>
				<?php endif; ?>

				<div class="call-to-action-wrap">
					<?php global $first_chapter; ?>
					<div class="call-to-action">
						<a class="btn red" href="<?php global $first_chapter; echo $first_chapter; ?>"><span class="read-icon"></span><?php _e('Read', 'pressbooks'); ?></a>

						<?php if ( @array_filter( get_option( 'pressbooks_ecommerce_links' ) ) ) : ?>
						 <!-- Buy -->
							 <a class="btn black" href="<?php echo get_option('home'); ?>/buy"><span class="buy-icon"></span><?php _e('Buy', 'pressbooks'); ?></a>
						 <?php endif; ?>


					</div> <!-- end .call-to-action -->
				</div><!--  end .call-to-action-wrap -->

				<?php
					$files = \PBT\Utility\latest_exports();
					$option = get_option( 'pressbooks_export_options', array( 'share_latest_export_files' => 0 ) );
					if ( ! empty( $files ) && ( true == $option['share_latest_export_files'] ) ) { ?>
						<div class="downloads">
							<h4><?php _e( 'Download in the following formats:', 'pressbooks' ); ?></h4>
							<?php foreach ( $files as $key => $value ) :
								$filename = preg_replace( '/(-\d{10})(.*)/ui', "$1", $value );
								$file_extension = substr( strrchr( $key, '.' ), 1 );

								switch ( $file_extension ) {
									case 'html':
										$file_class = 'xhtml';
										break;
									case 'xml':
										$pre_suffix = strcmp( $key, '._vanilla.xml' );
										$file_class = ( 0 === $pre_suffix) ? 'vanillawxr' : 'wxr';
										break;
									case 'epub':
										$pre_suffix = strcmp( $key, '._3.epub' );
										$file_class = ( 0 === $pre_suffix ) ? 'epub3' : 'epub';
										break;
									case 'pdf':
										$pre_suffix = strcmp( $key, '._oss.pdf' );
										$file_class = ( 0 === $pre_suffix ) ? 'mpdf' : 'pdf';
										break;
									default:
										$file_class = $file_extension;
										break;
								}
								// rewrite rule
								$url = "open/download?filename={$filename}&type={$file_class}";
								// for Google Analytics (classic), change to:
								// $tracking = "_gaq.push(['_trackEvent','exportFiles','Downloads','{$file_class}']);";
								// for Google Analytics (universal), change to:
								// $tracking = "ga('send','event','exportFiles','Downloads','{$file_class}');";
								// Piwik Analytics event tracking _paq.push('trackEvent', category, action, name)
								$tracking = "_paq.push(['trackEvent','exportFiles','Downloads','{$file_class}']);";
							?>
								<link itemprop="bookFormat" href="http://schema.org/EBook">
									<a rel="nofollow" onclick="<?= $tracking; ?>" itemprop="offers" itemscope itemtype="http://schema.org/Offer" href="<?= $url; ?>">
										<span class="export-file-icon small <?= $file_class; ?>" title="<?= esc_attr( $filename ); ?>"></span>
										<meta itemprop="price" content="$0.00">
										<link itemprop="availability" href="http://schema.org/InStock">
									</a>
							<?php endforeach; ?>
						</div>
					<?php }
				?>


	</section> <!-- end .top-block -->
