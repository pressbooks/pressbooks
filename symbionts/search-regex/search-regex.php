<?php
/*
Plugin Name: Search Regex
Plugin URI: http://urbangiraffe.com/plugins/search-regex
Description: Adds search &amp; replace functionality across posts, pages, comments, and meta-data, with full regular expression support
Author: John Godley
Version: 1.4.14
Author URI: http://urbangiraffe.com/
*/

include dirname( __FILE__ ).'/plugin.php';

class SearchRegex extends SearchRegex_Plugin {
	function SearchRegex()	{
		if (  is_admin()) {
			$this->register_plugin( 'search-regex', __FILE__ );
			$this->add_filter( 'admin_menu' );
			$this->add_action( 'load-tools_page_search-and-replace', 'search_head' );
		}
	}

	function search_head() {
		include dirname( __FILE__ ).'/models/search.php';
		include dirname( __FILE__ ).'/models/result.php';

		wp_enqueue_style( 'search-regex', plugin_dir_url( __FILE__ ).'admin.css', $this->version() );
	}

	function admin_screen()	{
		$searches = Search::get_searches();

		$search_pattern = $replace_pattern = '';
		if ( isset( $_POST['search_pattern'] ) )
			$search_pattern  = stripslashes( $_POST['search_pattern'] );

		if ( isset( $_POST['replace_pattern'] ) )
			$replace_pattern = stripslashes( $_POST['replace_pattern'] );

		$search_pattern  = str_replace( "\'", "'", $search_pattern );
		$replace_pattern = str_replace( "\'", "'", $replace_pattern );
		$orderby         = 'asc';

		if ( isset( $_POST['orderby'] ) && $_POST['orderby'] == 'desc' )
			$orderby = 'desc';

		$limit  = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 10;
		$offset = 0;
		$source = 'SearchPostContent';

		if ( Search::valid_search( $source ) && ( isset( $_POST['search'] ) || isset( $_POST['replace'] ) || isset( $_POST['replace_and_save'] ) ) ) {
			$klass    = stripslashes( $source );
			$searcher = new $klass;

			if ( isset( $_POST['regex'] ) ) {
				$searcher->set_regex_options( empty( $_POST['regex_dot'] ) ? 0 : 1, empty( $_POST['regex_case'] ) ? 0 : 1, empty( $_POST['regex_multi'] ) ? 0 : 1 );
			}

			// Make sure no one sneaks in with a replace
			if ( !current_user_can( 'administrator' ) && !current_user_can( 'search_regex_write' ) ) {
				unset( $_POST['replace'] );
				unset( $_POST['replace_and_save'] );
				$_POST['search'] = 'search';
			}

			$results = array();

			if ( isset( $_POST['search'] ) )
				$results = $searcher->search_for_pattern( $search_pattern, $limit, $offset, $orderby );
			elseif ( isset( $_POST['replace'] ) )
				$results = $searcher->search_and_replace( $search_pattern, $replace_pattern, $limit, $offset, $orderby );
			elseif ( isset( $_POST['replace_and_save'] ) )
				$results = $searcher->search_and_replace( $search_pattern, $replace_pattern, $limit, $offset, $orderby, true );

			if ( !is_array( $results ) )
				$this->render_error( $results );
			elseif ( isset( $_POST['replace_and_save'] ) )
				$this->render_message( sprintf( '%d occurrence(s) replaced', count( $results ) ) );

			$this->render_admin( 'search', array( 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ) );

			if ( is_array( $results ) && !isset( $_POST['replace_and_save'] ) )
				$this->render_admin( 'results', array( 'search' => $searcher, 'results' => $results ) );
		}
		else
			$this->render_admin( 'search', array( 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ) );
	}

	function admin_menu()	{
		if ( current_user_can( 'administrator' ) ) {
    		$menu = add_management_page( __( 'Search and Replace', 'pressbooks' ), __( 'Search and Replace', 'pressbooks' ), 'administrator', 'search-and-replace', array( &$this, 'admin_screen' ) );
    		add_action( 'admin_print_scripts-' . $menu, array( &$this, 'js' ) );
    	}
	}
	
	function js() {
	    wp_enqueue_script( 'search-and-replace', plugins_url('/js/search-and-replace.js', __FILE__) );
	}
	
	function base_url() {
		return __FILE__;
	}

	function version() {
		$plugin_data = implode( '', file( __FILE__ ) );

		if ( preg_match( '|Version:(.*)|i', $plugin_data, $version ) )
			return trim( $version[1] );
		return '';
	}
}

$search_regex = new SearchRegex;
