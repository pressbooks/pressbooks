<?php

use Pressbooks\Container;

class ExportMock extends \Pressbooks\Modules\Export\Export {

	function convert() {
		$this->outputPath = \Pressbooks\Utility\create_tmp_file();
		return true;
	}

	function validate() {
		return file_exists( $this->outputPath );
	}
}


class Modules_ExportTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \ExportMock
	 */
	protected $export;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->export = new \ExportMock();
	}

	public function test_getExportStylePath() {

		$this->_book( 'pressbooks-donham' );

		$path = $this->export->getExportStylePath( 'epub' );
		$this->assertStringEndsWith( '/export/epub/style.scss', $path );

		$path = $this->export->getExportStylePath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/style.scss', $path );

		$path = $this->export->getExportStylePath( 'web' );
		$this->assertStringEndsWith( '/pressbooks-donham/style.scss', $path );

		$path = $this->export->getExportStylePath( 'foobar' );
		$this->assertFalse( $path );
	}

	//  public function test_getGlobalTypographyMixinPath() {
	//      // TODO: Testing this as-is triggers updateGlobalTypographyMixin, generates _mixins.css, generates _global-font-stack.scss... Code needs to be decoupled?
	//      $this->markTestIncomplete();
	//  }

	public function test_getExportScriptPath() {

		$this->_book( 'pressbooks-donham' );

		$path = $this->export->getExportScriptPath( 'epub' );
		$this->assertFalse( $path );

		$path = $this->export->getExportScriptPath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/script.js', $path );

		$path = $this->export->getExportScriptPath( 'foobar' );
		$this->assertFalse( $path );
	}

	public function test_isParsingSubsections() {

		$val = $this->export->isParsingSubsections();
		$this->assertInternalType( 'bool', $val );
	}

	//  public function test_logError() {
	//      // TODO: Testing this as-is would send emails, writes to error log... Need to be refactored.
	//      $this->markTestIncomplete();
	//  }

	public function test_createTmpFile() {

		$file = $this->export->createTmpFile();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );
	}

	public function test_timestampedFileName() {

		$this->_book();

		$file = $this->export->timestampedFileName( 'epub', true );
		$this->assertStringEndsWith( '.epub', $file );
		$this->assertStringStartsWith( '/', $file );

		update_option( 'blogname', '!My+Book+Name!' );
		$file = $this->export->timestampedFileName( 'foo', false );
		$this->assertStringEndsWith( '.foo', $file );
		$this->assertStringStartsNotWith( '/', $file );
		$this->assertNotContains( '!', $file );
		$this->assertNotContains( '+', $file );
	}

	public function test_nonce_AND_verifyNonce() {

		$time1 = time();
		$nonce1 = $this->export->nonce( $time1 );
		$this->assertInternalType( 'string', $nonce1 );

		$time2 = $time1 + 1;
		$nonce2 = $this->export->nonce( $time2 );
		$this->assertNotEquals( $nonce1, $nonce2 );

		$this->assertTrue( $this->export->verifyNonce( $time1, $nonce1 ) );
		$this->assertFalse( $this->export->verifyNonce( $time1, $nonce2 ) );

		$time3 = $time1 - ( 60 * 5 + 1 );
		$nonce3 = $this->export->nonce( $time3 );
		$this->assertFalse( $this->export->verifyNonce( $time3, $nonce3 ) );
	}

	public function test_mimeType() {

		$i = $this->export;
		$mime = $i::mimeType( __DIR__ . '/data/pb.png' );
		$this->assertStringStartsWith( 'image/png', $mime );
	}

	public function test_getExportFolder() {

		$this->_book();

		$i = $this->export;
		$path = $i::getExportFolder();

		$this->assertTrue( is_dir( $path ) );
		$this->assertStringStartsWith( 'deny from all', file_get_contents( $path . '.htaccess' ) );
	}

	public function test_getLatestExportStylePath() {

		$this->_book();

		$i = $this->export;

		$css = '/* Silence is golden. */';

		$css_files = [];

		$timestamp1 = time();
		$css_file1 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp1.css";
		\Pressbooks\Utility\put_contents( $css_file1, $css );
		$css_files[] = $css_file1;

		$timestamp2 = time();
		$css_file2 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp2.css";
		\Pressbooks\Utility\put_contents( $css_file1, $css );
		$css_files[] = $css_file2;

		$latest = $i->getLatestExportStylePath( 'prince' );
		$this->assertEquals( Container::get( 'Sass' )->pathToUserGeneratedCss() . '/prince-' . $timestamp2 . '.css', $latest );

		$latest = $i->getLatestExportStylePath( 'garbage' );
		$this->assertFalse( $latest );

		$latest = $i->getLatestExportStylePath( '*.*' );
		$this->assertFalse( $latest );

		foreach ( $css_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
	}

	public function test_getLatestExportStyleUrl() {

		$this->_book();

		$i = $this->export;

		$css = '/* Silence is golden. */';

		$css_files = [];

		$timestamp1 = time();
		$css_file1 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp1.css";
		\Pressbooks\Utility\put_contents( $css_file1, $css );
		$css_files[] = $css_file1;

		$timestamp2 = time();
		$css_file2 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp2.css";
		\Pressbooks\Utility\put_contents( $css_file2, $css );
		$css_files[] = $css_file2;

		$latest = $i->getLatestExportStyleUrl( 'prince' );
		$this->assertEquals( network_home_url( sprintf( '/wp-content/uploads/sites/%d/pressbooks/css/prince-%d.css', get_current_blog_id(), $timestamp2 ) ), $latest );

		$latest = $i->getLatestExportStyleUrl( 'garbage' );
		$this->assertFalse( $latest );

		$latest = $i->getLatestExportStyleUrl( '*.*' );
		$this->assertFalse( $latest );

		foreach ( $css_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
	}

	public function test_truncateExportStylesheets() {

		$this->_book();

		$i = $this->export;

		$css = '/* Silence is golden. */';

		$css_files = [];

		$webbook_css = Container::get( 'Sass' )->pathToUserGeneratedCss() . '/style.css';
		\Pressbooks\Utility\put_contents( $webbook_css, $css );

		$timestamp1 = time();
		$css_file1 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp1.css";
		\Pressbooks\Utility\put_contents( $css_file1, $css );
		$css_files[] = $css_file1;

		$timestamp2 = time();
		$css_file2 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp2.css";
		\Pressbooks\Utility\put_contents( $css_file2, $css );
		$css_files[] = $css_file2;

		$timestamp3 = time();
		$css_file3 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp3.css";
		\Pressbooks\Utility\put_contents( $css_file3, $css );
		$css_files[] = $css_file3;

		$timestamps = [ $timestamp1, $timestamp2, $timestamp3 ];
		rsort( $timestamps );

		$files = scandir( Container::get( 'Sass' )->pathToUserGeneratedCss() );

		$i->truncateExportStylesheets( 'prince' );

		$i->truncateExportStylesheets( 'style' );

		$files = scandir( Container::get( 'Sass' )->pathToUserGeneratedCss() );

		$this->assertTrue( in_array( 'style.css', $files, true ) );

		for ( $i = 0; $i < 3; $i++ ) {
			$t = $timestamps[ $i ];
			if ( $i == 0 ) {
				$this->assertTrue( in_array( "prince-$t.css", $files, true ) );
			} else {
				$this->assertTrue( in_array( "prince-$t.css", $files, true ) );
			}
		}

		foreach ( $css_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
	}


	public function test_filters_useDocraptorInsteadOfPrince() {
		$filters = new \Pressbooks\Modules\Export\Prince\Filters();
		$this->assertTrue( is_bool( $filters->overridePrince() ) );
		$this->assertTrue( is_array( $filters->addToModules( [] ) ) ); // TODO: This test sucks
	}

	/**
	 * Sanity check that exports run without obvious errors
	 * Verify XHTML content for good measure
	 */
	public function test_sanityChecks() {

		$runtime = new \SebastianBergmann\Environment\Runtime();

		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );

		$modules[] = '\Pressbooks\Modules\Export\Xhtml\Xhtml11'; // Must be first! Other tests depend on this. Never comment out, never change position.
		$modules[] = '\Pressbooks\Modules\Export\Prince\Pdf';
		$modules[] = '\Pressbooks\Modules\Export\Prince\PrintPdf';
		$modules[] = '\Pressbooks\Modules\Export\Prince\Docraptor';
		$modules[] = '\Pressbooks\Modules\Export\Prince\DocraptorPrint';
		$modules[] = '\Pressbooks\Modules\Export\Epub\Epub201'; // Must be set before MOBI
		$modules[] = '\Pressbooks\Modules\Export\Epub\Epub3';
		// $modules[] = '\Pressbooks\Modules\Export\Mobi\Kindlegen'; // Must be set after EPUB // TODO: Download/install Kindlegen in Travis build script
		$modules[] = '\Pressbooks\Modules\Export\InDesign\Icml';
		$modules[] = '\Pressbooks\Modules\Export\WordPress\Wxr';
		$modules[] = '\Pressbooks\Modules\Export\WordPress\VanillaWxr';
		// $modules[] = '\Pressbooks\Modules\Export\Odt\Odt'; // TODO: Download/install Saxon-HE in Travis build script
		$modules[] = '\Pressbooks\Modules\Export\HTMLBook\HTMLBook';

		$paths = [];
		$xhtml_path = null;
		foreach ( $modules as $module ) {
			/** @var \Pressbooks\Modules\Export\Export $exporter */
			$exporter = new $module( [] );

			if (
				strpos( $module, '\Prince\\' ) !== false ||
				strpos( $module, '\Odt\\' ) !== false
			) {
				$exporter->url = $xhtml_path;
			}

			$this->assertTrue( $exporter->convert(), "Could not convert with {$module}" );
			if ( strpos( $module, '\HTMLBook\HTMLBook' ) !== false ) {
				// TODO: HTMLBook is too strict we don't pass the validation
			} elseif ( $runtime->isPHPDBG() && strpos( $module, '\Epub\Epub' ) !== false ) {
				// TODO: exec(): Unable to fork [/usr/bin/epubcheck -q /path/to.epub 2>&1]
			} else {
				$this->assertTrue( $exporter->validate(), "Could not validate with {$module}" );
			}
			$paths[] = $exporter->getOutputPath();

			if ( strpos( $module, '\Xhtml\Xhtml11' ) !== false ) {
				$xhtml_path = $exporter->getOutputPath();
			}

			unset( $exporter );
		}

		// Verify XHTML content for good measure
		$xhtml_content = file_get_contents( ( $xhtml_path ) );
		$this->assertContains( '<span class="footnote">', $xhtml_content );
		$this->assertContains( 'wp.com/latex.php', $xhtml_content );
		$this->assertContains( ' <div id="attachment_1" ', $xhtml_content );
		$this->assertContains( '<p><em>Ka kite ano!</em></p>', $xhtml_content );
		$this->assertContains( 'https://github.com/pressbooks/pressbooks', $xhtml_content );
		$this->assertContains( '</h2><h2 class="chapter-subtitle">Or, A Chapter to Test</h2></div>', $xhtml_content );

		foreach ( $paths as $path ) {
			unlink( $path );
		}
	}

	public function test_sanityCheckXhtmlWithoutBuckram() {

		$this->_book( 'pressbooks-donham' ); // Use an old book.
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );

		$exporter = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( [] );
		$this->assertTrue( $exporter->convert() );
		$this->assertTrue( $exporter->validate() );
		$xhtml_content = file_get_contents( $exporter->getOutputPath() );

		$this->assertContains( '<span class="footnote">', $xhtml_content );
		$this->assertContains( 'wp.com/latex.php', $xhtml_content );
		$this->assertContains( ' <div id="attachment_1" ', $xhtml_content );
		$this->assertContains( '<p><em>Ka kite ano!</em></p>', $xhtml_content );
		$this->assertContains( 'https://github.com/pressbooks/pressbooks', $xhtml_content );
		// Heading elements should be in a "bad" place.
		$this->assertContains( '</h2></div><div class="ugc chapter-ugc"><h2 class="chapter-subtitle">Or, A Chapter to Test</h2>', $xhtml_content );

		unlink( $exporter->getOutputPath() );
	}

	public function test_sanityCheckXhtmlDebug() {
		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		$_GET['debug'] = 'prince';
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}
		$timestamp = time();
		$css = '/* Silence is golden. */';
		$css_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp.css";
		\Pressbooks\Utility\put_contents( $css_file, $css );

		$exporter = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( [] );
		$this->assertTrue( $exporter->convert() );
		$this->assertTrue( $exporter->validate() );
		$xhtml_content = file_get_contents( $exporter->getOutputPath() );
		$url = network_home_url( sprintf( '/wp-content/uploads/sites/%d/pressbooks/css/prince-', get_current_blog_id() ) );
		$this->assertContains( "<link rel='stylesheet' href='$url", $xhtml_content );
		unlink( $exporter->getOutputPath() );
	}
}
