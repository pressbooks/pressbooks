<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014, 2015 hakre <http://hakre.wordpress.com>
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
 * Class XMLReaderNodeTest
 */
class XMLReaderNodeTest extends XMLReaderTestCase
{
    /**
     * some XMLReaderNode can not be turned into a SimpleXMLElement, this tests how robust XMLReaderNode
     * is for the job.
     *
     * @test
     */
    function asSimpleXMLforElementAndSignificantWhitespace()
    {
        $reader = new XMLReaderStub('<root>
            <!-- <3 <3 love XMLReader::SIGNIFICANT_WHITESPACE (14) <3 <3 -->
        </root>');

        $reader->read(); // (#1) <root>

        // test asSimpleXML() for XMLReader::ELEMENT
        $this->assertSame(XMLReader::ELEMENT, $reader->nodeType);
        $node = new XMLReaderNode($reader);
        $sxml = $node->getSimpleXMLElement();
        $this->assertInstanceOf('SimpleXMLElement', $sxml);

        $reader->read(); // (#14) SIGNIFICANT_WHITESPACE

        // test asSimpleXML() for XMLReader::SIGNIFICANT_WHITESPACE
        $this->assertSame(XMLReader::SIGNIFICANT_WHITESPACE, $reader->nodeType);
        $node = new XMLReaderNode($reader);
        $sxml = $node->getSimpleXMLElement();
        $this->assertNull($sxml);
    }

    /**
     * @test
     */
    function expand()
    {
        $reader = new XMLReaderStub('<products>
            <!--suppress HtmlUnknownAttribute -->
            <product category="Desktop">
                <name> Desktop 1 (d)</name>
                <price>499.99</price>
            </product>
            <!--suppress HtmlUnknownAttribute -->
            <product category="Tablet">
                <name>Tablet 1 (t)</name>
                <price>1099.99</price>
            </product>
        </products>');

        $products = new XMLElementIterator($reader, 'product');
        $doc      = new DOMDocument;
        $xpath    = new DOMXpath($doc);
        foreach ($products as $product) {
            $node = $product->expand($doc);
            $this->assertInstanceOf('DOMNode', $node);
            $this->assertSame($node->ownerDocument, $doc);
            $this->assertEquals('product', $xpath->evaluate('local-name(.)', $node));
            $this->addToAssertionCount(1);
        }
        $this->assertGreaterThan(0, $previous = $this->getNumAssertions());

        unset($doc);
        $reader->rewind();
        foreach ($products as $product) {
            $node = $product->expand();
            $this->assertInstanceOf('DOMNode', $node);
            $this->assertInstanceOf('DOMDocument', $node->ownerDocument);
            $doc   = $node->ownerDocument;
            $xpath = new DOMXpath($doc);
            $this->assertSame($node->ownerDocument, $doc);
            $this->assertEquals('product', $xpath->evaluate('local-name(.)', $node));
            $this->addToAssertionCount(1);
        }

        $this->assertGreaterThan($previous, $this->getNumAssertions());
    }
}
