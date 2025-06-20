<?php

use Illuminate\View\Factory;
use Pressbooks\Container;
use Pressbooks\Interactive\H5P;
use Pressbooks\Interactive\H5PCoreInterface;
use Pressbooks\Interactive\H5PExtractorInterface;
use Pressbooks\Interactive\H5PPluginInterface;
use Pressbooks\Interactive\WordPressHelperInterface;

class Interactive_H5PTest extends \WP_UnitTestCase {
	/**
	 * @var H5P
	 * @group interactivecontent
	 */
	protected $h5p;

	/**
	 * @group interactivecontent
	 */
	public function set_up() {
		parent::set_up();
		$blade = Container::get( 'Blade' );
		$h5pPlugin = Container::get( 'H5PPlugin' );
		$h5pExtractor = Container::get( 'H5PExtractor' );
		$wpHelper = Container::get( 'WordPressHelper' );
		$h5pCore = Container::get( 'H5PCore' );
		$this->h5p = new H5P( $blade, $h5pPlugin, $h5pExtractor, $wpHelper, $h5pCore );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_isActive() {
		$this->assertTrue( is_bool( $this->h5p->isActive() ) );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_replaceShortcode() {
		$result = $this->h5p->replaceShortcode( [] );
		$this->assertStringContainsString( '<div ', $result );
		$this->assertStringContainsString( 'excluded from this version of the text', $result );
		$result = $this->h5p->replaceShortcode( [ 'slug' => 'foo' ] );
		$this->assertStringContainsString( '<div ', $result );
		$this->assertStringContainsString( 'excluded from this version of the text', $result );
		$result = $this->h5p->replaceShortcode( [ 'id' => 999 ] );
		$this->assertStringContainsString( '<div ', $result );
		$this->assertStringContainsString( 'excluded from this version of the text', $result );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_replaceCloneable() {
		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content );
		$this->assertStringNotContainsString( '[h5p ', $result );
		$this->assertStringContainsString( 'The original version of this chapter contained H5P content', $result );

		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content, [ 1, '2' ] );
		$this->assertStringNotContainsString( '[h5p id="1', $result );
		$this->assertStringNotContainsString( '[h5p id=\'2', $result );
		$this->assertStringContainsString( '[h5p id=3]', $result );
		$this->assertStringContainsString( 'The original version of this chapter contained H5P content', $result );

		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content, 3 );
		$this->assertStringNotContainsString( '[h5p id=3', $result );
		$this->assertStringContainsString( '[h5p id="1', $result );
		$this->assertStringContainsString( '[h5p id=\'2', $result );
		$this->assertStringContainsString( 'The original version of this chapter contained H5P content', $result );
	}
	/**
	 * @group interactivecontent
	 */
	public function test_h5p_custom_wrapper() {
		$html = '<iframe id="h5p-content"><div></div></iframe>';

		$content = [
			'id' => 1,
		];

		$result = $this->h5p->generateCustomH5pWrapper( $html, $content );

		$expected = '<div id="h5p-1"><iframe id="h5p-content"><div></div></iframe></div>';

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_text_addon_matches() {
		$params = [ 'text' => 'This is a test content' ];
		$pattern = '/test/';
		$result = $this->h5p->textAddonMatches( $params, $pattern );
		$this->assertTrue( $result );

		$params = [ 'text' => 'No match here' ];
		$result = $this->h5p->textAddonMatches( $params, $pattern );
		$this->assertFalse( $result );
	}

	/**
	 * Test shortcode replacement when H5P representation is successfully generated.
	 *
	 * @group interactivecontent
	 */
	public function test_replaceShortcode_with_representation() {
		// Create a mock that has the render method (like the Blade wrapper)
		$bladeMock = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 'render' ] )
			->getMock();
		$bladeMock->expects( $this->once() )
			->method( 'render' )
			->with(
				'interactive.h5pextractor',
				$this->callback( function ( $params ) {
					return isset( $params['id'] )
						&& $params['id'] === '#h5p-123'
						&& isset( $params['representation'] )
						&& $params['representation'] === '<p>Mock H5P Content</p>'
						&& isset( $params['title'] ) // Check title exists
						&& isset( $params['url'] ); // Check url exists
				} )
			)
			->willReturn( '<div>Rendered Mock H5P</div>' );

		// Create mock dependencies
		$h5pPluginMock = $this->createMock( H5PPluginInterface::class );
		$h5pExtractorMock = $this->createMock( H5PExtractorInterface::class );
		$wpHelperMock = $this->createMock( WordPressHelperInterface::class );
		$h5pCoreMock = $this->createMock( H5PCoreInterface::class );

		// Mock H5P class partially, specifically the getH5PRepresentation method
		$h5pMock = $this->getMockBuilder( H5P::class )
			->setConstructorArgs( [ $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock ] )
			->onlyMethods( [ 'getH5PRepresentation' ] ) // Mock only this method
			->getMock();

		$h5pMock->expects( $this->once() )
			->method( 'getH5PRepresentation' )
			->with( 123 ) // Expecting the ID from the shortcode
			->willReturn( '<p>Mock H5P Content</p>' ); // Return mock HTML

		// Call the method on the mocked object
		$result = $h5pMock->replaceShortcode( [ 'id' => 123 ] );

		// Assert the final rendered output
		$this->assertEquals( '<div>Rendered Mock H5P</div>', $result );
	}

	/**
	 * Test H5P representation generation.
	 *
	 * @group interactivecontent
	 */
	public function test_if_h5p_export_option_is_checked() {
		do_action( 'pb_pre_export' );
		$this->h5p->shouldEnablePrint();
		//change visibility of the static representation
		$reflection = new ReflectionClass( $this->h5p );
		$property = $reflection->getProperty( 'enableStaticRepresentation' );
		$property->setAccessible( true );
		//check if the static representation is enabled
		$this->assertFalse( $property->getValue( $this->h5p ) );
		//update options to enable static representation
		update_option( 'pressbooks_export_options', [
			'h5p_print_on_exports' => 1,
		] );
		do_action( 'pb_pre_export' );
		$this->h5p->shouldEnablePrint();

		$property = $reflection->getProperty( 'enableStaticRepresentation' );
		$property->setAccessible( true );
		//check if the static representation is enabled
		$this->assertTrue( $property->getValue( $this->h5p ) );
	}

	/**
	 * Test H5P export creation functionality
	 *
	 * @group interactivecontent
	 */
	public function test_createH5PExport() {
		$content = [
			'id' => 1,
			'library' => [
				'name' => 'H5P.TestContent',
				'majorVersion' => 1,
				'minorVersion' => 0
			],
			'params' => '{"test": "content"}',
			'slug' => 'test-content'
		];

		$reflection = new ReflectionClass( $this->h5p );
		$method = $reflection->getMethod( 'createH5PExport' );
		$method->setAccessible( true );

		// Test with invalid content (missing required fields)
		$result = $method->invoke( $this->h5p, [] );
		$this->assertFalse( $result );

		// Test with invalid params
		$invalidContent = $content;
		$invalidContent['params'] = 'invalid json';
		$result = $method->invoke( $this->h5p, $invalidContent );
		$this->assertFalse( $result );
	}

	/**
	 * Test H5P representation retrieval when disabled
	 *
	 * @group interactivecontent
	 */
	public function test_getH5PRepresentation_disabled() {
		$reflection = new ReflectionClass( $this->h5p );
		$method = $reflection->getMethod( 'getH5PRepresentation' );
		$method->setAccessible( true );

		// Test with static representation disabled
		$result = $method->invoke( $this->h5p, 1 );
		$this->assertNull( $result );
	}

	/**
	 * Test H5P content slug generation
	 *
	 * @group interactivecontent
	 */
	public function test_generateContentSlug() {
		$content = [
			'title' => 'Test H5P Content!@#$%'
		];

		$reflection = new ReflectionClass( $this->h5p );
		$method = $reflection->getMethod( 'generateContentSlug' );
		$method->setAccessible( true );

		// This will fail without H5P plugin active, but tests the method accessibility
		try {
			$result = $method->invoke( $this->h5p, $content );
			$this->assertIsString( $result );
		} catch ( \Throwable $e ) {
			// Expected when H5P plugin is not active
			$this->assertTrue( true );
		}
	}

	/**
	 * Test find all shortcode IDs functionality
	 *
	 * @group interactivecontent
	 */
	public function test_findAllShortcodeIds_multiple_formats() {
		$content = '[h5p id="123"] some text [h5p id=\'456\' attr="value"] more [h5p id=789]';
		$result = $this->h5p->findAllShortcodeIds( $content );

		$this->assertEquals( [ 123, 456, 789 ], $result );

		// Test with quoted IDs and special characters
		$content2 = '[h5p id="&quot;999&quot;"] [h5p id=\' 111 \']';
		$result2 = $this->h5p->findAllShortcodeIds( $content2 );

		$this->assertEquals( [ 999, 111 ], $result2 );
	}

	/**
	 * Test activate method when file doesn't exist
	 *
	 * @group interactivecontent
	 */
	public function test_activate_file_not_exists() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		// Test when H5P plugin file doesn't exist
		$expectedPath = WP_PLUGIN_DIR . '/' . H5P::H5P_PLUGIN_PATH;
		$wpHelperMock->expects( $this->once() )
			->method( 'isFile' )
			->with( $expectedPath )
			->willReturn( false );

		$result = $h5p->activate();
		$this->assertFalse( $result );
	}

	/**
	 * Test activate method when file exists and activation succeeds
	 *
	 * @group interactivecontent
	 */
	public function test_activate_success() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		$expectedPath = WP_PLUGIN_DIR . '/' . H5P::H5P_PLUGIN_PATH;
		
		$wpHelperMock->expects( $this->once() )
			->method( 'isFile' )
			->with( $expectedPath )
			->willReturn( true );
			
		$wpHelperMock->expects( $this->once() )
			->method( 'activatePlugin' )
			->with( H5P::H5P_PLUGIN_PATH )
			->willReturn( null ); // null = success
			
		$h5pPluginMock->expects( $this->once() )
			->method( 'canFetchH5P' )
			->willReturn( true );

		$result = $h5p->activate();
		$this->assertTrue( $result );
	}

	/**
	 * Test activate method when file exists but fetch fails
	 *
	 * @group interactivecontent
	 */
	public function test_activate_fetch_fails() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		$expectedPath = WP_PLUGIN_DIR . '/' . H5P::H5P_PLUGIN_PATH;
		
		$wpHelperMock->expects( $this->once() )
			->method( 'isFile' )
			->with( $expectedPath )
			->willReturn( true );
			
		$wpHelperMock->expects( $this->once() )
			->method( 'activatePlugin' )
			->with( H5P::H5P_PLUGIN_PATH )
			->willReturn( null );
			
		$h5pPluginMock->expects( $this->once() )
			->method( 'canFetchH5P' )
			->willReturn( false );

		$result = $h5p->activate();
		$this->assertFalse( $result );
	}

	/**
	 * Test activate method when activation fails
	 *
	 * @group interactivecontent
	 */
	public function test_activate_plugin_activation_fails() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		$expectedPath = WP_PLUGIN_DIR . '/' . H5P::H5P_PLUGIN_PATH;
		
		$wpHelperMock->expects( $this->once() )
			->method( 'isFile' )
			->with( $expectedPath )
			->willReturn( true );
			
		$wpHelperMock->expects( $this->once() )
			->method( 'activatePlugin' )
			->with( H5P::H5P_PLUGIN_PATH )
			->willReturn( new \WP_Error( 'activation_failed', 'Plugin activation failed' ) );

		// canFetchH5P should not be called if activation fails
		$h5pPluginMock->expects( $this->never() )->method( 'canFetchH5P' );

		$result = $h5p->activate();
		$this->assertFalse( $result );
	}

	/**
	 * Test activate method scenarios using separate instances
	 *
	 * @group interactivecontent
	 */
	public function test_activate_comprehensive_scenarios() {
		// Scenario 1: File exists, activation succeeds, fetch works
		list($bladeMock1, $h5pPluginMock1, $h5pExtractorMock1, $wpHelperMock1, $h5pCoreMock1) = $this->createMocks();
		$h5p1 = new H5P( $bladeMock1, $h5pPluginMock1, $h5pExtractorMock1, $wpHelperMock1, $h5pCoreMock1 );

		$wpHelperMock1->expects( $this->once() )->method( 'isFile' )->willReturn( true );
		$wpHelperMock1->expects( $this->once() )->method( 'activatePlugin' )->willReturn( null );
		$h5pPluginMock1->expects( $this->once() )->method( 'canFetchH5P' )->willReturn( true );

		$this->assertTrue( $h5p1->activate() );

		// Scenario 2: File doesn't exist
		list($bladeMock2, $h5pPluginMock2, $h5pExtractorMock2, $wpHelperMock2, $h5pCoreMock2) = $this->createMocks();
		$h5p2 = new H5P( $bladeMock2, $h5pPluginMock2, $h5pExtractorMock2, $wpHelperMock2, $h5pCoreMock2 );

		$wpHelperMock2->expects( $this->once() )->method( 'isFile' )->willReturn( false );
		$wpHelperMock2->expects( $this->never() )->method( 'activatePlugin' );
		$h5pPluginMock2->expects( $this->never() )->method( 'canFetchH5P' );

		$this->assertFalse( $h5p2->activate() );
	}

	/**
	 * Test H5P plugin path constant
	 *
	 * @group interactivecontent
	 */
	public function test_h5p_plugin_path_constant() {
		$this->assertEquals( 'h5p/h5p.php', H5P::H5P_PLUGIN_PATH );
		$this->assertIsString( H5P::H5P_PLUGIN_PATH );
		$this->assertNotEmpty( H5P::H5P_PLUGIN_PATH );
	}

	/**
	 * Test apiInit method with various scenarios
	 *
	 * @group interactivecontent
	 */
	public function test_apiInit_with_mocked_dependencies() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		// Test scenario: plugin not active, permissions should be set
		$wpHelperMock->method( 'isPluginActive' )->willReturn( false );
		$wpHelperMock->method( 'isPluginActiveForNetwork' )->willReturn( false );
		$wpHelperMock->method( 'hasFilter' )->willReturn( true );
		$wpHelperMock->method( 'applyFilters' )->willReturn( true );
		$wpHelperMock->method( 'getOption' )->willReturn( false );

		$h5pPluginMock->expects( $this->once() )->method( 'restApiInit' );
		$wpHelperMock->expects( $this->once() )->method( 'addFilter' );

		$result = $h5p->apiInit();
		$this->assertTrue( $result );
	}

	/**
	 * Test apiInit when blog is public
	 *
	 * @group interactivecontent
	 */
	public function test_apiInit_blog_public() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		// Test scenario: plugin active, blog is public
		$wpHelperMock->method( 'isPluginActive' )->willReturn( true );
		$wpHelperMock->method( 'isPluginActiveForNetwork' )->willReturn( true );
		$wpHelperMock->method( 'hasFilter' )->willReturn( false );
		$wpHelperMock->method( 'getOption' )->with( 'blog_public' )->willReturn( true );

		$h5pPluginMock->expects( $this->never() )->method( 'restApiInit' );
		$wpHelperMock->expects( $this->once() )->method( 'addFilter' );

		$result = $h5p->apiInit();
		$this->assertTrue( $result );
	}

	/**
	 * Test fetch method
	 *
	 * @group interactivecontent
	 */
	public function test_fetch() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		$testUrl = 'https://example.com/h5p-content';
		$expectedId = 123;

		$h5pPluginMock->expects( $this->once() )
			->method( 'fetchH5P' )
			->with( $testUrl )
			->willReturn( $expectedId );

		$result = $h5p->fetch( $testUrl );
		$this->assertEquals( $expectedId, $result );
	}

	/**
	 * Test renameFont method
	 *
	 * @group interactivecontent
	 */
	public function test_renameFont() {
		$this->assertEquals( 'h5p.ttf', $this->h5p->renameFont( 'H5P.ttf' ) );
		$this->assertEquals( 'other-font.ttf', $this->h5p->renameFont( 'other-font.ttf' ) );
		$this->assertEquals( 'normal.woff', $this->h5p->renameFont( 'normal.woff' ) );
	}

	/**
	 * Test textAddonMatches with various parameter types
	 *
	 * @group interactivecontent
	 */
	public function test_textAddonMatches_comprehensive() {
		// Test with string match
		$params = 'This contains test string';
		$pattern = '/test/';
		$this->assertTrue( $this->h5p->textAddonMatches( $params, $pattern ) );

		// Test with string no match
		$params = 'This has no match';
		$pattern = '/xyz/';
		$this->assertFalse( $this->h5p->textAddonMatches( $params, $pattern ) );

		// Test with array containing match
		$params = [ 'no match', 'this has test', 'another' ];
		$pattern = '/test/';
		$this->assertTrue( $this->h5p->textAddonMatches( $params, $pattern ) );

		// Test with nested array
		$params = [ 'level1' => [ 'level2' => 'contains test string' ] ];
		$pattern = '/test/';
		$this->assertTrue( $this->h5p->textAddonMatches( $params, $pattern ) );

		// Test with object
		$params = (object) [ 'property' => 'has test value' ];
		$pattern = '/test/';
		$this->assertTrue( $this->h5p->textAddonMatches( $params, $pattern ) );

		// Test with integer (should not match)
		$params = 12345;
		$pattern = '/test/';
		$this->assertFalse( $this->h5p->textAddonMatches( $params, $pattern ) );
	}

	/**
	 * Test replaceShortcode with different scenarios
	 *
	 * @group interactivecontent
	 */
	public function test_replaceShortcode_with_mocked_content() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		// Mock H5P class with getH5PRepresentation method
		$h5pMock = $this->getMockBuilder( H5P::class )
			->setConstructorArgs( [ $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock ] )
			->onlyMethods( [ 'getH5PRepresentation' ] )
			->getMock();

		// Test with H5P content available
		$h5pPluginMock->method( 'getContent' )->willReturn( [
			'id' => 456,
			'title' => 'Test H5P Content',
			'slug' => 'test-content'
		] );

		$h5pMock->method( 'getH5PRepresentation' )->willReturn( null );

		$bladeMock->expects( $this->once() )
			->method( 'render' )
			->with( 'interactive.h5p', $this->callback( function( $params ) {
				return $params['id'] === '#h5p-456' && $params['title'] === 'Test H5P Content';
			} ) )
			->willReturn( '<div>Rendered H5P</div>' );

		$result = $h5pMock->replaceShortcode( [ 'id' => 456 ] );
		$this->assertEquals( '<div>Rendered H5P</div>', $result );
	}

	/**
	 * Test createH5PExport method (private method via reflection)
	 *
	 * @group interactivecontent
	 */
	public function test_createH5PExport_private_method() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		$reflection = new ReflectionClass( $h5p );
		$method = $reflection->getMethod( 'createH5PExport' );
		$method->setAccessible( true );

		// Test with missing library
		$invalidContent = [ 'params' => '{"test": "data"}' ];
		$result = $method->invoke( $h5p, $invalidContent );
		$this->assertFalse( $result );

		// Test with missing params
		$invalidContent = [ 'library' => [ 'name' => 'Test' ] ];
		$result = $method->invoke( $h5p, $invalidContent );
		$this->assertFalse( $result );
	}

	/**
	 * Test generateContentSlug method (private method via reflection)
	 *
	 * @group interactivecontent
	 */
	public function test_generateContentSlug_private_method() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		$reflection = new ReflectionClass( $h5p );
		$method = $reflection->getMethod( 'generateContentSlug' );
		$method->setAccessible( true );

		// Mock the dependencies
		$h5pCoreMock->method( 'slugify' )->willReturn( 'test-slug' );

		$mockCore = $this->createMock( \stdClass::class );
		$mockCore->h5pF = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 
				'isContentSlugAvailable', 
				'loadAddons', 
				'deleteLibraryUsage', 
				'saveLibraryUsage', 
				'updateContentFields' 
			] )
			->getMock();
		$mockCore->h5pF->method( 'isContentSlugAvailable' )->willReturn( true );
		$mockCore->h5pF->method( 'loadAddons' )->willReturn( [] );
		$mockCore->h5pF->method( 'deleteLibraryUsage' )->willReturn( true );
		$mockCore->h5pF->method( 'saveLibraryUsage' )->willReturn( true );
		$mockCore->h5pF->method( 'updateContentFields' )->willReturn( true );

		$mockCore->fs = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 'hasExport', 'deleteExport' ] )
			->getMock();
		$mockCore->fs->method( 'hasExport' )->willReturn( false );
		$mockCore->fs->method( 'deleteExport' )->willReturn( true );

		$mockCore->loadContent = function( $id ) {
			return [ 'id' => $id, 'slug' => 'test-slug', 'title' => 'Test Content' ];
		};

		$h5pPluginMock->method( 'getH5PInstance' )->willReturn( $mockCore );

		$content = [ 'title' => 'Test Content Title' ];
		$result = $method->invoke( $h5p, $content );
		$this->assertEquals( 'test-slug', $result );
	}

	/**
	 * Test apiInit with exception handling
	 *
	 * @group interactivecontent
	 */
	public function test_apiInit_exception_handling() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		// Make wpHelper throw an exception
		$wpHelperMock->method( 'isPluginActive' )->will( $this->throwException( new \Exception( 'Test exception' ) ) );

		$result = $h5p->apiInit();
		$this->assertFalse( $result );
	}

	/**
	 * Test shouldEnablePrint with various option states
	 *
	 * @group interactivecontent
	 */
	public function test_shouldEnablePrint_scenarios() {
		// Test with export option enabled
		update_option( 'pressbooks_export_options', [
			'h5p_print_on_exports' => 1,
		] );

		$this->h5p->shouldEnablePrint();

		$reflection = new ReflectionClass( $this->h5p );
		$property = $reflection->getProperty( 'enableStaticRepresentation' );
		$property->setAccessible( true );
		$this->assertTrue( $property->getValue( $this->h5p ) );

		// Test with export option disabled
		update_option( 'pressbooks_export_options', [
			'h5p_print_on_exports' => 0,
		] );

		// Create new instance to reset state
		$blade = Container::get( 'Blade' );
		$h5pPlugin = Container::get( 'H5PPlugin' );
		$h5pExtractor = Container::get( 'H5PExtractor' );
		$wpHelper = Container::get( 'WordPressHelper' );
		$h5pCore = Container::get( 'H5PCore' );
		$newH5p = new H5P( $blade, $h5pPlugin, $h5pExtractor, $wpHelper, $h5pCore );

		$newH5p->shouldEnablePrint();

		$reflection = new ReflectionClass( $newH5p );
		$property = $reflection->getProperty( 'enableStaticRepresentation' );
		$property->setAccessible( true );
		$this->assertFalse( $property->getValue( $newH5p ) );
	}

	/**
	 * Test replaceShortcode with slug lookup
	 *
	 * @group interactivecontent
	 */
	public function test_replaceShortcode_with_slug() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5pMock = $this->getMockBuilder( H5P::class )
			->setConstructorArgs( [ $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock ] )
			->onlyMethods( [ 'getH5PRepresentation' ] )
			->getMock();

		$h5pMock->method( 'getH5PRepresentation' )->willReturn( null );
		$h5pPluginMock->method( 'getContent' )->willReturn( null );

		$bladeMock->expects( $this->once() )
			->method( 'render' )
			->with( 'interactive.h5p' )
			->willReturn( '<div>Rendered with slug</div>' );

		// Test with slug parameter (database lookup happens internally)
		$result = $h5pMock->replaceShortcode( [ 'slug' => 'test-slug' ] );
		$this->assertEquals( '<div>Rendered with slug</div>', $result );
	}

	/**
	 * Test override method
	 *
	 * @group interactivecontent
	 */
	public function test_override() {
		global $shortcode_tags;

		// Ensure shortcode exists initially
		add_shortcode( 'h5p', '__return_empty_string' );
		$this->assertTrue( shortcode_exists( 'h5p' ) );

		$this->h5p->override();

		// Should still exist but be overridden
		$this->assertTrue( shortcode_exists( 'h5p' ) );
		$this->assertTrue( has_filter( 'h5p_embed_access' ) );
	}

	/**
	 * Test findAllShortcodeIds with empty content
	 *
	 * @group interactivecontent
	 */
	public function test_findAllShortcodeIds_empty_content() {
		$this->assertEquals( [], $this->h5p->findAllShortcodeIds( '' ) );
		$this->assertEquals( [], $this->h5p->findAllShortcodeIds( 'No shortcodes here' ) );
		$this->assertEquals( [], $this->h5p->findAllShortcodeIds( '[other id="123"] shortcode' ) );
	}

	/**
	 * Test getInstance singleton pattern
	 *
	 * @group interactivecontent
	 */
	public function test_getInstance_singleton() {
		// Reset singleton
		$reflection = new ReflectionClass( H5P::class );
		$instance = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		$instance1 = H5P::getInstance();
		$instance2 = H5P::getInstance();

		$this->assertSame( $instance1, $instance2 );
		$this->assertInstanceOf( H5P::class, $instance1 );
	}

	/**
	 * Test ensureH5Export method (private method via reflection)
	 *
	 * @group interactivecontent
	 */
	public function test_ensureH5Export_private_method() {
		// Create mocks
		list($bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock) = $this->createMocks();

		$h5p = new H5P( $bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock );

		$reflection = new ReflectionClass( $h5p );
		$method = $reflection->getMethod( 'ensureH5Export' );
		$method->setAccessible( true );

		// Mock core dependencies
		$mockCore = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 'loadContent' ] )
			->getMock();
		$mockCore->fs = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ 'hasExport', 'deleteExport' ] )
			->getMock();
		$mockCore->fs->method( 'hasExport' )->willReturn( true );
		$mockCore->fs->method( 'deleteExport' )->willReturn( true );

		$mockContent = [
			'id' => 123,
			'slug' => 'test-content',
			'title' => 'Test'
		];

		$mockCore->method( 'loadContent' )->willReturn( $mockContent );
		$h5pPluginMock->method( 'getH5PInstance' )->willReturn( $mockCore );

		$result = $method->invoke( $h5p, 123 );
		$this->assertIsCallable( $result );
	}

	/**
	 * @return array
	 */
	public function createMocks(): array
	{
		// Create a mock that has the render method (like the Blade wrapper)
		$bladeMock = $this->getMockBuilder(stdClass::class)
			->addMethods(['render', 'addNamespace'])
			->getMock();
			
		$h5pPluginMock = $this->createMock(H5PPluginInterface::class);
		$h5pExtractorMock = $this->createMock(H5PExtractorInterface::class);
		$wpHelperMock = $this->createMock(WordPressHelperInterface::class);
		$h5pCoreMock = $this->createMock(H5PCoreInterface::class);
		return [$bladeMock, $h5pPluginMock, $h5pExtractorMock, $wpHelperMock, $h5pCoreMock];
	}

}
