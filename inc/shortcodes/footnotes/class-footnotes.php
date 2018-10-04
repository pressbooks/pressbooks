<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Footnotes;

class Footnotes {

	/**
	 * @var Footnotes
	 */
	static $instance = null;

	/**
	 * @var array
	 */
	var $footnotes = [];

	/**
	 * @var array
	 */
	var $numbered = [];

	/**
	 * Function to init our class, set filters & hooks, set a singleton instance
	 *
	 * @return Footnotes
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Footnotes $obj
	 */
	static public function hooks( Footnotes $obj ) {
		add_shortcode( 'footnote', [ $obj, 'shortcodeHandler' ] );
		add_filter(
			'no_texturize_shortcodes',
			function ( $excluded_shortcodes ) {
				$excluded_shortcodes[] = 'footnote';
				return $excluded_shortcodes;
			}
		);
		// do_shortcode() is registered as a default filter on 'the_content' with a priority of 11.
		// We need to run $this->footNoteContent() after this, and after attributions which is set to 12, set to 13
		add_filter( 'the_content', [ $obj, 'footnoteContent' ], 13 );
	}

	/**
	 *
	 */
	public function __construct() {
	}

	/**
	 * Pre-process footnote shortcode
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function shortcodeHandler( $atts, $content = '' ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		$a = shortcode_atts(
			[
				'numbered' => 'yes',
				'symbol' => '*',
				'suptext' => ' ',
			], $atts
		);

		if ( ! $content ) {
			return '';
		}

		if ( ! isset( $this->footnotes[ $id ] ) ) {
			$this->footnotes[ $id ] = [];
			if ( 'no' === $a['numbered'] ) {
				$this->numbered[ $id ] = false;
			} else {
				$this->numbered[ $id ] = true;
			}
		}

		$this->footnotes[ $id ][] = $content;
		$footnotes = $this->footnotes[ $id ];
		$num = count( $footnotes );
		$numlabel = "$id-$num";

		$retval = '<a class="footnote" title="' . \Pressbooks\Sanitize\sanitize_xml_attribute( wp_strip_all_tags( $content ) ) . '" id="return-footnote-' . $numlabel . '" href="#footnote-' . $numlabel . '"><sup class="footnote">[';

		if ( $this->numbered[ $id ] ) {
			$retval .= $num;
		} else {
			$retval .= $a['symbol'];
		}

		if ( trim( $a['suptext'] ) ) {
			if ( $this->numbered[ $id ] ) {
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

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		if ( ! empty( $this->footnotes ) && isset( $this->footnotes[ $id ] ) ) {
			$footnotes = $this->footnotes[ $id ];
		} else {
			return $content;
		}

		if ( $this->numbered[ $id ] ) {
			$content .= '<hr class="before-footnotes" /><div class="footnotes"><ol>';
		} else {
			$content .= '<hr class="before-footnotes" /><div class="footnotes"><ul>';
		}

		foreach ( $footnotes as $num => $footnote ) {
			$num++;
			$numlabel = "$id-$num";
			$content .= '<li id="footnote-' . $numlabel . '">' . make_clickable( $footnote ) . ' <a href="#return-footnote-' . $numlabel . '" class="return-footnote">&crarr;</a></li>';
		}

		if ( $this->numbered[ $id ] ) {
			$content .= '</ol></div>';
		} else {
			$content .= '</ul></div>';
		}

		unset( $this->footnotes[ $id ] ); // Done, reset

		return $content;
	}

	/**
	 * Echo a failure message for jQuery then die
	 *
	 * @param string $msg (optional)
	 */
	static function ajaxFailure( $msg = '' ) {

		if ( ! headers_sent() ) {
			header( 'HTTP/1.0 500 Internal Server Error' );
		}
		if ( $msg ) {
			echo "Something went wrong: \n\n $msg";
		}
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
		$patterns = [
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#_ftnref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](#_ftnref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#_ednref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](#_ednref([0-9]+))["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(#sdfootnote([0-9]+)anc)["\']+.*?>(?:[^<]+|.*?)?</a>(.*?)</div>~si', // Libre Office
		];

		/**
		 * A $replacer must be in the same position as a corresponding $pattern above,
		 * use __REPLACE_ME__ to substitute for what we don't know yet.
		 */
		$replacers = [
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#_ftn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](?:#_ftn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#_edn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+.*?[\.doc|\.docx](?:#_edn__REPLACE_ME__)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // MS Word
			'~<a[\s]+[^>]*?href[\s]?=[\s"\']+(?:#sdfootnote__REPLACE_ME__sym)["\']+.*?>(?:[^<]+|.*?)?</a>~si', // Libre Office
		];

		$footnotes = [];
		$find = [];
		$replace = [];

		foreach ( $patterns as $i => $pattern ) {

			preg_match_all( $pattern, $html, $footnotes, PREG_SET_ORDER );

			foreach ( $footnotes as $footnote ) {

				$tmp = wp_kses(
					$footnote[3], [
						'b' => [],
						'em' => [],
						'i' => [],
						'strong' => [],
					]
				);
				$tmp = \Pressbooks\Sanitize\remove_control_characters( $tmp );
				$tmp = trim( preg_replace( '/\s+/', ' ', $tmp ) ); // Normalize white spaces

				$find[] = str_replace( '__REPLACE_ME__', preg_quote( $footnote[2] ), $replacers[ $i ] );
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
		$json = wp_json_encode(
			[
				'content' => $html,
			]
		);
		echo $json;

		wp_die();
	}

}
