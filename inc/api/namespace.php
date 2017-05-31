<?php

namespace Pressbooks\Api;

/**
 * REST API init
 *
 * There are a couple ways to initialize REST endpoints in WP. One is passing `show_in_rest`, `rest_base`, and/or `rest_controller_class`
 * arguments to `register_post_type()`, another is the `rest_api_init` action. This function covers the latter.
 *
 * @see \Pressbooks\PostType\register_post_types
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/
 */
function init() {
	$controller = new Endpoints\Controller\Media();
	$controller->register_routes();
}
