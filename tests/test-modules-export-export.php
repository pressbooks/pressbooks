<?php

use Pressbooks\Container;

class ExportMock extends \Pressbooks\Modules\Export\Export {
	/**
	 * @group export
	 */
	function convert() {
		$this->outputPath = \Pressbooks\Utility\create_tmp_file();
		return true;
	}

	/**
	 * @group export
	 */
	function validate() {
		return file_exists( $this->outputPath );
	}
}

class Modules_Export_ExportTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @var \ExportMock
	 * @group export
	 */
	protected $export;

	/**
	 * @group export
	 */
	public function moduleProvider() {
		return [
			[ '\Pressbooks\Modules\Export\Xhtml\Xhtml11', false ],
			[ '\Pressbooks\Modules\Export\Prince\Pdf', '\Pressbooks\Modules\Export\Xhtml\Xhtml11' ],
			[ '\Pressbooks\Modules\Export\Prince\PrintPdf', '\Pressbooks\Modules\Export\Xhtml\Xhtml11' ],
			[ '\Pressbooks\Modules\Export\Prince\Docraptor', '\Pressbooks\Modules\Export\Xhtml\Xhtml11' ],
			[ '\Pressbooks\Modules\Export\Prince\DocraptorPrint', '\Pressbooks\Modules\Export\Xhtml\Xhtml11' ],
			[ '\Pressbooks\Modules\Export\Epub\Epub', false ],
			[ '\Pressbooks\Modules\Export\WordPress\Wxr', false ],
			[ '\Pressbooks\Modules\Export\WordPress\VanillaWxr', false ],
			// [ '\Pressbooks\Modules\Export\Odt\Odt', false ], // TODO: Download/install Saxon-HE in Travis build script
			[ '\Pressbooks\Modules\Export\HTMLBook\HTMLBook', false ],
			[ '\Pressbooks\Modules\Export\ThinCc\WebLinks', false ],
		];
	}

	/**
	 * @group export
	 */
	public function moduleProviderHtml() {
		return [
			[ '\Pressbooks\Modules\Export\Xhtml\Xhtml11', false ],
			[ '\Pressbooks\Modules\Export\HTMLBook\HTMLBook', false ],
		];
	}

	/**
	 * @group export
	 */
	public function set_up() {
		parent::set_up();
		$this->export = new \ExportMock();
		do_action( 'pb_pre_export' );
	}

	/**
	 * @group export
	 */
	public function test_getExportStylePath() {
		$this->_book( 'pressbooks-luther' );

		$path = $this->export->getExportStylePath( 'epub' );
		$this->assertStringEndsWith( '/export/epub/style.scss', $path );

		$path = $this->export->getExportStylePath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/style.scss', $path );

		$path = $this->export->getExportStylePath( 'web' );
		$this->assertStringEndsWith( '/pressbooks-luther/style.scss', $path );

		$path = $this->export->getExportStylePath( 'foobar' );
		$this->assertFalse( $path );
	}

	//  public function test_getGlobalTypographyMixinPath() {
	//      // TODO: Testing this as-is triggers updateGlobalTypographyMixin, generates _mixins.css, generates _global-font-stack.scss... Code needs to be decoupled?
	//      $this->markTestIncomplete();
	//  }

	/**
	 * @group export
	 */
	public function test_getExportScriptPath() {
		$this->_book( 'pressbooks-luther' );

		$path = $this->export->getExportScriptPath( 'epub' );
		$this->assertFalse( $path );

		$path = $this->export->getExportScriptPath( 'prince' );
		$this->assertStringEndsWith( '/export/prince/script.js', $path );

		$path = $this->export->getExportScriptPath( 'foobar' );
		$this->assertFalse( $path );
	}

	/**
	 * @group export
	 */
	public function test_shouldParseSubsections() {
		$val = $this->export->shouldParseSubsections();
		$this->assertIsBool( $val );
	}

	//  public function test_logError() {
	//      // TODO: Testing this as-is would send emails, writes to error log... Need to be refactored.
	//      $this->markTestIncomplete();
	//  }

	/**
	 * @group export
	 */
	public function test_createTmpFile() {
		$file = $this->export->createTmpFile();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );
	}

	/**
	 * @group export
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
		$this->assertStringNotContainsString( '!', $file );
		$this->assertStringNotContainsString( '+', $file );
	}

	/**
	 * @group export
	 */
	public function test_nonce_AND_verifyNonce() {
		$time1 = time();
		$nonce1 = $this->export->nonce( $time1 );
		$this->assertIsString( $nonce1 );

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
	 * @group export
	 */
	public function test_mimeType() {
		$i = $this->export;
		$mime = $i::mimeType( __DIR__ . '/data/pb.png' );
		$this->assertStringStartsWith( 'image/png', $mime );
	}

	/**
	 * @group export
	 */
	public function test_getExportFolder() {
		$this->_book();

		$i = $this->export;
		$path = $i::getExportFolder();

		$this->assertTrue( is_dir( $path ) );
		$this->assertStringStartsWith( 'deny from all', file_get_contents( $path . '.htaccess' ) );
	}

	/**
	 * @group export
	 */
	public function test_getLatestExportStylePath() {
		$this->_book();

		$i = $this->export;

		$css = '/* Silence is golden. */';

		$css_files = [];

		$timestamp1 = time();
		$css_file1 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp1.css";
		$this->assertTrue( \Pressbooks\Utility\put_contents( $css_file1, $css ) );
		$css_files[] = $css_file1;

		$timestamp2 = time();
		$css_file2 = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp2.css";
		$this->assertTrue( \Pressbooks\Utility\put_contents( $css_file1, $css ) );
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

	/**
	 * @group export
	 */
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

	/**
	 * @group export
	 */
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

	/**
	 * @group export
	 */
	public function test_filters_useDocraptorInsteadOfPrince() {
		$filters = new \Pressbooks\Modules\Export\Prince\Filters();
		$this->assertTrue( is_bool( $filters->overridePrince() ) );
		$this->assertTrue( is_array( $filters->addToModules( [] ) ) ); // TODO: This test sucks
	}

	/**
	 * Sanity check that exports run without obvious errors
	 * Verify XHTML content for good measure
	 *
	 * @dataProvider moduleProvider
	 * @group export
	 */
	public function test_sanityChecks( $module, $prerequisite ) {
		$runtime = new \SebastianBergmann\Environment\Runtime();

		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		$contributor = [
			'slug' => 'patmetheny',
			'name' => 'Pat Metheny',
			'contributor_first_name' => 'Pat',
			'contributor_last_name' => 'Metheny',
			'contributor_description' => 'The <strong>drummer</strong> is the leader of any band',
		];
		( new \Pressbooks\Contributors() )->insert( $contributor, $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		update_option( 'pressbooks_theme_options_global', [ 'parse_subsections' => 1 ] );
		add_filter( 'pb_mathjax_use', '__return_false' );

		$paths = [];
		$xhtml_path = null;

		$modules = ( $prerequisite ) ? [ $prerequisite, $module ] : [ $module ];

		foreach ( $modules as $format ) {
			/** @var \Pressbooks\Modules\Export\Export $exporter */
			$exporter = new $format( [] );

			if (
				strpos( $format, '\Prince\\' ) !== false ||
				strpos( $format, '\Odt\\' ) !== false
			) {
				$exporter->url = $xhtml_path;
			}

			$this->assertTrue( $exporter->convert(), "Could not convert with {$module}" );
			$paths[] = $exporter->getOutputPath();
			if ( strpos( $format, '\Xhtml\Xhtml11' ) !== false ) {
				$xhtml_path = $exporter->getOutputPath();
			}
			if ( strpos( $format, '\HTMLBook\HTMLBook' ) !== false ) {
				// TODO: HTMLBook is too strict we don't pass the validation
			} elseif ( strpos( $format, '\Epub\Epub' ) !== false ) {
				// TODO: exec(): Unable to fork [/usr/bin/java -jar /opt/epubcheck/epubcheck.jar -q /path/to.epub 2>&1]
			} else {
				$this->assertTrue( $exporter->validate(), "Could not validate with {$format}" );
			}

			unset( $exporter );
		}

		if ( $xhtml_path ) {
			// Verify XHTML content for good measure
			$xhtml_content = file_get_contents( ( $xhtml_path ) );
			$this->assertStringContainsString( '<div class="footnotes">', $xhtml_content );
			$this->assertStringContainsString( '[latex]', $xhtml_content ); // TODO: add_filter( 'pb_mathjax_use', '__return_true' );
			$this->assertStringContainsString( ' <div id="attachment_1" ', $xhtml_content );
			$this->assertStringContainsString( '<p><em>Ka kite ano!</em></p>', $xhtml_content );
			$this->assertStringContainsString( 'https://github.com/pressbooks/pressbooks', $xhtml_content );

			$this->assertStringContainsString( '<p class="chapter-subtitle">Or, A Chapter to Test</p>', $xhtml_content );
			$this->assertStringContainsString( '<p>One or more interactive elements has been excluded from this version of the text', $xhtml_content );
			$this->assertStringContainsString( '#oembed-', $xhtml_content );

		}

		foreach ( $paths as $path ) {
			unlink( $path );
		}
	}

	/**
	 * @group export
	 */
	public function test_sanityCheckXhtmlWithoutBuckram() {
		$this->_book( 'pressbooks-luther' ); // Use an old book.
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		add_filter( 'pb_mathjax_use', '__return_false' );

		$module = '\Pressbooks\Modules\Export\Xhtml\Xhtml11';
		$exporter = new $module( [] );
		$this->assertTrue( $exporter->convert(), "Could not convert with {$module}" );
		$this->assertTrue( $exporter->validate(), "Could not validate with {$module}" );
		$xhtml_content = file_get_contents( $exporter->getOutputPath() );

		$this->assertStringContainsString( '<div class="footnotes">', $xhtml_content );
		$this->assertStringContainsString( '[latex]', $xhtml_content );
		$this->assertStringContainsString( ' <div id="attachment_1" ', $xhtml_content );
		$this->assertStringContainsString( '<p><em>Ka kite ano!</em></p>', $xhtml_content );
		$this->assertStringContainsString( 'https://github.com/pressbooks/pressbooks', $xhtml_content );
		// Heading elements should be in a "bad" place.
		$this->assertStringContainsString( '<div class="ugc chapter-ugc">', $xhtml_content );
		$this->assertStringContainsString( '<p class="chapter-subtitle">Or, A Chapter to Test</p>', $xhtml_content );

		unlink( $exporter->getOutputPath() );
	}

	/**
	 * @group export
	 */
	public function test_sanityCheckXhtmlDebug() {
		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		$_GET['debug'] = 'prince';
		$_GET['movefootnotes'] = true;
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}
		$timestamp = time();
		$css = '/* Silence is golden. */';
		$css_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp.css";
		\Pressbooks\Utility\put_contents( $css_file, $css );

		$module = '\Pressbooks\Modules\Export\Xhtml\Xhtml11';
		$exporter = new $module( [] );
		$this->assertTrue( $exporter->convert(), "Could not convert with {$module}" );
		$this->assertTrue( $exporter->validate(), "Could not validate with {$module}" );
		$xhtml_content = file_get_contents( $exporter->getOutputPath() );
		$url = network_home_url( sprintf( '/wp-content/uploads/sites/%d/pressbooks/css/prince-', get_current_blog_id() ) );
		$this->assertStringContainsString( "<link rel='stylesheet' href='$url", $xhtml_content );
		unlink( $exporter->getOutputPath() );
	}

	/**
	 * @dataProvider moduleProviderHtml
	 * @group export
	 */
	public function test_sanityCheckOptimizeForPrint( $module, $prerequisite ) {
		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		( new \Pressbooks\Contributors() )->insert( 'Ned Zimmerman', $meta_post->ID );
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );
		$modules = ( $prerequisite ) ? [ $prerequisite, $module ] : [ $module ];

		$_GET['optimize-for-print'] = 1;
		foreach ( $modules as $format ) {
			/** @var \Pressbooks\Modules\Export\Export $exporter */
			$exporter = new $format( [] );
			$this->assertTrue( $exporter->convert(), "Could not convert with {$module}" );
			$dom = new \DOMDocument();
			libxml_use_internal_errors( true );
			$dom->loadHTMLFile( $exporter->getOutputPath(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			libxml_clear_errors();
			$sections = $dom->getElementsByTagName( 'body' );
			$this->assertStringContainsString( 'print', $sections[0]->getAttribute( 'class' ) );
			unlink( $exporter->getOutputPath() );
		}

		$_GET['optimize-for-print'] = 0;
		foreach ( $modules as $format ) {
			/** @var \Pressbooks\Modules\Export\Export $exporter */
			$exporter = new $format( [] );
			$this->assertTrue( $exporter->convert(), "Could not convert with {$module}" );
			$dom = new \DOMDocument();
			libxml_use_internal_errors( true );
			$dom->loadHTMLFile( $exporter->getOutputPath(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			libxml_clear_errors();
			$sections = $dom->getElementsByTagName( 'body' );
			$this->assertStringNotContainsString( 'print', $sections[0]->getAttribute( 'class' ) );
			unlink( $exporter->getOutputPath() );
		}
	}

	/**
	 * @group export
	 */
	public function test_getContributorsForSectionXHTML() {
		$this->_book();
		$meta_post = ( new \Pressbooks\Metadata() )->getMetaPost();
		$contributor_metadata = [
			'name' => 'Pat Metheny',
			'institution' => 'Pressbooks University',
			'picture' => 'Sorry, there is not picture! :/',
			'url' => 'https://pressbooks.com',
			'linkedin' => 'https://linkedin.com/pat',
			'twitter' => 'https://twitter.com/pat',
			'github' => 'https://github.com/pat',
			'description' => '<strong>I am a description</strong>',
		];
		$contributor = ( new \Pressbooks\Contributors() )->insert( $contributor_metadata['name'], $meta_post->ID );

		$term = get_term_by( 'term_id', $contributor['term_id'], 'contributor' );
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_description',
			$contributor_metadata['description']
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_institution',
			$contributor_metadata['institution']
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_picture',
			$contributor_metadata['picture']
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_user_url',
			$contributor_metadata['url']
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_twitter',
			$contributor_metadata['twitter']
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_linkedin',
			$contributor_metadata['linkedin']
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_github',
			$contributor_metadata['github']
		);

		$contributors_print = \Pressbooks\Modules\Export\get_contributors_section( $meta_post->ID );
		$this->assertStringContainsString( $contributor_metadata['name'], $contributors_print );
		$this->assertStringContainsString( $contributor_metadata['github'], $contributors_print );
		$this->assertStringContainsString( $contributor_metadata['linkedin'], $contributors_print );
		$this->assertStringContainsString( $contributor_metadata['twitter'], $contributors_print );
		$this->assertStringContainsString( $contributor_metadata['url'], $contributors_print );
		$this->assertStringContainsString( $contributor_metadata['institution'], $contributors_print );
		$this->assertStringContainsString( $contributor_metadata['description'], $contributors_print );
		$this->assertStringContainsString( "<h3 class=\"about-authors\">About the Author</h3>", $contributors_print );
	}

	/**
	 * @group export
	 */
	public function test_HTMLBookConstructor() {
		$html_book = new Pressbooks\Modules\Export\HTMLBook\HTMLBook( [ 'endnotes' => true ] );
		$this->assertArrayHasKey( 'endnotes', $_GET );
		$this->assertTrue( $_GET['endnotes'] );
	}

	/**
	 * @group export
	 */
	public function test_endnoteShortcode() {
		$html_book = new Pressbooks\Modules\Export\HTMLBook\HTMLBook( [ 'endnotes' => true ] );
		$end_note = $html_book->endnoteShortcode( [] , 'I am a endnote, see you!');
		$attributes = $end_note->getAttributes();
		$this->assertArrayHasKey( 'class', $attributes );
		$this->assertEquals( 'endnote', $attributes['class'] );
	}


	/**
	 * @group export
	 * @test
	 */
	public function normalize_external_url_references():void  {
		$epub = new \Pressbooks\Modules\Export\Epub\Epub( [] );
		$css_font_import_1 = "@import \"https://fonts.googleapis.com/css?family=Roboto:400,400i,700,700i\";\n";
		$css_font_import_2 = "@import \"https://fonts.googleapis.com/css?family=Roboto+Slab:400,700\";\n";
		$css = $css_font_import_1 . $css_font_import_2 . "body { font-family: 'Roboto', sans-serif; }";

		$css = $epub->normalizeExternalFontsUrls( $css, 'epub/assets/' );

		$this->assertStringNotContainsString( $css_font_import_1, $css );
		$this->assertStringNotContainsString( $css_font_import_2, $css );

		$this->assertStringContainsString( '@import url(assets/Roboto.css);', $css );
		$this->assertStringContainsString( '@import url(assets/Roboto-Slab.css);', $css );
	}
}
