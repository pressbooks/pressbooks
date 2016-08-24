<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 *
 * Adapted from John Godley's Search Regex (https://github.com/johngodley/search-regex)
 */

namespace Pressbooks\Modules\SearchAndReplace;

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
    add_filter( 'admin_menu', array( &$this, 'admin_menu' ) );
    add_action( 'load-tools_page_pressbooks-search-and-replace', array( &$this, 'search_head' ) );
  }

  function search_head() {
  	include PB_PLUGIN_DIR . 'includes/modules/searchandreplace/class-pb-search.php';
  	include PB_PLUGIN_DIR . 'includes/modules/searchandreplace/class-pb-result.php';
    wp_enqueue_style( 'search-and-replace', \Pressbooks\Utility\asset_path( 'styles/search-and-replace.css' ) );
    wp_register_script( 'search-and-replace', \Pressbooks\Utility\asset_path( 'scripts/search-and-replace.js' ) );
    wp_localize_script( 'search-and-replace', 'pb_sr', $this->get_l10n() );
    wp_enqueue_script( 'search-and-replace' );
  }

  function get_l10n() {
      return [
          'warning_text' => __( 'Once you&rsquo;ve pressed &lsquo;Replace & Save&rsquo; there is no going back! Have you checked &lsquo;Preview Replacements&rsquo; to make sure this will do what you want it to do?', 'pressbooks' )
      ];
  }

  function admin_menu() {
    if ( current_user_can( 'administrator' ) ) {
			add_management_page(
        __( 'Search & Replace', 'pressbooks' ),
        __( 'Search & Replace', 'pressbooks' ),
        'administrator',
        'pressbooks-search-and-replace',
        array( &$this, 'admin_screen' )
      );
		}
  }

  function admin_screen() {
    $searches = \Pressbooks\Modules\SearchAndReplace\Search::get_searches();
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

    if ( isset( $_POST['orderby'] ) && $_POST['orderby'] === 'desc' ) {
        $orderby = 'desc';
    }

    $limit  = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0;

    $offset = 0;

    $source = isset( $_POST['source'] ) ? stripslashes( $_POST['source'] ) : '';

    if ( \Pressbooks\Modules\SearchAndReplace\Search::valid_search( $source ) && ( isset( $_POST['search'] ) || isset( $_POST['replace'] ) || isset( $_POST['replace_and_save'] ) ) ) {
        $searcher = new $source;

        // Make sure no one sneaks in with a replace
        if ( ! current_user_can( 'administrator' ) ) {
            unset( $_POST['replace'] );
            unset( $_POST['replace_and_save'] );
            $_POST['search'] = 'search';
        }
        $results = array();
        if ( isset( $_POST['search'] ) ) {
            $results = $searcher->search_for_pattern( $search_pattern, $limit, $offset, $orderby );
        }
        elseif ( isset( $_POST['replace'] ) ) {
            $results = $searcher->search_and_replace( $search_pattern, $replace_pattern, $limit, $offset, $orderby );
        }
        elseif ( isset( $_POST['replace_and_save'] ) ) {
            $results = $searcher->search_and_replace( $search_pattern, $replace_pattern, $limit, $offset, $orderby, true );
        }
        if ( ! is_array( $results ) ) {
            $this->render_error( $results );
        }
        elseif ( isset( $_POST['replace_and_save'] ) ) {
?>
            <div class="updated" id="message" onclick="this.parentNode.removeChild (this)">
             <p><?php printf( _n( '%d occurrence replaced.', '%d occurrences replaced.', count( $results ) ), count( $results ) ) ?></p>
            </div>
<?php
        }
        $this->render( 'search', array( 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ) );
        if ( is_array( $results ) && ! isset( $_POST['replace_and_save'] ) ) {
            $this->render( 'results', array( 'search' => $searcher, 'results' => $results ) );
        }
    }
    else {
        $this->render( 'search', array( 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ) );
    }
  }

  private function render( $template, $template_vars = [] ) {
	foreach ( $template_vars as $key => $val ) {
		$$key = $val;
	}

    if ( file_exists( PB_PLUGIN_DIR . "templates/admin/$template.php" ) )
		include PB_PLUGIN_DIR . "templates/admin/$template.php";
	}

    function render_error( $message ) {
    	?>
    <div class="fade error" id="message">
    	<p><?php echo $message ?></p>
    </div>
    <?php
    	}
}
