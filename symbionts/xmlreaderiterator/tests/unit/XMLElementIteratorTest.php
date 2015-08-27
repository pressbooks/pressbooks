<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013 hakre <http://hakre.wordpress.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author hakre <http://hakre.wordpress.com>
 * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>
 */

/**
 * Class XMLElementIteratorTest
 */
class XMLElementIteratorTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function creationAndCurrent()
    {
        $reader = $this->createReader();

        $it = new XMLElementIterator($reader);

        $this->assertSame('xml', $it->current()->getName());
        $it->next();
        $this->assertSame('node1', $it->current()->getName());
        $it->next();
        $this->assertSame('info1', $it->current()->getName());
    }

    /** @test */
    public function string()
    {
        $reader = new XMLReaderStub('<root><b>has</b></root>');

        /** @var XMLElementIterator|XMLReaderNode[]|XMLReader $it */
        $it = new XMLElementIterator($reader);

        $it->rewind();
        $this->assertEquals(true, $it->valid());
        $this->assertEquals("has", (string) $it);
        $this->assertEquals("has", $it->readString());
    }

    /** @test */
    public function iteration()
    {
        $reader = new XMLReaderStub('<root><b>has</b></root>');

        /** @var XMLElementIterator|XMLReaderNode[] $it */
        $it = new XMLElementIterator($reader);

        $this->assertEquals(false, $it->valid());
        $this->assertSame(null, $it->valid());

        $it->rewind();
        $this->assertEquals(true, $it->valid());
        $this->assertEquals('root', $it->current()->getName());
        $this->assertEquals(0, $it->key());

        $it->rewind();
        $this->assertEquals(true, $it->valid());
        $current = $it->current();
        $this->assertEquals('root', $current->getName());
        $this->assertEquals(0, $it->key());

        $string = $current->readString();
        $this->assertEquals('has', $string);

        $it->next();
        $this->assertEquals(true, $it->valid());
        $current = $it->current();
        $this->assertEquals('b', $current->getName());
        $this->assertEquals(1, $it->key());

        $it->next();
        $this->assertEquals(false, $it->valid());
        $current = $it->current();
        $this->assertEquals(null, $current);

    }

    /** @test */
    public function getChildren()
    {
        $reader = $this->createReader();

        $it = new XMLElementIterator($reader);

        $xml = $it->current();
        $this->assertSame('xml', $xml->name); // ensure this is the root node
        $it->next();

        $array = $it->toArray();
        $this->assertSame(7, count($array));
        $this->assertSame("\n                test\n            ", $array['node4']);
    }

    /**
     * @test
     */
    function iterateOverNamedElements()
    {
        $reader = new XMLReaderStub('<r><a>1</a><a>2</a><b>c</b><a>3</a></r>');
        $it     = new XMLElementIterator($reader, 'a');

        $this->assertEquals(null, $it->valid());
        $it->rewind();
        $this->assertEquals(true, $it->valid());
        $this->assertEquals('a', $it->current()->getName());
        $it->next();
        $this->assertEquals('a', $it->current()->getName());
        $it->next();
        $this->assertEquals('a', $it->current()->getName());
        $this->assertEquals('3', $it);
        $it->next();
        $this->assertEquals(false, $it->valid());
    }

    private function createReader()
    {
        return new XMLReaderStub('<!-- -->
        <xml>
            <node1>
                <info1/>
            </node1>
            <node2 id="0">
                <info2>
                    <pool2/>
                </info2>
            </node2>
            <node3/>
            <node4>
                test
            </node4>
        </xml>');
    }
}
