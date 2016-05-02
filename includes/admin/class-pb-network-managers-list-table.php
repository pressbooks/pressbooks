<?php
/**
 * Contains the class for generating and displaying the Pressbooks Network Manager table.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Admin;

if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * @see http://codex.wordpress.org/Class_Reference/WP_List_Table
 */
class Network_Managers_List_Table extends \WP_List_Table {

    /** ************************************************************************
     * Constructor.
     ***************************************************************************/
    function __construct() {
                
        parent::__construct( array(
            'singular'  => 'super-admin',
            'plural'    => 'super-admins',
            'ajax'      => false
        ) );
        
    }


    /** ************************************************************************
     * Custom column method for the display_name column.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_display_name( $item ) {
	    return $item['display_name'];
	}
    
    /** ************************************************************************
     * Custom column method for the user_email column.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_user_email( $item ) {
	    return $item['user_email'];
	}


    /** ************************************************************************
     * Custom column method for the user_login column.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string HTML to be placed inside the column <td>
     **************************************************************************/
    function column_user_login($item){
        
        // Build row actions
        $current_user = wp_get_current_user();
        
	    if ( absint( $item['ID'] ) !== absint( $current_user->ID ) ) { // Don't let users restrict themselves
	        if ( $item['restricted'] == 1 ) {
		        $actions = array(
		            'unrestrict'	=> '<a data-restrict="0" data-restrict-text="' . __( 'Restrict Access', 'pressbooks' ) . '" data-unrestrict-text="' . __( 'Unrestrict Access', 'pressbooks' ) . '">' . __( 'Unrestrict Access', 'pressbooks' ) . '</a>',
		        );
	        } else {
	        	$actions = array(
	            	'restrict'	=> '<a data-restrict="1" data-restrict-text="' . __( 'Restrict Access', 'pressbooks' ) . '" data-unrestrict-text="' . __( 'Unrestrict Access', 'pressbooks' ) . '">' . __( 'Restrict Access', 'pressbooks' ) . '</a>',
				);
	        }
        } else {
	        $actions = array();
        }
        
        // Return the title contents
        return sprintf('%1$s %2$s %3$s',
            /*$1%s*/ get_avatar( $item['ID'], 32 ),
            /*$2%s*/ '<span class="user_login">' . $item['user_login'] . '</span>',
            /*$3%s*/ $this->row_actions($actions)
        );
    }


    /** ************************************************************************
     * Define the table's columns and titles.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'user_login'	=> 'Username',
            'display_name'	=> 'Name',
            'user_email'	=> 'E-mail',
        );
        return $columns;
    }


    /** ************************************************************************
     * Define which columns are sortable.
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            'user_login'	=> array( 'user_login', true ),     //true means it's already sorted
            'display_name'	=> array( 'display_name', false ),
            'user_email'	=> array ('user_email', false )
        );
        return $sortable_columns;
    }

    /**
     * Set up classes for a single row based on active status
     *
     * @param object $item The current item
     * @return string A row of cells
     */
	function single_row( $item ) {
		$class = '';
		if ( $item['restricted'] == 1 )
			$class = 'restricted';
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
        global $wpdb;

        /**
         * Define column headers.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * Build column header array.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
                        
        /**
         * Check for sorting input and sort the data in our array accordingly.
         */
         
        $data = $wpdb->get_results( "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = 'site_admins'" ); // Get site admins
        $data = maybe_unserialize( $data[0]->meta_value );
        if ( !is_array( $data ) )
        	$data = array( $data );
        $restricted = $wpdb->get_results( "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = 'pressbooks_network_managers'" );
        // Get restricted site admins (network managers)
        if ( $restricted ) {
	        $restricted = maybe_unserialize( $restricted[0]->meta_value );
	    } else {
		    $restricted = array();
		}
		$tmp = array();
        foreach ( $data as $id => $username ) {
	       $user = get_user_by( 'slug', $username );
	       $user = $user->data;
	       $is_restricted = ( in_array( $user->ID, $restricted ) ) ? true : false; // Determine admin's restricted status
	       $tmp[$id] = array(
		       'ID'				=> $user->ID,
		       'user_login'		=> $user->user_login,
		       'display_name'	=> $user->display_name,
		       'user_email'		=> $user->user_email,
		       'restricted'		=> $is_restricted,
	       );
        }
        $data = $tmp;        
		usort( $data, function( $a, $b ) {
		    $orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'user_login';
		    $order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
		    $result = strcmp( $a[$orderby], $b[$orderby] );
		    return ( $order === 'asc' ) ? $result : -$result;
		});
                
        $this->items = $data; // Return our data
        
    }

}