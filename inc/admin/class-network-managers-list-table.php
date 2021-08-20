<?php
/**
 * Contains the class for generating and displaying the Pressbooks Network Manager table.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin;

/**
 * @see http://codex.wordpress.org/Class_Reference/WP_List_Table
 */
class Network_Managers_List_Table extends \WP_List_Table {

	/** ************************************************************************
	 * Constructor.
	 ***************************************************************************/
	function __construct() {

		parent::__construct(
			[
				'singular' => 'super-admin',
				'plural' => 'super-admins',
				'ajax' => false,
			]
		);

	}

	/** ************************************************************************
	 * Custom column method for the display_name column.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 **************************************************************************/
	function column_display_name( $item ) {
		return $item['display_name'];
	}

	/** ************************************************************************
	 * Custom column method for the user_email column.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 **************************************************************************/
	function column_user_email( $item ) {
		return $item['user_email'];
	}

	/** ************************************************************************
	 * Custom column method for the user_login column.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string HTML to be placed inside the column <td>
	 **************************************************************************/
	function column_user_login( $item ) {

		// Build row actions
		$current_user = wp_get_current_user();

		if ( absint( $item['ID'] ) !== absint( $current_user->ID ) ) { // Don't let users restrict themselves
			if ( ! empty( $item['restricted'] ) ) {
				$actions = [
					'unrestrict' => '<a data-restrict="0" data-restrict-text="' . __( 'Restrict Access', 'pressbooks' ) . '" data-unrestrict-text="' . __( 'Unrestrict Access', 'pressbooks' ) . '">' . __( 'Unrestrict Access', 'pressbooks' ) . '</a>',
				];
			} else {
				$actions = [
					'restrict' => '<a data-restrict="1" data-restrict-text="' . __( 'Restrict Access', 'pressbooks' ) . '" data-unrestrict-text="' . __( 'Unrestrict Access', 'pressbooks' ) . '">' . __( 'Restrict Access', 'pressbooks' ) . '</a>',
				];
			}
		} else {
			$actions = [];
		}

		// Return the title contents
		return sprintf(
			'%1$s <span class="user_login">%2$s</span> %3$s',
			get_avatar( $item['ID'], 32 ),
			$item['user_login'],
			$this->row_actions( $actions )
		);
	}

	/** ************************************************************************
	 * Define the table's columns and titles.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_columns() {
		$columns = [
			'user_login' => 'Username',
			'display_name' => 'Name',
			'user_email' => 'E-mail',
		];
		return $columns;
	}

	/** ************************************************************************
	 * Define which columns are sortable.
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 **************************************************************************/
	function get_sortable_columns() {
		$sortable_columns = [
			'user_login' => [ 'user_login', true ], // True means it's already sorted
			'display_name' => [ 'display_name', false ],
			'user_email' => [ 'user_email', false ],
		];
		return $sortable_columns;
	}

	/**
	 * Set up classes for a single row based on active status
	 *
	 * @param object $item The current item
	 */
	function single_row( $item ) {
		$class = '';
		if ( ! empty( $item['restricted'] ) ) {
			$class = 'restricted';
		}
		echo "<tr id='" . $item['ID'] . "' class='$class'>";
		$this->single_row_columns( $item );
		echo "</tr>\n";
	}

	/** ************************************************************************
	 * Prepare data for display
	 *
	 * @global \wpdb $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	function prepare_items() {

		/**
		 * Define column headers.
		 */
		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		/**
		 * Build column header array.
		 */
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		/**
		 * Check for sorting input and sort the data in our array accordingly.
		 */

		$network_admins = get_site_option( 'site_admins' );
		$network_managers = get_network_option( null, 'pressbooks_network_managers', [] );
		$tmp = [];
		foreach ( $network_admins as $id => $username ) {
			$user = get_user_by( 'login', $username );
			$user = $user->data;
			$is_restricted = ( in_array( absint( $user->ID ), $network_managers, true ) ) ? true : false; // Determine admin's restricted status
			$tmp[ $id ] = [
				'ID' => $user->ID,
				'user_login' => $user->user_login,
				'display_name' => $user->display_name,
				'user_email' => $user->user_email,
				'restricted' => $is_restricted,
			];
		}
		$network_admins = $tmp;
		usort(
			$network_admins, function ( $a, $b ) {
				$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'user_login';
				$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
				$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
				return ( 'asc' === $order ) ? $result : -$result;
			}
		);

		$this->items = $network_admins; // Return our data
	}

}
