<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin;

use Pressbooks\Catalog;

/**
 * @see http://codex.wordpress.org/Class_Reference/WP_List_Table
 */
class Catalog_List_Table extends \WP_List_Table {
	// ----------------------------------------------------------------------------------------------------------------
	// WordPress Overrides
	// ----------------------------------------------------------------------------------------------------------------

	public function __construct( array $args = [] ) {
		parent::__construct( [
			'singular' => 'book',
			'plural' => 'books', // Parent will create bulk nonce: "bulk-{$plural}"
			'ajax' => true,
		] );
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
	public function column_default( $item, $column_name ): string {
		if ( preg_match( '/^tag_\d+$/', $column_name ) ) {
			return $this->renderTagColumn( $item, $column_name );
		}

		return esc_html( $item[ $column_name ] );
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_title( $item ): string {
		[ $user_id, $blog_id ] = explode( ':', $item['ID'] );

		// Build row actions
		$actions = [
			'visit' => sprintf( '<a href="%s">%s</a>', get_home_url( $blog_id ), __( 'Visit Book', 'pressbooks' ) ),
		];

		// Only include admin link if user has admin rights to the book in question
		if ( is_super_admin( $user_id ) || is_user_member_of_blog( $user_id, $blog_id ) ) {
			$actions['dashboard'] = sprintf( '<a href="%s">%s</a>', get_admin_url( $blog_id ), __( 'Visit Admin', 'pressbooks' ) );
		}

		return sprintf( '<span class="title">%1$s</span> %2$s', $item['title'], $this->row_actions( $actions ) );
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_status( $item ): string {
		$page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? 'pb_catalog' ) );

		// TODO, Better HTML?
		if ( $item['status'] ) {
			$remove_url = sprintf( get_admin_url() . 'index.php?page=%s&action=%s&ID=%s', sanitize_text_field( $page ), 'remove', $item['ID'] );
			$remove_url = add_query_arg( '_wpnonce', wp_create_nonce( $item['ID'] ), $remove_url );
			$remove_url = static::addSearchParamsToUrl( $remove_url );

			$status = '<span data-icon="b" class="yes-icon"></span><span class="assistive-text">Yes</span>';
			$actions = [
				'remove' => sprintf( '<a href="%s">%s</a>', $remove_url, __( 'Hide in Catalog', 'pressbooks' ) ),
			];
		} else {
			$add_url = sprintf( get_admin_url() . 'index.php?page=%s&action=%s&ID=%s', sanitize_text_field( $page ), 'add', $item['ID'] );
			$add_url = add_query_arg( '_wpnonce', wp_create_nonce( $item['ID'] ), $add_url );
			$add_url = static::addSearchParamsToUrl( $add_url );

			$status = '<span data-icon="c" class="no-icon"></span><span class="assistive-text">No</span>';
			$actions = [
				'add' => sprintf( '<a href="%s">%s</a>', $add_url, __( 'Show in Catalog', 'pressbooks' ) ),
			];
		}

		// Return the title contents
		return sprintf(
			'<span class="status">%1$s</span> %2$s',
			$status,
			$this->row_actions( $actions )
		);
	}

	/**
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_cover( $item ): string {
		$img = esc_url( $item['cover'] );
		$alt = esc_attr( $item['title'] );

		return "<img src='$img' alt='$alt' />";
	}

	/**
	 * Hidden elements should be visible when focused.
	 *
	 * @param mixed $item
	 * @param string $classes
	 * @param string $data
	 * @param string $primary
	 */
	protected function _column_title( $item, $classes, $data, $primary ): void {
		$this->hasRowActionsFix( 'column_title', $item, $classes, $data, $primary );
	}

	/**
	 * Hidden elements should be visible when focused.
	 * Note: Total _column_tag_x methods much equal \Pressbooks\Catalog::MAX_TAGS_GROUP
	 *
	 * @param mixed $item
	 * @param string $classes
	 * @param string $data
	 * @param string $primary
	 *
	 * @see \Pressbooks\Catalog::MAX_TAGS_GROUP
	 */
	protected function _column_tag_1( $item, $classes, $data, $primary ): void {
		$this->hasRowActionsFix( 'tag_1', $item, $classes, $data, $primary );
	}

	/**
	 * Hidden elements should be visible when focused.
	 * Note: Total _column_tag_x methods much equal \Pressbooks\Catalog::MAX_TAGS_GROUP
	 *
	 * @param mixed $item
	 * @param string $classes
	 * @param string $data
	 * @param string $primary
	 *
	 * @see \Pressbooks\Catalog::MAX_TAGS_GROUP
	 */
	protected function _column_tag_2( $item, $classes, $data, $primary ): void {
		$this->hasRowActionsFix( 'tag_2', $item, $classes, $data, $primary );
	}

	/**
	 * Hidden elements should be visible when focused.
	 *
	 * @param string $column_name
	 * @param mixed $item
	 * @param string $classes
	 * @param string $data
	 * @param string $primary
	 */
	protected function hasRowActionsFix( $column_name, $item, $classes, $data, $primary ): void {
		if ( preg_match( '/^([\w-]+)="?(.*?)"?$/', $data, $matches ) ) {
			$attr_name = esc_attr( $matches[1] );
			$attr_value = esc_attr( $matches[2] );

			// Escape only the value
			$data = "{$attr_name}='{$attr_value}'";
		} else {
			$data = esc_attr( $data );
		}

		// Data already escaped, disabling it...
		// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
		echo '<td class="', esc_html( $classes ), ' has-row-actions" ', $data, '>';

		if ( method_exists( $this, $column_name ) ) {
			// We want to render the resulting html here....
			// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
			echo call_user_func( [ $this, $column_name ], $item );
		} else {
			// We want to render the resulting html here....
			// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
			echo $this->column_default( $item, $column_name );
		}

		// We want to render the resulting html here....
		// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
		echo $this->handle_row_actions( $item, $column_name, $primary );

		echo '</td>';
	}

	/**
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'], // Let's simply repurpose the table's singular label ("book")
			$item['ID'] // The value of the checkbox should be the record's id
		);
	}

	/**
	 * This method dictates the table's columns and titles.
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns(): array {
		$profile = ( new Catalog() )->getProfile();

		$columns = [
			'cb' => '<input type="checkbox" />', // Render a checkbox instead of text
			'status' => __( 'Catalog Status', 'pressbooks' ),
			'privacy' => __( 'Privacy Status', 'pressbooks' ),
			'cover' => __( 'Cover', 'pressbooks' ),
			'title' => __( 'Title', 'pressbooks' ),
			'author' => __( 'Author', 'pressbooks' ),
		];

		for ( $i = 1; $i <= Catalog::MAX_TAGS_GROUP; ++$i ) {
			$columns[ "tag_{$i}" ] = ! empty( $profile[ "pb_catalog_tag_{$i}_name" ] ) ? esc_html( wp_strip_all_tags( $profile[ "pb_catalog_tag_{$i}_name" ] ) ) : __( 'Tag', 'pressbooks' ) . " $i";
		}

		$columns['featured'] = __( 'Featured', 'pressbooks' );
		$columns['pub_date'] = __( 'Pub Date', 'pressbooks' );

		return $columns;
	}

	/**
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting.
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	public function get_sortable_columns(): array {
		return [
			'status' => [ 'status', false ],
			'privacy' => [ 'privacy', false ],
			'title' => [ 'title', false ],
			'author' => [ 'author', false ],
			'pub_date' => [ 'pub_date', false ],
		];
	}

	/**
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	public function get_bulk_actions(): array {
		return [
			'add' => __( 'Show in Catalog', 'pressbooks' ),
			'remove' => __( 'Hide in Catalog', 'pressbooks' ),
		];
	}

	/**
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args()
	 */
	public function prepare_items(): void {
		// Define Columns
		$columns = $this->get_columns();
		$hidden = $this->getHiddenColumns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Get data, sort
		$data = $this->getItemsData();
		$valid_cols = $this->get_sortable_columns();

		$order = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ?? '' ) );
		$direction = strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ?? 'ASC' ) ) ) === 'ASC' ? 'ASC' : 'DESC';

		if ( isset( $valid_cols[ $order ] ) ) {
			$data = wp_list_sort( $data, $order, $direction );
		} else {
			$data = wp_list_sort( $data, [
				'status' => 'desc',
				'title' => 'asc',
			] );
		}

		// Pagination
		$per_page = 1000;
		$current_page = $this->get_pagenum();
		$total_items = count( $data );

		/*
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		/*
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;

		/* REQUIRED. We also have to register our pagination options & calculations. */
		$args = [
			'total_items' => $total_items, // WE have to calculate the total number of items
			'per_page' => $per_page, // WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages
		];

		$this->set_pagination_args( $args );
	}

	/**
	 * Form is POST not GET. Override parent method to compensate.
	 *
	 * @param bool $with_id
	 */
	public function print_column_headers( $with_id = true ): void {
		if ( isset( $_GET['pb_catalog_search'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['pb_catalog_search'] ) ), 'pb_catalog_search' ) ) {
				wp_die( esc_html__( 'Security check.', 'pressbooks' ) );
			}
		}

		if ( empty( $_GET['s'] ) && ! empty( $_POST['s'] ) ) {
			$_SERVER['REQUEST_URI'] = esc_url( add_query_arg( 's', sanitize_text_field( wp_unslash( $_POST['s'] ) ) ) );
		}

		if ( empty( $_GET['orderby'] ) && ! empty( $_POST['orderby'] ) ) {
			$_GET['orderby'] = sanitize_text_field( wp_unslash( $_POST['orderby'] ) );
		}

		if ( empty( $_GET['order'] ) && ! empty( $_POST['order'] ) ) {
			$_GET['order'] = sanitize_text_field( wp_unslash( $_POST['order'] ) );
		}

		parent::print_column_headers( $with_id );
	}

	// ----------------------------------------------------------------------------------------------------------------
	// Pressbooks Stuff
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * @param object $item A singular item (one full row's worth of data)
	 * @param string $column_name The name/slug of the column to be processed
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	protected function renderTagColumn( $item, $column_name ): string {
		$html = Catalog::tagsToString( $item[ $column_name ] );

		if ( ! $html ) {
			$html = '<span style="color:silver">n/a</span>';
		}

		// Build row actions
		$actions = [
			'edit_tags' => sprintf(
				'<a href="?page=%s&action=%s&ID=%s">%s</a>',
				sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? 'pb_catalog' ) ),
				'edit_tags',
				$item['ID'],
				__( 'Edit Tags', 'pressbooks' )
			),
		];

		// Return the title contents
		return sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
	}

	/**
	 * TODO: This isn't well documented, not sure i'm doing it right...
	 *
	 * @return array
	 */
	protected function getHiddenColumns(): array {
		return [
			'featured',
		];
	}

	/**
	 * @return array
	 */
	protected function getItemsData(): array {
		// TODO: Improve search filter for big data

		$catalog_obj = new Catalog();
		$data = $catalog_obj->getAggregate();

		foreach ( $data as $key => $val ) {
			$data[ $key ]['status'] = ( ! empty( $val['deleted'] ) ) ? 0 : 1;
			$data[ $key ]['privacy'] = ( ! empty( $val['private'] ) ? __( 'Private', 'pressbooks' ) : __( 'Public', 'pressbooks' ) );
			$data[ $key ]['cover'] = $val['cover_url']['pb_cover_small'];
		}

		return $this->searchFilter( $data );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	protected function searchFilter( array $data ): array {
		$keyword = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) ) );

		if ( ! $keyword ) {
			return $data;
		}

		$filtered_data = [];

		foreach ( $data as $_ => $val ) {
			if ( $this->atLeastOneKeyword( $keyword, $val ) ) {
				$filtered_data[] = $val;
			}
		}

		return $filtered_data;
	}

	/**
	 * @param $keyword
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function atLeastOneKeyword( $keyword, array $data ): bool {
		// TODO: Does this work with multi-byte characters?
		foreach ( $data as $key => $val ) {
			if ( is_array( $val ) ) {
				if ( $this->atLeastOneKeyword( $keyword, $val ) ) {
					return true;
				}
			} elseif ( false !== stripos( $val, $keyword ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * WP Hook, Instantiate UI
	 */
	public static function addMenu(): void {
		$url = get_admin_url( get_current_blog_id(), '/index.php?page=pb_catalog' );
		$view_url = static::viewCatalogUrl();
		$edit_url = $url . '&action=edit_profile';

		$page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? 'pb_catalog' ) );
		$user_id = isset( $_REQUEST['user_id'] ) ? (int) sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ) : null;
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : null;

		if ( $user_id ) {
			$edit_url .= '&user_id=' . sanitize_text_field( $user_id );
		}

		$list_table = new static();
		$list_table->prepare_items();

		?>
		<div class="wrap">
			<h1><?php echo $user_id ? esc_html( ucfirst( get_userdata( $user_id )->user_login ) ) : esc_html__( 'My Catalog', 'pressbooks' ); ?></h1>
				<a href="<?php echo esc_url( $edit_url ); ?>" class=" page-title-action"><?php _e( 'Edit Profile', 'pressbooks' ); ?></a>
				<a href="<?php echo esc_url( $view_url ); ?>" class=" page-title-action"><?php _e( 'Visit Catalog', 'pressbooks' ); ?></a>
			<?php
			if ( $search ) {
				$total_items = $list_table->get_pagination_arg( 'total_items' );
				if ( $total_items === 0 ) {
					/* translators: %s: search keywords */
					$search_results = sprintf( __( 'Search results for &#8220;%s&#8221; returned no items', 'pressbooks' ), esc_html( wp_unslash( $search ) ) );
				} elseif ( $total_items === 1 ) {
					/* translators: %s: search keywords */
					$search_results = sprintf( __( 'Search results for &#8220;%s&#8221; returned 1 item', 'pressbooks' ), esc_html( wp_unslash( $search ) ) );
				} else {
					/* translators: %s: search keywords, %d: total items found */
					$search_results = sprintf( __( 'Search results for &#8220;%1$s&#8221; returned %2$d items', 'pressbooks' ), esc_html( wp_unslash( $search ) ), $total_items );
				}
				echo '<span id="search-results" class="subtitle" role="alert"></span>';
				// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
				echo '<script>window.addEventListener("load", function(event){document.getElementById("search-results").innerHTML="' . $search_results . '";});</script>';
			}
			?>
			<div class="postbox">
				<div class="inside">
					<h2><?php echo esc_html__( 'Organize your public Catalog page.', 'pressbooks' ); ?></h2>
					<h3><span data-icon="a" class="show-hide-icon"></span><?php echo esc_html__( 'Show/Hide books', 'pressbooks' ); ?></h3>
					<p><?php printf( esc_html__( 'To display a book in your catalog choose "%s" under Catalog Status. ', 'pressbooks' ), '<strong>' . esc_html__( 'Show in Catalog', 'pressbooks' ) . '</strong>' ); ?>
						<br>
						<?php printf( esc_html__( 'To hide a book in your catalog choose "%s" under Catalog Status.', 'pressbooks' ), '<strong>' . esc_html__( 'Hide in Catalog', 'pressbooks' ) . '</strong>' ); ?>
					</p>

					<h3><span data-icon="g" class="sort-icon"></span><?php echo esc_html__( 'Catalog sorting', 'pressbooks' ); ?></h3>
					<p>
						<?php
						// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
						printf( __( 'To add sorting ability, add your Tag names to your <a href="%s">Catalog Profile</a> page (ex: Authors, Book Genre), then add the appropriate tags to each individual book.', 'pressbooks' ), esc_url( $edit_url ) );
						?>
					</p>

					<h3><span data-icon="f" class="share-icon"></span><?php echo esc_html__( 'Share your catalog', 'pressbooks' ); ?></h3>
					<p>
						<?php echo esc_html__( 'The public link to your catalog page', 'pressbooks' ); ?>:
						<a href="<?php echo esc_url( $view_url ); ?>">
							<?php echo esc_url( $view_url ); ?>
						</a>
					</p>
				</div>
			</div><!-- end .postbox -->

			<div id="books-search-container">
				<form class="inline-form" method="post" action="<?php echo esc_url( $url ); ?>">
					<?php wp_nonce_field( 'bulk-books' ); // Nonce auto-generated by WP_List_Table ?>
					<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>"/>
					<?php if ( $user_id ) : ?>
						<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
					<?php endif; ?>
					<div id="add-by-url">
						<input type="text" id="add_book_by_url" name="add_book_by_url"/>
						<label for="add_book_by_url">
							<input type="submit" name="" id="search-submit" class="button" value="<?php esc_attr_e( 'Add By URL', 'pressbooks' ); ?>">
						</label>
					</div>
				</form>
				<form id="books-search" class="inline-form" method="get" action="<?php echo esc_url( $url ); ?>">
					<?php wp_nonce_field( 'pb_catalog_search', 'pb_catalog_search', false ); ?>
					<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>"/>
					<?php if ( $user_id ) : ?>
						<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
					<?php endif; ?>
					<?php $list_table->search_box( __( 'Search', 'pressbooks' ), 'search_id' ); ?>
				</form>
			</div>

			<form id="books-filter" method="post" action="<?php echo esc_url( $url ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>"/>
				<?php if ( $user_id ) : ?>
					<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>"/>
				<?php endif; ?>
				<?php $list_table->display(); ?>
			</form>
		</div>
		<?php

	}

	/**
	 * Rebuild a URL with known search parameters
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function addSearchParamsToUrl( string $url ): string {
		if ( ! empty( $_REQUEST['s'] ) ) {
			$url = add_query_arg( 's', sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ), $url );
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$url = add_query_arg( 'orderby', sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ), $url );
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			$url = add_query_arg( 'order', sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ), $url );
		}

		if ( ! empty( $_REQUEST['paged'] ) ) {
			$url = add_query_arg( 'paged', (int) sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ), $url );
		}

		return esc_url( $url );
	}

	/**
	 * Generate catalog URL. Dies on problem.
	 *
	 * @return string
	 */
	public static function viewCatalogUrl(): string {
		if ( isset( $_REQUEST['user_id'] ) ) {
			if ( false === current_user_can( 'edit_user', (int) $_REQUEST['user_id'] ) ) {
				// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
				wp_die( __( 'You do not have permission to do that.', 'pressbooks' ) );
			}

			$u = get_userdata( (int) $_REQUEST['user_id'] );

			if ( false === $u ) {
				// @phpcs:ignore Pressbooks.Security.EscapeOutput.OutputNotEscaped
				wp_die( __( 'The requested user does not exist.', 'pressbooks' ) );
			}

			$user_login = get_userdata( (int) $_REQUEST['user_id'] )->user_login;
		} else {
			$user_login = get_userdata( get_current_user_id() )->user_login;
		}

		return network_home_url( "/catalog/$user_login" );
	}
}
