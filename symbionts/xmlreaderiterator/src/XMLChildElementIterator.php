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
 * Class XMLChildElementIterator
 *
 * Iterate over child element nodes of the current XMLReader node
 */
class XMLChildElementIterator extends XMLElementIterator
{
    /**
     * @var null|int
     */
    private $stopDepth;

    /**
     * @var bool
     */
    private $descendTree;

    /**
     * @var bool
     */
    private $didRewind;

    /**
     * @var int
     */
    private $index;

    /**
     * @inheritdoc
     *
     * @param bool $descendantAxis traverse children of children
     */
    public function __construct(XMLReader $reader, $name = null, $descendantAxis = false)
    {
        parent::__construct($reader, $name);
        $this->descendTree = $descendantAxis;
    }

    /**
     * @throws UnexpectedValueException
     * @return void
     */
    public function rewind()
    {
        // this iterator can not really rewind. instead it places itself onto the
        // first children.

        if ($this->reader->nodeType === XMLReader::NONE) {
            $this->moveToNextElement();
        }

        if ($this->stopDepth === null) {
            $this->stopDepth = $this->reader->depth;
        }

        // move to first child - if any
        parent::next();
        parent::rewind();

        $this->index = 0;
        $this->didRewind = true;
    }

    public function next()
    {
        if ($this->valid()) {
            $this->index++;
        }

        while ($this->valid()) {
            parent::next();
            if ($this->descendTree || $this->reader->depth === $this->stopDepth + 1) {
                break;
            }
        };
    }

    public function valid()
    {
        if (!($valid = parent::valid())) {
            return $valid;
        }

        return $this->reader->depth > $this->stopDepth;
    }

    /**
     * @return XMLReaderNode|null
     */
    public function current()
    {
        $this->didRewind || self::rewind();
        return parent::current();
    }

    public function key()
    {
        return $this->index;
    }
}
