<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content of the Organize page for a book */

global $user_ID;
$adminUrl = admin_url();
$statuses = get_post_statuses();
$book_structure = \PressBooks\Book::getBookStructure();
?>

<style type="text/css">
    .widefat thead tr th {
        color: #676767;
    }

    tbody {
        margin-top: 10px;
    }
</style>


<div class="wrap">
<div id="icon-edit" class="icon32"></div>
<h2>
	<?php bloginfo( 'name' ); ?>
	<?php if ( is_super_admin() ): ?>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>edit.php?post_type=front-matter"><?php _e( 'Front Matter', 'pressbooks' ); ?></a>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>edit.php?post_type=chapter"><?php _e( 'Chapters', 'pressbooks' ); ?></a>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>edit.php?post_type=back-matter"><?php _e( 'Back Matter', 'pressbooks' ); ?></a>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>edit.php?post_type=part"><?php _e( 'Part', 'pressbooks' ); ?></a>
	<?php else: ?>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>admin.php?page=pb_export"><?php _e( 'Export', 'pressbooks' ); ?></a>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>post-new.php?post_type=front-matter"><?php _e( 'Add Front Matter', 'pressbooks' ); ?></a>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>post-new.php?post_type=back-matter"><?php _e( 'Add Back Matter', 'pressbooks' ); ?></a>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>post-new.php?post_type=chapter"><?php _e( 'Add Chapter', 'pressbooks' ); ?></a>
    <a class="button add-new-h2" href="<?php echo $adminUrl; ?>post-new.php?post_type=part"><?php _e( 'Add Part', 'pressbooks' ); ?></a>
	<?php endif; ?>
</h2>

<table id="front-matter" class="wp-list-table widefat fixed front-matter" cellspacing="0">
    <thead>
    <tr>
        <th><?php _e('Front Matter', 'pressbooks'); ?></th>
        <th><?php _e('Author', 'pressbooks'); ?></th>
        <th><?php _e('Comments', 'pressbooks'); ?></th>
        <th><?php _e('Status', 'pressbooks'); ?></th>
        <th>&nbsp;</th>
        <th><?php _e('Export', 'pressbooks'); ?></th>
    </tr>
    </thead>

    <tbody id="the-list">
	<?php foreach ( $book_structure['front-matter'] as $fm ): ?>
    <tr id="front-matter-<?php echo $fm['ID']; ?>">
        <td class="post-title page-title column-title">
            <strong><a href="<?php echo 'post.php?post=' . $fm['ID'] . '&action=edit'; ?>">
				<?php echo $fm['post_title']; ?></a>
            </strong>
        </td>
        <td class="author column-author">
			<?php echo $fm['post_author'] == $user_ID ? 'You' : get_userdata( $fm['post_author'] )->display_name; ?>
        </td>
        <td class="comments column-comments">
            <a class="post-com-count" href="<?php echo 'edit-comments.php?p=' . $fm['ID']; ?>">
          <span class="comment-count" style="width: auto !important;float: none;">
          <?php echo $fm['comment_count']; ?>
          </span>
            </a>
        </td>
        <td class="status column-status"><?php echo $statuses[$fm['post_status']]; ?></td>
        <td class="chapter-action column-chapter-action">
            <a href="<?php echo 'post.php?post=' . $fm['ID'] . '&action=edit'; ?>">
				<?php _e( 'Edit', 'pressbooks' ); ?>
            </a>
            &mdash;
            <a href="<?php echo get_delete_post_link( $fm['ID'] ); ?>" onclick="if (!confirm('Are you sure you want to delete this?')){ return false }"
               style="color: #FF0000 !important;">
				<?php _e( 'Delete', 'pressbooks' ); ?>
            </a>
        </td>
		<?php $export = get_post_meta( $fm['ID'], 'pb_export', true ); ?>
        <td class="export column-export">
			<?php $export = get_post_meta( $fm['ID'], 'pb_export', true );
			if ( $export ): ?>
                <span class="fm_export">
          <input class="fm_export_check" type="checkbox" name="fm-export[<?php echo $fm['ID']; ?>]" checked="checked" id="fm_export_<?php echo $fm['ID']; ?>" />
        </span>
				<?php else: ?>
                <span class="fm_export">
          <input class="fm_export_check" type="checkbox" name="fm-export[<?php echo $fm['ID']; ?>]" id="fm_export_<?php echo $fm['ID']; ?>" />
        </span>
				<?php endif; ?>

        </td>
    </tr>
		<?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th><a href="<?php echo $adminUrl; ?>post-new.php?post_type=front-matter" class="button"><?php _e('Add Front Matter', 'pressbooks'); ?></a>
        </th>
    </tr>
    </tfoot>

</table>

<?php foreach ( $book_structure['part'] as $part ): ?>

<table id="part-<?php echo $part['ID']; ?>" class="wp-list-table widefat fixed chapters" cellspacing="0">
    <thead>
    <tr>
        <th>
            <a href="<?php echo 'post.php?post=' . $part['ID'] . '&action=edit'; ?>"><?php echo $part['post_title']; ?></a>
        </th>
        <th><?php _e('Author', 'pressbooks'); ?></th>
        <th><?php _e('Comments', 'pressbooks'); ?></th>
        <th><?php _e('Status', 'pressbooks'); ?></th>
        <th>
            <a href="<?php echo 'post.php?post=' . $part['ID'] . '&action=edit'; ?>">
				<?php _e( 'Edit', 'pressbooks' ); ?>
            </a>
			<?php
			// don't allow deletion of last remaining part. Bad things happen.
			if ( count( $book_structure['part'] ) > 1 ): ?>
                &mdash;
                <a href="<?php echo get_delete_post_link( $part['ID'] ); ?>" onclick="if (!confirm('Are you sure you want to delete this?')){ return false }" style="color: #FF0000 !important;">
					<?php _e( 'Delete', 'pressbooks' ); ?>
                </a>
				<?php endif; ?>
        </th>

        <th><?php _e('Export', 'pressbooks'); ?></th>
    </tr>
    </thead>

	<?php if ( count( $part['chapters'] ) > 0 ): ?>
    <tbody id="the-list">
		<?php foreach ( $part['chapters'] as $chapter ): ?>

    <tr id="chapter-<?php echo $chapter['ID']; ?>">
        <td class="post-title page-title column-title">
            <strong>
                <a href="<?php echo 'post.php?post=' . $chapter['ID'] . '&action=edit'; ?>"><?php echo $chapter['post_title']; ?></a>
            </strong>
        </td>
        <!-- Include dragger image here - ../assets/imgages/dragger.png -->
        <td class="author column-author">
			<?php echo $chapter['post_author'] == $user_ID ? 'You' : get_userdata( $chapter['post_author'] )->display_name; ?>
        </td>
        <td class="comments column-comments">

            <a class="post-com-count" href="<?php echo 'edit-comments.php?p=' . $chapter['ID']; ?>">
        <span class="comment-count" style="width: auto !important;float: none;">
        <?php echo $chapter['comment_count']; ?>
            </a>
        </td>
        <td class="status column-status">
			<?php echo $statuses[$chapter['post_status']]; ?>
        </td>
        <td class="chapter-action column-chapter-action">
            <a href="<?php echo 'post.php?post=' . $chapter['ID'] . '&action=edit'; ?>">
				<?php _e( 'Edit', 'pressbooks' ); ?>
            </a> &mdash;
            <a href="<?php echo get_delete_post_link( $chapter['ID'] ); ?>" onclick="if (!confirm('Are you sure you want to delete this?')){ return false }" style="color: #FF0000 !important;">
				<?php _e( 'Delete', 'pressbooks' ); ?>
            </a>
        </td>
        <td class="export column-export">
			<?php $export = get_post_meta( $chapter['ID'], 'pb_export', true ); ?>
			<?php if ( $export ): ?>
            <input class="chapter_export_check" type="checkbox" name="export[<?php echo $chapter['ID']; ?>]" checked="checked" id="chapter_export_<?php echo $chapter['ID']; ?>" />
			<?php else: ?>
            <input class="chapter_export_check" type="checkbox" name="export[<?php echo $chapter['ID']; ?>]" id="chapter_export_<?php echo $chapter['ID']; ?>" />
			<?php endif; ?>
        </td>
    </tr>

		<?php endforeach; ?>
    </tbody>
	<?php endif; ?>
    <tfoot>
    <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>
            <a href="<?php echo $adminUrl; ?>post-new.php?post_type=chapter&amp;startparent=<?php echo $part['ID']; ?>" class="button"><?php _e('Add Chapter', 'pressbooks'); ?></a>
        </th>
    </tr>
    </tfoot>
</table>
	<?php endforeach; ?>
<p>
    <a class="button" href="<?php echo $adminUrl; ?>post-new.php?post_type=part"><?php _e( 'Add Part', 'pressbooks' ); ?></a>
</p>


<table id="back-matter" class="wp-list-table widefat fixed back-matter" cellspacing="0">
    <thead>
    <tr>
        <th><?php _e('Back Matter', 'pressbooks'); ?></th>
        <th><?php _e('Author', 'pressbooks'); ?></th>
        <th><?php _e('Comments', 'pressbooks'); ?></th>
        <th><?php _e('Status', 'pressbooks'); ?></th>
        <th>&nbsp;</th>
        <th><?php _e('Export', 'pressbooks'); ?></th>
    </tr>
    </thead>

    <tbody id="the-list">
	<?php foreach ( $book_structure['back-matter'] as $bm ): ?>
    <tr id="back-matter-<?php echo $bm['ID']; ?>">
        <td class="post-title page-title column-title">
            <strong><a href="<?php echo 'post.php?post=' . $bm['ID'] . '&action=edit'; ?>">
				<?php echo $bm['post_title']; ?></a>
            </strong>
        </td>
        <td class="author column-author">
			<?php echo $bm['post_author'] == $user_ID ? 'You' : get_userdata( $bm['post_author'] )->display_name; ?>
        </td>
        <td class="comments column-comments">
            <a class="post-com-count" href="<?php echo 'edit-comments.php?p=' . $bm['ID']; ?>">
          <span class="comment-count" style="width: auto !important;float: none;">
          <?php echo $bm['comment_count']; ?>
          </span>
            </a>
        </td>
        <td class="status column-status"><?php echo $statuses[$bm['post_status']]; ?></td>
        <td class="chapter-action column-chapter-action">
            <a href="<?php echo 'post.php?post=' . $bm['ID'] . '&action=edit'; ?>">
				<?php _e( 'Edit', 'pressbooks' ); ?>
            </a>
            &mdash;
            <a href="<?php echo get_delete_post_link( $bm['ID'] ); ?>" onclick="if (!confirm('Are you sure you want to delete this?')){ return false }"
               style="color: #FF0000 !important;">
				<?php _e( 'Delete', 'pressbooks' ); ?>
            </a>
        </td>
		<?php $export = get_post_meta( $bm['ID'], 'pb_export', true ); ?>
        <td class="export column-export">
			<?php $export = get_post_meta( $bm['ID'], 'pb_export', true );
			if ( $export ): ?>
                <span class="bm_export">
          <input class="bm_export_check" type="checkbox" name="bm-export[<?php echo $bm['ID']; ?>]" checked="checked" id="bm_export_<?php echo $bm['ID']; ?>" />
        </span>
				<?php else: ?>
                <span class="bm_export">
          <input class="bm_export_check" type="checkbox" name="bm-export[<?php echo $bm['ID']; ?>]" id="bm_export_<?php echo $bm['ID']; ?>" />
        </span>
				<?php endif; ?>

        </td>
    </tr>
		<?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th><a href="<?php echo $adminUrl; ?>post-new.php?post_type=back-matter" class="button"><?php _e('Add Back Matter', 'pressbooks'); ?></a></th>
    </tr>
    </tfoot>

</table>
</div>

<div id="loader">
    <br /><img src="<?php echo PB_PLUGIN_URL; ?>assets/images/loader.gif" alt="Loader" id="loaderimg" /><br /><br />

    <h3 id="loadermsg"><?php _e( 'Reordering the Chapters', 'pressbooks' ); ?>...</h3>
</div>
