<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 *
 * Adapted from John Godley's Search Regex (https://github.com/johngodley/search-regex)
 */

namespace Pressbooks\Modules\SearchAndReplace;

use Pressbooks\Modules\SearchAndReplace\Search;
use Pressbooks\Modules\SearchAndReplace\Result;

class SearchAndReplace {
	private static $instance = null;

	static function init() {
		if ( is_admin() ) {
			if ( is_null( self::$instance ) ) {
				self::$instance = new \Pressbooks\Modules\SearchAndReplace\SearchAndReplace();
			}
			return self::$instance;
		}
	}

	function __construct() {
		add_filter( 'admin_menu', [ &$this, 'adminMenu' ] );
		add_action( 'load-tools_page_pressbooks-search-and-replace', [ &$this, 'searchHead' ] );
	}

	function searchHead() {
		wp_enqueue_style( 'search-and-replace', \Pressbooks\Utility\asset_path( 'styles/search-and-replace.css' ) );
		wp_register_script( 'search-and-replace', \Pressbooks\Utility\asset_path( 'scripts/search-and-replace.js' ) );
		wp_localize_script( 'search-and-replace', 'pb_sr', $this->getL10n() );
		wp_enqueue_script( 'search-and-replace' );
	}

	function getL10n() {
		return [
		  'warning_text' => __( 'Once you&rsquo;ve pressed &lsquo;Replace & Save&rsquo; there is no going back! Have you checked &lsquo;Preview Replacements&rsquo; to make sure this will do what you want it to do?', 'pressbooks' ),
		];
	}

	function adminMenu() {
		if ( current_user_can( 'administrator' ) ) {
			add_management_page(
				__( 'Search & Replace', 'pressbooks' ),
				__( 'Search & Replace', 'pressbooks' ),
				'administrator',
				'pressbooks-search-and-replace',
				[ &$this, 'adminScreen' ]
			);
		}
	}

	function adminScreen() {
		$searches = Search::getSearches();
		if ( isset( $_POST['search_pattern'] ) && ! wp_verify_nonce( $_POST['pressbooks-search-and-replace-nonce'], 'search' ) ) {
			return;
		}

		$search_pattern = $replace_pattern = '';

		if ( isset( $_POST['search_pattern'] ) ) {
			$search_pattern  = stripslashes( $_POST['search_pattern'] );
		}

		if ( isset( $_POST['replace_pattern'] ) ) {
			$replace_pattern = stripslashes( $_POST['replace_pattern'] );
		}

		$search_pattern  = str_replace( "\'", "'", $search_pattern );
		$replace_pattern = str_replace( "\'", "'", $replace_pattern );
		$orderby = 'asc';

		if ( isset( $_POST['orderby'] ) && 'desc' === $_POST['orderby'] ) {
			$orderby = 'desc';
		}

		$limit  = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0;

		$offset = 0;

		$source = isset( $_POST['source'] ) ? stripslashes( $_POST['source'] ) : '';

		if ( Search::validSearch( $source ) && ( isset( $_POST['search'] ) || isset( $_POST['replace'] ) || isset( $_POST['replace_and_save'] ) ) ) {
			$searcher = new $source;

			// Enable regex mode
			$searcher->regex = ! empty( $_POST['regex'] );

			// Make sure no one sneaks in with a replace
			if ( ! current_user_can( 'administrator' ) ) {
				unset( $_POST['replace'] );
				unset( $_POST['replace_and_save'] );
				$_POST['search'] = 'search';
			}
			$results = [];
			if ( isset( $_POST['search'] ) ) {
				$results = $searcher->searchForPattern( $search_pattern, $limit, $offset, $orderby );
			} elseif ( isset( $_POST['replace'] ) ) {
				$results = $searcher->searchAndReplace( $search_pattern, $replace_pattern, $limit, $offset, $orderby );
			} elseif ( isset( $_POST['replace_and_save'] ) ) {
				$results = $searcher->searchAndReplace( $search_pattern, $replace_pattern, $limit, $offset, $orderby, true );
			}
			if ( ! is_array( $results ) ) {
				$this->renderError( $results );
			} elseif ( isset( $_POST['replace_and_save'] ) ) {
	?>
		  <div class="updated" id="message" onclick="this.parentNode.removeChild (this)">
		   <p><?php printf( _n( '%d occurrence replaced.', '%d occurrences replaced.', count( $results ) ), count( $results ) ) ?></p>
		  </div>
<?php
			}
			$this->render( 'search', [ 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ] );
			if ( is_array( $results ) && ! isset( $_POST['replace_and_save'] ) ) {
				$this->render( 'results', [ 'search' => $searcher, 'results' => $results ] );
			}
		} else {
			$this->render( 'search', [ 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ] );
		}
	}

	private function render( $template, $template_vars = [] ) {
		foreach ( $template_vars as $key => $val ) {
			$$key = $val;
		}

		if ( file_exists( PB_PLUGIN_DIR . "templates/admin/$template.php" ) ) {
			include PB_PLUGIN_DIR . "templates/admin/$template.php";
		}
	}

	function renderError( $message ) {
		?>
	<div class="fade error" id="message">
		<p><?php echo $message ?></p>
	</div>
	<?php
	}
}
