<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


use \PressBooks\Book;
use \PressBooks\Catalog;


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
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

		global $status, $page;

		$args = array(
			'singular' => 'book',
			'plural' => 'books',
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
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
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
			'visit' => sprintf( '<a href="%s">%s</a>', get_site_url( $blog_id ), __( 'Visit' ) ),
			'dashboard' => sprintf( '<a href="%s">%s</a>', get_admin_url( $blog_id ), __( 'Edit Book', 'pressbooks' ) ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&book=%s">%s</a>', $_REQUEST['page'], 'delete', $item['ID'], __( 'Remove From Catalog', 'pressbooks' ) ),
		);

		// Return the title contents
		return sprintf( '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/*$1%s*/
			$item['title'],
			/*$2%s*/
			$item['ID'],
			/*$3%s*/
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

		$html = "<img src='$img' style='width: auto; height: 100px' alt='$alt' />";

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

		$columns = array(
			'cb' => '<input type="checkbox" />', // Render a checkbox instead of text
			'status' => __( 'Status', 'pressbooks' ),
			'cover' => __( 'Cover', 'pressbooks' ),
			'title' => __( 'Title', 'pressbooks' ),
			'author' => __( 'Author', 'pressbooks' ),
			'tag_1' => __( 'Tag 1', 'pressbooks' ),
			'tag_2' => __( 'Tag 2', 'pressbooks' ),
			'featured' => __( 'Featured', 'pressbooks' ),
			'pub_date' =>  __( 'Pub Date', 'pressbooks' ),
		);

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
			'title' => array( 'title', true ), // true means it's already sorted
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
			'delete' => __( 'Delete', 'pressbooks' ),
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


		// TODO: Handle bulk actions
		if ( 'delete' === $this->current_action() ) {
			wp_die( 'TODO: Remove from catalog' );
		}
		if ( 'edit' === $this->current_action() ) {
			wp_die( 'TODO: Edit tags' );
		}


		// Query for and create data
		// TODO: Caching

		$catalog_obj = new Catalog();
		$catalog = $catalog_obj->get();
		$userblogs = get_blogs_of_user( $catalog_obj->getUserId() );
		$data = array();
		$already_loaded = array();
		$i = 0;

		foreach ( $catalog as $key => $val ) {
			switch_to_blog( $val['blogs_id'] );
			$metadata = Book::getBookInformation();
			$data[$i]['ID'] = "{$val['users_id']}:{$val['blogs_id']}";
			$data[$i]['status'] = 'In Catalog'; // TODO
			$data[$i]['title'] = @$metadata['pb_title'];
			$data[$i]['cover'] = @$metadata['pb_cover_image'];
			$data[$i]['author'] = @$metadata['pb_author'];
			$data[$i]['tag_1'] = $catalog_obj->getTagsByBook( $val['blogs_id'], 1 );
			$data[$i]['tag_2'] = $catalog_obj->getTagsByBook( $val['blogs_id'], 2 );
			$data[$i]['featured'] = $val['featured'];
			$data[$i]['pub_date'] = ! empty( $metadata['pb_publication_date'] ) ? date( 'Y-m-d', (int) $metadata['pb_publication_date'] ) : '';
			++$i;
			$already_loaded[$val['blogs_id']] = true;
		}

		foreach ( $userblogs as $book ) {

			// Skip
			if ( is_main_site( $book->userblog_id ) ) continue;
			if ( isset( $already_loaded[$book->userblog_id] ) ) continue;

			switch_to_blog( $book->userblog_id );
			$metadata = Book::getBookInformation();
			$data[$i]['ID'] = "{$catalog_obj->getUserId()}:{$book->userblog_id}";
			$data[$i]['status'] = 'Not In Catalog'; // TODO
			$data[$i]['title'] = @$metadata['pb_title'];
			$data[$i]['cover'] = @$metadata['pb_cover_image'];
			$data[$i]['author'] = @$metadata['pb_author'];
			$data[$i]['tag_1'] = $catalog_obj->getTagsByBook( $book->userblog_id, 1 );
			$data[$i]['tag_2'] = $catalog_obj->getTagsByBook( $book->userblog_id, 1 );
			$data[$i]['featured'] = 0;
			$data[$i]['pub_date'] = ! empty( $metadata['pb_publication_date'] ) ? date( 'Y-m-d', (int) $metadata['pb_publication_date'] ) : '';
			++$i;
		}

		restore_current_blog();

		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'title'; // If no sort, default to title
		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; // If no order, default to asc
		$data = \PressBooks\Utility\multi_sort( $data, "$orderby:$order" );


		// Pagination

		$per_page = 5;
		$current_page = $this->get_pagenum();
		$total_items = count( $data );


		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );


		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;


		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$args = array(
			'total_items' => $total_items, // WE have to calculate the total number of items
			'per_page' => $per_page, // WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) // WE have to calculate the total number of pages
		);
		$this->set_pagination_args( $args );

	}


	function _js_vars() {

		parent::_js_vars();
	}


	function ajax_response() {

		parent::ajax_response();
	}


	// ----------------------------------------------------------------------------------------------------------------
	// PressBooks Stuff
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	protected function renderTagColumn( $item, $column_name ) {

		$html = Catalog::tagsToString( $item[$column_name] );

		// Build row actions
		$actions = array(
			'edit' => sprintf( '<a href="?page=%s&action=%s&book=%s">%s</a>', $_REQUEST['page'], 'edit', $item['ID'], __( 'Edit Tags', 'pressbooks' ) ),
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
	 * TODO: This isn't documented, not sure i'm doing it right...
	 *
	 * @return array
	 */
	protected function getHiddenColumns() {

		$hidden_columns = array(
			'featured',
		);

		return $hidden_columns;
	}

}