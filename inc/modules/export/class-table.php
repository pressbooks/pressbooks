<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export;

class Table extends \WP_List_Table {

	public function __construct( $args = [] ) {
		$args = [
			'singular' => 'file',
			'plural' => 'files', // Parent will create bulk nonce: "bulk-{$plural}"
			'ajax' => true,
		];
		parent::__construct( $args );
	}

	/**
	 * Override
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		$format = $this->getTinyHash( $item['format'] );
		echo "<tr data-id='{$item['ID']}' data-format='{$format}'>";
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * This method is called when the parent class can't find a method
	 * for a given column. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists. If it doesn't this one will be used.
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param object $item A singular item (one full row's worth of data)
	 * @param string $column_name The name/slug of the column to be processed
	 *
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ID[]" value="%s" />', $item['ID'] );
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_file( $item ) {
		$html = '<div class="export-file">';
		$html .= $this->getIcon( $item['file'] );
		$html .= '<div class="export-file-name">' . esc_html( $item['file'] ) . '</div>';
		$html .= '</div>';

		$delete_url = sprintf( '/admin.php?page=%s&action=%s&ID=%s', $_REQUEST['page'], 'delete', $item['ID'] );
		$delete_url = get_admin_url( get_current_blog_id(), $delete_url );
		$delete_url = esc_url( add_query_arg( '_wpnonce', wp_create_nonce( 'bulk-files' ), $delete_url ) );
		$onclick = 'onclick="if ( !confirm(\'' . esc_attr( __( 'Are you sure you want to delete this?', 'pressbooks' ) ) . '\') ) { return false }"';
		$actions['delete'] = sprintf(
			'<a href="%s" aria-label="%s" ' . $onclick . '>%s</a>',
			$delete_url,
			/* translators: %s: filename */
			esc_attr( sprintf( __( 'Delete &#8220;%s&#8221;' ), $item['file'] ) ),
			_x( 'Delete', 'verb' )
		);

		$download_url = get_admin_url( get_current_blog_id(), "/admin.php?page=pb_export&download_export_file={$item['file']}" );
		$actions['download'] = sprintf(
			'<a href="%s" aria-label="%s" >%s</a>',
			$download_url,
			/* translators: %s: filename */
			esc_attr( sprintf( __( 'Download &#8220;%s&#8221;' ), $item['file'] ) ),
			_x( 'Download', 'verb' )
		);

		$html .= $this->row_actions( $actions );

		return $html;
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_pin( $item ) {
		$html = "<input type='checkbox' name='pin[{$item['ID']}]' value='1' " . checked( $item['pin'] ) . '/>';
		return $html;
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return [
			'cb' => '<input type="checkbox" />',
			'file' => __( 'File', 'pressbooks' ),
			'format' => __( 'Format', 'pressbooks' ),
			'size' => __( 'Size', 'pressbooks' ),
			'pin' => '📌', // <span class="dashicons dashicons-admin-post"></span> or 📌
			'exported' => __( 'Exported', 'pressbooks' ),
		];
	}

	/**
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'file' => [ 'file', true ],
			'format' => [ 'format', true ],
			'pin' => [ 'pin', true ],
			'exported' => [ 'exported', true ],
		];
		return $sortable_columns;
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions() {
		return [
			'delete' => 'Delete',
		];
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {

		// Process any actions first
		$this->processBulkActions();

		// Define Columns
		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Data
		$data = $this->getLatestExports();

		// Pagination
		$per_page = $this->get_items_per_page( 'pb_export_per_page', 50 );
		$current_page = $this->get_pagenum();
		$total_items = count( $data );
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'exported';
		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';

		// Data slice
		$data = wp_list_sort( $data, $orderby, $order );
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->items = $data;

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * @return array
	 */
	protected function getLatestExports() {
		$dir = untrailingslashit( Export::getExportFolder() );
		$ignored = [ '.', '..', '.svn', '.git', '.htaccess' ];
		$files = [];
		foreach ( scandir( $dir ) as $file ) {
			if ( in_array( $file, $ignored, true ) || is_dir( "$dir/$file" ) ) {
				continue;
			}
			$stat = stat( "$dir/$file" );
			$files[] = [
				'ID' => $this->getTinyHash( $file ),
				'file' => $file,
				'format' => $this->getFormat( $file ),
				'size' => \Pressbooks\Utility\format_bytes( $stat['size'] ),
				'pin' => 0,
				'exported' => date_i18n( 'Y-m-d H:i', $stat['mtime'] ),
			];
		}
		return $files;
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	protected function getCssClass( $file ) {
		$file_extension = substr( strrchr( $file, '.' ), 1 );
		switch ( $file_extension ) {
			case 'epub':
				$pre_suffix = strstr( $file, '._3.epub' );
				break;
			case 'pdf':
				$pre_suffix = strstr( $file, '._print.pdf' );
				break;
			case 'html':
				$pre_suffix = strstr( $file, '.-htmlbook.html' );
				break;
			case 'xml':
				$pre_suffix = strstr( $file, '._vanilla.xml' );
				break;
			default:
				$pre_suffix = false;
		}
		if ( 'html' === $file_extension && '.-htmlbook.html' === $pre_suffix ) {
			$file_class = 'htmlbook';
		} elseif ( 'html' === $file_extension && false === $pre_suffix ) {
			$file_class = 'xhtml';
		} elseif ( 'xml' === $file_extension && '._vanilla.xml' === $pre_suffix ) {
			$file_class = 'vanillawxr';
		} elseif ( 'xml' === $file_extension && false === $pre_suffix ) {
			$file_class = 'wxr';
		} elseif ( 'epub' === $file_extension && '._3.epub' === $pre_suffix ) {
			$file_class = 'epub3';
		} elseif ( 'pdf' === $file_extension && '._print.pdf' === $pre_suffix ) {
			$file_class = 'print-pdf';
		} else {
			/**
			 * Map custom export format file extensions to their CSS class.
			 *
			 * For example, here's how one might set the CSS class for a .docx file:
			 *
			 * add_filter( 'pb_get_export_file_class', function ( $file_extension ) {
			 *    if ( 'docx' == $file_extension ) {
			 *        return 'word';
			 *    }
			 *    return $file_extension;
			 * } );
			 *
			 * @since 3.9.8
			 *
			 * @param string $file_extension
			 */
			$file_class = apply_filters( 'pb_get_export_file_class', $file_extension );
		}
		return $file_class;
	}

	/**
	 * @param string $file
	 * @param string $size
	 *
	 * @return string
	 */
	protected function getIcon( $file, $size = 'large' ) {
		$file_class = $this->getCssClass( $file );
		$html = "<div class='export-file-icon {$size} {$file_class}' title='" . esc_attr( $file ) . "'></div>";
		return $html;
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	protected function getFormat( $file ) {
		$file_class = $this->getCssClass( $file );
		return ucfirst( $file_class );
	}

	/**
	 * Delete
	 */
	protected function processBulkActions() {
		if ( 'delete' === $this->current_action() ) {
			check_admin_referer( 'bulk-files' );
			$ids = isset( $_REQUEST['ID'] ) ? $_REQUEST['ID'] : [];
			if ( ! is_array( $ids ) ) {
				$ids = [ $ids ];
			}
			if ( ! empty( $ids ) ) {
				$latest_exports = $this->getLatestExports();
				foreach ( $ids as $id ) {
					$this->deleteFileById( $id, $latest_exports );
				}
			}
		}
	}

	/**
	 * Using the smallest hash because we want these to fit in a cookie. (A cookie has max 4096 bytes)
	 *
	 * @param string $s
	 *
	 * @return string
	 */
	protected function getTinyHash( $s ) {
		return hash( 'crc32b', $s );
	}

	/**
	 * @param string $id
	 * @param array $latest_exports
	 */
	protected function deleteFileById( $id, $latest_exports ) {
		foreach ( $latest_exports as $latest_export ) {
			if ( hash_equals( $latest_export['ID'], $id ) ) {
				unlink( trailingslashit( Export::getExportFolder() ) . $latest_export['file'] );
				break;
			}
		}
	}
}
