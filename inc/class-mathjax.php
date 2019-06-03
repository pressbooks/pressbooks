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
	 * @var array{pb_mathjax_url: string, bg: string, fg: string, fontsize: string}
	 */
	private $defaultOptions = [
		'pb_mathjax_url' => '',
		'fg' => '000000',
		'bg' => 'transparent',
		'fontsize' => '',
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
		$options = $this->getOptions();

		$this->usePbMathJax = true;
		if ( $this->getOptions()['pb_mathjax_url'] ) {
			$test_formula = '\displaystyle P_\nu^{-\mu}(z)=\frac{\left(z^2-1\right)^{\frac{\mu}{2}}}{2^\mu \sqrt{\pi}\Gamma\left(\mu+\frac{1}{2}\right)}\int_{-1}^1\frac{\left(1-t^2\right)^{\mu -\frac{1}{2}}}{\left(z+t\sqrt{z^2-1}\right)^{\mu-\nu}}dt';
			$test_image = $this->latexRender( $test_formula );
		} else {
			$test_image = '<p>😞😞😞</p>';
		}

		$blade = Container::get( 'Blade' );
		echo $blade->render(
			'admin.mathjax',
			[
				'wp_nonce_field' => wp_nonce_field( 'save', 'pb-mathjax-nonce', true, false ),
				'test_image' => $test_image,
				'pb_mathjax_url' => $options['pb_mathjax_url'],
				'fg' => $options['fg'],
				'bg' => $options['bg'],
				'fontsize' => $options['fontsize'],
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

		// PB MathJax URL
		$pb_mathjax_url = $this->defaultOptions['pb_mathjax_url'];
		if ( filter_var( $_POST['pb_mathjax_url'] ?? '', FILTER_VALIDATE_URL ) ) {
			$pb_mathjax_url = $_POST['pb_mathjax_url'];
		}

		// Text color
		$fg = strtolower( substr( preg_replace( '/[^0-9a-f]/i', '', $_POST['fg'] ?? '' ), 0, 6 ) );
		$l = strlen( $fg );
		if ( 6 > $l ) {
			$fg .= str_repeat( '0', 6 - $l );
		}

		// Backrground color
		if ( 'transparent' === trim( $_POST['bg'] ) ) {
			$bg = 'transparent';
		} else {
			$bg = substr( preg_replace( '/[^0-9a-f]/i', '', $_POST['bg'] ?? '' ), 0, 6 );
			$l = strlen( $bg );
			if ( 6 > $l ) {
				$bg .= str_repeat( '0', 6 - $l );
			}
		}

		// Fontsize
		$fontsize = \Pressbooks\Sanitize\cleanup_css( trim( $_POST['fontsize'] ?? $this->defaultOptions['fontsize'] ) );

		$options = [
			'pb_mathjax_url' => $pb_mathjax_url,
			'bg' => $bg,
			'fg' => $fg,
			'fontsize' => $fontsize,
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
		$latex = $this->latexEntityDecode( $latex );
		// Remove unsupported fg, bg, size attributes
		if ( preg_match( '/.+(&fg=[0-9a-f]{6}).*/i', $latex, $fg_matches ) ) {
			$latex = str_replace( $fg_matches[1], '', $latex );
		}
		if ( preg_match( '/.+(&bg=[0-9a-f]{6}).*/i', $latex, $bg_matches ) ) {
			$latex = str_replace( $bg_matches[1], '', $latex );
		}
		if ( preg_match( '/.+(&s=[0-9-]{1,2}).*/i', $latex, $s_matches ) ) {
			$latex = str_replace( $s_matches[1], '', $latex );
		}
		return $this->latexRender( $latex );
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
	 *
	 * @return string
	 */
	public function latexRender( $latex ) {
		if ( $this->usePbMathJax ) {
			$options = $this->getOptions();
			$url = rtrim( $options['pb_mathjax_url'], '/' );
			$url .= "{}/latex?latex=" . rawurlencode( $latex ) . '&bg=' . $options['bg'] . '&fg=' . $options['fg'] . '&s=' . rawurlencode( $options['fontsize'] );
			// TODO: Copy pasta from pb-latex does not belong here. Refactor, use SVG
			if ( ! empty( $_GET['pb-latex-zoom'] ) ) {
				// Undocumented zoom parameter increases image resolution
				// @see https://github.com/Automattic/jetpack/issues/7392
				$url .= '&zoom=' . (int) $_GET['pb-latex-zoom'];
			}
			$url = esc_url( $url );
			$alt = str_replace( '\\', '&#92;', esc_attr( $latex ) );
			return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="latex mathjax" />';
		} else {
			// Return simplified shortcode. Used as MathJax delimiters.
			return "[latex]{$latex}[/latex]";
		}
	}

	/**
	 * The shortcode way.
	 *
	 * Example: [latex]\LaTeX[/latex]
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function latexShortcode( $atts, $content = '' ) {
		// No attributes are supported by our code
		return $this->latexRender( $this->latexEntityDecode( $content ) );
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
		$latex = $this->latexEntityDecode( $latex );
		// Remove unsupported fg, bg, size attributes
		if ( preg_match( '/.+(&fg=[0-9a-f]{6}).*/i', $latex, $fg_matches ) ) {
			$latex = str_replace( $fg_matches[1], '', $latex );
		}
		if ( preg_match( '/.+(&bg=[0-9a-f]{6}).*/i', $latex, $bg_matches ) ) {
			$latex = str_replace( $bg_matches[1], '', $latex );
		}
		if ( preg_match( '/.+(&s=[0-9-]{1,2}).*/i', $latex, $s_matches ) ) {
			$latex = str_replace( $s_matches[1], '', $latex );
		}
		return $this->asciiMathRender( $latex );
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
	 *
	 * @return string
	 */
	public function asciiMathRender( $asciimath ) {
		if ( $this->usePbMathJax ) {
			$options = $this->getOptions();
			$url = rtrim( $options['pb_mathjax_url'], '/' );
			$url .= "{}/asciimath?asciimath=" . rawurlencode( $asciimath ) . '&bg=' . $options['bg'] . '&fg=' . $options['fg'] . '&s=' . rawurlencode( $options['fontsize'] );
			// TODO: Copy pasta from pb-latex does not belong here. Refactor, use SVG
			if ( ! empty( $_GET['pb-latex-zoom'] ) ) {
				// Undocumented zoom parameter increases image resolution
				// @see https://github.com/Automattic/jetpack/issues/7392
				$url .= '&zoom=' . (int) $_GET['pb-latex-zoom'];
			}
			$url = esc_url( $url );
			$alt = str_replace( '\\', '&#92;', esc_attr( $asciimath ) );
			return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="asciimath mathjax" />';
		} else {
			// Return simplified shortcode. Used as MathJax delimiters.
			return "[math]{$asciimath}[/math]";
		}
	}

	/**
	 * The shortcode way.
	 *
	 * Example: [math]\AsciiMath[/math]
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function asciiMathShortcode( $atts, $content = '' ) {
		// No attributes are supported by our code
		return $this->asciiMathRender( $this->asciiMathEntityDecode( $content ) );
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
			// If the file ends in _CHTML, then it is the CommonHTML output processor
			// The "-full" configuration is substantially larger (on the order of 70KB more)
			wp_enqueue_script( 'pb_mathjax', 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML-full&delayStartupUntil=configured' );
		}
	}

	/**
	 * @see http://docs.mathjax.org/en/latest/configuration.html
	 */
	public function addHeaders() {
		// Only load MathJax if there's math to process (Improves browser performance)
		if ( ! is_admin() && $this->sectionHasMath() ) {
			// Font colors & size
			$options = $this->getOptions();
			if ( $options['bg'] === 'transparent' ) {
				// Omit background from config
				$css = "color: '#{$options['fg']}'";
			} else {
				$css = "'background-color': '#{$options['bg']}', color: '#{$options['fg']}'";
			}
			if ( ! empty( $options['fontsize'] ) ) {
				$css .= ", 'font-size': '{$options['fontsize']} !important'";
			}
			// Config
			echo "<script type='text/x-mathjax-config'>
			MathJax.Hub.Config( {
				TeX: { extensions: [ 'cancel.js', 'mhchem.js' ] },
				tex2jax: { inlineMath: [ ['[latex]','[/latex]'] ] },
				asciimath2jax: { delimiters: [ ['[math]','[/math]'] ] },
				styles: { '.MathJax_CHTML': { {$css} } }
			} );
			</script>
			<script type='text/javascript'>
				MathJax.Hub.Configured();
			</script>";
		}
	}


	/**
	 * @return array{pb_mathjax_url: string, bg: string, fg: string, fontsize: string}
	 * @see \Pressbooks\MathJax::$defaultOptions
	 */
	public function getOptions() {
		$options = get_option( self::OPTION, [] );
		$pb_mathjax_url = trim( $options['pb_mathjax_url'] ?? $this->defaultOptions['pb_mathjax_url'] );
		$bg = trim( $options['bg'] ?? $this->defaultOptions['bg'] );
		$fg = trim( $options['fg'] ?? $this->defaultOptions['fg'] );
		$fontsize = trim( $options['fontsize'] ?? $this->defaultOptions['fontsize'] );
		return [
			'pb_mathjax_url' => $pb_mathjax_url,
			'bg' => $bg,
			'fg' => $fg,
			'fontsize' => $fontsize,
		];
	}

}
