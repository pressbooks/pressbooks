<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Terms extends \WP_REST_Terms_Controller {
	public function __construct( $parent_taxonomy ) {
		parent::__construct( $parent_taxonomy );
		$this->namespace = 'pressbooks/v2';
	}
}
