<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Utility;

use function Pressbooks\Redirect\force_download;

/**
 * This trait is used to allow exporting taxonomies.
 */
trait Exportable {
	/**
	 * Adds the required filters to handle exporting and importing data.
	 *
	 * @param $obj
	 * @return void
	 */
	public function bootExportable( $obj ) {
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
		return array_merge( $actions, [
			'download-csv' => __( 'Download CSV', 'pressbooks' ),
		] );
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
		if ( 'download-csv' !== $action ) {
			return;
		}

		$this->exportCsv( $ids );
	}

	/**
	 * Generates and download a CSV file with all the selected resources.
	 *
	 * @param $ids
	 * @return void
	 */
	public function exportCsv( $ids ) {
		$items = $this->getExportableContent( $ids );

		if ( empty( $items ) ) {
			return;
		}

		$content = $this->generateCsvContent( $items );

		$this->downloadContent( $content );
	}

	/**
	 * Return a list of taxonomy terms that should be exported.
	 *
	 * @param $ids
	 * @return array
	 */
	public function getExportableContent( $ids ) {
		$items = [];
		$fields = $this->getExportableFields();

		foreach ($ids as $id) {
			$term = get_term( $id, self::TAXONOMY );
			$term_meta = get_term_meta( $term->term_id );

			$item = [
				'name' => $term->name,
				'slug' => $term->slug,
			];

			foreach( $fields as $field ) {
				$value = $term_meta[ $field ] ?? [];

				$item[ str_replace( self::TAXONOMY . '_', '', $field ) ] = $value[0] ?? '';
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Generate the CSV content for the given array of items.
	 *
	 * @param array $items
	 * @return false|string
	 */
	public function generateCsvContent( $items ) {
		ob_start();

		$df = fopen( 'php://output', 'w' );

		fputcsv( $df, array_keys( $items[0] ) );

		foreach ($items as $row) {
			fputcsv( $df, $row );
		}

		fclose( $df );

		return ob_get_clean();
	}

	/**
	 * @param string $content
	 * @return void
	 */
	public function downloadContent( $content )
	{
		$filename = self::TAXONOMY . '-list-' . time() . '.csv';

		$file = create_tmp_file();

		put_contents( $file, $content );

		force_download( $file, false, $filename );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}
}
