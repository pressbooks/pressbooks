<?php
/* Outputs the content for the Import page for a book */


if (!empty($_GET['import_error'])) {
  printf('<div class="error">Warning: There was a problem with the import. See logs for more details.</div>');
}
?>
<?php
if (false == get_option('pressbooks_selective_import')) {
  add_option('pressbooks_selective_import');
}
if (false == get_option('pressbooks_selective_import_chapters')) {
  add_option('pressbooks_selective_import_chapters');
}

$selected = get_option('pressbooks_selective_import');
$chapters = get_option('pressbooks_selective_import_chapters');

if (!isset($selected)) {
  $selected = 1;
}
?>

<div class="wrap">
  <div id="icon-edit-pages" class="icon32"></div>
  <h2><?php bloginfo('name'); ?></h2>

  <?php if (( $chapters ) && $_GET['select_chapters'] == 'step1') { ?>
    <div class="input-wrap">
      <ul>
        <?php
        foreach ($chapters as $chapter) {
          echo "<li>" . $chapter . "</li>";
        }
        ?>
      </ul>
    </div>

  <?php } elseif( !isset( $_GET['select_chapters'] )) { ?> 
    <div class="import-page">
      <h3><?= _e('Import File', 'pressbooks') ?></h3>

      <div class="import-format-wrap">

        <form action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=pb_import&amp;upload_file=yes" method="POST" enctype="multipart/form-data" class="upload_file">
          <fieldset>
            <legend><?php _e('Select chapters', 'pressbooks'); ?>:</legend>
            <input type="checkbox" id="select" name="pressbooks_selective_import" value="1" <?php checked(1, $selected, true); ?>/>
            <label for="select"> <?php _e('Import only the chapters that I select', 'pressbooks'); ?></label>
          </fieldset>
  <?php if (isset($_GET['import_error']) && $_GET['import_error'] == 'filetype'): ?>
            <div class="input-wrap">
              <span class="error"><?= _e('The file type you tried to upload is not yet supported.', 'pressbooks') ?></span>
            </div>
  <?php endif; ?>
          <input type="file" name="import_file" id="import_file">
          <input type="submit" name="Submit" value="<?= _e('Upload', 'pressbooks') ?>" class="file-submit">
        </form>

      </div>
<?php }; ?>
    <div class="clear"></div>