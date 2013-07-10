			<section class="third-block-wrap"> 
				<div class="third-block clearfix">
				<h2>Table of Contents</h2>
				<?php $book = pb_get_book_structure(); ?>
				<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
					<script>jQuery("table-of-content").scrollLeft(300);
					</script>	
					<ul class="table-of-content" id="table-of-content">
												
							<ul class="front-matter">
								<?php foreach ($book['front-matter'] as $fm): ?>
								<?php if ($fm['post_status'] != 'publish') continue; // Skip ?>
								<li><a href="<?php echo get_permalink($fm['ID']); ?>"><?php echo $fm['post_title'];?></a></li>
								<?php endforeach; ?>
							</ul>
							<?php foreach ($book['part'] as $part):?>
							<li><h4><?php if ( count( $book['part'] ) > 1 ) echo $part['post_title']; ?></h4></li>
							<ul>
								<?php foreach ($part['chapters'] as $chapter) : ?>
									<?php if ($chapter['post_status'] != 'publish') continue; // Skip ?>
									<li><a href="<?php echo get_permalink($chapter['ID']); ?>"><?php echo $chapter['post_title']; ?></a></li>
								<?php endforeach; ?>
							</ul>
							<?php endforeach; ?>
							<li><h4><!-- Back-matter --></h4></li>
							<ul class="back-matter">
								<?php foreach ($book['back-matter'] as $fm): ?>
								<?php if ($fm['post_status'] != 'publish') continue; // Skip ?>
								<li><a href="<?php echo get_permalink($fm['ID']); ?>"><?php echo $fm['post_title'];?></a></li>
								<?php endforeach; ?>
							</ul>

					</ul><!-- end #toc -->	
						
				</div><!-- end .third-block -->
			</section> <!-- end .third-block -->		