<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013, 2015 hakre <http://hakre.wordpress.com>
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
 * Class XMLReaderIterationTest
 */
class XMLReaderIterationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $iterator = new XMLReaderIteration(new XMLReaderStub('<root/>'));
        $this->assertInstanceOf('XMLReaderIteration', $iterator);
        $this->assertInstanceOf('Traversable', $iterator);
        $this->assertInstanceOf('Iterator', $iterator);
    }

    /**
     * @test
     */
    public function iteration()
    {
        $reader   = new XMLReaderStub('<root><element></element></root>');
        $iterator = new XMLReaderIteration($reader);

        $data = array(
            array(XMLReader::ELEMENT, 0),
            array(XMLReader::ELEMENT, 1),
            array(XMLReader::END_ELEMENT, 1),
            array(XMLReader::END_ELEMENT, 0),
        );

        $count = 0;

        /* @var $reader XMLReader */
        foreach ($iterator as $index => $reader) {
            $this->assertSame($count, $index);
            list($nodeType, $depth) = $data[$index];
            $this->assertSame($nodeType, $reader->nodeType);
            $this->assertSame($depth, $reader->depth);
            $count++;
        }

        $this->assertSame(4, $count);
    }

    /**
     * @test
     */
    public function skipNextRead()
    {
        $reader   = new XMLReaderStub('<r/>');
        $iterator = new XMLReaderIteration($reader);

        $key = null;

        foreach ($iterator as $key => $node) {
            $this->assertEquals('r', $node->name);
            if ($key >= 6) {
                break;
            }
            $iterator->skipNextRead();
        }

        $this->assertEquals(6, $key);

        $reader   = new XMLReaderStub('<r><a/><a><b><c/></b></a><a></a><a/></r>');
        $iterator = new XMLReaderIteration($reader);

        foreach ($iterator as $node) {
            if ($node->name === 'r') {
                continue;
            }
            $this->assertEquals(XMLReader::ELEMENT, $node->nodeType);
            $this->assertEquals('a', $node->name);

            $node->next();
            $iterator->skipNextRead();
        }
    }
}
