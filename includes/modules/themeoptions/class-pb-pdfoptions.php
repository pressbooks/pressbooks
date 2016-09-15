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
	static $currentVersion = 1;

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
	function __construct(array $options) {
 		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();
		$this->strings = $this->getStringOptions();
		$this->integers = $this->getIntegerOptions();
		$this->floats = $this->getFloatOptions();
		$this->predefined = $this->getPredefinedOptions();

 		foreach ( $this->defaults as $key => $value ) {
 			if ( !isset ( $this->options[ $key ] ) ) {
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

		if ( \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 2 ) ) {
			add_settings_field(
				'pdf_body_font_size',
				__( 'Body Font Size', 'pressbooks' ),
				array( $this, 'renderBodyFontSizeField' ),
				$_page,
				$_section,
				array(
					__( 'Heading sizes are proportional to the body font size and will also be affected by this setting.', 'pressbooks' ),
					'pt'
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
					'em'
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
				 __( 'Custom&hellip;', 'pressbooks' )
			)
		);

		add_settings_field(
			'pdf_page_width',
			__( 'Page Width', 'pressbooks' ),
			array( $this, 'renderPageWidthField' ),
			$_page,
			$_section,
			array(
				__( 'Page width must be expressed in CSS-compatible units, e.g. &lsquo;5.5in&rsquo; or &lsquo;10cm&rsquo;.')
			)
		);

		add_settings_field(
			'pdf_page_height',
			__( 'Page Height', 'pressbooks' ),
			array( $this, 'renderPageHeightField' ),
			$_page,
			$_section,
			array(
				__( 'Page height must be expressed in CSS-compatible units, e.g. &lsquo;8.5in&rsquo; or &lsquo;10cm&rsquo;.')
			)
		);

		if ( \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 2 ) ) {
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
				 'skiplines' => __( 'Skip lines between paragraphs', 'pressbooks' )
			)
		);

		add_settings_field(
			'pdf_blankpages',
			__( 'Blank Pages', 'pressbooks' ),
			array( $this, 'renderBlankPagesField' ),
			$_page,
			$_section,
			array(
				 'include' => __( 'Include blank pages (for print PDF)', 'pressbooks' ),
				 'remove' => __( 'Remove all blank pages (for web PDF)', 'pressbooks' )
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
				'72dpi' => __( 'Low (72 DPI)', 'pressbooks' )
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
				 'endnotes' => __( 'Display as chapter endnotes', 'pressbooks' )
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

		if ( \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 2 ) ) {
			add_settings_field(
				'running_content',
				__( 'Running Heads & Feet', 'pressbooks' ),
				array( $this, 'renderRunningContentField' ),
				$_page,
				$_section,
				array(
					__('Running content appears in either running heads or running feet (at the top or bottom of the page) depending on your theme.', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_front_matter_left',
				__( 'Front Matter Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentFrontMatterLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%section_title%' => __('Front Matter Title', 'pressbooks'),
					'%section_author%' => __('Front Matter Author', 'pressbooks'),
					'%section_subtitle%' => __('Front Matter Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_front_matter_right',
				__( 'Front Matter Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentFrontMatterRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%section_title%' => __('Front Matter Title', 'pressbooks'),
					'%section_author%' => __('Front Matter Author', 'pressbooks'),
					'%section_subtitle%' => __('Front Matter Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_introduction_left',
				__( 'Introduction Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentIntroductionLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%section_title%' => __('Introduction Title', 'pressbooks'),
					'%section_author%' => __('Introduction Author', 'pressbooks'),
					'%section_subtitle%' => __('Introduction Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_introduction_right',
				__( 'Introduction Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentIntroductionRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%section_title%' => __('Introduction Title', 'pressbooks'),
					'%section_author%' => __('Introduction Author', 'pressbooks'),
					'%section_subtitle%' => __('Introduction Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_part_left',
				__( 'Part Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentPartLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%part_number%' => __('Part Number', 'pressbooks'),
					'%part_title%' => __('Part Title', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_part_right',
				__( 'Part Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentPartRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%part_number%' => __('Part Number', 'pressbooks'),
					'%part_title%' => __('Part Title', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_chapter_left',
				__( 'Chapter Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentChapterLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%part_number%' => __('Part Number', 'pressbooks'),
					'%part_title%' => __('Part Title', 'pressbooks'),
					'%section_title%' => __('Chapter Title', 'pressbooks'),
					'%section_author%' => __('Chapter Author', 'pressbooks'),
					'%section_subtitle%' => __('Chapter Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_chapter_right',
				__( 'Chapter Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentChapterRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%part_number%' => __('Part Number', 'pressbooks'),
					'%part_title%' => __('Part Title', 'pressbooks'),
					'%section_title%' => __('Chapter Title', 'pressbooks'),
					'%section_author%' => __('Chapter Author', 'pressbooks'),
					'%section_subtitle%' => __('Chapter Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_back_matter_left',
				__( 'Back Matter Left Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentBackMatterLeftField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%section_title%' => __('Back Matter Title', 'pressbooks'),
					'%section_author%' => __('Back Matter Author', 'pressbooks'),
					'%section_subtitle%' => __('Back Matter Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);

			add_settings_field(
				'running_content_back_matter_right',
				__( 'Back Matter Right Page Running Content', 'pressbooks' ),
				array( $this, 'renderRunningContentBackMatterRightField' ),
				$_page,
				$_section,
				array(
					'%book_title%' => __('Book Title', 'pressbooks'),
					'%book_subtitle%' => __('Book Subtitle', 'pressbooks'),
					'%book_author%' => __('Book Author', 'pressbooks'),
					'%section_title%' => __('Back Matter Title', 'pressbooks'),
					'%section_author%' => __('Back Matter Author', 'pressbooks'),
					'%section_subtitle%' => __('Back Matter Subtitle', 'pressbooks'),
					'%blank%' => __('Blank', 'pressbooks'),
					'' => __('Custom&hellip;', 'pressbooks')
				)
			);
		}

		if ( ! \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 2 ) ) {
		 	add_settings_field(
				'pdf_fontsize',
				__( 'Increase Font Size', 'pressbooks' ),
				array( $this, 'renderFontSizeField' ),
				$_page,
				$_section,
				array(
				    __('Increases font size and line height for greater accessibility', 'pressbooks' )
				)
			);
		}

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
			switch( $options['pdf_page_size'] ) {
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
		if ( !isset( $options['pdf_paragraph_separation'] ) || $options['pdf_paragraph_separation'] == '1' ) {
			$options['pdf_paragraph_separation'] = 'indent';
		} elseif ( $options['pdf_paragraph_separation'] == '2' ) {
			$options['pdf_paragraph_separation'] = 'skiplines';
		}

		if ( !isset( $options['pdf_blankpages'] ) || $options['pdf_blankpages'] == '1' ) {
			$options['pdf_blankpages'] = 'include';
		} elseif ( $options['pdf_blankpages'] == '2' ) {
			$options['pdf_blankpages'] = 'remove';
		}

		if ( !isset( $options['pdf_footnotes_style'] ) || $options['pdf_footnotes_style'] == '1' ) {
			$options['pdf_footnotes_style'] = 'footnotes';
		} elseif ( $options['pdf_footnotes_style'] == '2' ) {
			$options['pdf_footnotes_style'] = 'endnotes';
		}

		// Add missing defaults.
		foreach ( $this->defaults as $key => $value ) {
			if ( !isset( $options[ $key ] ) ) {
				$options[ $key ] = $value;
			}
		}

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the pdf_body_font_size input.
	 * @param array $args
	 */
	function renderBodyFontSizeField( $args ) {
		$this->renderField('pdf_body_font_size', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_body_font_size', @$this->options['pdf_body_font_size'], $args[0], $args[1], 'text', 'small-text');
	}

	/**
	 * Render the pdf_body_line_height input.
	 * @param array $args
	 */
	function renderBodyLineHightField( $args ) {
		if ( ! isset( $this->options['pdf_body_line_height'] ) ) {
			$this->options['pdf_body_line_height'] = $this->defaults['pdf_body_line_height'];
		}
		$this->renderField('pdf_body_line_height', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_body_line_height', @$this->options['pdf_body_line_height'], $args[0], $args[1], 'text', 'small-text');
	}

	/**
	 * Render the pdf_page_size select.
	 * @param array $args
	 */
	function renderPageSizeField( $args ) {
		if ( ! isset( $this->options['pdf_page_size'] ) ) {
			if ( isset( $this->options['pdf_page_width'] ) && isset( $this->options['pdf_page_height'] ) ) {
				if ( $this->options['pdf_page_width'] == '5.5in' && $this->options['pdf_page_height'] == '8.5in' ) {
					$this->options['pdf_page_size'] = 1;
				} elseif ( $this->options['pdf_page_width'] == '6in' && $this->options['pdf_page_height'] == '9in' ) {
					$this->options['pdf_page_size'] = 2;
				} elseif ( $this->options['pdf_page_width'] == '8.5in' && $this->options['pdf_page_height'] == '11in' ) {
					$this->options['pdf_page_size'] = 3;
				} elseif ( $this->options['pdf_page_width'] == '8.5in' && $this->options['pdf_page_height'] == '9.25in' ) {
					$this->options['pdf_page_size'] = 4;
				} elseif ( $this->options['pdf_page_width'] == '5in' && $this->options['pdf_page_height'] == '7.75in' ) {
					$this->options['pdf_page_size'] = 5;
				} elseif ( $this->options['pdf_page_width'] == '4.25in' && $this->options['pdf_page_height'] == '7in' ) {
					$this->options['pdf_page_size'] = 6;
				} elseif ( $this->options['pdf_page_width'] == '21cm' && $this->options['pdf_page_height'] == '29.7cm' ) {
					$this->options['pdf_page_size'] = 7;
				} elseif ( $this->options['pdf_page_width'] == '14.8cm' && $this->options['pdf_page_height'] == '21cm' ) {
					$this->options['pdf_page_size'] = 8;
				} elseif ( $this->options['pdf_page_width'] == '5in' && $this->options['pdf_page_height'] == '8in' ) {
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
		$this->renderField('pdf_page_width', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_page_width', @$this->options['pdf_page_width'], $args[0], '', 'text', 'small-text');
	}

	/**
	 * Render the pdf_page_height input.
	 * @param array $args
	 */
	function renderPageHeightField( $args ) {
		$this->renderField('pdf_page_height', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_page_height', @$this->options['pdf_page_height'], $args[0], '', 'text', 'small-text');
	}

	/**
	 * Render the margins diagram.
	 * @param array $args
	 */
	function renderMarginsField( $args ) { ?>
		<div class="margin-diagram">
			<p class="description"><?= $args[0]; ?></p>
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
	<?php }

	/**
	 * Render the pdf_page_margin_outside input.
	 * @param array $args
	 */
	function renderOutsideMarginField( $args ) { ?>
		<?php $this->renderField('pdf_page_margin_outside', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_page_margin_outside', @$this->options['pdf_page_margin_outside'], $args[0], '', 'text', 'small-text');
	}

	/**
	 * Render the pdf_page_margin_inside input.
	 * @param array $args
	 */
	function renderInsideMarginField( $args ) {
		$this->renderField('pdf_page_margin_inside', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_page_margin_inside', @$this->options['pdf_page_margin_inside'], $args[0], '', 'text', 'small-text');
	}

	/**
	 * Render the pdf_page_margin_top input.
	 * @param array $args
	 */
	function renderTopMarginField( $args ) {
		$this->renderField('pdf_page_margin_top', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_page_margin_top', @$this->options['pdf_page_margin_top'], $args[0], '', 'text', 'small-text');
	}

	/**
	 * Render the pdf_page_margin_bottom input.
	 * @param array $args
	 */
	function renderBottomMarginField( $args ) {
		$this->renderField('pdf_page_margin_bottom', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_page_margin_bottom', @$this->options['pdf_page_margin_bottom'], $args[0], '', 'text', 'small-text');
	}

	/**
	 * Render the pdf_hyphens checkbox.
	 * @param array $args
	 */
	function renderHyphenationField( $args ) {
		$this->renderCheckbox( 'pdf_hyphens', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_hyphens', @$this->options['pdf_hyphens'], $args[0] );
	}

	/**
	 * Render the pdf_paragraph_separation radio buttons.
	 * @param array $args
	 */
	function renderParagraphSeparationField( $args ) {
		$this->renderRadioButtons( 'pdf_paragraph_separation', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_paragraph_separation', @$this->options['pdf_paragraph_separation'], $args);
	}

	/**
	 * Render the pdf_blankpages radio buttons.
	 * @param array $args
	 */
	function renderBlankPagesField( $args ) {
		$this->renderRadioButtons( 'pdf_blankpages', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_blankpages', @$this->options['pdf_blankpages'], $args);
	}

	/**
	 * Render the pdf_toc checkbox.
	 * @param array $args
	 */
	function renderTOCField( $args ) {
		$this->renderCheckbox( 'pdf_toc', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_toc', @$this->options['pdf_toc'], $args[0] );
	}

	/**
	 * Render the pdf_image_resolution radio buttons.
	 * @param array $args
	 */
	function renderImageResolutionField( $args ) {
		$this->renderRadioButtons( 'pdf_image_resolution', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_image_resolution', @$this->options['pdf_image_resolution'], $args);
	}

	/**
	 * Render the pdf_crop_marks checkbox.
	 * @param array $args
 	*/
	function renderCropMarksField( $args ) {
		$this->renderCheckbox( 'pdf_crop_marks', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_crop_marks', @$this->options['pdf_crop_marks'], $args[0] );
	}

	/**
	 * Render the pdf_romanize_parts checkbox.
	 * @param array $args
	 */
	function renderRomanizePartsField( $args ) {
		$this->renderCheckbox( 'pdf_romanize_parts', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_romanize_parts', @$this->options['pdf_romanize_parts'], $args[0] );
	}

	/**
	 * Render the pdf_footnotes_style radio buttons.
	 * @param array $args
	 */
	function renderFootnoteStyleField( $args ) {
		$this->renderRadioButtons( 'pdf_footnotes_style', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_footnotes_style', @$this->options['pdf_footnotes_style'], $args);
	}

	/**
	 * Render the widows input.
	 * @param array $args
	 */
	function renderWidowsField( $args ) {
		$this->renderField('widows', 'pressbooks_theme_options_' . $this->getSlug(), 'widows', @$this->options['widows'], '', '', 'text', 'small-text');
	}

	/**
	 * Render the orphans input.
	 * @param array $args
	 */
	function renderOrphansField( $args ) {
		$this->renderField('orphans', 'pressbooks_theme_options_' . $this->getSlug(), 'orphans', @$this->options['orphans'], '', '', 'text', 'small-text');
	}

	/**
	 * Render the running content instructional diagram.
	 * @param array $args
	 */
	function renderRunningContentField( $args ) { ?>
		<p class="description"><?= $args[0]; ?></p>
	<?php }

	/**
	 * Render the running_content_front_matter_left input.
	 * @param array $args
	 */
	function renderRunningContentFrontMatterLeftField( $args ) {
		$this->renderCustomSelect('running_content_front_matter_left', 'running_content_front_matter_left', @$this->options['running_content_front_matter_left'], $args);
		$this->renderField('running_content_front_matter_left', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_front_matter_left', @$this->options['running_content_front_matter_left'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_front_matter_right input.
	 * @param array $args
	 */
	function renderRunningContentFrontMatterRightField( $args ) {
		$this->renderCustomSelect('running_content_front_matter_right', 'running_content_front_matter_right', @$this->options['running_content_front_matter_right'], $args);
		$this->renderField('running_content_front_matter_right', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_front_matter_right', @$this->options['running_content_front_matter_right'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_introduction_left input.
	 * @param array $args
	 */
	function renderRunningContentIntroductionLeftField( $args ) {
		$this->renderCustomSelect('running_content_introduction_left', 'running_content_introduction_left', @$this->options['running_content_introduction_left'], $args);
		$this->renderField('running_content_introduction_left', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_introduction_left', @$this->options['running_content_introduction_left'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_introduction_right input.
	 * @param array $args
	 */
	function renderRunningContentIntroductionRightField( $args ) {
		$this->renderCustomSelect('running_content_introduction_right', 'running_content_introduction_right', @$this->options['running_content_introduction_right'], $args);
		$this->renderField('running_content_introduction_right', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_introduction_right', @$this->options['running_content_introduction_right'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_part_left input.
	 * @param array $args
	 */
	function renderRunningContentPartLeftField( $args ) {
		$this->renderCustomSelect('running_content_part_left', 'running_content_part_left', @$this->options['running_content_part_left'], $args);
		$this->renderField('running_content_part_left', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_part_left', @$this->options['running_content_part_left'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_part_right input.
	 * @param array $args
	 */
	function renderRunningContentPartRightField( $args ) {
		$this->renderCustomSelect('running_content_part_right', 'running_content_part_right', @$this->options['running_content_part_right'], $args);
		$this->renderField('running_content_part_right', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_part_right', @$this->options['running_content_part_right'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_chapter_left input.
	 * @param array $args
	 */
	function renderRunningContentChapterLeftField( $args ) {
		$this->renderCustomSelect('running_content_chapter_left', 'running_content_chapter_left', @$this->options['running_content_chapter_left'], $args);
		$this->renderField('running_content_chapter_left', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_chapter_left', @$this->options['running_content_chapter_left'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_chapter_right input.
	 * @param array $args
	 */
	function renderRunningContentChapterRightField( $args ) {
		$this->renderCustomSelect('running_content_chapter_right', 'running_content_chapter_right', @$this->options['running_content_chapter_right'], $args);
		$this->renderField('running_content_chapter_right', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_chapter_right', @$this->options['running_content_chapter_right'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_back_matter_left input.
	 * @param array $args
	 */
	function renderRunningContentBackMatterLeftField( $args ) {
		$this->renderCustomSelect('running_content_back_matter_left', 'running_content_back_matter_left', @$this->options['running_content_back_matter_left'], $args);
		$this->renderField('running_content_back_matter_left', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_back_matter_left', @$this->options['running_content_back_matter_left'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the running_content_back_matter_right input.
	 * @param array $args
	 */
	function renderRunningContentBackMatterRightField( $args ) {
		$this->renderCustomSelect('running_content_back_matter_right', 'running_content_back_matter_right', @$this->options['running_content_back_matter_right'], $args);
		$this->renderField('running_content_back_matter_right', 'pressbooks_theme_options_' . $this->getSlug(), 'running_content_back_matter_right', @$this->options['running_content_back_matter_right'], '', '', 'text', 'regular-text code');
	}

	/**
	 * Render the pdf_fontsize checkbox.
	 * @param array $args
	 */
	function renderFontSizeField( $args ) {
		$this->renderCheckbox( 'pdf_fontsize', 'pressbooks_theme_options_' . $this->getSlug(), 'pdf_fontsize', @$this->options['pdf_fontsize'], $args[0] );
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
  	return __('PDF Options', 'pressbooks');
  }

	/**
	 * Get an array of default values for the PDF options tab.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return array(
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
			'pdf_blankpages' => 'include',
			'pdf_toc' => 1,
			'pdf_image_resolution' => '300dpi',
			'pdf_crop_marks' => 0,
			'pdf_romanize_parts' => 0,
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
		);
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return array(
			'pdf_hyphens',
			'pdf_toc',
			'pdf_crop_marks',
			'pdf_romanize_parts',
			'pdf_fontsize'
		);
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	static function getStringOptions() {
		return array(
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
			'running_content_back_matter_right'
		);
	}

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	static function getIntegerOptions() {
		return array(
			'pdf_body_font_size',
			'widows',
			'orphans'
		);
	}

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	static function getFloatOptions() {
		return array(
			'pdf_body_line_height'
		);
	}

	/**
	 * Get an array of options which return predefined values.
	 *
	 * @return array $options
	 */
	static function getPredefinedOptions() {
		return array(
			'pdf_paragraph_separation',
			'pdf_blankpages',
			'pdf_image_resolution',
			'pdf_footnotes_style'
		);
	}
}
