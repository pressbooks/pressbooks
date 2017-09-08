<?php


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

		$this->_book();

		$path = $this->export->getExportStylePath( 'epub' );
		$this->assertStringEndsWith( '/export/epub/style.scss', $path );

		$path = $this->export->getExportStylePath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/style.scss', $path );

		$path = $this->export->getExportStylePath( 'web' );
		$this->assertStringEndsWith( '/pressbooks-book/style.scss', $path );

		$path = $this->export->getExportStylePath( 'foobar' );
		$this->assertFalse( $path );
	}

	//  public function test_getGlobalTypographyMixinPath() {
	//      // TODO: Testing this as-is triggers updateGlobalTypographyMixin, generates _mixins.css, generates _global-font-stack.scss... Code needs to be decoupled?
	//      $this->markTestIncomplete();
	//  }

	public function test_getExportScriptPath() {

		$this->_book();

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

	public function test_sanityChecks() {

		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		update_post_meta( $meta_post->ID, 'pb_author', 'Zimmerman, Ned' );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );

		$modules[] = '\Pressbooks\Modules\Export\Xhtml\Xhtml11'; // Must be first
		$modules[] = '\Pressbooks\Modules\Export\Prince\Pdf';
		$modules[] = '\Pressbooks\Modules\Export\Prince\PrintPdf';
		$modules[] = '\Pressbooks\Modules\Export\Epub\Epub201'; // Must be set before MOBI
		$modules[] = '\Pressbooks\Modules\Export\Epub\Epub3';
		// $modules[] = '\Pressbooks\Modules\Export\Mobi\Kindlegen'; // Must be set after EPUB // TODO: Download/install Kindlegen in Travis build script
		$modules[] = '\Pressbooks\Modules\Export\InDesign\Icml';
		$modules[] = '\Pressbooks\Modules\Export\WordPress\Wxr';
		$modules[] = '\Pressbooks\Modules\Export\WordPress\VanillaWxr';
		// $modules[] = '\Pressbooks\Modules\Export\Odt\Odt'; // TODO: Download/install Saxon-HE in Travis build script

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
			$this->assertTrue( $exporter->validate(), "Could not validate with {$module}" );
			$paths[] = $exporter->getOutputPath();

			if ( strpos( $module, '\Xhtml\Xhtml1' ) !== false ) {
				$xhtml_path = $exporter->getOutputPath();
			}
		}

		foreach ( $paths as $path ) {
			unlink( $path );
		}
	}

}
