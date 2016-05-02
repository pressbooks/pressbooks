<?php
/**
 * Support for short quotations of modern (monotonic) Greek, classical (polytonic) Greek, and ancient Hebrew in
 * non-UTF8 documents.
 *
 * @deprecated
 * @see      http://www.wikipublisher.org/wiki/Pressbooks/GreekAndHebrew
 *
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv2 (or any later version)
 */
namespace Pressbooks\Shortcodes\Wikipublisher;


class Glyphs {

	/**
	 * @var Glyphs - Static property to hold our singleton instance
	 */
	static $instance = false;


	// ISO-639-3
	protected $supported_languages = array(
		'grc', // Ancient Greek (polytonic)
		'ell', // Modern Greek (monotonic)
		'hbo', // Ancient Hebrew
	);


	/**
	 * Setup the [pb_language] shortcode, which is private to force the use of getInstance()
	 *
	 * @deprecated
	 */
	private function __construct() {

		add_shortcode( 'pb_language', array( $this, 'lang_shortcode' ) );
		add_filter( 'no_texturize_shortcodes', function ( $excluded_shortcodes ) {
			$excluded_shortcodes[] = 'pb_language';
			return $excluded_shortcodes;
		} );

	}


	/**
	 * Function to instantiate our class and make it a singleton
	 *
	 * @deprecated
	 * @return Glyphs
	 */
	public static function getInstance() {
		if ( ! self::$instance )
			self::$instance = new self;

		return self::$instance;
	}


	/**
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	function lang_shortcode( $atts, $content = null ) {

		$a = shortcode_atts( array(
			'lang' => '',
		), $atts );

		if ( empty( $content ) || empty( $a['lang'] ) || ! in_array( $a['lang'], $this->supported_languages ) ) {
			// We don't support this language
			$_error = "*** ERROR: Unsupported pb_language attribute: {$a['lang']} -- ";
			$_error .= "Valid choices, based on ISO-639-3, are: " . implode( $this->supported_languages, ', ' ) . ") ***";

			return $_error;
		}

		// Reverse Wordpress' fancy formatting
		// We want to keep all characters such as ‘ ` " '' [...] < > should be &gt; &lt; Ie. not numeric
		$content = str_replace( array( '&#8216;', '&#8217;', '&lsquo;', '&rsquo;' ), "'", $content ); // Change back to '
		$content = str_replace( array( '&#8220;', '&#8221;', '&ldquo;', '&rdquo;' ), '"', $content ); // Change back to "
		$content = str_replace( array( '<br>', '<br />', '<p>', '</p>', '</p>' ), null, $content ); // Get rid of wpautop() auto-formatting
		$content = htmlspecialchars( $content, ENT_NOQUOTES, 'UTF-8', false );

		$language = strtolower( $a['lang'] );

		if ( 'grc' == $language ) {
			// Ancient Greek (polytonic)
			$content = '<span lang="grc">' . $this->greek( $content, 'grc' ) . '</span>';
		} elseif ( 'ell' == $language ) {
			// Modern Greek (monotonic)
			$content = '<span lang="el">' . $this->greek( $content, 'ell' ) . '</span>';
		} elseif ( 'hbo' == $language ) {
			// Ancient Hebrew
			$content = '<span lang="he" dir="rtl">' . $this->hebrew( $content ) . '</span>';
		}

		return $content;
	}


	/**
	 * @param        $text
	 * @param string $lang
	 *
	 * @return string
	 */
	private function greek( $text, $lang = 'grc' ) {

		$monotonics = array(
			"'a" => '&#940;',
			"'e" => '&#941;',
			"'h" => '&#942;',
			"'i" => '&#943;',
			"'o" => '&#972;',
			"'u" => '&#973;',
			"'w" => '&#974;',
			"'A" => '&#902;',
			"'E" => '&#904;',
			"'H" => '&#905;',
			"'I" => '&#906;',
			"'O" => '&#908;',
			"'U" => '&#910;',
			"'W" => '&#911;',
			"'\"i" => '&#912;',
			"'\"u" => '&#944;' );

		$polytonics = array(
			"&gt;`a|" => '&#8066;',
			"&gt;`h|" => '&#8082;',
			"&gt;`w|" => '&#8098;',
			"&gt;'a|" => '&#8068;',
			"&gt;'h|" => '&#8084;',
			"&gt;'w|" => '&#8100;',
			"&gt;`a" => '&#7938;',
			"&gt;`e" => '&#7954;',
			"&gt;`h" => '&#7970;',
			"&gt;`i" => '&#7986;',
			"&gt;`o" => '&#8002;',
			"&gt;`u" => '&#8018;',
			"&gt;`w" => '&#8034;',
			"&gt;'a" => '&#7940;',
			"&gt;'e" => '&#7956;',
			"&gt;'h" => '&#7972;',
			"&gt;'i" => '&#7988;',
			"&gt;'o" => '&#8004;',
			"&gt;'u" => '&#8020;',
			"&gt;'w" => '&#8036;',

			"&lt;`a|" => '&#8067;',
			"&lt;`h|" => '&#8083;',
			"&lt;`w|" => '&#8099;',
			"&lt;'a|" => '&#8069;',
			"&lt;'h|" => '&#8085;',
			"&lt;'w|" => '&#8101;',
			"&lt;`a" => '&#7939;',
			"&lt;`e" => '&#7955;',
			"&lt;`h" => '&#7971;',
			"&lt;`i" => '&#7987;',
			"&lt;`o" => '&#8003;',
			"&lt;`u" => '&#8019;',
			"&lt;`w" => '&#8035;',
			"&lt;'a" => '&#7941;',
			"&lt;'e" => '&#7957;',
			"&lt;'h" => '&#7973;',
			"&lt;'i" => '&#7989;',
			"&lt;'o" => '&#8005;',
			"&lt;'u" => '&#8021;',
			"&lt;'w" => '&#8037;',

			"&gt;`A|" => '&#8074;',
			"&gt;`H|" => '&#8090;',
			"&gt;`W|" => '&#8106;',
			"&gt;'A|" => '&#8076;',
			"&gt;'H|" => '&#8092;',
			"&gt;'W|" => '&#8108;',
			"&gt;`A" => '&#7946;',
			"&gt;`E" => '&#7962;',
			"&gt;`H" => '&#7978;',
			"&gt;`I" => '&#7994;',
			"&gt;`O" => '&#8010;',
			"&gt;`W" => '&#8042;',
			"&gt;'A" => '&#7948;',
			"&gt;'E" => '&#7964;',
			"&gt;'H" => '&#7980;',
			"&gt;'I" => '&#7996;',
			"&gt;'O" => '&#8012;',
			"&gt;'W" => '&#8044;',

			"&lt;`A|" => '&#8075;',
			"&lt;`H|" => '&#8091;',
			"&lt;`W|" => '&#8107;',
			"&lt;'A|" => '&#8077;',
			"&lt;'H|" => '&#8093;',
			"&lt;'W|" => '&#8109;',
			"&lt;`A" => '&#7947;',
			"&lt;`E" => '&#7963;',
			"&lt;`H" => '&#7979;',
			"&lt;`I" => '&#7995;',
			"&lt;`O" => '&#8011;',
			"&lt;`U" => '&#8027;',
			"&lt;`W" => '&#8043;',
			"&lt;'A" => '&#7949;',
			"&lt;'E" => '&#7965;',
			"&lt;'H" => '&#7981;',
			"&lt;'I" => '&#7997;',
			"&lt;'O" => '&#8013;',
			"&lt;'U" => '&#8029;',
			"&lt;'W" => '&#8045;',

			"`a|" => '&#8114;',
			"'a|" => '&#8116;',
			"`h|" => '&#8130;',
			"'h|" => '&#8132;',
			"`w|" => '&#8178;',
			"'w|" => '&#8180;',
			"'a" => '&#8049;',
			"'e" => '&#8051;',
			"'h" => '&#8053;',
			"'i" => '&#8055;',
			"'o" => '&#8057;',
			"'u" => '&#8059;',
			"'w" => '&#8061;',
			"`a" => '&#8048;',
			"`e" => '&#8050;',
			"`h" => '&#8052;',
			"`i" => '&#8054;',
			"`o" => '&#8056;',
			"`u" => '&#8058;',
			"`w" => '&#8060;',

			"'A" => '&#8123;',
			"'E" => '&#8137;',
			"'H" => '&#8139;',
			"'I" => '&#8155;',
			"'O" => '&#8185;',
			"'U" => '&#8171;',
			"'W" => '&#8187;',
			"`A" => '&#8122;',
			"`E" => '&#8136;',
			"`H" => '&#8138;',
			"`I" => '&#8154;',
			"`O" => '&#8184;',
			"`U" => '&#8170;',
			"`W" => '&#8186;',

			'~a|' => '&#8119;',
			'~h|' => '&#8135;',
			'~w|' => '&#8183;',
			"~a" => '&#8118;',
			"~h" => '&#8134;',
			"~i" => '&#8150;',
			"~u" => '&#8166;',
			"~w" => '&#8182;',
			"~&lt;a|" => '&#8071;',
			"~&lt;h|" => '&#8087;',
			"~&lt;w|" => '&#8103;',
			"~&lt;A|" => '&#8079;',
			"~&lt;H|" => '&#8095;',
			"~&lt;W|" => '&#8111;',
			"~&lt;a" => '&#7943;',
			"~&lt;h" => '&#7975;',
			"~&lt;i" => '&#7991;',
			"~&lt;u" => '&#8023;',
			"~&lt;w" => '&#8039;',
			"~&lt;A" => '&#7951;',
			"~&lt;H" => '&#7983;',
			"~&lt;I" => '&#7999;',
			"~&lt;U" => '&#8031;',
			"~&lt;W" => '&#8047;',
			'~&gt;a|' => '&#8070;',
			'~&gt;h|' => '&#8086;',
			'~&gt;w|' => '&#8102;',
			'~&gt;a' => '&#7942;',
			'~&gt;h' => '&#7974;',
			'~&gt;i' => '&#7990;',
			'~&gt;u' => '&#8022;',
			'~&gt;w' => '&#8038;',
			'~&gt;A|' => '&#8078;',
			'~&gt;H|' => '&#8094;',
			'~&gt;W|' => '&#8110;',
			'~&gt;A' => '&#7950;',
			'~&gt;H' => '&#7982;',
			'~&gt;I' => '&#7998;',
			'~&gt;W' => '&#8046;',

			"&gt;a|" => '&#8064;',
			"&lt;a|" => '&#8065;',
			"&gt;h|" => '&#8080;',
			"&lt;h|" => '&#8081;',
			"&gt;w|" => '&#8096;',
			"&lt;w|" => '&#8097;',
			"&gt;A|" => '&#8072;',
			"&lt;A|" => '&#8073;',
			"&gt;H|" => '&#8088;',
			"&lt;H|" => '&#8089;',
			"&gt;W|" => '&#8104;',
			"&lt;W|" => '&#8105;',
			"&gt;a" => '&#7936;',
			"&lt;a" => '&#7937;',
			"&gt;e" => '&#7952;',
			"&lt;e" => '&#7953;',
			"&gt;h" => '&#7968;',
			"&lt;h" => '&#7969;',
			"&gt;i" => '&#7984;',
			"&lt;i" => '&#7985;',
			"&gt;o" => '&#8000;',
			"&lt;o" => '&#8001;',
			"&gt;u" => '&#8016;',
			"&lt;u" => '&#8017;',
			"&gt;w" => '&#8032;',
			"&lt;w" => '&#8033;',
			"&gt;r" => '&#8164;',
			"&lt;r" => '&#8165;',
			"a|" => '&#8115;',
			"h|" => '&#8131;',
			"w|" => '&#8179;',
			"A|" => '&#8124;',
			"H|" => '&#8140;',
			"W|" => '&#8188;',
			"&gt;A" => '&#7944;',
			"&lt;A" => '&#7945;',
			"&gt;E" => '&#7960;',
			"&lt;E" => '&#7961;',
			"&gt;H" => '&#7976;',
			"&lt;H" => '&#7977;',
			"&gt;I" => '&#7992;',
			"&lt;I" => '&#7993;',
			"&gt;O" => '&#8008;',
			"&lt;O" => '&#8009;',
			"&lt;U" => '&#8025;',
			"&gt;W" => '&#8040;',
			"&lt;W" => '&#8041;',
			"&lt;R" => '&#8172;',

			"~\"i" => '&#8151;',
			"~\"u" => '&#8167;',
			"'\"i" => '&#8147;',
			"'\"u" => '&#8163;',
			"`\"i" => '&#8146;',
			"`\"u" => '&#8162;'
		);

		$gr_alphabet = array( '"i' => '&#970;',
			'"u' => '&#971;',
			'"I' => '&#938;',
			'"U' => '&#939;',
			's ' => '&#962; ',
			'sv' => '&#963;',
			'a' => '&#945;',
			'b' => '&#946;',
			'c' => '&#962;',
			'd' => '&#948;',
			'e' => '&#949;',
			'f' => '&#966;',
			'g' => '&#947;',
			'h' => '&#951;',
			'i' => '&#953;',
			'j' => '&#952;',
			'k' => '&#954;',
			'l' => '&#955;',
			'm' => '&#956;',
			'n' => '&#957;',
			'o' => '&#959;',
			'p' => '&#960;',
			'q' => '&#967;',
			'r' => '&#961;',
			's' => '&#963;',
			't' => '&#964;',
			'u' => '&#965;',
			'v' => '',
			'w' => '&#969;',
			'x' => '&#958;',
			'y' => '&#968;',
			'z' => '&#950;',
			'A' => '&#913;',
			'B' => '&#914;',
			'C' => '',
			'D' => '&#916;',
			'E' => '&#917;',
			'F' => '&#934;',
			'G' => '&#915;',
			'H' => '&#919;',
			'I' => '&#921;',
			'J' => '&#920;',
			'K' => '&#922;',
			'L' => '&#923;',
			'M' => '&#924;',
			'N' => '&#925;',
			'O' => '&#927;',
			'P' => '&#928;',
			'Q' => '&#935;',
			'R' => '&#929;',
			'S' => '&#931;',
			'T' => '&#932;',
			'U' => '&#933;',
			'V' => '',
			'W' => '&#937;',
			'X' => '&#926;',
			'Y' => '&#936;',
			'Z' => '&#918;',
			";;" => ';&#903;',
			'?' => '&#894;',
			"''&" => '&lsquo;&',
			"''" => '&rsquo;'
		);

		$r = ( $lang == 'grc' ) ? str_replace( array_keys( $polytonics ), array_values( $polytonics ), $text ) : str_replace( array_keys( $monotonics ), array_values( $monotonics ), $text );

		return str_replace( array_keys( $gr_alphabet ), array_values( $gr_alphabet ), $r );
	}


	/**
	 * @param $text
	 *
	 * @return string
	 */
	private function hebrew( $text ) {

		$he_alphabet = array(
			";" => '!&#1475;',
			"'" => '&#1488;',
			"b" => '&#1489;',
			"g" => '&#1490;',
			"d" => '&#1491;',
			"/a?" => '&#9676;&#1463;',
			"/a`" => '&#1506;&#1463;',
			"/ah*" => '&#1492;&#1468;&#1463;',
			"/ah" => '&#1492;&#1463;',
			"/a.h" => '&#1495;&#1463;',
			".h" => '&#1495;',
			"h" => '&#1492;',
			"w" => '&#1493;',
			"z" => '&#1494;',
			".t" => '&#1496;',
			"y" => '&#1497;',
			"K" => '&#1498;',
			"k!" => '&#1498;',
			"k " => '&#1498; ',
			"k" => '&#1499;',
			"l" => '&#1500;',
			"M" => '&#1501;',
			"m!" => '&#1501;',
			"m " => '&#1501; ',
			"m" => '&#1502;',
			"N" => '&#1503;',
			"n!" => '&#1503;',
			"n " => '&#1503; ',
			"n" => '&#1504;',
			"`" => '&#1506;',
			"P" => '&#1507;',
			"p!" => '&#1507;',
			"p " => '&#1507; ',
			"p" => '&#1508;',
			".S" => '&#1509;',
			".s!" => '&#1509;',
			".s " => '&#1509; ',
			".s" => '&#1510;',
			"/s" => '&#1513;',
			"+s" => '&#1513;&#1473;',
			",s" => '&#1513;&#1474;',
			"s" => '&#1505;',
			"q" => '&#1511;',
			"r" => '&#1512;',
			"t" => '&#1514;',
			"E:" => '&#1457;',
			"a:" => '&#1458;',
			"A:" => '&#1459;',
			":" => '&#1456;',
			"i" => '&#1460;',
			"e" => '&#1461;',
			"E" => '&#1462;',
			"a" => '&#1463;',
			"A" => '&#1464;',
			"o" => '&#1465;',
			"u" => '&#1467;',
			"O" => '&#1493;&#1465;',
			"U" => '&#1493;&#1468;',
			"*" => '&#1468;',
			"--" => '&#1470;',
			"?" => '&#9676;',
			"!" => '',
			"|" => ''
		);

		return str_replace( array_keys( $he_alphabet ), array_values( $he_alphabet ), $text );
	}

}