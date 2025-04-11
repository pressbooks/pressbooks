<?php

use Pressbooks\Modules\Export\ExportHelpers;

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
		$metadata = \Pressbooks\Book::getBookInformation( null, false, false );
		$book_contents = \Pressbooks\Book::getBookContents();
		$this->taxonomy = \Pressbooks\Taxonomy::init();
		$this->contributors = new \Pressbooks\Contributors();

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
		$metadata = \Pressbooks\Book::getBookInformation( null, false, false );
		$book_contents = \Pressbooks\Book::getBookContents();
		$this->taxonomy = \Pressbooks\Taxonomy::init();
		$this->contributors = new \Pressbooks\Contributors();

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
}
