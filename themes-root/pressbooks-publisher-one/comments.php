<?php

  /**
  *@desc Included at the bottom of post.php and single.php, deals with all comment layout
  */

  if ( !empty($post->post_password) && $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) :
?>	
<p><?php _e('Enter password.', 'pressbooks'); ?></p>
<?php return; endif; ?>

		<h5 id="comments"><?php comments_popup_link(__('0 comments','pressbooks'), __('1 comment', 'pressbooks'), __('% comments', 'pressbooks'), 'comments-link', false); ?>
		
			<?php if ( comments_open() ) : ?>
			<a href="#postcomment" title="<?php _e('Leave a comment', 'pressbooks'); ?>">&raquo;</a>
			<?php endif; ?>
		</h5>
		<?php if ( $comments ) : ?>
			<ol id="commentlist">
				<?php wp_list_comments(); ?> 
				<?php foreach ($comments as $comment) : ?>
				<?php endforeach; ?>
			</ol>
<?php paginate_comments_links() ?>

<?php endif; ?>


<?php if ( comments_open() ) : ?>


<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
<p><?php printf(__('You must be <a href="%s">logged in</a> to post a comment.', 'pressbooks' ), get_option('siteurl')."/wp-login.php?redirect_to=".urlencode(get_permalink()));?></p>
<?php else : ?>

<?php comment_form(); ?>

<?php endif; // If registration required and not logged in ?>

<?php else : // Comments are closed ?>
<p class="comments-off"><?php _e('Sorry, comments are closed.', 'pressbooks'); ?></p>
<?php endif; ?>
