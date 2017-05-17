<?php

class CompatibilityTest extends \WP_UnitTestCase {

	/**
	 * @covers \pb_meets_minimum_requirements
	 */
	public function test_pb_meets_minimum_requirements() {

		$result = \pb_meets_minimum_requirements();

		$this->assertTrue( is_bool( $result ) );
	}

}
