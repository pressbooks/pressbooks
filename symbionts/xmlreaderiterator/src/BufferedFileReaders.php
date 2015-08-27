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
 * Class BufferedFileReaders
 *
 * Brigade of BufferedFileRead objects as keyed instances based on
 * their filename.
 *
 * @since 0.1.3
 */
class BufferedFileReaders
{
    /**
     * this wrapper is a multi-singleton based on the filename
     *
     * @var BufferedFileRead[]
     */
    private $readers;

    /**
     * @param $filename
     * @param $mode
     * @param $use_include_path
     * @param $context
     *
     * @return BufferedFileRead or null on error
     */
    public function getReaderForFile($filename, $mode, $use_include_path, $context)
    {
        $readers = $this->readers;
        if (!isset($readers[$filename])) {
            $reader = new BufferedFileRead();
            $result = $reader->fopen($filename, $mode, $use_include_path, $context);

            return $this->readers[$filename] = $result ? $reader : null;
        }
        return $readers[$filename];
    }

    public function close()
    {
        if (!$this->readers) {
            return;
        }

        foreach ($this->readers as $reader) {
            $reader && $reader->close();
        }

        $this->readers = null;
    }

    public function removeReaderForFile($filename)
    {
        if (!isset($this->readers[$filename])) {
            return false;
        }

        $this->readers[$filename]->close();

        unset($this->readers[$filename]);

        return true;
    }

    public function isFileConsumed($filename)
    {
        if (!isset($this->readers[$filename]) || !$reader = $this->readers[$filename]) {
            return false;
        }

        if ($reader->feof() && !strlen($reader->buffer)) {
            return true;
        }

        return false;
    }

    public function __destruct()
    {
        $this->close();
    }
}
