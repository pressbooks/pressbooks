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
 * Class XMLReaderElementTest
 */
class XMLReaderElementTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var XMLReader
     */
    protected $reader;

    protected function setUp()
    {
        $this->reader  = new XMLReaderStub('<root><child pos="first">node value</child><child pos="first"/></root>');
    }

    /** @test */
    public function elementCreation() {
        $reader = $this->reader;
        $reader->next();
        $element = new XMLReaderElement($reader);
        $this->assertSame($element->getNodeTypeName(), $element->getNodeTypeName(XMLReader::ELEMENT));
        $this->assertSame($element->name, 'root');
    }

    /** @test */
    public function readerAttributeHandling() {
        $reader = new XMLReaderStub("<root pos=\"first\" plue=\"a&#13;&#10;b&#32;  c\t&#9;d\">node value</root>");
        $reader->next();
        $this->assertSame("first", $reader->getAttribute('pos'));
        $this->assertSame("a\r\nb   c \td", $reader->getAttribute('plue'), 'entity handling');
        $element = new XMLReaderElement($reader);
        $xml = $element->getXMLElementAround();
        $this->assertSame("<root pos=\"first\" plue=\"a&#13;&#10;b   c &#9;d\"/>", $xml, 'XML generation');
    }

    /** @test  */
    public function checkNodeValue() {
        $reader = new XMLReaderStub('<root><b>has</b></root>');

        /** @var XMLElementIterator|XMLReaderNode[] $it */
        $it = new XMLElementIterator($reader);
        $count = 0;
        foreach ($it as $element) {
            $this->assertEquals('has', $element->readString());
            $count++;
        }
        $this->assertEquals(2, $count);
    }
}
