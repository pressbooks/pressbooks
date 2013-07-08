<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

use \PressBooks\Image as PB_Image;
use \PressBooks\Catalog as PB_Catalog;

// TODO: Move logic out of the template

// -------------------------------------------------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------------------------------------------------

/**
 * Get catalog logo
 *
 * @param array $profile
 *
 * @return string
 */
function _logo_url( $profile ) {

	if ( empty( $profile['pb_catalog_logo'] ) )
		return PB_PLUGIN_URL . 'assets/images/default-book-cover-100x100.jpg';

	elseif ( PB_Image\is_default_cover( $profile['pb_catalog_logo'] ) )
		return PB_PLUGIN_URL . 'assets/images/default-book-cover-100x100.jpg';

	else
		return PB_Catalog::thumbnailFromUserId( $profile['users_id'], 'thumbnail' );
}

/**
 * Try to get the height of cover using the image name
 * Ie. http://blah/foobar-225x126.jpg would return 126
 *
 * @param string $cover_url
 *
 * @return int
 */
function _cover_height( $cover_url ) {

	$cover_height = 300;

	if ( preg_match( '/x(\d+)(?=\.(jp?g|png|gif)$)/i', $cover_url, $matches ) ) {
		$new_cover_height = (int) $matches[1];
		if ( $new_cover_height < 100 ) $new_cover_height = $cover_height;
		elseif ( $new_cover_height > $cover_height ) $new_cover_height = $cover_height;
	}

	return isset( $new_cover_height ) ? $new_cover_height : $cover_height;
}


/**
 * Get book data
 * Sort by featured DESC, title ASC
 *
 * @param PB_Catalog $catalog
 *
 * @return array
 */
function _books( PB_Catalog $catalog ) {

	$tag_group = $tag_id = false;
	if ( ! empty( $_REQUEST['tag_group'] ) && ! empty( $_REQUEST['tag_id'] ) ) {
		$tag_group = absint( $_REQUEST['tag_group'] );
		$tag_id = absint( $_REQUEST['tag_id'] );
	}

	$books = $catalog->getAggregate();

	foreach ( $books as $key => $val ) {

		// Deleted
		if ( $val['deleted'] ) {
			unset ( $books[$key] );
			continue;
		}
		// Tagged
		if ( $tag_group && $tag_id  ) {
			$tag_found = false;
			foreach ( $val["tag_{$tag_group}"] as $tag ) {
				if ( $tag_id == $tag['id'] ) {
					$tag_found = true;
					break;
				}
			}
			if ( ! $tag_found ) {
				unset ( $books[$key] );
				continue;
			}
		}

		// Calculate cover height
		$books[$key]['cover_height'] = _cover_height( $val['cover_url']['pb_cover_medium'] );
	}

	return \PressBooks\Utility\multi_sort( $books, 'featured:desc', 'title:asc' );
}


/**
 * Get base url
 *
 * @param $user_id
 *
 * @return string
 */
function _base_url( $user_id ) {

	static $base_url = false; // Cheap cache
	if ( false === $base_url ) {
		$base_url = get_userdata( $user_id )->user_login;
		$base_url = network_site_url( "/catalog/$base_url" );
	}

	return $base_url;
}

/**
 * Get tag url
 *
 * @param int $user_id
 * @param int $tag_group
 * @param int $tag_id
 *
 * @return string
 */
function _tag_url( $user_id, $tag_group, $tag_id ) {

	return _base_url( $user_id ) . "?tag_group=$tag_group&tag_id=$tag_id";
}

// -------------------------------------------------------------------------------------------------------------------
// Variables
// -------------------------------------------------------------------------------------------------------------------

$base_href = PB_PLUGIN_URL . 'themes-root/pressbooks-publisher-one/';
$catalog = new PB_Catalog( absint( $user_id ) ); // Note: $user_id is set in PB_Catalog::loadTemplate()
$profile = $catalog->getProfile();
$books = _books( $catalog );
$h1_title = __( 'Catalog', 'pressbooks' );

// -------------------------------------------------------------------------------------------------------------------
// HTML
// -------------------------------------------------------------------------------------------------------------------
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
	<title><?php _e( 'Catalog Page', 'pressbooks' ); ?></title>
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
		<?php if ( $profile['pb_catalog_url'] ): ?><a href="<?php echo $profile['pb_catalog_url']; ?>"><?php endif; ?>
		<img class="catalog-logo" src="<?php echo _logo_url( $profile ); ?>" alt="catalog-logo" width="100" height="99" />
		<?php if ( $profile['pb_catalog_url'] ): ?></a><?php endif; ?>
		<p class="about-blurb"><?php echo $profile['pb_catalog_about']; ?></p>
		<br />

		<!-- Tags -->
		<?php for ( $i = 1; $i <= 2; ++$i ) : ?>
		<?php $tags = $catalog->getTags( $i, false ); ?>
			<h3><?php echo ( ! empty( $profile["pb_catalog_tag_{$i}_name"] ) ) ? $profile["pb_catalog_tag_{$i}_name"] : __( 'Tag', 'pressbooks' ) . " $i"; ?></h3>
			<ul>
				<?php
				foreach ( $tags as $val ) {
					$tag_url = _tag_url( $catalog->getUserId(), $i, $val['id'] );
					echo "<li><a href='$tag_url' ";
					if ( $i == @$_REQUEST['tag_group'] && $val['id'] == @$_REQUEST['tag_id'] ) {
						echo 'class="active"';
						$h1_title = __( 'Catalog, filtering by', 'pressbooks' ) . ": {$val['tag']}";
						$h1_title .= ' <span style="font-weight:normal;font-size:60%;">[<a href="' . _base_url( $catalog->getUserId() ) . '">x</a>]</span>';
					}
					echo ">{$val['tag']}</a></li>" . "\n";
				}
				?>
			</ul>
		<?php endfor; ?>


	</div><!-- end catalog-sidebar -->

	<!-- Books! -->
	<?php

	?>
	<div class="catalog-content" id="catalog-content">

		<h1><?php echo $h1_title ?></h1>


		<!-- Books -->
		<?php foreach ( $books as $b ) : ?>
			<div class="book-data">

				<div class="book">
					<p class="book-description"><a href="<?php echo get_site_url( $b['blogs_id']  ); ?>"><?php echo $b['about']; ?><span class="book-link">&rarr;</span></a></p>
					<img src="<?php echo $b['cover_url']['pb_cover_medium']; ?>" alt="book-cover" width="225" height="<?php echo $b['cover_height']; ?>" />
				</div><!-- end .book -->

				<div class="book-info">
					<h2><?php echo $b['title']; ?></h2>

					<p><a href="<?php echo get_site_url( $b['blogs_id'] ); ?>"><?php echo $b['author']; ?></a></p>
				</div><!-- end book-info -->

			</div><!-- end .book-data -->
		<?php
		endforeach;
		?>


	</div>	<!-- end .catalog -->

</div><!-- end .catalog-wrap -->

<?php wp_footer(); ?>
</body>
</html>