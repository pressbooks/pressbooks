<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Outputs the content of the Organize page for a book */

$statuses = get_post_stati( [], 'objects' );
$book_structure = \Pressbooks\Book::getBookStructure();
// wp_die( '<pre>' . print_r( $book_structure, true ) . '</pre>' );
$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
$book_is_public = ( ! empty( get_option( 'blog_public' ) ) ) ? 1 : 0;
$disable_comments = \Pressbooks\Utility\disable_comments();
$wc = \Pressbooks\Book::wordCount();
$wc_selected_for_export = \Pressbooks\Book::wordCount( true );
$contributors = new \Pressbooks\Contributors();
$start_point = false;
$ebook_options = get_option( 'pressbooks_theme_options_ebook' );
if ( isset( $ebook_options['ebook_start_point'] ) && ! empty( $ebook_options['ebook_start_point'] ) ) {
	$start_point = $ebook_options['ebook_start_point'];
}
?>

<div class="wrap">
	<div aria-live="assertive" role="alert" class="visually-hidden"><span class="spinner"></span><p class="message"></p></div>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
	<div id="publicize-panel" class="postbox">
		<div class="inside">
			<?php if ( $book_is_public ) { ?>
			<h4 class="publicize-alert public"><?php _e( 'This book\'s global privacy is set to', 'pressbooks' ); ?> <span><?php _e( 'Public', 'pressbooks' ); ?></span></h4>
			<?php } else { ?>
			<h4 class="publicize-alert private"><?php _e( 'This book\'s global privacy is set to', 'pressbooks' ); ?> <span><?php _e( 'Private', 'pressbooks' ); ?></span></h4>
			<?php } ?>
			<div class="publicize-form">
				<label for="blog-public"><input type="radio" <?php checked( $book_is_public, 1 ); ?> value="1" name="blog_public" id="blog-public"><span class="public"><?php _e( 'Public', 'pressbooks' ); ?></span> &mdash;
					<?php _e( 'Promote your book, set individual chapters privacy below.', 'pressbooks' ); ?>
				</label>
				<label for="blog-private"><input type="radio" <?php checked( $book_is_public, 0 ); ?> value="0" name="blog_public" id="blog-private"><span class="private"><?php _e( 'Private', 'pressbooks' ); ?></span> &mdash;
					<?php _e( 'Only users you invite can see your book, regardless of individual chapter visibility below.', 'pressbooks' ); ?>
				</label>
			</div>
		</div>
	</div>
	<?php endif; ?>
	<h1 class="wp-heading-inline"><?php bloginfo( 'name' ); ?></h1>
		<?php if ( is_super_admin() ) : ?>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=front-matter' ); ?>"><?php _e( 'Front Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=chapter' ); ?>"><?php _e( 'Chapters', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=back-matter' ); ?>"><?php _e( 'Back Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=part' ); ?>"><?php _e( 'Parts', 'pressbooks' ); ?></a>
		<?php else : ?>
			<a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=pb_export' ); ?>"><?php _e( 'Export', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=front-matter' ); ?>"><?php _e( 'Add Front Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=back-matter' ); ?>"><?php _e( 'Add Back Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=chapter' ); ?>"><?php _e( 'Add Chapter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=part' ); ?>"><?php _e( 'Add Part', 'pressbooks' ); ?></a>
		<?php endif; ?>
	<p class="word-count">
		<strong><?php _e( 'Word Count:', 'pressbooks' ); ?></strong> <?php printf( __( '%s (whole book)', 'pressbooks' ), "<span id='wc-all'>$wc</span>" ); ?> /
		<?php printf( __( '%s (selected for export)', 'pressbooks' ), "<span id='wc-selected-for-export'>$wc_selected_for_export</span>" ); ?>
	</p>

	<?php

	// Iterate through types and output nice tables for each one.
	$types = [
		'front-matter' => [
			'name' => __( 'Front Matter', 'pressbooks' ),
			'abbreviation' => 'fm',
		],
		'chapter' => [
			'name' => __( 'Chapter', 'pressbooks' ),
			'abbreviation' => 'chapter',
		],
		'back-matter' => [
			'name' => __( 'Back Matter', 'pressbooks' ),
			'abbreviation' => 'bm',
		],
	];

	foreach ( $types as $slug => $type ) :
		$name = $type['name'];
		 // Chapters have to be handled differently.
		if ( 'chapter' === $slug ) :
		?>
			<?php
			$parts = count( $book_structure['part'] );
			$p = 1;
			foreach ( $book_structure['part'] as $part ) :
			?>
				<table id="part_<?php echo $part['ID']; ?>" class="wp-list-table widefat fixed striped chapters">
					<thead>
						<tr>
							<th scope="col" id="title" class="has-row-actions manage-column column-title column-primary">
								<a href="<?php echo admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ); ?>"><?php echo $part['post_title']; ?></a>
								<div class="row-actions">
									<a href="<?php echo admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ); ?>"><?php _e( 'Edit', 'pressbooks' ); ?></a> |
														<?php
														if ( count( $book_structure['part'] ) > 1 ) :
															// Don't allow deletion of last remaining part. Bad things happen.
					?>
					 <a class="delete-link" href="<?php echo get_delete_post_link( $part['ID'] ); ?>" onclick="if ( !confirm( '<?php _e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Trash', 'pressbooks' ); ?></a> | <?php endif; ?> <a href="<?php echo get_permalink( $part['ID'] ); ?>"><?php _e( 'View', 'pressbooks' ); ?></a>
								</div>

							</th>
							<th><?php _e( 'Authors', 'pressbooks' ); ?></th>
							<?php
							if ( false === $disable_comments ) :
							?>
								<th><?php _e( 'Comments', 'pressbooks' ); ?></th>
							<?php endif; ?>
							<th role="button"><?php _e( 'Show in Web', 'pressbooks' ); ?></th>
							<th role="button"><?php _e( 'Show in Exports', 'pressbooks' ); ?></th>
							<th role="button"><?php _e( 'Show Title', 'pressbooks' ); ?></th>
						</tr>
					</thead>

					<?php if ( count( $part['chapters'] ) > 0 ) : ?>

					<tbody id="the-list">
					<?php
					$chapters = count( $part['chapters'] );
					$c = 1; // Start the chapter counter
					foreach ( $part['chapters'] as $content ) :
					?>
						<tr id="<?php echo $slug; ?>_<?php echo $content['ID']; ?>">
							<td class="title column-title has-row-actions">
								<div class="row-title"><a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>">
								<?php echo $content['post_title']; ?>
								<?php if ( $start_point === $content['ID'] ) { ?>
									<span class="ebook-start-point" title="<?php _e( 'Ebook start point', 'pressbooks' ); ?>">&#9733;</span>
								<?php } ?></a>
								<div class="row-actions">
									<a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>"><?php _e( 'Edit', 'pressbooks' ); ?></a> | <a class="delete-link" href="<?php echo get_delete_post_link( $content['ID'] ); ?>" onclick="if ( !confirm( '<?php _e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Trash', 'pressbooks' ); ?></a> | <a href="<?php echo get_permalink( $content['ID'] ); ?>"><?php _e( 'View', 'pressbooks' ); ?></a>
								<?php
								if ( $c > 1 || ( $p > 1 && $parts > 1 ) || $c < $chapters || $p < $parts ) :
?>
<span class="reorder"><?php endif; ?>
								<?php if ( $c > 1 || ( $p > 1 && $parts > 1 ) ) : ?>
 | <button class="move-up"><?php _e( 'Move Up', 'pressbooks' ); ?></button>
									<?php endif; ?>
									<?php if ( $c < $chapters || $p < $parts ) : ?>
 | <button class="move-down"><?php _e( 'Move Down', 'pressbooks' ); ?></button>
									<?php endif; ?>
								<?php
								if ( $c > 1 || ( $p > 1 && $parts > 1 ) || $c < $chapters || $p < $parts ) :
?>
</span><?php endif; ?>
								</div>
								</div>
							</td>
							<td class="author column-author">
							<span class="author-label"><?php _e( 'Authors', 'pressbooks' ); ?>:</span>
								<?php
								$section_authors = $contributors->get( $content['ID'], 'pb_authors' );
								if ( $section_authors ) {
									echo $section_authors;
								} else {
									echo '—';
								}
								?>
							</td>
							<?php
							if ( false === $disable_comments ) :
							?>
							<td class="comments column-comments">
								<a class="post-comment-count" href="<?php echo admin_url( 'edit-comments.php?p=' . $content['ID'] ); ?>">
									<span class="comment-count"><?php echo $content['comment_count']; ?></span>
								</a>
							</td>
							<?php endif; ?>
							<?php
							$visibility = [
								'web' => ( in_array( $content['post_status'], [ 'web-only', 'publish' ], true ) ) ? true : false,
								'export' => ( in_array( $content['post_status'], [ 'private', 'publish' ], true ) ) ? true : false,
							];
							?>
							<td class="visibility column-web">
								<input class="web_visibility" type="checkbox" data-id="<?php echo $content['ID']; ?>" name="web_visibility_[<?php echo $content['ID']; ?>]" id="web_visibility_<?php echo $content['ID']; ?>" <?php checked( true, $visibility['web'] ); ?> />
								<label for="web_visibility_<?php echo $content['ID']; ?>"><?php _e( 'Show in Web', 'pressbooks' ); ?></label>
							</td>
							<td class="visibility column-exports">
								<input class="export_visibility" type="checkbox" data-id="<?php echo $content['ID']; ?>" name="export_visibility_[<?php echo $content['ID']; ?>]" id="export_visibility_<?php echo $content['ID']; ?>" <?php checked( true, $visibility['export'] ); ?> />
								<label for="export_visibility_<?php echo $content['ID']; ?>"><?php _e( 'Show in Exports', 'pressbooks' ); ?></label>
							</td>
							<td class="export column-showtitle">
								<input class="show_title" type="checkbox" data-id="<?php echo $content['ID']; ?>" name="show_title_[<?php echo $content['ID']; ?>]" id="show_title_<?php echo $content['ID']; ?>" <?php checked( get_post_meta( $content['ID'], 'pb_show_title', true ), 'on', true ); ?>/>
								<label for="show_title_<?php echo $content['ID']; ?>"><?php _e( 'Show Title', 'pressbooks' ); ?></label>
							</td>
						</tr>
					<?php
					$c++;
					endforeach;
					?>
					</tbody>
					<?php endif; ?>
					<tfoot>
						<tr>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<?php
							if ( false === $disable_comments ) :
?>
<th>&nbsp;</th><?php endif; ?>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<th>
								<a href="<?php echo admin_url( 'post-new.php?post_type=' . $slug . '&startparent=' . $part['ID'] ); ?>" class="button"><?php _e( 'Add', 'pressbooks' ); ?> <?php echo $name; ?></a>
							</th>
						</tr>
					</tfoot>
				</table>
			<?php
			$p++;
			endforeach;
			?>
			<p><a class="button" href="<?php echo admin_url( 'post-new.php?post_type=part' ); ?>"><?php _e( 'Add Part', 'pressbooks' ); ?></a></p>
		<?php else : ?>
		<table id="<?php echo $slug; ?>" class="wp-list-table widefat fixed striped <?php echo $slug; ?>" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="title" class="has-row-actions manage-column column-title column-primary"><?php echo $name; ?></th>
					<th><?php _e( 'Authors', 'pressbooks' ); ?></th>
					<?php if ( false === $disable_comments ) : ?>
					<th><?php _e( 'Comments', 'pressbooks' ); ?></th>
					<?php endif; ?>
					<th role="button"><?php _e( 'Show in Web', 'pressbooks' ); ?></th>
					<th role="button"><?php _e( 'Show in Exports', 'pressbooks' ); ?></th>
					<th role="button"><?php _e( 'Show Title', 'pressbooks' ); ?></th>
				</tr>
			</thead>

			<tbody id="the-list">
			<?php
			$sections = count( $book_structure[ $slug ] );
			$s = 1; // Start the counter
			foreach ( $book_structure[ $slug ] as $content ) :
			?>
				<tr id="<?php echo $slug; ?>_<?php echo $content['ID']; ?>">
					<td class="title column-title has-row-actions">
						<div class="row-title"><a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>">
						<?php echo $content['post_title']; ?>
						<?php if ( $start_point === $content['ID'] ) { ?>
							<span class="ebook-start-point" title="<?php _e( 'Ebook start point', 'pressbooks' ); ?>">&#9733;</span>
						<?php } ?></a>
						<div class="row-actions">
							<a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>"><?php _e( 'Edit', 'pressbooks' ); ?></a> | <a class="delete-link" href="<?php echo get_delete_post_link( $content['ID'] ); ?>" onclick="if ( !confirm( '<?php _e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Trash', 'pressbooks' ); ?></a> | <a href="<?php echo get_permalink( $content['ID'] ); ?>"><?php _e( 'View', 'pressbooks' ); ?></a>
							<?php
							if ( $s > 1 || $s < $sections ) {
								echo '<span class="reorder">';
							}
							?>
							<?php if ( $s > 1 ) : ?>
| <button class="move-up"><?php _e( 'Move Up', 'pressbooks' ); ?></button>
							<?php endif; ?>
							<?php if ( $s < $sections ) : ?>
| <button class="move-down"><?php _e( 'Move Down', 'pressbooks' ); ?></button>
							<?php endif; ?>
							<?php
							if ( $s > 1 || $s < $sections ) :
?>
</span><?php endif; ?>
						</div>
						</div>
					</td>
					<td class="author column-author">
						<span class="author-label"><?php _e( 'Authors', 'pressbooks' ); ?>:</span>
						<?php
						$section_authors = $contributors->get( $content['ID'], 'pb_authors' );
						if ( $section_authors ) {
							echo $section_authors;
						} else {
							echo '—';
						}
						?>
					</td>
					<?php
					if ( false === $disable_comments ) :
?>
<td class="comments column-comments">
						<a class="post-comment-count" href="<?php echo admin_url( 'edit-comments.php?p=' . $content['ID'] ); ?>">
							<span class="comment-count"><?php echo $content['comment_count']; ?></span>
						</a>
					</td><?php endif; ?>
					<?php
					$visibility = [
						'web' => ( in_array( $content['post_status'], [ 'web-only', 'publish' ], true ) ) ? true : false,
						'export' => ( in_array( $content['post_status'], [ 'private', 'publish' ], true ) ) ? true : false,
					];
					?>
					<td class="status column-web">
						<input class="web_visibility" type="checkbox" data-id="<?php echo $content['ID']; ?>" name="web_visibility_[<?php echo $content['ID']; ?>]" id="web_visibility_<?php echo $content['ID']; ?>" <?php checked( true, $visibility['web'] ); ?> />
						<label for="web_visibility_<?php echo $content['ID']; ?>"><?php _e( 'Show in Web', 'pressbooks' ); ?></label>
					</td>
					<td class="export column-exports">
						<input class="export_visibility" type="checkbox" data-id="<?php echo $content['ID']; ?>" name="export_visibility_[<?php echo $content['ID']; ?>]" id="export_visibility_<?php echo $content['ID']; ?>" <?php checked( true, $visibility['export'] ); ?> />
						<label for="export_visibility_<?php echo $content['ID']; ?>"><?php _e( 'Show in Exports', 'pressbooks' ); ?></label>
					</td>
					<td class="export column-showtitle">
					<input class="show_title" type="checkbox" data-id="<?php echo $content['ID']; ?>" name="show_title_[<?php echo $content['ID']; ?>]" id="show_title_<?php echo $content['ID']; ?>" <?php checked( get_post_meta( $content['ID'], 'pb_show_title', true ), 'on', true ); ?>/>
					<label for="show_title_<?php echo $content['ID']; ?>"><?php _e( 'Show Title', 'pressbooks' ); ?></label>
		</td>
				</tr>
			<?php
			$s++;
			endforeach;
			?>
			</tbody>
			<tfoot>
				<tr>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<?php
					if ( false === $disable_comments ) :
?>
<th>&nbsp;</th><?php endif; ?>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>
						<a href="<?php echo admin_url( 'post-new.php?post_type=' . $slug ); ?>" class="button"><?php _e( 'Add', 'pressbooks' ); ?> <?php echo $name; ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
