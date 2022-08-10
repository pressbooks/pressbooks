<?php

class CoverGenerator_GeneratorTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @return \Pressbooks\Covergenerator\Input
	 * @group covergenerator
	 */
	protected function input() {
		return ( new \Pressbooks\Covergenerator\Input() )
			->setTitle( 'My Test Cover' )
			->setSpineTitle( 'My Test Cover' )
			->setSubtitle( 'Test' )
			->setAuthor( 'Pressbooks' )
			->setSpineAuthor( 'Pressbooks' )
			->setAbout( 'This is a test' )
			->setTrimWidth( '5.5in' )
			->setTrimHeight( '8.5in' )
			->setSpineWidth( '1.9531in' );
	}

	/**
	 * @group covergenerator
	 */
	public function test_generators() {
		\Pressbooks\Covergenerator\Covergenerator::commandLineDefaults();

		// V2
		$this->_book();

		$g = new \Pressbooks\Covergenerator\DocraptorPdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\DocraptorJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrincePdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrinceJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );

		// V1
		$this->_book( 'pressbooks-luther' );

		$g = new \Pressbooks\Covergenerator\DocraptorPdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\DocraptorJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrincePdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrinceJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertStringContainsString( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );
	}
}
