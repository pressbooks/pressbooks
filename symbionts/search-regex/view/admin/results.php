<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>
<div class="wrap">
	<h2><?php _e ('Results', 'pressbooks') ?></h2>

	<?php if (count ($results) > 0) : ?>
	  <p><?php printf (__('%1$s result(s) found.', 'pressbooks'), count ($results)); ?></p>

		<ol class="results">
		<?php foreach ($results AS $pos => $result) : ?>
			<li id="search_<?php echo $result->id.'_'.$result->offset ?>"<?php if ($pos % 2 == 1) echo ' class="alt"' ?>>

				<div id="options_<?php echo $result->id.'_'.$result->offset ?>" class="options"><?php echo implode (' | ', $search->get_options ($result)); ?></div>

				<?php $search->show ($result); ?>

				<div class="searchx" id="value_<?php echo $result->id.'_'.$result->offset ?>"><?php echo $result->search ?></div>

				<?php if ($result->replace) : ?>
					<?php _e ('replaced with:', 'pressbooks') ?>
					<div class="replacex" id="replace_<?php echo $result->id.'_'.$result->offset ?>"><?php echo $result->replace ?></div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ol>

		<img src="<?php echo plugins_url( '/images/small.gif', $this->base_url() ); ?>" style="display: none" alt="pre"/>

		<script type="text/javascript" charset="utf-8">
			var re_text = new Array (), re_input = new Array (), re_replace = new Array (), re_text_replace = new Array ();

			<?php foreach ($results AS $result) : ?>
<?php
	$id    = $result->id.'_'.$result->offset;
	$edit  = '<br/><input type="submit" name="save" value="'.__( 'Save','pressbooks' ).'" onclick="save_edit(\\\''.get_class ($search).'\\\','.$result->id.','.$result->offset.','.$result->left.','.$result->left_length.');return false"/>';
	$rep   = '<br/><input type="submit" name="save" value="'.__( 'Save','pressbooks' ).'" onclick="save_edit_rep(\\\''.get_class ($search).'\\\','.$result->id.','.$result->offset.','.$result->left.','.$result->left_length.');return false"/>';
?>

re_text['<?php echo $id ?>'] = '<?php echo $result->for_js ($result->search); ?>';

<?php if ($result->single_line ()) : ?>
	re_input['<?php echo $id ?>'] = '<input id="txt_<?php echo $id ?>" style="width: 95%" type="text" name="replace" value="<?php echo $result->for_js ($result->search_plain); ?>"/><?php echo $edit ?>';
<?php else : ?>
	re_input['<?php echo $id ?>'] = '<textarea id="txt_<?php echo $id ?>" style="width: 95%" rows="2" name="replace"><?php echo $result->for_js ($result->search_plain); ?><\/textarea><?php echo $edit ?>';
<?php endif; ?>

<?php if ($result->replace) : ?>
	re_text_replace['<?php echo $id ?>'] = '<?php echo $result->for_js ($result->replace); ?>';
	<?php if ($result->single_line ()) : ?>
	re_replace['<?php echo $id ?>'] = '<input id="rep_<?php echo $id ?>" style="width: 95%" type="text" name="replace" value="<?php echo $result->for_js ($result->replace_plain); ?>"/><?php echo $rep ?>';
	<?php else : ?>
	re_replace['<?php echo $id ?>'] = '<textarea id="rep_<?php echo $id ?>" style="width: 95%" rows="2" name="replace"><?php echo $result->for_js ($result->replace_plain); ?><\/textarea><?php echo $rep ?>';
	<?php endif; ?>
<?php endif; ?>

			<?php endforeach; ?>
		</script>

	<?php else : ?>
	<p><?php _e ('There are no results.', 'pressbooks') ?></p>
	<?php endif; ?>
</div>
