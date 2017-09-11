<?php
/**
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 *
 * Plugin Name: WP LaTeX for Pressbooks
 * Description:  Converts inline latex code into PNG images that are displayed in your Pressbooks blog posts.  Use either [latex]e^{\i \pi} + 1 = 0[/latex] or $latex e^{\i \pi} + 1 = 0$ or $$ e^{\i \pi} + 1 = 0 $$ syntax.
 * Version: 1.0.0
 * Author: Brad Payne
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 *
 * This plugin is forked from the original WP Latex v1.8 http://wordpress.org/plugins/wp-latex/ (c) Sidney Markowitz, Automattic, Inc.
 * It modifies the plugin to work with Pressbooks, strips unwanted features, adds others — activated at the network level
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PBLatex {

	var $options;
	var $methods;

	function init() {
		$this->options = get_option( 'pb_latex' );

		/**
		 * Append latex render methods to the list of default methods.
		 *
		 * @since 3.9.7
		 *
		 * @param array The list of default latex renderers.
		 */
		$this->methods = apply_filters( 'pb_latex_renderers', array(
			'Automattic_Latex_WPCOM' => 'wpcom',
		) );

		add_action( 'wp_head', array( &$this, 'wpHead' ) );

		add_filter( 'the_content', array( &$this, 'inlineToShortcode' ), 7 ); // Before wptexturize()
		add_filter( 'the_content', array( &$this, 'doThisShortcode' ), 8 ); // Before wpautop()

		/**
		 * Add additional style/script/shortcode dependencies for a given latex renderer.
		 * Ex:
		 * if ( 'katex' == $method ) {
		 *   wp_enqueue_script( 'pb_katex', 'path/to/katex.js' );
		 *   add_shortcode( 'katex', 'katexShortCode' );
		 * }
		 *
		 * @since 3.9.7
		 *
		 * @param string The method.
		 */
		if ( has_action( 'pb_enqueue_latex_scripts' ) ) {
			do_action( 'pb_enqueue_latex_scripts', $this->options['method'] );
		} else {
			add_shortcode( 'latex', array( &$this, 'shortCode' ) );
		}

		add_filter( 'no_texturize_shortcodes', function ( $excluded_shortcodes ) {
			$excluded_shortcodes[] = 'pb-latex';
			return $excluded_shortcodes;
		} );
	}

	function wpHead() {
		/**
		 * Add config scripts to the head of the page.
		 * Ex:
		 *
		 * if ( 'mathjax' == $method ) {
		 *   echo '<script type="text/javascript">
		 *     MathJax.Hub.Configured();
		 *   </script>';
		 * }
		 *
		 * @since 3.9.7
		 *
		 * @param string The method.
		 */
		apply_filters( 'pb_add_latex_config_scripts', $this->options['method'] );

		if ( !$this->options['css'] )
		return;
?>
<style type="text/css">
/* <![CDATA[ */
<?php echo $this->options['css']; ?>

/* ]]> */
</style>
<?php
	}

	// [latex size=0 color=000000 background=ffffff]\LaTeX[/latex]
	// Shortcode -> <img> markup.  Creates images as necessary.
	function shortCode( $_atts, $latex ) {
		$atts = shortcode_atts( array(
		    'size' => 0,
		    'color' => false,
		    'background' => false,
			), $_atts );

		$latex = preg_replace( array( '#<br\s*/?>#i', '#</?p>#i' ), ' ', $latex );

		$latex = str_replace(
			array( '&lt;', '&gt;', '&quot;', '&#8220;', '&#8221;', '&#039;', '&#8125;', '&#8127;', '&#8217;', '&#038;', '&amp;', "\n", "\r", "\xa0", '&#8211;' ),
			array( '<',    '>',    '"',      '``',       "''",     "'",      "'",       "'",       "'",       '&',      '&',     ' ',  ' ',  ' ',    '-' ),
			$latex
		);

		$latex_object = $this->latex( $latex, $atts['background'], $atts['color'], $atts['size'] );

		$url = esc_url( $latex_object->url );
		$alt = esc_attr( is_wp_error( $latex_object->error ) ? $latex_object->error->get_error_message() . ": $latex_object->latex" : $latex_object->latex  );

		return "<img src='$url' alt='$alt' title='$alt' class='latex' />";
	}

	function sanitizeColor( $color ) {
		$color = substr( preg_replace( '/[^0-9a-f]/i', '', $color ), 0, 6 );
		if ( 6 > $l = strlen( $color ) ) $color .= str_repeat( '0', 6 - $l );
		return $color;
	}

	function &latex( $latex, $background = false, $color = false, $size = 0 ) {
		$method = $this->methods[$this->options['method']];

		if ( empty( $method ) ) return false;

		if ( ! $background )
				$background = empty( $this->options['bg'] ) ? 'ffffff' : $this->options['bg'];
		if ( ! $color )
				$color = empty( $this->options['fg'] ) ? '000000' : $this->options['fg'];

		if ( 'wpcom' == $method ) {
			require_once( dirname( __FILE__ ) . "/automattic-latex-wpcom.php" );
		} else {
			/**
			 * Require custom latex class file.
			 * Ex: require_once( __DIR__ . '/custom_latex.php' );
			 *
			 * @since 3.9.7
			 *
			 * @param string The name of the class to be used.
			 */
			apply_filters( 'pb_require_latex', $method );
		}

		$latex_object = new $this->options['method']( $latex, $background, $color, $size, WP_CONTENT_DIR . '/latex', WP_CONTENT_URL . '/latex' );
		if ( isset( $this->options['wrapper'] ) )
				$latex_object->wrapper( $this->options['wrapper'] );
		$latex_object->url();

		return $latex_object;
	}

	function inlineToShortcode( $content ) {
		// double dollar
		$content = preg_replace( '/\${2}(.*)\${2}/isU', "[latex] $1 [/latex]", $content );

		if ( false === strpos( $content, '$latex' ) ) return $content;

		return preg_replace_callback( '#(\s*)\$latex[= ](.*?[^\\\\])\$(\s*)#', array( &$this, 'inlineToShortcodeCallback' ), $content );
	}

	function inlineToShortcodeCallback( $matches ) {
		$r = "{$matches[1]}[latex";

		if ( preg_match( '/.+((?:&#038;|&amp;|&)s=(-?[0-4])).*/i', $matches[2], $s_matches ) ) {
			$r .= ' size="' . ( int ) $s_matches[2] . '"';
			$matches[2] = str_replace( $s_matches[1], '', $matches[2] );
		}

		if ( preg_match( '/.+((?:&#038;|&amp;|&)fg=([0-9a-f]{6})).*/i', $matches[2], $fg_matches ) ) {
			$r .= ' color="' . $fg_matches[2] . '"';
			$matches[2] = str_replace( $fg_matches[1], '', $matches[2] );
		}

		if ( preg_match( '/.+((?:&#038;|&amp;|&)bg=([0-9a-f]{6})).*/i', $matches[2], $bg_matches ) ) {
			$r .= ' background="' . $bg_matches[2] . '"';
			$matches[2] = str_replace( $bg_matches[1], '', $matches[2] );
		}

		return "$r]{$matches[2]}[/latex]{$matches[3]}";
	}

	function doThisShortcode( $text ) {
		$current_shortcodes = $GLOBALS['shortcode_tags'];
		remove_all_shortcodes();

		add_shortcode( 'latex', [ $this, 'shortCode' ] );

		$text = do_shortcode( $text );

		$GLOBALS['shortcode_tags'] = $current_shortcodes;

		return $text;
	}

}

if ( is_admin() ) {
	require( dirname( __FILE__ ) . '/pb-latex-admin.php' );
	$pb_latex = new PBLatexAdmin;
} else {
	$pb_latex = new PBLatex;
}

add_action( 'init', array( &$pb_latex, 'init' ) );
