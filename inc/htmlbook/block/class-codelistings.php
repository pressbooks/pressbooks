<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <pre>
 *
 * Attribute requirements: `data-type="programlisting"`
 *
 * Optional HTMLBook-specific attribute: `data-code-language`, used to indicate language of code listing (e.g.,
 * `data-code-language="php"`)
 *
 * Example:
 *
 *     <pre data-type="programlisting">print "<em>Hello World</em>"</pre>
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_code_listings
 */
class CodeListings extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'pre';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'programlisting',
	];

	/**
	 * @var string
	 */
	protected $codeLanguage;

	/**
	 * @return string
	 */
	public function getCodeLanguage(): string {
		return $this->codeLanguage;
	}

	/**
	 * @param string $code_language
	 */
	public function setCodeLanguage( string $code_language ) {
		$this->codeLanguage = $code_language;
	}

	/**
	 * @return string
	 */
	public function attributes(): string {
		if ( ! empty( $this->codeLanguage ) ) {
			$this->attributes['data-code-language'] = $this->codeLanguage;
		}
		return parent::attributes();
	}

}
