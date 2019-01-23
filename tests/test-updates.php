<?php

use Pressbooks\Updates;

class UpdatesTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Updates
	 */
	protected $updates;

	/**
	 */
	public function setUp() {
		parent::setUp();
		// Create fake incompatible plugin
		copy( WP_PLUGIN_DIR . '/hello.php', WP_PLUGIN_DIR . '/pressbooks-hello.php' );
		activate_plugin( 'pressbooks-hello.php' );
		$this->updates = new Updates();
	}

	public function tearDown() {
		parent::tearDown();
		// Remove fake incompatible plugin
		deactivate_plugins( 'pressbooks-hello.php' );
		unlink( WP_PLUGIN_DIR . '/pressbooks-hello.php' );
	}

	public function test_init() {
		$instance = Updates::init();
		$this->assertTrue( $instance instanceof \Pressbooks\Updates );
	}

	public function test_gitHubUpdater() {
		$this->updates->gitHubUpdater();
		$this->assertTrue( has_filter( 'puc_is_slug_in_use-pressbooks' ) );
	}

	public function test_inPluginUpdateMessage() {
		ob_start();
		$this->updates->inPluginUpdateMessage( [ 'new_version' => '9.9.9.' ] );
		$buffer = ob_get_clean();
		$this->assertContains( '<thead>', $buffer );
	}

//	public function test_coreUpgradePreamble() {
//		// TODO: How do we fake that there's a newer version of Pressbooks available?
//		// ob_start();
//		// $this->updates->coreUpgradePreamble();
//		// $buffer = ob_get_clean();
//		// $this->assertContains( '<thead>', $buffer );
//	}

	public function test_getBaseName() {
		$basename = $this->updates->getBaseName();
		$this->assertContains( 'pressbooks', $basename );
	}

	public function test_extraPluginHeaders() {
		$headers = $this->updates->extraPluginHeaders( [] );
		$this->assertArrayHasKey( 'PBTested', $headers );
	}

	public function test_getUntestedPlugins() {
		$list = $this->updates->getUntestedPlugins( '5.0.0' );
		$this->assertTrue( is_array( $list ) );
	}

	public function test_getPluginsWithHeader() {
		$list = $this->updates->getPluginsWithHeader( Updates::VERSION_TESTED_HEADER );
		$this->assertTrue( is_array( $list ) );
	}

	public function test_getPluginsWithPressbooksInDescription() {
		$list = $this->updates->getPluginsWithPressbooksInDescription();
		$this->assertTrue( is_array( $list ) );
	}


}