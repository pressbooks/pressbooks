<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeOptions;

use Pressbooks\Container;
use Pressbooks\CustomCss;

class PDFOptions extends \Pressbooks\Options {

	/**
	 * The value for option: pressbooks_theme_options_pdf_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 2;

	/**
	* PDF theme options.
	*
	* @var array
	*/
	public $options;

	/**
	* PDF theme defaults.
	*
	* @var array
	*/
	public $defaults;

	/**
	* Constructor.
	*
	* @param array $options
	*/
	function __construct( array $options ) {
		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();
		$this->strings = $this->getStringOptions();
		$this->integers = $this->getIntegerOptions();
		$this->floats = $this->getFloatOptions();
		$this->predefined = $this->getPredefinedOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Configure the PDF options tab using the settings API.
	 */
	function init() {
		$_page = $_option = 'pressbooks_theme_options_' . $this->getSlug();
		$_section = $this->getSlug() . '_options_section';

		if ( false == get_option( $_option ) ) {
			add_option( $_option, $this->defaults );
		}

		add_settings_section(
			$_section,
			$this->getTitle(),
			array( $this, 'display' ),
			$_page
		);

		if ( \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) {
			add_settings_field(
				'pdf_body_font_size',
				__( 'Body Font Size', 'pressbooks' ),
				array( $this, 'renderBodyFontSizeField' ),
				$_page,
				$_section,
				array(
					__( 'Heading sizes are proportional to the body font size and will also be affected by this setting.', 'pressbooks' ),
					'pt',
				)
			);

			add_settings_field(
				'pdf_body_line_height',
				__( 'Body Line Height', 'pressbooks' ),
				array( $this, 'renderBodyLineHightField' ),
				$_page,
				$_section,
				array(
					'',
					'em',
				)
			);
		}

		add_settings_field(
			'pdf_page_size',
			__( 'Page Size', 'pressbooks' ),
			array( $this, 'renderPageSizeField' ),
			$_page,
			$_section,
			array(
				 __( 'Digest (5.5&quot; &times; 8.5&quot;)', 'pressbooks' ),
				 __( 'US Trade (6&quot; &times; 9&quot;)', 'pressbooks' ),
				 __( 'US Letter (8.5&quot; &times; 11&quot;)', 'pressbooks' ),
				 __( 'Custom (8.5&quot; &times; 9.25&quot;)', 'pressbooks' ),
				 __( 'Duodecimo (5&quot; &times; 7.75&quot;)', 'pressbooks' ),
				 __( 'Pocket (4.25&quot; &times; 7&quot;)', 'pressbooks' ),
				 __( 'A4 (21cm &times; 29.7cm)', 'pressbooks' ),
				 __( 'A5 (14.8cm &times; 21cm)', 'pressbooks' ),
				 __( '5&quot; &times; 8&quot;', 'pressbooks' ),
				 __( 'Custom&hellip;', 'pressbooks' ),
			)
		);

		add_settings_field(
			'pdf_page_width',
			__( 'Page Width', 'pressbooks' ),
			array( $this, 'renderPageWidthField' ),
			$_page,
			$_section,
			array(
				__( 'Page width must be expressed in CSS-compatible units, e.g. &lsquo;5.5in&rsquo; or &lsquo;10cm&rsquo;.' )
			)
		);

		add_settings_field(
			'pdf_page_height',
			__( 'Page Height', 'pressbooks' ),
			array( $this, 'renderPageHeightField' ),
			$_page,
			$_section,
			array(
				__( 'Page height must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.' )
			)
		);

		if ( \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) {
			add_settings_field(
				'pdf_page_margins',
				__( 'Margins', 'pressbooks' ),
				array( $this, 'renderMarginsField' ),
				$_page,
				$_section,
				array(
					__( 'Customize your book&rsquo;s margins using the fields below.', 'pressbooks' )
				)
			);

			add_settings_field(
				'pdf_page_margin_outside',
				__( 'Outside Margin', 'pressbooks' ),
				array( $this, 'renderOutsideMarginField' ),
				$_page,
				$_section,
				array(
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' )
				)
			);

			add_settings_field(
				'pdf_page_margin_inside',
				__( 'Inside Margin', 'pressbooks' ),
				array( $this, 'renderInsideMarginField' ),
				$_page,
				$_section,
				array(
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' )
				)
			);

			add_settings_field(
				'pdf_page_margin_top',
				__( 'Top Margin', 'pressbooks' ),
				array( $this, 'renderTopMarginField' ),
				$_page,
				$_section,
				array(
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' )
				)
			);

			add_settings_field(
				'pdf_page_margin_bottom',
				__( 'Bottom Margin', 'pressbooks' ),
				array( $this, 'renderBottomMarginField' ),
				$_page,
				$_section,
				array(
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' )
				)
			);
		}

		add_settings_field(
			'pdf_hyphens',
			__( 'Hyphens', 'pressbooks' ),
			array( $this, 'renderHyphenationField' ),
			$_page,
			$_section,
			array(
				 __( 'Enable hyphenation', 'pressbooks' )
			)
		);

		add_settings_field(
			'pdf_paragraph_separation',
			__( 'Paragraph Separation', 'pressbooks' ),
			array( $this, 'renderParagraphSeparationField' ),
			$_page,
			$_section,
			array(
				 'indent' => __( 'Indent paragraphs', 'pressbooks' ),
				 'skiplines' => __( 'Skip lines between paragraphs', 'pressbooks' ),
			)
		);

		add_settings_field(
			'pdf_sectionopenings',
			__( 'Section Openings', 'pressbooks' ),
			array( $this, 'renderSectionOpeningsField' ),
			$_page,
			$_section,
			array(
				 'openauto' => __( 'Left or right page section opening (for print PDF)', 'pressbooks' ),
				 'openright' => __( 'Right page section openings (for print PDF)', 'pressbooks' ),
				 'remove' => __( 'No blank pages (for web PDF)', 'pressbooks' ),
			)
		);

		add_settings_field(
			'pdf_toc',
			__( 'Table of Contents', 'pressbooks' ),
			array( $this, 'renderTOCField' ),
			$_page,
			$_section,
			array(
				 __( 'Display table of contents', 'pressbooks' )
			)
		);

		add_settings_field(
			'pdf_image_resolution',
			__( 'Image resolution', 'pressbooks' ),
			array( $this, 'renderImageResolutionField' ),
			$_page,
			$_section,
			array(
				'300dpi' => __( 'High (300 DPI)', 'pressbooks' ),
				'72dpi' => __( 'Low (72 DPI)', 'pressbooks' ),
			)
		);

		add_settings_field(
			'pdf_crop_marks',
			__( 'Crop Marks', 'pressbooks' ),
			array( $this, 'renderCropMarksField' ),
			$_page,
			$_section,
			array(
				 __( 'Display crop marks', 'pressbooks' )
			)
		);

		if ( CustomCss::isCustomCss() ) {
			add_settings_field(
				'pdf_romanize_parts',
				__( 'Romanize Part Numbers', 'pressbooks' ),
				array( $this, 'renderRomanizePartsField' ),
				$_page,
				$_section,
				array(
					 __( 'Convert part numbers into Roman numerals', 'pressbooks' )
				)
			);
		}

		add_settings_field(
			'pdf_footnotes_style',
			__( 'Footnote Style', 'pressbooks' ),
			array( $this, 'renderFootnoteStyleField' ),
			$_page,
			$_section,
			array(
				 'footnotes' => __( 'Regular footnotes', 'pressbooks' ),
				 'endnotes' => __( 'Display as chapter endnotes', 'pressbooks' ),
			)
		);

		add_settings_field(
			'widows',
			__( 'Widows', 'pressbooks' ),
			array( $this, 'renderWidowsField' ),
			$_page,
			$_section
		);

		add_settings_field(
			'orphans',
			__( 'Orphans', 'pressbooks' ),
			array( $this, 'renderOrphansField' ),
			$_page,
			$_section
		);

		if ( \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) {
			add_settings_field(
				'running_content',
				__( 'Running Heads & Feet', 'pressbooks' ),
				array( $this, 'renderRunningContentField' ),
				$_page,
				$_section,
				array(
					__( 'Running content appears in either running heads or running feet (at the top or bottom of the page) depending on your theme.', 'pressbooks' )
				)
			);

			add_settings_field(
				'running_content_front_matter_left',
				__( 'Front Matter Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentFrontMatterLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Front Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Front Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Front Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_front_matter_right',
				__( 'Front Matter Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentFrontMatterRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Front Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Front Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Front Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_introduction_left',
				__( 'Introduction Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentIntroductionLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Introduction Title', 'pressbooks' ),
					'%section_author%' => __( 'Introduction Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Introduction Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_introduction_right',
				__( 'Introduction Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentIntroductionRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Introduction Title', 'pressbooks' ),
					'%section_author%' => __( 'Introduction Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Introduction Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_part_left',
				__( 'Part Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentPartLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%part_number%' => __( 'Part Number', 'pressbooks' ),
					'%part_title%' => __( 'Part Title', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_part_right',
				__( 'Part Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentPartRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%part_number%' => __( 'Part Number', 'pressbooks' ),
					'%part_title%' => __( 'Part Title', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_chapter_left',
				__( 'Chapter Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentChapterLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%part_number%' => __( 'Part Number', 'pressbooks' ),
					'%part_title%' => __( 'Part Title', 'pressbooks' ),
					'%section_title%' => __( 'Chapter Title', 'pressbooks' ),
					'%section_author%' => __( 'Chapter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Chapter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_chapter_right',
				__( 'Chapter Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentChapterRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%part_number%' => __( 'Part Number', 'pressbooks' ),
					'%part_title%' => __( 'Part Title', 'pressbooks' ),
					'%section_title%' => __( 'Chapter Title', 'pressbooks' ),
					'%section_author%' => __( 'Chapter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Chapter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_back_matter_left',
				__( 'Back Matter Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentBackMatterLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Back Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Back Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Back Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);

			add_settings_field(
				'running_content_back_matter_right',
				__( 'Back Matter Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentBackMatterRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Back Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Back Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Back Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
				)
			);
		}

		if ( ! \Pressbooks\Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) {
			 add_settings_field(
				 'pdf_fontsize',
				 __( 'Increase Font Size', 'pressbooks' ),
				 array( $this, 'renderFontSizeField' ),
				 $_page,
				 $_section,
				 array(
					__( 'Increases font size and line height for greater accessibility', 'pressbooks' )
				 )
			 );
		}

		/**
		 * Add custom settings fields.
		 *
		 * @since 3.9.7
		 */
		do_action( 'pb_theme_options_pdf_add_settings_fields', $_page, $_section );

		register_setting(
			$_option,
			$_option,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Display the PDF options tab description.
	 */
	function display() {
		echo '<p>' . __( 'These options apply to PDF exports.', 'pressbooks' ) . '</p>';
	}

	/**
	 * Render the PDF options tab form (NOT USED).
	 */
	function render() {}

	/**
	 * Upgrade handler for PDF options.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		} elseif ( $version < 2 ) {
			$this->upgradeSectionOpenings();
		}
	}

	/**
	 * Substitute human-readable values, add new defaults, replace pdf_page_size
	 * with pdf_page_width and pdf_page_height.
	 */
	function doInitialUpgrade() {
		$_option = $this->getSlug();
		$options = get_option( 'pressbooks_theme_options_' . $_option, $this->defaults );

		// Replace pdf_page_size with pdf_page_width and pdf_page_height
		if ( isset( $options['pdf_page_size'] ) ) {
			switch ( $options['pdf_page_size'] ) {
				case 1:
					$options['pdf_page_width'] = '5.5in';
					$options['pdf_page_height'] = '8.5in';
					break;
				case 2:
					$options['pdf_page_width'] = '6in';
					$options['pdf_page_height'] = '9in';
					break;
				case 3:
					$options['pdf_page_width'] = '8.5in';
					$options['pdf_page_height'] = '11in';
					break;
				case 4:
					$options['pdf_page_width'] = '8.5in';
					$options['pdf_page_height'] = '9.25in';
					break;
				case 5:
					$options['pdf_page_width'] = '5in';
					$options['pdf_page_height'] = '7.75in';
					break;
				case 6:
					$options['pdf_page_width'] = '4.25in';
					$options['pdf_page_height'] = '7in';
					break;
				case 7:
					$options['pdf_page_width'] = '21cm';
					$options['pdf_page_height'] = '29.7cm';
					break;
				case 8:
					$options['pdf_page_width'] = '14.8cm';
					$options['pdf_page_height'] = '21cm';
					break;
				case 9:
					$options['pdf_page_width'] = '5in';
					$options['pdf_page_height'] = '8in';
					break;
				default:
					$options['pdf_page_width'] = '5.5in';
					$options['pdf_page_height'] = '8.5in';
			}
			unset( $options['pdf_page_size'] );
		}

		// Substitute human-readable values
		if ( ! isset( $options['pdf_paragraph_separation'] ) || '1' == $options['pdf_paragraph_separation'] ) {
			$options['pdf_paragraph_separation'] = 'indent';
		} elseif ( '2' == $options['pdf_paragraph_separation'] ) {
			$options['pdf_paragraph_separation'] = 'skiplines';
		}

		if ( ! isset( $options['pdf_blankpages'] ) || '1' == $options['pdf_blankpages'] ) {
			$options['pdf_blankpages'] = 'include';
		} elseif ( '2' == $options['pdf_blankpages'] ) {
			$options['pdf_blankpages'] = 'remove';
		}

		if ( ! isset( $options['pdf_footnotes_style'] ) || '1' == $options['pdf_footnotes_style'] ) {
			$options['pdf_footnotes_style'] = 'footnotes';
		} elseif ( '2' == $options['pdf_footnotes_style'] ) {
			$options['pdf_footnotes_style'] = 'endnotes';
		}

		// Add missing defaults.
		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $options[ $key ] ) ) {
				$options[ $key ] = $value;
			}
		}

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Replace pdf_blankpages option with pdf_sectionopenings option.
	 */
	function upgradeSectionOpenings() {
		$_option = $this->getSlug();
		$options = get_option( 'pressbooks_theme_options_' . $_option, $this->defaults );

		// Get more specific
		if ( ! isset( $options['pdf_blankpages'] ) || '1' == $options['pdf_blankpages'] || 'include' == $options['pdf_blankpages'] ) {
			$options['pdf_sectionopenings'] = 'openauto';
		} elseif ( '2' == $options['pdf_blankpages'] || 'remove' == $options['pdf_blankpages'] ) {
			$options['pdf_sectionopenings'] = 'remove';
		}
		unset( $options['pdf_blankpages'] );

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the pdf_body_font_size input.
	 * @param array $args
	 */
	function renderBodyFontSizeField( $args ) {
		$this->renderField( array(
			'id' => 'pdf_body_font_size',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_body_font_size',
			'value' => ( isset( $this->options['pdf_body_font_size'] ) ) ? $this->options['pdf_body_font_size'] : $this->defaults['pdf_body_font_size'],
			'description' => $args[0],
			'append' => $args[1],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the pdf_body_line_height input.
	 * @param array $args
	 */
	function renderBodyLineHightField( $args ) {
		$this->renderField( array(
			'id' => 'pdf_body_line_height',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_body_line_height',
			'value' => ( isset( $this->options['pdf_body_line_height'] ) ) ? $this->options['pdf_body_line_height'] : $this->defaults['pdf_body_line_height'],
			'description' => $args[0],
			'append' => $args[1],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the pdf_page_size select.
	 * @param array $args
	 */
	function renderPageSizeField( $args ) {
		if ( ! isset( $this->options['pdf_page_size'] ) ) {
			if ( isset( $this->options['pdf_page_width'] ) && isset( $this->options['pdf_page_height'] ) ) {
				if ( '5.5in' == $this->options['pdf_page_width'] && '8.5in' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 1;
				} elseif ( '6in' == $this->options['pdf_page_width'] && '9in' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 2;
				} elseif ( '8.5in' == $this->options['pdf_page_width'] && '11in' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 3;
				} elseif ( '8.5in' == $this->options['pdf_page_width'] && '9.25in' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 4;
				} elseif ( '5in' == $this->options['pdf_page_width'] && '7.75in' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 5;
				} elseif ( '4.25in' == $this->options['pdf_page_width'] && '7in' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 6;
				} elseif ( '21cm' == $this->options['pdf_page_width'] && '29.7cm' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 7;
				} elseif ( '14.8cm' == $this->options['pdf_page_width'] && '21cm' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 8;
				} elseif ( '5in' == $this->options['pdf_page_width'] && '8in' == $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 9;
				} else {
					$this->options['pdf_page_size'] = 10;
				}
			} else {
				$this->options['pdf_page_size'] = 1;
			}
		}

		$html = "<select name='pressbooks_theme_options_pdf[pdf_page_size]' id='pdf_page_size' >";
		foreach ( $args as $key => $val ) {
			$html .= "<option value='" . ( $key + 1 ) . "' " . selected( $key + 1, $this->options['pdf_page_size'], false ) . ">$val</option>";
		}
		$html .= '<select>';
		echo $html;
	}

	/**
	 * Render the pdf_page_width input.
	 * @param array $args
	 */
	function renderPageWidthField( $args ) {
		$this->renderField( array(
			'id' => 'pdf_page_width',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_page_width',
			'value' => @$this->options['pdf_page_width'],
			'description' => $args[0],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the pdf_page_height input.
	 * @param array $args
	 */
	function renderPageHeightField( $args ) {
		$this->renderField( array(
			'id' => 'pdf_page_height',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_page_height',
			'value' => @$this->options['pdf_page_height'],
			'description' => $args[0],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the margins diagram.
	 * @param array $args
	 */
	function renderMarginsField( $args ) {
	?>
		<div class="margin-diagram">
			<p class="description"><?php echo $args[0]; ?></p>
			<div class="pages">
				<div class="page left">
					<div class="margin outside"></div>
					<div class="margin top"></div>
					<div class="margin inside"></div>
					<div class="margin bottom"></div>
				</div>
				<div class="page right">
					<div class="margin inside"></div>
					<div class="margin top"></div>
					<div class="margin outside"></div>
					<div class="margin bottom"></div>
				</div>
			</div>
			<div class="legend">
				<ul>
					<li class="outside"><span class="color"></span> <?php _e( 'Outside Margin', 'pressbooks' ); ?></li>
					<li class="inside"><span class="color"></span> <?php _e( 'Inside Margin', 'pressbooks' ); ?></li>
					<li class="top"><span class="color"></span> <?php _e( 'Top Margin', 'pressbooks' ); ?></li>
					<li class="bottom"><span class="color"></span> <?php _e( 'Bottom Margin', 'pressbooks' ); ?></li>
				</ul>
			</div>
		</div>
		<p><strong><?php _e( 'IMPORTANT: If you plan to use a print-on-demand service, margins under 2cm on any side can cause your file to be rejected.', 'pressbooks' ); ?></strong></p>
	<?php }

	/**
	 * Render the pdf_page_margin_outside input.
	 * @param array $args
	 */
	function renderOutsideMarginField( $args ) {
	?>
		<?php $this->renderField( array(
			'id' => 'pdf_page_margin_outside',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_page_margin_outside',
			'value' => @$this->options['pdf_page_margin_outside'],
			'description' => $args[0],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the pdf_page_margin_inside input.
	 * @param array $args
	 */
	function renderInsideMarginField( $args ) {
		$this->renderField( array(
			'id' => 'pdf_page_margin_inside',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_page_margin_inside',
			'value' => @$this->options['pdf_page_margin_inside'],
			'description' => $args[0],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the pdf_page_margin_top input.
	 * @param array $args
	 */
	function renderTopMarginField( $args ) {
		$this->renderField( array(
			'id' => 'pdf_page_margin_top',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_page_margin_top',
			'value' => @$this->options['pdf_page_margin_top'],
			'description' => $args[0],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the pdf_page_margin_bottom input.
	 * @param array $args
	 */
	function renderBottomMarginField( $args ) {
		$this->renderField( array(
			'id' => 'pdf_page_margin_bottom',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_page_margin_bottom',
			'value' => @$this->options['pdf_page_margin_bottom'],
			'description' => $args[0],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the pdf_hyphens checkbox.
	 * @param array $args
	 */
	function renderHyphenationField( $args ) {
		$this->renderCheckbox( array(
			'id' => 'pdf_hyphens',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_hyphens',
			'value' => @$this->options['pdf_hyphens'],
			'label' => $args[0],
		) );
	}

	/**
	 * Render the pdf_paragraph_separation radio buttons.
	 * @param array $args
	 */
	function renderParagraphSeparationField( $args ) {
		$this->renderRadioButtons( array(
			'id' => 'pdf_paragraph_separation',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_paragraph_separation',
			'value' => @$this->options['pdf_paragraph_separation'],
			'choices' => $args,
		) );
	}

	/**
	 * Render the pdf_sectionopenings radio buttons.
	 * @param array $args
	 */
	function renderSectionOpeningsField( $args ) {
		$this->renderRadioButtons( array(
			'id' => 'pdf_sectionopenings',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_sectionopenings',
			'value' => @$this->options['pdf_sectionopenings'],
			'choices' => $args,
		) );
	}

	/**
	 * Render the pdf_toc checkbox.
	 * @param array $args
	 */
	function renderTOCField( $args ) {
		$this->renderCheckbox( array(
			'id' => 'pdf_toc',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_toc',
			'value' => @$this->options['pdf_toc'],
			'label' => $args[0],
		) );
	}

	/**
	 * Render the pdf_image_resolution radio buttons.
	 * @param array $args
	 */
	function renderImageResolutionField( $args ) {
		$this->renderRadioButtons( array(
			'id' => 'pdf_image_resolution',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_image_resolution',
			'value' => @$this->options['pdf_image_resolution'],
			'choices' => $args,
		) );
	}

	/**
	 * Render the pdf_crop_marks checkbox.
	 * @param array $args
		*/
	function renderCropMarksField( $args ) {
		$this->renderCheckbox( array(
			'id' => 'pdf_crop_marks',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_crop_marks',
			'value' => @$this->options['pdf_crop_marks'],
			'label' => $args[0],
		) );
	}

	/**
	 * Render the pdf_romanize_parts checkbox.
	 * @param array $args
	 */
	function renderRomanizePartsField( $args ) {
		$this->renderCheckbox( array(
			'id' => 'pdf_romanize_parts',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_romanize_parts',
			'value' => @$this->options['pdf_romanize_parts'],
			'label' => $args[0],
		) );
	}

	/**
	 * Render the pdf_footnotes_style radio buttons.
	 * @param array $args
	 */
	function renderFootnoteStyleField( $args ) {
		$this->renderRadioButtons( array(
			'id' => 'pdf_footnotes_style',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_footnotes_style',
			'value' => @$this->options['pdf_footnotes_style'],
			'choices' => $args,
		) );
	}

	/**
	 * Render the widows input.
	 * @param array $args
	 */
	function renderWidowsField( $args ) {
		$this->renderField( array(
			'id' => 'widows',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'widows',
			'value' => @$this->options['widows'],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the orphans input.
	 * @param array $args
	 */
	function renderOrphansField( $args ) {
		$this->renderField( array(
			'id' => 'orphans',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'orphans',
			'value' => @$this->options['orphans'],
			'type' => 'text',
			'class' => 'small-text',
		) );
	}

	/**
	 * Render the running content instructional diagram.
	 * @param array $args
	 */
	function renderRunningContentField( $args ) {
	?>
		<p class="description"><?php echo $args[0]; ?></p>
	<?php }

	/**
	 * Render the running_content_front_matter_left input.
	 * @param array $args
	 */
	function renderRunningContentFrontMatterLeftField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_front_matter_left',
			'name' => 'running_content_front_matter_left',
			'value' => @$this->options['running_content_front_matter_left'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_front_matter_left',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_front_matter_left',
			'value' => @$this->options['running_content_front_matter_left'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_front_matter_right input.
	 * @param array $args
	 */
	function renderRunningContentFrontMatterRightField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_front_matter_right',
			'name' => 'running_content_front_matter_right',
			'value' => @$this->options['running_content_front_matter_right'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_front_matter_right',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_front_matter_right',
			'value' => @$this->options['running_content_front_matter_right'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_introduction_left input.
	 * @param array $args
	 */
	function renderRunningContentIntroductionLeftField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_introduction_left',
			'name' => 'running_content_introduction_left',
			'value' => @$this->options['running_content_introduction_left'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_introduction_left',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_introduction_left',
			'value' => @$this->options['running_content_introduction_left'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_introduction_right input.
	 * @param array $args
	 */
	function renderRunningContentIntroductionRightField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_introduction_right',
			'name' => 'running_content_introduction_right',
			'value' => @$this->options['running_content_introduction_right'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_introduction_right',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_introduction_right',
			'value' => @$this->options['running_content_introduction_right'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_part_left input.
	 * @param array $args
	 */
	function renderRunningContentPartLeftField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_part_left',
			'name' => 'running_content_part_left',
			'value' => @$this->options['running_content_part_left'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_part_left',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_part_left',
			'value' => @$this->options['running_content_part_left'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_part_right input.
	 * @param array $args
	 */
	function renderRunningContentPartRightField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_part_right',
			'name' => 'running_content_part_right',
			'value' => @$this->options['running_content_part_right'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_part_right',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_part_right',
			'value' => @$this->options['running_content_part_right'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_chapter_left input.
	 * @param array $args
	 */
	function renderRunningContentChapterLeftField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_chapter_left',
			'name' => 'running_content_chapter_left',
			'value' => @$this->options['running_content_chapter_left'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_chapter_left',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_chapter_left',
			'value' => @$this->options['running_content_chapter_left'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_chapter_right input.
	 * @param array $args
	 */
	function renderRunningContentChapterRightField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_chapter_right',
			'name' => 'running_content_chapter_right',
			'value' => @$this->options['running_content_chapter_right'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_chapter_right',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_chapter_right',
			'value' => @$this->options['running_content_chapter_right'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_back_matter_left input.
	 * @param array $args
	 */
	function renderRunningContentBackMatterLeftField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_back_matter_left',
			'name' => 'running_content_back_matter_left',
			'value' => @$this->options['running_content_back_matter_left'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_back_matter_left',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_back_matter_left',
			'value' => @$this->options['running_content_back_matter_left'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the running_content_back_matter_right input.
	 * @param array $args
	 */
	function renderRunningContentBackMatterRightField( $args ) {
		$this->renderCustomSelect( array(
			'id' => 'running_content_back_matter_right',
			'name' => 'running_content_back_matter_right',
			'value' => @$this->options['running_content_back_matter_right'],
			'choices' => $args,
		) );
		$this->renderField( array(
			'id' => 'running_content_back_matter_right',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'running_content_back_matter_right',
			'value' => @$this->options['running_content_back_matter_right'],
			'type' => 'text',
			'class' => 'regular-text code',
		) );
	}

	/**
	 * Render the pdf_fontsize checkbox.
	 * @param array $args
	 */
	function renderFontSizeField( $args ) {
		$this->renderCheckbox( array(
			'id' => 'pdf_fontsize',
			'name' => 'pressbooks_theme_options_' . $this->getSlug(),
			'option' => 'pdf_fontsize',
			'value' => @$this->options['pdf_fontsize'],
			'label' => $args[0],
		) );
	}

	/**
	 * Get the slug for the PDF options tab.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
		return 'pdf';
	}

	/**
	 * Get the localized title of the PDF options tab.
	 *
	 * @return string $title
	 */
	static function getTitle() {
		return __( 'PDF Options', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the PDF options tab.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		/**
		 * @since 3.9.7 TODO
		 */
		return apply_filters( 'pb_theme_options_pdf_defaults', array(
			'pdf_body_font_size' => '11',
			'pdf_body_line_height' => '1.4',
			'pdf_page_width' => '5.5in',
			'pdf_page_height' => '8.5in',
			'pdf_page_margin_outside' => '2cm',
			'pdf_page_margin_inside' => '2cm',
			'pdf_page_margin_top' => '2cm',
			'pdf_page_margin_bottom' => '2cm',
			'pdf_hyphens' => 0,
			'pdf_paragraph_separation' => 'indent',
			'pdf_sectionopenings' => 'openauto',
			'pdf_toc' => 1,
			'pdf_image_resolution' => '300dpi',
			'pdf_crop_marks' => 0,
			'pdf_romanize_parts' => 1,
			'pdf_footnotes_style' => 'footnotes',
			'widows' => 2,
			'orphans' => 1,
			'running_content_front_matter_left' => '%book_title%',
			'running_content_front_matter_right' => '%section_title%',
			'running_content_introduction_left' => '%book_title%',
			'running_content_introduction_right' => '%section_title%',
			'running_content_part_left' => '%book_title%',
			'running_content_part_right' => '%part_title%',
			'running_content_chapter_left' => '%book_title%',
			'running_content_chapter_right' => '%section_title%',
			'running_content_back_matter_left' => '%book_title%',
			'running_content_back_matter_right' => '%section_title%',
			'pdf_fontsize' => 0,
		) );
	}

	/**
	 * Filter the array of default values for the PDF options tab.
	 *
	 * @param array $defaults
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {
		return $defaults;
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		/**
		 * Allow custom boolean options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 */
		return apply_filters( 'pb_theme_options_pdf_booleans', array(
			'pdf_hyphens',
			'pdf_toc',
			'pdf_crop_marks',
			'pdf_romanize_parts',
			'pdf_fontsize',
		) );
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	static function getStringOptions() {
		/**
		 * Allow custom string options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 */
		return apply_filters( 'pb_theme_options_pdf_strings', array(
			'pdf_page_width',
			'pdf_page_height',
			'pdf_page_margin_outside',
			'pdf_page_margin_inside',
			'pdf_page_margin_top',
			'pdf_page_margin_bottom',
			'running_content_front_matter_left',
			'running_content_front_matter_right',
			'running_content_introduction_left',
			'running_content_introduction_right',
			'running_content_part_left',
			'running_content_part_right',
			'running_content_chapter_left',
			'running_content_chapter_right',
			'running_content_back_matter_left',
			'running_content_back_matter_right',
		) );
	}

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	static function getIntegerOptions() {
		/**
		 * Allow custom integer options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 */
		return apply_filters( 'pb_theme_options_pdf_integers', array(
			'pdf_body_font_size',
			'widows',
			'orphans',
		) );
	}

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	static function getFloatOptions() {
		/**
		 * Allow custom float options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 */
		return apply_filters( 'pb_theme_options_pdf_floats', array(
			'pdf_body_line_height'
		) );
	}

	/**
	 * Get an array of options which return predefined values.
	 *
	 * @return array $options
	 */
	static function getPredefinedOptions() {
		/**
		 * Allow custom predifined options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 */
		return apply_filters( 'pb_theme_options_pdf_predefined', array(
			'pdf_paragraph_separation',
			'pdf_sectionopenings',
			'pdf_image_resolution',
			'pdf_footnotes_style',
		) );
	}

	/**
	 * Replace running content tags with strings.
	 *
	 * @since 3.9.8
	 */
	static function replaceRunningContentTags( $input ) {
		$input = '"' . $input . '"';

		return str_replace(
			array(
				'%book_title%',
				'%book_subtitle%',
				'%book_author%',
				'%part_number%',
				'%part_title%',
				'%section_title%',
				'%section_author%',
				'%section_subtitle%',
				'%blank%',
			),
			array(
				'" string(book-title) "',
				'" string(book-subtitle) "',
				'" string(book-author) "',
				'" string(part-number) "',
				'" string(part-title) "',
				'" string(section-title) "',
				'" string(chapter-author) "',
				'" string(chapter-subtitle) "',
				'',
			),
			$input
		);
	}

	/**
	 * Apply overrides.
	 *
	 * @since 3.9.8
	 */
	static function scssOverrides( $scss ) {
		$scss .= "/* Theme Options */\n";

		// --------------------------------------------------------------------
		// Global Options

		$sass = \Pressbooks\Container::get( 'Sass' );
		$options = get_option( 'pressbooks_theme_options_global' );

		// Display chapter numbers? true (default) / false
		if ( ! $options['chapter_numbers'] ) {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$chapter-number-display: none; \n";
				$scss .= "\$part-number-display: none; \n";
				$scss .= "\$toc-chapter-number-display: none; \n";
				$scss .= "\$toc-part-number-display: none; \n";
			} else {
				$scss .= "div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number, #toc .part a::before, #toc .chapter a::before { display: none !important; } \n";
			}
		}

		// --------------------------------------------------------------------
		// PDF Options

		$options = get_option( 'pressbooks_theme_options_pdf' );

		// Change body font size
		if ( $sass->isCurrentThemeCompatible( 2 ) && isset( $options['pdf_body_font_size'] ) ) {
			$fontsize = $options['pdf_body_font_size'] . 'pt';
			$scss .= "\$body-font-size: (\n
				epub: medium,\n
				prince: $fontsize,
				web: 14pt\n
			); \n";
		}

		// Change body line height
		if ( $sass->isCurrentThemeCompatible( 2 ) && isset( $options['pdf_body_line_height'] ) ) {
			$lineheight = $options['pdf_body_line_height'] . 'em';
			$scss .= "\$body-line-height: (\n
				epub: 1.4em,\n
				prince: $lineheight,
				web: 1.8em,\n
			); \n";
		}

		// Page dimensions
		$width = $options['pdf_page_width'];
		$height = $options['pdf_page_height'];

		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$page-width: $width; \n";
			$scss .= "\$page-height: $height; \n";
		} else {
			$scss .= "@page { size: $width $height; } \n";
		}

		// Margins
		$outside = ( isset( $options['pdf_page_margin_outside'] ) ) ? $options['pdf_page_margin_outside'] : '2cm';
		$inside = ( isset( $options['pdf_page_margin_inside'] ) ) ? $options['pdf_page_margin_inside'] : '2cm';
		$top = ( isset( $options['pdf_page_margin_top'] ) ) ? $options['pdf_page_margin_top'] : '2cm';
		$bottom = ( isset( $options['pdf_page_margin_bottom'] ) ) ? $options['pdf_page_margin_bottom'] : '2cm';

		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$page-margin-left-top: $top; \n";
			$scss .= "\$page-margin-left-right: $inside; \n";
			$scss .= "\$page-margin-left-bottom: $bottom; \n";
			$scss .= "\$page-margin-left-left: $outside; \n";
			$scss .= "\$page-margin-right-top: $top; \n";
			$scss .= "\$page-margin-right-right: $outside; \n";
			$scss .= "\$page-margin-right-bottom: $bottom; \n";
			$scss .= "\$page-margin-right-left: $inside; \n";
		}

		// Image resolution
		if ( isset( $options['pdf_image_resolution'] ) ) {
			$resolution = $options['pdf_image_resolution'];
		} else {
			$resolution = '300dpi';
		}
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "\$prince-image-resolution: $resolution !default; \n";
		} else {
			$scss .= "img { prince-image-resolution: $resolution; } \n";
		}

		// Display crop marks? true / false (default)
		if ( 1 == $options['pdf_crop_marks'] ) {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$page-cropmarks: crop; \n";
			} else {
				$scss .= "@page { marks: crop } \n";
			}
		}

		// Hyphens?
		if ( 1 == $options['pdf_hyphens'] ) {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$para-hyphens: auto; \n"; // TODO
			} else {
				$scss .= "p { hyphens: auto; } \n";
			}
		} else {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$para-hyphens: manual; \n"; // TODO
			} else {
				$scss .= "p { hyphens: manual; } \n";
			}
		}

		// Indent paragraphs?
		if ( 'skiplines' == $options['pdf_paragraph_separation'] ) {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$para-margin-top: 1em; \n";
				$scss .= "\$para-indent: 0; \n";
			} else {
				$scss .= "p + p { text-indent: 0em; margin-top: 1em; } \n";
			}
		}

		// Include blank pages?
		if ( isset( $options['pdf_sectionopenings'] ) ) {
			if ( 'openright' == $options['pdf_sectionopenings'] ) {
				if ( $sass->isCurrentThemeCompatible( 2 ) ) {
					$scss .= "\$recto-verso-standard-opening: right; \n";
					$scss .= "\$recto-verso-first-section-opening: right; \n";
					$scss .= "\$recto-verso-section-opening: right; \n";
				} else {
					$scss .= "#title-page, #toc, div.part, div.front-matter, div.front-matter.introduction, div.front-matter + div.front-matter, div.chapter, div.chapter + div.chapter, div.back-matter, div.back-matter + div.back-matter, #half-title-page h1.title:first-of-type  { page-break-before: right; } \n";
					$scss .= "#copyright-page { page-break-before: left; }\n";
				}
			} elseif ( 'remove' == $options['pdf_sectionopenings'] ) {
				if ( $sass->isCurrentThemeCompatible( 2 ) ) {
					$scss .= "\$recto-verso-standard-opening: auto; \n";
					$scss .= "\$recto-verso-first-section-opening: auto; \n";
					$scss .= "\$recto-verso-section-opening: auto; \n";
					$scss .= "\$recto-verso-copyright-page-opening: auto; \n";
				} else {
					$scss .= "#title-page, #copyright-page, #toc, div.part, div.front-matter, div.back-matter, div.chapter, #half-title-page h1.title:first-of-type  { page-break-before: auto; } \n";
				}
			}
		}

		// Display TOC? true (default) / false
		if ( ! $options['pdf_toc'] ) {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$toc-display: none; \n";
			} else {
				$scss .= "#toc { display: none; } \n";
			}
		}

		// Widows
		if ( isset( $options['widows'] ) ) {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$widows: {$options['widows']}; \n";
			} else {
				$scss .= "p { widows: {$options['widows']}; }\n";
			}
		} else {
			if ( ! $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= 'p { widows: 2; }' . "\n";
			}
		}

		// Orphans
		if ( isset( $options['orphans'] ) ) {
			if ( $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= "\$orphans: {$options['orphans']}; \n";
			} else {
				$scss .= "p { orphans: {$options['orphans']}; }\n";
			}
		} else {
			if ( ! $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= 'p { orphans: 1; }' . "\n";
			}
		}

		// Running Content
		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$front_matter_running_content_left = ( isset( $options['running_content_front_matter_left'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_front_matter_left'] ) : 'string(book-title)';
			$front_matter_running_content_right = ( isset( $options['running_content_front_matter_right'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_front_matter_right'] ) : 'string(section-title)';
			$introduction_running_content_left = ( isset( $options['running_content_introduction_left'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_introduction_left'] ) : 'string(book-title)';
			$introduction_running_content_right = ( isset( $options['running_content_introduction_right'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_introduction_right'] ) : 'string(section-title)';
			$part_running_content_left = ( isset( $options['running_content_part_left'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_part_left'] ) : 'string(book-title)';
			$part_running_content_right = ( isset( $options['running_content_part_right'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_part_right'] ) : 'string(part-title)';
			$chapter_running_content_left = ( isset( $options['running_content_chapter_left'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_chapter_left'] ) : 'string(book-title)';
			$chapter_running_content_right = ( isset( $options['running_content_chapter_right'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_chapter_right'] ) : 'string(section-title)';
			$back_matter_running_content_left = ( isset( $options['running_content_back_matter_left'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_back_matter_left'] ) : 'string(book-title)';
			$back_matter_running_content_right = ( isset( $options['running_content_back_matter_right'] ) ) ? \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( $options['running_content_back_matter_right'] ) : 'string(section-title)';
			$scss .= "\$front-matter-running-content-left: $front_matter_running_content_left; \n";
			$scss .= "\$front-matter-running-content-right: $front_matter_running_content_right; \n";
			$scss .= "\$introduction-running-content-left: $introduction_running_content_left; \n";
			$scss .= "\$introduction-running-content-right: $introduction_running_content_right; \n";
			$scss .= "\$part-running-content-left: $part_running_content_left; \n";
			$scss .= "\$part-running-content-right: $part_running_content_right; \n";
			$scss .= "\$chapter-running-content-left: $chapter_running_content_left; \n";
			$scss .= "\$chapter-running-content-right: $chapter_running_content_right; \n";
			$scss .= "\$back-matter-running-content-left: $back_matter_running_content_left; \n";
			$scss .= "\$back-matter-running-content-right: $back_matter_running_content_right; \n";
		}

		// a11y Font Size
		if ( @$options['pdf_fontsize'] ) {
			if ( ! $sass->isCurrentThemeCompatible( 2 ) ) {
				$scss .= 'body { font-size: 1.3em; line-height: 1.3; }' . "\n";
			}
		}

		return $scss;
	}
}
