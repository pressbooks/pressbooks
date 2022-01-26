<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\ThinCC;

use Pressbooks\Book;
use Pressbooks\Modules\Export\Export;

class WebLinks extends Export {

	/**
	 * @var string
	 */
	protected $version = '1.1';

	/**
	 * @var string
	 */
	protected $suffix = '_1_1_weblinks.imscc';

	/**
	 * Temporary directory used to build Common Cartridge, no trailing slash!
	 *
	 * @var string
	 */
	protected $tmpDir;

	/**
	 * @var string
	 */
	protected $errorLog = '';

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
	public function getTmpDir() {
		return $this->tmpDir;
	}

	/**
	 * Mandatory convert method, create $this->outputPath
	 *
	 * @return bool
	 */
	public function convert() {
		if ( empty( $this->tmpDir ) || ! is_dir( $this->tmpDir ) ) {
			$this->logError( '$this->tmpDir must be set before calling convert().' );
			return false;
		}

		try {
			$this->createResources();
			$this->createManifest();
		} catch ( \Exception $e ) {
			$this->logError( $e->getMessage() );
			return false;
		}

		$filename = $this->timestampedFileName( $this->suffix );
		if ( ! $this->zip( $filename ) ) {
			return false;
		}
		$this->outputPath = $filename;
		return true;
	}

	/**
	 * Mandatory validate method, check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	public function validate() {
		$use_errors = libxml_use_internal_errors( true );
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->tmpDir ) ) as $file ) {
			if ( $file->isFile() ) {
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
			return false;
		}

		return true;
	}

	/**
	 * Delete temporary directory
	 */
	public function deleteTmpDir() {
		// Cleanup temporary directory, if any
		if ( ! empty( $this->tmpDir ) ) {
			\Pressbooks\Utility\rmrdir( $this->tmpDir );
		}
	}

	/**
	 *
	 */
	public function createManifest() {
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

		\Pressbooks\Utility\put_contents(
			$this->tmpDir . '/imsmanifest.xml',
			$xml
		);
	}

	/**
	 * @return string
	 */
	public function identifiers() {
		$xml = '';
		$struct = Book::getBookStructure();

		// Front Matter
		$fm_xml = '';
		foreach ( $struct['front-matter'] as $k => $v ) {
			if ( $this->showInWeb( $v['post_status'] ) ) {
				$fm_xml .= '<item identifier="' . $this->identifier( $v['ID'], 'I_' ) . '" identifierref="' . $this->identifier( $v['ID'] ) . '">';
				$fm_xml .= '<title>' . $v['post_title'] . '</title>';
				$fm_xml .= '</item>';
			}
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
				if ( $this->showInWeb( $v['post_status'] ) ) {
					$ch_xml .= '<item identifier="' . $this->identifier( $v['ID'], 'I_' ) . '" identifierref="' . $this->identifier( $v['ID'] ) . '">';
					$ch_xml .= '<title>' . $v['post_title'] . '</title>';
					$ch_xml .= '</item>';
				}
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
			if ( $this->showInWeb( $v['post_status'] ) ) {
				$bm_xml .= '<item identifier="' . $this->identifier( $v['ID'], 'I_' ) . '" identifierref="' . $this->identifier( $v['ID'] ) . '">';
				$bm_xml .= '<title>' . $v['post_title'] . '</title>';
				$bm_xml .= '</item>';
			}
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
	public function resources() {
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
	public function createResources() {
		$links = $this->getExports();
		foreach ( $links as $id => $title ) {
			$view = $this->getView( $id, $title );
			$data = $this->getData( $id, $title, $view );
			$file = $this->identifier( $id ) . '.xml';
			$xml = $this->render( $view, $data );
			$xml = $this->formatXML( $xml, $file );
			\Pressbooks\Utility\put_contents(
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
	public function zip( $filename ) {
		$zip = new \PclZip( $filename );
		$files = [];
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->tmpDir ) ) as $file ) {
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
	public function getExports() {

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
	public function getData( $id, $title, $view ) {

		$data = [
			'title' => $title,
			'url' => wp_get_shortlink( $id ),
		];

		return $data;
	}

	/**
	 * Render a Blade template
	 *
	 * @param string $view
	 * @param array $data
	 *
	 * @return string
	 */
	public function render( $view, $data ) {
		$version = str_replace( '.', '_', $this->version );
		return \Pressbooks\Container::get( 'Blade' )->render( "thincc.{$version}.{$view}", $data );
	}

	/**
	 * Get name of Blade template view
	 *
	 * @param int $post_id
	 * @param string $title
	 *
	 * @return string
	 */
	public function getView( $post_id, $title ) {
		return 'web_link';
	}

	/**
	 * @param int $post_id
	 * @param string $title
	 *
	 * @return string
	 */
	public function getResourceType( $post_id, $title ) {
		return 'imswl_xmlv1p1';
	}

	/**
	 * @param int $post_id
	 * @param string $prefix
	 *
	 * @return string
	 */
	public function identifier( $post_id, $prefix = 'R_' ) {
		return $prefix . get_current_blog_id() . '_' . $post_id;
	}

	/**
	 * @param string $xml
	 * @param string $error_log_prefix (optional)
	 *
	 * @return string
	 */
	public function formatXML( $xml, $error_log_prefix = '' ) {
		$use_errors = libxml_use_internal_errors( true );
		$dom = new \DOMDocument;
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
	 * @return @bool
	 */
	public function showInWeb( $post_status ) {
		$visibility = [
			'web-only',
			'publish',
		];
		return in_array( $post_status, $visibility, true );
	}

}
