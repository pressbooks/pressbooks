<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Catalog;


/**
 * Add My Catalog menu.
 */
function add_menu() {

	add_submenu_page( 'index.php', __( 'My Catalog', 'pressbooks' ), __( 'My Catalog', 'pressbooks' ), 'read', 'catalog', __NAMESPACE__ . '\display_catalog_page' );
	add_submenu_page( 'index.php', __( 'My Catalog (WIP)', 'pressbooks' ), __( 'My Catalog (WIP)', 'pressbooks' ), 'read', 'catalog_wip', __NAMESPACE__ . '\render_list_page' );
}


/**
 * Displays catalog administration page.
 */
function display_catalog_page() {

	$vars = array();

	// Are we editing our own catalog or another users?
	if ( isset( $_REQUEST['user_id'] ) && current_user_can( 'edit_user', (int) $_REQUEST['user_id'] ) ) {
		$vars['user_id'] = (int) $_REQUEST['user_id'];
	}

	load_catalog_template( $vars );
}


/**
 * Simple templating function.
 *
 * @param array $vars
 */
function load_catalog_template( $vars ) {

	extract( $vars );
	require( PB_PLUGIN_DIR . 'admin/templates/catalog.php' );
}


/**
 * TODO
 */
function render_list_page() {

	// Create an instance of our package class...
	$testListTable = new \PressBooks\Catalog_List_Table();

	// Fetch, prepare, sort, and filter our data...
	$testListTable->prepare_items();

	?>
	<div class="wrap">

		<div id="icon-users" class="icon32"><br /></div>
		<h2>List Table Test</h2>

		<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
		<form id="books-filter" method="get">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<!-- Now we can render the completed list table -->
			<?php $testListTable->display() ?>
		</form>

	</div>
<?php
}