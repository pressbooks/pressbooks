<?php

namespace Pressbooks\Interactive;

/**
 * PHET HTML5
 */
class Phet {

	public const EMBED_ID = 'phet_html5';

	public const EMBED_URL_REGEX = '#https?://phet\.colorado\.edu/sims/html/(.+)/?#i';

	protected $iframes_counter = [];

	/**
	 * @param \Jenssegers\Blade\Blade $blade
	 */
	public function __construct( protected $blade ) {
	}

	/**
	 * Register embed handler for web
	 */
	public function registerEmbedHandlerForWeb() {
		wp_embed_register_handler(
			self::EMBED_ID,
			self::EMBED_URL_REGEX,
			[ $this, 'applyEmbedFilterForWeb' ]
		);
	}

	/**
	 * Apply Embed filter for web
	 *
	 * @param $matches
	 * @param $attr
	 * @param $url
	 * @param $rawattr
	 * @return mixed|void
	 */
	public function applyEmbedFilterForWeb( $matches, $attr, $url, $rawattr ) {
		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]
		$this->setIframeCounterByPost( $id );
		$embed = sprintf(
			'<iframe id="iframe-phet-%2$s" src="https://phet.colorado.edu/sims/html/%1$s" width="800" height="600" scrolling="no" allowfullscreen></iframe>',
			esc_attr( $matches[1] ),
			$this->iframes_counter[ $id ]
		);
		apply_filters( 'embed_' . self::EMBED_ID, $embed, $matches, $attr, $url, $rawattr );
		return $embed;
	}

	/**
	 * Increment iframe counter property for the given post.
	 *
	 * @param $id int
	 */
	public function setIframeCounterByPost( $id ) {
		if ( isset( $this->iframes_counter[ $id ] ) ) {
			$this->iframes_counter[ $id ] ++;
		} else {
			$this->iframes_counter[ $id ] = 1;
		}
	}

	/**
	 * Register embed handler for exports
	 */
	public function registerEmbedHandlerForExport() {
		wp_embed_register_handler(
			self::EMBED_ID,
			self::EMBED_URL_REGEX,
			[ $this, 'applyEmbedFilterForExport' ]
		);
	}

	/**
	 * Apply embed filter for export
	 *
	 * @param $matches
	 * @param $attr
	 * @param $url
	 * @param $rawattr
	 * @return mixed|void
	 */
	public function applyEmbedFilterForExport( $matches, $attr, $url, $rawattr ) {
		global $id;
		$this->setIframeCounterByPost( $id );
		$embed = $this->blade->render(
			'interactive.media', [
				'title' => get_the_title( $id ),
				'url' => wp_get_shortlink( $id ) . '#iframe-phet-' . $this->iframes_counter[ $id ],
				'tag' => 'oembed',
			]
		);
		apply_filters( 'embed_' . self::EMBED_ID, $embed, $matches, $attr, $url, $rawattr );
		return $embed;
	}

}
