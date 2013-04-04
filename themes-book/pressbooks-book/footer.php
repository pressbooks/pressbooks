<?php if( !is_single() ){?>
	
	</div><!-- #content -->

<?php } ?>
<?php get_sidebar(); ?>
</div><!-- #wrap -->
<div class="push"></div>
</div><!-- .wrapper for sitting footer at the bottom of the page -->
<div class="footer">
	<div class="inner">
		<?php if (get_option('blog_public') == '1' || is_user_logged_in()): ?>
			<?php if (is_page() || is_home( ) ): ?>
			
			<table>
				<tr>
					<td><?php _e('Book Name', 'pressbooks'); ?>:</td>
					<td><?php bloginfo('name'); ?></td>
				</tr>
				<?php global $metakeys; ?>
       			 <?php $metadata = pb_get_book_information();?>
				<?php foreach ($metadata as $key => $val): ?>
				<?php if ( isset( $metakeys[$key] ) && ! empty( $val ) ): ?>
				<tr>
					<td><?php echo $metakeys[$key]; ?></td>
					<td><?php if ( 'pb_publication_date' == $key ) { $val = date_i18n( 'F j, Y', $val ); } echo $val; ?></td>
				<?php endif; ?>
				<?php endforeach; ?>
				</tr>
				<?php
				// Copyright
				echo '<tr><td>' . __( 'Copyright', 'pressbooks' ) . '</td><td>';
				echo ( ! empty( $metadata['pb_copyright_year'] ) ) ? $metadata['pb_copyright_year'] : date( 'Y' );
				if ( ! empty( $metadata['pb_copyright_holder'] ) ) echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
				echo "</td></tr>\n";
				?>

				</table>
				<?php endif; ?>
				<?php endif; ?>
			<p class="cie-name"><a href="http://pressbooks.com"><?php _e('PressBooks.com: Simple Book Production', 'pressbooks'); ?></a></p>
	</div><!-- #inner -->
</div><!-- #footer -->
<?php if ((is_super_admin()) || ($_SERVER['REMOTE_ADDR'] == '96.22.106.184')): ?>
<div id="debug"><?php
?></div>
<script type="text/javascript" charset="utf-8">

jQuery(document).keydown(function(e){
	if (e.which == 38) { // up arrow
		if (jQuery('#debug').is(':hidden')) { jQuery('#debug').slideToggle('fast'); }
		else { jQuery('#debug').animate({height: '100%'}); }
		return false;
	}
	else if (e.which == 40) { // down arrow
		 if (jQuery('#debug').is(':visible')) {
			if (jQuery('#debug').height() > 150) { jQuery('#debug').animate({height: '150px'}); }
			else { jQuery('#debug').slideToggle('fast'); }
		 }
		 return false;
	}
});
</script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
<?php while (@ob_end_flush()); ?>
