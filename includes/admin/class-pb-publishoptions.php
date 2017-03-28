<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin;

class PublishOptions extends \Pressbooks\Options {
	/**
	 * The value for option: pressbooks_ecommerce_links_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 1;

	/**
	* Publish options.
	*
	* @var array
	*/
	public $options;

	/**
	* Publish defaults.
	*
	* @var array
	*/
	public $defaults;

	/**
	* Constructor.
	*
	* @param array $options
	*/
	function __construct( array $options ) {
			$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->urls = $this->getUrlOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Configure the publish options page using the settings API.
	 */
	function init() {
		$_page = $_option = $this->getSlug();
		$_section = $this->getSlug() . '_section';

		add_settings_section(
			$_section,
			'',
			array( $this, 'display' ),
			$_page
		);

		add_settings_field(
			'amazon',
			__( 'Amazon URL', 'pressbooks' ),
			array( $this, 'renderAmazonField' ),
			$_page,
			$_section
		);

		add_settings_field(
			'oreilly',
			__( 'O\'Reilly URL', 'pressbooks' ),
			array( $this, 'renderOReillyField' ),
			$_page,
			$_section
		);

		add_settings_field(
			'barnesandnoble',
			__( 'Barnes and Noble URL', 'pressbooks' ),
			array( $this, 'renderBarnesAndNobleField' ),
			$_page,
			$_section
		);

		add_settings_field(
			'kobo',
			__( 'Kobo URL', 'pressbooks' ),
			array( $this, 'renderKoboField' ),
			$_page,
			$_section
		);

		add_settings_field(
			'ibooks',
			__( 'iBooks URL', 'pressbooks' ),
			array( $this, 'renderiBooksField' ),
			$_page,
			$_section
		);

		add_settings_field(
			'otherservice',
			__( 'Other Service URL', 'pressbooks' ),
			array( $this, 'renderOtherServiceField' ),
			$_page,
			$_section
		);

		register_setting(
			$_page,
			$_option,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Display the publish options page description.
	 */
	function display() {
		ob_start(); ?>
		<p><?php _e( 'Once your book is finished, you can download the files and submit them to ebookstores and print-on-demand providers. And if you like, Pressbooks can print your books, and ship them right to your door.', 'pressbooks' ); ?></p>

		<div class="postbox">
			<div class="inside">
				<h3><?php _e( 'Ebook Stores', 'pressbooks' ); ?></h3>
				<p><?php printf( __( 'Once you have downloaded your files, you can either submit them to ebookstores yourself, or use a third-party distributor. Recommended self-serve ebookstores are <a href="%1$1s">Kindle</a>, <a href="%2$2s">Kobo</a>, and <a href="%3$3s">Nook</a>. Other ebook stores include Apple iBooks and Google.', 'pressbooks' ), 'https://kdp.amazon.com', 'https://www.kobo.com/writinglife', 'https://www.nookpress.com' ); ?></p>
				<p><?php printf( __( 'If you do not wish to submit your ebooks yourself, we recommend using a third-party distribution service such as <a href="%1s">IngramSpark</a>, which can also make your books available online in print.', 'pressbooks' ), 'https://ingramspark.com' ); ?></p>

				<h3><?php _e( 'Print-on-Demand', 'pressbooks' ); ?></h3>
				<p><?php printf( __( 'If you wish to sell your printed books online, we recommend going through <a href="%1$1s">IngramSpark</a> or Amazon\'s <a href="%2$2s">CreateSpace</a>.', 'pressbooks' ), 'https://ingramspark.com', 'https://www.createspace.com' ); ?></p>

				<h3><?php _e( 'Ordering Printed Books', 'pressbooks' ); ?></h3>
				<p><?php printf( __( 'Pressbooks can print your books and send them to you. For pricing and more details, please send an email to: <a href="%1s">print@pressbooks.com</a>', 'pressbooks' ), 'mailto:print@pressbooks.com' ); ?></p>
			</div>
		</div>

		<h3><?php _e( 'Adding BUY Links to Your Pressbooks Web Book', 'pressbooks' ); ?></h3>
		<p><?php _e( 'If you would like to add <strong>BUY</strong> links to your Pressbooks web book, add the links to your book at the different retailers below:', 'pressbooks' ); ?></p>

		<?php $output = ob_get_contents();
		ob_end_clean();

		echo apply_filters( 'pressbooks_publish_page', $output );
	}

	function render() {
	?>
		<div class="wrap">
			<h1><?php echo $this->getTitle(); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( $this->getSlug() );
				do_settings_sections( $this->getSlug() );
				submit_button(); ?>
			</form>
		</div> <?php
	}

	function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		}
	}

	function doInitialUpgrade() {}

	/**
	 * Render the amazon field.
	 * @param array $args
	 */
	function renderAmazonField() {
		$this->renderField( array(
			'id' => 'amazon',
			'name' => $this->getSlug(),
			'option' => 'amazon',
			'value' => ( isset( $this->options['amazon'] ) ) ? $this->options['amazon'] : '',
			'type' => 'url',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the oreilly field.
	 * @param array $args
	 */
	function renderOReillyField() {
		$this->renderField( array(
			'id' => 'oreilly',
			'name' => $this->getSlug(),
			'option' => 'oreilly',
			'value' => ( isset( $this->options['oreilly'] ) ) ? $this->options['oreilly'] : '',
			'type' => 'url',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the barnesandnoble field.
	 * @param array $args
	 */
	function renderBarnesAndNobleField() {
		$this->renderField( array(
			'id' => 'barnesandnoble',
			'name' => $this->getSlug(),
			'option' => 'barnesandnoble',
			'value' => ( isset( $this->options['barnesandnoble'] ) ) ? $this->options['barnesandnoble'] : '',
			'type' => 'url',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the barnesandnoble field.
	 * @param array $args
	 */
	function renderKoboField() {
		$this->renderField( array(
			'id' => 'kobo',
			'name' => $this->getSlug(),
			'option' => 'kobo',
			'value' => ( isset( $this->options['kobo'] ) ) ? $this->options['kobo'] : '',
			'type' => 'url',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the ibooks field.
	 * @param array $args
	 */
	function renderiBooksField() {
		$this->renderField( array(
			'id' => 'ibooks',
			'name' => $this->getSlug(),
			'option' => 'ibooks',
			'value' => ( isset( $this->options['ibooks'] ) ) ? $this->options['ibooks'] : '',
			'type' => 'url',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the ibooks field.
	 * @param array $args
	 */
	function renderOtherServiceField() {
		$this->renderField( array(
			'id' => 'otherservice',
			'name' => $this->getSlug(),
			'option' => 'otherservice',
			'value' => ( isset( $this->options['otherservice'] ) ) ? $this->options['otherservice'] : '',
			'type' => 'url',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Get the slug for the publish options page.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
		return 'pressbooks_ecommerce_links';
	}

	/**
	 * Get the localized title of the export options page.
	 *
	 * @return string $title
	 */
	static function getTitle() {
		return __( 'Publish', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the export options page.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return array(
			'amazon' => '',
			'oreilly' => '',
			'barnesandnoble' => '',
			'kobo' => '',
			'ibooks' => '',
			'otherservice' => '',
		);
	}

	/**
	 * Get an array of options which return URLs.
	 *
	 * @return array $options
	 */
	static function getUrlOptions() {
		return array(
			'amazon',
			'oreilly',
			'barnesandnoble',
			'kobo',
			'ibooks',
			'otherservice',
		);
	}

	/**
	 * Filter the array of default values for this set of options
	 *
	 * @param array $defaults
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {
		return $defaults;
	}
}
