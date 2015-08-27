<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013, 2014 hakre <http://hakre.wordpress.com>
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
 * Class XMLReaderStub
 *
 * Stub class to write tests more quickly, allows to create an XMLReader on strings with XML
 *
 * @see XMLReader
 */
class XMLReaderStub extends XMLReader
{
    private $xml;

    public function __construct($xml)
    {
        $this->xml = $xml;
        $this->rewind();
    }

    public function rewind()
    {
        $xml = $this->xml;

        if ($xml[0] === '<') {
            $xml = 'data://text/xml;base64,' . base64_encode($this->xml);
        }

        $this->open($xml);
    }
}
