<?php

// --------------------------------------------------------------------------------------------------------------------
// Sanity check
// --------------------------------------------------------------------------------------------------------------------

$scriptName = basename( $argv[0] );

if ( $argc < 3 ) {
	echo "Error: $scriptName expects at least 2 parameters.\n";
	echo "Usage: `php $scriptName /path/to/input.scss /path/to/output.css`\n";
	die();
}

$inputFileName = $argv[1];
$outputFileName = $argv[2];

if ( ! file_exists( $inputFileName ) )
	die( "Error: The file $inputFileName was not found.\n" );

// --------------------------------------------------------------------------------------------------------------------
// Sassify
// --------------------------------------------------------------------------------------------------------------------

$includePaths = [
	__DIR__ . '/../assets/scss/partials',
	__DIR__ . '/../assets/scss/fonts',
	dirname( realpath( $inputFileName ) ),
];

try {
// Requires: https://github.com/sensational/sassphp
	$sass = new \Sass();
	$sass->setStyle( Sass::STYLE_EXPANDED );
	$sass->setIncludePath( implode( ':', $includePaths ) );
	$css = $sass->compileFile( $inputFileName );
}
catch ( Exception $e ) {
	die( $e->getMessage() );
}

file_put_contents( $outputFileName, $css );

echo( "$outputFileName was created successfully!\n" );
