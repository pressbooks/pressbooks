<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014 hakre <http://hakre.wordpress.com>
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
 * Class XMLReaderNextIteration
 *
 * Iteration over XMLReader skipping subtrees
 *
 * @link http://php.net/manual/en/xmlreader.next.php
 *
 * @since 0.1.5
 */
class XMLReaderNextIteration implements Iterator
{
    /**
     * @var XMLReader
     */
    private $reader;
    private $index;
    private $valid;
    private $localName;

    public function __construct(XMLReader $reader, $localName = null)
    {
        $this->reader    = $reader;
        $this->localName = $localName;
    }

    public function rewind()
    {
        // XMLReader can not rewind, instead we move on if before the first node
        $this->moveReaderToCurrent();

        $this->index = 0;
    }

    public function valid()
    {
        return $this->valid;
    }

    public function current()
    {
        return $this->reader;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->valid && $this->index++;
        if ($this->localName) {
            $this->valid = $this->reader->next($this->localName);
        } else {
            $this->valid = $this->reader->next();
        }
    }

    /**
     * move cursor to the next element but only if it's not yet there
     */
    private function moveReaderToCurrent()
    {
        if (
            ($this->reader->nodeType === XMLReader::NONE)
            or ($this->reader->nodeType !== XMLReader::ELEMENT)
            or ($this->localName && $this->localName !== $this->reader->localName)
        ) {
            self::next();
        }
    }
}
