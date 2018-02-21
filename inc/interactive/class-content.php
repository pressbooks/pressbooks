<?php

namespace Pressbooks\Interactive;

use Pressbooks\Container;
use Masterminds\HTML5;

class Content {

	/**
	 * Anchor we append to URLs to hint that its interactive content
	 */
	const ANCHOR = '#pb-interactive-content';

	/**
	 * @var Content
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	protected $whitelistedDomains = [
		'phet.colorado.edu',
		'www.openassessments.org',
	];

	/**
	 * @var \Jenssegers\Blade\Blade
	 */
	protected $blade;

	/**
	 * @var H5P
	 */
	protected $h5p;

	/***
	 * @var Phet
	 */
	protected $phet;

	/**
	 * @return Content
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Content $obj
	 */
	static public function hooks( Content $obj ) {

		// Iframes
		// Note to self: admins are not affected by kses
		add_filter( 'pre_kses', [ $obj, 'deleteIframesNotOnWhitelist' ], 1, 2 ); // Priority equals one because this should go first
		add_filter( 'wp_kses_allowed_html', [ $obj, 'allowIframesInHtml' ], 10, 2 );

		// Embeds
		// @see https://codex.wordpress.org/Embeds/
		add_action( 'init', [ $obj, 'registerEmbedHandlers' ] );
		add_filter( 'oembed_providers', [ $obj, 'addExtraOembedProviders' ] );
		add_action( 'save_post', [ $obj, 'deleteOembedCaches' ] );

		// Export hacks
		add_action( 'pb_pre_export', [ $obj, 'beforeExport' ] );
	}


	public function __construct() {
		$this->blade = Container::get( 'Blade' );
		$this->h5p = new H5P( $this->blade );
		$this->phet = new Phet( $this->blade );
	}

	/**
	 * Delete <iframe> sources not on our whitelist
	 * Content is expected to be raw, e.g. before the_content filters have been run
	 * Hooked into `pre_kses` filter

	 *
	 * @param string $content Content to run through kses.
	 * @param array $allowed_html Allowed HTML elements.
	 *
	 * @return string
	 */
	public function deleteIframesNotOnWhitelist( $content, $allowed_html ) {
		// Check if this is a post, bail if it isn't
		if ( ! is_array( $allowed_html ) ) {
			$allowed_html = [ $allowed_html ];
		}
		if ( in_array( 'post', $allowed_html, true ) === false ) {
			return $content;
		}
		// Check for iframe HTML code, bail if there isn't any
		if ( stripos( $content, '<iframe' ) === false ) {
			return $content;
		}

		/**
		 * @param array $value
		 *
		 * @since 5.1.0
		 */
		$whitelist = apply_filters( 'pb_whitelisted_domains', $this->whitelistedDomains );

		$changed = false;
		$doc = new HTML5();
		$dom = $doc->loadHTML( wpautop( $content ) );
		$elements = $dom->getElementsByTagName( 'iframe' );
		for ( $i = $elements->length; --$i >= 0; ) { // If you're deleting elements from within a loop, you need to loop backwards
			$iframe = $elements->item( $i );
			$src = $iframe->getAttribute( 'src' );
			$parse = wp_parse_url( $src );
			if ( ! in_array( $parse['host'], $whitelist, true ) ) {
				$iframe->parentNode->removeChild( $iframe );
				$changed = true;
			}
		}

		if ( ! $changed ) {
			// Nothing was changed, return as is
			return $content;
		} else {
			$s = $doc->saveHTML( $dom );
			$s = \Pressbooks\Sanitize\strip_container_tags( $s );
			$s = \Pressbooks\Sanitize\reverse_wpautop( $s );
			return $s;
		}
	}

	/**
	 * Replace <iframe> with standard text
	 * Content is expected to be rendered, e.g. after the_content filters have been run
	 * Hooked into `the_content` filter
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function replaceIframes( $content ) {
		// Check for iframe HTML code, bail if there isn't any
		if ( stripos( $content, '<iframe' ) === false ) {
			return $content;
		}

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		$doc = new HTML5();
		$dom = $doc->loadHTML( $content );

		$html = $this->blade->render(
			'interactive.shared', [
				'title' => $this->getTitle( $id ),
				'url' => get_permalink( $id ),
			]
		);
		$fragment = $doc->loadHTMLFragment( $html );

		$elements = $dom->getElementsByTagName( 'iframe' );
		for ( $i = $elements->length; --$i >= 0; ) {  // If you're deleting elements from within a loop, you need to loop backwards
			$iframe = $elements->item( $i );
			$iframe->parentNode->replaceChild( $dom->importNode( $fragment, true ), $iframe );
		}

		$s = $doc->saveHTML( $dom );
		$s = \Pressbooks\Sanitize\strip_container_tags( $s );
		return $s;
	}

	/**
	 * Add <iframe> to allowed post tags in wp_kses
	 *
	 * @param array $allowed
	 * @param string $context
	 *
	 * @return array
	 */
	public function allowIframesInHtml( $allowed, $context ) {
		if ( $context !== 'post' ) {
			return $allowed;
		}
		if ( current_user_can( 'publish_posts' ) === false ) {
			return $allowed;
		}
		$allowed['iframe'] = [
			'src' => true,
			'width' => true,
			'height' => true,
			'frameborder' => true,
			'marginwidth' => true,
			'marginheight' => true,
			'scrolling' => true,
			'title' => true,
		];

		return $allowed;
	}

	/**
	 * Filters the returned oEmbed HTML.
	 * Hooked into `oembed_dataparse` filter
	 *
	 * @param string $return The returned oEmbed HTML.
	 * @param object $data A data object result from an oEmbed provider. See Response Parameters in https://oembed.com/ specification
	 * @param string $url The URL of the content to be embedded.
	 *
	 * @return string
	 */
	public function replaceOembed( $return, $data, $url ) {

		// Check for iframe HTML code, bail if there isn't any
		if ( stripos( $return, '<iframe' ) === false ) {
			return $return;
		}

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		$title = $data->title ?? $this->getTitle( $id );
		$img_src = $data->thumbnail_url ?? null;
		$provider_name = $data->provider_name ?? null;
		$url = get_permalink( $id );

		$html = $this->blade->render(
			'interactive.oembed', [
				'title' => $title,
				'img_src' => $img_src,
				'provider_name' => $provider_name,
				'url' => $url,
			]
		);

		return $html;
	}

	/**
	 * Replace interactive tags such as <audio>, <video>
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function replaceInteractiveTags( $html ) {

		$tags = [
			'audio',
			'video',
		];

		// Check for HTML tags, bail if there isn't any
		$found = false;
		foreach ( $tags as $tag ) {
			if ( stripos( $html, "<{$tag}" ) !== false ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return $html;
		}

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		$doc = new HTML5();
		$dom = $doc->loadHTML( $html );
		foreach ( $tags as $tag ) {
			// Load blade template based on $tag
			$html = $this->blade->render(
				"interactive.{$tag}", [
					'title' => $this->getTitle( $id ),
					'url' => get_permalink( $id ),
				]
			);
			$fragment = $doc->loadHTMLFragment( $html );

			// Replace
			$elements = $dom->getElementsByTagName( $tag );
			for ( $i = $elements->length; --$i >= 0; ) {  // If you're deleting elements from within a loop, you need to loop backwards
				$iframe = $elements->item( $i );
				$iframe->parentNode->replaceChild( $dom->importNode( $fragment, true ), $iframe );
			}
		}

		$s = $doc->saveHTML( $dom );
		$s = \Pressbooks\Sanitize\strip_container_tags( $s );
		return $s;
	}

	/**
	 * Add oEmbed providers
	 * Hooked into `oembed_providers` filter
	 *
	 * @see \WP_oEmbed
	 * @see https://oembed.com/
	 * @see https://github.com/iamcal/oembed/tree/master/providers
	 *
	 * @param array $providers
	 *
	 * @return array
	 */
	public function addExtraOembedProviders( $providers ) {

		// Format (string), Provider (string), Regex (bool)
		$providers['#https?://mathembed\.com/latex\?inputText=.*#i'] = [ 'https://mathembed.com/oembed', true ];
		$providers['#https?://www\.openassessments\.org/assessments/.*#i'] = [ 'https://www.openassessments.org/oembed.json', true ];

		return $providers;
	}

	/**
	 * Delete all oEmbed caches
	 *
	 * @param int $post_id
	 */
	public function deleteOembedCaches( $post_id = 0 ) {
		if ( $post_id ) {
			global $wp_embed;
			$wp_embed->delete_oembed_caches( $post_id );
		} else {
			global $wpdb;
			$post_metas = $wpdb->get_results( "SELECT post_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '_oembed_%' " );
			foreach ( $post_metas as $post_meta ) {
				delete_post_meta( $post_meta->post_id, $post_meta->meta_key );
			}
		}
	}

	/**
	 * Is supported when cloning?
	 *
	 * @param string $content
	 *
	 * @return bool
	 */
	public function isCloneable( $content ) {

		// H5P not supported in cloning
		$tagnames = [ $this->h5p::SHORTCODE ];
		preg_match_all( '/' . get_shortcode_regex( $tagnames ) . '/', $content, $matches, PREG_SET_ORDER );
		if ( ! empty( $matches ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Replace unsupported content when cloning
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function replaceCloneable( $content ) {
		$content = $this->h5p->replaceCloneable( $content );
		return $content;
	}

	/**
	 * Used for sites that do not support oEmbed
	 *
	 * @see wp_embed_register_handler
	 */
	public function registerEmbedHandlers() {
		$this->phet->registerEmbedHandlerForWeb();
	}

	/**
	 * Hooked into `pb_pre_export` action
	 */
	public function beforeExport() {
		$this->overrideH5P();
		$this->overridePhet();
		$this->overrideIframes();
		$this->overrideEmbeds();
	}

	/**
	 * Override H5P
	 */
	protected function overrideH5P() {
		if ( $this->h5p->isActive() ) {
			$this->h5p->override();
		}
	}

	/**
	 * Override Phet
	 */
	protected function overridePhet() {
		wp_embed_unregister_handler( $this->phet::EMBED_ID );
		$this->phet->registerEmbedHandlerForExport();
	}

	/**
	 * Override any <iframe> code found in HTML
	 */
	protected function overrideIframes() {
		if ( self::$instance ) {
			remove_filter( 'pre_kses', [ self::$instance, 'deleteIframesNotOnWhitelist' ] );
			remove_filter( 'wp_kses_allowed_html', [ self::$instance, 'allowIframesInHtml' ] );
		}

		add_filter( 'the_content', [ $this, 'replaceIframes' ], 999 ); // Priority equals 999 because this should go last
	}

	/**
	 * Override WordPress Embeds
	 */
	protected function overrideEmbeds() {
		global $wp_embed;
		$wp_embed->usecache = false;
		add_filter( 'oembed_ttl', '__return_zero', 999 );
		add_filter( 'embed_defaults', function ( $args ) {
			// Embed cache keys are created by doing `md5( $url . serialize( $attr ) )`
			// By adding an HTML5 Data Attribute we change the MD5, thereby busting the cache when exporting
			$args['data-pb-export'] = 'true';
			return $args;
		} );
		add_filter( 'oembed_dataparse', [ $this, 'replaceOembed' ], 1, 3 );
	}

	/**
	 * @param int $post_id
	 *
	 * @return string
	 */
	protected function getTitle( $post_id ) {
		$title = get_the_title( $post_id );
		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' );
		}
		return $title;
	}

}
