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
  $selected = 'step1';
}
?>

<div class="wrap">
  <div id="icon-edit-pages" class="icon32"></div>
  <h2><?php bloginfo('name'); ?></h2>
  <div class="import-page">
    <?php if (( $chapters ) && $_GET['select_chapters'] == 'step1') { ?>
      <form id="selective-import" action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=pb_import&amp;select_chapters=step2" method="post"> 
        <table class="wp-list-table widefat">
          <thead>
            <tr>
              <th>Import</th>
              <th>Title</th>
              <th>Front Matter</th>
              <th>Chapters</th>
              <th>Back Matter</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($chapters['chapters'] as $key => $chapter) {
              echo "<tr><td><input type='checkbox' id='selective_import' name='chapters[$key][import]' value='1' checked=''></td>
                  <td>" . $key . "</td>
                  <td><input type='radio' name='chapters[$key][type]' value='front-matter'> </td>
                  <td><input type='radio' name='chapters[$key][type]' value='chapter' checked='checked'> </td>
                  <td><input type='radio' name='chapters[$key][type]' value='back-matter'> </td>
                    
                    </tr>";
            }
            ?>
          </tbody>

        </table>
        <input type="hidden" name="pressbooks_selective_import" value="step2">
        <input type="submit" name="pressbooks_select_chapters" value="<?= _e('Import', 'pressbooks') ?>" >
      </form>

    <?php } elseif (!isset($_GET['select_chapters'])) { ?> 

      <h3><?= _e('Import File', 'pressbooks') ?></h3>


      <form action="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=pb_import&amp;upload_file=yes" method="POST" enctype="multipart/form-data" class="upload_file">
        <fieldset>
          <legend><?php _e('Select chapters', 'pressbooks'); ?>:</legend>
          <input type="checkbox" id="select" name="pressbooks_selective_import" value="step1" <?php checked('step1', $selected, true); ?>/>
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


    <?php }; ?>
  </div>
  <div class="clear"></div>