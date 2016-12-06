<?php
use Roots\Sage\Assets;

function pressbooks_publisher_admin_scripts($hook) {
    if ( 'sites.php' !== $hook ) {
        return;
    }

    wp_enqueue_script( 'pressbooks-publisher-admin', Assets\asset_path( 'scripts/catalog-admin.js' ), array('jquery'), '20150527' );
	wp_localize_script( 'pressbooks-publisher-admin', 'PB_Publisher_Admin', array(
		'publisherAdminNonce' => wp_create_nonce( 'pressbooks-publisher-admin' ),
		'catalog_updated' => __( 'Catalog updated.', 'pressbooks' ),
		'catalog_not_updated' => __( 'Sorry, but your catalog was not updated. Please try again.', 'pressbooks' ),
		'dismiss_notice' => __( 'Dismiss this notice.', 'pressbooks' ),
	));
}

add_action( 'admin_enqueue_scripts', 'pressbooks_publisher_admin_scripts' );

function pressbooks_publisher_update_catalog() {
	$blog_id = absint( $_POST['book_id'] );
	$in_catalog = $_POST['in_catalog'];

	if ( current_user_can( 'manage_network' ) && check_ajax_referer( 'pressbooks-publisher-admin' ) ) {
		if ( $in_catalog == 'true' ) {
			update_blog_option( $blog_id, 'pressbooks_publisher_in_catalog', 1 );
		} else {
			delete_blog_option( $blog_id, 'pressbooks_publisher_in_catalog' );
		}
	}
}

add_action( 'wp_ajax_pressbooks_publisher_update_catalog', 'pressbooks_publisher_update_catalog' );

function pressbooks_publisher_catalog_columns( $columns ) {
	$columns[ 'in_catalog' ] = __( 'In Catalog', 'pressbooks' );
	return $columns;
}

add_filter( 'wpmu_blogs_columns', 'pressbooks_publisher_catalog_columns' );

function pressbooks_publisher_catalog_column( $column, $blog_id ) {

	if ( 'in_catalog' == $column && ! is_main_site( $blog_id ) ) { ?>
		<input class="in-catalog" type="checkbox" name="in_catalog" value="1" <?php checked( get_blog_option( $blog_id, 'pressbooks_publisher_in_catalog' ), 1 ); ?> <?php
		if ( ! get_blog_option( $blog_id, 'blog_public' ) ) { ?>disabled="disabled" title="<?php _e( 'This book is private, so you can&rsquo;t display it in your catalog.', 'pressbooks' ); ?>"<?php } ?> />
	<?php }

}

add_action( 'manage_blogs_custom_column', 'pressbooks_publisher_catalog_column', 1, 3 );
add_action( 'manage_sites_custom_column', 'pressbooks_publisher_catalog_column', 1, 3 );
