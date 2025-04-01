<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated


namespace Pressbooks;

use Pressbooks\Shortcodes\Glossary\Glossary;

/**
 * Heavily inspired by JetPack Latex and MathJax-LaTeX
 *
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
	 * @var array{fg: string}
	 */
	private $defaultOptions = [
		'fg' => '000000',
	];

	/**
	 * Webbook Section Cache
	 *
	 * @var array
	 */
	private $sectionHasMath = [];

	/**
	 * Use PB MathJax Node.js service to render SVG/PNG image?
	 *
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
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			/**
			 * Disable PB MathJax
			 *
			 * @since  5.9.0
			 * @param  bool $var
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
	public static function hooks( MathJax $obj ) {
		if ( Book::isBook() ) {
			add_action( 'admin_menu', [ $obj, 'addMenu' ] );
		}

		// AsciiMath
		add_shortcode( 'asciimath', [ $obj, 'asciiMathShortcode' ] );
		add_filter( 'the_content', [ $obj, 'parseAsciiMathMarkup' ], 9 ); // before wptexturize

		// old LaTeX support
		add_shortcode( 'latex', [ $obj, 'latexShortcode' ] );
		add_filter( 'the_content', [ $obj, 'parseLatexMarkup' ], 9 ); // before wptexturize

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
		add_filter('pb_pdf_css_override', [ $obj, 'displayMathHandler' ] );
		add_filter('pb_epub_css_override', [ $obj, 'displayMathHandler' ] );
		add_action( 'pb_pre_export', [ $obj, 'beforeExport' ] );
	}

	/**
	 * MathJax constructor.
	 */
	public function __construct() {
		if ( ! defined( 'PB_MATHJAX_URL' ) ) {
			define( 'PB_MATHJAX_URL', '' );
		}
	}

	/**
	 * pb_pre_export
	 */
	public function beforeExport() {
		$this->usePbMathJax = true;
		add_filter( 'the_content', [ $this, 'replaceMathML' ], 999 );
		// This should run before wpautop and wptexturize because formulas can contain line breaks
		add_filter( 'the_content', [ $this, 'replaceLatexDelimitersOnExports' ], 8 );
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
			$test_image = $this->renderFormula( $test_formula, 'latex' );
		} else {
			$test_image = '<p class="latex mathjax">' . __( '<code>PB_MATHJAX_URL</code> is not configured.', 'pressbooks' ) . '</p>';
		}

		$blade = Container::get( 'Blade' );
		echo $blade->render(
			'admin.mathjax',
			[
				'wp_nonce_field' => wp_nonce_field( 'save', 'pb-mathjax-nonce', true, false ),
				'test_image' => $test_image,
				'fg' => $options['fg'],
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

		$options = [
			'fg' => $fg,
		];

		if ( update_option( self::OPTION, $options ) ) {
			Book::deleteBookObjectCache();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if the current post has MathJax-related content.
	 * More efficient than looping over multiple strings.
	 *
	 * @return bool
	 */
	public function sectionHasMath(): bool {
		$post = get_post();
		if ( ! $post ) {
			return false;
		}

		if ( Glossary::isGlossaryPost( $post ) ) {
			return true;
		}

		$id = $post->ID;
		if ( isset( $this->sectionHasMath[ $id ] ) ) {
			return $this->sectionHasMath[ $id ];
		}

		$content = $post->post_content;

		// Check for shortcodes (better than searching manually)
		if ( has_shortcode( $content, 'latex' ) || has_shortcode( $content, 'asciimath' ) ) {
			$this->sectionHasMath[ $id ] = true;
			return true;
		}

		// Check for specific math tags
		$math_tags = [ '[table id=', '[/latex]', '$latex', '[/asciimath]', '$asciimath', '</math>' ];
		foreach ( $math_tags as $math_tag ) {
			if ( str_contains( $content, $math_tag ) ) {
				$this->sectionHasMath[ $id ] = true;
				return true;
			}
		}

		// Use regex to check for LaTeX delimiters
		$has_math = (bool) preg_match( '/(?:\\\\\[|\\\\\]|\\\\\(|\\\\\)|\$\$|\$latex\s+[^$]+\$|\$\s+[^$]+\s+\$)/', $content );
		$this->sectionHasMath[ $id ] = $has_math;
		return $has_math;
	}

	/**
	 * @see https://docs.mathjax.org/en/latest/options/index.html
	 */
	public function addScripts() {
		// Only load MathJax if there's math to process (Improves browser performance)
		if ( ! is_admin() && $this->sectionHasMath() ) {
			// If the file ends in _CHTML, then it is the CommonHTML output processor
			wp_enqueue_script( 'pb_mathjax', 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js', [], null, true );
		}
	}

	/**
	 * Only load MathJax if there's math to process (Improves browser performance)
	 * @see https://docs.mathjax.org/en/latest/options/index.html
	 */
	public function addHeaders() {
		if ( ! is_admin() && $this->sectionHasMath() ) {
			// Color
			$options = $this->getOptions();
			echo "<script>
window.MathJax = {
	versionWarnings: false,
    loader: {
        load: [
			'input/asciimath',
			'output/chtml',
            '[tex]/ams',
            '[tex]/bbox',
            '[tex]/boldsymbol',
            '[tex]/braket',
            '[tex]/cancel',
            '[tex]/color',
            '[tex]/enclose',
            '[tex]/gensymb',
            '[tex]/mathtools',
            '[tex]/mhchem',
            '[tex]/textmacros',
            '[tex]/newcommand',
            '[tex]/noerrors',
            '[tex]/physics',
            '[tex]/unicode'
        ]
    },
	asciimath: {
			delimiters: [['`','`'],['[asciimath]','[/asciimath]']]
		},
    tex: {
        inlineMath: [['\\\\(', '\\\\)'], ['[latex]','[/latex]']],
        displayMath: [['$$', '$$'], ['\\\\[', '\\\\]']],
        packages: {
            '[+]': [
                'ams',        // AMS math features
                'bbox',       // Boxed expressions
                'boldsymbol', // Bold symbols
                'braket',     // Quantum mechanics notation
                'cancel',     // Strike-through notation
                'color',      // Colored text
                'enclose',    // Provide notations like rounded boxes and underlining
                'gensymb',    // General symbols (degrees, angles, etc.)
                'mathtools',  // Extended math tools
                'mhchem',     // Chemistry formulas
                'textmacros', // Text formatting macros
                'newcommand', // Define custom LaTeX commands useful for macros
                'noerrors',   // Suppresses errors
                'unicode'     // Unicode math symbols
            ]
        },
        tags: 'ams',
            formatError: function (message) {
				return '\\\\color{red}{\\\\text{MathJax error: ' + message + '}}';
			}
    },
    svg: {
        fontCache: 'global'
    }
};
</script>";
			echo <<<STYLES
<style>
.MathJax {
    color: #{$options['fg']} !important;
}
</style>
STYLES;

		}
	}

	/**
	 * @return array{fg: string}
	 * @see    MathJax
	 */
	public function getOptions() {
		$options = get_option( self::OPTION, [] );
		$fg = trim( $options['fg'] ?? $this->defaultOptions['fg'] );
		return [
			'fg' => $fg,
		];
	}

	/**
	 * Render image (SVG/PNG) for exports, simplified shortcode for webbook
	 *
	 * @param string $formula
	 * @param string $type
	 *
	 * @return string
	 */
	public function renderFormula( $formula, $type = 'latex' ) {
		$formula = trim( $formula );
		$formula = $this->latexEntityDecode( $formula );

		if ( apply_filters( 'pb_mathjax_use', $this->usePbMathJax ) && PB_MATHJAX_URL ) {
			$options = $this->getOptions();
			$url = rtrim( PB_MATHJAX_URL, '/' );
			// Safe base64 encoding for URL
			$url .= '/' . $type . '?' . $type . '=' . str_replace( '=', '', strtr( base64_encode( $formula ), '+/', '-_' ) ) . '&fg=' . $options['fg'];

			if ( apply_filters( 'pb_mathjax_use_svg', $this->useSVG ) ) {
				$url .= '&svg=1';
			}

			// Base64 encode parameter for better PB MathJax parsing
			$url .= '&isBase64=1';

			$alt = str_replace( '\\', '&#92;', esc_attr( $formula ) );
			return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="' . $type . ' mathjax" />';
		} else {
			// Return simplified shortcode. Used as MathJax delimiters.
			return "[{$type}]{$formula}[/$type]";
		}
	}

	// ------------------------------------------------------------------------
	// LaTeX
	// ------------------------------------------------------------------------

	/**
	 * Old LaTeX support.
	 * This function will search-replace old LaTeX shortcodes with new ones.
	 *
	 * Supports for old-style "$latex $" shortcodes.
	 * - $latex e^{i \pi} + 1 = 0$ -> [latex]e^{i \pi} + 1 = 0[/latex]
	 *
	 * @param  string $content
	 * @return string
	 */
	public function parseLatexMarkup( $content ) {

		$patterns = [
			'%\$latex(?:=\s*|\s+)((?:[^$]+|(?<=(?<!\\\\)\\\\)\$)+)(?<!\\\\)\$%ix',
		];

		foreach ( $patterns as $pattern ) {
			$content = preg_replace_callback($pattern, function ( $matches ) {
				return $this->renderFormula( $matches[1], 'latex' );
			}, $content);
		}
		return $content;

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
	 * The shortcode way.
	 *
	 * Example: [latex]\LaTeX[/latex]
	 *
	 * @param array  $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public function latexShortcode( $atts, $content = '' ) {
		return $this->renderFormula( $content, 'latex' );
	}

	public function replaceLatexDelimitersOnExports( $content ) {
		$patterns = [
			// Match block LaTeX equations: \[ ... \]
			'%\\\\\[(.*?)\\\\\]%s', // \[ ... \]
			// Match inline LaTeX equations: \( ... \)
			'%\\\\\((.*?)\\\\\)%s',
			// Match $$ ... $$ LaTeX equations
			'%\$\$(.*?)\$\$%s', // $$ ... $$
		];
		foreach ( $patterns as $index => $pattern ) {
			$content = preg_replace_callback($pattern, function ( $matches ) use ($index) {
				$rendered = $this->renderFormula( $matches[1], 'latex' );
				// Wrap in div if it's display math (\[...\]) or $$...$$
				if ($index === 0 || $index === 2) {
					return '<div class="display-math">' . $rendered . '</div>';
				}
				return $rendered;
			}, $content);
		}
		return $content;
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
	public function parseAsciiMathMarkup( $content ) {

		$pattern = '/\$asciimath\s+([^$]+?)\s*\$/i';

		return preg_replace_callback( $pattern, function ( $matches ) {
			return $this->renderFormula( $matches[1], 'asciimath' );
		}, $content );

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
	 * The shortcode way.
	 *
	 * Example: [asciimath]\AsciiMath[/asciimath]
	 *
	 * @param array  $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function asciiMathShortcode( $atts, $content = '' ) {
		// No attributes are supported by our code
		return $this->renderFormula( $this->asciiMathEntityDecode( $content ), 'asciimath' );
	}

	// ------------------------------------------------------------------------
	// MathML
	// ------------------------------------------------------------------------

	/**
	 * @return array
	 */
	public function mathmlTags() {
		// Elements Common to Presentation and Content MathML
		// https://www.w3.org/TR/MathML3/appendixa.html#parsing.rnc.common
		$common_att = [ 'id', 'xref', 'class', 'style', 'href' ];
		$common_pres_att = [ 'mathcolor', 'mathbackground' ];
		$common_token_att = [ 'mathvariant', 'mathsize', 'dir' ];
		$def_enc_att = [ 'encoding', 'definitionURL' ];

		$mathml_tags = [
			// Presentation Markup (https://www.w3.org/TR/MathML3/chapter3.html)
			'annotation'     => array_merge( [ 'cd', 'name', 'src' ], $def_enc_att ),
			'annotation-xml' => array_merge( [ 'cd', 'name', 'src' ], $def_enc_att ),
			'maction'        => array_merge( [ 'actiontype', 'selection' ], $common_pres_att, $common_att ),
			'maligngroup'    => array_merge( [ 'groupalign' ], $common_pres_att, $common_att ),
			'malignmark'     => array_merge( [ 'edge' ], $common_pres_att, $common_att ),
			'math'           => array_merge( [ 'display', 'overflow', 'xmlns' ], $common_pres_att, $common_att ),
			'menclose'       => array_merge( [ 'notation' ], $common_pres_att, $common_att ),
			'merror'         => array_merge( $common_pres_att, $common_att ),
			'mfenced'        => array_merge( [ 'open', 'close', 'separators' ], $common_pres_att, $common_att ),
			'mfrac'          => array_merge( [ 'linethickness', 'numalign', 'denomalign', 'bevelled' ], $common_pres_att, $common_att ),
			'mglyph'         => array_merge( [ 'src', 'width', 'height', 'valign', 'alt', 'mathbackround' ], $common_att ),
			'mi'             => array_merge( $common_token_att, $common_pres_att, $common_att ),
			'mlabeledtr'     => array_merge( [ 'rowalign', 'columnalign', 'groupalign' ], $common_pres_att, $common_att ),
			'mlongdiv'       => array_merge( [ 'longdivstyle' ], $common_pres_att, $common_att ),
			'mmultiscripts'  => array_merge( [ 'subscriptshift', 'superscriptshift' ], $common_pres_att, $common_att ),
			'mn'             => array_merge( $common_token_att, $common_pres_att, $common_att ),
			'mo'             => array_merge( [ 'form', 'fence', 'separator', 'lspace', 'rspace', 'stretchy', 'symmetric', 'maxsize', 'minsize', 'largeop', 'movablelimits', 'accent' ], $common_token_att, $common_pres_att, $common_att ),
			'mover'          => array_merge( [ 'accent', 'align' ], $common_pres_att, $common_att ),
			'mpadded'        => array_merge( [ 'height', 'depth', 'width', 'lspace', 'voffset' ], $common_pres_att, $common_att ),
			'mphantom'       => array_merge( [ 'mathbackground' ], $common_att ),
			'mroot'          => array_merge( $common_pres_att, $common_att ),
			'mrow'           => array_merge( [ 'dir' ], $common_pres_att, $common_att ),
			'ms'             => array_merge( [ 'lquote', 'rquote' ], $common_token_att, $common_pres_att, $common_att ),
			'mscarries'      => array_merge( [ 'position', 'location', 'crossout', 'scriptsizemultiplier' ], $common_pres_att, $common_att ),
			'mscarry'        => array_merge( [ 'location', 'crossout' ], $common_pres_att, $common_att ),
			'msgroup'        => array_merge( [ 'position', 'shift' ], $common_pres_att, $common_att ),
			'msline'         => array_merge( [ 'position', 'length', 'leftoverhang', 'rightoverhang', 'mslinethickness' ], $common_pres_att, $common_att ),
			'mspace'         => array_merge( [ 'width', 'height', 'depth', 'linebreak' ], $common_pres_att, $common_att ),
			'msqrt'          => array_merge( $common_pres_att, $common_att ),
			'msrow'          => array_merge( [ 'position' ], $common_pres_att, $common_att ),
			'mstack'         => array_merge( [ 'align', 'stackalign', 'charalign', 'charspacing' ], $common_pres_att, $common_att ),
			'mstyle'         => array_merge( [ 'scriptlevel', 'displaystyle', 'scriptsizemultiplier', 'scriptminsize', 'infixlinebreakstyle', 'decimalpoint' ], $common_pres_att, $common_att ),
			'msub'           => array_merge( [ 'subscriptshift' ], $common_pres_att, $common_att ),
			'msubsup'        => array_merge( [ 'subscriptshift', 'superscriptshift' ], $common_pres_att, $common_att ),
			'msup'           => array_merge( [ 'superscriptshift' ], $common_pres_att, $common_att ),
			'mtable'         => array_merge( [ 'align', 'rowalign', 'columnalign', 'groupalign', 'alignmentscope', 'columnwidth', 'width', 'rowspacing', 'columnspacing', 'rowlines', 'columnlines', 'frame', 'framespacing', 'equalrows', 'equalcolumns', 'displaystyle', 'side', 'minlabelspacing' ], $common_pres_att, $common_att ),
			'mtd'            => array_merge( [ 'rowspan', 'columnspan', 'rowalign', 'columnalign', 'groupalign' ], $common_pres_att, $common_att ),
			'mtext'          => array_merge( $common_token_att, $common_pres_att, $common_att ),
			'mtr'            => array_merge( [ 'rowalign', 'columnalign', 'groupalign' ], $common_pres_att, $common_att ),
			'munder'         => array_merge( [ 'accentunder', 'align' ], $common_pres_att, $common_att ),
			'munderover'     => array_merge( [ 'accent', 'accentunder', 'align' ], $common_pres_att, $common_att ),
			'semantics'      => array_merge( [ 'cd', 'name', 'src' ], $def_enc_att ),
			// Content Markup (https://www.w3.org/TR/MathML3/chapter4.html)
			'abs'                 => array_merge( $common_att, $def_enc_att ),
			'and'                 => array_merge( $common_att, $def_enc_att ),
			'apply'               => array_merge( $common_att, $def_enc_att ),
			'approx'              => array_merge( $common_att, $def_enc_att ),
			'arccos'              => array_merge( $common_att, $def_enc_att ),
			'arccosh'             => array_merge( $common_att, $def_enc_att ),
			'arccot'              => array_merge( $common_att, $def_enc_att ),
			'arccoth'             => array_merge( $common_att, $def_enc_att ),
			'arccsc'              => array_merge( $common_att, $def_enc_att ),
			'arccsch'             => array_merge( $common_att, $def_enc_att ),
			'arcsec'              => array_merge( $common_att, $def_enc_att ),
			'arcsech'             => array_merge( $common_att, $def_enc_att ),
			'arcsin'              => array_merge( $common_att, $def_enc_att ),
			'arcsinh'             => array_merge( $common_att, $def_enc_att ),
			'arctan'              => array_merge( $common_att, $def_enc_att ),
			'arctanh'             => array_merge( $common_att, $def_enc_att ),
			'arg'                 => array_merge( $common_att, $def_enc_att ),
			'bind'                => array_merge( $common_att, $def_enc_att ),
			'bvar'                => array_merge( $common_att, $def_enc_att ),
			'card'                => array_merge( $common_att, $def_enc_att ),
			'cartesianproduct'    => array_merge( $common_att, $def_enc_att ),
			'ceiling'             => array_merge( $common_att, $def_enc_att ),
			'ci'                  => array_merge( [ 'type' ], $common_att, $def_enc_att ),
			'cn'                  => array_merge( [ 'type', 'base' ], $common_att, $def_enc_att ),
			'cs'                  => array_merge( $common_att, $def_enc_att ),
			'codomain'            => array_merge( $common_att, $def_enc_att ),
			'complexes'           => array_merge( $common_att, $def_enc_att ),
			'compose'             => array_merge( $common_att, $def_enc_att ),
			'condition'           => array_merge( $common_att, $def_enc_att ),
			'conjugate'           => array_merge( $common_att, $def_enc_att ),
			'cos'                 => array_merge( $common_att, $def_enc_att ),
			'cosh'                => array_merge( $common_att, $def_enc_att ),
			'cot'                 => array_merge( $common_att, $def_enc_att ),
			'coth'                => array_merge( $common_att, $def_enc_att ),
			'csc'                 => array_merge( $common_att, $def_enc_att ),
			'csch'                => array_merge( $common_att, $def_enc_att ),
			'csymbol'             => array_merge( [ 'type', 'cd' ], $common_att, $def_enc_att ),
			'curl'                => array_merge( $common_att, $def_enc_att ),
			'degree'              => $common_att,
			'determinant'         => array_merge( $common_att, $def_enc_att ),
			'diff'                => array_merge( $common_att, $def_enc_att ),
			'divergence'          => array_merge( $common_att, $def_enc_att ),
			'divide'              => array_merge( $common_att, $def_enc_att ),
			'domain'              => array_merge( $common_att, $def_enc_att ),
			'domainofapplication' => $common_att,
			'emptyset'            => array_merge( $common_att, $def_enc_att ),
			'eq'                  => array_merge( $common_att, $def_enc_att ),
			'equivalent'          => array_merge( $common_att, $def_enc_att ),
			'eulergamma'          => array_merge( $common_att, $def_enc_att ),
			'exists'              => array_merge( $common_att, $def_enc_att ),
			'exp'                 => array_merge( $common_att, $def_enc_att ),
			'exponentiale'        => array_merge( $common_att, $def_enc_att ),
			'factorial'           => array_merge( $common_att, $def_enc_att ),
			'factorof'            => array_merge( $common_att, $def_enc_att ),
			'false'               => array_merge( $common_att, $def_enc_att ),
			'floor'               => array_merge( $common_att, $def_enc_att ),
			'forall'              => array_merge( $common_att, $def_enc_att ),
			'gcd'                 => array_merge( $common_att, $def_enc_att ),
			'geq'                 => array_merge( $common_att, $def_enc_att ),
			'grad'                => array_merge( $common_att, $def_enc_att ),
			'gt'                  => array_merge( $common_att, $def_enc_att ),
			'ident'               => array_merge( $common_att, $def_enc_att ),
			'image'               => array_merge( $common_att, $def_enc_att ),
			'imaginary'           => array_merge( $common_att, $def_enc_att ),
			'imaginaryi'          => array_merge( $common_att, $def_enc_att ),
			'implies'             => array_merge( $common_att, $def_enc_att ),
			'in'                  => array_merge( $common_att, $def_enc_att ),
			'infinity'            => array_merge( $common_att, $def_enc_att ),
			'int'                 => array_merge( $common_att, $def_enc_att ),
			'integers'            => array_merge( $common_att, $def_enc_att ),
			'intersect'           => array_merge( $common_att, $def_enc_att ),
			'interval'            => array_merge( [ 'closure' ], $common_att, $def_enc_att ),
			'inverse'             => array_merge( $common_att, $def_enc_att ),
			'lambda'              => array_merge( $common_att, $def_enc_att ),
			'laplacian'           => array_merge( $common_att, $def_enc_att ),
			'lcm'                 => array_merge( $common_att, $def_enc_att ),
			'leq'                 => array_merge( $common_att, $def_enc_att ),
			'limit'               => array_merge( $common_att, $def_enc_att ),
			'list'                => array_merge( [ 'order' ], $common_att, $def_enc_att ),
			'ln'                  => array_merge( $common_att, $def_enc_att ),
			'log'                 => array_merge( $common_att, $def_enc_att ),
			'lowlimit'            => array_merge( $common_att, $def_enc_att ),
			'lt'                  => array_merge( $common_att, $def_enc_att ),
			'matrix'              => array_merge( $common_att, $def_enc_att ),
			'matrixrow'           => array_merge( $common_att, $def_enc_att ),
			'max'                 => array_merge( $common_att, $def_enc_att ),
			'mean'                => array_merge( $common_att, $def_enc_att ),
			'median'              => array_merge( $common_att, $def_enc_att ),
			'min'                 => array_merge( $common_att, $def_enc_att ),
			'minus'               => array_merge( $common_att, $def_enc_att ),
			'mode'                => array_merge( $common_att, $def_enc_att ),
			'moment'              => array_merge( $common_att, $def_enc_att ),
			'momentabout'         => array_merge( $common_att, $def_enc_att ),
			'naturalnumbers'      => array_merge( $common_att, $def_enc_att ),
			'neq'                 => array_merge( $common_att, $def_enc_att ),
			'not'                 => array_merge( $common_att, $def_enc_att ),
			'notanumber'          => array_merge( $common_att, $def_enc_att ),
			'notin'               => array_merge( $common_att, $def_enc_att ),
			'notprsubset'         => array_merge( $common_att, $def_enc_att ),
			'notsubset'           => array_merge( $common_att, $def_enc_att ),
			'or'                  => array_merge( $common_att, $def_enc_att ),
			'otherwise'           => array_merge( $common_att, $def_enc_att ),
			'outerproduct'        => array_merge( $common_att, $def_enc_att ),
			'partialdiff'         => array_merge( $common_att, $def_enc_att ),
			'pi'                  => array_merge( $common_att, $def_enc_att ),
			'piece'               => array_merge( $common_att, $def_enc_att ),
			'piecewise'           => array_merge( $common_att, $def_enc_att ),
			'plus'                => array_merge( $common_att, $def_enc_att ),
			'power'               => array_merge( $common_att, $def_enc_att ),
			'primes'              => array_merge( $common_att, $def_enc_att ),
			'product'             => array_merge( $common_att, $def_enc_att ),
			'prsubset'            => array_merge( $common_att, $def_enc_att ),
			'quotient'            => array_merge( $common_att, $def_enc_att ),
			'rationals'           => array_merge( $common_att, $def_enc_att ),
			'real'                => array_merge( $common_att, $def_enc_att ),
			'reals'               => array_merge( $common_att, $def_enc_att ),
			'rem'                 => array_merge( $common_att, $def_enc_att ),
			'root'                => array_merge( $common_att, $def_enc_att ),
			'scalarproduct'       => array_merge( $common_att, $def_enc_att ),
			'sdev'                => array_merge( $common_att, $def_enc_att ),
			'sec'                 => array_merge( $common_att, $def_enc_att ),
			'sech'                => array_merge( $common_att, $def_enc_att ),
			'selector'            => array_merge( $common_att, $def_enc_att ),
			'sep'                 => array_merge( $common_att, $def_enc_att ),
			'set'                 => array_merge( [ 'type' ], $common_att, $def_enc_att ),
			'setdiff'             => array_merge( $common_att, $def_enc_att ),
			'share'               => array_merge( [ 'src' ], $common_att ),
			'sin'                 => array_merge( $common_att, $def_enc_att ),
			'sinh'                => array_merge( $common_att, $def_enc_att ),
			'subset'              => array_merge( $common_att, $def_enc_att ),
			'sum'                 => array_merge( $common_att, $def_enc_att ),
			'tan'                 => array_merge( $common_att, $def_enc_att ),
			'tanh'                => array_merge( $common_att, $def_enc_att ),
			'tendsto'             => array_merge( [ 'type' ], $common_att, $def_enc_att ),
			'times'               => array_merge( $common_att, $def_enc_att ),
			'transpose'           => array_merge( $common_att, $def_enc_att ),
			'true'                => array_merge( $common_att, $def_enc_att ),
			'union'               => array_merge( $common_att, $def_enc_att ),
			'uplimit'             => array_merge( $common_att, $def_enc_att ),
			'variance'            => array_merge( $common_att, $def_enc_att ),
			'vector'              => array_merge( $common_att, $def_enc_att ),
			'vectorproduct'       => array_merge( $common_att, $def_enc_att ),
			'xor'                 => array_merge( $common_att, $def_enc_att ),
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
	 * @param  $content
	 * @return string without <br /> tags
	 */
	public function filterLineBreakTagsInMthml( $content ) {
		$filtered_content = preg_replace_callback(
			'/(<math.*>.*<\/math>)/isU',
			function ( $matches ) {
				return str_replace( [ '<br/>', '<br />', '<br>', '<p>', '</p>' ], '', $matches[0] );
			},
			$content
		);
		return null === $filtered_content ? $content : $filtered_content;
	}

	/**
	 * Removes the <br /> and <p> tags inside math tags
	 *
	 * @param  $content
	 * @return string without <br /> tags
	 */
	public function filterLineBreakTagsInSvg( $content ) {
		$filtered_content = preg_replace_callback(
			'/(<svg.*>.*<\/svg>)/isU',
			function ( $matches ) {
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
				 * @since  5.9.0
				 */
				if ( apply_filters( 'pb_mathjax_use', $this->usePbMathJax ) && PB_MATHJAX_URL ) {
					$options = $this->getOptions();
					$url = rtrim( PB_MATHJAX_URL, '/' );
					$url .= '/mathml?mathml=' . rawurlencode( $mathml ) . '&fg=' . $options['fg'];
					/**
					 * Return a SVG instead of a PNG
					 *
					 * @param bool $var
					 *
					 * @return bool
					 * @since  5.9.0
					 */
					if ( apply_filters( 'pb_mathjax_use_svg', $this->useSVG ) ) {
						$url .= '&svg=1';
					}
					$url = esc_url( $url );
					$alt = str_replace( "\n", '', normalize_whitespace( wp_strip_all_tags( $mathml ) ) );
					$alt = str_replace( '\\', '&#92;', esc_attr( $alt ) );
					return '<img src="' . $url . '" alt="' . $alt . '" title="' . $alt . '" class="mathml mathjax" />';
				}
				return null;
			},
			$content
		);

		return null === $filtered_content ? $content : $filtered_content;

	}

	public function displayMathHandler($scss) {
		$scss.= ".display-math { display: block; text-align:center; padding-top: 20px; padding-bottom:20px; } \n";
		return $scss;
	}

}
