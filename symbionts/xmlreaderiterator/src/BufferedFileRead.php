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
 * Class BufferedFileRead
 *
 * @since 0.1.3
 */
final class BufferedFileRead
{
    const MODE_READ_BINARY = 'rb';
    /**
     * @var string
     */
    public $buffer;

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var string
     */
    private $file;

    /**
     * number of bytes to have *maximum* ahead in buffer at read
     *
     * @var int
     * @see readAhead
     */
    private $maxAhead = 8192;

    /**
     * number of bytes to read ahead. can not be larger than
     * maxAhead.
     *
     * @var int
     * @see maxAhead
     */
    private $readAhead = 0;

    /**
     * @param      $filename
     * @param      $mode
     * @param null $use_include_path
     * @param null $context
     *
     * @return bool
     */
    public function fopen($filename, $mode, $use_include_path = null, $context = null) {

        if ($mode !== self::MODE_READ_BINARY) {
            $message = sprintf(
                "unsupported mode '%s', only '%s' is supported for buffered file read", $mode, self::MODE_READ_BINARY
            );
            trigger_error($message);

            return false;
        }

        if ($context === null) {
            $handle = fopen($filename, self::MODE_READ_BINARY, $use_include_path);
        } else {
            $handle = fopen($filename, self::MODE_READ_BINARY, $use_include_path, $context);
        }

        if (!$handle) {
            return false;
        }

        $this->file   = $filename;
        $this->handle = $handle;

        return true;
    }

    /**
     * appends up to $count bytes to the buffer up to
     * the read-ahead limit
     *
     * @param $count
     *
     * @return int|bool length of buffer or FALSE on error
     */
    public function append($count)
    {
        $bufferLen = strlen($this->buffer);

        if ($bufferLen >= $count + $this->maxAhead) {
            return $bufferLen;
        }

        ($ahead = $this->readAhead)
            && ($delta = $bufferLen - $ahead) < 0
            && $count -= $delta;

        $read = fread($this->handle, $count);
        if ($read === false) {
            throw new UnexpectedValueException(sprintf('Can not deal with fread() errors.'));
        }

        if ($readLen = strlen($read)) {
            $this->buffer .= $read;
            $bufferLen += $readLen;
        }

        return $bufferLen;
    }

    /**
     * shift bytes from buffer
     *
     * @param $bytes - up to buffer-length bytes
     *
     * @return string
     */
    public function shift($bytes)
    {
        $bufferLen = strlen($this->buffer);

        if ($bytes === $bufferLen) {
            $return       = $this->buffer;
            $this->buffer = '';
        } else {
            $return       = substr($this->buffer, 0, $bytes);
            $this->buffer = substr($this->buffer, $bytes);
        }

        return $return;
    }

    public function fread($count) {
        return fread($this->handle, $count);
    }

    public function feof() {
        return feof($this->handle);
    }

    /**
     * @return string
     */
    public function getFile() {
        return $this->file;
    }

    public function __toString() {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getReadAhead() {
        return $this->readAhead;
    }

    /**
     * @param int $readAhead
     */
    public function setReadAhead($readAhead) {
        $this->readAhead = max(0, (int)$readAhead);
    }

    public function close() {
        if ($this->handle && fclose($this->handle)) {
            $this->handle = null;
        }

        $this->buffer = '';
    }

    public function __destruct() {
        $this->close();
    }
}
