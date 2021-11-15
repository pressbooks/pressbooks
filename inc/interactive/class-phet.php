<?php

namespace Pressbooks\Interactive;

/**
 * PHET HTML5
 */
class Phet {

	const EMBED_ID = 'phet_html5';

	const EMBED_URL_REGEX = '#https?://phet\.colorado\.edu/sims/html/(.+)/?#i';

	/**
	 * @var \Jenssegers\Blade\Blade
	 */
	protected $blade;

	protected $iframes_counter = [];

	/**
	 * @param \Jenssegers\Blade\Blade $blade
	 */
	public function __construct( $blade ) {
		$this->blade = $blade;
	}

	/**
	 * Register embed handler for web
	 */
	public function registerEmbedHandlerForWeb() {
		wp_embed_register_handler(
			self::EMBED_ID,
			self::EMBED_URL_REGEX,
			function ( $matches, $attr, $url, $rawattr ) {
				global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]
				if ( isset( $this->iframes_counter[ $id ] ) ) {
					$this->iframes_counter[ $id ] ++;
				} else {
					$this->iframes_counter[ $id ] = 1;
				}
				$embed = sprintf(
					'<iframe id="iframe-phet-%2$s" src="https://phet.colorado.edu/sims/html/%1$s" width="800" height="600" scrolling="no" allowfullscreen></iframe>',
					esc_attr( $matches[1] ),
					$this->iframes_counter[ $id ]
				);
				return apply_filters( 'embed_' . self::EMBED_ID, $embed, $matches, $attr, $url, $rawattr );
			}
		);
	}

	/**
	 * Register embed handler for exports
	 */
	public function registerEmbedHandlerForExport() {
		wp_embed_register_handler(
			self::EMBED_ID,
			self::EMBED_URL_REGEX,
			function ( $matches, $attr, $url, $rawattr ) {
				global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]
				if ( isset( $this->iframes_counter[ $id ] ) ) {
					$this->iframes_counter[ $id ] ++;
				} else {
					$this->iframes_counter[ $id ] = 1;
				}
				$embed = $this->blade->render(
					'interactive.media', [
						'title' => get_the_title( $id ),
						'url' => wp_get_shortlink( $id ),
						'tag' => 'iframe',
						'id' => 'iframe-phet-' . $this->iframes_counter[ $id ],
					]
				);
				return apply_filters( 'embed_' . self::EMBED_ID, $embed, $matches, $attr, $url, $rawattr );
			}
		);
	}

}
