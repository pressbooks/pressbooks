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
	</div>
	</body>
</html>
