<?php

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

}
