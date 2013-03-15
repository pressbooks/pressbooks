<?php get_header(); ?>

 
	<div class="full-page">
		<?php global $wpdb;
			$query = "SELECT blog_id FROM " . $wpdb->base_prefix . "blogs WHERE spam != '1' AND archived != '1' AND deleted != '1' AND public = '1' AND blog_id != '1' ORDER BY blog_id";
			$books = $wpdb->get_results($query); ?>
 

			<div class="full-page-header"> 

			    <h2>Books</h2>
			
			</div>

	
<?php $catalogue = array();
	foreach($books as $book) {
		$book_details = get_blog_details($book->blog_id);
		switch_to_blog($book->blog_id);
		$book_meta = pb_get_book_information();
		restore_current_blog();
		$catalogue[$book->blog_id]['url'] = $book_details->siteurl;
		$catalogue[$book->blog_id]['title'] = $book_details->blogname;
		if ( ! empty( $book_meta['pb_cover_image'] ) ) :
			$catalogue[$book->blog_id]['cover'] = $book_meta['pb_cover_image'];
		else : 
			$catalogue[$book->blog_id]['cover'] = get_bloginfo('template_directory') . '/images/default-cover-full-page.png';
		endif;
		if ( ! empty( $book_meta['pb_about_50'] ) ) :
			$description = $book_meta['pb_about_50'];
			$description = explode(' ', $description);
			$description = array_slice($description, 0, 25);
			$description = implode(' ', $description);
			$catalogue[$book->blog_id]['description'] = $description . '&hellip;';
		else : 
			$catalogue[$book->blog_id]['description'] = '';
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
   <div id="post-<?php echo($id); ?>" <?php post_class('full-page-post'); ?>>
    		
    		
    		<!-- Thumbnail -->				
				<a href="<?php echo($data['url']); ?>" rel="bookmark" class="full-page-image"><img src="<?php echo($data['cover']); ?>" alt="<?php echo($data['title']); ?>" width="155" height="233" title="<?php echo($data['title']); ?>" /></a>    		
 
    		        <h2 class="postTitle"><a href="<?php echo($data['url']); ?>" rel="bookmark"><?php echo($data['title']); ?></a></h2>    
	
			      	<div class="post-content">
				      	<?php echo($data['description']); ?>
				      	<a class="more-link" href="<?php echo($data['url']); ?>" rel="bookmark">Read more &raquo;</a>
				      	<div class="clear"></div>
				   </div><!-- end .post-content -->
		
				   <hr class="noCss" />
	
				   <?php //comments_template(); // Get wp-comments.php template ?>
	 </div> <!-- end .post_class -->	
	 <?php } ?>

        


		 <div class="pagination-older"><?php next_posts_link('Older Entries ') ?></div>
		 <div class=" pagination-newer"><?php previous_posts_link('Newer Entries ') ?></div> 
    
</div><!-- end .full-page -->

	

<?php get_footer(); ?>
