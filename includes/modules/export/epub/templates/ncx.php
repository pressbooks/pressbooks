<?php

// @see: \PressBooks\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) )
	exit;

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>
<!DOCTYPE ncx PUBLIC "-//NISO//DTD ncx 2005-1//EN"
"http://www.daisy.org/z3986/2005/ncx-2005-1.dtd">

<ncx version="2005-1" xml:lang="en" xmlns="http://www.daisy.org/z3986/2005/ncx/">

	<head>
		<!-- The following four metadata items are required for all NCX documents,
		including those conforming to the relaxed constraints of OPS 2.0 -->

		<meta name="dtb:uid" content="<?php echo trim( $dtd_uid ); ?>" /> <!-- same as in .opf -->
		<meta name="dtb:depth" content="2"/> <!-- 1 or higher -->
		<meta name="dtb:totalPageCount" content="0"/> <!-- must be 0 -->
		<meta name="dtb:maxPageNumber" content="0"/> <!-- must be 0 -->
	</head>

	<docTitle>
		<text><?php bloginfo('name'); ?></text>
	</docTitle>

	<?php if ( ! empty( $author ) ): ?>
	<docAuthor>
		<text><?php echo $author; ?></text>
	</docAuthor>
	<?php endif; ?>

	<navMap>
		<?php
		// Map has a [ Part -> Chapter ] <NavPoint> hierarchy
		$i = 1;
		$part_open = false;
		foreach ( $manifest as $k => $v ) {

			if ( true == $part_open && ! preg_match( '/^chapter-/', $k ) ) {
				$part_open = false;
				echo '</navPoint>';
			}

			if ( get_post_meta( $v['ID'], 'pb_part_invisible', true ) !== 'on' ) {

				$text = strip_tags( \PressBooks\Sanitize\decode( $v['post_title'] ) );
				if ( ! $text ) $text = ' ';
	
				printf( '
					<navPoint id="%s" playOrder="%s">
					<navLabel><text>%s</text></navLabel>
					<content src="OEBPS/%s" />
					', $k, $i, $text, $v['filename'] );
	
				if ( preg_match( '/^part-/', $k ) ) {
					$part_open = true;
				} else {
					echo '</navPoint>';
				}
				
			++$i;

			}

		}
		if ( true == $part_open ) {
			echo '</navPoint>';
		}
		?>
	</navMap>
</ncx>