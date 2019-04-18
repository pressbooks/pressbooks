<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 *
 * Adapted from John Godley's Search Regex (https://github.com/johngodley/search-regex)
 */

namespace Pressbooks\Modules\SearchAndReplace;

use PressbooksMix\Assets;

class SearchAndReplace {

	/**
	 * @var SearchAndReplace
	 */
	private static $instance = null;

	/**
	 * @return SearchAndReplace|null
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	static public function hooks( SearchAndReplace $obj ) {
		if ( is_admin() ) {
			add_filter( 'admin_menu', [ $obj, 'adminMenu' ] );
			add_action( 'load-tools_page_pressbooks-search-and-replace', [ $obj, 'searchHead' ] );
		}
	}

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Load styles and scripts
	 */
	public function searchHead() {
		$assets = new Assets( 'pressbooks', 'plugin' );
		wp_enqueue_style( 'search-and-replace', $assets->getPath( 'styles/search-and-replace.css' ) );
		wp_register_script( 'search-and-replace', $assets->getPath( 'scripts/search-and-replace.js' ) );
		wp_localize_script( 'search-and-replace', 'pb_sr', $this->getL10n() );
		wp_enqueue_script( 'search-and-replace' );
	}

	/**
	 * @return array
	 */
	public function getL10n() {
		return [
			'warning_text' => __( 'Once you&rsquo;ve pressed &lsquo;Replace & Save&rsquo; there is no going back! Have you checked &lsquo;Preview Replacements&rsquo; to make sure this will do what you want it to do?', 'pressbooks' ),
		];
	}

	/**
	 * Add admin page
	 */
	public function adminMenu() {
		if ( current_user_can( 'administrator' ) ) {
			add_management_page(
				__( 'Search & Replace', 'pressbooks' ),
				__( 'Search & Replace', 'pressbooks' ),
				'administrator',
				'pressbooks-search-and-replace',
				[ $this, 'adminScreen' ]
			);
		}
	}

	/**
	 * Callable for add_management_page
	 */
	public function adminScreen() {
		$searches = Search::getSearches();

		if ( isset( $_POST['search_pattern'] ) && ! wp_verify_nonce( $_POST['pressbooks-search-and-replace-nonce'], 'search' ) ) {
			return;
		}

		$search_pattern = '';
		$replace_pattern = '';

		if ( isset( $_POST['search_pattern'] ) ) {
			$search_pattern = stripslashes( $_POST['search_pattern'] );
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

		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0;

		$offset = 0;

		$source = isset( $_POST['source'] ) ? stripslashes( $_POST['source'] ) : '';

		if ( Search::validSearch( $source ) && ( isset( $_POST['search'] ) || isset( $_POST['replace'] ) || isset( $_POST['replace_and_save'] ) ) ) {

			/** @var \Pressbooks\Modules\SearchAndReplace\Search $searcher */
			$searcher = new $source;

			// Enable regex mode
			$enabled = ( defined( 'PB_ENABLE_REGEX_SEARCHREPLACE' ) && PB_ENABLE_REGEX_SEARCHREPLACE ) || is_super_admin();
			$searcher->regex = $enabled && ! empty( $_POST['regex'] );

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
				<div class="updated" id="message" role="status" onclick="this.parentNode.removeChild (this)">
					<p><?php printf( _n( '%d occurrence replaced.', '%d occurrences replaced.', count( $results ) ), count( $results ) ); ?></p>
				</div>
				<?php
			}
			$this->render(
				'search', [
					'search' => $search_pattern,
					'replace' => $replace_pattern,
					'searches' => $searches,
					'source' => $source,
				]
			);
			if ( is_array( $results ) && ! isset( $_POST['replace_and_save'] ) ) {
				$this->render(
					'results', [
						'search' => $searcher,
						'results' => $results,
					]
				);
			}
		} else {
			$this->render(
				'search', [
					'search' => $search_pattern,
					'replace' => $replace_pattern,
					'searches' => $searches,
					'source' => $source,
				]
			);
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

	public function renderError( $message ) {
		?>
	<div class="fade error" id="message">
		<p><?php echo $message; ?></p>
	</div>
		<?php
	}
}
