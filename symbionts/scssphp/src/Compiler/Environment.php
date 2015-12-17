<?php
/**
 * SCSSPHP
 *
 * @copyright 2012-2015 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://leafo.github.io/scssphp
 */

namespace Leafo\ScssPhp\Compiler;

use Leafo\ScssPhp\Block;

/**
 * SCSS compiler environment
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
class Environment
{
    /**
     * @var \Leafo\ScssPhp\Block
     */
    public $block;

    /**
     * @var \Leafo\ScssPhp\Compiler\Environment
     */
    public $parent;

    /**
     * @var array
     */
    public $store;

    /**
     * @var integer
     */
    public $depth;
}
