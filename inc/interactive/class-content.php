<?php

namespace Pressbooks\Interactive;

use Pressbooks\Book;
use Pressbooks\Container;

class Content {

	/**
	 * Anchor we append to URLs to hint that its interactive content
	 */
	const ANCHOR = '#pb-interactive-content';

	/**
	 * @var Content
	 */
	private static $instance = null;

	/**
	 * @var \Jenssegers\Blade\Blade
	 */
	protected $blade;

	/**
	 * @return Content
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			$blade = Container::get( 'Blade' );
			self::$instance = new self( $blade );
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Content $obj
	 */
	static public function hooks( Content $obj ) {
		if ( Book::isBook() ) {
			add_action( 'pb_pre_export', [ $obj, 'beforeExport' ] );
		}
	}

	/**
	 * @param \Jenssegers\Blade\Blade $blade
	 */
	public function __construct( $blade ) {
		$this->blade = $blade;
	}

	/**
	 * Hooked into `pb_pre_export` action
	 */
	public function beforeExport() {
		$this->overrideH5P();
	}

	/**
	 * Override H5P WordPress Plugin
	 */
	protected function overrideH5P() {
		$h5p = new H5P( $this->blade );
		if ( $h5p->isActive() ) {
			$h5p->override();
		}
	}

}
