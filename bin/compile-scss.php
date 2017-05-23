<?php

// --------------------------------------------------------------------------------------------------------------------
// Sanity check
// --------------------------------------------------------------------------------------------------------------------

$script_name = basename( $argv[0] );

if ( $argc < 3 ) {
	echo "Error: $script_name expects at least 2 parameters.\n";
	echo "Usage: `php $script_name /path/to/input.scss /path/to/output.css`\n";
	die();
}

$input_file_name = $argv[1];
$output_file_name = $argv[2];

if ( ! file_exists( $input_file_name ) ) {
	die( "Error: The file $input_file_name was not found.\n" );
}

// --------------------------------------------------------------------------------------------------------------------
// Sassify
// --------------------------------------------------------------------------------------------------------------------

$includePaths = [
	__DIR__ . '/../assets/scss/partials',
	__DIR__ . '/../assets/scss/fonts',
	dirname( realpath( $input_file_name ) ),
];

try {
	// Requires: https://github.com/sensational/sassphp
	$sass = new \Sass();
	$sass->setStyle( Sass::STYLE_EXPANDED );
	$sass->setIncludePath( implode( ':', $includePaths ) );
	$css = $sass->compileFile( $input_file_name );
} catch ( Exception $e ) {
	die( $e->getMessage() );
}

file_put_contents( $output_file_name, $css );

echo( "$output_file_name was created successfully!\n" );
