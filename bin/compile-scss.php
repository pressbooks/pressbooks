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

$include_paths = [
	__DIR__ . '/../assets/scss/partials',
	__DIR__ . '/../assets/scss/fonts',
	dirname( realpath( $input_file_name ) ),
];

$scss = \Pressbooks\Utility\get_contents( $input_file_name );

try {
	$scssphp = new \Leafo\ScssPhp\Compiler;
	$scssphp->setImportPaths( $include_paths );
	$css = $scssphp->compile( $scss );
} catch ( Exception $e ) {
	die( $e->getMessage() );
}

\Pressbooks\Utility\put_contents( $output_file_name, $css );

echo( "$output_file_name was created successfully!\n" );
