<?php

use Pressbooks\Book;
use Pressbooks\Contributors;
use Pressbooks\Modules\Export\ExportHelpers;
use Pressbooks\Modules\Export\Xhtml\Xhtml11;
use Pressbooks\Taxonomy;

/**
 * @group export_helpers
 */

class ExportHelpersTest extends \WP_UnitTestCase {
	use ExportHelpers;
	use utilsTrait;

	/**
	 * @var bool
	 */
	protected $displayAboutTheAuthors;

	protected $wrapHeaderElements = false;

	public $taxonomy;
	public $contributors;

	/**
	 * @group export_helpers
	 */
	public function test_countPartsAndChapters() {
		$book_contents = [
			'part' => [
				[
					'chapters' => [
						'chapter 1',
						'chapter 2',
						'chapter 3',
					],
				],
				[
					'chapters' => [
						'chapter 1',
						'chapter 2',
					],
				]
			],
		];
		$this->assertEquals( 7, $this->countPartsAndChapters( $book_contents ) );

		$book_contents = [ 'part' => [ [ 'chapters' => [] ] ] ];
		$this->assertEquals( 1, $this->countPartsAndChapters( $book_contents ) );
	}

	/**
	 * @group export_helpers
	 */
	public function test_mapBookDataAndContentFrontMatter() {
		$this->_book();
		$metadata = Book::getBookInformation( null, false, false );
		$book_contents = Book::getBookContents();
		$this->taxonomy = Taxonomy::init();
		$this->contributors = new Contributors();

		$front_matter_mapped = $this->mapBookDataAndContent(
			$book_contents['front-matter'][0],
			$metadata,
			1,
			[
				'type' => 'front_matter',
				'endnotes' => true,
				'footnotes' => true,
			]
		);
		$this->assertEquals( 'introduction', $front_matter_mapped['subclass'] );
		$this->assertStringContainsString( '<span class="display-none">Introduction</span>', $front_matter_mapped['title'] );
		$this->assertEquals( 'front-matter-introduction', $front_matter_mapped['slug'] );
		$this->assertEquals( 'This is where you can write your introduction.', $front_matter_mapped['content'] );
		$this->assertEquals( 'front-matter', $front_matter_mapped['post_type_class'] );
	}

	/**
	 * @group export_helpers
	 */
	public function test_mapBookDataAndContentBackMatter() {
		$this->_book();
		$metadata = Book::getBookInformation( null, false, false );
		$book_contents = Book::getBookContents();
		$this->taxonomy = Taxonomy::init();
		$this->contributors = new Contributors();

		$back_matter_mapped = $this->mapBookDataAndContent(
			$book_contents['back-matter'][0],
			$metadata,
			1,
			[
				'type' => 'back_matter',
				'endnotes' => true,
				'footnotes' => true,
			]
		);

		$this->assertEquals( 'appendix', $back_matter_mapped['subclass'] );
		$this->assertStringContainsString( '<span class="display-none">Appendix</span>', $back_matter_mapped['title'] );
		$this->assertEquals( 'back-matter-appendix', $back_matter_mapped['slug'] );
		$this->assertEquals( 'This is where you can add appendices or other back matter.', $back_matter_mapped['content'] );
		$this->assertEquals( 'back-matter', $back_matter_mapped['post_type_class'] );
	}

	public function doSectionLevelLicense( $metadata, $id ) {
		return '';
	}

	public function removeAttributionLink( $content ) {
		$xhtml_reflection = new \ReflectionClass( 'Pressbooks\Modules\Export\Xhtml\Xhtml11' );
		$xhtml_method = $xhtml_reflection->getMethod( 'removeAttributionLink' );
		$xhtml_method->setAccessible( true );
		return $xhtml_method->invokeArgs( new Xhtml11( [ ] ), [ $content ] );
	}

	public function doEndnotes( $id ) {
		$xhtml = new Xhtml11( [ ] );
		return $xhtml->doEndnotes( $id );
	}

	public function doFootnotes( $id ) {
		$xhtml = new Xhtml11( [ ] );
		return $xhtml->doFootnotes( $id );
	}

	/**
	 * @group export_helpers
	 */
    public function test_renderTitle() {
        $this->_book();
        $metadata = Book::getBookInformation( null, false, false );
        // Add contributor data to metadata
        $metadata['pb_authors'] = 'Test Author';
        $metadata['pb_editors'] = 'Test Editor';

        $book_contents = Book::getBookContents();
        $this->taxonomy = Taxonomy::init();
        $this->contributors = new Contributors();

        // Test XHTML11 renderTitle
        $xhtml = new Xhtml11( [] );
        // Set the taxonomy property for Xhtml class
        $xhtml_reflection_prop = new \ReflectionClass( $xhtml );
        $xhtml_taxonomy_prop = $xhtml_reflection_prop->getProperty( 'taxonomy' );
        $xhtml_taxonomy_prop->setAccessible( true );
        $xhtml_taxonomy_prop->setValue( $xhtml, $this->taxonomy );

        ob_start();
        $xhtml_reflection = new \ReflectionClass( $xhtml );
        $xhtml_method = $xhtml_reflection->getMethod( 'renderTitle' );
        $xhtml_method->setAccessible( true );
        $xhtml_method->invokeArgs( $xhtml, [ $book_contents, $metadata ] );
        $xhtml_output = ob_get_clean();

        // Basic assertions for XHTML output - adjust as needed
        $this->assertStringContainsString( '<div id="title-page"',
            $xhtml_output );
        $this->assertStringContainsString( get_bloginfo( 'name' ), $xhtml_output ); // Check if book title exists
        // Check for contributors
        $this->assertStringContainsString( 'Test Author', $xhtml_output );
        $this->assertStringNotContainsString( __( 'Edited by ', 'pressbooks' ) . 'Test Editor', $xhtml_output );

        // Test Epub renderTitle
        $epub = new \Pressbooks\Modules\Export\Epub\Epub( [] );
        $epub_reflection = new \ReflectionClass( $epub );
        $epub_method = $epub_reflection->getMethod( 'renderTitle' );
        $epub_method->setAccessible( true );

        // Epub renderTitle modifies the manifest and creates files, it doesn't echo directly.
        // We need to check the side effects, like file creation and manifest update.
        // Let's mock createEpubFile and updateManifest to verify they are called correctly.
        $mock_epub = $this->getMockBuilder( \Pressbooks\Modules\Export\Epub\Epub::class )
            ->setConstructorArgs( [[]] )
            ->onlyMethods( [ 'createEpubFile', 'updateManifest' ] )
            ->getMock();

        $mock_epub->expects( $this->once() )
            ->method( 'createEpubFile' )
            ->with(
                $this->equalTo( 'title-page.xhtml' ), // Assuming default extension
                // Check that the data array contains expected contributor info in its rendered content
                $this->callback(function($data) {
                    $this->assertArrayHasKey('post_content', $data);
                    $this->assertStringContainsString('Test Author', $data['post_content']);
                    $this->assertStringNotContainsString(__( 'Edited by ', 'pressbooks' ) . 'Test Editor', $data['post_content']);
                    return true; // Return true if all assertions pass
                })
            );

        $mock_epub->expects( $this->once() )
            ->method( 'updateManifest' )
            ->with(
                $this->equalTo( 'title-page' ),
                $this->arrayHasKey( 'filename' )
            );

        // Need to set the taxonomy property for Epub class as well
        $epub_taxonomy_prop = $epub_reflection->getProperty( 'taxonomy' );
        $epub_taxonomy_prop->setAccessible( true );
        $epub_taxonomy_prop->setValue( $mock_epub, $this->taxonomy );

        // Invoke the method on the mock object
        $epub_method->invokeArgs( $mock_epub, [ $book_contents, $metadata ] );

        // Since invokeArgs doesn't return a value for protected methods called on mocks,
        // the assertions are handled by the mock expectations above.
        // We can add a dummy assertion to prevent PHPUnit risky test warning
        $this->assertTrue( true );
    }

	/**
	 * @group export_helpers
	 */
    public function test_renderTitle_without_authors() {
        $this->_book();
        $metadata = Book::getBookInformation( null, false, false );
        // Add multiple contributor data to metadata as array of arrays with 'name' key
        $metadata['pb_authors'] = null;
        $metadata['pb_editors'] = [['name' => 'Test Editor 1'], ['name' => 'Test Editor 2']];

        $book_contents = Book::getBookContents();
        $this->taxonomy = Taxonomy::init();
        $this->contributors = new Contributors();

        // --- Test XHTML11 renderTitle (Multiple Contributors) ---
        $xhtml = new Xhtml11( [] );
        $xhtml_reflection_prop = new \ReflectionClass( $xhtml );
        $xhtml_taxonomy_prop = $xhtml_reflection_prop->getProperty( 'taxonomy' );
        $xhtml_taxonomy_prop->setAccessible( true );
        $xhtml_taxonomy_prop->setValue( $xhtml, $this->taxonomy );

        ob_start();
        $xhtml_reflection = new \ReflectionClass( $xhtml );
        $xhtml_method = $xhtml_reflection->getMethod( 'renderTitle' );
        $xhtml_method->setAccessible( true );
        $xhtml_method->invokeArgs( $xhtml, [ $book_contents, $metadata ] );
        $xhtml_output = ob_get_clean();

        // Assertions for XHTML output (Multiple Contributors) - Expecting ' and ' separation
        $this->assertStringContainsString( '<div id="title-page"', $xhtml_output );
        $this->assertStringContainsString( get_bloginfo( 'name' ), $xhtml_output );
        $this->assertStringContainsString( __( 'Edited by ', 'pressbooks' ) . 'Test Editor 1 and Test Editor 2', $xhtml_output );

        // --- Test Epub renderTitle (Multiple Contributors) ---
        $epub_reflection = new \ReflectionClass( '\Pressbooks\Modules\Export\Epub\Epub' );
        $epub_method = $epub_reflection->getMethod( 'renderTitle' );
        $epub_method->setAccessible( true );
        $epub_taxonomy_prop = $epub_reflection->getProperty( 'taxonomy' );
        $epub_taxonomy_prop->setAccessible( true );

        $mock_epub = $this->getMockBuilder( \Pressbooks\Modules\Export\Epub\Epub::class )
            ->setConstructorArgs( [[]] )
            ->onlyMethods( [ 'createEpubFile', 'updateManifest' ] )
            ->getMock();

        $mock_epub->expects( $this->once() )
            ->method( 'createEpubFile' )
            ->with(
                $this->equalTo( 'title-page.xhtml' ),
                // Check that the data array contains expected multiple contributor info (names separated by ' and ')
                $this->callback(function($data) {
                    $this->assertArrayHasKey('post_content', $data);
                    $this->assertStringContainsString(__( 'Edited by ', 'pressbooks' ) . 'Test Editor 1 and Test Editor 2', $data['post_content']);
                    return true; // Return true if all assertions pass
                })
            );

        $mock_epub->expects( $this->once() )
            ->method( 'updateManifest' )
            ->with(
                $this->equalTo( 'title-page' ),
                $this->arrayHasKey( 'filename' )
            );

        $epub_taxonomy_prop->setValue( $mock_epub, $this->taxonomy );
        $epub_method->invokeArgs( $mock_epub, [ $book_contents, $metadata ] );

        // Add a final dummy assertion if needed to prevent risky test warnings, though the mock expectations should cover it.
        $this->assertTrue( true );
    }

	/**
	 * @group export_helpers
	 */
	public function test_cleanH5PCss() {

		$css = <<<CSS
/* H5P CSS */
body > div { *width: 100px; }
.h5p-iframe-wrapper { font-size: unset; }
.h5p-text { src: url('') format('woff2'); src: url('') format('truetype'); }
.h5p-background { background: url('') 10px center no-repeat; }
.ui-datepicker-rtl { direction: rtl; }
.button &gt; span { display: inline-block; }
CSS;
		$result = $this->cleanH5PCss($css);
		$expected = <<<CSS
/* H5P CSS */
body > div { width: 100px; }
.h5p-iframe-wrapper {  }
.h5p-text {   }
.h5p-background {  }

.button > span { display: inline-block; }
CSS;
		$this->assertEquals($expected, $result);

	}

	/**
	 * @group export_helpers
	 */
	public function test_xhtml_title_element_escapes_special_characters() {
		$original_memory_limit = ini_get( 'memory_limit' );
		$this->_book();

		$unsafe_name = 'Arts & Sciences <Test>';
		$override_blogname = function () use ( $unsafe_name ) {
			return $unsafe_name;
		};
		add_filter( 'pre_option_blogname', $override_blogname );

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		add_filter( 'pb_mathjax_use', '__return_false' );

		$exporter = new Xhtml11( [] );
		$converter = $exporter->convert();
		$this->runGenerator( $converter );

		$this->assertNotEmpty( $converter->getReturn() );

		$xhtml_content = file_get_contents( $exporter->getOutputPath() );

		$this->assertStringContainsString(
			'<title>Arts &amp; Sciences &lt;Test&gt;</title>',
			$xhtml_content,
			'The XHTML <title> element must escape special HTML characters in the site name.'
		);
		$this->assertStringNotContainsString(
			'<title>Arts & Sciences <Test></title>',
			$xhtml_content,
			'The XHTML <title> element must not contain unescaped special characters.'
		);

		remove_filter( 'pre_option_blogname', $override_blogname );
		remove_filter( 'pb_mathjax_use', '__return_false' );
		wp_set_current_user( 0 );
		unlink( $exporter->getOutputPath() );
		delete_transient( Xhtml11::TRANSIENT );
		Book::deleteBookObjectCache();
		restore_current_blog();
		ini_set( 'memory_limit', $original_memory_limit );
	}
}
