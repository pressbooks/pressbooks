<?php

namespace Pressbooks\Log;

class Log {

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var StorageProvider
	 */
	private $store_provider;

	/**
	 * @var string
	 */
	private $file_header;

	const CSV_COLUMNS = [ 'Date', 'Key', 'Value' ];

	public function __construct( StorageProvider $store_provider ) {
		$this->store_provider = $store_provider;
		$this->data = [];
		$this->file_header = implode( ',', self::CSV_COLUMNS ) . "\n";
	}

	public function addRowToData( string $key, array $value ) {
		$this->data[ $key ] = $value;
	}

	public function store() {
		return $this->store_provider->store( $this->data, $this->file_header );
	}

}
