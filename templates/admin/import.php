<?php

use Pressbooks\Cloner\Cloner;
use Pressbooks\Modules\Import\Epub\Epub201;
use Pressbooks\Modules\Import\Html\Xhtml;
use Pressbooks\Modules\Import\Odf\Odt;
use Pressbooks\Modules\Import\Ooxml\Docx;
use Pressbooks\Modules\Import\WordPress\Wxr;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$import_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/tools.php?page=pb_import&import=yes' ), 'pb-import' );
$import_revoke_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/tools.php?page=pb_import&revoke=yes' ), 'pb-revoke-import' );
$current_import = get_option( 'pressbooks_current_import' );
$custom_post_types = apply_filters( 'pb_import_custom_post_types', [] );
$html_type_of = Cloner::isEnabled() ? __( 'Web page or Pressbooks webbook (.html or URL)', 'pressbooks' ) : __( 'Web page (.html or URL)', 'pressbooks' );

/**
 * Allows users to append import options to the select field.
 *
 * @since 3.9.6
 *
 * @param array $value The list of current import options in select field.
 */
$import_option_types = apply_filters( 'pb_select_import_type', [
	Epub201::TYPE_OF => __( 'EPUB (.epub)', 'pressbooks' ),
	Docx::TYPE_OF => __( 'Microsoft Word (.docx)', 'pressbooks' ),
	Odt::TYPE_OF => __( 'OpenOffice (.odt)', 'pressbooks' ),
	Wxr::TYPE_OF => __( 'Pressbooks/WordPress XML (.wxr or .xml)', 'pressbooks' ),
	Xhtml::TYPE_OF => $html_type_of,
] );

?>
<div class="wrap">

	<h1><?php _e( 'Import', 'pressbooks' ); ?></h1>

	<?php if ( is_array( $current_import ) && isset( $current_import['file'] ) ) { ?>

	<!-- STEP 2: Import in progress -->

		<p><?php _e( 'Select content below for import into Pressbooks.', 'pressbooks' ); ?></p>
		<p><?php _e( 'Source:', 'pressbooks' ); ?> <code><?php echo basename( $current_import['file'] ); ?></code></p>

		<script type="text/javascript">
		// <![CDATA[
		jQuery(function ($) {
			// Power hover
			$('tr').not(':first').hover(
					function () {
						$(this).css('background', '#ffff99');
					},
					function () {
						$(this).css('background', '');
					}
			);
			// Power select
			$("#checkall").click(function() {
				$('td > :checkbox').prop('checked', this.checked);
			});
			// Abort import
			$('#abort_button').bind('click', function () {
				if (!confirm('<?php esc_attr_e( 'Are you sure you want to abort the import?', 'pressbooks' ); ?>')) {
					return false;
				}
				else {
					window.location.href = "<?php echo htmlspecialchars_decode( $import_revoke_url ); ?>";
					return false;
				}
			});
		});
		// ]]>
	</script>

	<form id="pb-import-form-step-2" action="<?php echo $import_form_url ?>" method="post">
		<?php $colspan = ! empty( $current_import['allow_parts'] ) ? 5 : 4; ?>
		<table class="wp-list-table widefat">
			<thead>
			<tr>
				<td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="checkall" /> <label for="checkall" class="screen-reader-text"><?php _e( 'Import', 'pressbooks' ); ?></label></th>
				<th><?php _e( 'Title', 'pressbooks' ); ?></th>
				<th style="width:10%;"><?php _e( 'Front Matter', 'pressbooks' ); ?></th>
				<th style="width:10%;"><?php _e( 'Chapter', 'pressbooks' ); ?></th>
				<?php if ( ! empty( $current_import['allow_parts'] ) ) {?>
				<th style="width:10%;"><?php _e( 'Part', 'pressbooks' ); ?></th>
				<?php } ?>
				<th style="width:10%;"><?php _e( 'Back Matter', 'pressbooks' ); ?></th>
				<th style="width:10%;"><?php _e( 'Glossary', 'pressbooks' ); ?></th>
				<?php if ( has_filter( 'pb_import_custom_post_types' ) ) { ?>
				<th style="width:10%;"><?php _e( 'Other', 'pressbooks' ); ?></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php
			$i = 1;
			foreach ( $current_import['chapters'] as $key => $chapter ) {
				?>
				<tr <?php if ( $i % 2 ) { echo 'class="alt"';} ?> >
					<td><input type='checkbox' id='selective_import_<?php echo $i; ?>' name='chapters[<?php echo $key; ?>][import]' value='1'></td>
					<?php if ( isset( $current_import['post_types'][ $key ] ) && 'metadata' == $current_import['post_types'][ $key ] ) { ?>
						<td><label for="selective_import_<?php echo $i; ?>"><em>(<?php echo __( 'Book Information', 'pressbooks' ); ?>)</em></label></td>
						<td colspan="<?php echo $colspan; ?>"><input type="hidden" name='chapters[<?php echo $key; ?>][type]' value="metadata" /></td>
					<?php }
					else { ?>
						<td><label for="selective_import_<?php echo $i; ?>"><?php echo $chapter; ?></label></td>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='front-matter' <?php checked( isset( $current_import['post_types'][ $key ] ) && 'front-matter' == $current_import['post_types'][ $key ] );?>></td>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='chapter' <?php checked( ! isset( $current_import['post_types'][ $key ] ) || 'chapter' == $current_import['post_types'][ $key ] );?>></td>
						<?php if ( ! empty( $current_import['allow_parts'] ) ) {?>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='part' <?php checked( isset( $current_import['post_types'][ $key ] ) && 'part' == $current_import['post_types'][ $key ] );?>></td>
						<?php } ?>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='back-matter' <?php checked( isset( $current_import['post_types'][ $key ] ) && 'back-matter' == $current_import['post_types'][ $key ] );?>></td>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='glossary' <?php checked( isset( $current_import['post_types'][ $key ] ) && 'glossary' == $current_import['post_types'][ $key ] );?>></td>
						<?php if ( has_filter( 'pb_import_custom_post_types' ) ) { ?>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='<?php echo $current_import['post_types'][ $key ] ?>' <?php checked( isset( $current_import['post_types'][ $key ] ) && in_array( $current_import['post_types'][ $key ], $custom_post_types ) );?>></td>
						<?php } ?>
					<?php } ?>
				</tr>
				<?php
				++$i;
			}
			?>
			</tbody>
		</table>

		<p><input type='checkbox' id='show_imports_in_web' name='show_imports_in_web' value='1'><label for="show_imports_in_web"> <?php _e( 'Show imported content in web', 'pressbooks' ); ?></label></p>

		<progress id="pb-sse-progressbar" max="100"></progress>
		<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info" aria-live="polite"></span></p>
		<p><?php
			submit_button( __( 'Import Selection', 'pressbooks' ), 'primary', 'submit', false );
			echo ' &nbsp; ';
			submit_button( __( 'Cancel', 'pressbooks' ), 'delete', 'abort_button', false );
		?></p>

	</form>

	<?php } else { ?>

		<!-- STEP 1: Start by uploading a file -->

		<script type="text/javascript">
			jQuery(function ($) {
				var pb_file = $('#pb-file');
				pb_file.find('[type="radio"]').on('change', function() {
					var pb_file_checked = pb_file.find('[type="radio"]:checked').val();
					if ( pb_file_checked === 'file' ) {
						$( '#import_http' ).val( '' ).attr( 'disabled', true);
						$( '#import_file' ).removeAttr( 'disabled' ).focus();
					} else if ( pb_file_checked === 'url' ) {
						$('#import_http').removeAttr('disabled').focus();
						$('#import_file').val('').attr('disabled', true);
					}
				});
			});
		</script>
		<p>
			<?php _e( 'Maximum file size:', 'pressbooks' );
			echo ' ' . \Pressbooks\Utility\file_upload_max_size(); ?>
		</p>

		<form id="pb-import-form-step-1" action="<?php echo $import_form_url ?>" enctype="multipart/form-data" method="post">

			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="type_of"><?php _e( 'Import Type', 'pressbooks' ); ?></label>
					</th>
					<td>
						<select id="type_of" name="type_of">
							<?php foreach ( $import_option_types as $option => $label ) { ?>
								<option value="<?php echo $option; ?>"><?php echo $label; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr class="pb-input-types">
					<th scope="row">
						<label for="import_file"><?php _e( 'Import Source', 'pressbooks' ); ?></label>
					</th>
					<td id="pb-file">
						<fieldset>
							<input id="r1" name="import_type" type="radio" value="file" checked="checked"/>
							<label for="r1">Upload File </label>
							<input type="file" name="import_file" id="import_file" style="display:block;"/>
						</fieldset>
						<fieldset>
							<input id="r2" type="radio" name="import_type" value="url"/>
							<label for="r2">Import from URL</label>
							<input type="url" class="widefat" name="import_http" id="import_http" placeholder="https://url-to-import.com" style="display:block;" disabled/>
						</fieldset>
					</td>
				</tr>

				</tbody>
			</table>

			<?php submit_button( __( 'Begin Import', 'pressbooks' ) ); ?>
		</form>

	<?php } ?>

</div>
