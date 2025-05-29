<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\ThinCC;

use DOMDocument;
use function Pressbooks\Utility\put_contents;
use function Pressbooks\Utility\rmrdir;
use Generator;
use Pressbooks\Book;
use Pressbooks\Container;
use Pressbooks\Modules\Export\Export;
use RecursiveIteratorIterator;

class WebLinks extends Export {

	/**
	 * @var string
	 */
	protected string $version = '1.1';

	/**
	 * @var string
	 */
	protected string $suffix = '_1_1_weblinks.imscc';

	/**
	 * Temporary directory used to build Common Cartridge, no trailing slash!
	 *
	 * @var string
	 */
	protected string $tmpDir;

	/**
	 * @var string
	 */
	protected string $errorLog = '';

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {
		if ( ! class_exists( '\PclZip' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
		}
		$this->tmpDir = $this->createTmpDir();
	}

	/**
	 * Delete temporary directory when done.
	 */
	function __destruct() {
		$this->deleteTmpDir();
	}

	/**
	 * @return string
	 */
	public function getTmpDir(): string {
		return $this->tmpDir;
	}

	/**
	 * Delete temporary directory
	 */
	public function deleteTmpDir(): void {
		// Cleanup temporary directory, if any
		if ( ! empty( $this->tmpDir ) ) {
			rmrdir( $this->tmpDir );
		}
	}

	/**
	 *
	 */
	public function createManifest(): void {
		$metadata = Book::getBookInformation();
		$data = [
			'lang' => ! empty( $metadata['pb_language'] ) ? $metadata['pb_language'] : 'en-US',
			'course_name' => $metadata['pb_title'] ?? '',
			'course_description' => $metadata['pb_about_50'] ?? $metadata['pb_about_140'] ?? $metadata['pb_about_unlimited'] ?? '',
			'organization_items' => $this->identifiers(),
			'resources' => $this->resources(),
		];
		$xml = $this->render( 'manifest', $data );
		$xml = $this->formatXML( $xml, 'imsmanifest.xml' );

		put_contents(
			$this->tmpDir . '/imsmanifest.xml',
			$xml
		);
	}

	/**
	 * @return string
	 */
	public function identifiers(): string {
		$xml = '';
		$struct = Book::getBookStructure();

		// Front Matter
		$fm_xml = '';
		foreach ( $struct['front-matter'] as $k => $v ) {
			$fm_xml = $this->getStr( $v, $fm_xml );
		}
		if ( ! empty( $fm_xml ) ) {
			$xml .= '<item identifier="frontmatter">';
			$xml .= '<title>Front Matter</title>';
			$xml .= $fm_xml;
			$xml .= '</item>';
		}

		// Parts & Chapters
		foreach ( $struct['part'] as $key => $value ) {
			$ch_xml = '';
			if ( $this->showInWeb( $value['post_status'] ) && $value['has_post_content'] ) {
				$ch_xml .= '<item identifier="' . $this->identifier( $value['ID'], 'I_' ) . '" identifierref="' . $this->identifier( $value['ID'] ) . '">';
				$ch_xml .= '<title>' . $value['post_title'] . '</title>';
				$ch_xml .= '</item>';
			}
			foreach ( $value['chapters'] as $k => $v ) {
				$ch_xml = $this->getStr( $v, $ch_xml );
			}
			if ( ! empty( $ch_xml ) ) {
				$xml .= '<item identifier="' . $this->identifier( $value['ID'], 'IM_' ) . '">';
				$xml .= '<title>' . $value['post_title'] . '</title>';
				$xml .= $ch_xml;
				$xml .= '</item>';
			}
		}

		// Back Matter
		$bm_xml = '';
		foreach ( $struct['back-matter'] as $k => $v ) {
			$bm_xml = $this->getStr( $v, $bm_xml );
		}
		if ( ! empty( $bm_xml ) ) {
			$xml .= '<item identifier="backmatter">';
			$xml .= '<title>Back Matter</title>';
			$xml .= $bm_xml;
			$xml .= '</item>';
		}

		return $xml;
	}

	/**
	 * @return string
	 */
	public function resources(): string {
		$xml = '';
		$links = $this->getExports();
		foreach ( $links as $id => $title ) {
			$xml .= '<resource identifier="' . $this->identifier( $id ) . '" type="' . $this->getResourceType( $id, $title ) . '">';
			$xml .= '<file href="' . $this->identifier( $id ) . '.xml"/>';
			$xml .= '</resource>';

		}
		return $xml;
	}

	/**
	 *
	 */
	public function createResources(): void {
		$links = $this->getExports();
		foreach ( $links as $id => $title ) {
			$view = $this->getView( $id, $title );
			$data = $this->getData( $id, $title, $view );
			$file = $this->identifier( $id ) . '.xml';
			$xml = $this->render( $view, $data );
			$xml = $this->formatXML( $xml, $file );
			put_contents(
				$this->tmpDir . "/$file",
				$xml
			);
		}
	}

	/**
	 * @param $filename
	 *
	 * @return bool
	 */
	public function zip( $filename ): bool {
		$zip = new \PclZip( $filename );
		$files = [];
		foreach ( new RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->tmpDir ) ) as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$files[] = $file->getPathname();
		}
		$list = $zip->add( $files, '', $this->tmpDir );
		if ( 0 === absint( $list ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @return array
	 */
	public function getExports(): array {

		$links = [];
		$struct = Book::getBookStructure();

		foreach ( $struct['front-matter'] as $k => $v ) {
			if ( $this->showInWeb( $v['post_status'] ) ) {
				$links[ $v['ID'] ] = $v['post_title'];
			}
		}
		foreach ( $struct['part'] as $key => $value ) {
			if ( $this->showInWeb( $value['post_status'] ) && $value['has_post_content'] ) {
				$links[ $value['ID'] ] = $value['post_title'];
			}
			foreach ( $value['chapters'] as $k => $v ) {
				if ( $this->showInWeb( $v['post_status'] ) ) {
					$links[ $v['ID'] ] = $v['post_title'];
				}
			}
		}
		foreach ( $struct['back-matter'] as $k => $v ) {
			if ( $this->showInWeb( $v['post_status'] ) ) {
				$links[ $v['ID'] ] = $v['post_title'];
			}
		}

		return $links;
	}

	/**
	 * Get array for Blade template view
	 *
	 * @param int $id
	 * @param string $title
	 * @param string $view
	 *
	 * @return array
	 */
	public function getData( $id, $title, $view ): array {
		return [
			'title' => $title,
			'url' => wp_get_shortlink( $id ),
		];
	}

	/**
	 * Render a Blade template
	 *
	 * @param string $view
	 * @param array $data
	 *
	 * @return string
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function render( $view, $data ): string {
		$version = str_replace( '.', '_', $this->version );
		return Container::get( 'Blade' )->render( "thincc.{$version}.{$view}", $data );
	}

	/**
	 * Get name of Blade template view
	 *
	 * @param int $post_id
	 * @param string $title
	 *
	 * @return string
	 */
	public function getView( $post_id, $title ): string {
		return 'web_link';
	}

	/**
	 * @param int $post_id
	 * @param string $title
	 *
	 * @return string
	 */
	public function getResourceType( $post_id, $title ): string {
		return 'imswl_xmlv1p1';
	}

	/**
	 * @param int $post_id
	 * @param string $prefix
	 *
	 * @return string
	 */
	public function identifier( $post_id, $prefix = 'R_' ): string {
		return $prefix . get_current_blog_id() . '_' . $post_id;
	}

	/**
	 * @param string $xml
	 * @param string $error_log_prefix (optional)
	 *
	 * @return string
	 */
	public function formatXML( $xml, $error_log_prefix = '' ): string {
		$use_errors = libxml_use_internal_errors( true );
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML( $xml );
		$dom->formatOutput = true;

		$xml = $dom->saveXML();

		$errors = libxml_get_errors();
		if ( ! empty( $errors ) ) {
			if ( ! empty( $error_log_prefix ) ) {
				$this->errorLog .= "### {$error_log_prefix} ### \n";
			}
			foreach ( $errors as $error ) {
				$this->errorLog .= $error->message . "\n";
			}
		}
		libxml_clear_errors();
		libxml_use_internal_errors( $use_errors );

		return ( $xml !== false ? $xml : '' );
	}

	/**
	 * @see https://pressbooks.org/blog/2018/01/17/pressbooks-5-developer-guide/#content-visibility
	 *
	 * @param string $post_status
	 *
	 * @return bool @bool
	 */
	public function showInWeb( $post_status ): bool {
		$visibility = [
			'web-only',
			'publish',
		];
		return in_array( $post_status, $visibility, true );
	}

	function convertGenerator(): Generator {
		if ( empty( $this->tmpDir ) || ! is_dir( $this->tmpDir ) ) {
			$this->logError( '$this->tmpDir must be set before calling convert().' );
			yield 'error' => '$this->tmpDir must be set before calling convert().';
			return false;
		}

		yield 35 => __( 'Creating resources...', 'pressbooks' );
		try {
			$this->createResources();
			yield 50 => __( 'Creating manifest...', 'pressbooks' );
			$this->createManifest();
		} catch ( \Exception $e ) {
			$this->logError( $e->getMessage() );
			yield 'error' => $e->getMessage();
			return false;
		}

		yield 65 => __( 'Creating archive...', 'pressbooks' );
		$filename = $this->timestampedFileName( $this->suffix );
		if ( ! $this->zip( $filename ) ) {
			yield 'error' => __( 'Failed to create archive.', 'pressbooks' );
			return false;
		}
		$this->outputPath = $filename;
		yield 80 => __( 'Done!', 'pressbooks' );
		return true;
	}

	public function convert(): Generator {
		$use_errors = libxml_use_internal_errors( true );
		yield 90 => __( 'Starting validation...', 'pressbooks' );

		$files = new RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->tmpDir ) );
		$total_files = iterator_count( $files );
		$files->rewind();

		$current = 0;
		foreach ( $files as $file ) {
			if ( $file->isFile() ) {
				$current++;
				$xml = simplexml_load_file( $file );
				if ( false === $xml ) {
					$this->errorLog .= "### {$file} ### \n";
					foreach ( libxml_get_errors() as $error ) {
						$this->errorLog .= $error->message . "\n";
					}
				}
			}
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $use_errors );

		if ( ! empty( $this->errorLog ) ) {
			$this->logError( $this->errorLog );
			yield 100 => __( 'Validation failed.', 'pressbooks' );
			return false;
		}

		yield 100 => __( 'Validation completed successfully.', 'pressbooks' );
		return true;
	}

	public function validate(): Generator {
		yield 90 => __( 'Validating Web Links export.', 'pressbooks' );
		if ( ! is_dir( $this->tmpDir ) || ! is_readable( $this->tmpDir ) ) {
			$this->logError( '$this->tmpDir must be set before calling validate().' );
			yield 'error' => '$this->tmpDir must be set before calling validate().';
			return false;
		}

		if ( ! is_file( $this->tmpDir . '/imsmanifest.xml' ) ) {
			$this->logError( 'Manifest file not found.' );
			yield 'error' => __( 'Manifest file not found.', 'pressbooks' );
			return false;
		}

		yield 100 => __( 'Web Links export validation successful.', 'pressbooks' );
		return true;
	}

	/**
	 * @param mixed $v
	 * @param string $fm_xml
	 * @return string
	 */
	public function getStr( mixed $v, string $fm_xml ): string {
		if ( $this->showInWeb( $v['post_status'] ) ) {
			$fm_xml .= '<item identifier="' . $this->identifier( $v['ID'], 'I_' ) . '" identifierref="' . $this->identifier( $v['ID'] ) . '">';
			$fm_xml .= '<title>' . $v['post_title'] . '</title>';
			$fm_xml .= '</item>';
		}
		return $fm_xml;
	}
}
