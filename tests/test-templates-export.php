<?php

use Pressbooks\Container;

class TemplateExportTest extends \WP_UnitTestCase {
	use utilsTrait;

	public function set_up() {
		parent::set_up();
		$this->blade = Container::get( 'Blade' );
	}

	/**
	 * @group export_templates
	 */
	public function test_genericPostType() {
		$content = '<p>I am a HTML content <span>inside</span> <strong>a nice template</strong>.';
		$endnote = 'I am an <span>endnote</span>';
		$footnote = 'I am a footnote without <i>reference.</i>';
		$short_title = 'Short title!';
		$generic_post_rendered = $this->blade->render(
			'export/generic-post-type',
			[
				'post_type_class' => 'front-matter',
				'subclass' => 'front-matter-subclass',
				'slug' => 'front-intro-01',
				'post_number' => 2,
				'short_title' => $short_title,
				'title' => 'I am a nice Front Matter title',
				'content' => $content,
				'endnotes' => $endnote,
				'footnotes' => $footnote,
			]
		);

		$this->assertStringContainsString(
			"<div class=\"front-matter front-matter-subclass \" id=\"front-intro-01\" title=\"$short_title\">",
			$generic_post_rendered
		);
		$this->assertStringContainsString( '<p class="front-matter-number">2</p>', $generic_post_rendered );
		$this->assertStringContainsString( '<h1 class="front-matter-title">I am a nice Front Matter title</h1>', $generic_post_rendered );
		$this->assertStringContainsString( '<div class="ugc front-matter-ugc">', $generic_post_rendered );
		$this->assertStringContainsString( $content, $generic_post_rendered );
		$this->assertStringContainsString( $endnote, $generic_post_rendered );
		$this->assertStringContainsString( $footnote, $generic_post_rendered );
	}

	/**
	 * @group export_templates
	 */
	public function test_chapter() {
		$endnote = 'It will <span>be</span> in Montreal';
		$footnote = '<strong>Soon!</strong>';
		$append = "Metheny’s body of <a href='#nothing'>work</a> includes compositions for solo guitar";
		$content = 'Over the years, he has <i>performed</i> with artists as diverse as Steve Reich to Ornette Coleman to Herbie Hancock';
		$title = "Pat Metheny's presentation";
		$sanitized_title = 'Pat Metheny presentation';
		$short_title = 'Music in Montreal';
		$subtitle = 'Pat Metheny - Montreal';
		$author = 'John Doe';
		$chapter_rendered = $this->blade->render(
			'export/chapter',
			[
				'subclass' => 'chapter-subclass',
				'slug' => 'chapter-001',
				'sanitized_title' => $sanitized_title,
				'number' => 1,
				'title' => $title,
				'is_new_buckram' => true,
				'output_short_title' => true,
				'author' => $author,
				'subtitle' => $subtitle,
				'short_title' => $short_title,
				'content' => $content,
				'append_content' => $append,
				'endnotes' => $endnote,
				'footnotes' => $footnote,
			]
		);

		$this->assertStringContainsString(
			"<div class=\"chapter chapter-subclass \" id=\"chapter-001\" title=\"$sanitized_title\">",
			$chapter_rendered
		);
		$this->assertStringContainsString( '<p class="chapter-number">1</p>', $chapter_rendered );
		$this->assertStringContainsString( "<h1 class=\"chapter-title\">$title</h1>", $chapter_rendered );
		$this->assertStringContainsString( "<p class=\"short-title\">$short_title</p>", $chapter_rendered );
		$this->assertStringContainsString( "<p class=\"chapter-subtitle\">$subtitle</p>", $chapter_rendered );
		$this->assertStringContainsString( "<p class=\"chapter-author\">$author</p>", $chapter_rendered );
		$this->assertStringContainsString( '<div class="ugc chapter-ugc">', $chapter_rendered );
		$this->assertStringContainsString( $content, $chapter_rendered );
		$this->assertStringContainsString( $append, $chapter_rendered );
		$this->assertStringContainsString( $footnote, $chapter_rendered );
		$this->assertStringContainsString( $endnote, $chapter_rendered );
	}

	/**
	 * @group export_templates
	 */
	public function test_halfTitle() {
		$title = 'I am an amazing book!';
		$half_title_rendered = $this->blade->render(
			'export/half-title', [ 'title' => $title ]
		);

		$this->assertStringContainsString( '<div id="half-title-page">', $half_title_rendered );
		$this->assertStringContainsString( "<h1 class=\"title\">$title</h1>", $half_title_rendered );
	}

	/**
	 * @group export_templates
	 */
	public function test_part() {
		$rendered_part = $this->blade->render(
			'export/part',
			[
				'invisibility' => '',
				'introduction' => '',
				'slug' => 'part-1',
				'number' => 1,
				'title' => 'This is an amazing part of the book!',
				'content' => 'Are you ready?',
				'endnotes' => 'I am an <span>endnote</span>',
				'footnotes' => '<strong>I am a footnote</strong>',
			]
		);

		$this->assertStringContainsString( '<div class="part  " id="part-1">', $rendered_part );
		$this->assertStringContainsString( '<p class="part-number">1</p>', $rendered_part );
		$this->assertStringContainsString( '<h1 class="part-title">This is an amazing part of the book!</h1>', $rendered_part );
		$this->assertStringContainsString( '<div class="ugc part-ugc">', $rendered_part );
		$this->assertStringContainsString( 'Are you ready?', $rendered_part );
		$this->assertStringContainsString( 'I am an <span>endnote</span>', $rendered_part );
		$this->assertStringContainsString( '<strong>I am a footnote</strong>', $rendered_part );
	}

	/**
	 * @group export_templates
	 */
	public function test_cover() {
		$cover_rendered = $this->blade->render(
			'export/cover', [
				'src' => 'https://pressbooks.org/an-image.jpg',
				'alt' => 'Pressbooks',
			]
		);

		$this->assertStringContainsString( '<div id="cover-image">', $cover_rendered );
		$this->assertStringContainsString( '<img src="assets/https://pressbooks.org/an-image.jpg" alt="Pressbooks" />', $cover_rendered );
	}

	/**
	 * @group export_templates
	 */
	public function test_dedicationAndEpigraph() {
		$epigraph_rendered = $this->blade->render(
			'export/dedication-epigraph', //TODO: Review if it could be consolidated in a single file
			[
				'subclass' => 'epigraph-subclass',
				'slug' => 'epigraph-1',
				'front_matter_number' => 2,
				'title' => 'Thank you',
				'content' => 'Dedicated to Carl J.',
				'endnotes' => 'Thanks Carl J.',
				'footnotes' => 'Carl J. is a friend.',
			]
		);

		$this->assertStringContainsString( '<div class="front-matter epigraph-subclass " id="epigraph-1">', $epigraph_rendered );
		$this->assertStringContainsString( '<p class="front-matter-number">2</p>', $epigraph_rendered );
		$this->assertStringContainsString( '<h1 class="front-matter-title">Thank you</h1>', $epigraph_rendered );
		$this->assertStringContainsString( 'Dedicated to Carl J', $epigraph_rendered );
		$this->assertStringContainsString( 'Thanks Carl J.', $epigraph_rendered );
		$this->assertStringContainsString( 'Carl J. is a friend.', $epigraph_rendered );
	}

	/**
	 * @group export_templates
	 */
	public function test_copyright() {
		$copyright_rendered = $this->blade->render( 'export/copyright', [
			'license_copyright' => 'Public Domain',
			'has_default' => true,
			'default_copyright_name' => 'Public Domain Test',
			'default_copyright_date' => 2022,
			'default_copyright_holder' => 'Default holder text',
		] );

		$this->assertStringContainsString( '<div id="copyright-page">', $copyright_rendered );
		$this->assertStringContainsString( '<div class="ugc">', $copyright_rendered );
		$this->assertStringContainsString( 'Public Domain', $copyright_rendered );
		$this->assertStringContainsString( 'Public Domain Test', $copyright_rendered );
		$this->assertStringContainsString( '2022', $copyright_rendered );
		$this->assertStringContainsString( 'Default holder text', $copyright_rendered );
	}

	/**
	 * @group export_templates
	 */
	public function test_title() {
		$title_rendered = $this->blade->render(
			'export/title', [
				'content' => 'I am a content in the title page!',
			]
		);

		$this->assertStringContainsString( '<div id="title-page">', $title_rendered );
		$this->assertStringContainsString( 'I am a content in the title page!', $title_rendered );
	}
}
