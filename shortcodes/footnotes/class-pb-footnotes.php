<?php
/**
 * @author   PressBooks <code@pressbooks.org>
 * @license  GPLv2 (or any later version)
 */
namespace PressBooks\Shortcodes\Footnotes;


class Footnotes {

	/**
	 * @var array
	 */
	var $footnotes = array();

	/**
	 * @var array
	 */
	var $numbered = array();


	/**
	 * Constructor as init routine.
	 */
	function __construct() {

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

		$retval = '<a class="footnote" title="' . \PressBooks\Sanitize\sanitize_xml_attribute( wp_strip_all_tags( $content ) ) . '" id="return-footnote-' . $numlabel . '" href="#footnote-' . $numlabel . '"><sup class="footnote">[';

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
			$content .= '<li id="footnote-' . $numlabel . '">' . $footnote . '<a href="#return-footnote-' . $numlabel . '" class="return-footnote">&crarr;</a></li>';
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

			wp_localize_script( 'editor', 'PB_FootnotesToken', array(
				'nonce' => wp_create_nonce( 'pb-footnote-convert' ),
				'fn_title' => __( 'Insert Footnote', 'pressbooks' ),
				'ftnref_title' => __( 'Convert MS Word Footnotes', 'pressbooks' ),
			) );

			add_filter( 'mce_external_plugins', array( $this, 'addFootnotePlugin' ) );
			add_filter( 'mce_buttons', array( $this, 'registerFootnoteButton' ) );
		}

	}


	/**
	 * Quicktag buttons for text mode editor
	 */
	function myCustomQuicktags() {
		wp_enqueue_script(
			'my_custom_quicktags',
				PB_PLUGIN_URL . 'shortcodes/footnotes/quicktags.js',
			array( 'quicktags' )
		);


	}


	/**
	 * Add buttons to TinyMCE interface
	 *
	 * @param $buttons
	 *
	 * @return array
	 */
	function registerFootnoteButton( $buttons ) {

		array_push( $buttons, '|', 'footnote', 'ftnref_convert' );

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

		$plugin_array['footnote'] = PB_PLUGIN_URL . 'shortcodes/footnotes/footnote.js?ver=1.0';
		$plugin_array['ftnref_convert'] = PB_PLUGIN_URL . 'shortcodes/footnotes/ftnref-convert.js?ver=1.0';

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
		die();
	}


	/**
	 * WP_Ajax hook. Convert MS Word footnotes to Pressbooks compatible [footnotes]
	 */
	static function convertWordFootnotes() {

		if ( ! current_user_can( 'edit_posts' ) || ! check_ajax_referer( 'pb-footnote-convert', false, false ) ) {
			static::ajaxFailure( __( 'Invalid permissions.', 'pressbooks' ) );
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
		 */
		$patterns = array(
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#_ftnref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#_ednref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#sdfootnote([0-9]+)anc)["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // Libre Office
		);

		/**
		 * A $replacer must be in the same position as a corresponding $pattern above,
		 * use __REPLACE_ME__ to substitute for what we don't know yet.
		 */
		$replacers = array(
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#_ftn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#_edn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
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
				$tmp = \PressBooks\Sanitize\remove_control_characters( $tmp );
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

		// Send back JSON
		header( 'Content-Type: application/json' );
		$json = json_encode( array( 'content' => $html ) );
		echo $json;

		// @see http://codex.wordpress.org/AJAX_in_Plugins#Error_Return_Values
		// Will append 0 to returned json string if we don't die()
		die();
	}

}




