<?php

namespace Pressbooks\Covergenerator;

/**
 * Mutator methods (get/set) for all things Input. (Input object is passed as constructor parameter to Generator)
 *
 * @see Generator
 */
class Input {

	/**
	 * Book title
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Book title (for spine)
	 *
	 * @var string
	 */
	protected $spine_title;

	/**
	 * Book subtitle
	 *
	 * @var string
	 */
	protected $subtitle;

	/**
	 * Book author
	 *
	 * @var string
	 */
	protected $author;

	/**
	 * Book author (for spine)
	 *
	 * @var string
	 */
	protected $spine_author;

	/**
	 * About the book text
	 *
	 * @var string
	 */
	protected $about;

	/**
	 * Path to ISBN image
	 *
	 * @var string
	 */
	protected $isbnImage;

	/**
	 * Text transform
	 *
	 * @var string
	 */
	protected $textTransform;

	/**
	 * Trim width
	 * CSS compatible, example: '6in'
	 *
	 * @var string
	 */
	protected $trimWidth;

	/**
	 * Trim height
	 * CSS compatible, example: '9in'
	 *
	 * @var string
	 */
	protected $trimHeight;

	/**
	 * Trim bleed
	 * CSS compatible, example: '0.125in'
	 *
	 * @var string
	 */
	protected $trimBleed;

	/**
	 * Spine width
	 * CSS compatible, example: '0.725in'
	 *
	 * @var string
	 */
	protected $spineWidth;

	/**
	 * Spine background color
	 * CSS compatible, example: 'pink' or '#FFC0CB' or ...
	 *
	 * @var string
	 */
	protected $spineBackgroundColor;

	/**
	 * Spine font color
	 * CSS compatible, example: 'black' or '#000000' or ...
	 *
	 * @var string
	 */
	protected $spineFontColor;

	/**
	 * Back-cover background color
	 * CSS compatible, example: 'green' or '#008000' or ...
	 *
	 * @var string
	 */
	protected $backBackgroundColor;

	/**
	 * Back-cover font color
	 * CSS compatible, example: 'black' or '#000000' or ...
	 *
	 * @var string
	 */
	protected $backFontColor;

	/**
	 * Path to front-cover background image
	 *
	 * @var string
	 */
	protected $frontBackgroundImage;

	/**
	 * Front-cover background color
	 * CSS compatible, example: 'yellow' or '#ffff00' or ...
	 *
	 * @var string
	 */
	protected $frontBackgroundColor;

	/**
	 * Front-cover font color
	 * CSS compatible, example: 'black' or '#000000' or ...
	 *
	 * @var string
	 */
	protected $frontFontColor;

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title
	 *
	 * @return Input
	 */
	public function setTitle( $title ) {
		$this->title = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSpineTitle() {
		return $this->spine_title;
	}

	/**
	 * @param string $spine_title
	 *
	 * @return Input
	 */
	public function setSpineTitle( $spine_title ) {
		$this->spine_title = $spine_title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSubtitle() {
		return $this->subtitle;
	}

	/**
	 * @param string $subtitle
	 *
	 * @return Input
	 */
	public function setSubtitle( $subtitle ) {
		$this->subtitle = $subtitle;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * @param string $author
	 *
	 * @return Input
	 */
	public function setAuthor( $author ) {
		$this->author = $author;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSpineAuthor() {
		return $this->spine_author;
	}

	/**
	 * @param string $spine_author
	 *
	 * @return Input
	 */
	public function setSpineAuthor( $spine_author ) {
		$this->spine_author = $spine_author;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAbout() {
		return $this->about;
	}

	/**
	 * @param string $about
	 *
	 * @return Input
	 */
	public function setAbout( $about ) {
		$this->about = $about;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIsbnImage() {
		return $this->isbnImage;
	}

	/**
	 * @param string $isbn_image
	 *
	 * @return Input
	 */
	public function setIsbnImage( $isbn_image ) {
		$this->isbnImage = $isbn_image;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTextTransform() {
		return $this->textTransform;
	}

	/**
	 * @param string $text_transform
	 *
	 * @return Input
	 */
	public function setTextTransform( $text_transform ) {
		$this->textTransform = $text_transform;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTrimWidth() {
		return $this->trimWidth;
	}

	/**
	 * @param string $trim_width
	 *
	 * @return Input
	 */
	public function setTrimWidth( $trim_width ) {
		$this->trimWidth = $trim_width;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTrimHeight() {
		return $this->trimHeight;
	}

	/**
	 * @param string $trim_height
	 *
	 * @return Input
	 */
	public function setTrimHeight( $trim_height ) {
		$this->trimHeight = $trim_height;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTrimBleed() {
		return $this->trimBleed;
	}

	/**
	 * @param string $trim_bleed
	 *
	 * @return Input
	 */
	public function setTrimBleed( $trim_bleed ) {
		$this->trimBleed = $trim_bleed;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSpineWidth() {
		return $this->spineWidth;
	}

	/**
	 * @param string $spine_width
	 *
	 * @return Input
	 */
	public function setSpineWidth( $spine_width ) {
		$this->spineWidth = $spine_width;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSpineBackgroundColor() {
		return $this->spineBackgroundColor;
	}

	/**
	 * @param string $spine_background_color
	 *
	 * @return Input
	 */
	public function setSpineBackgroundColor( $spine_background_color ) {
		$this->spineBackgroundColor = $spine_background_color;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSpineFontColor() {
		return $this->spineFontColor;
	}

	/**
	 * @param string $spine_font_color
	 *
	 * @return Input
	 */
	public function setSpineFontColor( $spine_font_color ) {
		$this->spineFontColor = $spine_font_color;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBackBackgroundColor() {
		return $this->backBackgroundColor;
	}

	/**
	 * @param string $back_background_color
	 *
	 * @return Input
	 */
	public function setBackBackgroundColor( $back_background_color ) {
		$this->backBackgroundColor = $back_background_color;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBackFontColor() {
		return $this->backFontColor;
	}

	/**
	 * @param string $back_font_color
	 *
	 * @return Input
	 */
	public function setBackFontColor( $back_font_color ) {
		$this->backFontColor = $back_font_color;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFrontBackgroundImage() {
		return $this->frontBackgroundImage;
	}

	/**
	 * @param string $front_background_image
	 *
	 * @return Input
	 */
	public function setFrontBackgroundImage( $front_background_image ) {
		$this->frontBackgroundImage = wp_json_encode( $front_background_image, JSON_UNESCAPED_SLASHES );
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFrontBackgroundColor() {
		return $this->frontBackgroundColor;
	}

	/**
	 * @param string $front_background_color
	 *
	 * @return Input
	 */
	public function setFrontBackgroundColor( $front_background_color ) {
		$this->frontBackgroundColor = $front_background_color;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFrontFontColor() {
		return $this->frontFontColor;
	}

	/**
	 * @param string $front_font_color
	 *
	 * @return Input
	 */
	public function setFrontFontColor( $front_font_color ) {
		$this->frontFontColor = $front_font_color;
		return $this;
	}
}
