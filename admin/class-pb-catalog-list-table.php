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


	protected $userId;


	/**
	 * Constructor, must call parent
	 */
	function __construct() {

		global $status, $page;

		$args = array(
			'singular' => 'book',
			'plural' => 'books',
			'ajax' => false,
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
			'dashboard' => sprintf( '<a href="%s">%s</a>', get_admin_url( $blog_id ), __( 'Dashboard' ) ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&book=%s">%s</a>', $_REQUEST['page'], 'delete', $item['ID'], __( 'Delete' ) ),
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
			'title' => __( 'Title', 'pressbooks' ),
			'cover' => __( 'Cover', 'pressbooks' ),
			'author' => __( 'Author', 'pressbooks' ),
			'tag_1' => __( 'Tag 1', 'pressbooks' ),
			'tag_2' => __( 'Tag 2', 'pressbooks' ),
			'featured' => __( 'Featured', 'pressbooks' ),
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
			wp_die( 'Items deleted (or they would be if we had items to delete)!' );
		}


		// Query for and create data

		$catalog = ( new Catalog() )->get(); // PHP 5.4+
		$data = array();

		foreach ( $catalog as $key => $val ) {
			switch_to_blog( $val['blogs_id'] );
			$metadata = Book::getBookInformation();
			$data[$key]['ID'] = "{$val['users_id']}:{$val['blogs_id']}";
			$data[$key]['title'] = @$metadata['pb_title'];
			$data[$key]['cover'] = @$metadata['pb_cover_image'];
			$data[$key]['author'] = @$metadata['pb_author'];
			$data[$key]['tag_1'] = $val['tag_1'];
			$data[$key]['tag_2'] = $val['tag_2'];
			$data[$key]['featured'] = $val['featured'];
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
		$this->set_pagination_args( array(
										 'total_items' => $total_items, //WE have to calculate the total number of items
										 'per_page' => $per_page, //WE have to determine how many items to show on a page
										 'total_pages' => ceil( $total_items / $per_page ) //WE have to calculate the total number of pages
									) );
	}


	/**
	 * TODO: http://wordpress.stackexchange.com/questions/58576/auto-complete-or-auto-suggest-from-list-of-post-titles
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	protected function renderTagColumn( $item, $column_name ) {

		$value = esc_attr( $item[$column_name] );

		$html = "<input type='text' value='$value' />";

		return $html;
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