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
 * Class XMLSequenceStreamPath
 *
 * @since 0.1.3
 */
class XMLSequenceStreamPath
{
    /**
     * @var string
     */
    private $path;

    public function __construct($path) {
        $this->path = $path;
    }

    public function getProtocol() {
        $parts = $this->parsePath($this->path);
        return $parts['scheme'];
    }

    public function getSpecific() {
        $parts = $this->parsePath($this->path);
        return $parts['specific'];
    }

    public function getFile() {
        $specific = $this->getSpecific();
        $specific = str_replace(array('\\', '/./'), '/', $specific);
        return $specific;
    }

    private function parsePath($path) {

        $parts = array_combine(array('scheme', 'specific'), explode('://', $path, 2) + array(null, null));

        if (null === $parts['specific']) {
            throw new UnexpectedValueException(sprintf("Path '%s' has no protocol", $path));
        }

        return $parts;
    }

    function __toString() {
        return $this->path;
    }
}
