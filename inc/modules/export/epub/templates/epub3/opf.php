<?php

// See templating function for reference: \Pressbooks\Modules\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use HumanNameParser\Parser;
use HumanNameParser\Exception\NameParsingException;
use function \Pressbooks\Utility\oxford_comma_explode;

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>
<package version="3.0" xmlns="http://www.idpf.org/2007/opf" unique-identifier="PrimaryID">

	<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
		<?php
		// Required, Title
		echo '<dc:title>' . ( ! empty( $meta['pb_title'] ) ? $meta['pb_title'] : get_bloginfo( 'name' ) ) . '</dc:title>';
		unset( $meta['pb_title'] );
		echo "\n";

		// Required, Language
		echo '<dc:language>' . $lang . '</dc:language>';
		unset( $meta['pb_language'] );
		echo "\n";

		// Required, Modification date
		echo '<meta property="dcterms:modified">' . ( date( 'Y-m-d\TH:i:s\Z' ) ) . '</meta>';
		echo "\n";

		// Required, Primary ID
		if ( ! empty( $meta['pb_ebook_isbn'] ) ) {
			echo '<dc:identifier id="PrimaryID">' . trim( $meta['pb_ebook_isbn'] ) . '</dc:identifier>';
		} else {
			echo '<dc:identifier id="PrimaryID">' . trim( get_bloginfo( 'url' ) ) . '</dc:identifier>';
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

		// First author
		if ( ! \Pressbooks\Utility\empty_space( $meta['pb_authors'] ) ) {
			$first_author = oxford_comma_explode( $meta['pb_authors'] )[0];
		} else {
			$first_author = __( 'Authored by: ', 'pressbooks' ) . get_bloginfo( 'url' );
		}
		echo '<dc:creator id="author">' . $first_author . '</dc:creator>' . "\n";

		// Contributing authors
		if ( ! empty( $meta['pb_authors'] ) ) {
			$contributors = oxford_comma_explode( $meta['pb_authors'] );
			foreach ( $contributors as $contributor ) {
				echo '<dc:contributor>' . trim( $contributor ) . '</dc:contributor>' . "\n";
			}
			unset( $meta['pb_authors'] );
		}
		if ( ! empty( $meta['pb_contributors'] ) ) {
			$contributors = oxford_comma_explode( $meta['pb_contributors'] );
			foreach ( $contributors as $contributor ) {
				echo '<dc:contributor>' . trim( $contributor ) . '</dc:contributor>' . "\n";
			}
			unset( $meta['pb_contributors'] );
		}

		// Refines
		echo '<meta refines="#author" property="file-as">';

		try {
			// TODO: Refactor to use term metadata: contributor_last_name, contributor_first_name
			$nameparser = new Parser();
			$author = $nameparser->parse( $first_author );
			$author_file_as = $author->getLastName() . ', ' . $author->getFirstName();
		} catch ( NameParsingException $e ) {
			$author_file_as = $first_author;
		}
		echo $first_author;
		echo '</meta>';
		unset( $meta['pb_authors'] );

		// Copyright
		if ( ! empty( $meta['pb_copyright_year'] ) || ! empty( $meta['pb_copyright_holder'] ) ) {
			echo '<dc:rights>';
			echo __( 'Copyright', 'pressbooks' ) . ' &#169; ';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				echo $meta['pb_copyright_year'];
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				echo strftime( '%Y', $meta['pb_publication_date'] );
			} else {
				echo date( 'Y' );
			}
			if ( ! empty( $meta['pb_copyright_holder'] ) ) {
				echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $meta['pb_copyright_holder'];
			}
			if ( ! empty( $do_copyright_license ) ) {
				echo '. ' . $do_copyright_license;
			}
			echo "</dc:rights>\n";
		}
		unset( $meta['pb_copyright_year'], $meta['pb_copyright_holder'] );
		unset( $do_copyright_license );

		// Rest of metadata
		foreach ( $meta as $key => $val ) {
			switch ( $key ) {

				case 'pb_publisher':
					echo "<dc:publisher>$val</dc:publisher>\n";
					break;

				case 'pb_publication_date':
					echo '<dc:date>';
					echo date( 'Y-m-d', (int) $val );
					echo "</dc:date>\n";
					break;

				case 'pb_bisac_subject':
					$subjects = explode( ',', $val );
					foreach ( $subjects as $subject ) {
						echo '<dc:subject>' . trim( $subject ) . "</dc:subject>\n";
					}
					break;

				default:
					// TODO: There should be default behaviour? E.g. echo "<!-- $key, $val -->\n";
					break;
			}
		}

		// Required for Kindle: Cover Image
		echo '<meta name="cover" content="cover-image" />' . "\n";
		?>
	</metadata>


	<manifest>
		<?php
		echo $manifest_filelist;
		echo $manifest_assets;
		?>
		<item id="toc" properties="nav" href="toc.xhtml" media-type="application/xhtml+xml"/>
		<item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml" />
		<?php if ( ! empty( $stylesheet ) ) : ?>
		<item id="stylesheet" href="OEBPS/<?php echo $stylesheet; ?>"  media-type="text/css" />
		<?php endif; ?>
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
		<reference type="toc" title="Table of Contents" href="OEBPS/table-of-contents.xhtml" />
		<reference type="cover" title="cover" href="OEBPS/front-cover.xhtml" />
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
		if ( false === $start_key ) {
			$keys = array_keys( $manifest );
			$position = array_search( 'table-of-contents', $keys, true );
			if ( isset( $keys[ $position + 1 ] ) ) {
				$start_key = $keys[ $position + 1 ];
			}
		}

		if ( false !== $start_key ) {
			printf( '<reference type="text" title="start" href="OEBPS/%s" />', $manifest[ $start_key ]['filename'] );
			echo "\n";
		}

		?>
	</guide>


</package>
