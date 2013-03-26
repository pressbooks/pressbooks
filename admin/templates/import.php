<?php
/* Outputs the content for the Import page for a book */


if ( ! empty( $_GET['import_error'] ) ) {
	printf('<div class="error">Warning: There was a problem with the import. See logs for more details.</div>');
}
?>


<div class="wrap">
<div id="icon-edit-pages" class="icon32"></div>
<h2><?php bloginfo( 'name' ); ?></h2>

<div class="import-page">
<h3><?=__('Import File')?></h3>

<div class="import-format-wrap">

    <form action="<?php bloginfo( 'url' ); ?>/wp-admin/admin.php?page=pb_import&amp;upload_file=yes" method="POST" enctype="multipart/form-data" class="upload_file">
		<?php if ( isset( $_GET['import_error'] ) && $_GET['import_error'] == 'filetype' ): ?>
        <div class="input-wrap">
            <span class="error"><?=__('The file type you tried to upload is not yet supported.')?></span>
        </div>
		<?php endif; ?>
        <input type="file" name="import_file" id="import_file">

        <input type="submit" name="Submit" value="<?=__('Upload')?>" class="file-submit">
    </form>

</div>
<div class="clear"></div>