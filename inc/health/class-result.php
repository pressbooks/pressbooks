<?php

namespace Pressbooks\Health;

use Illuminate\Contracts\Support\Arrayable;

class Result implements Arrayable {
	public bool $status;

	public string $message;

	public function __construct( bool $status ) {
		$this->status = $status;
	}

	public static function make(): self {
		return new self( $status = true );
	}

	public function ok( string $message = '' ): self {
		$this->status = true;

		$this->message = $message;

		return $this;
	}

	public function failed( string $message = '' ): self {
		$this->status = false;

		$this->message = $message;

		return $this;
	}

	public function toArray(): array {
		return [
			'status' => $this->status ? 'Ok' : 'Failed',
			'message' => $this->message,
		];
	}
}
