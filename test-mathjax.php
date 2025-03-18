<?php
/**
 * Test file for MathJax improvements
 */

// Mock WordPress functions needed by MathJax class
if ( ! function_exists( 'wp_html_split' ) ) {
	function wp_html_split( $string ) {
		return preg_split( '/<[^>]+>/', $string, -1, PREG_SPLIT_DELIM_CAPTURE );
	}
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	function is_plugin_active( $plugin ) {
		return false;
	}
}

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	function is_plugin_active_for_network( $plugin ) {
		return false;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string ) {
		return strip_tags( $string );
	}
}

if ( ! function_exists( 'normalize_whitespace' ) ) {
	function normalize_whitespace( $string ) {
		return preg_replace( '/\s+/', ' ', trim( $string ) );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		if ( $option === 'pb_mathjax_config' ) {
			return [
				'fg' => '000000',
				'bg' => 'ffffff',
			];
		}
		return $default;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		// Do nothing
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		// Do nothing
	}
}

// Define constants needed by MathJax class
if ( ! defined( 'PB_MATHJAX_URL' ) ) {
	define( 'PB_MATHJAX_URL', 'https://example.com/mathjax' );
}

// Include the MathJax class
require_once 'inc/class-mathjax.php';

// Create a new instance of MathJax
$mathjax = new Pressbooks\MathJax();
$mathjax->usePbMathJax = true;

// Sample content with LaTeX and HTML entities
$content = '<h3>Paragraph 1: Calculus &amp; Differentiation</h3>
In mathematical analysis, the derivative of a function \( f(x) \) with respect to \( x \) is denoted as \( f\'(x) \) or \( \dv{f}{x} \). The chain rule states that if \( y = g(f(x)) \), then the derivative is given by \( \dv{y}{x} = \dv{g}{f} \cdot \dv{f}{x} \). Additionally, the second derivative, which represents the curvature, is written as \( \pdv[2]{f}{x} \).

For instance, the integral of a polynomial function can be computed using the formula: \( f(x) \)

\[
\int x^n \dd{x} = \frac{x^{n+1}}{n+1} + C
\]

where \( C \) is the constant of integration.';

// Test the replaceLatexDelimiters method
echo "Testing replaceLatexDelimiters method:\n";
$result = $mathjax->replaceLatexDelimiters( $content );
echo 'Result contains LaTeX shortcodes or img tags: ' . ( strpos( $result, '[latex]' ) !== false || strpos( $result, '<img' ) !== false ? 'Yes' : 'No' ) . "\n";
echo 'Sample of result: ' . substr( $result, 0, 200 ) . "...\n";

// Test the replaceMathML method
$mathml_content = '<math xmlns="http://www.w3.org/1998/Math/MathML"><mrow><mi>E</mi><mo>=</mo><mi>m</mi><msup><mi>c</mi><mn>2</mn></msup></mrow></math>';
echo "\nTesting replaceMathML method:\n";
$result = $mathjax->replaceMathML( $mathml_content );
echo 'Result contains img tag: ' . ( strpos( $result, '<img' ) !== false ? 'Yes' : 'No' ) . "\n";
echo 'Sample of result: ' . substr( $result, 0, 200 ) . "...\n";

// Test the asciiMathMarkup method
$asciimath_content = 'The formula $asciimath x^2 + y^2 = r^2$ represents a circle.';
echo "\nTesting asciiMathMarkup method:\n";
$result = $mathjax->asciiMathMarkup( $asciimath_content );
echo 'Result contains asciimath shortcodes or img tags: ' . ( strpos( $result, '[asciimath]' ) !== false || strpos( $result, '<img' ) !== false ? 'Yes' : 'No' ) . "\n";
echo 'Sample of result: ' . substr( $result, 0, 200 ) . "...\n";

echo "\nTests completed.\n";
