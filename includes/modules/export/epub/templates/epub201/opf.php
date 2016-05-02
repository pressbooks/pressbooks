<?php

// @see: \Pressbooks\Modules\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) )
	exit;

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>
<package version="2.0" xmlns="http://www.idpf.org/2007/opf" unique-identifier="PrimaryID">


	<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
		<?php
		// Required, Title
		echo '<dc:title>' . ( ! empty( $meta['pb_title'] ) ? $meta['pb_title'] : get_bloginfo( 'name' ) ) . '</dc:title>';
		unset( $meta['pb_title'] );
		echo "\n";

		// Required, Language
		echo '<dc:language>' . $lang . '</dc:language>';
		unset ( $meta['pb_language'] );
		echo "\n";

		// Required, Primary ID
		if ( ! empty( $meta['pb_ebook_isbn'] ) ) {
			echo '<dc:identifier id="PrimaryID" opf:scheme="ISBN">' . trim( $meta['pb_ebook_isbn'] ) . '</dc:identifier>';
		} else {
			echo '<dc:identifier id="PrimaryID" opf:scheme="URI">' . trim( get_bloginfo( 'url' ) ) . '</dc:identifier>';
		}
		unset( $meta['pb_ebook_isbn'] );
		echo "\n";

		// Pick best non-html description
		if ( ! empty( $meta['pb_about_50'] ) ) {
			echo "<dc:description>{$meta['pb_about_50']}</dc:description>\n";
			unset( $meta['pb_about_50'] );
		} elseif ( ! empty( $meta['pb_about_140'] ) ) {
			echo "<dc:description>{$meta['pb_about_140']}</dc:description>\n";
			unset( $meta['pb_about_140'] );
		}

		// Author
		echo '<dc:creator opf:role="aut"';
		if ( ! empty( $meta['pb_author_file_as'] ) ) {
			echo ' opf:file-as="' . $meta['pb_author_file_as'] . '"';
		}
		echo '>';
		if ( ! empty( $meta['pb_author'] ) ) {
			echo $meta['pb_author'];
		}
		echo '</dc:creator>' . "\n";
		unset( $meta['pb_author_file_as'], $meta['pb_author'] );
		
		// Contributing authors
		if ( ! empty( $meta['pb_contributing_authors'] ) ){
			$contributors = explode( ',', $meta['pb_contributing_authors'] );
			
			foreach ( $contributors as $contributor ){
				echo '<dc:contributor opf:role="aut">' . trim( $contributor ) . '</dc:contributor>' . "\n";
			}
			unset( $meta['pb_contributing_authors'] );
		}

		// Copyright
		if ( ! empty( $meta['pb_copyright_year'] ) || ! empty( $meta['pb_copyright_holder'] ) ) {
			echo '<dc:rights>';
			echo _( 'Copyright' ) . ' &#169; ';
			if ( ! empty( $meta['pb_copyright_year'] ) ) echo $meta['pb_copyright_year'] . ' ';
			if ( ! empty( $meta['pb_copyright_holder'] ) ) echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $meta['pb_copyright_holder'];
			if ( ! empty( $do_copyright_license ) ) echo '. ' . $do_copyright_license;
			echo "</dc:rights>\n";
		}
		unset( $meta['pb_copyright_year'], $meta['pb_copyright_holder'] );
		unset( $do_copyright_license );

		// Rest of metadata
		foreach ( $meta as $key => $val ) {
			switch ( $key ) {

				case 'pb_publisher' :
					echo "<dc:publisher>$val</dc:publisher>\n";
					break;

				case 'pb_publication_date' :
					echo '<dc:date opf:event="publication">';
					echo date( 'Y-m-d', (int) $val );
					echo "</dc:date>\n";
					break;

				case 'pb_bisac_subject' :
					$subjects = explode( ',', $val );
					foreach ( $subjects as $subject ) {
						echo '<dc:subject>' . trim( $subject ) . "</dc:subject>\n";
					}
					break;

				default:
					// TODO: echo "<!-- $key, $val -->\n";
					break;
			}
		}

		// Required (for Kindle), Cover
		echo '<meta name="cover" content="cover-image" />' . "\n";
		?>
	</metadata>


	<manifest>
		<?php
		foreach ( $manifest as $k => $v ) {
			printf( '<item id="%s" href="OEBPS/%s" media-type="application/xhtml+xml" />', $k, $v['filename'] );
			echo "\n";
		}
		echo $manifest_assets;
		?>
		<item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml" />
		<?php if ( ! empty( $stylesheet ) ): ?><item id="stylesheet" href="OEBPS/<?php echo $stylesheet; ?>"  media-type="text/css" /><?php endif; ?>
	</manifest>


	<spine toc="ncx">
		<?php
		foreach ( $manifest as $k => $v ) {

			$linear = 'yes';

			printf( '<itemref idref="%s" linear="%s" />', $k, $linear );
			echo "\n";
		}
		?>
	</spine>


	<guide>
		<reference type="toc" title="Table of Contents" href="OEBPS/table-of-contents.html" />
		<reference type="cover" title="cover" href="OEBPS/front-cover.html" />
		<?php

		/* Set the EPUB's start-point */

		// First, look if the user has set this themselves.
		$start_key = false;
		foreach ( $manifest as $key => $val ) {
			if ( $val['ID'] > 0 && get_post_meta( $val['ID'], 'pb_ebook_start', true ) ) {
				$start_key = $key;
				break;
			}
		}

		// If nothing was found, set « the first page after the table of contents » as start point
		if ( $start_key === false ) {
			$keys = array_keys( $manifest );
			$position = array_search( 'table-of-contents', $keys );
			if ( isset( $keys[$position + 1] ) ) {
				$start_key = $keys[$position + 1];
			}
		}

		if ( $start_key !== false ) {
			printf( '<reference type="text" title="start" href="OEBPS/%s" />', $manifest[$start_key]['filename'] );
			echo "\n";
		}

		?>
	</guide>


</package>
