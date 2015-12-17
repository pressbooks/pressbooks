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

namespace Leafo\ScssPhp\Node;

use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Node;
use Leafo\ScssPhp\Type;

/**
 * SCSS dimension + optional units
 *
 * {@internal
 *     This is a work-in-progress.
 *
 *     The \ArrayAccess interface is temporary until the migration is complete.
 * }}
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
class Number extends Node implements \ArrayAccess
{
    /**
     * @var integer
     */
    static public $precision = 5;

    /**
     * @see http://www.w3.org/TR/2012/WD-css3-values-20120308/
     *
     * @var array
     */
    static protected $unitTable = array(
        'in' => array(
            'in' => 1,
            'pc' => 6,
            'pt' => 72,
            'px' => 96,
            'cm' => 2.54,
            'mm' => 25.4,
            'q'  => 101.6,
        ),
        'turn' => array(
            'deg'  => 360,
            'grad' => 400,
            'rad'  => 6.28318530717958647692528676, // 2 * M_PI
            'turn' => 1,
        ),
        's' => array(
            's'  => 1,
            'ms' => 1000,
        ),
        'Hz' => array(
            'Hz'  => 1,
            'kHz' => 0.001,
        ),
        'dpi' => array(
            'dpi'  => 1,
            'dpcm' => 2.54,
            'dppx' => 96,
        ),
    );

    /**
     * @var integer|float
     */
    public $dimension;

    /**
     * @var array
     */
    public $units;

    /**
     * Initialize number
     *
     * @param mixed $dimension
     * @param mixed $initialUnit
     */
    public function __construct($dimension, $initialUnit)
    {
        $this->type      = Type::T_NUMBER;
        $this->dimension = $dimension;
        $this->units     = is_array($initialUnit)
            ? $initialUnit
            : ($initialUnit ? array($initialUnit => 1)
                            : array());
    }

    /**
     * Coerce number to target units
     *
     * @param array $units
     *
     * @return \Leafo\ScssPhp\Node\Number
     */
    public function coerce($units)
    {
        if ($this->unitless()) {
            return new Number($this->dimension, $units);
        }

        $dimension = $this->dimension;

        foreach (self::$unitTable['in'] as $unit => $conv) {
            $from       = isset($this->units[$unit]) ? $this->units[$unit] : 0;
            $to         = isset($units[$unit]) ? $units[$unit] : 0;
            $factor     = pow($conv, $from - $to);
            $dimension /= $factor;
        }

        return new Number($dimension, $units);
    }

    /**
     * Normalize number
     *
     * @return \Leafo\ScssPhp\Node\Number
     */
    public function normalize()
    {
        $dimension = $this->dimension;
        $units     = array();

        $this->normalizeUnits($dimension, $units, 'in');

        return new Number($dimension, $units);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if ($offset === -2) {
            return $sourceIndex !== null;
        }

        if ($offset === -1
            || $offset === 0
            || $offset === 1
            || $offset === 2
        ) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        switch ($offset) {
            case -2:
                return $this->sourceIndex;

            case -1:
                return $this->sourcePosition;

            case 0:
                return $this->type;

            case 1:
                return $this->dimension;

            case 2:
                return $this->units;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === 1) {
            $this->dimension = $value;
        } elseif ($offset === 2) {
            $this->units = $value;
        } elseif ($offset == -1) {
            $this->sourcePosition = $value;
        } elseif ($offset == -2) {
            $this->sourceIndex = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($offset === 1) {
            $this->dimension = null;
        } elseif ($offset === 2) {
            $this->units = null;
        } elseif ($offset === -1) {
            $this->sourcePosition = null;
        } elseif ($offset === -2) {
            $this->sourceIndex = null;
        }
    }

    /**
     * Returns true if the number is unitless
     *
     * @return boolean
     */
    public function unitless()
    {
        return ! array_sum($this->units);
    }

    /**
     * Returns unit(s) as the product of numerator units divided by the product of denominator units
     *
     * @return string
     */
    public function unitStr()
    {
        $numerators = array();
        $denominators = array();

        foreach ($this->units as $unit => $unitSize) {
            if ($unitSize > 0) {
                $numerators = array_pad($numerators, count($numerators) + $unitSize, $unit);
                continue;
            }

            if ($unitSize < 0) {
                $denominators = array_pad($denominators, count($denominators) + $unitSize, $unit);
                continue;
            }
        }

        return implode('*', $numerators) . (count($denominators) ? '/' . implode('*', $denominators) : '');
    }

    /**
     * Output number
     *
     * @param \Leafo\ScssPhp\Compiler $compiler
     *
     * @return string
     */
    public function output(Compiler $compiler = null)
    {
        $dimension = round($this->dimension, self::$precision);

        $units = array_filter($this->units, function ($unitSize) {
            return $unitSize;
        });

        // @todo refactor normalize()
        if (count($units) > 1 && array_sum($units) === 0) {
            $dimension = $this->dimension;
            $units     = array();

            $this->normalizeUnits($dimension, $units, 'in');

            $dimension = round($dimension, self::$precision);
            $units     = array_filter($units, function ($unitSize) {
                return $unitSize;
            });
        }

        $unitSize = array_sum($units);

        if ($compiler && ($unitSize > 1 || $unitSize < 0 || count($units) > 1)) {
            $compiler->throwError((string) $dimension . $this->unitStr() . " isn't a valid CSS value.");
        }

        reset($units);
        list($unit, ) = each($units);

        return (string) $dimension . $unit;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->output();
    }

    /**
     * Normalize units
     *
     * @param integer|float $dimension
     * @param array         $units
     * @param string        $baseUnit
     */
    private function normalizeUnits(&$dimension, &$units, $baseUnit = 'in')
    {
        $dimension = $this->dimension;
        $units = array();

        foreach ($this->units as $unit => $exp) {
            if (isset(self::$unitTable[$baseUnit][$unit])) {
                $factor = pow(self::$unitTable[$baseUnit][$unit], $exp);

                $unit = $baseUnit;
                $dimension /= $factor;
            }

            $units[$unit] = $exp + (isset($units[$unit]) ? $units[$unit] : 0);
        }
    }
}
