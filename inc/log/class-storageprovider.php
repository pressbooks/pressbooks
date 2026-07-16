<?php

namespace Pressbooks\Log;

interface StorageProvider {
	function store( array $data, ?string $file_header = null );
	function getDataFormat( array $data );
	function setClient( $client );
}
