<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

/**
 * Heavily inspired by Automattic's
 * @see https://github.com/Automattic/jetpack/blob/master/modules/latex.php
 */
class MathJax {

	const OPTION = 'pb_mathjax';

	/**
	 * @var MathJax
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $defaultOptions = [
		'fg' => '000000',
		'bg' => 'transparent',
	];

	/**
	 * Webbook Section Cache
	 *
	 * @var array
	 */
	private $sectionHasMath = [];

	/**
	 * Use PB MathJax Node.js service to render SVG/PNG image?
	 * @see https://github.com/pressbooks/pb-mathjax
	 *
	 * @var bool
	 */
	public $usePbMathJax = false;

	/**
	 * @return MathJax
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			// Don't initialize MathJax if QuickLaTeX is active
			if ( ! is_plugin_active( 'wp-quicklatex/wp-quicklatex.php' ) && ! is_plugin_active_for_network( 'wp-quicklatex/wp-quicklatex.php' ) ) {
				self::hooks( self::$instance );
			}
		}
		return self::$instance;
	}

	/**
	 * @param MathJax $obj
	 */
	static public function hooks( MathJax $obj ) {
		if ( Book::isBook() ) {
			add_action( 'admin_menu', [ $obj, 'addMenu' ] );
		}
		add_filter(
			'no_texturize_shortcodes',
			function ( $shortcodes ) {
				$shortcodes[] = 'latex';
				$shortcodes[] = 'math';
				return $shortcodes;
			}
		);
		add_shortcode( 'latex', [ $obj, 'latexShortcode' ] );
		add_shortcode( 'math', [ $obj, 'asciiMathShortcode' ] );
		add_filter( 'the_content', [ $obj, 'dollarSignLatexMarkup' ], 9 ); // before wptexturize
		add_filter( 'the_content', [ $obj, 'dollarSignAsciiMathMarkup' ], 9 ); // before wptexturize
		add_action( 'wp_enqueue_scripts', [ $obj, 'addScripts' ] );
		add_action( 'wp_head', [ $obj, 'addHeaders' ] );
		add_action( 'pb_pre_export', [ $obj, 'beforeExport' ] );
	}

	/**
	 * pb_pre_export
	 */
	public function beforeExport() {
		$this->usePbMathJax = true;
	}

	/**
	 * Put MathJax under options meny
	 */
	public function addMenu() {
		add_options_page(
			__( 'MathJax', 'pressbooks' ),
			__( 'MathJax', 'pressbooks' ),
			'edit_posts',
			'pressbooks_mathjax',
			[ $this, 'renderPage' ]
		);
	}

	/**
	 * MathJax admin page
	 */
	public function renderPage() {
		$this->saveOptions();
		$options = get_option( self::OPTION, $this->defaultOptions );

		$this->usePbMathJax = true;
		$test_formula = '\displaystyle P_\nu^{-\mu}(z)=\frac{\left(z^2-1\right)^{\frac{\mu}{2}}}{2^\mu \sqrt{\pi}\Gamma\left(\mu+\frac{1}{2}\right)}\int_{-1}^1\frac{\left(1-t^2\right)^{\mu -\frac{1}{2}}}{\left(z+t\sqrt{z^2-1}\right)^{\mu-\nu}}dt';
		$test_image = $this->latexRender( $test_formula, $this->defaultOptions['fg'], $this->defaultOptions['bg'] );

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
	 * MathJax admin page form submission
	 *
	 * @return bool
	 */
	public function saveOptions() {
		if ( ! isset( $_POST['pb-mathjax-nonce'] ) || ! wp_verify_nonce( $_POST['pb-mathjax-nonce'], 'save' ) ) {
			return false;
		}

		$fg = strtolower( substr( preg_replace( '/[^0-9a-f]/i', '', $_POST['fg'] ), 0, 6 ) );
		$l = strlen( $fg );
		if ( 6 > $l ) {
			$fg .= str_repeat( '0', 6 - $l );
		}

		if ( 'transparent' === trim( $_POST['bg'] ) ) {
			$bg = 'transparent';
		} else {
			$bg = substr( preg_replace( '/[^0-9a-f]/i', '', $_POST['bg'] ), 0, 6 );
			$l = strlen( $bg );
			if ( 6 > $l ) {
				$bg .= str_repeat( '0', 6 - $l );
			}
		}

		$options = [
			'bg' => $bg,
			'fg' => $fg,
		];

		return update_option( self::OPTION, $options );
	}

	/**
	 * LaTeX support.
	 *
	 * Backward compatibility support for "$latex $" shortcodes.
	 *
	 * $latex e^{i \pi} + 1 = 0$ -> [latex]e^{i \pi} + 1 = 0[/latex]
	 * $latex [a, b]$ -> [latex][a, b][/latex]
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function dollarSignLatexMarkup( $content ) {
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
			if ( '' === $element || '<' === $element[0] ) {
				continue;
			}

			if ( false === stripos( $element, '$latex' ) ) {
				continue;
			}

			$element = preg_replace_callback( $regex, [ $this, '_dollarSignLatexSrc' ], $element );
		}

		return implode( '', $textarr );
	}

	/**
	 * Basically, a private method used by `preg_replace_callback` in `$this->dollarSignLatexMarkup`
	 * (Can't be a real private method because `callable $callback`)
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	public function _dollarSignLatexSrc( $matches ) {
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
	 * Render image (SVG/PNG) for exports, simplified shortcode for webbook
	 *
	 * @param string $latex
	 * @param string $fg
	 * @param string $bg
	 * @param int $s
	 *
	 * @return string
	 */
	public function latexRender( $latex, $fg, $bg, $s = 0 ) {
		if ( $this->usePbMathJax ) {
			// TODO: Change to pb-mathjax URL
			$url = '//s0.wp.com/latex.php?latex=' . rawurlencode( $latex ) . '&bg=' . $bg . '&fg=' . $fg . '&s=' . $s;
			if ( ! empty( $_GET['pb-latex-zoom'] ) ) {
				// TODO: Copy pasta from pb-latex does not belong here. Refactor, use SVG
				// Undocumented zoom parameter increases image resolution
				// @see https://github.com/Automattic/jetpack/issues/7392
				$url .= '&zoom=' . (int) $_GET['pb-latex-zoom'];
			}
			$url = esc_url( $url );
			$alt = str_replace( '\\', '&#92;', esc_attr( $latex ) );
			return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="latex mathjax" />';
		} else {
			// Return simplified shortcode. Used as MathJax delimiters.
			// Foreground, background, & size (for single math equations) not supported in webbook
			return "[latex]{$latex}[/latex]";
		}
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

	/**
	 * AsciiMath support.
	 *
	 * Backward compatibility support for "$math $" shortcodes.
	 *
	 * $math e^{i \pi} + 1 = 0$ -> [math]e^{i \pi} + 1 = 0[/math]
	 * $math [a, b]$ -> [math][a, b][/math]
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function dollarSignAsciiMathMarkup( $content ) {
		$textarr = wp_html_split( $content );

		$regex = '%
			\$math(?:=\s*|\s+)
			((?:
				[^$]+ # Not a dollar
			|
				(?<=(?<!\\\\)\\\\)\$ # Dollar preceded by exactly one slash
			)+)
			(?<!\\\\)\$ # Dollar preceded by zero slashes
		%ix';

		foreach ( $textarr as &$element ) {
			if ( '' === $element || '<' === $element[0] ) {
				continue;
			}

			if ( false === stripos( $element, '$math' ) ) {
				continue;
			}

			$element = preg_replace_callback( $regex, [ $this, '_dollarSignAsciiMathSrc' ], $element );
		}

		return implode( '', $textarr );
	}

	/**
	 * Basically, a private method used by `preg_replace_callback` in `$this->dollarSignAsciiMathMarkup`
	 * (Can't be a real private method because `callable $callback`)
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	public function _dollarSignAsciiMathSrc( $matches ) {
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

		return $this->asciiMathRender( $latex, $fg, $bg, $s );
	}

	/**
	 * @param string $asciimath
	 *
	 * @return string
	 */
	public function asciiMathEntityDecode( $asciimath ) {
		return str_replace( [ '&lt;', '&gt;', '&quot;', '&#039;', '&#038;', '&amp;', "\n", "\r" ], [ '<', '>', '"', "'", '&', '&', ' ', ' ' ], $asciimath );
	}

	/**
	 * Render image (SVG/PNG) for exports, simplified shortcode for webbook
	 *
	 * @param string $asciimath
	 * @param string $fg
	 * @param string $bg
	 * @param int $s
	 *
	 * @return string
	 */
	public function asciiMathRender( $asciimath, $fg, $bg, $s = 0 ) {
		if ( $this->usePbMathJax ) {
			// TODO: Change to pb-mathjax URL
			$url = '//s0.wp.com/asciimath.php?asciimath=' . rawurlencode( $asciimath ) . '&bg=' . $bg . '&fg=' . $fg . '&s=' . $s;
			if ( ! empty( $_GET['pb-latex-zoom'] ) ) {
				// TODO: Copy pasta from pb-latex does not belong here. Refactor, use SVG
				// Undocumented zoom parameter increases image resolution
				// @see https://github.com/Automattic/jetpack/issues/7392
				$url .= '&zoom=' . (int) $_GET['pb-latex-zoom'];
			}
			$url = esc_url( $url );
			$alt = str_replace( '\\', '&#92;', esc_attr( $asciimath ) );
			return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="asciimath mathjax" />';
		} else {
			// Return simplified shortcode. Used as MathJax delimiters.
			// Foreground, background, & size (for single math equations) not supported in webbook
			return "[math]{$asciimath}[/math]";
		}
	}

	/**
	 * The shortcode way.
	 *
	 * Example: [math s=4 bg=00f fg=ff0]sum_(i=1)^n i^3=((n(n+1))/2)^2[/math]
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function asciiMathShortcode( $atts, $content = '' ) {

		$atts = shortcode_atts(
			[
				's' => 0,
				'bg' => $this->defaultOptions['bg'],
				'fg' => $this->defaultOptions['fg'],
			], $atts, 'math'
		);

		return $this->asciiMathRender( $this->asciiMathEntityDecode( $content ), $atts['fg'], $atts['bg'], $atts['s'] );
	}

	/**
	 * @return bool
	 */
	public function sectionHasMath() {
		$has_math = false;
		$post = get_post();
		if ( $post ) {
			$id = $post->ID;
			if ( isset( $this->sectionHasMath[ $id ] ) ) {
				$has_math = $this->sectionHasMath[ $id ];
			} else {
				$content = $post->post_content;
				$math_tags = [ '[/latex]', '$latex', '[/math]', '$math' ];
				foreach ( $math_tags as $math_tag ) {
					if ( strpos( $content, $math_tag ) !== false ) {
						$has_math = true;
						break;
					}
				}
				$this->sectionHasMath[ $id ] = $has_math;
			}
		}
		return $has_math;
	}

	/**
	 * @see http://docs.mathjax.org/en/latest/configuration.html
	 */
	public function addScripts() {
		// Only load MathJax if there's math to process (Improves browser performance)
		if ( ! is_admin() && $this->sectionHasMath() ) {
			// File ends in _CHTML, then it is the CommonHTML output processor
			// The "-full" configuration is larger
			wp_enqueue_script( 'pb_mathjax', 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML-full&delayStartupUntil=configured' );
		}
	}

	/**
	 * @see http://docs.mathjax.org/en/latest/configuration.html
	 */
	public function addHeaders() {
		// Only load MathJax if there's math to process (Improves browser performance)
		if ( ! is_admin() && $this->sectionHasMath() ) {
			// Colors
			$options = get_option( self::OPTION, $this->defaultOptions );
			if ( $options['bg'] === 'transparent' ) {
				$colors = "color: '#{$options['fg']}'";
			} else {
				$colors = "'background-color': '#{$options['bg']}', color: '#{$options['fg']}'";
			}
			// Config
			echo "<script type='text/x-mathjax-config'>
			MathJax.Hub.Config( {
				TeX: { extensions: [ 'cancel.js', 'mhchem.js' ] },
				tex2jax: { inlineMath: [ ['[latex]','[/latex]'] ] },
				asciimath2jax: { delimiters: [ ['[math]','[/math]'] ] },
				styles: { '.MathJax_CHTML': { {$colors} } }
			} );
			</script>
			<script type='text/javascript'>
				MathJax.Hub.Configured();
			</script>";
		}
	}

}
