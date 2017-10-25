<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <div>
 *
 * Attribute requirements: `data-type="equation"` (From [DOCBOOK]; no close match in [EPUB3SSV])
 *
 * Note: HTMLBook supports embedded MathML in HTML content documents, which can be used here.
 *
 * Example:
 *
 *     <div data-type="equation">
 *       <h5>Pythagorean Theorem</h5>
 *       <math xmlns="http://www.w3.org/1998/Math/MathML">
 *         <msup><mi>a</mi><mn>2</mn></msup>
 *         <mo>+</mo>
 *         <msup><mi>b</mi><mn>2</mn></msup>
 *         <mo>=</mo>
 *         <msup><mi>c</mi><mn>2</mn></msup>
 *       </math>
 *     </div>
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_equation
 */
class Equation {

	/**
	 * @var string
	 */
	protected $tag = 'div';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'equation',
	];

}
