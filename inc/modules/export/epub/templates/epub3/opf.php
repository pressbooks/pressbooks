<?php
/**
 * File description
 *
 * @tags
 * See templating function for reference: \Pressbooks\Modules\Export\Export loadTemplate()
 */
// TODO: Review escaping in the next refactor
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function \Pressbooks\Sanitize\sanitize_xml_attribute;
use function \Pressbooks\Utility\explode_remove_and;
use HumanNameParser\Exception\NameParsingException;
use HumanNameParser\Parser;

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
        // TODO: Add DOI?
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
        // Check to see if we have pb_editors.
        // If yes, add each one as a dc:creator with role edt in order in which they are listed in book info
        // Check to see if we have pb_authors.
        // yes, add each one as a dc:creator with role aut in the order in which they are listed in book info
        // Check to see if we have pb_translators
        // If yes, add each one as a dc:creator with role trl
        // Check to see if we have pb_illustrators
        // If yes, add each one as a dec:creator with role ill
        $index = 1;
        if ( ! \Pressbooks\Utility\empty_space( $meta['pb_editors'] ) ) {
            $editors = explode_remove_and(';', $meta['pb_editors']);
            foreach ( $editors as $editor ) {
                $contributor_number = $index;
                if ( $index < 10 ) {
                    $contributor_number = str_pad( $index, 2, '0', STR_PAD_LEFT );
                }
                echo "<dc:creator id='creator{$contributor_number}'>{$editor}</dc:creator>\n";
                echo "<meta refines='#creator{$contributor_number}' property='role' scheme='marc:relators' id='role'>edt</meta>\n";
                // TODO: add file-as if possible
                // echo "<meta refines='#creator{$contributor_number}' property='file-as'>last name, first name</meta>";
                $index++;
            }
        }
        if ( ! \Pressbooks\Utility\empty_space( $meta['pb_authors'] ) ) {
            $authors = explode_remove_and(';', $meta['pb_authors']);
            foreach ( $authors as $author ) {
                $contributor_number = $index;
                if ( $index < 10 ) {
                    $contributor_number = str_pad( $index, 2, '0', STR_PAD_LEFT );
                }
                echo "<dc:creator id='creator{$contributor_number}'>{$author}</dc:creator>\n";
                echo "<meta refines='#creator{$contributor_number}' property='role' scheme='marc:relators' id='role'>aut</meta>\n";
                // TODO: add file-as if possible
                // echo "<meta refines='#creator{$contributor_number}' property='file-as'>last name, first name</meta>";
                $index++;
            }
        }
        if ( ! \Pressbooks\Utility\empty_space( $meta['pb_translators'] ) ) {
            $translators = explode_remove_and( ';', $meta['pb_translators'] );
            foreach ( $translators as $translator ) {
                $contributor_number = $index;
                if ( $index < 10 ) {
                    $contributor_number = str_pad( $index, 2, '0', STR_PAD_LEFT );
                }
                echo "dc:creator id='creator{$contributor_number}>{$translator}</dc:creator>\n";
                echo "<meta refines='#creator{$contributor_number}' property='role' scheme='marc:relators' id='role'>trl</meta>\n";
                $index++;
            }
        }
        if ( ! Pressbooks\Utility\empty_space( $meta['pb_illustrators'] ) ) {
            $illustrators = explode_remove_and( ';', $meta['pb_illustrators'] );
            foreach( $illustrators as $illustrator ) {
                $contributor_number = $index;
                if( $index < 10 ) {
                    $contributor_number = str_pad( $index, 2, '0', STR_PAD_LEFT );
                }
                echo "dc:creator id='creator{$contributor_number}>{$illustrator}</dc:creator>\n";
                echo "<meta refines='#creator{$contributor_number}' property='role' scheme='marc:relators' id='role'>ill</meta>\n";
                $index++;
            }
        }
        if ( $index === 1 ) {
			echo '<dc:creator id="creator">Pressbooks</dc:creator>';
		}

		// Contributing authors
        // TODO: Check to see if we have pb_contributor
        // If yes, add each one as a dc:contributor with role ctb
		if ( ! empty( $meta['pb_contributors'] ) ) {
			$contributors = explode_remove_and( ';', $meta['pb_contributors'] );
			foreach ( $contributors as $contributor ) {
				echo '<dc:contributor>' . trim( $contributor ) . '</dc:contributor>' . "\n";
                echo "<meta refines='#contributor{$contributor_number}' property='role' scheme='marc:relators' id='role'>ctb</meta>";
			}
			// TODO: WHY DO THIS?
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
			echo sanitize_xml_attribute( __( 'Copyright', 'pressbooks' ) ) . ' &#169; ';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				echo $meta['pb_copyright_year'];
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				echo strftime( '%Y', $meta['pb_publication_date'] );
			} else {
				echo date( 'Y' );
			}
			if ( ! empty( $meta['pb_copyright_holder'] ) ) {
				echo ' ' . sanitize_xml_attribute( __( 'by', 'pressbooks' ) ) . ' ' . $meta['pb_copyright_holder'];
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
		$start_id = false;
		$ebook_options = get_option( 'pressbooks_theme_options_ebook', [] );
		if ( isset( $ebook_options['ebook_start_point'] ) && ! empty( $ebook_options['ebook_start_point'] ) ) {
			$start_id = $ebook_options['ebook_start_point'];
		}

		// Determine the manifest key for the post ID.
		foreach ( $manifest as $key => $value ) {
			if ( $start_id === $value['ID'] ) {
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
