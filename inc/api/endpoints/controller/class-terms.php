<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Terms extends \WP_REST_Terms_Controller {
	public function __construct( $parent_taxonomy ) {
		parent::__construct( $parent_taxonomy );
		$this->namespace = 'pressbooks/v2';
	}

	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ): bool {
		if ( has_filter( 'pb_set_api_items_permission' ) && apply_filters( 'pb_set_api_items_permission', $this->rest_base ) ) {
			return true;
		}
		return parent::get_items_permissions_check( $request );
	}
}
