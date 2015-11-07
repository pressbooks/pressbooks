<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! headers_sent() ) {
	header( 'HTTP/1.0 200 OK' );
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
}
echo '<?xml version="1.0" encoding="'.get_option( 'blog_charset' ).'"?'.'>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php

	$query_args = array(
		'post_type'   => array( 'post', 'page', 'front-matter', 'part', 'chapter', 'back-matter' ),
		'post_status' => 'publish',
		'orderby'     => 'date',
		'posts_per_page' => 50000,
	);
	query_posts( $query_args );

	if ( have_posts()) : while (have_posts() ) : the_post();

		// Skip example pages
		if ( 'page' == $post->post_type && 'sample-page' == $post->post_name ) continue;
		elseif ( 'page' == $post->post_type && 'access-denied' == $post->post_name ) continue;
		elseif ( 'post' == $post->post_type && 'hello-world' == $post->post_name ) continue;

		?>
		<url>
			<loc><?php echo get_permalink( $post->ID ); ?></loc>
			<lastmod><?php echo mysql2date( 'Y-m-d', get_post_modified_time('Y-m-d H:i:s', true), false ); ?></lastmod>
		</url>
	<?php
	endwhile; endif;
	?>
</urlset>