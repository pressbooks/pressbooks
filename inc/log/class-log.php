<?php

namespace Pressbooks\Log;

class Log {

	private array $data;

	private string $file_header;

	public const CSV_COLUMNS = [ 'Date', 'Key', 'Value' ];

	public function __construct( private StorageProvider $store_provider ) {
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
