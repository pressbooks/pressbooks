<?php

namespace Pressbooks\Api\Endpoints\Controller\Books\Parameters;

abstract class ExclusionParameter {

	protected array $includedValues = [];

	protected array $excludedValues = [];

	public function __construct( private array $values ) {
		$this->values = array_map( 'strtolower', $this->values );
		$this->includedValues = array_filter( $this->values, fn( string $value ) => ! str_starts_with( $value, '-' ) );
		$excluded_values = array_filter( $this->values, fn( string $value ) => str_starts_with( $value, '-' ) );

		$this->excludedValues = ! empty( $excluded_values ) ?
			array_map( fn( string $value ) => substr( $value, 1 ), $excluded_values ) : [];
	}

	public function getPlaceHolders( string $meta_key ): array {
		$included_values = implode( '|', $this->includedValues );
		$excluded_values = implode( '|', $this->excludedValues );
		return array_merge(
			[ $meta_key ],
			$this->includedValues ? [ $included_values ] : [],
			$this->excludedValues ? [ $excluded_values ] : []
		);
	}
}
