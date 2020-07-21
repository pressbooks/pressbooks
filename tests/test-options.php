<?php

use Pressbooks\Modules\ThemeOptions\PDFOptions;
use \Pressbooks\Admin\Network\SharingAndPrivacyOptions;

class OptionsMock extends \Pressbooks\Options {
	/**
	 * The value for option: pressbooks_mock_options_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 1;

	/**
	 * Export options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Export defaults.
	 *
	 * @var array
	 */
	public $defaults;

	/**
	 * Constructor.
	 *
	 * @param array $options The retrieved options.
	 */
	function __construct( array $options ) {
		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();
		$this->strings = $this->getStringOptions();
		$this->integers = $this->getIntegerOptions();
		$this->floats = $this->getFloatOptions();
		$this->predefined = $this->getPredefinedOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 *
	 */
	function init() {
		$_page = $_option = $this->getSlug();
		$_section = $this->getSlug() . '_section';

		add_settings_section(
			$_section,
			'',
			[ $this, 'display' ],
			$_page
		);

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Display the mock options page description.
	 */
	function display() {
		echo '<p>' . 'Mock settings.' . '</p>';
	}

	/**
	 *
	 */
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

	/**
	 *
	 */
	function upgrade( $version ) {
		// Unnecessary.
	}

	/**
	 * Get the slug for the mock options page.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
		return 'mock';
	}

	/**
	 * Get the localized title of the mock options page.
	 *
	 * @return string $title
	 */
	static function getTitle() {
		return 'Mock';
	}

	/**
	 *
	 */
	static function getDefaults() {
		return [
			'option_bool' => 1,
			'option_string' => 'foo',
			'option_int' => 42,
			'option_float' => 2.5,
			'option_predef' => 'European Swallow',
		];
	}

	/**
	 * Filter the array of default values for this set of options
	 *
	 * @param array $defaults The input array of default values.
	 *
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {
		return $defaults;
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return [ 'option_bool' ];
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	static function getStringOptions() {
		return [ 'option_string' ];
	}

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	static function getIntegerOptions() {
		return [ 'option_int' ];
	}

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	static function getFloatOptions() {
		return [ 'option_float' ];
	}

	/**
	 * Get an array of options which return predefined values.
	 *
	 * @return array $options
	 */
	static function getPredefinedOptions() {
		return [ 'option_predef' ];
	}
}

class OptionsTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var OptionsMock
	 */
	protected $options;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->options = new \OptionsMock(
			[
				'option_bool' => '1',
				'option_string' => 'foo',
				'option_int' => '42',
				'option_float' => '2.5',
				'option_predef' => 'European Swallow',
			]
		);
	}

	/**
	 * @group options
	 */
	public function test_sanityChecks( $options = [] ) {
		$this->_book(); // We need Book Info now :(

		if ( empty( $options ) ) {
			$options[] = '\Pressbooks\Modules\ThemeOptions\EbookOptions';
			$options[] = '\Pressbooks\Modules\ThemeOptions\GlobalOptions';
			$options[] = '\Pressbooks\Modules\ThemeOptions\PDFOptions';
			$options[] = '\Pressbooks\Modules\ThemeOptions\WebOptions';
			$options[] = '\Pressbooks\Admin\PublishOptions';
			$options[] = '\Pressbooks\Admin\ExportOptions';
			$options[] = '\Pressbooks\Admin\Network\SharingAndPrivacyOptions';
		}

		foreach ( $options as $option ) {
			/** @var \Pressbooks\Options $opt */
			$opt = new $option( [] );
			ob_start();
			$opt->init();
			$opt->display();
			$opt->render();
			$buffer = ob_get_clean();
			$this->assertNotEmpty( $buffer );

			$slug = $opt::getSlug();
			$this->assertTrue( is_string( $slug ) );

			$title = $opt::getTitle();
			$this->assertTrue( is_string( $title ) );

			$defaults = $opt::filterDefaults( $opt::getDefaults() );
			$this->assertTrue( is_array( $defaults ) );

			if ( method_exists( $option, 'scssOverrides' ) ) {
				$this->assertStringStartsWith( 'HELLO WORLD', $option::scssOverrides( "HELLO WORLD \n" ) );
			}
		}
	}

	/**
	 * @group options
	 */
	public function test_sanityChecks_ShapeShifter() {
		add_filter( 'pb_is_shape_shifter_compatible', '__return_true' );
		$options[] = '\Pressbooks\Modules\ThemeOptions\EbookOptions';;
		$options[] = '\Pressbooks\Modules\ThemeOptions\PDFOptions';
		$options[] = '\Pressbooks\Modules\ThemeOptions\WebOptions';
		$this->test_sanityChecks( $options );
		remove_filter( 'pb_is_shape_shifter_compatible', '__return_true' );
	}

	/**
	 * @group options
	 */
	public function test_sanitize() {

		// Test empty boolean.
		$input = [];
		$result = $this->options->sanitize( $input );
		$this->assertArrayHasKey( 'option_bool', $result );
		$this->assertEquals( $result['option_bool'], 0 );

		// Test null boolean.
		$input = [
			'option_bool' => null,
		];
		$result = $this->options->sanitize( $input );
		$this->assertArrayHasKey( 'option_bool', $result );
		$this->assertEquals( $result['option_bool'], 0 );

		// Test true boolean.
		$input = [
			'option_bool' => '1',
		];
		$result = $this->options->sanitize( $input );
		$this->assertArrayHasKey( 'option_bool', $result );
		$this->assertEquals( $result['option_bool'], 1 );

		// Test string.
		$input = [
			'option_string' => 'String that needs sanitizing.<script></script>',
		];
		$result = $this->options->sanitize( $input );
		$this->assertArrayHasKey( 'option_string', $result );
		$this->assertEquals( $result['option_string'], 'String that needs sanitizing.' );

		// Test integer.
		$input = [
			'option_int' => '42',
		];
		$result = $this->options->sanitize( $input );
		$this->assertArrayHasKey( 'option_int', $result );
		$this->assertEquals( $result['option_int'], 42 );

		// Test float.
		$input = [
			'option_float' => '1.5',
		];
		$result = $this->options->sanitize( $input );
		$this->assertArrayHasKey( 'option_float', $result );
		$this->assertEquals( $result['option_float'], 1.5 );

		// Test predefined.
		$input = [
			'option_predef' => 'European Swallow',
		];
		$result = $this->options->sanitize( $input );
		$this->assertArrayHasKey( 'option_predef', $result );
		$this->assertEquals( $result['option_predef'], 'European Swallow' );
	}

	/**
	 * @group options
	 */
	function test_PDFOptions_replaceRunningContentTags() {
		$v = PDFOptions::replaceRunningContentTags( '%book_title%' );
		$this->assertEquals( '"" string(book-title) ""', $v );

		$v = PDFOptions::replaceRunningContentTags( 'blah %book_title% blah' );
		$this->assertEquals( '"blah " string(book-title) " blah"', $v );

		$v = PDFOptions::replaceRunningContentTags( '%blank%' );
		$this->assertEquals( '""', $v );

		$v = PDFOptions::replaceRunningContentTags( 'blah %blank% blah' );
		$this->assertEquals( '"blah  blah"', $v );
	}

	/**
	 * @group options
	 */
	public function test_renderColorField() {
		ob_start();
		\Pressbooks\Options::renderColorField([
			'id' => 'test_color',
			'name' => 'pressbooks_options_test',
			'option' => 'test_color',
			'value' => '',
			'default' => '#c00'
		]);
		$buffer = ob_get_clean();

		$this->assertEquals( '<input id="test_color" class="color-picker" name="pressbooks_options_test[test_color]" type="text" data-default-color="#c00" value="" />', $buffer );
	}

	/**
	 * @group options
	 */
	public function test_renderCheckbox() {
		ob_start();
		\Pressbooks\Options::renderCheckbox([
			'id' => 'test_checkbox',
			'name' => 'pressbooks_options_test',
			'option' => 'test_checkbox',
			'value' => 1,
			'label' => 'Test Checkbox'
		]);
		$buffer = ob_get_clean();

		$this->assertEquals( '<input id="test_checkbox" name="pressbooks_options_test[test_checkbox]" type="checkbox" value="1"  checked=\'checked\'/><label for="test_checkbox">Test Checkbox</label>', $buffer );
	}

	/**
	 * @group options
	 */
	public function test_deleteCacheAfterUpdate() {
		$now = time() - 60;
		set_transient( 'pb_cache_deleted', $now, DAY_IN_SECONDS );

		\Pressbooks\Options::deleteCacheAfterUpdate( 'wordpress_foo_bar' );
		$this->assertEquals( $now, get_transient( 'pb_cache_deleted' ) );

		\Pressbooks\Options::deleteCacheAfterUpdate( 'pressbooks_foo_bar' );
		$this->assertNotEquals( $now, get_transient( 'pb_cache_deleted' ) );
	}

	/**
	 * @group options
	 */
	public function test_getOption() {
		$opt = new \Pressbooks\Admin\Network\SharingAndPrivacyOptions( [] );
		$this->assertNotNull( $opt::getOption( 'enable_cloning' ) );
		$this->assertNull( $opt::getOption( 'does_not_exist' ) );

		$opt = new \Pressbooks\Admin\ExportOptions( [] );
		$this->assertNotNull( $opt::getOption( 'email_validation_logs' ) );
		$this->assertNull( $opt::getOption( 'does_not_exist' ) );
	}

	/**
	 * @group options
	 */
	public function test_renderBodyFontField() {

		$fonts = \Pressbooks\Container::get( 'Styles' )->getShapeShifterFonts();

		$options = new \Pressbooks\Modules\ThemeOptions\EbookOptions( [] );
		ob_start();
		$options->renderBodyFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );
		$this->assertContains( '<select name="pressbooks_theme_options_ebook[ebook_body_font]"', $buffer );

		$options = new \Pressbooks\Modules\ThemeOptions\PDFOptions( [] );
		ob_start();
		$options->renderBodyFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );
		$this->assertContains( '<select name="pressbooks_theme_options_pdf[pdf_body_font]"', $buffer );


		$options = new \Pressbooks\Modules\ThemeOptions\WebOptions( [] );
		ob_start();
		$options->renderBodyFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );
		$this->assertContains( '<select name="pressbooks_theme_options_web[webbook_body_font]"', $buffer );

	}

	/**
	 * @group options
	 */
	public function test_renderHeaderFontField() {

		$fonts = \Pressbooks\Container::get( 'Styles' )->getShapeShifterFonts();

		$options = new \Pressbooks\Modules\ThemeOptions\EbookOptions( [] );
		ob_start();
		$options->renderHeaderFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );
		$this->assertContains( '<select name="pressbooks_theme_options_ebook[ebook_header_font]"', $buffer );

		$options = new \Pressbooks\Modules\ThemeOptions\PDFOptions( [] );
		ob_start();
		$options->renderHeaderFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );
		$this->assertContains( '<select name="pressbooks_theme_options_pdf[pdf_header_font]"', $buffer );


		$options = new \Pressbooks\Modules\ThemeOptions\WebOptions( [] );
		ob_start();
		$options->renderHeaderFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );
		$this->assertContains( '<select name="pressbooks_theme_options_web[webbook_header_font]"', $buffer );

	}

	/**
	 * @group options
	 */
	public function test_optionNetworkDirectoryExcluded() {
		$_option = SharingAndPrivacyOptions::getSlug();
		$privacy_options = new SharingAndPrivacyOptions( [ 'network_directory_excluded' => 1 ] );
		$_REQUEST['_wpnonce'] = wp_create_nonce( $_option . '-options' );
		$_POST['network_directory_excluded'] = '0';
		ob_start();
		$privacy_options->render();
		$buffer = ob_get_clean();

		$this->assertContains( '<input id="network_directory_excluded" name="pressbooks_sharingandprivacy_options[network_directory_excluded]" type="checkbox" value="1" />', $buffer );
	}
}
