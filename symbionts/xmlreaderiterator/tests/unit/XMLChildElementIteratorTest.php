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
 * Class XMLChildElementIteratorTest
 */
class XMLChildElementIteratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    function iteration()
    {
        $reader = new XMLReaderStub('<!-- comment --><root><child></child></root>');

        $it = new XMLChildElementIterator($reader);

        $this->assertEquals(false, $it->valid());
        $this->assertSame(null, $it->valid());

        $it->rewind();
        $this->assertEquals(true, $it->valid());
        $this->assertEquals('child', $it->current()->getName());

        $it->next();
        $this->assertEquals(false, $it->valid());

        $reader = new XMLReaderStub('<root><none></none><one><child></child></one><none></none></root>');
        $base = new XMLElementIterator($reader);
        $base->rewind();
        $root = $base->current();
        $this->assertEquals('root', $root->getName());
        $children = $root->getChildElements();
        $this->assertEquals('root', $reader->name);
        $children->rewind();
        $this->assertEquals('none', $reader->name);
        $children->next();
        $this->assertEquals('one', $reader->name);
        $childChildren = new XMLChildElementIterator($reader);
        $this->assertEquals('child', $childChildren->current()->getName());
        $childChildren->next();
        $this->assertEquals(false, $childChildren->valid());
        $this->assertEquals('none', $reader->name);
        $childChildren->next();
        $this->assertEquals('none', $reader->name);

        $this->assertEquals(true, $children->valid());
        $children->next();
        $this->assertEquals(false, $children->valid());


        // children w/o descendants
        $reader->rewind();
        $expected = array('none', 'one', 'none');
        $root = $base->current();
        $this->assertEquals('root', $root->getName());

        $count = 0;
        foreach($root->getChildElements() as $index => $child) {
            $this->assertSame($count++, $index);
            $this->assertEquals($expected[$index], $reader->name);
        }
        $this->assertEquals(count($expected), $count);

        // children w/ descendants
        $reader->rewind();
        $expected = array('none', 'one', 'child', 'none');
        $root = $base->current();
        $this->assertEquals('root', $root->getName());

        $count = 0;
        foreach($root->getChildElements(null, true) as $index => $child) {
            $this->assertSame($count++, $index);
            $this->assertEquals($expected[$index], $reader->name);
        }
        $this->assertEquals(count($expected), $count);

    }
}
