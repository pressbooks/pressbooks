<?php
/**
 * SCSSPHP
 *
 * @copyright 2015 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://leafo.github.io/scssphp
 */

namespace Leafo\ScssPhp\Base;

/**
 * Range class
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
class Range
{
    public $first;
    public $last;

    /**
     * Initialize range
     *
     * @param integer|float $first
     * @param integer|float $last
     */
    public function __construct($first, $last)
    {
        $this->first = $first;
        $this->last = $last;
    }

    /**
     * Test for inclusion in range
     *
     * @param integer|float $value
     *
     * @return boolean
     */
    public function includes($value)
    {
        return $value >= $this->first && $value <= $this->last;
    }
}
