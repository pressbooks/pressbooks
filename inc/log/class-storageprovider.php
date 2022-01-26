<?php

namespace Pressbooks\Log;

interface StorageProvider {
	function store( array $data, string $file_header );
	function getDataFormat( array $data );
	function setClient( $client );
}
