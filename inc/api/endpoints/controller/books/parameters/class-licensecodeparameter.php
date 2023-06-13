<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

use Pressbooks\DataCollector\Book as BookDataCollector;

class LicenseCodeParameter extends ExclusionParameter implements BookParameter {
	public function getQueryCondition(): string {
		$included = ! empty( $this->includedValues );

		global $wpdb;

		$query = '';

		if ( $included ) {
			$placeholders = implode( ',', array_fill( 0, count( $this->includedValues ), '%s' ) );
			$query = " AND EXISTS(SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND meta_value IN ($placeholders)";
			$query .= ' AND b.blog_id = blog_id)';
		}

		if ( ! empty( $this->excludedValues ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $this->excludedValues ), '%s' ) );

			if ( $included ) {
				$query .= ' AND ';
			}

			$query .= " AND NOT EXISTS(SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND meta_value IN ($placeholders)";
			$query .= ' AND b.blog_id = blog_id)';
		}

		return $query;
	}

	public function getPlaceHolderValues(): array {
		return $this->getPlaceHolders( BookDataCollector::LICENSE_CODE );
	}
}
