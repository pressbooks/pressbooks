<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Google\Client as GoogleClient;
use Google\Service\Docs as DocsService;
use Google\Service\Drive as DriveService;
use function Pressbooks\Utility\put_contents;

class DocsFetcher {

	protected GoogleClient $client;

	public function __construct( GoogleClient $client ) {
		$this->client = $client;
	}

	public function fetchDocument( string $doc_id ): array {
		$service = new DocsService( $this->client );
		$doc = $service->documents->get( $doc_id );
		return json_decode( json_encode( $doc->toSimpleObject() ), true );
	}

	public function getFileMetadata( string $doc_id ): array {
		$service = new DriveService( $this->client );
		$file = $service->files->get( $doc_id, [ 'fields' => 'name,mimeType' ] );
		return [
			'title'    => $file->getName(),
			'mimeType' => $file->getMimeType(),
		];
	}

	public function downloadImage( string $content_uri ) {
		$http = $this->client->authorize();
		try {
			$response = $http->get( $content_uri );
			if ( $response->getStatusCode() === 200 ) {
				return (string) $response->getBody();
			}
		} catch ( \Exception $e ) {
			// Fall through
		}
		return false;
	}

	public function fetchAndCache( string $doc_id, string $cache_dir ): string {
		$doc = $this->fetchDocument( $doc_id );
		$hash = substr( md5( wp_json_encode( $doc ) ), 0, 8 );
		$path = rtrim( $cache_dir, '/' ) . "/gdoc-{$doc_id}-{$hash}.json";
		put_contents( $path, wp_json_encode( $doc, JSON_PRETTY_PRINT ) );
		return $path;
	}
}
