<?php

use Pressbooks\Modules\ThemeOptions\PDFOptions;

class OptionsMock extends \Pressbooks\Options {
	/**
	 * The value for option: pressbooks_mock_options_version
	 *
	 * @see upgrade()
	 * @var int
	 * @group options
	 */
	const VERSION = 1;

	/**
	 * Export options.
	 *
	 * @var array
	 * @group options
	 */
	public $options;

	/**
	 * Export defaults.
	 *
	 * @var array
	 * @group options
	 */
	public $defaults;

	/**
	 * Constructor.
	 *
	 * @param array $options The retrieved options.
	 * @group options
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
	 * @group options
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
	 * @group options
	 */
	function display() {
		echo '<p>' . 'Mock settings.' . '</p>';
	}

	/**
	 * @group options
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
	 * @group options
	 */
	function upgrade( $version ) {
		// Unnecessary.
	}

	/**
	 * Get the slug for the mock options page.
	 *
	 * @return string $slug
	 * @group options
	 */
	static function getSlug() {
		return 'mock';
	}

	/**
	 * Get the localized title of the mock options page.
	 *
	 * @return string $title
	 * @group options
	 */
	static function getTitle() {
		return 'Mock';
	}

	/**
	 * @group options
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
	 * @group options
	 */
	static function filterDefaults( $defaults ) {
		return $defaults;
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 * @group options
	 */
	static function getBooleanOptions() {
		return [ 'option_bool' ];
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 * @group options
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
	 * @var \Pressbooks\Options
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

	public function test_sanityChecks_ShapeShifter() {
		add_filter( 'pb_is_shape_shifter_compatible', '__return_true' );
		$options[] = '\Pressbooks\Modules\ThemeOptions\EbookOptions';;
		$options[] = '\Pressbooks\Modules\ThemeOptions\PDFOptions';
		$options[] = '\Pressbooks\Modules\ThemeOptions\WebOptions';
		$this->test_sanityChecks( $options );
		remove_filter( 'pb_is_shape_shifter_compatible', '__return_true' );
	}

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

	public function test_renderColorField() {
		ob_start();
		$output = \Pressbooks\Options::renderColorField([
			'id' => 'test_color',
			'name' => 'pressbooks_options_test',
			'option' => 'test_color',
			'value' => '',
			'default' => '#c00'
		]);
		$buffer = ob_get_clean();

		$this->assertEquals( '<input id="test_color" class="color-picker" name="pressbooks_options_test[test_color]" type="text" data-default-color="#c00" value="" />', $buffer );
	}

	public function test_renderCheckbox() {
		ob_start();
		$output = \Pressbooks\Options::renderCheckbox([
			'id' => 'test_checkbox',
			'name' => 'pressbooks_options_test',
			'option' => 'test_checkbox',
			'value' => 1,
			'label' => 'Test Checkbox'
		]);
		$buffer = ob_get_clean();

		$this->assertEquals( '<input id="test_checkbox" name="pressbooks_options_test[test_checkbox]" type="checkbox" value="1"  checked=\'checked\'/><label for="test_checkbox">Test Checkbox</label>', $buffer );
	}

	public function test_deleteCacheAfterUpdate() {
		$now = time() - 60;
		set_transient( 'pb_cache_deleted', $now, DAY_IN_SECONDS );

		\Pressbooks\Options::deleteCacheAfterUpdate( 'wordpress_foo_bar' );
		$this->assertEquals( $now, get_transient( 'pb_cache_deleted' ) );

		\Pressbooks\Options::deleteCacheAfterUpdate( 'pressbooks_foo_bar' );
		$this->assertNotEquals( $now, get_transient( 'pb_cache_deleted' ) );
	}

	public function test_getOption() {
		$opt = new \Pressbooks\Admin\Network\SharingAndPrivacyOptions( [] );
		$this->assertNotNull( $opt::getOption( 'enable_cloning' ) );
		$this->assertNull( $opt::getOption( 'does_not_exist' ) );

		$opt = new \Pressbooks\Admin\ExportOptions( [] );
		$this->assertNotNull( $opt::getOption( 'email_validation_logs' ) );
		$this->assertNull( $opt::getOption( 'does_not_exist' ) );
	}

	public function test_renderBodyFontField() {

		$fonts = \Pressbooks\Container::get( 'Styles' )->getShapeShifterFonts();

		$options = new \Pressbooks\Modules\ThemeOptions\EbookOptions( [] );
		ob_start();
		$options->renderBodyFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );

		$options = new \Pressbooks\Modules\ThemeOptions\PDFOptions( [] );
		ob_start();
		$options->renderBodyFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );


		$options = new \Pressbooks\Modules\ThemeOptions\WebOptions( [] );
		ob_start();
		$options->renderBodyFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );

	}

	public function test_renderHeaderFontField() {

		$fonts = \Pressbooks\Container::get( 'Styles' )->getShapeShifterFonts();

		$options = new \Pressbooks\Modules\ThemeOptions\EbookOptions( [] );
		ob_start();
		$options->renderHeaderFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );

		$options = new \Pressbooks\Modules\ThemeOptions\PDFOptions( [] );
		ob_start();
		$options->renderHeaderFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );


		$options = new \Pressbooks\Modules\ThemeOptions\WebOptions( [] );
		ob_start();
		$options->renderHeaderFontField( $fonts );
		$buffer = ob_get_clean();
		$this->assertContains( '</optgroup>', $buffer );

	}

}
