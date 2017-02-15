<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$import_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/tools.php?page=pb_import&import=yes' ), 'pb-import' );
$import_revoke_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/tools.php?page=pb_import&revoke=yes' ), 'pb-revoke-import' );
$current_import = get_option( 'pressbooks_current_import' );
$custom_post_types = apply_filters( 'pb_import_custom_post_types', array() );

/**
 * Allows users to append import options to the select field.
 *
 * @since 3.9.6
 *
 * @param array The list of current import options in select field.
 */
$import_option_types = apply_filters( 'pb_select_import_type', array(
	'wxr' => __( 'WXR (WordPress eXtended RSS)' ),
	'epub' => __( 'EPUB (for Nook, iBooks, Kobo etc.)' ),
	'odt' => __( 'ODT (word processing file format of OpenDocument)' ),
	'docx' => __( 'DOCX (word processing file format of Microsoft)' ),
	'html' => __( 'HTML (scrape content from a URL)' ),
) );

?>
<div class="wrap">

	<h1><?php _e( 'Import', 'pressbooks' ); ?></h1>

	<?php if ( is_array( $current_import ) && isset( $current_import['file'] ) ) { ?>

	<!-- Import in progress -->

		<p><?php printf( __( 'Import in progress: %s', 'pressbooks' ) , basename( $current_import['file'] ) ); ?></p>

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

	<form id="pb-import-form" action="<?php echo $import_form_url ?>" method="post">
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
					<?php } else { ?>
						<td><label for="selective_import_<?php echo $i; ?>"><?php echo $chapter; ?></label></td>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='front-matter' <?php checked( isset( $current_import['post_types'][ $key ] ) && 'front-matter' == $current_import['post_types'][ $key ] );?>></td>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='chapter' <?php checked( ! isset( $current_import['post_types'][ $key ] ) || 'chapter' == $current_import['post_types'][ $key ] );?>></td>
						<?php if ( ! empty( $current_import['allow_parts'] ) ) {?>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='part' <?php checked( isset( $current_import['post_types'][ $key ] ) && 'part' == $current_import['post_types'][ $key ] );?>></td>
						<?php } ?>
						<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='back-matter' <?php checked( isset( $current_import['post_types'][ $key ] ) && 'back-matter' == $current_import['post_types'][ $key ] );?>></td>
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

		<p><input type='checkbox' id='import_as_drafts' name='import_as_drafts' value='1' checked><label for="import_as_drafts"> <?php _e( 'Import as drafts', 'pressbooks' ); ?></label></p>

		<p><?php
			submit_button( __( 'Import Selection', 'pressbooks' ), 'primary', 'submit', false );
			echo ' &nbsp; ';
			submit_button( __( 'Cancel', 'pressbooks' ), 'delete', 'abort_button', false );
		?></p>

	</form>

	<?php } else { ?>

		<!-- Start by uploading a file -->

		<script type="text/javascript">
			jQuery(function ($) {
				$('#pb-www').hide();

				$( ".pb-html-target").change(
					function(){
						var val = $('.pb-html-target').val();

							if (val == 'html') {
							$('#pb-file').hide();
							$('#pb-www').show();
						} else {
							$('#pb-file').show();
							$('#pb-www').hide();
							// clear http value at input elem
							$('.widefat').val('');

						}

					});

			});
			</script>
		<p>
			<?php _e( 'Supported file extensions:', 'pressbooks' ); ?> XML, EPUB, ODT, DOCX, HTML <br />
			<?php _e( 'Maximum file size:', 'pressbooks' );
			echo ' ' . \Pressbooks\Utility\file_upload_max_size(); ?>
		</p>

		<form id="pb-import-form" action="<?php echo $import_form_url ?>" enctype="multipart/form-data" method="post">

			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="type_of"><?php _e( 'Type of file', 'pressbooks' ); ?></label>
					</th>
					<td>
						<select id="type_of" name="type_of" class="pb-html-target">
							<?php foreach ( $import_option_types as $option => $label ) { ?>
								<option value="<?php echo $option; ?>"><?php echo $label; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="import_file"><?php _e( 'File', 'pressbooks' ); ?></label>
					</th>
					<td id="pb-file">
						<input type="file" name="import_file" id="import_file">
					</td>
					<td id="pb-www">
						<input type="text" class="widefat" name="import_html" id="import_html" placeholder="http://url-of-the-html-page-to-import.html">
					</td>
				</tr>

				</tbody>
			</table>

			<?php submit_button( __( 'Upload file', 'pressbooks' ) ); ?>
		</form>

	<?php } ?>

</div>
