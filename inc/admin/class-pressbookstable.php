<?php

namespace Pressbooks\Admin;

use Pressbooks\Container;
use WP_List_Table;

class PressbooksTable extends WP_List_Table {

	public function print_column_headers( $with_id = true ): void {
		$columns  = $this->get_columns();
		$hidden   = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();
		$orderby  = $_GET['orderby'] ?? '';
		$order    = $_GET['order'] ?? 'asc';

		$columns_prepared = [];

		foreach ( $columns as $column_key => $column_display_name ) {
			$classes = [ 'manage-column', "column-$column_key" ];
			$is_sortable = isset( $sortable[ $column_key ] );

			if ( in_array( $column_key, $hidden, true ) ) {
				$classes[] = 'hidden';
			}

			if ( $is_sortable ) {
				$classes[] = 'sortable';

				if ( $orderby === $column_key ) {
					$classes[] = "sorted $order";
				}
			}

			$class_string = implode( ' ', $classes );

			$new_order = ( $orderby === $column_key && $order === 'asc' ) ? 'desc' : 'asc';
			$url = add_query_arg( [ 'orderby' => $column_key, 'order' => $new_order ] );

			if ( $orderby === $column_key ) {
				$screen_reader_text = sprintf(
					__( '%1$s column, sorted %2$s. Activate to sort %3$s.', 'pressbooks' ),
					$column_display_name,
					( $order === 'asc' ? 'ascending' : 'descending' ),
					( $order === 'asc' ? 'descending' : 'ascending' )
				);
			} else {
				$screen_reader_text = sprintf(
					__( 'Sort by %s in ascending order.', 'pressbooks' ),
					$column_display_name
				);
			}

			$columns_prepared[] = [
				'key' => $column_key,
				'label' => $column_display_name,
				'class' => $class_string,
				'sortable' => $is_sortable,
				'url' => esc_url( $url ),
				'screen_reader_text' => $screen_reader_text,
			];
		}

		echo Container::get( 'Blade' )
			->render( 'admin.pressbooks-table.column-headers', [
				'columns' => $columns_prepared,
			] );
	}
}
