<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

/**
 * Heavily inspired by JetPack Latex and MathJax-LaTeX
 * @see https://github.com/Automattic/jetpack/blob/master/modules/latex.php
 * @see https://github.com/phillord/mathjax-latex
 */
class MathJax {

	const OPTION = 'pb_mathjax';

	/**
	 * @var MathJax
	 */
	private static $instance = null;

	/**
	 * @var array{fg: string, font: string}
	 */
	private $defaultOptions = [
		'fg' => '000000',
		'font' => 'TeX',
	];

	/**
	 * @see http://docs.mathjax.org/en/latest/options/output-processors/SVG.html#configure-svg
	 *
	 * @var array
	 */
	private $possibleFonts = [
		'TeX',
		'STIX-Web',
		'Asana-Math',
		'Neo-Euler',
		'Gyre-Pagella',
		'Gyre-Termes',
		'Latin-Modern',
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
	 * When using PB MathJax, generate a SVG instead of a PNG
	 *
	 * @var bool
	 */
	public $useSVG = false;

	/**
	 * @return MathJax
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			/**
			 * Disable PB MathJax
			 *
			 * @since 5.9.0
			 * @param bool $var
			 * @return bool
			 */
			$disabled =
				apply_filters( 'pb_mathjax_disabled', false ) ||
				is_plugin_active( 'wp-quicklatex/wp-quicklatex.php' ) ||
				is_plugin_active_for_network( 'wp-quicklatex/wp-quicklatex.php' );

			if ( ! $disabled ) {
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

		// LaTeX
		add_shortcode( 'latex', [ $obj, 'latexShortcode' ] );
		add_filter( 'the_content', [ $obj, 'dollarSignLatexMarkup' ], 9 ); // before wptexturize

		// AsciiMath
		add_shortcode( 'asciimath', [ $obj, 'asciiMathShortcode' ] );
		add_filter( 'the_content', [ $obj, 'dollarSignAsciiMathMarkup' ], 9 ); // before wptexturize

		// MathML
		$obj->allowMathmlTags();
		add_filter( 'tiny_mce_before_init', [ $obj, 'allowMathmlTagsInTinyMce' ], 25 );
		add_filter( 'the_content', [ $obj, 'filterLineBreakTagsInMthml' ], 13 ); // after wpautop

		// SVG
		add_filter( 'the_content', [ $obj, 'filterLineBreakTagsInSvg' ], 13 ); // after wpautop

		// Rendering stuff
		add_filter(
			'no_texturize_shortcodes',
			function ( $shortcodes ) {
				$shortcodes[] = 'latex';
				$shortcodes[] = 'asciimath';
				return $shortcodes;
			}
		);
		add_action( 'wp_enqueue_scripts', [ $obj, 'addScripts' ] );
		add_action( 'wp_head', [ $obj, 'addHeaders' ] );
		add_action( 'pb_pre_export', [ $obj, 'beforeExport' ] );
	}

	/**
	 * MathJax constructor.
	 */
	public function __construct() {
		if ( ! defined( 'PB_MATHJAX_URL' ) ) {
			define( 'PB_MATHJAX_URL', 'http://kizu514.com:3000' ); // TODO: For textopress testing only! Change back to `false` before release
		}
	}

	/**
	 * pb_pre_export
	 */
	public function beforeExport() {
		$this->usePbMathJax = true;
		add_filter( 'the_content', [ $this, 'replaceMathML' ], 999 );
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

		if ( PB_MATHJAX_URL ) {
			$this->usePbMathJax = true;
			$this->useSVG = true;
			$test_formula = '\displaystyle P_\nu^{-\mu}(z)=\frac{\left(z^2-1\right)^{\frac{\mu}{2}}}{2^\mu \sqrt{\pi}\Gamma\left(\mu+\frac{1}{2}\right)}\int_{-1}^1\frac{\left(1-t^2\right)^{\mu -\frac{1}{2}}}{\left(z+t\sqrt{z^2-1}\right)^{\mu-\nu}}dt';
			$test_image = $this->latexRender( $test_formula );
		} else {
			$test_image = '<p class="latex mathjax">' . __( '<code>PB_MATHJAX_URL</code> not configured.', 'pressbooks' ) . '</p>';
		}

		$blade = Container::get( 'Blade' );
		echo $blade->render(
			'admin.mathjax',
			[
				'wp_nonce_field' => wp_nonce_field( 'save', 'pb-mathjax-nonce', true, false ),
				'test_image' => $test_image,
				'fg' => $options['fg'],
				'font' => $options['font'],
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

		// Text color
		$fg = strtolower( substr( preg_replace( '/[^0-9a-f]/i', '', $_POST['fg'] ?? '' ), 0, 6 ) );
		$l = strlen( $fg );
		if ( 6 > $l ) {
			$fg .= str_repeat( '0', 6 - $l );
		}

		// Font
		if ( in_array( $_POST['font'], $this->possibleFonts, true ) ) {
			$font = $_POST['font'];
		} else {
			$font = $this->possibleFonts[0];
		}

		$options = [
			'font' => $font,
			'fg' => $fg,
		];

		return update_option( self::OPTION, $options );
	}

	/**
	 * Does post_content have maths?
	 *
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
				$math_tags = [ '[/latex]', '$latex', '[/asciimath]', '$asciimath', '</math>' ];
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
			$css = "color: '#{$options['fg']}'";
			// TODO: CommonHTML currently only supports MathJax’s default TeX fonts.
			// Config
			echo "<script type='text/x-mathjax-config'>
			MathJax.Hub.Config( {	
				extensions: [ 'Safe.js' ],	 	
				MathML: { extensions: [ 'content-mathml.js' ] },
				TeX: { extensions: [ 'autoload-all.js' ] },
				tex2jax: { inlineMath: [ ['[latex]','[/latex]'] ] },
				asciimath2jax: { delimiters: [ ['[asciimath]','[/asciimath]'] ] },
				styles: { '.MathJax_CHTML': { {$css} } },
			} );
			</script>
			<script type='text/javascript'>
				MathJax.Hub.Configured();
			</script>";
		}
	}


	/**
	 * @return array{fg: string}
	 * @see \Pressbooks\MathJax::$defaultOptions
	 */
	public function getOptions() {
		$options = get_option( self::OPTION, [] );
		$fg = trim( $options['fg'] ?? $this->defaultOptions['fg'] );
		$font = trim( $options['font'] ?? $this->defaultOptions['font'] );
		return [
			'fg' => $fg,
			'font' => $font,
		];
	}

	// ------------------------------------------------------------------------
	// LaTeX
	// ------------------------------------------------------------------------

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
		$latex = trim( $latex );
		/**
		 * Use PB-MathJax micro-service
		 *
		 * @param bool $var
		 *
		 * @return bool
		 * @since 5.9.0
		 */
		if ( apply_filters( 'pb_mathjax_use', $this->usePbMathJax ) && PB_MATHJAX_URL ) {
			$options = $this->getOptions();
			$url = rtrim( PB_MATHJAX_URL, '/' );
			$url .= '/latex?latex=' . rawurlencode( $latex ) . '&fg=' . $options['fg'] . '&font=' . $options['font'];
			/**
			 * Return a SVG instead of a PNG
			 *
			 * @param bool $var
			 *
			 * @return bool
			 * @since 5.9.0
			 */
			if ( apply_filters( 'pb_mathjax_use_svg', $this->useSVG ) ) {
				$url .= '&svg=1';
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

	// ------------------------------------------------------------------------
	// AsciiMath
	// ------------------------------------------------------------------------

	/**
	 * AsciiMath support.
	 *
	 * Backward compatibility support for "$asciimath $" shortcodes.
	 *
	 * $asciimath e^{i \pi} + 1 = 0$ -> [asciimath]e^{i \pi} + 1 = 0[/asciimath]
	 * $asciimath [a, b]$ -> [asciimath][a, b][/asciimath]
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function dollarSignAsciiMathMarkup( $content ) {
		$textarr = wp_html_split( $content );

		$regex = '%
			\$asciimath(?:=\s*|\s+)
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

			if ( false === stripos( $element, '$asciimath' ) ) {
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
		$asciimath = trim( $asciimath );
		/**
		 * Use PB-MathJax micro-service
		 *
		 * @param bool $var
		 *
		 * @return bool
		 * @since 5.9.0
		 */
		if ( apply_filters( 'pb_mathjax_use', $this->usePbMathJax ) && PB_MATHJAX_URL ) {
			$options = $this->getOptions();
			$url = rtrim( PB_MATHJAX_URL, '/' );
			$url .= '/asciimath?asciimath=' . rawurlencode( $asciimath ) . '&fg=' . $options['fg'] . '&font=' . $options['font'];
			/**
			 * Return a SVG instead of a PNG
			 *
			 * @param bool $var
			 *
			 * @return bool
			 * @since 5.9.0
			 */
			if ( apply_filters( 'pb_mathjax_use_svg', $this->useSVG ) ) {
				$url .= '&svg=1';
			}
			$url = esc_url( $url );
			$alt = str_replace( '\\', '&#92;', esc_attr( $asciimath ) );
			return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="asciimath mathjax" />';
		} else {
			// Return simplified shortcode. Used as MathJax delimiters.
			return "[asciimath]{$asciimath}[/asciimath]";
		}
	}

	/**
	 * The shortcode way.
	 *
	 * Example: [asciimath]\AsciiMath[/asciimath]
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

	// ------------------------------------------------------------------------
	// MathML
	// ------------------------------------------------------------------------

	/**
	 * @return array
	 */
	public function mathmlTags() {
		$mathml_tags = [
			// Presentation Markup (https://developer.mozilla.org/en-US/docs/Web/MathML/Element)
			'annotation'     => [ 'definitionURL', 'encoding', 'cd', 'name', 'src' ],
			'annotation-xml' => [ 'definitionURL', 'encoding', 'cd', 'name', 'src' ],
			'maction'        => [ 'actiontype', 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor', 'selection' ],
			'maligngroup'    => [],
			'malignmark'     => [],
			'math'           => [ 'class', 'id', 'style', 'dir', 'href', 'mathbackground', 'mathcolor', 'display', 'overflow', 'xmlns' ],
			'menclose'       => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor', 'notation' ],
			'merror'         => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor' ],
			'mfenced'        => [ 'class', 'id', 'style', 'close', 'href', 'mathbackground', 'mathcolor', 'open', 'separators' ],
			'mfrac'          => [ 'bevelled', 'class', 'id', 'style', 'denomalign', 'href', 'linethickness', 'mathbackground', 'mathcolor', 'numalign' ],
			'mglyph'         => [ 'alt', 'class', 'id', 'style', 'height', 'href', 'mathbackground', 'src', 'valign', 'width' ],
			'mi'             => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant' ],
			'mlabeledtr'     => [ 'class', 'id', 'style', 'columnalign', 'groupalign', 'href', 'mathbackground', 'mathcolor', 'rowalign' ],
			'mlongdiv'       => [],
			'mmultiscripts'  => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor', 'subscriptshift', 'superscriptshift' ],
			'mn'             => [ 'class', 'id', 'style', 'dir', 'href', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant' ],
			'mo'             => [ 'accent', 'class', 'id', 'style', 'dir', 'fence', 'form', 'href', 'largeop', 'lspace', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant', 'maxsize', 'minsize', 'moveablelimits', 'rspace', 'separator', 'stretchy', 'symmetric' ],
			'mover'          => [ 'accent', 'align', 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor' ],
			'mpadded'        => [ 'class', 'id', 'style', 'depth', 'height', 'href', 'lspace', 'mathbackground', 'mathcolor', 'voffset', 'width' ],
			'mphantom'       => [ 'class', 'id', 'style', 'mathbackground' ],
			'mroot'          => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor' ],
			'mrow'           => [ 'class', 'id', 'style', 'dir', 'href', 'mathbackground', 'mathcolor' ],
			'ms'             => [ 'class', 'id', 'style', 'dir', 'lquote', 'href', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant', 'rquote' ],
			'mscarries'      => [],
			'mscarry'        => [],
			'msgroup'        => [],
			'msline'         => [],
			'mspace'         => [ 'class', 'id', 'style', 'depth', 'height', 'linebreak', 'mathbackground', 'width' ],
			'msqrt'          => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor' ],
			'msrow'          => [],
			'mstack'         => [],
			'mstyle'         => [ 'dir', 'decimalpoint', 'displaystyle', 'infixlinebreakstyle', 'scriptlevel', 'scriptminsize', 'scriptsizemultiplier' ],
			'msub'           => [ 'class', 'id', 'style', 'mathbackground', 'mathcolor', 'subscriptshift' ],
			'msubsup'        => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor', 'subscriptshift', 'superscriptshift' ],
			'msup'           => [ 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor', 'superscriptshift' ],
			'mtable'         => [ 'class', 'id', 'style', 'align', 'alignmentscope', 'columnalign', 'columnlines', 'columnspacing', 'columnwidth', 'displaystyle', 'equalcolumns', 'equalrows', 'frame', 'framespacing', 'groupalign', 'href', 'mathbackground', 'mathcolor', 'minlabelspacing', 'rowalign', 'rowlines', 'rowspacing', 'side', 'width' ],
			'mtd'            => [ 'class', 'id', 'style', 'columnalign', 'columnspan', 'groupalign', 'href', 'mathbackground', 'mathcolor', 'rowalign', 'rowspan' ],
			'mtext'          => [ 'class', 'id', 'style', 'dir', 'href', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant' ],
			'mtr'            => [ 'class', 'id', 'style', 'columnalign', 'groupalign', 'href', 'mathbackground', 'mathcolor', 'rowalign' ],
			'munder'         => [ 'accentunder', 'align', 'class', 'id', 'style', 'mathbackground', 'mathcolor' ],
			'munderover'     => [ 'accent', 'accentunder', 'align', 'class', 'id', 'style', 'href', 'mathbackground', 'mathcolor' ],
			'semantics'      => [ 'definitionURL', 'encoding', 'cd', 'name', 'src' ],
			// Content Markup (https://www.w3.org/TR/MathML3/chapter4.html)
			'abs' => [],
			'and' => [],
			'apply' => [],
			'approx' => [],
			'arccos' => [],
			'arccosh' => [],
			'arccot' => [],
			'arccoth' => [],
			'arccsc' => [],
			'arccsch' => [],
			'arcsec' => [],
			'arcsech' => [],
			'arcsin' => [],
			'arcsinh' => [],
			'arctan' => [],
			'arctanh' => [],
			'arg' => [],
			'bvar' => [],
			'card' => [],
			'cartesianproduct' => [],
			'ceiling' => [],
			'ci' => [ 'type' ],
			'cn' => [ 'type', 'base' ],
			'codomain' => [],
			'complexes' => [],
			'compose' => [],
			'condition' => [],
			'conjugate' => [],
			'cos' => [],
			'cosh' => [],
			'cot' => [],
			'coth' => [],
			'csc' => [],
			'csch' => [],
			'csymbol' => [ 'type', 'cd' ],
			'curl' => [],
			'degree' => [],
			'determinant' => [],
			'diff' => [],
			'divergence' => [],
			'divide' => [],
			'domain' => [],
			'domainofapplication' => [],
			'emptyset' => [],
			'eq' => [],
			'equivalent' => [],
			'eulergamma' => [],
			'exists' => [],
			'exp' => [],
			'exponentiale' => [],
			'factorial' => [],
			'factorof' => [],
			'false' => [],
			'floor' => [],
			'forall' => [],
			'gcd' => [],
			'geq' => [],
			'grad' => [],
			'gt' => [],
			'ident' => [],
			'image' => [],
			'imaginary' => [],
			'imaginaryi' => [],
			'implies' => [],
			'in' => [],
			'infinity' => [],
			'int' => [],
			'integers' => [],
			'intersect' => [],
			'interval' => [ 'closure' ],
			'inverse' => [],
			'lambda' => [],
			'laplacian' => [],
			'lcm' => [],
			'leq' => [],
			'limit' => [],
			'list' => [],
			'ln' => [],
			'log' => [],
			'lowlimit' => [],
			'lt' => [],
			'matrix' => [],
			'matrixrow' => [],
			'max' => [],
			'mean' => [],
			'median' => [],
			'min' => [],
			'minus' => [],
			'mode' => [],
			'moment' => [],
			'momentabout' => [],
			'naturalnumbers' => [],
			'neq' => [],
			'not' => [],
			'notanumber' => [],
			'notin' => [],
			'notprsubset' => [],
			'notsubset' => [],
			'or' => [],
			'otherwise' => [],
			'outerproduct' => [],
			'partialdiff' => [],
			'pi' => [],
			'piece' => [],
			'piecewise' => [],
			'plus' => [],
			'power' => [],
			'primes' => [],
			'product' => [],
			'prsubset' => [],
			'quotient' => [],
			'rationals' => [],
			'real' => [],
			'reals' => [],
			'rem' => [],
			'root' => [],
			'scalarproduct' => [],
			'sdev' => [],
			'sec' => [],
			'sech' => [],
			'selector' => [],
			'sep' => [],
			'set' => [],
			'setdiff' => [],
			'sin' => [],
			'sinh' => [],
			'subset' => [],
			'sum' => [],
			'tan' => [],
			'tanh' => [],
			'tendsto' => [],
			'times' => [],
			'transpose' => [],
			'true' => [],
			'union' => [],
			'uplimit' => [],
			'variance' => [],
			'vector' => [],
			'vectorproduct' => [],
			'xor' => [],
		];
		return $mathml_tags;
	}


	/**
	 * Allow MathML tags within WordPress
	 * http://vip.wordpress.com/documentation/register-additional-html-attributes-for-tinymce-and-wp-kses/
	 * https://developer.mozilla.org/en-US/docs/Web/MathML/Element
	 */
	public function allowMathmlTags() {
		global $allowedposttags;
		foreach ( $this->mathmlTags() as $tag => $attributes ) {
			$allowedposttags[ $tag ] = [];
			foreach ( $attributes as $a ) {
				$allowedposttags[ $tag ][ $a ] = true;
			}
		}
	}

	/**
	 * Ensure that the MathML tags will not be removed by the TinyMCE editor
	 *
	 * TODO: Switching between Text & Visual editors mangles MathML with spaces in it
	 *
	 * @param array options
	 *
	 * @return array
	 */
	public function allowMathmlTagsInTinyMce( $options ) {
		$extended_tags = [];
		foreach ( $this->mathmlTags() as $tag => $attributes ) {
			if ( ! empty( $attributes ) ) {
				$tag = $tag . '[' . implode( '|', $attributes ) . ']';
			}
			$extended_tags[] = $tag;
		}
		if ( ! isset( $options['extended_valid_elements'] ) ) {
			$options['extended_valid_elements'] = '';
		}
		$options['extended_valid_elements'] .= ',' . implode( ',', $extended_tags );
		$options['extended_valid_elements'] = trim( $options['extended_valid_elements'], ',' );

		return $options;
	}

	/**
	 * Removes the <br /> and <p> tags inside math tags
	 *
	 * @param $content
	 * @return string without <br /> tags
	 */
	public function filterLineBreakTagsInMthml( $content ) {
		$filtered_content = preg_replace_callback(
			'/(<math.*>.*<\/math>)/isU',
			function( $matches ) {
				return str_replace( [ '<br/>', '<br />', '<br>', '<p>', '</p>' ], '', $matches[0] );
			},
			$content
		);
		return null === $filtered_content ? $content : $filtered_content;
	}

	/**
	 * Removes the <br /> and <p> tags inside math tags
	 *
	 * @param $content
	 * @return string without <br /> tags
	 */
	public function filterLineBreakTagsInSvg( $content ) {
		$filtered_content = preg_replace_callback(
			'/(<svg.*>.*<\/svg>)/isU',
			function( $matches ) {
				return str_replace( [ '<br/>', '<br />', '<br>', '<p>', '</p>' ], '', $matches[0] );
			},
			$content
		);
		return null === $filtered_content ? $content : $filtered_content;
	}

	/**
	 * Replace <math> with an image
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function replaceMathML( $content ) {

		// Check for <math> HTML code, bail if there isn't any
		if ( stripos( $content, '<math' ) === false ) {
			return $content;
		}

		$filtered_content = preg_replace_callback(
			'/(<math.*>.*<\/math>)/isU',
			function ( $matches ) {
				$mathml = trim( $matches[0] );
				/**
				 * Use PB-MathJax micro-service
				 *
				 * @param bool $var
				 *
				 * @return bool
				 * @since 5.9.0
				 */
				if ( apply_filters( 'pb_mathjax_use', $this->usePbMathJax ) && PB_MATHJAX_URL ) {
					$options = $this->getOptions();
					$url = rtrim( PB_MATHJAX_URL, '/' );
					$url .= '/mathml?mathml=' . rawurlencode( $mathml ) . '&fg=' . $options['fg'] . '&font=' . $options['font'];
					/**
					 * Return a SVG instead of a PNG
					 *
					 * @param bool $var
					 *
					 * @return bool
					 * @since 5.9.0
					 */
					if ( apply_filters( 'pb_mathjax_use_svg', $this->useSVG ) ) {
						$url .= '&svg=1';
					}
					$url = esc_url( $url );
					$alt = str_replace( "\n", '', normalize_whitespace( strip_tags( $mathml ) ) );
					$alt = str_replace( '\\', '&#92;', esc_attr( $alt ) );
					return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="mathml mathjax" />';
				}
				return null;
			},
			$content
		);

		return null === $filtered_content ? $content : $filtered_content;

	}

}
