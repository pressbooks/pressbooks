<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Revisions extends \WP_REST_Revisions_Controller {

	public function __construct( $parent_post_type ) {
		parent::__construct( $parent_post_type );
		$this->namespace = 'pressbooks/v2';
	}

}
