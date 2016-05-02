<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin;

use \Pressbooks\Catalog;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * @see http://codex.wordpress.org/Class_Reference/WP_List_Table
 */
class Catalog_List_Table extends \WP_List_Table {


	// ----------------------------------------------------------------------------------------------------------------
	// WordPress Overrides
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Constructor, must call parent
	 */
	function __construct() {

		$args = array(
			'singular' => 'book',
			'plural' => 'books', // Parent will create bulk nonce: "bulk-{$plural}"
			'ajax' => true,
		);
		parent::__construct( $args );
	}


	/**
	 * This method is called when the parent class can't find a method
	 * for a given column. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists. If it doesn't this one will be used.
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param object $item A singular item (one full row's worth of data)
	 * @param string $column_name The name/slug of the column to be processed
	 *
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	function column_default( $item, $column_name ) {

		if ( preg_match( '/^tag_\d+$/', $column_name ) ) {
			return $this->renderTagColumn( $item, $column_name );
		}

		return esc_html( $item[$column_name] );
	}


	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_title( $item ) {

		list( $user_id, $blog_id ) = explode( ':', $item['ID'] );

		// Build row actions
		$actions = array(
			'visit' => sprintf( '<a href="%s">%s</a>', get_site_url( $blog_id ), __( 'Visit Book' ) )
		);
		
		// Only include admin link if user has admin rights to the book in question
		if ( is_super_admin( $user_id ) || is_user_member_of_blog( $user_id, $blog_id ) )
			$actions['dashboard'] = sprintf( '<a href="%s">%s</a>', get_admin_url( $blog_id ), __( 'Visit Admin', 'pressbooks' ) );

		// Return the title contents
		return sprintf( '<span class="title">%1$s</span> %2$s',
			/*$1%s*/
			$item['title'],
			/*$2%s*/
			$this->row_actions( $actions )
		);
	}


	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_status( $item ) {

		$add_url = sprintf( get_admin_url() . 'index.php?page=%s&action=%s&ID=%s', $_REQUEST['page'], 'add', $item['ID'] );
		$add_url = esc_url( add_query_arg( '_wpnonce', wp_create_nonce( $item['ID'] ), $add_url ) );
		$add_url = static::addSearchParamsToUrl( $add_url );

		$remove_url = sprintf( get_admin_url() . 'index.php?page=%s&action=%s&ID=%s', $_REQUEST['page'], 'remove', $item['ID'] );
		$remove_url = esc_url( add_query_arg( '_wpnonce', wp_create_nonce( $item['ID'] ), $remove_url ) );
		$remove_url = static::addSearchParamsToUrl( $remove_url );

		// TODO, Better HTML?
		if ( $item['status'] ) {
			$status = '<span data-icon="b" class="yes-icon"></span><span class="assistive-text">Yes</span>';
			$actions = array(
				'remove' => sprintf( '<a href="%s">%s</a>', $remove_url, __( 'Hide in Catalog', 'pressbooks' ) ),
			);
		} else {
			$status = '<span data-icon="c" class="no-icon"></span><span class="assistive-text">No</span>';
			$actions = array(
				'add' => sprintf( '<a href="%s">%s</a>', $add_url, __( 'Show in Catalog', 'pressbooks' ) ),
			);
		}

		// Return the title contents
		return sprintf( '<span class="status">%1$s</span> %2$s',
			/*$1%s*/
			$status,
			/*$2%s*/
			$this->row_actions( $actions )
		);
	}


	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_cover( $item ) {

		$img = esc_url( $item['cover'] );
		$alt = esc_attr( $item['title'] );

		$html = "<img src='$img' alt='$alt' />";

		return $html;
	}


	/**
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/
			$this->_args['singular'], // Let's simply repurpose the table's singular label ("book")
			/*$2%s*/
			$item['ID'] // The value of the checkbox should be the record's id
		);
	}


	/**
	 * This method dictates the table's columns and titles.
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	function get_columns() {

		$profile = ( new Catalog() )->getProfile();

		$columns = array(
			'cb' => '<input type="checkbox" />', // Render a checkbox instead of text
			'status' => __( 'Catalog Status', 'pressbooks' ),
			'privacy' => __( 'Privacy Status', 'pressbooks' ),
			'cover' => __( 'Cover', 'pressbooks' ),
			'title' => __( 'Title', 'pressbooks' ),
			'author' => __( 'Author', 'pressbooks' ),
		);

		for ( $i = 1; $i <= Catalog::$maxTagsGroup; ++$i ) {
			$columns["tag_{$i}"] = ! empty( $profile["pb_catalog_tag_{$i}_name"] ) ? $profile["pb_catalog_tag_{$i}_name"] : __( 'Tag', 'pressbooks' ) . " $i";
		}

		$columns['featured'] = __( 'Featured', 'pressbooks' );
		$columns['pub_date'] = __( 'Pub Date', 'pressbooks' );

		return $columns;
	}


	/**
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting.
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	function get_sortable_columns() {

		$sortable_columns = array(
			'status' => array( 'status', false ),
			'privacy' => array( 'privacy', false ),
			'title' => array( 'title', false ),
			'author' => array( 'author', false ),
			'pub_date' => array( 'pub_date', false ),
		);

		return $sortable_columns;
	}



	/**
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	function get_bulk_actions() {

		$actions = array(
			'add' => __( 'Show in Catalog', 'pressbooks' ),
			'remove' => __( 'Hide in Catalog', 'pressbooks' ),
		);

		return $actions;
	}


	/**
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args()
	 */
	function prepare_items() {

		// Define Columns
		$columns = $this->get_columns();
		$hidden = $this->getHiddenColumns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Get data, sort
		$data = $this->getItemsData();
		$valid_cols = $this->get_sortable_columns();

		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; // If no order, default to asc
		if ( isset( $_REQUEST['orderby'] ) && isset( $valid_cols[$_REQUEST['orderby']] ) ) {
			$data = \Pressbooks\Utility\multi_sort( $data, "{$_REQUEST['orderby']}:$order" );
		} else {
			$data = \Pressbooks\Utility\multi_sort( $data, 'status:desc', 'title:asc' ); // Default
		}

		// Pagination
		$per_page = 1000;
		$current_page = $this->get_pagenum();
		$total_items = count( $data );

		/* The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );


		/* REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;

		/* REQUIRED. We also have to register our pagination options & calculations.
		 */
		$args = array(
			'total_items' => $total_items, // WE have to calculate the total number of items
			'per_page' => $per_page, // WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) // WE have to calculate the total number of pages
		);
		$this->set_pagination_args( $args );

	}


	/**
	 * Form is POST not GET. Override parent method to compensate.
	 *
	 * @param bool $with_id
	 */
	function print_column_headers( $with_id = true ) {

		if ( empty( $_GET['s'] ) && ! empty( $_POST['s'] ) )
			$_SERVER['REQUEST_URI'] = esc_url( add_query_arg( 's', $_POST['s'] ) );

		if ( empty( $_GET['orderby'] ) && ! empty( $_POST['orderby'] ) )
			$_GET['orderby'] = $_POST['orderby'];

		if ( empty( $_GET['order'] ) && ! empty( $_POST['order'] ) )
			$_GET['order'] = $_POST['order'];

		parent::print_column_headers( $with_id );
	}


	function _js_vars() {

		parent::_js_vars();
	}


	function ajax_response() {

		parent::ajax_response();
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Pressbooks Stuff
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * @param object $item A singular item (one full row's worth of data)
	 * @param string $column_name The name/slug of the column to be processed
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	protected function renderTagColumn( $item, $column_name ) {

		$html = Catalog::tagsToString( $item[$column_name] );

		if ( ! $html ) $html = '<span style="color:silver">n/a</span>';

		// Build row actions
		$actions = array(
			'edit_tags' => sprintf( '<a href="?page=%s&action=%s&ID=%s">%s</a>', $_REQUEST['page'], 'edit_tags', $item['ID'], __( 'Edit Tags', 'pressbooks' ) ),
		);

		// Return the title contents
		return sprintf( '%1$s %2$s',
			/*$1%s*/
			$html,
			/*$2%s*/
			$this->row_actions( $actions )
		);

	}

	/**
	 * TODO: This isn't well documented, not sure i'm doing it right...
	 *
	 * @return array
	 */
	protected function getHiddenColumns() {

		$hidden_columns = array(
			'featured',
		);

		return $hidden_columns;
	}


	/**
	 * @return array
	 */
	protected function getItemsData() {

		// TODO: Improve search filter for big data

		$catalog_obj = new Catalog();
		$data = $catalog_obj->getAggregate();

		foreach ( $data as $key => $val ) {
			$data[$key]['status'] = ( 1 == $val['deleted'] ) ? 0 : 1;
			$data[$key]['privacy'] = ( 1 == $val['private'] ? __( 'Private', 'pressbooks' ) : __( 'Public', 'pressbooks' ) );
			$data[$key]['cover'] = $val['cover_url']['pb_cover_small'];
		}

		return $this->searchFilter( $data );
	}


	/**
	 * @param array $data
	 *
	 * @return array
	 */
	protected function searchFilter( array $data ) {

		$keyword = (string) trim( @$_REQUEST['s'] );

		if ( ! $keyword ) {
			// No keyword
			return $data;
		}

		$filtered_data = array();
		foreach ( $data as $_ => $val ) {
			if ( $this->atLeastOneKeyword( $keyword, $val ) ) {
				$filtered_data[] = $val;
			}
		}

		return $filtered_data;
	}


	/**
	 * @param $keyword
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function atLeastOneKeyword( $keyword, array $data ) {

		// TODO: Does this work with multi-byte characters?

		foreach ( $data as $key => $val ) {
			if ( is_array( $val ) ) {
				$found = $this->atLeastOneKeyword( $keyword, $val );
				if ( $found ) return true;
				else continue;
			} elseif ( false !== stripos( $val, $keyword ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * WP Hook, Instantiate UI
	 */
	static function addMenu() {

		$url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog' );
		$view_url = static::viewCatalogUrl(); // Verifies $_REQUEST['user_id']

		$edit_url = $url . '&action=edit_profile';
		if ( isset( $_REQUEST['user_id'] ) )
			$edit_url .= '&user_id=' . $_REQUEST['user_id'];

		$list_table = new static();
		$list_table->prepare_items();
		?>
		<div class="wrap">
			<div class="postbox">
				<div class="inside">
					<h4><?php _e( 'Organize your public Catalog page.', 'pressbooks' ); ?></h4>
					<h5><span data-icon="a" class="show-hide-icon"></span><?php _e('Show/Hide books', 'pressbooks'); ?></h5>
					<p><?php _e( sprintf( 'To display a book in your catalog choose "%s" under Catalog Status. ', '<strong>' . __( 'Show in Catalog', 'pressbooks' ) . '</strong>' ), 'pressbooks' ); ?><br>
					<?php _e( sprintf( 'To hide a book in your catalog choose "%s" under Catalog Status.', '<strong>' . __( 'Hide in Catalog', 'pressbooks' ) . '</strong>' ), 'pressbooks' ); ?></p>

					<h5><span data-icon="g" class="sort-icon"></span><?php _e( 'Catalog sorting', 'pressbooks' ); ?></h5>
					<p><?php _e( sprintf( 'To add sorting ability, add your Tag names to your <a href="%s">Catalog Profile</a> page (ex: Authors, Book Genre), then add the appropriate tags to each individual book.', $edit_url ), 'pressbooks' ); ?></p>

					<h5><span data-icon="f" class="share-icon"></span><?php _e( 'Share your catalog', 'pressbooks' ); ?></h5>
					<p><?php _e( 'The public link to your catalog page', 'pressbooks' ); ?>: <a href="<?php echo $view_url; ?>"><?php echo $view_url; ?></a></p>
				</div>
			</div><!-- end .postbox -->
			
			<h2><?php echo isset( $_REQUEST['user_id'] ) ? ucfirst( get_userdata( absint( $_REQUEST['user_id'] ) )->user_login ) : __( 'My Catalog', 'pressbooks' ); ?>
				<a href="<?php echo $edit_url; ?>" class=" page-title-action"><?php _e( 'Edit Profile', 'pressbooks' ); ?></a>
				<a href="<?php echo $view_url; ?>" class=" page-title-action"><?php _e( 'Visit Catalog', 'pressbooks' ); ?></a>
			</h2>

			<form id="books-search" method="get" action="<?php echo $url; ?>" >
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<?php if ( @$_REQUEST['user_id'] ) : ?><input type="hidden" name="user_id" value="<?php echo esc_attr( $_REQUEST['user_id'] ); ?>" /><?php endif; ?>
				<?php $list_table->search_box( __( 'Search', 'pressbooks' ), 'search_id' ); ?>
			</form>

			<form id="books-filter" method="post" action="<?php echo $url; ?>" >
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<?php if ( @$_REQUEST['user_id'] ) : ?><input type="hidden" name="user_id" value="<?php echo esc_attr( $_REQUEST['user_id'] ); ?>" /><?php endif; ?>

				<div id="add-by-url">
					<input type="text" id="add_book_by_url" name="add_book_by_url" /><label for="add_book_by_url">
						<input type="submit" name="" id="search-submit" class="button" value="<?php esc_attr_e( 'Add By URL', 'pressbooks' ); ?>">
					</label>
					&nbsp;
				</div>

				<?php $list_table->display() ?>
			</form>

		</div>
	<?php

	}


	/**
	 * Rebuild a URL with known search parameters
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	static function addSearchParamsToUrl( $url ) {

		if ( ! empty( $_REQUEST['s'] ) )
			$url = esc_url( add_query_arg( 's', $_REQUEST['s'], $url ) );

		if ( ! empty( $_REQUEST['orderby'] ) )
			$url = esc_url( add_query_arg( 'orderby', $_REQUEST['orderby'], $url ) );

		if ( ! empty( $_REQUEST['order'] ) )
			$url = esc_url( add_query_arg( 'order', $_REQUEST['order'], $url ) );

		if ( ! empty( $_REQUEST['paged'] ) )
			$url = esc_url( add_query_arg( 'paged', $_REQUEST['paged'], $url ) );

		return $url;
	}


	/**
	 * Generate catalog URL. Dies on problem.
	 *
	 * @return string
	 */
	static function viewCatalogUrl() {

		if ( isset( $_REQUEST['user_id'] ) ) {

			if ( false == current_user_can( 'edit_user', (int) $_REQUEST['user_id'] ) )
				wp_die( __( 'You do not have permission to do that.' ) );

			$u = get_userdata( (int) $_REQUEST['user_id'] );
			if ( false == $u )
				wp_die( __( 'The requested user does not exist.' ) );

			$user_login = get_userdata( (int) $_REQUEST['user_id'] )->user_login;
		} else {
			$user_login = get_userdata( get_current_user_id() )->user_login;
		}

		$view_url = network_site_url( "/catalog/$user_login" );

		return $view_url;
	}

}
