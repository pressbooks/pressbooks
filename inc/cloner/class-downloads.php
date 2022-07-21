<?php
/**
 * Download images, videos, h5p...
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Cloner;

use function Pressbooks\Image\attachment_id_from_url;
use function Pressbooks\Image\strip_baseurl as image_strip_baseurl;
use function Pressbooks\Media\strip_baseurl as media_strip_baseurl;
use function Pressbooks\Utility\str_lreplace;

class Downloads {
	/**
	 * Regular expression for image extensions that Pressbooks knows how to resize, analyse, etc.
	 *
	 * @var string
	 */
	protected $pregSupportedImageExtensions = '/\.(jpe?g|gif|png)$/i';

	/**
	 * @var array
	 */
	protected $imageWasAlreadyDownloaded = [];

	/**
	 * @var array
	 */
	protected $mediaWasAlreadyDownloaded = [];

	/**
	 * @var array
	 */
	protected $H5PWasAlreadyDownloaded = [];

	/**
	 * @param Cloner $cloner
	 * @param \Pressbooks\Interactive\H5P $h5p
	 */
	public function __construct( protected $cloner, protected $h5p ) {
	}

	/**
	 * @return string
	 */
	public function getPregSupportedImageExtensions() {
		return $this->pregSupportedImageExtensions;
	}

	/**
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @see \Pressbooks\Cloner\Cloner::$knownMedia
	 * @return array{dom: \DOMDocument, attachments: int[]}
	 */
	public function scrapeAndKneadImages( \DOMDocument $dom ) {
		$images = $dom->getElementsByTagName( 'img' );
		$attachments = [];

		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			// Fetch image, change src
			$src_old = $image->getAttribute( 'src' );
			$attachment_id = $this->fetchAndSaveUniqueImage( $src_old );
			if ( $attachment_id === -1 ) {
				// Do nothing because image is not hosted on the source Pb network
			} elseif ( $attachment_id ) {
				$image->setAttribute( 'src', $this->replaceImage( $attachment_id, $src_old, $image ) );
				$attachments[] = $attachment_id;
			} else {
				// Tag broken image
				$image->setAttribute( 'src', "{$src_old}#fixme" );
			}
		}

		return [
			'dom' => $dom,
			'attachments' => $attachments,
		];
	}

	/**
	 * Load remote url of image into WP using media_handle_sideload()
	 * Will return -1 if image is not hosted on the source Pb network, or 0 if something went wrong.
	 *
	 * @param string $url
	 *
	 * @see \Pressbooks\Cloner\Cloner::$knownMedia
	 * @see media_handle_sideload
	 *
	 * @return int attachment ID, -1 if image is not hosted on the source Pb network, or 0 if import failed
	 */
	public function fetchAndSaveUniqueImage( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return 0;
		}
		if ( ! $this->sameAsSource( $url ) ) {
			return -1;
		}

		$known_media = $this->cloner->getKnownMedia();
		$filename = $this->basename( $url );
		$attached_file = image_strip_baseurl( $url );

		if ( isset( $known_media[ $attached_file ] ) ) {
			$remote_img_location = $known_media[ $attached_file ]->sourceUrl;
			$filename = basename( $remote_img_location );
		} else {
			$remote_img_location = $url;
		}

		if ( isset( $this->imageWasAlreadyDownloaded[ $remote_img_location ] ) ) {
			return $this->imageWasAlreadyDownloaded[ $remote_img_location ];
		}

		/* Process */

		if ( ! preg_match( $this->pregSupportedImageExtensions, $filename ) ) {
			// Unsupported image type
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = 0;
			return 0;
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = 0;
			return 0;
		}

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = \Pressbooks\Image\proper_image_extension( $tmp_name, $filename );
				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception ) {
				// Garbage, don't import
				$this->imageWasAlreadyDownloaded[ $remote_img_location ] = 0;
				@unlink( $tmp_name ); // @codingStandardsIgnoreLine
				return 0;
			}
		}

		$pid = media_handle_sideload(
			[
				'name' => $filename,
				'tmp_name' => $tmp_name,
			], 0
		);
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) {
			$pid = 0;
		} else {
			if ( isset( $known_media[ $attached_file ] ) ) {
				// Patch
				$m = $known_media[ $attached_file ];
				$request = new \WP_REST_Request( 'PATCH', "/wp/v2/media/{$pid}" );
				$request->set_body_params( $this->createMediaPatch( $m ) );
				$request->set_param( '_fields', 'id' );
				rest_do_request( $request );
				// Store a transitional state
				$this->cloner->createTransition( 'attachment', $m->id, $pid );
			}
			// Don't download the same file again
			$this->imageWasAlreadyDownloaded[ $remote_img_location ] = $pid;
		}
		@unlink( $tmp_name ); // @codingStandardsIgnoreLine

		return $pid;
	}

	/**
	 * @param $url
	 *
	 * @return bool
	 */
	public function sameAsSource( $url ) {
		return \Pressbooks\Utility\urls_have_same_host( $this->cloner->getSourceBookUrl(), $url );
	}

	/**
	 * Get sanitized basename without query string or anchors
	 *
	 * @param $url
	 *
	 * @return array|mixed|string
	 */
	public function basename( $url ) {
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );
		$filename = explode( '#', $filename )[0]; // Remove trailing anchors
		$filename = sanitize_file_name( urldecode( $filename ) );

		return $filename;
	}

	/**
	 * @param \Pressbooks\Entities\Cloner\Media $media
	 *
	 * @return array
	 */
	public function createMediaPatch( $media ) {
		return [
			'title' => $media->title,
			'meta' => $media->meta,
			'description' => $media->description,
			'caption' => $media->caption,
			'alt_text' => $media->altText,
		];
	}

	/**
	 * @param int $attachment_id
	 * @param string $src_old
	 * @param \DOMElement $image
	 *
	 * @see \Pressbooks\Cloner\Cloner::$knownMedia
	 *
	 * @return string
	 */
	public function replaceImage( $attachment_id, $src_old, $image ) {

		$src_new = wp_get_attachment_url( $attachment_id );
		$known_media = $this->cloner->getKnownMedia();

		if ( $this->sameAsSource( $src_old ) && isset( $known_media[ image_strip_baseurl( $src_old ) ] ) ) {
			$basename_old = $this->basename( $src_old );
			$basename_new = $this->basename( $src_new );
			$maybe_src_new = str_lreplace( $basename_new, $basename_old, $src_new );
			if ( $attachment_id === attachment_id_from_url( $maybe_src_new ) ) {
				// Our best guess is that this is a cloned image, use old filename to preserve WP resizing
				$src_new = $maybe_src_new;
				// Update image class to new id to preserve WP Size dropdown
				if ( $image->hasAttribute( 'class' ) ) {
					$image->setAttribute( 'class', preg_replace( '/wp-image-\d+/', "wp-image-{$attachment_id}", $image->getAttribute( 'class' ) ) );
				}
				// Update wrapper IDs
				if ( $image->parentNode->tagName === 'div' && str_contains( $image->parentNode->getAttribute( 'id' ), 'attachment_' ) ) {
					// <div> id
					$image->parentNode->setAttribute( 'id', preg_replace( '/attachment_\d+/', "attachment_{$attachment_id}", $image->parentNode->getAttribute( 'id' ) ) );
				}
				foreach ( $image->parentNode->childNodes as $child ) {
					if ( $child instanceof \DOMText &&
						str_contains( $child->nodeValue, '[caption ' ) &&
						str_contains( $child->nodeValue, 'attachment_' )
					) {
						// [caption] id
						$child->nodeValue = preg_replace( '/attachment_\d+/', "attachment_{$attachment_id}", $child->nodeValue );
					}
				}
			}
		}

		// Update srcset URLs
		if ( $image->hasAttribute( 'srcset' ) ) {
			$image->setAttribute( 'srcset', wp_get_attachment_image_srcset( $attachment_id ) );
		}

		return $src_new;
	}

	/**
	 * Parse HTML snippet, save all found media using media_handle_sideload(), return the HTML with changed URLs.
	 *
	 * Because we clone using WordPress raw format, we have to brute force against the text because the DOM
	 * can't see shortcodes, text urls, hrefs with no identifying info, etc.
	 *
	 * @param \DOMDocument $dom
	 * @param \Masterminds\HTML5 $html5
	 *
	 * @see \Pressbooks\Cloner\Cloner::$knownMedia
	 *
	 * @return array{dom: \DOMDocument, attachments: int[]}
	 */
	public function scrapeAndKneadMedia( \DOMDocument $dom, $html5 ) {

		$known_media = $this->cloner->getKnownMedia();
		$dom_as_string = $html5->saveHTML( $dom );
		$dom_as_string = \Pressbooks\Sanitize\strip_container_tags( $dom_as_string );

		$attachments = [];
		$changed = false;
		foreach ( $known_media as $alt => $media ) {
			if ( preg_match( $this->pregSupportedImageExtensions, $this->basename( $media->sourceUrl ) ) ) {
				// Skip images, these are handled elsewhere
				continue;
			}
			if ( str_contains( $dom_as_string, $media->sourceUrl ) ) {
				$src_old = $media->sourceUrl;
				$attachment_id = $this->fetchAndSaveUniqueMedia( $src_old );
				if ( $attachment_id === -1 ) {
					// Do nothing because media is not hosted on the source Pb network
				} elseif ( $attachment_id ) {
					$dom_as_string = str_replace( $src_old, wp_get_attachment_url( $attachment_id ), $dom_as_string );
					$attachments[] = $attachment_id;
					$changed = true;
				} else {
					// Tag broken media
					$dom_as_string = str_replace( $src_old, "{$src_old}#fixme", $dom_as_string );
					$changed = true;
				}
			}
		}

		return [
			'dom' => $changed ? $html5->loadHTML( $dom_as_string ) : $dom,
			'attachments' => $attachments,
		];
	}

	/**
	 * Load remote media into WP using media_handle_sideload()
	 * Will return -1 if media is not hosted on the source Pb network, or 0 if something went wrong.
	 *
	 * @param string $url
	 *
	 * @see \Pressbooks\Cloner\Cloner::$knownMedia
	 * @see media_handle_sideload
	 *
	 * @return int attachment ID, -1 if media is not hosted on the source Pb network, or 0 if import failed
	 */
	public function fetchAndSaveUniqueMedia( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return 0;
		}
		if ( ! $this->sameAsSource( $url ) ) {
			return -1;
		}

		$known_media = $this->cloner->getKnownMedia();
		$filename = $this->basename( $url );
		$attached_file = media_strip_baseurl( $url );

		if ( isset( $known_media[ $attached_file ] ) ) {
			$remote_media_location = $known_media[ $attached_file ]->sourceUrl;
			$filename = basename( $remote_media_location );
		} else {
			$remote_media_location = $url;
		}

		if ( isset( $this->mediaWasAlreadyDownloaded[ $remote_media_location ] ) ) {
			return $this->mediaWasAlreadyDownloaded[ $remote_media_location ];
		}

		/* Process */

		$tmp_name = download_url( $remote_media_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$this->mediaWasAlreadyDownloaded[ $remote_media_location ] = 0;
			return 0;
		}

		$pid = media_handle_sideload(
			[
				'name' => $filename,
				'tmp_name' => $tmp_name,
			], 0
		);
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) {
			$pid = 0;
		} else {
			if ( isset( $known_media[ $attached_file ] ) ) {
				// Patch
				$m = $known_media[ $attached_file ];
				$request = new \WP_REST_Request( 'PATCH', "/wp/v2/media/{$pid}" );
				$request->set_body_params( $this->createMediaPatch( $m ) );
				$request->set_param( '_fields', 'id' );
				rest_do_request( $request );
				// Store a transitional state
				$this->cloner->createTransition( 'attachment', $m->id, $pid );
			}
			// Don't download the same file again
			$this->mediaWasAlreadyDownloaded[ $remote_media_location ] = $pid;
		}
		@unlink( $tmp_name ); // @codingStandardsIgnoreLine

		return $pid;
	}

	/**
	 * Parse HTML snippet, download all found H5P, save transition states to change shortcodes in post processing step
	 *
	 * @param string $content
	 *
	 * @see \Pressbooks\Cloner\Cloner::$knownH5P
	 *
	 * @return array
	 */
	public function h5p( $content ) {
		$known_h5p = $this->cloner->getKnownH5P();
		$new_h5p_ids = [];
		$h5p_ids = $this->h5p->findAllShortcodeIds( $content );
		foreach ( $h5p_ids as $h5p_id ) {
			if ( ! isset( $this->H5PWasAlreadyDownloaded[ $h5p_id ] ) ) {
				foreach ( $known_h5p as $h5p ) {
					if ( absint( $h5p->id ) === absint( $h5p_id ) ) {
						$new_h5p_id = $this->h5p->fetch( $h5p->url );
						if ( $new_h5p_id ) {
							$new_h5p_ids[] = $new_h5p_id;
							$this->cloner->createTransition( 'h5p', $h5p_id, $new_h5p_id );
							$this->H5PWasAlreadyDownloaded[ $h5p_id ] = $new_h5p_id;
						} else {
							// An id of 0 means there was a problem. We have to replace the broken H5P shortcode with a warning
							$new_h5p_ids[] = "#fixme{$h5p->id}";
						}
						continue 2;
					}
				}
			}
		}
		return $new_h5p_ids;
	}

}
