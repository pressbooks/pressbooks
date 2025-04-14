<?php

namespace Pressbooks\Admin;

use Pressbooks\Container;
use WP_List_Table;

class PressbooksTable extends WP_List_Table {

	public function print_column_headers( $with_id = true ): void {
		$blade = Container::get( 'Blade' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $blade->render( 'admin.pressbooks-table.column-headers', [
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'columns' => $this->get_prepared_columns(),
		] );
	}

	private function get_prepared_columns(): array {
		$columns  = $this->get_columns();
		$hidden   = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();
		$orderby  = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
		$order    = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';

		$prepared = [];

		foreach ( $columns as $column_key => $column_label ) {
			$is_hidden = in_array( $column_key, $hidden, true );
			$is_sortable = isset( $sortable[ $column_key ] );
			$is_sorted = $orderby === $column_key;

			$prepared[] = [
				'key' => $column_key,
				'label' => $column_label,
				'class' => $this->get_column_class( $column_key, $is_hidden, $is_sortable, $is_sorted, $order ),
				'sortable' => $is_sortable,
				'url' => esc_url( $this->get_sorting_url( $column_key, $orderby, $order ) ),
				'screen_reader_text' => $this->get_screen_reader_text( $column_label, $is_sorted, $order ),
			];
		}

		return $prepared;
	}

	private function get_column_class( string $key, bool $is_hidden, bool $is_sortable, bool $is_sorted, string $order ): string {
		$classes = [ 'manage-column', "column-$key" ];
		if ( $is_hidden ) {
			$classes[] = 'hidden';
		}
		if ( $is_sortable ) {
			$classes[] = 'sortable';
			if ( $is_sorted ) {
				$classes[] = "sorted $order";
			}
		}
		return implode( ' ', $classes );
	}

	private function get_sorting_url( string $key, string $orderby, string $order ): string {
		$new_order = ( $orderby === $key && $order === 'asc' ) ? 'desc' : 'asc';
		return add_query_arg( [
			'orderby' => $key,
			'order'   => $new_order,
		] );
	}

	private function get_screen_reader_text( string $label, bool $is_sorted, string $order ): string {
		if ( $is_sorted ) {
			return sprintf(
				__( '%1$s column, sorted %2$s. Activate to sort %3$s.', 'pressbooks' ),
				$label,
				( $order === 'asc' ? 'ascending' : 'descending' ),
				( $order === 'asc' ? 'descending' : 'ascending' )
			);
		}

		return sprintf(
			__( 'Sort by %s in ascending order.', 'pressbooks' ),
			$label
		);
	}
}
