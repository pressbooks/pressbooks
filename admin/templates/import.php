<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

$import_form_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=pb_import&import=yes', 'pb-import' );
$import_revoke_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=pb_import&revoke=yes', 'pb-revoke-import' );
$current_import = get_option( 'pressbooks_current_import' );

?>
<div class="wrap">

	<div id="icon-themes" class="icon32"></div>
	<h2><?php _e( 'Import', 'pressbooks' ); ?></h2>

	<?php if ( is_array( $current_import ) && isset( $current_import['file'] ) ) { ?>

	<!-- Import in progress -->

		<p><?php printf( __('Import in progress: %s', 'pressbooks') , basename( $current_import['file'] ) ); ?></p>

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
				$(':checkbox').prop('checked', this.checked);
			});
			// Abort import
			$('#abort_button').bind('click', function () {
				if (!confirm('<?php esc_attr_e('Are you sure you want to abort the import?', 'pressbooks'); ?>')) {
					return false;
				}
				else {
					window.location.href = "<?php echo htmlspecialchars_decode($import_revoke_url); ?>";
					return false;
				}
			});
		});
		// ]]>
	</script>

	<form id="pb-import-form" action="<?php echo $import_form_url ?>" method="post">

		<table class="wp-list-table widefat">
			<thead>
			<tr>
				<th style="width:10%;"><?php _e( 'Import', 'pressbooks' ); ?></th>
				<th><?php _e( 'Title', 'pressbooks' ); ?></th>
				<th style="width:10%;"><?php _e( 'Front Matter', 'pressbooks' ); ?></th>
				<th style="width:10%;"><?php _e( 'Chapter', 'pressbooks' ); ?></th>
				<th style="width:10%;"><?php _e( 'Back Matter', 'pressbooks' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td><input type="checkbox" id="checkall" /></td>
				<td colspan="4" style="color:darkred;"><label for="checkall">Select all</label></td>
			</tr>
			<?php
			$i = 1;
			foreach ( $current_import['chapters'] as $key => $chapter ) {
				?>
				<tr <?php if ( $i % 2 ) echo 'class="alt"'; ?> >
					<td><input type='checkbox' id='selective_import_<?php echo $i; ?>' name='chapters[<?php echo $key; ?>][import]' value='1'></td>
					<td><label for="selective_import_<?php echo $i; ?>"><?php echo $chapter; ?></label></td>
					<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='front-matter'></td>
					<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='chapter' checked='checked'></td>
					<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='back-matter'></td>
				</tr>
				<?php
				++$i;
			}
			?>
			</tbody>
		</table>

		<p><?php
			submit_button( __( 'Start', 'pressbooks' ), 'primary', 'submit', false );
			echo " &nbsp; "; // Space
			submit_button( __( 'Cancel', 'pressbooks' ), 'delete', 'abort_button', false );
		?></p>

	</form>

	<?php } else { ?>

		<!-- Start by uploading a file -->

		<p>
			<?php _e( 'Supported file extensions:', 'pressbooks' ); ?> XML, EPUB, ODT <br />
			<?php _e( 'Maximum file size:', 'pressbooks' ); echo ' ' . ini_get( 'upload_max_filesize' ); ?>
		</p>

		<form id="pb-import-form" action="<?php echo $import_form_url ?>" enctype="multipart/form-data" method="post">

			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="type_of"><?php _e( 'Type of file', 'pressbooks' ); ?></label>
					</th>
					<td>
						<select id="type_of" name="type_of">
							<option value="wxr"><?php _e( 'WXR (WordPress eXtended RSS)', 'pressbooks' ); ?></option>
							<option value="epub"><?php _e( 'EPUB (for Nook, iBooks, Kobo etc.)', 'pressbooks' ); ?></option>
							<option value="odt"><?php _e( 'ODT (word processing file format of OpenDocument)', 'pressbooks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="import_file"><?php _e( 'File', 'pressbooks' ); ?></label>
					</th>
					<td>
						<input type="file" name="import_file" id="import_file">
					</td>
				</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Upload file', 'pressbooks' ) ); ?>
		</form>

	<?php } ?>

</div>