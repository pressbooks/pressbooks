<?php if( !is_single() ){?>

	</div><!-- #content -->

<?php } ?>
<?php if( !is_front_page() ){?>

	<?php get_sidebar(); ?>

	</div><!-- #wrap -->
	<div class="push"></div>

	</div><!-- .wrapper for sitting footer at the bottom of the page -->
<?php } ?>


<div class="footer">
	<div class="inner">
		<?php if ( get_option('blog_public' ) == '1' || is_user_logged_in() ): ?>
			<?php if ( is_page() || is_home() ): ?>

			<dl>
				<dt><?php _e( 'Book Name', 'pressbooks' ); ?>:</dt>
				<dd><?php bloginfo( 'name' ); ?></dd>
				<?php global $metakeys;
				$metadata = pb_get_book_information();
				foreach ( $metadata as $key => $val ) :
					if ( isset( $metakeys[ $key ] ) && ! empty( $val ) ) : ?>
						<dt><?php echo $metakeys[ $key ]; ?>:</dt>
						<dd><?php if ( 'pb_publication_date' == $key ) { $val = date_i18n( 'F j, Y', absint( $val ) ); }
						echo $val; ?></dd>
				<?php endif;
				endforeach; ?>
				<?php
				// Copyright
				echo '<dt>' . __( 'Copyright', 'pressbooks' ) . ':</dt><dd>';
				echo ( ! empty( $metadata['pb_copyright_year'] ) ) ? $metadata['pb_copyright_year'] : date( 'Y' );
				if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
					echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'];
				}
				echo "</dd>\n"; ?>

			</dl>
			<?php endif; ?>

			<?php echo pressbooks_copyright_license(); ?>

			<?php endif; ?>
			<p class="cie-name"><a href="https://pressbooks.com">Pressbooks: <?php _e('Simple Book Production', 'pressbooks'); ?></a></p>
	</div><!-- #inner -->
</div><!-- #footer -->
<?php wp_footer(); ?>
</body>
</html>
