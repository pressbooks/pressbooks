<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

// $importer = new \PressBooks\Import\Wordpress\Wxr();
// $importer->abortCurrentImport();

$import_form_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_import&import=yes', 'pb-import' );
$current_import = get_option( 'pressbooks_current_import' );

?>
<div class="wrap">

	<div id="icon-themes" class="icon32"></div>
	<h2><?php _e( 'Import', 'pressbooks' ); ?></h2>

	<?php if ( is_array( $current_import ) && isset( $current_import['file'] ) ) { ?>

	<!-- Import in progress -->

		<p><?php printf( __('Ready to import %s', 'pressbooks') , basename( $current_import['file'] ) ); ?></p>

		<script type="text/javascript">
		// <![CDATA[
		jQuery(function () {
			jQuery('.checkall').on('click', function () {
				jQuery(':checkbox').prop('checked', true);
			});
			jQuery('#abort_button').bind('click', function() {
				if (!confirm('<?php esc_attr_e('TODO: Are you sure you want to abort the import?', 'pressbooks'); ?>'))
					return false;
			});
		});
		// ]]>
	</script>
	<p><a href="javascript:;" class="checkall button">Select All</a></p>

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
			submit_button( __( 'Start The Import', 'pressbooks' ), 'primary', 'submit', false );
			echo " "; // Space
			submit_button( __( 'Abort', 'pressbooks' ), 'delete', 'abort_button', false );
		?></p>

	</form>

	<?php } else { ?>

		<!-- Start by uploading a file -->

		<p>Todo: Some other text goes here...</p>

		<form id="pb-import-form" action="<?php echo $import_form_url ?>" enctype="multipart/form-data" method="post">

			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="type_of">Type of</label>
					</th>
					<td>
						<select id="type_of" name="type_of">
							<option value="wxr">WXR</option>
							<option value="epub">EPUB</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="import_file">File</label>
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