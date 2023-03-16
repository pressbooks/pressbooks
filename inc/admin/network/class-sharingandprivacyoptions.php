<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

namespace Pressbooks\Admin\Network;

use function Pressbooks\Admin\NetworkManagers\is_restricted;
use function Pressbooks\Utility\str_lreplace;
use Pressbooks\BookDirectory;
use Pressbooks\DataCollector\Book;

class SharingAndPrivacyOptions extends \Pressbooks\Options {

	const NETWORK_DIRECTORY_EXCLUDED = 'network_directory_excluded';

	/**
	 * The value for *site* option: pressbooks_sharingandprivacy_options_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 4;

	/**
	 * Sharing and Privacy options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Sharing and Privacy defaults.
	 *
	 * @var array
	 */
	public $defaults;

	/**
	 * Sharing and Privacy booleans.
	 *
	 * @var array
	 */
	public $booleans;

	/**
	 * Sharing and Privacy multiline strings.
	 *
	 * @var array
	 */
	public $multiline_strings;

	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	function __construct( array $options ) {
		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();
		$this->multiline_strings = $this->getMultilineStringOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Configure the network export options page using the settings API.
	 */
	function init() {
		$_option = $this->getSlug();
		$_page = $_option;
		$_section = $this->getSlug() . '_section';

		add_settings_section(
			$_section,
			'',
			[ $this, 'display' ],
			$_page
		);

		add_settings_field(
			'allow_redistribution',
			__( 'Allow Redistribution', 'pressbooks' ),
			[ $this, 'renderAllowRedistributionField' ],
			$_page,
			$_section,
			[
				'class' => is_restricted() ? 'hidden' : '',
				__( 'Allow book administrators to enable redistribution of export files.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'enable_network_api',
			__( 'Enable network API', 'pressbooks' ),
			[ $this, 'renderAllowRootApi' ],
			$_page,
			$_section,
			[
				'class' => is_restricted() ? 'hidden' : '',
				__( 'Enable access to your network of books via the Pressbooks REST API.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'enable_cloning',
			__( 'Enable Cloning', 'pressbooks' ),
			[ $this, 'renderAllowCloning' ],
			$_page,
			$_section,
			[
				'class' => is_restricted() ? 'hidden' : '',
				__( 'Enable book cloning via the Pressbooks REST API.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'enable_thincc_weblinks',
			__( 'Enable CC with Weblinks', 'pressbooks' ),
			[ $this, 'renderAllowThinCcWeblinks' ],
			$_page,
			$_section,
			[
				'class' => is_restricted() ? 'hidden' : '',
				__( 'Allow users to produce Common Cartridge exports with simple Web Links.', 'pressbooks' ),
			]
		);

		add_settings_field(
			self::NETWORK_DIRECTORY_EXCLUDED,
			__( 'Book directory', 'pressbooks' ),
			[ $this, 'renderNetworkExcludeNonCataloguedPublicBooks' ],
			$_page,
			$_section,
			[
				'class' => is_restricted() ? 'hidden' : '',
				__( 'Exclude non-catalogued public books from Pressbooks Directory.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'iframe_whitelist',
			__( 'Iframe Allowlist', 'pressbooks' ),
			[ $this, 'renderIframesWhiteList' ],
			$_page,
			$_section,
			[
				__( 'To allowlist all content from a domain: <code>guide.pressbooks.com</code> To allowlist a path: <code>//guide.pressbooks.com/some/path/</code> One per line.', 'pressbooks' ),
				'label_for' => 'iframe_whitelist',
			]
		);

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Display the network sharing and privacy options page description.
	 */
	function display() {
		echo '<p>' . __( 'Sharing and Privacy settings.', 'pressbooks' ) . '</p>';
	}

	function render() {
		$_option = $this->getSlug();
		?>
		<div class="wrap">
			<h1><?php echo $this->getTitle(); ?></h1>
			<?php
			$nonce = ( ! empty( $_REQUEST['_wpnonce'] ) ) ? $_REQUEST['_wpnonce'] : '';
			if ( ! empty( $_POST ) ) {
				if ( ! wp_verify_nonce( $nonce, $_option . '-options' ) ) {
					wp_die( 'Security check' );
				} else {
					if ( isset( $_REQUEST[ $_option ] ) ) {
						$options = $this->sanitize( $_REQUEST[ $_option ] );
					} else {
						$options = $this->sanitize( [] ); // Get sanitized defaults
					}

					if ( $this->options['network_directory_excluded'] !== $options['network_directory_excluded'] ) {
						self::networkExcludeOption( (int) $options['network_directory_excluded'] );
					}

					update_site_option( $_option, $options );
					?>
					<div id="message" role="status" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></div>
					<?php
				}
			}
			?>
			<form method="post" action="">
				<?php
				settings_fields( $this->getSlug() );
				do_settings_sections( $this->getSlug() );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Update 'last_update' blog meta for all books
	 */
	public function updateBooksLastUpdatedDate() {
		$books = get_sites();

		foreach ( $books as $book ) {
			if ( '1' === $book->blog_id ) {
				continue;
			}

			update_blog_details( $book->blog_id, [ 'last_updated' => current_time( 'mysql', true ) ] );
		}
	}

	/**
	 * Performs network book directory exclusion logic for non catalog books
	 *
	 * @param bool $exclude   True for exclude and false for removing exclude
	 */
	public static function networkExcludeOption( bool $exclude ) {
		self::excludeNonCatalogBooksFromDirectory( 'excludeNonCatalogBooksFromDirectoryAction', ! $exclude );
	}

	/**
	 * Triggers a batch book directory delete for all NON catalog books
	 *
	 * @param bool $revert  un-checking network exclude
	 */
	static function excludeNonCatalogBooksFromDirectory( $callback, bool $revert = false ) {
		$book_ids = self::getPublicBooks( ! $revert );

		if ( count( $book_ids ) > 0 ) {
			self::$callback( $book_ids, $revert );
		}
	}

	/**
	 *  Returns all public book ids. Can filter by in catalog
	 *
	 * @return array    public books
	 */
	static function getPublicBooks( $only_non_catalog = false ) {
		global $wpdb;

		$public = Book::PUBLIC;
		$in_catalog = Book::IN_CATALOG;

		$sql_where_conditions = [ 'id != 1', 'public = 1' ];
		if ( $only_non_catalog ) {
			$sql_where_conditions[] = 'inCatalog = 0';
		}

		$sql_where = '';
		if ( ! empty( $sql_where_conditions ) ) {
			$sql_where = 'HAVING ';
			foreach ( $sql_where_conditions as $condition ) {
				$sql_where .= "($condition) AND ";
			}
			$sql_where = str_lreplace( ') AND ', ') ', $sql_where );
		}

		$sql = "
			SELECT
				b.blog_id AS id,
				MAX(IF(b.meta_key='{$public}',CAST(b.meta_value AS UNSIGNED),null)) AS public,
				MAX(IF(b.meta_key='{$in_catalog}',CAST(b.meta_value AS UNSIGNED),null)) AS inCatalog
			FROM {$wpdb->blogmeta} b
			GROUP BY id
			{$sql_where}
			";

		return array_map( 'intval', array_column( $wpdb->get_results( $sql, 'ARRAY_A' ), 'id' ) ); // @codingStandardsIgnoreLine
	}

	/**
	 * Perform actions during network book exclusion is enabled
	 *
	 * @param $book_ids
	 * @return array   Responses from actions
	 */
	static function excludeNonCatalogBooksFromDirectoryAction( array $book_ids, bool $revert = false ) {

		$is_deleted = [];

		if ( ! $revert ) {
			$is_deleted = array_map(
				function( $book_ids ) {
					return BookDirectory::init()->deleteBookFromDirectory( $book_ids );
				},
				array_chunk( $book_ids, 50 )
			);
		}

		$blogs_not_updated = [];

		foreach ( $book_ids as $book_id ) {
			if ( ! update_blog_details( $book_id, [ 'last_updated' => current_time( 'mysql', true ) ] ) ) {
				$blogs_not_updated[] = $book_id;
			}
		}

		return [
			'directory_delete_responses' => $is_deleted,
			'blogs_not_updated' => $blogs_not_updated,
		];
	}

	/**
	 * @param int $version
	 */
	function upgrade( $version ) {

		$slug = $this->getSlug();
		$options = get_site_option( $slug, [] );

		if ( $version < 2 ) {
			$options['enable_network_api'] = 1;
		}

		if ( $version < 3 ) {
			$options['enable_cloning'] = 1;
		}

		if ( $version < 4 ) {
			$options['enable_thincc_weblinks'] = 1;
		}

		update_site_option( $slug, $options );
	}

	/**
	 * Render the allow_redistribution checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowRedistributionField( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'allow_redistribution',
				'name' => $this->getSlug(),
				'option' => 'allow_redistribution',
				'value' => ( isset( $options['allow_redistribution'] ) ) ? $options['allow_redistribution'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the enable_network_api checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowRootApi( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'enable_network_api',
				'name' => $this->getSlug(),
				'option' => 'enable_network_api',
				'value' => ( isset( $options['enable_network_api'] ) ) ? $options['enable_network_api'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the enable_cloning checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowCloning( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'enable_cloning',
				'name' => $this->getSlug(),
				'option' => 'enable_cloning',
				'value' => ( isset( $options['enable_cloning'] ) ) ? $options['enable_cloning'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the enable_thincc_weblinks checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowThinCcWeblinks( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'enable_thincc_weblinks',
				'name' => $this->getSlug(),
				'option' => 'enable_thincc_weblinks',
				'value' => ( isset( $options['enable_thincc_weblinks'] ) ) ? $options['enable_thincc_weblinks'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the enable_thincc_weblinks checkbox.
	 *
	 * @param array $args
	 */
	function renderNetworkExcludeNonCataloguedPublicBooks( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => self::NETWORK_DIRECTORY_EXCLUDED,
				'name' => $this->getSlug(),
				'option' => self::NETWORK_DIRECTORY_EXCLUDED,
				'value' => ( isset( $options[ self::NETWORK_DIRECTORY_EXCLUDED ] ) ) ? $options[ self::NETWORK_DIRECTORY_EXCLUDED ] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the iframe_whitelist textarea.
	 *
	 * @param $args
	 */
	function renderIframesWhiteList( $args ) {
		unset( $args['label_for'], $args['class'] );
		$options = get_site_option( $this->getSlug() );
		$this->renderTextarea(
			[
				'id' => 'iframe_whitelist',
				'name' => $this->getSlug(),
				'option' => 'iframe_whitelist',
				'value' => ( isset( $options['iframe_whitelist'] ) ) ? $options['iframe_whitelist'] : '',
				'description' => $args[0],
			]
		);
	}

	/**
	 * Get the slug for the network export options page.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
		return 'pressbooks_sharingandprivacy_options';
	}

	/**
	 * Get the localized title of the network export options tab.
	 *
	 * @return string $title
	 */
	static function getTitle() {
		return __( 'Sharing and Privacy Settings', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the network export options page.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return [
			'allow_redistribution'           => 0,
			'enable_network_api'             => 1,
			'enable_cloning'                 => 1,
			'enable_thincc_weblinks'         => 1,
			'iframe_whitelist'               => '',
			self::NETWORK_DIRECTORY_EXCLUDED => 0,
		];
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return [
			'allow_redistribution',
			'enable_network_api',
			'enable_cloning',
			'enable_thincc_weblinks',
			self::NETWORK_DIRECTORY_EXCLUDED,
		];
	}

	/**
	 * Get an array of options which return multiline strings.
	 *
	 * @return array $options
	 */
	static function getMultilineStringOptions() {
		return [
			'iframe_whitelist',
		];
	}

	/**
	 * Filter the array of default values for this set of options
	 *
	 * @param array $defaults
	 *
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {
		return $defaults;
	}
}
