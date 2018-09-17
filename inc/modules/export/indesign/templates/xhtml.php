<?php

// See templating function for reference: \Pressbooks\Modules\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title><?php bloginfo(); ?></title>
</head>
<body>
<!-- cover image -->
<?php if ( isset( $meta['pb_cover_image'] ) ) { ?>
	<img src="<?php echo $meta['pb_cover_image']; ?>" alt="" class="featured" />
<?php } ?>

<!-- title page -->
<h1><?php bloginfo( 'name' ); ?></h1>

<?php if ( isset( $meta['pb_credit_override'] ) ) { ?>
	<h2><?php echo $meta['pb_credit_override']; ?></h2>
<?php } else { ?>
	<h2>by</h2>
	<?php if ( isset( $meta['pb_authors'] ) ) { ?>
		<h2><?php echo $meta['pb_authors']; ?></h2>
	<?php } ?>
<?php } ?>

<?php if ( isset( $meta['pb_contributors'] ) ) { ?>
	<h3><?php echo $meta['pb_contributors']; ?></h3>
<?php } ?>

<div class="page">
	<?php if ( isset( $meta['pb_print_isbn'] ) ) { ?>
		<p class="isbn"><strong>ISBN</strong>: <?php echo $meta['pb_print_isbn']; ?></p>
	<?php } ?>

	<?php if ( isset( $meta['pb_language'] ) ) { ?>
		<p class="language"><strong>Language</strong>: <?php echo $meta['pb_language']; ?></p>
	<?php } ?>

	<?php if ( isset( $meta['pb_publisher'] ) ) { ?>
		<p class="publisher"><strong>Publisher</strong>: <?php echo $meta['pb_publisher']; ?></p>
	<?php } ?>

	<?php if ( isset( $meta['pb_copyright_year'] ) || isset( $meta['pb_copyright_holder'] ) ) { ?>
		<p class="copyright_notice"><strong>Copyright</strong>:
			<?php
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				echo $meta['pb_copyright_year'] . ' ';
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				echo strftime( '%Y', $meta['pb_publication_date'] );
			} else {
				echo date( 'Y' );
			}
			if ( ! empty( $meta['pb_copyright_holder'] ) ) {
				echo ' by ' . $meta['pb_copyright_holder'] . '. ';
			}
			if ( ! empty( $do_copyright_license ) ) {
				echo $do_copyright_license . '. ';
			}
			?>
		</p>
	<?php } ?>

	<?php if ( isset( $meta['pb_keywords_tags'] ) ) { ?>
		<p class="keywords_tags"><strong>Keywords/Tags</strong>: <?php echo $meta['pb_keywords_tags']; ?></p>
	<?php } ?>
</div>

<?php

if ( isset( $meta['pb_about_unlimited'] ) ) {
	printf(
		'<h3>About the book</h3>%s',
		$meta['pb_about_unlimited']
	);
}

// Front Matter
foreach ( $book_contents['front-matter'] as $fm ) {

	if ( ! $fm['export'] ) {
		continue; // Skip
	}

	printf( '<div class="page" title="%s">', $fm['post_title'] );
	printf( '<h3>%s</h3>', $fm['post_title'] );
	printf( '%s</div>', $fm['post_content'] );
}

// Parts, Chapters
foreach ( $book_contents['part'] as $part ) {

	if ( count( $book_contents['part'] ) > 1 ) {
		printf( '<h2>%s</h2>', $part['post_title'] );
	}

	foreach ( $part['chapters'] as $chapter ) {

		if ( ! $chapter['export'] ) {
			continue; // Skip
		}

		printf( '<div class="page" title="%s">', $chapter['post_title'] );
		printf( '<h3>%s</h3>', $chapter['post_title'] );
		printf( '%s</div>', $chapter['post_content'] );
	}
}

?>

</body>
</html>
