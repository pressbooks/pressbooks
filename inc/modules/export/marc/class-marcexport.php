<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Marc;

use Pressbooks\Book;

/**
 * MARC21 export functionality
 */
class MarcExport {

	/**
	 * @var MarcExport
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return MarcExport
	 */
	public static function init(): MarcExport {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}

		return self::$instance;
	}

	/**
	 * Set up hooks
	 *
	 * @param MarcExport $obj
	 */
	public static function hooks( MarcExport $obj ): void {
		// Add metabox to Book Information page
		add_action( 'add_meta_boxes', [ $obj, 'addMetabox' ] );

		// Handle download requests
		add_action( 'admin_post_pb_download_marc', [ $obj, 'handleMarcDownload' ] );
		add_action( 'admin_post_pb_download_bibtex', [ $obj, 'handleBibtexDownload' ] );
	}

	/**
	 * Add metabox to Book Information page
	 */
	public function addMetabox(): void {
		$metadata = new \Pressbooks\Metadata();
		$post_id = $metadata->getMetaPostId();

		if ( ! $post_id ) {
			return;
		}

		add_meta_box(
			'pressbooks-marc-export',
			__( 'Bibliographic Metadata Export', 'pressbooks' ),
			[ $this, 'renderMetabox' ],
			'metadata',
			'side',
			'default'
		);
	}

	/**
	 * Render metabox content
	 *
	 * @param \WP_Post $post
	 */
	public function renderMetabox( \WP_Post $post ): void {
		// Get book information to check if we have enough data
		$metadata = Book::getBookInformation();

		$has_title = ! empty( $metadata['pb_title'] );
		$has_author = ! empty( $metadata['pb_authors'] ) && is_array( $metadata['pb_authors'] );

		if ( ! $has_title && ! $has_author ) {
			echo '<p>' . esc_html__( 'Please add at least a title to export MARC metadata.', 'pressbooks' ) . '</p>';
			return;
		}

		// Generate download URLs
		$marc_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=pb_download_marc' ),
			'pb_download_marc'
		);
		$bibtex_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=pb_download_bibtex' ),
			'pb_download_bibtex'
		);

		?>
		<div class="pb-marc-export">
			<p><?php esc_html_e( 'Download bibliographic metadata in standard formats.', 'pressbooks' ); ?></p>
			
			<p>
				<a href="<?php echo esc_url( $marc_url ); ?>" class="button button-primary button-large" style="width: 100%; margin-bottom: 8px;">
					<?php esc_html_e( 'Download MARC XML', 'pressbooks' ); ?>
				</a>
			</p>
			
			<p>
				<a href="<?php echo esc_url( $bibtex_url ); ?>" class="button button-secondary button-large" style="width: 100%;">
					<?php esc_html_e( 'Download BibTeX', 'pressbooks' ); ?>
				</a>
			</p>

			<details style="margin-top: 10px;">
				<summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">
					<?php esc_html_e( 'About These Formats', 'pressbooks' ); ?>
				</summary>
				<div style="font-size: 12px; line-height: 1.5; margin-bottom: 10px;">
					<strong><?php esc_html_e( 'MARC21', 'pressbooks' ); ?></strong>
					<p style="margin: 4px 0;">
						<?php esc_html_e( 'Standard format used by libraries and cataloging services worldwide for bibliographic data.', 'pressbooks' ); ?>
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: Link to MARC21 documentation */
								__( '<a href="%s" target="_blank">More info</a>', 'pressbooks' ),
								'https://www.loc.gov/marc/bibliographic/'
							),
							[ 'a' => [ 'href' => [], 'target' => [] ] ]
						);
						?>
					</p>
				</div>
				<div style="font-size: 12px; line-height: 1.5;">
					<strong><?php esc_html_e( 'BibTeX', 'pressbooks' ); ?></strong>
					<p style="margin: 4px 0;">
						<?php esc_html_e( 'Reference format widely used in academic publishing, especially in LaTeX documents.', 'pressbooks' ); ?>
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: Link to BibTeX documentation */
								__( '<a href="%s" target="_blank">More info</a>', 'pressbooks' ),
								'http://www.bibtex.org/'
							),
							[ 'a' => [ 'href' => [], 'target' => [] ] ]
						);
						?>
					</p>
				</div>
			</details>

			<?php if ( ! $has_author ): ?>
			<div class="notice notice-warning inline" style="margin-top: 10px;">
				<p style="margin: 0.5em 0;">
					<?php esc_html_e( 'No authors specified. Consider adding at least one author for better metadata quality.', 'pressbooks' ); ?>
				</p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle MARC XML download request
	 */
	public function handleMarcDownload(): void {
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'pb_download_marc' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'pressbooks' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pressbooks' ) );
		}

		try {
			// Generate MARC XML
			$generator = new MarcXmlGenerator();
			$xml = $generator->generateXml();
			$filename = $generator->getFilename();

			// Send headers
			header( 'Content-Type: application/xml; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Content-Length: ' . strlen( $xml ) );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// Output XML
			echo $xml;
			exit;

		} catch ( \Exception $e ) {
			wp_die(
				sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Error generating MARC XML: %s', 'pressbooks' ),
					esc_html( $e->getMessage() )
				)
			);
		}
	}

	/**
	 * Handle BibTeX download request
	 */
	public function handleBibtexDownload(): void {
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'pb_download_bibtex' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'pressbooks' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pressbooks' ) );
		}

		try {
			// Generate BibTeX
			$generator = new BibtexGenerator();
			$bibtex = $generator->generate();
			$filename = $generator->getFilename();

			// Send headers
			header( 'Content-Type: application/x-bibtex; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Content-Length: ' . strlen( $bibtex ) );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// Output BibTeX
			echo $bibtex;
			exit;

		} catch ( \Exception $e ) {
			wp_die(
				sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Error generating BibTeX: %s', 'pressbooks' ),
					esc_html( $e->getMessage() )
				)
			);
		}
	}
}
