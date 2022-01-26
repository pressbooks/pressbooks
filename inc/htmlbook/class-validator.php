<?php

namespace Pressbooks\HTMLBook;

use function Pressbooks\Utility\str_ends_with;

class Validator {

	/**
	 * @var string $schemaPath
	 */
	protected $schemaPath;

	/**
	 * @var array
	 */
	protected $errors = [];

	public function __construct() {
		if ( ! defined( 'PB_XMLLINT_COMMAND' ) ) {
			define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );
		}
	}

	/**
	 * @return string
	 */
	public function getSchemaPath() {
		return $this->schemaPath ?? ( PB_PLUGIN_DIR . 'symbionts/HTMLBook/schema/htmlbook.xsd' );
	}

	/**
	 * @param string $schema_path
	 */
	public function setSchemaPath( string $schema_path ) {
		$this->schemaPath = $schema_path;
	}

	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Validate an HTMLBook file
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function validate( string $path ) {

		$command = PB_XMLLINT_COMMAND . ' --noout --schema ' . escapeshellcmd( $this->getSchemaPath() ) . ' ' . escapeshellcmd( $path ) . ' 2>&1';

		// Execute command
		$this->errors = [];
		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var );

		if ( isset( $output[0] ) && str_ends_with( $output[0], ' validates' ) ) {
			return true;
		} else {
			$this->errors = $output;
			return false;
		}
	}

}
