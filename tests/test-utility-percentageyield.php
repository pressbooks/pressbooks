<?php

class PercentageYieldTest extends \WP_UnitTestCase {
	/**
	 * @return array
	 * @group utility
	 */
	public function validRanges() {
		return [
			[ 1, 100, 100 ],
			[ 1, 100, 50 ],
			[ 1, 50, 100 ],
			[ 90, 100, 5 ],
			[ 51, 62, 789 ],
		];
	}

	/**
	 * @dataProvider validRanges
	 *
	 * @param int $start
	 * @param int $end
	 * @param int $ticks
	 * @group utility
	 */
	public function test_tick( $start, $end, $ticks ) {
		$chunks = (int) max( round( $ticks / ( $end - $start ) ), 1 );
		$y = new \Pressbooks\Utility\PercentageYield( $start, $end, $ticks );
		$i = 1;
		$j = $start;
		while ( $i < $ticks ) {
			$loops = 0;
			foreach ( $y->tick( 'Test' ) as $percentage => $msg ) {
				$this->assertEquals( $j, $percentage );
				$this->assertStringContainsString( "Test ($i of $ticks)", $msg );
				++$loops;
			}
			$this->assertEquals( 1, $loops );
			if ( ++$i % $chunks === 0 ) {
				++$j;
			}
		}
	}

	/**
	 * @group utility
	 */
	public function test_tick_invalid_range() {
		$ticks = 100;
		$y = new \Pressbooks\Utility\PercentageYield( -999, 999, $ticks );
		for ( $i = 1; $i <= $ticks; ++$i ) {
			$loops = 0;
			foreach ( $y->tick( 'Test' ) as $percentage => $msg ) {
				$this->assertEquals( $percentage, $i );
				$this->assertStringContainsString( "Test ($i of $ticks)", $msg );
				++$loops;
			}
			$this->assertEquals( 1, $loops );
		}
	}

	/**
	 * @group utility
	 */
	public function test_tick_dumb_range() {
		$ticks = 100;
		$y = new \Pressbooks\Utility\PercentageYield( 99, 99, $ticks );
		for ( $i = 1; $i <= $ticks; ++$i ) {
			$loops = 0;
			foreach ( $y->tick( 'Test' ) as $percentage => $msg ) {
				$this->assertEquals( $percentage, 99 );
				$this->assertStringContainsString( "Test ($i of $ticks)", $msg );
				++$loops;
			}
			$this->assertEquals( 1, $loops );
		}
	}

	/**
	 * @group utility
	 */
	public function test_yield_more_than_we_estimated() {
		$ticks = 100;
		$y = new \Pressbooks\Utility\PercentageYield( 1, 100, $ticks );
		for ( $i = 1; $i <= $ticks; ++$i ) {
			$loops = 0;
			foreach ( $y->tick( 'Test' ) as $percentage => $msg ) {
				$this->assertEquals( $percentage, $i );
				$this->assertStringContainsString( "Test ($i of $ticks)", $msg );
				++$loops;
			}
			$this->assertEquals( 1, $loops );
		}
		for ( $i = 101; $i <= 999; ++$i ) {
			$loops = 0;
			foreach ( $y->tick( 'Test' ) as $percentage => $msg ) {
				$this->assertEquals( $percentage, 100 );
				$this->assertStringContainsString( "Test ($i of $ticks)", $msg );
				++$loops;
			}
			$this->assertEquals( 1, $loops );
		}
	}

	/**
	 * @group utility
	 */
	public function test_not_everything_is_yielded() {
		// Nothing emitted
		$loops = 0;
		$ticks = 100;
		$y = new \Pressbooks\Utility\PercentageYield( 1, 100, $ticks );
		for ( $i = 1; $i <= $ticks; ++$i ) {
			foreach ( $y->tick( 'Test', false ) as $percentage => $msg ) {
				++$loops;
			}
		}
		$this->assertEquals( 0, $loops );

		// Half emitted
		$loops = 0;
		$ticks = 100;
		$y = new \Pressbooks\Utility\PercentageYield( 1, 100, $ticks );
		for ( $i = 1; $i <= $ticks; ++$i ) {
			$emit = ( $i % 2 === 0 ) ? true : false;
			foreach ( $y->tick( 'Test', $emit ) as $percentage => $msg ) {
				$this->assertTrue( $percentage % 2 === 0 );
				++$loops;
			}
		}
		$this->assertEquals( 50, $loops );
	}
}
