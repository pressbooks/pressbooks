<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Media extends \WP_REST_Attachments_Controller {

	public function __construct() {
		parent::__construct( 'attachment' );
		$this->rest_base = 'media';
		$this->namespace = 'pressbooks/v2';
	}

}
