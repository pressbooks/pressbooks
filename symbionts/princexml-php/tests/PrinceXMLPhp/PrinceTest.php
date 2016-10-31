<?php

/*
 * This file is part of the PrinceXMLPhp.
 *
 * (c) Gridonic <hello@gridonic.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PrinceXMLPhp\Tests;

use PrinceXMLPhp\PrinceWrapper;

class PrinceTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf('PrinceXMLPhp\\PrinceWrapper', new PrinceWrapper('/usr/local/bin/prince'));
    }
}