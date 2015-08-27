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
 * Class XMLElementIterator
 *
 * Iterate over XMLReader element nodes
 *
 * @method string readString() inherited from IteratorIterator decoration of XMLReader
 */
class XMLElementIterator extends XMLReaderIterator
{
    private $index;
    private $name;
    private $didRewind;

    /**
     * @param XMLReader   $reader
     * @param null|string $name element name, leave empty or use '*' for all elements
     */
    public function __construct(XMLReader $reader, $name = null)
    {
        parent::__construct($reader);
        $this->setName($name);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        parent::rewind();
        $this->ensureCurrentElementState();
        $this->didRewind = true;
        $this->index     = 0;
    }

    /**
     * @return XMLReaderNode|null
     */
    public function current()
    {
        $this->didRewind || self::rewind();

        $this->ensureCurrentElementState();

        return self::valid() ? new XMLReaderNode($this->reader) : null;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        if (parent::valid()) {
            $this->index++;
        }
        parent::next();
        $this->ensureCurrentElementState();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = array();

        $this->didRewind || $this->rewind();

        if (!$this->valid()) {
            return array();
        }

        $this->ensureCurrentElementState();

        while ($this->valid()) {
            $element = new XMLReaderNode($this->reader);
            if ($this->reader->hasValue) {
                $string = $this->reader->value;
            } else {
                $string = $element->readString();
            }
            if ($this->name) {
                $array[] = $string;
            } else {
                $array[$element->name] = $string;
            }
            $this->moveToNextElementByName($this->name);
        }

        return $array;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->readString();
    }

    /**
     * decorate method calls
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->current(), $name), $args);
    }

    /**
     * decorate property get
     *
     * @param string $name
     *
     * @return string
     */
    public function __get($name)
    {
        return $this->current()->$name;
    }

    /**
     * @param null|string $name
     */
    public function setName($name = null)
    {
        $this->name = '*' === $name ? null : $name;
    }

    /**
     * take care the underlying XMLReader is at an element with a fitting name (if $this is looking for a name)
     */
    private function ensureCurrentElementState()
    {
        if ($this->reader->nodeType !== XMLReader::ELEMENT) {
            $this->moveToNextElementByName($this->name);
        } elseif ($this->name && $this->name !== $this->reader->name) {
            $this->moveToNextElementByName($this->name);
        }
    }
}
