<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Api_v1;

/**
 *  Abstract class provides common functionality for all resource requests
 */
abstract class Api {

	/**
	 * Control the list of output formats
	 *
	 * @var array
	 */
	protected $allowed_formats = array(
		'json',
		'xml',
	);

	abstract function controller( $args );

	/**
	 * List of simple error messages
	 * Echos a json response with appropriate error code
	 *
	 * @param string $code
	 */
	static function apiErrors( $code ) {

		switch ( $code ) {
			case 'method':
				$data = array(
					'messages' => 'GET is currently the only request method accepted, as of v1 of the API.',
					'documentation' => '/api/v1/docs',
				);
				break;
			case 'resource':
				$data = array(
					'messages' => 'The API requires a valid resource in order to return a response. Try looking for \'books\'',
					'documentation' => '/api/v1/docs',
				);
				break;
			case 'version':
				$data = array(
					'messages' => 'The version you\'re requesting is not supported. Current version of the API is v1',
					'documentation' => '/api/v1/docs',
				);
				break;
			case 'format':
				$data = array(
					'messages' => 'The format that is being requested is not supported. Try \'json\'',
					'documentation' => '/api/v1/docs',
				);
				break;
			case 'empty':
				$data = array(
					'messages' => 'There are no records that can be returned with the request that was made',
					'documentation' => '/api/v1/docs',
				);
				break;
			case 'offset':
				$data = array(
					'messages' => 'The offset is a larger value than the number of books available',
					'documentation' => '/api/v1/docs',
				);
				break;
			default:
				$data = array(
					'messages' => 'Something went wrong with your API request',
					'documentation' => '/api/v1/docs',
				);
		}

		wp_send_json_error( $data );
	}

	/**
	 * Give this an array and a format, it will send it to the correct rendering method
	 *
	 * @param array $data
	 * @param string $format
	 */
	protected function response( $data, $format = 'json' ) {
		if ( ! in_array( $format, $this->allowed_formats ) ) {
			static::apiErrors( 'format' );
		}

		if ( empty( $data ) ) {
			static::apiErrors( 'empty' );
		}

		$format = ucfirst( $format );
		$method = 'render' . $format;
		$this->$method( $data );
	}

	/**
	 * Given an array, will produce a json response
	 *
	 * @param array $data
	 *
	 * @throws \Exception
	 */
	protected function renderJson( $data ) {
		if ( ! is_array( $data ) ) {
			throw new \Exception( 'Data variable passed to \Pressbooks\Modules\Api_v1\Api\renderJson is not an array' );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Given an array, it will produce an xml response
	 *
	 * @TODO - build this function to support xml flavor ONIX
	 *
	 * @param array $data
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	protected function renderXml( $data ) {
		if ( ! is_array( $data ) ) {
			throw new \Exception( 'Data variable passed to \Pressbooks\Modules\Api_v1\Api\renderXml is not an array' );
		}

		// creating object of SimpleXMLElement
		$xml = new \SimpleXMLElement( "<?xml version=\"1.0\" encoding=\"utf-8\"?><xml></xml>" );

		// function call to convert array to xml
		$xml = $this->arrayToXml( $data, $xml );
		header( 'Content-type: text/xml' );

		echo $xml->asXML();
	}

	/**
	 * Recursive helper function
	 *
	 *
	 * @param array $data
	 * @param \SimpleXMLElement $xml
	 *
	 * @return \SimpleXMLElement
	 */
	private function arrayToXml( $data, \SimpleXMLElement $xml ) {

		foreach ( $data as $key => $value ) {

			if ( is_array( $value ) ) {
				if ( ! is_numeric( $key ) ) {
					$subnode = $xml->addChild( "$key" );
					$this->arrayToXml( $value, $subnode );
				}
				else {
					$subnode = $xml->addChild( "item$key" );
					$this->arrayToXml( $value, $subnode );
				}
			}
			else {
				$xml->addChild( "$key", htmlspecialchars( "$value" ) );
			}
		}

		return $xml;
	}

	protected function EncodeCsv( $csv ) {

	}

}
