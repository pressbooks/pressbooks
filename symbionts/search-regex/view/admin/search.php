<?php if (!defined( 'ABSPATH' )) die( 'No direct access allowed' ); ?>
<div class="wrap">
	<h2><?php _e( 'Search and Replace', 'pressbooks' ) ?></h2>
	<p><?php _e( 'Search and replace will replace ALL instances in the entire book.', 'pressbooks' ); ?></p>
	<p><?php _e( 'Replacements will only be saved if you click \'<strong>Replace &amp; Save</strong>\', otherwise you will ONLY get a preview of the results.', 'pressbooks'  ) ?></p>
	<p><?php _e( 'Be careful replacing text. There is no revert button. However, you can revert using the Revision History, chapter by chapter.', 'pressbooks'  ) ?></p>

	<form method="post" action="">
		<table class="searchargs">
		  <tr>
		    <th width="150"><?php _e( "Limit to", 'pressbooks'  ) ?></th>
		    <td>
					<?php $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0 ?>
					<select name="limit">
						<?php echo $this->select( array( '0' => __( 'No limit', 'pressbooks' ), '10' => '10', '25' => '25', '50' => '50', '100' => '100' ), $limit ) ?>
					</select>

					<strong><?php _e( 'Order By', 'pressbooks' ); ?>:</strong>
					<?php $orderby = isset( $_POST['orderby'] ) ? $_POST['orderby'] : ''; ?>
					<select name="orderby">
						<?php echo $this->select( array( 'asc' => __( 'Ascending', 'pressbooks' ), 'desc' => __( 'Descending', 'pressbooks' ) ), $orderby ); ?>
					</select>
				</td>
			</tr>
			<tr>
				<th width="150" valign="top"><?php _e( "Search string", 'pressbooks'  ) ?></th>
				<td>
				  <input class="term" type="text" name="search_pattern" value="<?php esc_attr_e( $search ) ?>"/><br/>
				</td>
			</tr>
			<tr>
			  <th width="150" valign="top"><?php _e( 'Replace string', 'pressbooks' ) ?></th>
				<td>
				  <input class="term" type="text" name="replace_pattern" value="<?php esc_attr_e( $replace ) ?>"/><br/>
				</td>
			</tr>
			<?php if ( is_super_admin() ) { ?>
			<tr>
				<th><label for="regex"><?php _e( 'Regex', 'pressbooks' ); ?>:</label></th>
				<td>
					<input id="regex" type="checkbox" value="regex" name="regex"<?php if (isset ($_POST['regex'])) echo 'checked="checked"' ?>/>
			  	<span id="regex-options" <?php if (!isset ($_POST['regex'])) : ?>style="display: none"<?php endif; ?> class="sub">
						<label for="case"><?php _e( 'case-insensitive:', 'pressbooks' ) ?></label> <input id="case" type="checkbox" name="regex_case" value="caseless"<?php if (isset ($_POST['regex_case'])) echo 'checked="checked"' ?>/>
				  	<label for="multi"><?php _e( 'multi-line:', 'pressbooks' ) ?></label> <input id="multi" type="checkbox" name="regex_multi" value="multiline"<?php if (isset ($_POST['regex_multi'])) echo 'checked="checked"' ?>/>
				  	<label for="dotall"><?php _e( 'dot-all:', 'pressbooks' ) ?></label> <input id="dotall" type="checkbox" name="regex_dot" value="dotall"<?php if (isset ($_POST['regex_dot'])) echo 'checked="checked"' ?>/>
						&mdash; <?php _e( 'remember to surround your regex with a delimiter!', 'pressbooks' ); ?>
					</span>
			  </td>
			</tr>
			<?php } ?>
			<tr>
			  <th width="150"></th>
				<td><p class="submit">
	      	<input class="button" type="submit" name="search" id="button_search" value="<?php esc_attr_e( 'Search', 'pressbooks' )?> &raquo;" />

					<?php if (current_user_can( 'administrator' ) || current_user_can( 'search_regex_write' )) : ?>
					<input class="button" type="submit" name="replace" id="button_replace" value="<?php esc_attr_e( 'Preview Replacements', 'pressbooks' )?> &raquo;" />
					<input class="button" type="button" name="replace_and_save" onClick="confSubmit(this.form);" id="button_replace_and_save" value="<?php esc_attr_e( 'Replace &amp; Save &raquo;', 'pressbooks' ) ?>"/>
					<div id="searcharguments"></div>
					<?php endif; ?>
	    		</p>
				</td>
			</tr>
		</table>
	</form>
</div>

<script type="text/javascript" charset="utf-8">
	var wp_loading = '<?php echo plugins_url( '/images/small.gif', $this->base_url() ); ?>';
</script>
