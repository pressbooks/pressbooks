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
		add_filter( 'pre_kses', [ $obj, 'deleteIframesNotOnWhitelist' ], 10, 2 );
		add_filter( 'wp_kses_allowed_html', [ $obj, 'allowIframesInHtml' ], 10, 2 );
		add_filter( 'oembed_providers', [ $obj, 'addExtraOembedProviders' ] );
		add_action( 'init', [ $obj, 'registerEmbedHandlers' ] );
		add_action( 'pb_pre_export', [ $obj, 'beforeExport' ] );
	}


	public function __construct() {
		$this->blade = Container::get( 'Blade' );
		$this->h5p = new H5P( $this->blade );
		$this->phet = new Phet( $this->blade );
	}

	/**
	 * Delete <iframe> sources not on our whitelist
	 * Hooked into `pre_kses`
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

		$changed = false;
		$doc = new HTML5();
		$dom = $doc->loadHTML( wpautop( $content ) );
		$elements = $dom->getElementsByTagName( 'iframe' );
		for ( $i = $elements->length; --$i >= 0; ) { // If you're deleting elements from within a loop, you need to loop backwards
			$iframe = $elements->item( $i );
			$src = $iframe->getAttribute( 'src' );
			$parse = wp_parse_url( $src );
			if ( ! in_array( $parse['host'], $this->whitelistedDomains, true ) ) {
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
			// reverse wpautop
			$s = str_replace( "\n", '', $s );
			$s = str_replace( '<p>', '', $s );
			$s = str_replace( [ '<br />', '<br>', '<br/>' ], "\n", $s );
			$s = str_replace( '</p>', "\n\n", $s );
			return $s;
		}
	}

	/**
	 * Replace <iframe> with standard text
	 * Hooked into `the_content`
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function replaceIframesWithStandardText( $content ) {
		// Check for iframe HTML code, bail if there isn't any
		if ( stripos( $content, '<iframe' ) === false ) {
			return $content;
		}

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]

		$doc = new HTML5();
		$dom = $doc->loadHTML( wpautop( $content ) );

		$html = $this->blade->render(
			'interactive.shared', [
				'title' => wp_strip_all_tags( get_the_title( $id ) ),
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
	 * Add oEmbed providers
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
		// TODO
		return $providers;
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
		add_filter( 'the_content', [ $this, 'replaceIframesWithStandardText' ], 999 );
	}

}
