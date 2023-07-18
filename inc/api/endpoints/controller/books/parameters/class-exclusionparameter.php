<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

abstract class ExclusionParameter {

	protected array $includedValues = [];

	protected array $excludedValues = [];

	public function __construct( private readonly array $values ) {
		$this->includedValues = array_filter( $this->values, fn( string $value ) => ! str_starts_with( $value, '-' ) );
		$excluded_values = array_filter( $this->values, fn( string $value ) => str_starts_with( $value, '-' ) );

		$this->excludedValues = ! empty( $excluded_values ) ?
			array_map( fn( string $value ) => substr( $value, 1 ), $excluded_values ) : [];
	}

	protected function getSubQuery( string $inclusion_condition, string $exclusion_condition ): string {
		$included = ! empty( $this->includedValues );

		global $wpdb;

		$query = " AND EXISTS(SELECT blog_id FROM {$wpdb->blogmeta} WHERE meta_key = %s AND (";

		if ( $included ) {
			$query .= $inclusion_condition;
		}

		if ( ! empty( $this->excludedValues ) ) {
			if ( $included ) {
				$query .= ' AND ';
			}

			$query .= $exclusion_condition;
		}
		$query .= ') AND b.blog_id = blog_id)';

		return $query;
	}
}
