<?php

use Pressbooks\CloneTokens;

/**
 * @group clone-tokens
 */
class CloneTokensTest extends \WP_UnitTestCase {

	/**
	 * @test
	 */
	public function generated_token_is_stored(): void {
		$tokens = new CloneTokens();
		$token = $tokens->generateToken();
		$this->assertNotEmpty( $token );

		$token_stored = get_option( 'pb_clone_tokens', [] );
		$this->assertEquals( $token, array_keys( $token_stored )[0] );
	}

	/**
	 * @test
	 */
	public function check_validation_token_and_deletion(): void {
		$tokens = new CloneTokens();
		$token = $tokens->generateToken();
		$this->assertTrue( $tokens->isTokenValid( $token ) );

		$token_stored = get_option( 'pb_clone_tokens', [] );
		$this->assertEmpty( $token_stored );

		$invalid_token = 'invalid_token';
		$this->assertFalse( $tokens->isTokenValid( $invalid_token ) );
	}

	/**
	 * @test
	 */
	public function check_expired_tokens(): void {
		$tokens = new CloneTokens();
		$token = $tokens->generateToken();

		$token_stored = get_option( 'pb_clone_tokens', [] );

		// change expiration time to 1 second past
		$token_stored = [ $token => array_values( $token_stored )[0] - 3601 ];
		update_option( 'pb_clone_tokens', $token_stored );

		$this->assertFalse( $tokens->isTokenValid( $token ) );
	}
}
