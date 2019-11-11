<div class="wrap">
	<h1>{{ __( 'Bulk Add New User', 'user' ) }}</h1>
	<form method="POST" action="{{ $form_url }}" method="post">
		<?php echo wp_nonce_field( $nonce ); ?>
		<h2>{{ __('Bulk add users', 'user') }}</h2>

		<table class="form-table" role="none">
			<tr>
				<th><label for="provision">{{ __('Emails', 'user') }}</label></th>
				<td>
					<div class="form-field term-description-wrap">
						<textarea name="users" id="users" rows="5" cols="50" spellcheck="false"></textarea>
						<p>{{ __('One entry per line', 'user') }}</p>
					</div>
				</td>
			</tr>

			<tr class="form-field">
				<th scope="row"><label for="adduser-role"><?php _e( 'Role' ); ?></label></th>
				<td><select name="role" id="adduser-role">
						<?php wp_dropdown_roles( get_option( 'default_role' ) ); ?>
					</select>
				</td>
			</tr>

		</table>

		{!! get_submit_button() !!}
	</form>
</div>
