<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Pressbooks\Book;
use Pressbooks\Modules\Import\Import;

class GoogleDocs extends Import {

	const TYPE_OF = 'google-docs';

	protected DocsMapper $mapper;
	protected ?DocsFetcher $fetcher = null;
	protected array $import_warnings = [];

	public function __construct() {
		$this->mapper = new DocsMapper();
	}

	function setCurrentImportOption( array $upload ): bool {
		if ( ! file_exists( $upload['file'] ) ) {
			return false;
		}

		$json = json_decode( file_get_contents( $upload['file'] ), true );
		if ( empty( $json ) || empty( $json['body']['content'] ) ) {
			return false;
		}

		$chapters_data = $this->mapper->toChapters( $json );
		$chapter_titles = [];
		foreach ( $chapters_data as $ch ) {
			$chapter_titles[] = $ch['title'];
		}

		if ( empty( $chapter_titles ) ) {
			$chapter_titles[] = '__UNKNOWN__';
		}

		$option = [
			'file'                => $upload['file'],
			'url'                 => $upload['url'] ?? null,
			'file_type'           => 'application/json',
			'type_of'             => self::TYPE_OF,
			'chapters'            => $chapter_titles,
			'post_types'          => [],
			'allow_parts'         => false,
			'default_post_status' => 'draft',
		];

		return update_option( 'pressbooks_current_import', $option );
	}

	function import( array $current_import ): bool {
		if ( ! file_exists( $current_import['file'] ) ) {
			return false;
		}

		// Set up the fetcher for image downloads
		try {
			$store = new CredentialsStore();
			$oauth = new OAuthClient( $store );
			$client = $oauth->getAuthedClient( get_current_user_id() );
			$this->fetcher = new DocsFetcher( $client );
		} catch ( \Exception $e ) {
			// Continue without image support
			$this->import_warnings[] = __( 'Could not authenticate with Google. Images will not be imported.', 'pressbooks' );
		}

		$json = json_decode( file_get_contents( $current_import['file'] ), true );
		if ( empty( $json ) ) {
			return false;
		}

		$chapters_data = $this->mapper->toChapters( $json );
		$chapter_parent = $this->getChapterParent();

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
			if ( ! $this->flaggedForImport( $id ) ) {
				continue;
			}

			if ( ! isset( $chapters_data[ $id ] ) ) {
				continue;
			}

			$ch = $chapters_data[ $id ];
			$html = $ch['body'] ?? '';

			$html = $this->processImages( $html, $ch['images'] ?? [] );
			$html = $this->tidy( $html );

			$post_type = $this->determinePostType( $id );
			$post_status = $current_import['default_post_status'] ?? 'draft';

			$new_post = [
				'post_title'   => wp_strip_all_tags( $ch['title'] ),
				'post_content' => $html,
				'post_type'    => $post_type,
				'post_status'  => $post_status,
			];

			if ( 'chapter' === $post_type ) {
				$new_post['post_parent'] = $chapter_parent;
			}

			$pid = wp_insert_post( add_magic_quotes( $new_post ) );
			if ( $pid && ! is_wp_error( $pid ) ) {
				update_post_meta( $pid, 'pb_show_title', 'on' );
				Book::consolidatePost( $pid, get_post( $pid ) );
			} else {
				$this->import_warnings[] = sprintf(
					__( 'Failed to import chapter: %s', 'pressbooks' ),
					$ch['title']
				);
			}

			if ( ! empty( $ch['warnings'] ) ) {
				foreach ( $ch['warnings'] as $type => $count ) {
					$this->import_warnings[] = sprintf( '%d %s skipped in "%s"', $count, str_replace( '_', ' ', $type ), $ch['title'] );
				}
			}
		}

		$this->revokeCurrentImport();

		return true;
	}

	protected function processImages( string $html, array $images ): string {
		if ( empty( $images ) || $this->fetcher === null ) {
			return $html;
		}

		foreach ( $images as $img ) {
			$placeholder = '#gdoc-image-' . $img['object_id'];
			$image_data = $this->fetcher->downloadImage( $img['content_uri'] );

			if ( $image_data === false ) {
				$html = str_replace( "src=\"{$placeholder}\"", 'src=""', $html );
				$this->import_warnings[] = sprintf(
					__( 'Could not download image: %s', 'pressbooks' ),
					$img['alt'] ?: $img['object_id']
				);
				continue;
			}

			$tmp_file = $this->createTmpFile();
			\Pressbooks\Utility\put_contents( $tmp_file, $image_data );

			$filename = 'gdoc-image-' . sanitize_file_name( $img['object_id'] ) . '.png';
			$filename = $this->properImageExtension( $tmp_file, $filename );

			$pid = media_handle_sideload( [
				'name'     => $filename,
				'tmp_name' => $tmp_file,
			], 0 );

			if ( is_wp_error( $pid ) ) {
				$this->import_warnings[] = sprintf(
					__( 'Could not sideload image: %s', 'pressbooks' ),
					$img['alt'] ?: $img['object_id']
				);
				$html = str_replace( "src=\"{$placeholder}\"", 'src=""', $html );
				continue;
			}

			if ( ! empty( $img['alt'] ) ) {
				update_post_meta( $pid, '_wp_attachment_image_alt', $img['alt'] );
			}
			if ( ! empty( $img['title'] ) ) {
				wp_update_post( [
					'ID'         => $pid,
					'post_title' => $img['title'],
				] );
			}

			$src = wp_get_attachment_url( $pid );
			if ( $src ) {
				$html = str_replace( "src=\"{$placeholder}\"", "src=\"{$src}\"", $html );
			}
		}

		return $html;
	}

	public function setFetcher( DocsFetcher $fetcher ): void {
		$this->fetcher = $fetcher;
	}

	public function getImportWarnings(): array {
		return $this->import_warnings;
	}
}
