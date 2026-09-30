<?php

namespace Pressbooks\Log;

interface StorageProvider {
	public function store( array $data, ?string $file_header = null );
	public function getDataFormat( array $data );
	public function setClient( $client );
}
