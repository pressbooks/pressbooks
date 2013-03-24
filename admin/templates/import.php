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
<h3><?=__('Import Epub')?></h3>

<div class="import-format-wrap">

    <form action="<?php bloginfo( 'url' ); ?>/wp-admin/admin.php?page=pb_import&amp;upload_epub=yes" method="POST" enctype="multipart/form-data" class="upload_epub">
		<?php if ( isset( $_GET['import_error'] ) && $_GET['import_error'] == 'filetype' ): ?>
        <div class="input-wrap">
            <span class="error"><?=__('You must upload a Epub file.')?></span>
        </div>
		<?php endif; ?>
        <input type="file" name="epub_file" id="epub_file">

        <input type="submit" name="Submit" value="<?=__('Upload')?>" class="epub-submit">
    </form>

</div>
<div class="clear"></div>