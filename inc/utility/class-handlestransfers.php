<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Utility;

use function Pressbooks\Image\is_valid_image;
use function Pressbooks\Image\proper_image_extension;
use function Pressbooks\Redirect\force_download;
use Pressbooks\Transferable;

/**
 * This trait allows exporting taxonomies.
 */
trait HandlesTransfers {
	/**
	 * Adds the required filters to handle exporting and importing data.
	 *
	 * @param $obj
	 * @return void
	 */
	public function bootExportable( Transferable $obj ) {
		add_action( self::TAXONOMY . '_pre_add_form', [ $obj, 'renderImportForm' ] );
		add_filter( 'bulk_actions-edit-' . $obj::TAXONOMY, [ $obj, 'addBulkAction' ] );
		add_filter( 'handle_bulk_actions-edit-' . self::TAXONOMY, [ $obj, 'handleBulkAction' ], 10, 3 );
	}

	/**
	 * Adds the download action to the bulk actions' dropdown.
	 *
	 * @param $actions
	 * @return array
	 */
	public function addBulkAction( $actions ) {
		return array_merge(
			$actions, [
				self::TAXONOMY . '-download' => __( 'Download JSON', 'pressbooks' ),
			]
		);
	}

	/**
	 * Handles the action received.
	 *
	 * @param bool $redirect
	 * @param string $action
	 * @param array $ids
	 * @return void
	 */
	public function handleBulkAction( $redirect, $action, $ids ) {
		switch ( $action ) {
			case self::TAXONOMY . '-download':
				$this->exportTaxonomyList( $ids );
				break;
			case self::TAXONOMY . '-import':
				$this->importTaxonomyList();
				break;
			default:
		}
	}

	/**
	 * Renders the import form to allow importing taxonomies.
	 *
	 * @return void
	 */
	public function renderImportForm() {
		$messages = $this->getFormMessages();
		?>
		<div class="form-wrap">
			<?php echo $messages['title'] ?? '' ?>
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo self::TAXONOMY ?>-import">
				<input type="hidden" name="taxonomy" value="<?php echo self::TAXONOMY ?>">
				<input type="hidden" name="post_type" value="post">
				<input type="hidden" name="delete_tags[]" />
				<?php wp_nonce_field( 'bulk-tags' ); ?>
				<div class="form-field <?php echo self::TAXONOMY ?>-prefix-wrap">
					<input type="file" name="import_file" />
					<?php echo $messages['hint'] ?? ''; ?>
				</div>
				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php echo __( 'Import', 'pressbooks' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Imports a JSON file content and creates the taxonomy terms.
	 *
	 * @return void
	 */
	public function importTaxonomyList() {
		// If no file was imported.
		$upload = $this->handleUpload();

		if ( ! $upload ) {
			return;
		}

		$items = $this->getImportableItems( $upload );

		unlink( $upload['file'] );

		if ( empty( $items ) ) {
			return;
		}

		$this->handleImport( $items );

		$_SESSION['pb_notices'][] = __( 'Successfully imported.', 'pressbooks' );
	}

	/**
	 * Generates and download a JSON file with all the selected resources.
	 *
	 * @param $ids
	 * @return void
	 */
	public function exportTaxonomyList( $ids ) {
		$items = $this->getExportableItems( $ids );

		if ( empty( $items ) ) {
			return;
		}

		$this->downloadJson( wp_json_encode( $items ) );
	}

	/**
	 * Return a list of taxonomy terms that should be exported.
	 *
	 * @param $ids
	 * @return array
	 */
	public function getExportableItems( $ids ) {
		$items = [];
		$fields = $this->getTransferableFields();

		foreach ( $ids as $id ) {
			$term = get_term( $id, self::TAXONOMY );
			$term_meta = get_term_meta( $term->term_id );

			$item = [
				'name' => $term->name,
				'slug' => $term->slug,
			];

			foreach ( $fields as $field ) {
				$value = $term_meta[ $field ] ?? [];

				$item[ $field ] = $value[0] ?? '';
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Download the content as a JSON file.
	 *
	 * @param string $content
	 * @return void
	 */
	public function downloadJson( $content ) {
		$filename = self::TAXONOMY . '-list-' . time() . '.json';

		$file = create_tmp_file();

		put_contents( $file, $content );

		force_download( $file, false, $filename );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}

	/**
	 * Handles file upload before importing contributors.
	 *
	 * @return array|false
	 */
	public function handleUpload() {
		if ( empty( $_FILES['import_file']['name'] ) ) {
			return false;
		}

		if ( $_FILES['import_file']['type'] !== 'application/json' ) {
			$_SESSION['pb_errors'][] = __( 'Sorry, this file type is not permitted for security reasons.' );

			return false;
		}

		$upload = wp_handle_upload(
			$_FILES['import_file'], [
				'test_type' => false,
				'action' => self::TAXONOMY . '-import',
			]
		);

		if ( ! empty( $upload['error'] ) ) {
			$_SESSION['pb_errors'][] = $upload['error'];

			return false;
		}

		return $upload;
	}

	/**
	 * Returns the list of contributors to import
	 *
	 * @param array $upload
	 * @return array
	 */
	public function getImportableItems( $upload ) {
		$items = [];
		$invalid_rows = false;

		foreach ( json_decode( file_get_contents( $upload['file'] ) ) as $key => $item ) {
			$item = (array) $item;

			if ( ! isset( $item['name'], $item['slug'] ) ) {
				$invalid_rows = true;

				continue;
			}

			$items[] = $item;
		}

		if ( $invalid_rows ) {
			$_SESSION['pb_errors'][] = __( 'One or more contributors could not be imported because they were missing a name or slug.', 'pressbooks' );
		}

		return $items;
	}

	/**
	 * Imports the list of contributors.
	 *
	 * @param array $items
	 */
	public function handleImport( $items ) {
		$importable_fields = $this->getTransferableFields();
		$changed = false;

		foreach ( $items as $item ) {
			$term = get_term_by( 'slug', $item['slug'], self::TAXONOMY );

			if ( ! $term ) {
				$results = wp_insert_term(
					sanitize_text_field( $item['name'] ), self::TAXONOMY, [
						'slug' => sanitize_text_field( $item['slug'] ),
					]
				);
			} else {
				$results = wp_update_term(
					$term->term_id, self::TAXONOMY, [
						'name' => sanitize_text_field( $item['name'] ),
						'slug' => sanitize_text_field( $item['slug'] ),
					]
				);
			}

			foreach ( $importable_fields as $field ) {
				if ( ! isset( $item[ $field ] ) ) {
					continue;
				}

				$sanitized_value = $this->sanitizeField( $field, $item[ $field ] );

				if ( $item[ $field ] !== $sanitized_value ) {
					$changed = true;
					$item[ $field ] = $sanitized_value;
				}

				if ( empty( $item[ $field ] ) ) {
					continue;
				}

				if ( false !== strpos( $field, 'picture' ) ) {
					// Skip the picture if we are unable to get the src url.
					$src = $this->handleImage( $item[ $field ] );

					if ( ! $src ) {
						continue;
					}

					$item[ $field ] = $src;
				}

				$term
					? update_term_meta( $results['term_id'], $field, $item[ $field ] )
					: add_term_meta( $results['term_id'], $field, $item[ $field ] );
			}
		}

		if ( $changed ) {
			$_SESSION['pb_errors'][] = __( 'Values for one or more of the imported contributors were altered by our validation routine. ', 'pressbooks' );
		}
	}

	/**
	 * Creates a new image based on the url provided during import.
	 *
	 * @param string $url
	 * @return false|string
	 */
	public function handleImage( $url ) {
		if ( ! $url ) {
			return false;
		}

		$parts = explode( '?', $url );
		$parts = explode( '#', $parts[0] );
		$parts = explode( '/', $parts[0] );

		$filename = sanitize_file_name( end( $parts ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			return false;
		}

		$tmp_name = download_url( $url );

		if ( ! is_valid_image( $tmp_name, $filename ) ) {
			try {
				$filename = proper_image_extension( $tmp_name, $filename );

				if ( ! is_valid_image( $tmp_name, $filename ) ) {
					return false;
				}
			} catch ( \Exception $exc ) {
				@unlink( $tmp_name ); // @codingStandardsIgnoreLine

				return false;
			}
		}

		$pid = media_handle_sideload(
			[
				'name' => $filename,
				'tmp_name' => $tmp_name,
			]
		);

		@unlink( $tmp_name ); // @codingStandardsIgnoreLine

		return wp_get_attachment_url( $pid );
	}
}
