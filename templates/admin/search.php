<?php if ( !defined ('ABSPATH') ) die(); ?>
<div class="wrap">
	<h1><?php _e ('Search & Replace', 'pressbooks') ?></h1>
	<p><?php _e( 'Search & Replace will find and replace ALL instances of the search pattern in your entire book. Replacements will only be saved if you click &lsquo;<strong>Replace &amp; Save</strong>&rsquo;.', 'pressbooks'  ) ?></p>
	<p><?php _e( 'Be careful replacing text. There is no undo button. However, you can revert your changes using the Revision History within each chapter, front matter or back matter.', 'pressbooks'  ) ?></p>
	<form id="search-form" method="post" action="">
		<table class="form-table search-form">
			<tr>
				<th scope="row"><?php _e( 'Search Within', 'pressbooks' ); ?>:</th>
				<td>
					<select name="source">
						<?php foreach ( $searches as $search_type ) : ?>
                        	<option value="<?php echo get_class( $search_type ) ?>" <?php selected( stripslashes( @$_POST['source'] ), get_class( $search_type ) ); ?>/><?php echo esc_attr( $search_type->name() ) ?></option>
						<?php endforeach; ?>
        			</fieldset>
				</td>
			</tr>
			<?php /* <tr>
				<th scope="row"><?php _e( 'Result Limit', 'pressbooks' ); ?>:</th>
				<td>
					<?php $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0 ?>
					<select name="limit">
						<option <?php selected( $limit, 0 ); ?> value="0"><?php _e( 'None', 'pressbooks' ); ?></option>
						<option <?php selected( $limit, 10 ); ?> value="10"><?php _e( '10', 'pressbooks' ); ?></option>
						<option <?php selected( $limit, 25 ); ?> value="25"><?php _e( '25', 'pressbooks' ); ?></option>
						<option <?php selected( $limit, 50 ); ?> value="50"><?php _e( '50', 'pressbooks' ); ?></option>
						<option <?php selected( $limit, 100 ); ?> value="100"><?php _e( '100', 'pressbooks' ); ?></option>
					</select>
				</td>
			</tr> */ ?>
			<tr>
				<th scope="row"><?php _e( 'Result Order', 'pressbooks' ); ?>:</th>
				<td>
					<?php $orderby = isset( $_POST['orderby'] ) ? $_POST['orderby'] : ''; ?>
					<select name="orderby">
						<option <?php selected( $orderby, 'asc' ); ?>value="asc"><?php _e( 'Ascending', 'pressbooks' ); ?></option>
						<option <?php selected( $orderby, 'desc' ); ?>value="desc"><?php _e( 'Descending', 'pressbooks' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( "Search For", 'pressbooks' ) ?>:</th>
				<td>
				  <input class="term" type="text" name="search_pattern" value="<?php esc_attr_e( $search ); ?>"/><br/>
				</td>
			</tr>
			<tr>
			  <th scope="row"><?php _e( 'Replace With', 'pressbooks' ) ?>:</th>
				<td>
				  <input class="term" type="text" name="replace_pattern" value="<?php esc_attr_e( $replace ) ?>"/><br/>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'search', 'pressbooks-search-and-replace-nonce' ); ?>
		<p class="submit">
			<input type="submit" class="button button-primary" name="search" value="<?php esc_attr_e( 'Search', 'pressbooks' )?>" />

			<?php if ( current_user_can( 'administrator' ) ) : ?>
				<input type="submit" class="button" name="replace" value="<?php esc_attr_e( 'Preview Replacements', 'pressbooks' )?>" />
				<input type="button" class="button" onClick="confirmSubmit(this.form);" value="<?php esc_attr_e( 'Replace &amp; Save', 'pressbooks' ) ?>"/>
			<?php endif; ?>
		</p>
	</form>
</div>
