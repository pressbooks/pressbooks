<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2015 hakre <http://hakre.wordpress.com>
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
 * Class XMLReaderTest
 *
 * @covers XMLSequenceStream
 */
class XMLSequenceStreamTest extends XMLReaderTestCase
{
    /**
     * @return array
     * @see declPosPattern
     */
    public function provideDeclarations()
    {
        return array(
            array('foo <?xml version="1.0"?>'),
            array('foo <?xml version="1.0" ?>'),
            array('foo <?xml     version  =   "1.0" ?>'),
            array('foo <?xml version="1.0" encoding="UTF-8"?>'),
        );
    }

    /**
     * @return array
     * @see readStream
     */
    public function provideStreamFiles()
    {
        return $this->addXmlFiles(array(), __DIR__ . '/../fixtures/streams');
    }

    /**
     * @test
     *
     * @dataProvider provideDeclarations
     *
     * @param string $subject
     */
    public function declPosPattern($subject)
    {
        $result = preg_match(XMLSequenceStream::DECL_POS_PATTERN, $subject, $matches, PREG_OFFSET_CAPTURE);
        $this->assertNotEquals(false, $result);
        $this->assertArrayHasKey(0, $matches, 'First match exists');
        $this->assertEquals(4, $matches[0][1], 'First match offset is correct');
    }

    /**
     * @test
     * @dataProvider provideStreamFiles
     *
     * @param $file
     */
    public function readStream($file)
    {
        stream_wrapper_register('xmlseq', 'XMLSequenceStream');
        $path = "xmlseq://" . $file;

        $count = 0;
        $xmlFileContents = array();
        while (XMLSequenceStream::notAtEndOfSequence($path)) {
            $count++;
            $reader = new XMLReader();
            $reader->open($path, 'UTF-8', LIBXML_COMPACT | LIBXML_PARSEHUGE);
            /** @var XMLElementIterator|XMLReaderNode $elements */
            $elements = new XMLElementIterator($reader);
            $xmlFileContents[] = $elements->getSimpleXMLElement();
        }

        XMLSequenceStream::clean();
        stream_wrapper_unregister('xmlseq');
        $this->assertGreaterThanOrEqual(2, $count, 'number of sequences');
    }
}
