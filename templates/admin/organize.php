<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Outputs the content of the Organize page for a book */

global $user_ID; // @codingStandardsIgnoreLine
$statuses = get_post_stati( [], 'objects' );
$book_structure = \Pressbooks\Book::getBookStructure();
$book_is_public = ( ! empty( get_option( 'blog_public' ) ) );
$disable_comments = \Pressbooks\Utility\disable_comments();
$wc = \Pressbooks\Book::wordCount();
$wc_selected_for_export = \Pressbooks\Book::wordCount( true );
?>

<div class="wrap">
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
	<div id="publicize-panel" class="postbox">
		<div class="inside">
			<?php if ( $book_is_public ) { ?>
			<h4 class="publicize-alert public"><?php _e( 'This book\'s global privacy is set to', 'pressbooks' ); ?> <span><?php _e( 'Public', 'pressbooks' ); ?></span></h4>
			<?php } else { ?>
			<h4 class="publicize-alert private"><?php _e( 'This book\'s global privacy is set to', 'pressbooks' ); ?> <span><?php _e( 'Private', 'pressbooks' ); ?></span></h4>
			<?php } ?>
			<div class="publicize-form">
				<label for="blog-public"><input type="radio" <?php if ( $book_is_public ) { echo 'checked="checked"';} ?> value="1" name="blog_public" id="blog-public"><span class="public<?php if ( $book_is_public ) { echo ' active';} ?>"><?php _e( 'Public', 'pressbooks' ); ?></span> &mdash;
					<?php _e( 'Promote your book, set individual chapters privacy below.', 'pressbooks' ); ?>
				</label>
				<label for="blog-private"><input type="radio" <?php if ( ! $book_is_public ) { echo 'checked="checked"';} ?> value="0" name="blog_public" id="blog-private"><span class="private<?php if ( ! $book_is_public ) { echo ' active';} ?>"><?php _e( 'Private', 'pressbooks' ); ?></span> &mdash;
					<?php _e( 'Only users you invite can see your book, regardless of individual chapter privacy settings below.', 'pressbooks' ); ?>
				</label>
			</div>
		</div>
	</div>
	<?php endif; ?>
	<h2><?php bloginfo( 'name' ); ?>
		<?php if ( is_super_admin() ) : ?>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=front-matter' ); ?>"><?php _e( 'Front Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=chapter' ); ?>"><?php _e( 'Chapters', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=back-matter' ); ?>"><?php _e( 'Back Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'edit.php?post_type=part' ); ?>"><?php _e( 'Part', 'pressbooks' ); ?></a>
		<?php else : ?>
			<a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=pb_export' ); ?>"><?php _e( 'Export', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=front-matter' ); ?>"><?php _e( 'Add Front Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=back-matter' ); ?>"><?php _e( 'Add Back Matter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=chapter' ); ?>"><?php _e( 'Add Chapter', 'pressbooks' ); ?></a>
			<a class="page-title-action" href="<?php echo admin_url( 'post-new.php?post_type=part' ); ?>"><?php _e( 'Add Part', 'pressbooks' ); ?></a>
		<?php endif; ?>
	</h2>

	<p>
		<strong><?php _e( 'Word Count:', 'pressbooks' ); ?></strong> <?php printf( __( '%s (whole book)', 'pressbooks' ), "<span id='wc-all'>$wc</span>" ); ?> /
		<?php printf( __( '%s (selected for export)', 'pressbooks' ), "<span id='wc-selected-for-export'>$wc_selected_for_export</span>" ); ?>
	</p>

	<?php // Iterate through types and output nice tables for each one.

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

	foreach ( $types as $type_slug => $type ) :
		$type_name = $type['name'];
		$type_abbr = $type['abbreviation'];
		if ( 'chapter' === $type_slug ) : // Chapters have to be handled differently. ?>
			<?php foreach ( $book_structure['part'] as $part ) : ?>
				<table id="part-<?php echo $part['ID']; ?>" class="wp-list-table widefat fixed <?php echo $type_slug; ?>s" cellspacing="0">
					<thead>
						<tr>
							<th class="has-row-actions">
								<a href="<?php echo admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ); ?>"><?php echo $part['post_title']; ?></a>
								<div class="row-actions">
									<a href="<?php echo admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ); ?>"><?php _e( 'Edit', 'pressbooks' ); ?></a> | <?php if ( count( $book_structure['part'] ) > 1 ) : // Don't allow deletion of last remaining part. Bad things happen. ?> <a class="delete-link" href="<?php echo get_delete_post_link( $part['ID'] ); ?>" onclick="if ( !confirm( '<?php _e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Trash', 'pressbooks' ); ?></a> | <?php endif; ?> <a href="<?php echo get_permalink( $part['ID'] ); ?>"><?php _e( 'View', 'pressbooks' ); ?></a>
								</div>

							</th>
							<th><?php _e( 'Author', 'pressbooks' ); ?></th>
							<?php if ( false === $disable_comments ) : ?><th><?php _e( 'Comments', 'pressbooks' ); ?></th><?php endif; ?>
							<th><?php _e( 'Status', 'pressbooks' ); ?></th>
							<th role="button"><?php _e( 'Private', 'pressbooks' ); ?></th>
							<th role="button"><?php _e( 'Show Title', 'pressbooks' ); ?></th>
							<th role="button"><?php _e( 'Export', 'pressbooks' ); ?></th>
						</tr>
					</thead>

					<?php if ( count( $part['chapters'] ) > 0 ) : ?>

					<tbody id="the-list">
					<?php foreach ( $part['chapters'] as $content ) : ?>
						<tr id="<?php echo $type_slug; ?>-<?php echo $content['ID']; ?>">
							<td class="title column-title has-row-actions">
								<div class="row-title"><a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>">
								<?php echo $content['post_title']; ?>
								<?php if ( get_post_meta( $content['ID'], 'pb_ebook_start', true ) ) { ?>
									<span class="ebook-start-point" title="<?php _e( 'Ebook start point', 'pressbooks' ); ?>">&#9733;</span>
								<?php } ?></a>
								<div class="row-actions">
									<a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>"><?php _e( 'Edit', 'pressbooks' ); ?></a> | <a class="delete-link" href="<?php echo get_delete_post_link( $content['ID'] ); ?>" onclick="if ( !confirm( '<?php _e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Trash', 'pressbooks' ); ?></a> | <a href="<?php echo get_permalink( $content['ID'] ); ?>"><?php _e( 'View', 'pressbooks' ); ?></a>
								</div>
								</div>
							</td>
							<td class="author column-author">
								<?php echo $content['post_author'] === $user_ID ? 'You' : get_userdata( $content['post_author'] )->display_name; // @codingStandardsIgnoreLine ?>
							</td>
							<?php if ( false === $disable_comments ) : ?><td class="comments column-comments">
								<a class="post-comment-count" href="<?php echo admin_url( 'edit-comments.php?p=' . $content['ID'] ); ?>">
									<span class="comment-count"><?php echo $content['comment_count']; ?></span>
								</a>
							</td><?php endif; ?>
							<td class="status column-status"><?php echo $statuses[ $content['post_status'] ]->label; ?></td>
							<td class="status column-privacy">
								<input class="<?php echo $type_abbr; ?>_privacy" type="checkbox" name="<?php echo $type_abbr; ?>-private[<?php echo $content['ID']; ?>]" id="<?php echo $type_abbr; ?>_private_<?php echo $content['ID']; ?>" <?php checked( 'private', get_post_status( $content['ID'] ) ); ?> />
							</td>
							<?php $export = get_post_meta( $content['ID'], 'pb_export', true ); ?>
							<td class="export column-showtitle">
								<input class="<?php echo $type_abbr; ?>_show_title_check" type="checkbox" name="<?php echo $type_abbr; ?>-showtitle[<?php echo $content['ID']; ?>]" id="<?php echo $type_abbr; ?>_show_title_<?php echo $content['ID']; ?>" <?php checked( get_post_meta( $content['ID'], 'pb_show_title', true ), 'on', true ); ?>/>
							</td>
							<td class="export column-export">
								<input class="<?php echo $type_abbr; ?>_export_check" type="checkbox" name="<?php echo $type_abbr; ?>-export[<?php echo $content['ID']; ?>]" id="<?php echo $type_abbr; ?>_export_<?php echo $content['ID']; ?>" <?php checked( get_post_meta( $content['ID'], 'pb_export', true ), 'on', true ); ?>/>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					<?php endif; ?>
					<tfoot>
						<tr>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<?php if ( false === $disable_comments ) : ?><th>&nbsp;</th><?php endif; ?>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<th>
								<a href="<?php echo admin_url( 'post-new.php?post_type=' . $type_slug . '&startparent=' . $part['ID'] ); ?>" class="button"><?php _e( 'Add', 'pressbooks' ); ?> <?php echo $type_name; ?></a>
							</th>
						</tr>
					</tfoot>
				</table>
			<?php endforeach; ?>
			<p><a class="button" href="<?php echo admin_url( 'post-new.php?post_type=part' ); ?>"><?php _e( 'Add Part', 'pressbooks' ); ?></a></p>
		<?php else : ?>
		<table id="<?php echo $type_slug; ?>" class="wp-list-table widefat fixed <?php echo $type_slug; ?>" cellspacing="0">
			<thead>
				<tr>
					<th><?php echo $type_name; ?></th>
					<th><?php _e( 'Author', 'pressbooks' ); ?></th>
					<?php if ( false === $disable_comments ) : ?><th><?php _e( 'Comments', 'pressbooks' ); ?></th><?php endif; ?>
					<th><?php _e( 'Status', 'pressbooks' ); ?></th>
					<th role="button"><?php _e( 'Private', 'pressbooks' ); ?></th>
					<th role="button"><?php _e( 'Show Title', 'pressbooks' ); ?></th>
					<th role="button"><?php _e( 'Export', 'pressbooks' ); ?></th>
				</tr>
			</thead>

			<tbody id="the-list">
			<?php foreach ( $book_structure[ $type_slug ] as $content ) : ?>
				<tr id="<?php echo $type_slug; ?>-<?php echo $content['ID']; ?>">
					<td class="title column-title has-row-actions">
						<div class="row-title"><a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>">
						<?php echo $content['post_title']; ?>
						<?php if ( get_post_meta( $content['ID'], 'pb_ebook_start', true ) ) { ?>
							<span class="ebook-start-point" title="<?php _e( 'Ebook start point', 'pressbooks' ); ?>">&#9733;</span>
						<?php } ?></a>
						<div class="row-actions">
							<a href="<?php echo admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ); ?>"><?php _e( 'Edit', 'pressbooks' ); ?></a> | <a class="delete-link" href="<?php echo get_delete_post_link( $content['ID'] ); ?>" onclick="if ( !confirm( '<?php _e( 'Are you sure you want to delete this?', 'pressbooks' ); ?>' ) ) { return false }"><?php _e( 'Trash', 'pressbooks' ); ?></a> | <a href="<?php echo get_permalink( $content['ID'] ); ?>"><?php _e( 'View', 'pressbooks' ); ?></a>
						</div>
						</div>
					</td>
					<td class="author column-author">
						<?php echo $content['post_author'] == $user_ID ? 'You' : get_userdata( $content['post_author'] )->display_name; // @codingStandardsIgnoreLine ?>
					</td>
					<?php if ( false === $disable_comments ) : ?><td class="comments column-comments">
						<a class="post-comment-count" href="<?php echo admin_url( 'edit-comments.php?p=' . $content['ID'] ); ?>">
							<span class="comment-count"><?php echo $content['comment_count']; ?></span>
						</a>
					</td><?php endif; ?>
					<td class="status column-status"><?php echo $statuses[ $content['post_status'] ]->label; ?></td>
					<td class="status column-privacy">
						<input class="<?php echo $type_abbr; ?>_privacy" type="checkbox" name="<?php echo $type_abbr; ?>-private[<?php echo $content['ID']; ?>]" id="<?php echo $type_abbr; ?>_private_<?php echo $content['ID']; ?>" <?php checked( 'private', get_post_status( $content['ID'] ) ); ?> />
					</td>
					<?php $export = get_post_meta( $content['ID'], 'pb_export', true ); ?>
					<td class="status column-showtitle">
						<input class="<?php echo $type_abbr; ?>_show_title_check" type="checkbox" name="<?php echo $type_abbr; ?>-showtitle[<?php echo $content['ID']; ?>]" id="<?php echo $type_abbr; ?>_show_title_<?php echo $content['ID']; ?>" <?php checked( get_post_meta( $content['ID'], 'pb_show_title', true ), 'on', true ); ?>/>
					</td>
					<td class="export column-export">
						<input class="<?php echo $type_abbr; ?>_export_check" type="checkbox" name="<?php echo $type_abbr; ?>-export[<?php echo $content['ID']; ?>]" id="<?php echo $type_abbr; ?>_export_<?php echo $content['ID']; ?>" <?php checked( get_post_meta( $content['ID'], 'pb_export', true ), 'on', true ); ?>/>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<?php if ( false === $disable_comments ) : ?><th>&nbsp;</th><?php endif; ?>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>
						<a href="<?php echo admin_url( 'post-new.php?post_type=' . $type_slug ); ?>" class="button"><?php _e( 'Add', 'pressbooks' ); ?> <?php echo $type_name; ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<?php endif; ?>
	<?php endforeach; ?>
</div>

<div id="loader" class="chapter">
	<p><img src="<?php echo PB_PLUGIN_URL; ?>assets/dist/images/loader.gif" alt="Loader" id="loaderimg" /></p>
	<h3 id="loadermsg"><?php _e( 'Reordering Chapters', 'pressbooks' ); ?>&hellip;</h3>
</div>

<div id="loader" class="fm">
	<p><img src="<?php echo PB_PLUGIN_URL; ?>assets/dist/images/loader.gif" alt="Loader" id="loaderimg" /></p>
	<h3 id="loadermsg"><?php _e( 'Reordering Front Matter', 'pressbooks' ); ?>&hellip;</h3>
</div>

<div id="loader" class="bm">
	<p><img src="<?php echo PB_PLUGIN_URL; ?>assets/dist/images/loader.gif" alt="Loader" id="loaderimg" /></p>
	<h3 id="loadermsg"><?php _e( 'Reordering Back Matter', 'pressbooks' ); ?>&hellip;</h3>
</div>
