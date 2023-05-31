<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class LicenseCodeParameter implements BookParameter {

	public function __construct( private readonly array $license_codes ) {
	}

	public function getQueryCondition(): string {
		$license_placeholder = implode( ',', array_fill( 0, count( $this->license_codes ), '%s' ) );

		global $wpdb;
		$query = " AND EXISTS(SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND meta_value IN ($license_placeholder)";
		$query .= ' AND b.blog_id = blog_id)';

		return $query;
	}

	public function getPlaceHolderValues(): array {
		return array_merge( [ BookDataCollector::LICENSE_CODE ], $this->license_codes );
	}
}
