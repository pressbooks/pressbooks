<?php

use Pressbooks\Book;
use Pressbooks\Contributors;
use Pressbooks\Modules\Export\ExportHelpers;
use Pressbooks\Taxonomy;

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
		return $xhtml_method->invokeArgs( new \Pressbooks\Modules\Export\Xhtml\Xhtml11( [ ] ), [ $content ] );
	}

	public function doEndnotes( $id ) {
		$xhtml = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( [ ] );
		return $xhtml->doEndnotes( $id );
	}

	public function doFootnotes( $id ) {
		$xhtml = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( [ ] );
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
        $metadata['pb_translators'] = 'Test Translator';
        $metadata['pb_illustrators'] = 'Test Illustrator';
        $metadata['pb_contributors'] = 'Test Contributor';

        $book_contents = Book::getBookContents();
        $this->taxonomy = Taxonomy::init();
        $this->contributors = new Contributors();

        // Test XHTML11 renderTitle
        $xhtml = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( [] );
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
        $this->assertStringContainsString( __( 'Edited By ', 'pressbooks' ) . 'Test Editor', $xhtml_output );
        $this->assertStringContainsString( __( 'Translated By ', 'pressbooks' ) . 'Test Translator', $xhtml_output );
        $this->assertStringContainsString( __( 'Illustrated By ', 'pressbooks' ) . 'Test Illustrator', $xhtml_output );
        $this->assertStringContainsString( __( 'Contributors: ', 'pressbooks' ) . 'Test Contributor', $xhtml_output );

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
                    $this->assertStringContainsString(__( 'Edited By ', 'pressbooks' ) . 'Test Editor', $data['post_content']);
                    $this->assertStringContainsString(__( 'Translated By ', 'pressbooks' ) . 'Test Translator', $data['post_content']);
                    $this->assertStringContainsString(__( 'Illustrated By ', 'pressbooks' ) . 'Test Illustrator', $data['post_content']);
                    $this->assertStringContainsString(__( 'Contributors: ', 'pressbooks' ) . 'Test Contributor', $data['post_content']);
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
}
