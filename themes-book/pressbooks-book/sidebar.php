<?php global $blog_id; ?>
	<?php if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))): ?>

	<div id="sidebar">

		<ul id="booknav">
		<!-- If Logged in show ADMIN -->
			<?php global $blog_id; ?>
			<?php if (current_user_can_for_blog($blog_id, 'edit_posts') || is_super_admin()): ?>
				<li class="admin-btn"><a href="<?php echo get_option('home'); ?>/wp-admin"><?php _e('Admin', 'pressbooks'); ?></a></li>
			<?php endif; ?>
		
				<li class="home-btn"><a href="<?php echo get_option('home'); ?>"><?php _e('Home', 'pressbooks'); ?></a></li>

		<!-- TOC button always there -->
				<li class="toc-btn"><a href="<?php echo get_option('home'); ?>/table-of-contents"><?php _e('Table of Contents', 'pressbooks'); ?></a></li>
			</ul>

		<!-- Pop out TOC only on READ pages -->
		<?php if (is_single()): ?>
		<?php $book = pb_get_book_structure(); ?>
		<div id="toc">
			<a href="#" class="close"><?php _e('Close', 'pressbooks'); ?></a>
			<ul>
				<li><h4><!-- Front-matter --></h4></li>
				<li>
					<ul>
						<?php foreach ($book['front-matter'] as $fm): ?>
						<?php if ( $fm['post_status'] !== 'publish' ) {
							if ( !current_user_can_for_blog( $blog_id, 'read_private_posts' ) ) {
								if ( current_user_can_for_blog( $blog_id, 'read' ) ) {
									if ( absint( get_option( 'permissive_private_content' ) ) !== 1 ) continue; // Skip
								} elseif ( !current_user_can_for_blog( $blog_id, 'read' ) ) {
									 continue; // Skip
								}
							}
						} ?>
						<li class="front-matter <?php echo pb_get_section_type( get_post($fm['ID']) ) ?>"><a href="<?php echo get_permalink($fm['ID']); ?>"><?php echo pb_strip_br( $fm['post_title'] );?></a>
              <?php if ( pb_should_parse_subsections() ){
                $sections = pb_get_subsections( $fm['ID'] );
                if ($sections){
                  $s = 1; ?>
                  <ul class="sections">
                    <?php foreach ( $sections as $id => $name ) { ?>
                      <li class="section"><a href="<?php echo get_permalink($fm['ID']); ?>#<?php echo $id; ?>"><?php echo $name; ?></a></li>
                    <?php } ?>
                  </ul>
                <?php }} ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</li>
				<?php foreach ($book['part'] as $part):?>
				<li><h4><?php if ( count( $book['part'] ) > 1 || get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on' ) { ?>
				<?php if ( $part['has_post_content'] ) { ?><a href="<?php echo get_permalink($part['ID']); ?>"><?php } ?>
				<?php echo $part['post_title']; ?>
				<?php if ( $part['has_post_content'] ) { ?></a><?php } ?>
				<?php } ?></h4></li>
				<li>
					<ul>
						<?php foreach ($part['chapters'] as $chapter) : ?>
							<?php if ( $chapter['post_status'] !== 'publish' ) {
								if ( !current_user_can_for_blog( $blog_id, 'read_private_posts' ) ) {
									if ( current_user_can_for_blog( $blog_id, 'read' ) ) {
										if ( absint( get_option( 'permissive_private_content' ) ) !== 1 ) continue; // Skip
									} elseif ( !current_user_can_for_blog( $blog_id, 'read' ) ) {
										 continue; // Skip
									}
								}
							} ?>
							<li class="chapter <?php echo pb_get_section_type( get_post($chapter['ID']) ) ?>"><a href="<?php echo get_permalink($chapter['ID']); ?>"><?php echo pb_strip_br( $chapter['post_title'] ); ?></a>
                <?php if ( pb_should_parse_subsections() ){
                  $sections = pb_get_subsections( $chapter['ID'] );
                  if ($sections){
                    $s = 1; ?>
                    <ul class="sections">
                      <?php foreach ( $sections as $id => $name ) { ?>
                        <li class="section"><a href="<?php echo get_permalink($chapter['ID']); ?>#<?php echo $id; ?>"><?php echo $name; ?></a></li>
                      <?php } ?>
                    </ul>
                  <?php }} ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</li>
				<?php endforeach; ?>
				<li><h4><!-- Back-matter --></h4></li>
				<li>
					<ul>
						<?php foreach ($book['back-matter'] as $bm): ?>
						<?php if ( $bm['post_status'] !== 'publish' ) {
							if ( !current_user_can_for_blog( $blog_id, 'read_private_posts' ) ) {
								if ( current_user_can_for_blog( $blog_id, 'read' ) ) {
									if ( absint( get_option( 'permissive_private_content' ) ) !== 1 ) continue; // Skip
								} elseif ( !current_user_can_for_blog( $blog_id, 'read' ) ) {
									 continue; // Skip
								}
							}
						} ?>
						<li class="back-matter <?php echo pb_get_section_type( get_post($bm['ID']) ) ?>"><a href="<?php echo get_permalink($bm['ID']); ?>"><?php echo pb_strip_br( $bm['post_title'] );?></a>
              <?php if ( pb_should_parse_subsections() ){
                $sections = pb_get_subsections( $bm['ID'] );
                if ($sections){
                  $s = 1; ?>
                  <ul class="sections">
                    <?php foreach ( $sections as $id => $name ) { ?>
                      <li class="section"><a href="<?php echo get_permalink($bm['ID']); ?>#<?php echo $id; ?>"><?php echo $name; ?></a></li>
                    <?php } ?>
                  </ul>
                <?php }} ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</li>
			</ul>
		</div><!-- end #toc -->
		<?php endif; ?>


	</div><!-- end #sidebar -->
	<?php endif; ?>
