<?php

use function Pressbooks\PostType\get_post_type_label;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = [
	'post_type' => [ 'front-matter', 'part', 'chapter', 'back-matter', 'glossary' ],
	'posts_per_page' => -1, // @codingStandardsIgnoreLine
	'post_status' => 'trash',
	'orderby' => 'post_modified',
	'order' => 'DESC',
];
$results = ( new \WP_Query() )->query( $args );

?>
<div class="wrap">
	<h1><?php _e( 'Trash' ); ?></h1>
	<p><?php _e( 'Restore deleted chapters, parts, front and back matter, and glossary terms', 'pressbooks' ); ?></p>
	<p><?php printf( __( '<strong>NOTE</strong>: Items in the trash will be permanently deleted after %d days.', 'pressbooks' ), EMPTY_TRASH_DAYS ); ?></p>

	<?php
	if ( empty( $results ) ) {
		echo '<h2>';
		_e( 'No trash found.', 'pressbooks' );
		echo '</h2></div>';
		return; // Exit!
	}
	?>

	<table class="widefat striped">
		<thead>
		<tr>
			<th><?php _e( 'Title' ); ?></th>
			<th><?php _e( 'Last Modified', 'pressbooks' ); ?></th>
			<th><?php _e( 'Type', 'pressbooks' ); ?></th>
			<th><?php _e( 'Action', 'pressbooks' ); ?></th>
		</tr>
		</thead>
		<tbody id="the-list">
		<?php
		/** @var \WP_Post $post */
		foreach ( $results as $post ) {
			if ( current_user_can( 'delete_post', $post->ID ) ) {
				echo '<tr>';
				$post_type_object = get_post_type_object( $post->post_type );
				$title = esc_html( empty( $post->post_title ) ? __( '(no title)' ) : $post->post_title );
				$type = get_post_type_label( $post->post_type );
				$date = $post->post_modified;
				echo "<td>{$title}</td>";
				echo "<td>{$date}</td>";
				echo "<td>{$type}</td>";
				printf(
					'<td><a href="%s" aria-label="%s">%s</a></td>',
					wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ),
					esc_attr( sprintf( __( 'Restore &#8220;%s&#8221; from the Trash' ), $title ) ),
					__( 'Restore' )
				);
				echo '</tr>';
			}
		}
		?>
		</tbody>
	</table>
</div>
