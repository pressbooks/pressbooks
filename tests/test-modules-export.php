<?php


class ExportMock extends \PressBooks\Modules\Export\Export {

	function convert() {
		$this->outputPath = \PressBooks\Utility\create_tmp_file();
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


	/**
	 * @covers \PressBooks\Modules\Export\Export::getExportStylePath
	 */
	public function test_getExportStylePath() {

		$this->_book();

		$path = $this->export->getExportStylePath( 'epub' );
		$this->assertStringEndsWith( '/export/epub/style.scss', $path );

		$path = $this->export->getExportStylePath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/style.scss', $path );

		$path = $this->export->getExportStylePath( 'foobar' );
		$this->assertFalse( $path );

		switch_theme( 'pressbooks-custom-css' );

		$path = $this->export->getExportStylePath( 'epub' );
		$this->assertStringEndsWith( '/export/epub/style.css', $path );

		$path = $this->export->getExportStylePath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/style.css', $path );

		$path = $this->export->getExportStylePath( 'foobar' );
		$this->assertFalse( $path );
	}


//	/**
//	 * @covers \PressBooks\Modules\Export\Export::getGlobalTypographyMixinPath
//	 */
//	public function test_getGlobalTypographyMixinPath() {
//		// TODO: Testing this as-is triggers updateGlobalTypographyMixin, generates _mixins.css, generates _global-font-stack.scss... Code needs to be decoupled?
//		$this->markTestIncomplete();
//	}


	/**
	 * @covers \PressBooks\Modules\Export\Export::getExportScriptPath
	 */
	public function test_getExportScriptPath() {

		$this->_book();

		$path = $this->export->getExportScriptPath( 'epub' );
		$this->assertFalse( $path );

		$path = $this->export->getExportScriptPath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/script.js', $path );

		$path = $this->export->getExportScriptPath( 'foobar' );
		$this->assertFalse( $path );

		switch_theme( 'pressbooks-custom-css' );

		$opt = get_option( 'pressbooks_theme_options_pdf' );

		$opt['pdf_romanize_parts'] = 0;
		update_option( 'pressbooks_theme_options_pdf', $opt);

		$path = $this->export->getExportScriptPath( 'epub' );
		$this->assertFalse( $path );

		$path = $this->export->getExportScriptPath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/script.js', $path );

		$opt['pdf_romanize_parts'] = 1;
		update_option( 'pressbooks_theme_options_pdf', $opt);

		$path = $this->export->getExportScriptPath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/script-romanize.js', $path );

		$path = $this->export->getExportScriptPath( 'foobar' );
		$this->assertFalse( $path );
	}


	/**
	 * @covers \PressBooks\Modules\Export\Export::isParsingSections
	 */
	public function test_isParsingSections() {

		$val = $this->export->isParsingSections();
		$this->assertInternalType( 'bool', $val );
	}


//	/**
//	 * @covers \PressBooks\Modules\Export\Export::logError
//	 */
//	public function test_logError() {
//		// TODO: Testing this as-is would send emails, writes to error log... Need to be refactored.
//		$this->markTestIncomplete();
//	}


	/**
	 * @covers \PressBooks\Modules\Export\Export::createTmpFile
	 */
	public function test_createTmpFile() {

		$file = $this->export->createTmpFile();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );
	}


	/**
	 * @covers \PressBooks\Modules\Export\Export::timestampedFileName
	 */
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


	/**
	 * @covers \PressBooks\Modules\Export\Export::nonce
	 * @covers \PressBooks\Modules\Export\Export::verifyNonce
	 */
	public function test_nonce_AND_verifyNonce() {

		if ( ! defined( 'NONCE_KEY' ) ) {
			define( 'NONCE_KEY', '40~wF,SH)lm,Zr+^[b?_M8Z.g4gk%^gnqr+ZtnT,p6_K5.NuuN 0g@Y|T9+yBI|{' );
		}

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


	/**
	 * @covers \PressBooks\Modules\Export\Export::mimeType
	 */
	function test_mimeType() {

		$i = $this->export;
		$mime = $i::mimeType( __DIR__ . '/data/pb.png' );
		$this->assertStringStartsWith( 'image/png', $mime );
	}


	/**
	 * @covers \PressBooks\Modules\Export\Export::getExportFolder
	 */
	function test_getExportFolder() {

		$this->_book();

		$i = $this->export;
		$path = $i::getExportFolder();

		$this->assertTrue( is_dir( $path ) );
		$this->assertStringStartsWith( 'deny from all', file_get_contents( $path . '.htaccess' ) );
	}

}
