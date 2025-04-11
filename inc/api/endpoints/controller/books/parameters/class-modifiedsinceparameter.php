<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

class ModifiedSinceParameter implements BookParameter {

	public function __construct( private readonly int $modified_since ) {
	}
	public function getQueryCondition(): string {
		return ' AND last_updated > %s';
	}

	public function getPlaceHoldervalues(): array {
		$epoch = $this->modified_since;
		$datetime = new \DateTime( "@$epoch" );
		return [ $datetime->format( 'Y-m-d H:i:s' ) ];
	}
}
