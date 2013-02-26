<?php
	global $wpdb;
	$query = "SELECT blog_id FROM " . $wpdb->base_prefix . "blogs WHERE spam != '1' AND archived != '1' AND deleted != '1' AND public = '1' AND blog_id != '1' ORDER BY blog_id";
	$books = $wpdb->get_results($query);
	
	echo '<ul>';
	$catalogue = array();
	foreach($books as $book) {
		$book_details = get_blog_details($book->blog_id);
		switch_to_blog($book->blog_id);
		$book_meta = pb_get_book_information();
		restore_current_blog();
		$catalogue[$book->blog_id]['url'] = $book_details->siteurl;
		$catalogue[$book->blog_id]['title'] = $book_details->blogname;
		if ( ! empty ( $book_meta['pb_cover_image'] ) ) :
			$catalogue[$book->blog_id]['cover'] = $book_meta['pb_cover_image'];
		else : 
			$catalogue[$book->blog_id]['cover'] = get_bloginfo('template_directory') . '/images/default-sidebar-cover.png';
		endif;
		if ( ! empty( $book_meta['pb_author'] ) ) :
			$catalogue[$book->blog_id]['author'] = $book_meta['pb_author'];
		endif;
		if ( ! empty( $book_meta['pb_catalogue_order'] ) ) :
			$catalogue[$book->blog_id]['order'] = $book_meta['pb_catalogue_order'];
		else : 
			$catalogue[$book->blog_id]['order'] = intval(0);
		endif;
	}
	
	function cmp( $a, $b ) {
		if ( $a['order'] == $b['order'] ) {
			if ( $a['title'] == $b['title'] ) return 0 ;
			return ( $a['title'] < $b['title'] ) ? -1 : 1;
		} else
			return ($a['order'] < $b['order']) ? -1 : 1;
	}
	
	usort($catalogue, 'cmp'); ?>
	
	<?php foreach ($catalogue as $id => $data) { ?>
		<div class="book-sidebar-warp">
			<!-- Thumbnail -->
			<a href="<?php echo($data['url']); ?>" rel="bookmark" class="sidebar-bookcover-image"><img src="<?php echo($data['cover']); ?>" alt="<?php echo($data['title']); ?>" width="100" height="151" title="<?php echo($data['title']); ?>" /></a>
			<div class="post">
				<h2><a href="<?php echo($data['url']); ?>" rel="bookmark"><?php echo($data['title']); ?></a></h2>
				
				<!-- Author name -->				
					<?php if( isset ( $data['author'] ) ) : ?>
		        	      <h5>by <?php echo $data['author']; ?></h5>
		        	<?php endif; ?>
	        </div><!-- end .post -->		       
		</div> <!-- end .book-sidebar-warp -->
	<?php }
	echo '</ul>';
?>
