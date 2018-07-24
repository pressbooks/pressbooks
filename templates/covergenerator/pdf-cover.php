<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html>
	<head>
	<title><?php echo apply_filters( 'the_content', $title ); ?></title>
	<style><?php echo $css; ?></style>
	</head>
	<body>
	<div class="cover">
	  <div class="cover-front">
		  <div class="content">
			  <div class="title"><?php echo apply_filters( 'the_content', $title ); ?></div>
				<?php if ( ! empty( $subtitle ) ) { ?>
			  <div class="subtitle"><?php echo apply_filters( 'the_content', $subtitle ); ?></div>
				<?php } ?>
			  <div class="author"><?php echo apply_filters( 'the_content', $author ); ?></div>
		  </div>
	  </div>
	  <div class="cover-back">
		  <div class="content">
				<?php if ( ! empty( $about ) ) { ?>
			  <div class="about"><?php echo apply_filters( 'the_content', $about ); ?></div>
				<?php } ?>
				<?php if ( ! empty( $isbn_image ) ) { ?>
			  <img class="isbn" src="<?php echo $isbn_image; ?>" alt="ISBN"/>
				<?php } ?>
		  </div>
	  </div>
	  <div class="cover-spine">
		  <div class="content">
				<?php if ( ! empty( $spine_title ) ) { ?>
			  <span class="title"><?php echo wp_kses( $spine_title, [] ); ?></span>
				<?php } ?>
				<?php if ( ! empty( $spine_author ) ) { ?>
			  <span class="author"><?php echo wp_kses( $spine_author, [] ); ?></span>
				<?php } ?>
		  </div>
	  </div>
	</div>
	</body>
</html>
