<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv2 (or any later version)
 */
namespace Pressbooks\Shortcodes\Footnotes;


class Footnotes {

	/**
	 * @var Footnotes - Static property to hold our singleton instance
	 */
	static $instance = false;


	/**
	 * @var array
	 */
	var $footnotes = array();


	/**
	 * @var array
	 */
	var $numbered = array();


	/**
	 * This is our constructor, which is private to force the use of getInstance()
	 */
	private function __construct() {

		add_shortcode( 'footnote', array( $this, 'shortcodeHandler' ) );
		add_filter( 'no_texturize_shortcodes', function ( $excluded_shortcodes ) {
			$excluded_shortcodes[] = 'footnote';
			return $excluded_shortcodes;
		} );

		// do_shortcode() is registered as a default filter on 'the_content' with a priority of 11.
		// We need to run $this->footNoteContent() after this, set to 12
		add_filter( 'the_content', array( $this, 'footnoteContent' ), 12 );

		add_action( 'init', array( $this, 'footnoteButton' ) ); // TinyMCE button
		add_action( 'admin_enqueue_scripts', array( $this, 'myCustomQuicktags' ) ); // Quicktag button
	}


	/**
	 * Function to instantiate our class and make it a singleton
	 *
	 * @return Footnotes
	 */
	public static function getInstance() {
		if ( ! self::$instance )
			self::$instance = new self;

		return self::$instance;
	}


	/**
	 * Pre-process footnote shortcode
	 *
	 * @param array   $atts
	 * @param string  $content
	 *
	 * @return string
	 */
	function shortcodeHandler( $atts, $content = '' ) {

		global $id;

		$a = shortcode_atts( array(
			'numbered' => 'yes',
			'symbol' => '*',
			'suptext' => ' '
		), $atts );


		if ( ! $content ) {
			return '';
		}

		if ( ! isset( $this->footnotes[$id] ) ) {
			$this->footnotes[$id] = array();
			if ( $a['numbered'] == 'no' ) {
				$this->numbered[$id] = false;
			} else {
				$this->numbered[$id] = true;
			}
		}

		$this->footnotes[$id][] = $content;
		$footnotes = $this->footnotes[$id];
		$num = count( $footnotes );
		$numlabel = "$id-$num";

		$retval = '<a class="footnote" title="' . \Pressbooks\Sanitize\sanitize_xml_attribute( wp_strip_all_tags( $content ) ) . '" id="return-footnote-' . $numlabel . '" href="#footnote-' . $numlabel . '"><sup class="footnote">[';

		if ( $this->numbered[$id] ) {
			$retval .= $num;
		} else {
			$retval .= $a['symbol'];
		}

		if ( trim( $a['suptext'] ) ) {
			if ( $this->numbered[$id] ) {
				$retval .= '. ';
			}

			$retval .= $a['suptext'];
		}
		$retval .= ']</sup></a>';

		return $retval;
	}


	/**
	 * Post-process footnote shortcode
	 *
	 * @param $content
	 *
	 * @return string
	 */
	function footnoteContent( $content ) {

		global $id;

		if ( ! empty( $this->footnotes ) && isset( $this->footnotes[$id] ) ) {
			$footnotes = $this->footnotes[$id];
		} else {
			return $content;
		}

		if ( $this->numbered[$id] ) {
			$content .= '<hr /><div class="footnotes"><ol>';
		} else {
			$content .= '<hr /><div class="footnotes"><ul>';
		}

		foreach ( $footnotes as $num => $footnote ) {
			$num ++;
			$numlabel = "$id-$num";
			$content .= '<li id="footnote-' . $numlabel . '">' . make_clickable( $footnote ) . ' <a href="#return-footnote-' . $numlabel . '" class="return-footnote">&crarr;</a></li>';
		}

		if ( $this->numbered[$id] ) {
			$content .= '</ol></div>';
		} else {
			$content .= '</ul></div>';
		}

		unset( $this->footnotes[$id] ); // Done, reset

		return $content;
	}


	/**
	 * Register our plugin with TinyMCE
	 */
	function footnoteButton() {

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( get_user_option( 'rich_editing' ) == 'true' ) {

			add_action( 'admin_enqueue_scripts', function () {
				wp_localize_script( 'editor', 'PB_FootnotesToken', array(
					'nonce' => wp_create_nonce( 'pb-footnote-convert' ),
					'fn_title' => __( 'Insert Footnote', 'pressbooks' ),
					'ftnref_title' => __( 'Convert MS Word Footnotes', 'pressbooks' ),
				) );
			} );

			add_filter( 'mce_external_plugins', array( $this, 'addFootnotePlugin' ) );
			add_filter( 'mce_buttons_3', array( $this, 'registerFootnoteButtons' ) );
		}

	}


	/**
	 * Quicktag buttons for text mode editor
	 */
	function myCustomQuicktags() {
		wp_enqueue_script( 'my_custom_quicktags', \Pressbooks\Utility\asset_path( 'scripts/quicktags.js' ), ['quicktags'] );
	}


	/**
	 * Add buttons to TinyMCE interface
	 *
	 * @param $buttons
	 *
	 * @return array
	 */
	function registerFootnoteButtons( $buttons ) {
		$buttons[] = 'footnote';
		$buttons[] = 'ftnref_convert';

		return $buttons;
	}


	/**
	 * Some JavaScript for our TinyMCE buttons
	 *
	 * @param $plugin_array
	 *
	 * @return mixed
	 */
	function addFootnotePlugin( $plugin_array ) {

		$plugin_array['footnote'] = \Pressbooks\Utility\asset_path( 'scripts/footnote.js' );
		$plugin_array['ftnref_convert'] = \Pressbooks\Utility\asset_path( 'scripts/ftnref-convert.js' );

		return $plugin_array;
	}


	/**
	 * Echo a failure message for jQuery then die
	 *
	 * @param string $msg (optional)
	 */
	static function ajaxFailure( $msg = '' ) {

		if ( ! headers_sent() ) header( "HTTP/1.0 500 Internal Server Error" );
		if ( $msg ) echo "Something went wrong: \n\n $msg";
		wp_die();
	}


	/**
	 * WP_Ajax hook. Convert MS Word footnotes to Pressbooks compatible [footnotes]
	 */
	static function convertWordFootnotes() {

		if ( ! current_user_can( 'edit_posts' ) || ! check_ajax_referer( 'pb-footnote-convert', false, false ) ) {
			static::ajaxFailure( __( 'Invalid permissions.', 'pressbooks' ) );
			return;
		}

		$html = urldecode( stripslashes( $_POST['content'] ) );

		/**
		 * Regular expression tip:
		 * (?: ), in contrast to ( ), is used to avoid capturing text.
		 *
		 * A $pattern must capture:
		 *  [0] => ... full capture ...
		 *  [1] => #_ftnref130
		 *  [2] => 130
		 *  [3] => ... the text we want to move ...
		 *
		 * Known MS Word variations:
		 *  href="#_ftn123" (-> #_ftnref123)
		 *  href="#_edn123" (-> #_ednref123)
		 *  href="/Users/foo/Documents/bar/9781426766497.doc#_ftn123" (-> .doc#_ftnref123)
		 *  href="/Users/foo/Documents/bar/9781426766497.docx#_edn123" (-> .docx#_ednref123)
		 *
		 * Known Libre Office variations:
		 *  href="#sdfootnote123sym" (-> #sdfootnote123anc)
		 */
		$patterns = array(
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#_ftnref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](#_ftnref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#_ednref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](#_ednref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#sdfootnote([0-9]+)anc)["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // Libre Office
		);

		/**
		 * A $replacer must be in the same position as a corresponding $pattern above,
		 * use __REPLACE_ME__ to substitute for what we don't know yet.
		 */
		$replacers = array(
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#_ftn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](?:#_ftn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#_edn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](?:#_edn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#sdfootnote__REPLACE_ME__sym)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // Libre Office
		);

		$footnotes = $find = $replace = array();

		foreach ( $patterns as $i => $pattern ) {

			preg_match_all( $pattern, $html, $footnotes, PREG_SET_ORDER );

			foreach ( $footnotes as $footnote ) {

				$tmp = wp_kses( $footnote[3], array(
					'b' => array(),
					'em' => array(),
					'i' => array(),
					'strong' => array(),
				) );
				$tmp = \Pressbooks\Sanitize\remove_control_characters( $tmp );
				$tmp = trim( preg_replace( '/\s+/', ' ', $tmp ) ); // Normalize white spaces

				$find[] = str_replace( '__REPLACE_ME__', preg_quote( $footnote[2] ), $replacers[$i] );
				$replace[] = '[footnote]' . $tmp . '[/footnote]';
			}

			// Remove originals when done
			$find[] = $pattern;
			$replace[] = '&hellip;</div>';
		}

		// Twerk it
		$html = preg_replace( $find, $replace, $html );

		// Important, complex regular expressions have been known to, literally, crash PHP.
		// When testing, make sure this function exits as expected.

		// Send back JSON
		header( 'Content-Type: application/json' );
		$json = json_encode( array( 'content' => $html ) );
		echo $json;

		wp_die();
	}

}
