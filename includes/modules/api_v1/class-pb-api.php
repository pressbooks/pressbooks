<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Api_v1;

/**
 *  Abstract class provides common functionality for all resource requests
 */
abstract class Api {
	
	protected $allowed_formats = array(
	    'json',
//	    'xml',
	);
	
	abstract function controller( $args );

	
	function apiErrors( $code ) {
		
		switch ( $code ) {
			case 'method':
				$data = array(
				    'messages' => 'GET is currently the only request method accepted, as of v1 of the API.',
				);
				break;
			case 'resource':
				$data = array(
				    'messages' => 'The API requires a valid resource in order to return a response. Try looking for \'books\'',
				);
				break;
			case 'version':
				$data = array(
				    'messages' => 'The version you\'re requesting is not supported. Current version of the API is v1',
				);
				break;
			case 'format':
				$data = array(
				    'messages' => 'The format that is being requested is not supported. Try \'json\'',
				);
				break;
			case 'empty':
				$data = array(
				    'messages' => 'There are no records that can be returned with the request that was made',
				);
				break;
			case 'offset':
				$data = array(
				    'messages' => 'The offset is a larger value than the number of books available',
				);
				break;
			default:
				$data = array(
				    'messages' => 'Something went wrong with your API request',
				);
		}

		return wp_send_json_error( $data );
	}

	protected function response( $data, $format='json' ) {
		if( ! in_array( $format, $this->allowed_formats )){
			$this->apiErrors( 'format' );
		}
		
		if ( empty( $data ) ) {
			$this->apiErrors( 'empty' );
		}
		
		$format = ucfirst( $format );
		$method = 'render' . $format;
		$this->$method( $data );
		
	}

	/**
	 * 
	 * @param type $data
	 * @return type
	 * @throws Exception
	 */
	protected function renderJson( $data ){
		if ( ! is_array( $data ) ){
			throw new Exception( 'Data variable passed to \PressBooks\Api_v1\Api\renderJson is not an array' );
			$this->apiErrors( 'empty' );
		}
		
		header( 'Content-Type: application/json; charset=UTF-8' );
		
		return json_encode( $data );
		
	}
	
	/**
	 * Placeholder function
	 * 
	 * @TODO - build this function to support xml flavor ONIX
	 * @param type $data
	 * @return type
	 */
	protected function renderXml( $data ){
		return;
		
	}
	
	protected function EncodeCsv( $csv ){
		
	}
		
}

