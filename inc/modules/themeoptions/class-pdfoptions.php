<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\ThemeOptions;

use function \Pressbooks\Utility\getset;
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
		$_option = 'pressbooks_theme_options_' . $this->getSlug();
		$_page = $_option;
		$_section = $this->getSlug() . '_options_section';

		if ( false === get_option( $_option ) ) {
			add_option( $_option, $this->defaults );
		}

		add_settings_section(
			$_section,
			$this->getTitle(),
			[ $this, 'display' ],
			$_page
		);

		$custom_styles = Container::get( 'Styles' );
		$v2_compatible = $custom_styles->isCurrentThemeCompatible( 2 );

		if ( $v2_compatible ) {
			add_settings_field(
				'pdf_body_font_size',
				__( 'Body Font Size', 'pressbooks' ),
				[ $this, 'renderBodyFontSizeField' ],
				$_page,
				$_section,
				[
					__( 'Heading sizes are proportional to the body font size and will also be affected by this setting.', 'pressbooks' ),
					'pt',
					'label_for' => 'pdf_body_font_size',
				]
			);

			add_settings_field(
				'pdf_body_line_height',
				__( 'Body Line Height', 'pressbooks' ),
				[ $this, 'renderBodyLineHightField' ],
				$_page,
				$_section,
				[
					'',
					'em',
					'label_for' => 'pdf_body_line_height',
				]
			);
		}

		add_settings_field(
			'pdf_page_size',
			__( 'Page Size', 'pressbooks' ),
			[ $this, 'renderPageSizeField' ],
			$_page,
			$_section,
			[
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
				'label_for' => 'pdf_page_size',
			]
		);

		add_settings_field(
			'pdf_page_width',
			__( 'Page Width', 'pressbooks' ),
			[ $this, 'renderPageWidthField' ],
			$_page,
			$_section,
			[
				__( 'Page width must be expressed in CSS-compatible units, e.g. &lsquo;5.5in&rsquo; or &lsquo;10cm&rsquo;.' ),
				'label_for' => 'pdf_page_width',
			]
		);

		add_settings_field(
			'pdf_page_height',
			__( 'Page Height', 'pressbooks' ),
			[ $this, 'renderPageHeightField' ],
			$_page,
			$_section,
			[
				__( 'Page height must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.' ),
				'label_for' => 'pdf_page_height',
			]
		);

		if ( $v2_compatible ) {
			add_settings_field(
				'pdf_page_margins',
				__( 'Margins', 'pressbooks' ),
				[ $this, 'renderMarginsField' ],
				$_page,
				$_section,
				[
					__( 'Customize your book&rsquo;s margins using the fields below.', 'pressbooks' ),
				]
			);

			add_settings_field(
				'pdf_page_margin_outside',
				__( 'Outside Margin', 'pressbooks' ),
				[ $this, 'renderOutsideMarginField' ],
				$_page,
				$_section,
				[
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' ),
					'label_for' => 'pdf_page_margin_outside',
				]
			);

			add_settings_field(
				'pdf_page_margin_inside',
				__( 'Inside Margin', 'pressbooks' ),
				[ $this, 'renderInsideMarginField' ],
				$_page,
				$_section,
				[
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' ),
					'label_for' => 'pdf_page_margin_inside',
				]
			);

			add_settings_field(
				'pdf_page_margin_top',
				__( 'Top Margin', 'pressbooks' ),
				[ $this, 'renderTopMarginField' ],
				$_page,
				$_section,
				[
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' ),
					'label_for' => 'pdf_page_margin_top',
				]
			);

			add_settings_field(
				'pdf_page_margin_bottom',
				__( 'Bottom Margin', 'pressbooks' ),
				[ $this, 'renderBottomMarginField' ],
				$_page,
				$_section,
				[
					__( 'Margins must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.', 'pressbooks' ),
					'label_for' => 'pdf_page_margin_bottom',
				]
			);
		}

		add_settings_field(
			'pdf_hyphens',
			__( 'Hyphens', 'pressbooks' ),
			[ $this, 'renderHyphenationField' ],
			$_page,
			$_section,
			[
				__( 'Enable hyphenation', 'pressbooks' ),
				'label_for' => 'pdf_hyphens',
			]
		);

		add_settings_field(
			'pdf_paragraph_separation',
			__( 'Paragraph Separation', 'pressbooks' ),
			[ $this, 'renderParagraphSeparationField' ],
			$_page,
			$_section,
			[
				'indent' => __( 'Indent paragraphs', 'pressbooks' ),
				'skiplines' => __( 'Skip lines between paragraphs', 'pressbooks' ),
			]
		);

		add_settings_field(
			'pdf_sectionopenings',
			__( 'Section Openings', 'pressbooks' ),
			[ $this, 'renderSectionOpeningsField' ],
			$_page,
			$_section,
			[
				'openauto' => __( 'Left or right page section opening (for print PDF)', 'pressbooks' ),
				'openright' => __( 'Right page section openings (for print PDF)', 'pressbooks' ),
				'remove' => __( 'No blank pages (for web PDF)', 'pressbooks' ),
			]
		);

		add_settings_field(
			'pdf_toc',
			__( 'Table of Contents', 'pressbooks' ),
			[ $this, 'renderTOCField' ],
			$_page,
			$_section,
			[
				__( 'Display table of contents', 'pressbooks' ),
				'label_for' => 'pdf_toc',
			]
		);

		add_settings_field(
			'pdf_crop_marks',
			__( 'Crop Marks', 'pressbooks' ),
			[ $this, 'renderCropMarksField' ],
			$_page,
			$_section,
			[
				__( 'Display crop marks', 'pressbooks' ),
				'label_for' => 'pdf_crop_marks',
			]
		);

		if ( CustomCss::isCustomCss() ) {
			add_settings_field(
				'pdf_romanize_parts',
				__( 'Romanize Part Numbers', 'pressbooks' ),
				[ $this, 'renderRomanizePartsField' ],
				$_page,
				$_section,
				[
					__( 'Convert part numbers into Roman numerals', 'pressbooks' ),
					'label_for' => 'pdf_romanize_parts',
				]
			);
		}

		add_settings_field(
			'pdf_footnotes_style',
			__( 'Footnote Style', 'pressbooks' ),
			[ $this, 'renderFootnoteStyleField' ],
			$_page,
			$_section,
			[
				'footnotes' => __( 'Regular footnotes', 'pressbooks' ),
				'endnotes' => __( 'Display as chapter endnotes', 'pressbooks' ),
			]
		);

		add_settings_field(
			'widows',
			__( 'Widows', 'pressbooks' ),
			[ $this, 'renderWidowsField' ],
			$_page,
			$_section,
			[
				'label_for' => 'widows',
			]
		);

		add_settings_field(
			'orphans',
			__( 'Orphans', 'pressbooks' ),
			[ $this, 'renderOrphansField' ],
			$_page,
			$_section,
			[
				'label_for' => 'orphans',
			]
		);

		if ( $v2_compatible ) {
			add_settings_field(
				'running_content',
				__( 'Running Heads & Feet', 'pressbooks' ),
				[ $this, 'renderRunningContentField' ],
				$_page,
				$_section,
				[
					__( 'Running content appears in either running heads or running feet (at the top or bottom of the page) depending on your theme.', 'pressbooks' ),
				]
			);

			add_settings_field(
				'running_content_front_matter_left',
				__( 'Front Matter Left Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentFrontMatterLeftField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Front Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Front Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Front Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_front_matter_left',
				]
			);

			add_settings_field(
				'running_content_front_matter_right',
				__( 'Front Matter Right Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentFrontMatterRightField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Front Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Front Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Front Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_front_matter_right',
				]
			);

			add_settings_field(
				'running_content_introduction_left',
				__( 'Introduction Left Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentIntroductionLeftField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Introduction Title', 'pressbooks' ),
					'%section_author%' => __( 'Introduction Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Introduction Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_introduction_left',
				]
			);

			add_settings_field(
				'running_content_introduction_right',
				__( 'Introduction Right Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentIntroductionRightField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Introduction Title', 'pressbooks' ),
					'%section_author%' => __( 'Introduction Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Introduction Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_introduction_right',
				]
			);

			add_settings_field(
				'running_content_part_left',
				__( 'Part Left Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentPartLeftField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%part_number%' => __( 'Part Number', 'pressbooks' ),
					'%part_title%' => __( 'Part Title', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_part_left',
				]
			);

			add_settings_field(
				'running_content_part_right',
				__( 'Part Right Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentPartRightField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%part_number%' => __( 'Part Number', 'pressbooks' ),
					'%part_title%' => __( 'Part Title', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_part_right',
				]
			);

			add_settings_field(
				'running_content_chapter_left',
				__( 'Chapter Left Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentChapterLeftField' ],
				$_page,
				$_section,
				[
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
					'label_for' => 'running_content_chapter_left',
				]
			);

			add_settings_field(
				'running_content_chapter_right',
				__( 'Chapter Right Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentChapterRightField' ],
				$_page,
				$_section,
				[
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
					'label_for' => 'running_content_chapter_right',
				]
			);

			add_settings_field(
				'running_content_back_matter_left',
				__( 'Back Matter Left Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentBackMatterLeftField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Back Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Back Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Back Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_back_matter_left',
				]
			);

			add_settings_field(
				'running_content_back_matter_right',
				__( 'Back Matter Right Page Running Content', 'pressbooks' ),
				[ $this, 'renderRunningContentBackMatterRightField' ],
				$_page,
				$_section,
				[
					'%book_title%' => __( 'Book Title', 'pressbooks' ),
					'%book_subtitle%' => __( 'Book Subtitle', 'pressbooks' ),
					'%book_author%' => __( 'Book Author', 'pressbooks' ),
					'%section_title%' => __( 'Back Matter Title', 'pressbooks' ),
					'%section_author%' => __( 'Back Matter Author', 'pressbooks' ),
					'%section_subtitle%' => __( 'Back Matter Subtitle', 'pressbooks' ),
					'%blank%' => __( 'Blank', 'pressbooks' ),
					'' => __( 'Custom&hellip;', 'pressbooks' ),
					'label_for' => 'running_content_back_matter_right',
				]
			);
		}

		if ( ! $v2_compatible ) {
			add_settings_field(
				'pdf_fontsize',
				__( 'Increase Font Size', 'pressbooks' ),
				[ $this, 'renderFontSizeField' ],
				$_page,
				$_section,
				[
					__( 'Increases font size and line height for greater accessibility', 'pressbooks' ),
					'label_for' => 'pdf_fontsize',
				]
			);
		}

		/**
		 * Add custom settings fields.
		 *
		 * @since 3.9.7
		 *
		 * @param string $arg1
		 * @param string $arg2
		 */
		do_action( 'pb_theme_options_pdf_add_settings_fields', $_page, $_section );

		register_setting(
			$_option,
			$_option,
			[ $this, 'sanitize' ]
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
	function render() {
	}

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
		if ( ! isset( $options['pdf_paragraph_separation'] ) || 1 === absint( $options['pdf_paragraph_separation'] ) ) {
			$options['pdf_paragraph_separation'] = 'indent';
		} elseif ( 2 === absint( $options['pdf_paragraph_separation'] ) ) {
			$options['pdf_paragraph_separation'] = 'skiplines';
		}

		if ( ! isset( $options['pdf_blankpages'] ) || 1 === absint( $options['pdf_blankpages'] ) ) {
			$options['pdf_blankpages'] = 'include';
		} elseif ( 2 === absint( $options['pdf_blankpages'] ) ) {
			$options['pdf_blankpages'] = 'remove';
		}

		if ( ! isset( $options['pdf_footnotes_style'] ) || 1 === absint( $options['pdf_footnotes_style'] ) ) {
			$options['pdf_footnotes_style'] = 'footnotes';
		} elseif ( 2 === absint( $options['pdf_footnotes_style'] ) ) {
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
		if ( ! isset( $options['pdf_blankpages'] ) || 1 === absint( $options['pdf_blankpages'] ) || 'include' === $options['pdf_blankpages'] ) {
			$options['pdf_sectionopenings'] = 'openauto';
		} elseif ( 2 === absint( $options['pdf_blankpages'] ) || 'remove' === $options['pdf_blankpages'] ) {
			$options['pdf_sectionopenings'] = 'remove';
		}
		unset( $options['pdf_blankpages'] );

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the pdf_body_font_size input.
	 *
	 * @param array $args
	 */
	function renderBodyFontSizeField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_body_font_size',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_body_font_size',
				'value' => ( isset( $this->options['pdf_body_font_size'] ) ) ? $this->options['pdf_body_font_size'] : $this->defaults['pdf_body_font_size'],
				'description' => $args[0],
				'append' => $args[1],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the pdf_body_line_height input.
	 *
	 * @param array $args
	 */
	function renderBodyLineHightField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_body_line_height',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_body_line_height',
				'value' => ( isset( $this->options['pdf_body_line_height'] ) ) ? $this->options['pdf_body_line_height'] : $this->defaults['pdf_body_line_height'],
				'description' => $args[0],
				'append' => $args[1],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the pdf_page_size select.
	 *
	 * @param array $args
	 */
	function renderPageSizeField( $args ) {
		unset( $args['label_for'], $args['class'] );
		if ( ! isset( $this->options['pdf_page_size'] ) ) {
			if ( isset( $this->options['pdf_page_width'] ) && isset( $this->options['pdf_page_height'] ) ) {
				if ( '5.5in' === $this->options['pdf_page_width'] && '8.5in' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 1;
				} elseif ( '6in' === $this->options['pdf_page_width'] && '9in' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 2;
				} elseif ( '8.5in' === $this->options['pdf_page_width'] && '11in' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 3;
				} elseif ( '8.5in' === $this->options['pdf_page_width'] && '9.25in' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 4;
				} elseif ( '5in' === $this->options['pdf_page_width'] && '7.75in' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 5;
				} elseif ( '4.25in' === $this->options['pdf_page_width'] && '7in' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 6;
				} elseif ( '21cm' === $this->options['pdf_page_width'] && '29.7cm' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 7;
				} elseif ( '14.8cm' === $this->options['pdf_page_width'] && '21cm' === $this->options['pdf_page_height'] ) {
					$this->options['pdf_page_size'] = 8;
				} elseif ( '5in' === $this->options['pdf_page_width'] && '8in' === $this->options['pdf_page_height'] ) {
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
		$html .= '</select>';
		echo $html;
	}

	/**
	 * Render the pdf_page_width input.
	 *
	 * @param array $args
	 */
	function renderPageWidthField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_page_width',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_page_width',
				'value' => getset( $this->options, 'pdf_page_width' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the pdf_page_height input.
	 *
	 * @param array $args
	 */
	function renderPageHeightField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_page_height',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_page_height',
				'value' => getset( $this->options, 'pdf_page_height' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the margins diagram.
	 *
	 * @param array $args
	 */
	function renderMarginsField( $args ) {
		unset( $args['label_for'], $args['class'] );
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
		<p>
			<strong><?php _e( 'IMPORTANT: If you plan to use a print-on-demand service, margins under 2cm on any side can cause your file to be rejected.', 'pressbooks' ); ?></strong>
		</p>
		<?php
	}

	/**
	 * Render the pdf_page_margin_outside input.
	 *
	 * @param array $args
	 */
	function renderOutsideMarginField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_page_margin_outside',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_page_margin_outside',
				'value' => getset( $this->options, 'pdf_page_margin_outside' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the pdf_page_margin_inside input.
	 *
	 * @param array $args
	 */
	function renderInsideMarginField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_page_margin_inside',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_page_margin_inside',
				'value' => getset( $this->options, 'pdf_page_margin_inside' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the pdf_page_margin_top input.
	 *
	 * @param array $args
	 */
	function renderTopMarginField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_page_margin_top',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_page_margin_top',
				'value' => getset( $this->options, 'pdf_page_margin_top' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the pdf_page_margin_bottom input.
	 *
	 * @param array $args
	 */
	function renderBottomMarginField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'pdf_page_margin_bottom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_page_margin_bottom',
				'value' => getset( $this->options, 'pdf_page_margin_bottom' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the pdf_hyphens checkbox.
	 *
	 * @param array $args
	 */
	function renderHyphenationField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'pdf_hyphens',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_hyphens',
				'value' => getset( $this->options, 'pdf_hyphens' ),
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the pdf_paragraph_separation radio buttons.
	 *
	 * @param array $args
	 */
	function renderParagraphSeparationField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderRadioButtons(
			[
				'id' => 'pdf_paragraph_separation',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_paragraph_separation',
				'value' => getset( $this->options, 'pdf_paragraph_separation' ),
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the pdf_sectionopenings radio buttons.
	 *
	 * @param array $args
	 */
	function renderSectionOpeningsField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderRadioButtons(
			[
				'id' => 'pdf_sectionopenings',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_sectionopenings',
				'value' => getset( $this->options, 'pdf_sectionopenings' ),
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the pdf_toc checkbox.
	 *
	 * @param array $args
	 */
	function renderTOCField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'pdf_toc',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_toc',
				'value' => getset( $this->options, 'pdf_toc' ),
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the pdf_crop_marks checkbox.
	 *
	 * @param array $args
	 */
	function renderCropMarksField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'pdf_crop_marks',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_crop_marks',
				'value' => getset( $this->options, 'pdf_crop_marks' ),
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the pdf_romanize_parts checkbox.
	 *
	 * @param array $args
	 */
	function renderRomanizePartsField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'pdf_romanize_parts',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_romanize_parts',
				'value' => getset( $this->options, 'pdf_romanize_parts' ),
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the pdf_footnotes_style radio buttons.
	 *
	 * @param array $args
	 */
	function renderFootnoteStyleField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderRadioButtons(
			[
				'id' => 'pdf_footnotes_style',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_footnotes_style',
				'value' => getset( $this->options, 'pdf_footnotes_style' ),
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the widows input.
	 *
	 * @param array $args
	 */
	function renderWidowsField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'widows',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'widows',
				'value' => getset( $this->options, 'widows' ),
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the orphans input.
	 *
	 * @param array $args
	 */
	function renderOrphansField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderField(
			[
				'id' => 'orphans',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'orphans',
				'value' => getset( $this->options, 'orphans' ),
				'type' => 'text',
				'class' => 'small-text',
			]
		);
	}

	/**
	 * Render the running content instructional diagram.
	 *
	 * @param array $args
	 */
	function renderRunningContentField( $args ) {
		unset( $args['label_for'], $args['class'] );
		?>
		<p class="description"><?php echo $args[0]; ?></p>
		<?php
	}

	/**
	 * Render the running_content_front_matter_left input.
	 *
	 * @param array $args
	 */
	function renderRunningContentFrontMatterLeftField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_front_matter_left',
				'name' => 'running_content_front_matter_left',
				'value' => getset( $this->options, 'running_content_front_matter_left' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_front_matter_left_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_front_matter_left',
				'value' => getset( $this->options, 'running_content_front_matter_left' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_front_matter_right input.
	 *
	 * @param array $args
	 */
	function renderRunningContentFrontMatterRightField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_front_matter_right',
				'name' => 'running_content_front_matter_right',
				'value' => getset( $this->options, 'running_content_front_matter_right' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_front_matter_right_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_front_matter_right',
				'value' => getset( $this->options, 'running_content_front_matter_right' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_introduction_left input.
	 *
	 * @param array $args
	 */
	function renderRunningContentIntroductionLeftField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_introduction_left',
				'name' => 'running_content_introduction_left',
				'value' => getset( $this->options, 'running_content_introduction_left' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_introduction_left_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_introduction_left',
				'value' => getset( $this->options, 'running_content_introduction_left' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_introduction_right input.
	 *
	 * @param array $args
	 */
	function renderRunningContentIntroductionRightField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_introduction_right',
				'name' => 'running_content_introduction_right',
				'value' => getset( $this->options, 'running_content_introduction_right' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_introduction_right_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_introduction_right',
				'value' => getset( $this->options, 'running_content_introduction_right' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_part_left input.
	 *
	 * @param array $args
	 */
	function renderRunningContentPartLeftField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_part_left',
				'name' => 'running_content_part_left',
				'value' => getset( $this->options, 'running_content_part_left' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_part_left_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_part_left',
				'value' => getset( $this->options, 'running_content_part_left' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_part_right input.
	 *
	 * @param array $args
	 */
	function renderRunningContentPartRightField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_part_right',
				'name' => 'running_content_part_right',
				'value' => getset( $this->options, 'running_content_part_right' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_part_right_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_part_right',
				'value' => getset( $this->options, 'running_content_part_right' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_chapter_left input.
	 *
	 * @param array $args
	 */
	function renderRunningContentChapterLeftField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_chapter_left',
				'name' => 'running_content_chapter_left',
				'value' => getset( $this->options, 'running_content_chapter_left' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_chapter_left_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_chapter_left',
				'value' => getset( $this->options, 'running_content_chapter_left' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_chapter_right input.
	 *
	 * @param array $args
	 */
	function renderRunningContentChapterRightField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_chapter_right',
				'name' => 'running_content_chapter_right',
				'value' => getset( $this->options, 'running_content_chapter_right' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_chapter_right_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_chapter_right',
				'value' => getset( $this->options, 'running_content_chapter_right' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_back_matter_left input.
	 *
	 * @param array $args
	 */
	function renderRunningContentBackMatterLeftField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_back_matter_left',
				'name' => 'running_content_back_matter_left',
				'value' => getset( $this->options, 'running_content_back_matter_left' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_back_matter_left_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_back_matter_left',
				'value' => getset( $this->options, 'running_content_back_matter_left' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the running_content_back_matter_right input.
	 *
	 * @param array $args
	 */
	function renderRunningContentBackMatterRightField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCustomSelect(
			[
				'id' => 'running_content_back_matter_right',
				'name' => 'running_content_back_matter_right',
				'value' => getset( $this->options, 'running_content_back_matter_right' ),
				'choices' => $args,
			]
		);
		$this->renderField(
			[
				'id' => 'running_content_back_matter_right_custom',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'running_content_back_matter_right',
				'value' => getset( $this->options, 'running_content_back_matter_right' ),
				'type' => 'text',
				'class' => 'regular-text code',
			]
		);
	}

	/**
	 * Render the pdf_fontsize checkbox.
	 *
	 * @param array $args
	 */
	function renderFontSizeField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'pdf_fontsize',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'pdf_fontsize',
				'value' => getset( $this->options, 'pdf_fontsize' ),
				'label' => $args[0],
			]
		);
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
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_pdf_defaults', [
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
			]
		);
	}

	/**
	 * Filter the array of default values for the PDF options tab.
	 *
	 * @param array $defaults
	 *
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {

		// SASS => WP
		$overrides = [
			'body-font-size' => 'pdf_body_font_size',
			'body-line-height' => 'pdf_body_line_height',
			'page-margin-top' => 'pdf_page_margin_top',
			'page-margin-inside' => 'pdf_page_margin_inside',
			'page-margin-bottom' => 'pdf_page_margin_bottom',
			'page-margin-outside' => 'pdf_page_margin_outside',
			'front-matter-running-content-left' => 'running_content_front_matter_left',
			'front-matter-running-content-right' => 'running_content_front_matter_right',
			'introduction-running-content-left' => 'running_content_introduction_left',
			'introduction-running-content-right' => 'running_content_introduction_right',
			'part-running-content-left' => 'running_content_part_left',
			'part-running-content-right' => 'running_content_part_right',
			'chapter-running-content-left' => 'running_content_chapter_left',
			'chapter-running-content-right' => 'running_content_chapter_right',
			'back-matter-running-content-left' => 'running_content_back_matter_left',
			'back-matter-running-content-right' => 'running_content_back_matter_right',
		];

		$transient_name = 'pressbooks_theme_options_pdf_parsed_sass_variables';
		$parsed_sass_variables = get_transient( $transient_name );
		if ( $parsed_sass_variables === false ) {
			// Order of files matter. If a variable is duplicated in other files then the last one takes precedence
			$parsed_sass_variables = [];
			$sass = Container::get( 'Sass' );
			$path_to_global = $sass->pathToGlobals();
			$path_to_theme = get_stylesheet_directory();
			$files = [
				$path_to_global . '/variables/_elements.scss',
				$path_to_global . '/variables/_structure.scss',
				$path_to_theme . '/assets/styles/components/_elements.scss',
				$path_to_theme . '/assets/styles/components/_structure.scss',
			];
			foreach ( $files as $file ) {
				if ( file_exists( $file ) ) {
					$parsed_sass_variables[] = $sass->parseVariables( \Pressbooks\Utility\get_contents( $file ) );
				}
			}
			set_transient( $transient_name, $parsed_sass_variables );
		}

		foreach ( $parsed_sass_variables as $parsed_variables ) {
			foreach ( $overrides as $sass_var => $wp_option ) {
				if ( isset( $parsed_variables[ $sass_var ] ) ) {
					$val = self::parseSassValue( $parsed_variables[ $sass_var ] );
					if ( ! empty( $val ) ) {
						if ( in_array( $wp_option, self::getFloatOptions(), true ) ) {
							$val = (float) preg_replace( '/[^0-9.]/', '', $val ); // Extract digits and periods
						} elseif ( in_array( $wp_option, self::getIntegerOptions(), true ) ) {
							$val = (int) preg_replace( '/[^0-9]/', '', $val ); // Extract digits
						} elseif ( in_array( $wp_option, self::getBooleanOptions(), true ) ) {
							$val = filter_var( $val, FILTER_VALIDATE_BOOLEAN ); // Convert to boolean
						} elseif ( strpos( $wp_option, 'running_content', true ) ) {
							$val = self::replaceRunningContentStrings( $val );
						}
						$defaults[ $wp_option ] = $val; // Override default with new value
					}
				}
			}
		}

		return $defaults;
	}

	/**
	 * @param string $val
	 *
	 * @return string
	 */
	static protected function parseSassValue( $val ) {

		if ( substr( $val, 0, 1 ) === '(' ) {
			// We think this is a Sass Map
			preg_match( '/prince:([^,]+)/', $val, $matches );
			if ( ! empty( $matches[1] ) ) {
				return trim( $matches[1] );
			}
			return ''; // Did not find prince mapping
		}

		if ( substr( $val, 0, 7 ) === 'string(' ) {
			// We think this is one of our running content variables
			preg_match( '/string\((.+?)\)/', $val, $matches );
			if ( ! empty( $matches[1] ) ) {
				return trim( str_replace( '-', '_', "%{$matches[1]}%" ) );
			}
			return ''; // Did not find what we were looking for
		}

		// Use as is
		return $val;
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
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_pdf_booleans', [
				'pdf_hyphens',
				'pdf_toc',
				'pdf_crop_marks',
				'pdf_romanize_parts',
				'pdf_fontsize',
			]
		);
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
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_pdf_strings', [
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
			]
		);
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
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_pdf_integers', [
				'widows',
				'orphans',
			]
		);
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
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_pdf_floats', [
				'pdf_body_font_size',
				'pdf_body_line_height',
			]
		);
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
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_pdf_predefined', [
				'pdf_paragraph_separation',
				'pdf_sectionopenings',
				'pdf_footnotes_style',
			]
		);
	}

	/**
	 * Replace running content tags with strings.
	 *
	 * @param string $input
	 *
	 * @return string
	 *
	 * @since 3.9.8
	 */
	static function replaceRunningContentTags( $input ) {
		$input = '"' . $input . '"';

		return str_replace(
			[
				'%book_title%',
				'%book_subtitle%',
				'%book_author%',
				'%part_number%',
				'%part_title%',
				'%section_title%',
				'%section_author%',
				'%section_subtitle%',
				'%blank%',
			],
			[
				'" string(book-title) "',
				'" string(book-subtitle) "',
				'" string(book-author) "',
				'" string(part-number) "',
				'" string(part-title) "',
				'" string(section-title) "',
				'" string(chapter-author) "',
				'" string(chapter-subtitle) "',
				'',
			],
			$input
		);
	}

	/**
	 * Replace running content strings with tags.
	 *
	 * @param string $input
	 *
	 * @return string
	 *
	 * @since 4.5.0
	 */
	static function replaceRunningContentStrings( $input ) {
		return str_replace(
			[
				'string(book-title)',
				'string(book-subtitle)',
				'string(book-author)',
				'string(part-number)',
				'string(part-title)',
				'string(section-title)',
				'string(chapter-author)',
				'string(chapter-subtitle)',
				'',
			],
			[
				'%book_title%',
				'%book_subtitle%',
				'%book_author%',
				'%part_number%',
				'%part_title%',
				'%section_title%',
				'%section_author%',
				'%section_subtitle%',
				'%blank%',
			],
			$input
		);
	}

	/**
	 * Apply overrides.
	 *
	 * @param string $scss
	 *
	 * @return string
	 *
	 * @since 3.9.8
	 */
	static function scssOverrides( $scss ) {

		$styles = Container::get( 'Styles' );
		$v2_compatible = $styles->isCurrentThemeCompatible( 2 );

		if ( ! $v2_compatible ) {
			$scss .= "/* Theme Options */\n";
		}

		// --------------------------------------------------------------------
		// Global Options

		$options = get_option( 'pressbooks_theme_options_global' );

		// Should we display chapter numbers? True (default) or false.
		if ( ! $options['chapter_numbers'] ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'chapter-number-display' => 'none',
						'part-number-display' => 'none',
						'toc-left-left-gutter' => '0',
						'toc-chapter-number-display' => 'none',
						'toc-left-chapter-number-display' => 'none',
						'toc-center-chapter-number-display' => 'none',
						'toc-part-number-display' => 'none',
						'toc-left-part-number-display' => 'none',
						'toc-center-part-number-display' => 'none',
					]
				);
			} else {
				$scss .= "div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number, #toc .part a::before, #toc .chapter a::before { display: none !important; } \n";  // TODO: NO
			}
		}

		// Textbox colours.

		if ( $v2_compatible ) {
			foreach ( [
				'edu_textbox_examples_header_color' => 'examples-header-color',
				'edu_textbox_examples_header_background' => 'examples-header-background',
				'edu_textbox_examples_background' => 'examples-background',
				'edu_textbox_exercises_header_color' => 'exercises-header-color',
				'edu_textbox_exercises_header_background' => 'exercises-header-background',
				'edu_textbox_exercises_background' => 'exercises-background',
				'edu_textbox_objectives_header_color' => 'learning-objectives-header-color',
				'edu_textbox_objectives_header_background' => 'learning-objectives-header-background',
				'edu_textbox_objectives_background' => 'learning-objectives-background',
				'edu_textbox_takeaways_header_color' => 'key-takeaways-header-color',
				'edu_textbox_takeaways_header_background' => 'key-takeaways-header-background',
				'edu_textbox_takeaways_background' => 'key-takeaways-background',
			] as $option => $variable ) {
				if ( isset( $options[ $option ] ) ) {
					$styles->getSass()->setVariables(
						[
							"$variable" => $options[ $option ],
						]
					);
				}
			}
		}

		// --------------------------------------------------------------------
		// PDF Options

		$options = get_option( 'pressbooks_theme_options_pdf' );

		// Change body font size
		if ( $v2_compatible && isset( $options['pdf_body_font_size'] ) ) {
			$fontsize = $options['pdf_body_font_size'] . 'pt';
			$styles->getSass()->setVariables(
				[
					'body-font-size' => "(epub: medium, prince: $fontsize, web: 14pt)",
				]
			);
		}

		// Change body line height
		if ( $v2_compatible && isset( $options['pdf_body_line_height'] ) ) {
			$lineheight = $options['pdf_body_line_height'] . 'em';
				$styles->getSass()->setVariables(
					[
						'body-line-height' => "(epub: 1.4em, prince: $lineheight, web: 1.8em)",
					]
				);
		}

		// Page dimensions
		$width = $options['pdf_page_width'];
		$height = $options['pdf_page_height'];

		if ( $v2_compatible ) {
			$styles->getSass()->setVariables(
				[
					'page-width' => $width,
					'page-height' => $height,
				]
			);
		} else {
			$scss .= "@page { size: $width $height; } \n";
		}

		// Margins
		if ( $v2_compatible ) {
			$styles->getSass()->setVariables(
				[
					'page-margin-top' => ( isset( $options['pdf_page_margin_top'] ) ) ? $options['pdf_page_margin_top'] : '2cm',
					'page-margin-inside' => ( isset( $options['pdf_page_margin_inside'] ) ) ? $options['pdf_page_margin_inside'] : '2cm',
					'page-margin-bottom' => ( isset( $options['pdf_page_margin_bottom'] ) ) ? $options['pdf_page_margin_bottom'] : '2cm',
					'page-margin-outside' => ( isset( $options['pdf_page_margin_outside'] ) ) ? $options['pdf_page_margin_outside'] : '2cm',
				]
			);
		}

		// Should we display crop marks? True or false (default).
		if ( 1 === absint( $options['pdf_crop_marks'] ) ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'page-cropmarks' => 'crop',
					]
				);
			} else {
				$scss .= "@page { marks: crop } \n";
			}
		}

		// Hyphens?
		if ( 1 === absint( $options['pdf_hyphens'] ) ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'para-hyphens' => 'auto',
					]
				);
			} else {
				$scss .= "p { hyphens: auto; } \n";
			}
		} else {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'para-hyphens' => 'manual',
					]
				);
			} else {
				$scss .= "p { hyphens: manual; } \n";
			}
		}

		// Indent paragraphs?
		$paragraph_separation = $options['pdf_paragraph_separation'] ?? 'indent';
		if ( 'skiplines' === $paragraph_separation ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'para-margin-top' => '1em',
						'para-indent' => '0',
					]
				);
			} else {
				$scss .= "p + p { text-indent: 0em; margin-top: 1em; } \n";
			}
		} elseif ( 'indent' === $paragraph_separation ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'para-margin-top' => '0',
						'para-indent' => '1em',
					]
				);
			} else {
				$scss .= "p + p { text-indent: 1em; margin-top: 0em; } \n";
			}
		}

		// Include blank pages?
		if ( isset( $options['pdf_sectionopenings'] ) ) {
			if ( 'openright' === $options['pdf_sectionopenings'] ) {
				if ( $v2_compatible ) {
					$styles->getSass()->setVariables(
						[
							'recto-verso-standard-opening' => 'right',
							'recto-verso-first-section-opening' => 'right',
							'recto-verso-section-opening' => 'right',
						]
					);
				} else {
					$scss .= "#title-page, #toc, div.part, div.front-matter, div.front-matter.introduction, div.front-matter + div.front-matter, div.chapter, div.chapter + div.chapter, div.back-matter, div.back-matter + div.back-matter, #half-title-page h1.title:first-of-type  { page-break-before: right; } \n";
					$scss .= "#copyright-page { page-break-before: left; }\n";
				}
			} elseif ( 'remove' === $options['pdf_sectionopenings'] ) {
				if ( $v2_compatible ) {
					$styles->getSass()->setVariables(
						[
							'recto-verso-standard-opening' => 'auto',
							'recto-verso-first-section-opening' => 'auto',
							'recto-verso-section-opening' => 'auto',
							'recto-verso-copyright-page-opening' => 'auto',
						]
					);
				} else {
					$scss .= "#title-page, #copyright-page, #toc, div.part, div.front-matter, div.back-matter, div.chapter, #half-title-page h1.title:first-of-type  { page-break-before: auto; } \n";
				}
			}
		}

		// Should we display the TOC? True (default) or false.
		if ( ! $options['pdf_toc'] ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'toc-display' => 'none',
					]
				);
			} else {
				$scss .= "#toc { display: none; } \n";
			}
		}

		// Widows
		if ( isset( $options['widows'] ) ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'widows' => $options['widows'],
					]
				);
			} else {
				$scss .= "p { widows: {$options['widows']}; }\n";
			}
		} else {
			if ( ! $v2_compatible ) {
				$scss .= 'p { widows: 2; }' . "\n";
			}
		}

		// Orphans
		if ( isset( $options['orphans'] ) ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'orphans' => $options['orphans'],
					]
				);
			} else {
				$scss .= "p { orphans: {$options['orphans']}; }\n";
			}
		} else {
			if ( ! $v2_compatible ) {
				$scss .= 'p { orphans: 1; }' . "\n";
			}
		}

		// Running Content
		if ( $v2_compatible ) {
			$front_matter_running_content_left = ( isset( $options['running_content_front_matter_left'] ) ) ? self::replaceRunningContentTags( $options['running_content_front_matter_left'] ) : 'string(book-title)';
			$front_matter_running_content_right = ( isset( $options['running_content_front_matter_right'] ) ) ? self::replaceRunningContentTags( $options['running_content_front_matter_right'] ) : 'string(section-title)';
			$introduction_running_content_left = ( isset( $options['running_content_introduction_left'] ) ) ? self::replaceRunningContentTags( $options['running_content_introduction_left'] ) : 'string(book-title)';
			$introduction_running_content_right = ( isset( $options['running_content_introduction_right'] ) ) ? self::replaceRunningContentTags( $options['running_content_introduction_right'] ) : 'string(section-title)';
			$part_running_content_left = ( isset( $options['running_content_part_left'] ) ) ? self::replaceRunningContentTags( $options['running_content_part_left'] ) : 'string(book-title)';
			$part_running_content_right = ( isset( $options['running_content_part_right'] ) ) ? self::replaceRunningContentTags( $options['running_content_part_right'] ) : 'string(part-title)';
			$chapter_running_content_left = ( isset( $options['running_content_chapter_left'] ) ) ? self::replaceRunningContentTags( $options['running_content_chapter_left'] ) : 'string(book-title)';
			$chapter_running_content_right = ( isset( $options['running_content_chapter_right'] ) ) ? self::replaceRunningContentTags( $options['running_content_chapter_right'] ) : 'string(section-title)';
			$back_matter_running_content_left = ( isset( $options['running_content_back_matter_left'] ) ) ? self::replaceRunningContentTags( $options['running_content_back_matter_left'] ) : 'string(book-title)';
			$back_matter_running_content_right = ( isset( $options['running_content_back_matter_right'] ) ) ? self::replaceRunningContentTags( $options['running_content_back_matter_right'] ) : 'string(section-title)';
			$styles->getSass()->setVariables(
				[
					'front-matter-running-content-left' => $front_matter_running_content_left,
					'front-matter-running-content-right' => $front_matter_running_content_right,
					'introduction-running-content-left' => $introduction_running_content_left,
					'introduction-running-content-right' => $introduction_running_content_right,
					'part-running-content-left' => $part_running_content_left,
					'part-running-content-right' => $part_running_content_right,
					'chapter-running-content-left' => $chapter_running_content_left,
					'chapter-running-content-right' => $chapter_running_content_right,
					'back-matter-running-content-left' => $back_matter_running_content_left,
					'back-matter-running-content-right' => $back_matter_running_content_right,
				]
			);
		}

		// a11y Font Size
		if ( ! empty( $options['pdf_fontsize'] ) ) {
			if ( ! $v2_compatible ) {
				$scss .= 'body { font-size: 1.3em; line-height: 1.3; }' . "\n";
			}
		}

		return $scss;
	}
}
