<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

/**
 * Heavily inspired by Automattic's https://github.com/Automattic/jetpack/blob/master/modules/latex.php
 */
class MathJax {

	/**
	 * @var array
	 */
	private $defaultOptions = [
		'fg' => '000000',
		'bg' => 'transparent',
	];

	/**
	 * @var MathJax
	 */
	private static $instance = null;

	/**
	 * @return MathJax
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param MathJax $obj
	 */
	static public function hooks( MathJax $obj ) {

		add_action( 'admin_menu', [ $obj, 'addMenu' ] );

		add_filter(
			'no_texturize_shortcodes',
			function ( $shortcodes ) {
				$shortcodes[] = 'latex';
				return $shortcodes;
			}
		);

		add_filter( 'the_content', [ $obj, 'latexMarkup' ], 9 );

		// IF EXPORT LOAD SHORTCODE
		add_shortcode( 'latex', [ $obj, 'latexShortcode' ] );

		// ELSE IF BOOK AND THE CONTENT HAS MATH THEN MATHJAX
		wp_enqueue_script( 'pb_mathjax', 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-MML-AM_CHTML.js&delayStartupUntil=configured' );
		add_action( 'wp_head', [ $obj, 'addHeaders' ] );

	}

	/**
	 * Put PB MathJax under options meny
	 */
	public function addMenu() {
		add_options_page(
			__( 'PB MathJax', 'pressbooks' ),
			__( 'PB MathJax', 'pressbooks' ),
			'edit_posts',
			'pressbooks_mathjax',
			[ $this, 'renderPage' ]
		);
	}

	/**
	 * PB MathJax admin page
	 */
	public function renderPage() {
		$this->saveOptions();
		$options = get_option( 'pb_latex', $this->defaultOptions );


		$test_latex_formula = '\displaystyle P_\nu^{-\mu}(z)=\frac{\left(z^2-1\right)^{\frac{\mu}{2}}}{2^\mu \sqrt{\pi}\Gamma\left(\mu+\frac{1}{2}\right)}\int_{-1}^1\frac{\left(1-t^2\right)^{\mu -\frac{1}{2}}}{\left(z+t\sqrt{z^2-1}\right)^{\mu-\nu}}dt';
		$test_image = $this->latexRender( $test_latex_formula, $this->defaultOptions['fg'], $this->defaultOptions['bg'] );

		$blade = Container::get( 'Blade' );
		echo $blade->render(
			'admin.mathjax',
			[
				'wp_nonce_field' => wp_nonce_field( 'save', 'pb-mathjax-nonce', true, false ),
				'test_image' => $test_image,
				'fg' => $options['fg'],
				'bg' => $options['bg'],
			]
		);
	}

	/**
	 * PB MathJax admin page form submission
	 *
	 * @return bool
	 */
	public function saveOptions() {
		if ( ! isset( $_POST['pb-mathjax-nonce'] ) || ! wp_verify_nonce( $_POST['pb-mathjax-nonce'], 'save' ) ) {
			return false;
		}

		$fg = strtolower( substr( preg_replace( '/[^0-9a-f]/i', '', $_POST['fg'] ), 0, 6 ) );
		if ( 6 > $l = strlen( $fg ) ) {
			$fg .= str_repeat( '0', 6 - $l );
		}

		if ( 'transparent' == trim( $_POST['bg'] ) ) {
			$bg = 'transparent';
		} else {
			$bg = substr( preg_replace( '/[^0-9a-f]/i', '', $_POST['bg'] ), 0, 6 );
			if ( 6 > $l = strlen( $bg ) ) {
				$bg .= str_repeat( '0', 6 - $l );
			}
		}

		$options = [
			'bg' => $bg,
			'fg' => $fg,
		];

		return update_option( 'pb_latex', $options );
	}

	/**
	 * LaTeX support.
	 *
	 * Backward compatibility requires support for both "[latex][/latex]", and
	 * "$latex $" shortcodes.
	 *
	 * $latex e^{\i \pi} + 1 = 0$  ->  [latex]e^{\i \pi} + 1 = 0[/latex]
	 * $latex [a, b]$              ->  [latex][a, b][/latex]
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function latexMarkup( $content ) {
		$textarr = wp_html_split( $content );

		$regex = '%
		\$latex(?:=\s*|\s+)
		((?:
			[^$]+ # Not a dollar
		|
			(?<=(?<!\\\\)\\\\)\$ # Dollar preceded by exactly one slash
		)+)
		(?<!\\\\)\$ # Dollar preceded by zero slashes
	%ix';

		foreach ( $textarr as &$element ) {
			if ( '' == $element || '<' === $element[0] ) {
				continue;
			}

			if ( false === stripos( $element, '$latex' ) ) {
				continue;
			}

			$element = preg_replace_callback( $regex, [ $this, 'latexSrc' ], $element );
		}

		return implode( '', $textarr );
	}

	/**
	 * @param $matches
	 *
	 * @return string
	 */
	public function latexSrc( $matches ) {
		$latex = $matches[1];
		$bg = $this->defaultOptions['bg'];
		$fg = $this->defaultOptions['fg'];
		$s = 0;
		$latex = $this->latexEntityDecode( $latex );
		if ( preg_match( '/.+(&fg=[0-9a-f]{6}).*/i', $latex, $fg_matches ) ) {
			$fg = substr( $fg_matches[1], 4 );
			$latex = str_replace( $fg_matches[1], '', $latex );
		}
		if ( preg_match( '/.+(&bg=[0-9a-f]{6}).*/i', $latex, $bg_matches ) ) {
			$bg = substr( $bg_matches[1], 4 );
			$latex = str_replace( $bg_matches[1], '', $latex );
		}
		if ( preg_match( '/.+(&s=[0-9-]{1,2}).*/i', $latex, $s_matches ) ) {
			$s = (int) substr( $s_matches[1], 3 );
			$latex = str_replace( $s_matches[1], '', $latex );
		}
		return $this->latexRender( $latex, $fg, $bg, $s );
	}

	/**
	 * @param string $latex
	 *
	 * @return string
	 */
	public function latexEntityDecode( $latex ) {
		return str_replace( [ '&lt;', '&gt;', '&quot;', '&#039;', '&#038;', '&amp;', "\n", "\r" ], [ '<', '>', '"', "'", '&', '&', ' ', ' ' ], $latex );
	}

	/**
	 * @param string $latex
	 * @param string $fg
	 * @param string $bg
	 * @param int $s
	 *
	 * @return string
	 */
	public function latexRender( $latex, $fg, $bg, $s = 0 ) {
		// TODO: Change to pb-mathjax URL
		$url = "//s0.wp.com/latex.php?latex=" . urlencode( $latex ) . "&bg=" . $bg . "&fg=" . $fg . "&s=" . $s;
		$url = esc_url( $url );
		$alt = str_replace( '\\', '&#92;', esc_attr( $latex ) );
		return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="latex" />';
	}

	/**
	 * The shortcode way.
	 *
	 * Example: [latex s=4 bg=00f fg=ff0]\LaTeX[/latex]
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function latexShortcode( $atts, $content = '' ) {

		$atts = shortcode_atts(
			[
				's' => 0,
				'bg' => $this->defaultOptions['bg'],
				'fg' => $this->defaultOptions['fg'],
			], $atts, 'latex'
		);

		return $this->latexRender( $this->latexEntityDecode( $content ), $atts['fg'], $atts['bg'], $atts['s'] );
	}


	public function addHeaders() {
		echo '<script type="text/x-mathjax-config">
			MathJax.Hub.Config({
				TeX: { extensions: ["cancel.js", "mhchem.js"] },
				tex2jax: {inlineMath: [["[latex]","[/latex]"]] }
			});
			</script>
			<script type="text/javascript">
				MathJax.Hub.Configured();
			</script>';
	}

}