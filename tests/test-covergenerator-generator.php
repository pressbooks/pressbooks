<?php

class CoverGenerator_GeneratorTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @return \Pressbooks\Covergenerator\Input
	 */
	protected function input() {
		$input = new \Pressbooks\Covergenerator\Input();
		$input
			->setTitle( 'My Test Cover' )
			->setSpineTitle( 'My Test Cover' )
			->setSubtitle( 'Test' )
			->setAuthor( 'Pressbooks' )
			->setSpineAuthor( 'Pressbooks' )
			->setAbout( 'This is a test' )
			->setTrimWidth( '5.5in' )
			->setTrimHeight( '8.5in' )
			->setSpineWidth( '1.9531in' );
		return $input;
	}


	public function test_generators() {

		\Pressbooks\Covergenerator\Covergenerator::commandLineDefaults();

		// V2
		$this->_book();

		$g = new \Pressbooks\Covergenerator\DocraptorPdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\DocraptorJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrincePdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrinceJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );

		// V1
		$this->_book( 'pressbooks-donham' );

		$g = new \Pressbooks\Covergenerator\DocraptorPdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\DocraptorJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrincePdf( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'pdf', \Pressbooks\Media\mime_type( $output_path ) );

		$g = new \Pressbooks\Covergenerator\PrinceJpg( $this->input() );
		$output_path = $g->generate();
		$this->assertFileExists( $output_path );
		$this->assertContains( 'jpeg', \Pressbooks\Media\mime_type( $output_path ) );
	}


}
