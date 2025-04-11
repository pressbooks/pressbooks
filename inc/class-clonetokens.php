<?php

namespace Pressbooks;

class CloneTokens {

	/**
	 * @var string
	 */
	protected $option_name = 'pb_clone_tokens';

	/**
	 * @var array
	 */
	protected $tokens = [];

	/**
	 * @var int
	 */
	protected $expiration = 3600; // 1 hour

	public function __construct() {
		$this->tokens = get_option( $this->option_name, [] );
	}

	/**
	 * @return string
	 */
	public function generateToken(): string {
		$token = md5( microtime() . wp_rand() );
		$this->tokens[ $token ] = time();
		$this->save();
		return $token;
	}

	/**
	 * @param string $token
	 *
	 * @return bool
	 */
	public function isTokenValid( string $token ): bool {
		$this->removeExpiredTokens();
		if ( isset( $this->tokens[ $token ] ) ) {
			unset( $this->tokens[ $token ] );
			$this->save();
			return true;
		}
		return false;
	}

	/**
	 * @return void
	 */
	private function removeExpiredTokens(): void {
		$this->tokens = get_option( $this->option_name, [] );
		foreach ( $this->tokens as $token => $timestamp ) {
			if ( $timestamp + $this->expiration < time() ) {
				unset( $this->tokens[ $token ] );
			}
		}
		$this->save();
	}

	/**
	 * @return void
	 */
	protected function save(): void {
		update_option( $this->option_name, $this->tokens );
	}

}
