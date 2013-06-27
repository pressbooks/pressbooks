<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

// var_dump( $catalog->get() );

$base_href = PB_PLUGIN_URL . 'themes-root/pressbooks-publisher-one/';

$profile = $catalog->getProfile();
$logo_url = \PressBooks\Image\thumbnail_from_url( $profile['pb_catalog_logo'], 'thumbnail' );

$tags_1 = $catalog->getTags( 1 );
$tags_1_name = ! empty( $profile["pb_catalog_tag_1_name"] ) ? $profile["pb_catalog_tag_1_name"] : __( 'Tag', 'pressbooks' ) . ' 1';

$tags_2 = $catalog->getTags( 2 );
$tags_2_name = ! empty( $profile["pb_catalog_tag_2_name"] ) ? $profile["pb_catalog_tag_2_name"] : __( 'Tag', 'pressbooks' ) . ' 2';


$books = $catalog->getBookIds();


?>
<!DOCTYPE html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7 ]> <html <?php language_attributes(); ?> class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html <?php language_attributes(); ?> class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html <?php language_attributes(); ?> class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html <?php language_attributes(); ?> class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--><html <?php language_attributes(); ?> class="no-js"> <!--<![endif]-->
<head>
	<base href="<?php echo $base_href; ?>">
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/favicon.ico" />
	<title>Catalog Page</title>
	<link rel="stylesheet" type="text/css" href="style-catalog.css" />
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,600,400italic' rel='stylesheet' type='text/css'>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js" type="text/javascript"></script>
	<script src="js/jquery.equalizer.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		// <![CDATA[
		$(function () {
			$('#catalog-content').equalizer({ columns: '> div', min: 165 });
		});
		// ]]>
	</script>
<?php // wp_head(); ?>
</head>
<body>

<div class="catalog-wrap">

	<div class="catalog-sidebar">
		<img class="catalog-logo" src="<?php echo $logo_url; ?>" alt="catalog-logo" width="100" height="99" />
		<p class="about-blurb"><?php echo $profile['pb_catalog_about']; ?></p>
		<a class="link-more" href="<?php echo $profile['pb_catalog_url']; ?>">Learn more &raquo;  </a>

		<!-- Tags -->
		<?php for ( $i = 1; $i <= 2; ++$i ) : ?>
		<?php $name_var = "tags_{$i}_name"; $tags_var = "tags_{$i}"; ?>
			<h3><?php echo $$name_var; ?></h3>
			<ul>
				<?php
				foreach ( $$tags_var as $val ) {
					echo "<li><a href='#{$val['id']}' >{$val['tag']}</a></li>" . "\n";
					// TODO: class="active"
				}
				?>
			</ul>
		<?php endfor; ?>


	</div><!-- end catalog-sidebar -->

	<!-- Books! -->
	<?php

	?>
	<div class="catalog-content" id="catalog-content">

		<h1><?php _e( 'Catalog', 'pressbooks' ); ?></h1>


		<!-- Books -->
		<?php
		foreach ( $books as $id ) :

			switch_to_blog( $id );

			$metadata = \PressBooks\Book::getBookInformation();
			$title = $metadata['pb_title'];
			$author = $metadata['pb_author'];
			$cover_url = \PressBooks\Image\thumbnail_from_url( $metadata['pb_cover_image'], 'pb_cover_medium' );
			$about = strip_tags( pb_decode( @$metadata['pb_about_unlimited'] ) );
		?>
			<div class="book-data">

				<div class="book">
					<p class="book-description"><a href="<?php echo get_site_url( $id ); ?>"><?php echo $about; ?><span class="book-link">&rarr;</span></a></p>
					<img src="<?php echo $cover_url; ?>" alt="book-cover" width="225" />
				</div><!-- end .book -->

				<div class="book-info">
					<h2><?php echo $title; ?></h2>

					<p><a href="<?php echo get_site_url( $id, '/authors' ); ?>"><?php echo $author; ?></a></p> <!-- I'm assuming here we are linking to Author's about page -->
				</div><!-- end book-info -->

			</div><!-- end .book-data -->
		<?php
		endforeach;
		restore_current_blog();
		?>


	</div>	<!-- end .catalog -->

</div><!-- end .catalog-wrap -->

<?php wp_footer(); ?>
</body>
</html>