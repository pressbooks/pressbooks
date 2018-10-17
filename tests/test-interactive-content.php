<?php

class Interactive_ContentTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Interactive\Content
	 */
	protected $content;

	public function setUp() {
		parent::setUp();
		$this->content = new \Pressbooks\Interactive\Content();
	}


	public function test_deleteIframesNotOnWhitelist() {
		$raw = '
		Test One
		<iframe src="https://phet.colorado.edu/sims/html/balancing-act/latest/balancing-act_en.html" width="800" height="600" scrolling="no" allowfullscreen></iframe>
		Test Two
		 <iframe src="https://garbage.com/bad.html" width="800" height="600" scrolling="no" allowfullscreen></iframe>
		';

		$result = $this->content->deleteIframesNotOnWhitelist( $raw, [ 'post' ] );

		$this->assertEquals( 1, substr_count( $result, '<iframe' ) );
		$this->assertContains( 'Test One', $result );
		$this->assertContains( 'Test Two', $result );
		$this->assertContains( '<iframe src="https://phet.colorado.edu/', $result );
		$this->assertContains( '[embed]https://garbage.com/bad.html[/embed]', $result );
		$this->assertNotContains( '<p>', $result );
	}

	public function test_replaceIframes() {
		$html = '
		<p>Test</p>
		<iframe src="https://this-is-fine.com/meh.html" width="800" height="600" scrolling="no" allowfullscreen></iframe>
		';

		$result = $this->content->replaceIframes( $html );

		$this->assertNotContains( '<iframe', $result );
		$this->assertContains( '<div ', $result );
		$this->assertContains( '<p>Test</p>', $result );
		$this->assertContains( 'excluded from this version of the text', $result );
	}

	public function test_allowIframesInHtml() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$allowed = $this->content->allowIframesInHtml( [], 'post' );
		$this->assertTrue( ! empty( $allowed['iframe'] ) );
	}

	public function test_replaceOembed() {

		$data = new \StdClass();
		$data->provider_name = 'Localhost';
		$data->thumbnail_url = 'http://localhost/image.png';

		$html = '
		<iframe src="https://this-is-fine.com/meh.html" width="800" height="600" scrolling="no" allowfullscreen></iframe>
		';

		$result = $this->content->replaceOembed( $html, $data, null );

		$this->assertNotContains( '<iframe', $result );
		$this->assertContains( '<div ', $result );
		$this->assertContains( $data->provider_name, $result );
		$this->assertContains( $data->thumbnail_url, $result );
		$this->assertContains( 'excluded from this version of the text', $result );
	}

	public function test_replaceInteractiveTags() {

		$html = '
			<audio controls="controls">
			<source type="audio/mpeg" src="http://localhost/test.mp3" />
			<a href="http://localhost/test.mp3">http://localhost/test.mp3</a>
			</audio>
		';

		$result = $this->content->replaceInteractiveTags( $html );
		$this->assertNotContains( '<audio', $result );
		$this->assertContains( '<div ', $result );
		$this->assertContains( 'excluded from this version of the text', $result );
	}

	public function test_addExtraOembedProviders() {
		$providers = $this->content->addExtraOembedProviders( [] );
		$this->assertNotEmpty( $providers );
	}

	public function test_deleteOembedCaches() {
		$this->content->deleteOembedCaches( 1 );
		$this->content->deleteOembedCaches();

		global $wpdb;
		$id = $wpdb->get_var( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key LIKE '_oembed_%' " );
		$this->assertEmpty( $id );
	}

	public function test_isCloneable() {
		$content = '[h5p id="1"]';
		$this->assertFalse( $this->content->isCloneable( $content ) );

		$content = 'OK then!';
		$this->assertTrue( $this->content->isCloneable( $content ) );
	}

}
