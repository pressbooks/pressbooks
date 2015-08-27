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

class ExamplesTest extends XMLReaderTestCase
{
    private $cwd;

    protected function setUp()
    {
        $this->cwd = getcwd();
        parent::setUp();
    }

    protected function tearDown()
    {
        chdir($this->cwd);
        parent::tearDown();
    }


    /**
     * @test
     *
     * @param $file
     *
     * @throws Exception
     * @throws PHPUnit_Framework_SkippedTest
     *
     * @dataProvider provideExampleFiles
     */
    public function runPhpFile($file) {
        $name = basename($file, '.php');

        $buffer = null;
        try {
            $this->addToAssertionCount(1);
            ob_start();
            {
                chdir(dirname($file));
                $this->saveInclude($file);
            }
            $buffer = ob_get_clean();
        } catch(PHPUnit_Framework_SkippedTest $e) {
            throw $e;
        } catch(Exception $e) {
            $this->fail(sprintf('Example %s did throw an exception %s with message %s.', $name, get_class($e), $e->getMessage()));
        }

        $expectedFile = $this->getExpectedFile($file);
        $expected = file_get_contents($expectedFile);
        if ($expected[0] === '~') {
            $this->assertNotSame(false, preg_match($expected, ""), 'validate the regex pattern for validity first');
            $this->assertRegExp($expected, $buffer);
        } else {
            $this->assertEquals($expected, $buffer);
        }
    }

    private function saveInclude() {
        include func_get_arg(0);
    }

    private function getExpectedFile($forFile) {
        $name = basename($forFile);
        $name = strtr($name, '.', '_');
        $file = __DIR__ . '/Expectations/' . $name . '.out';

        return $file;
    }

    /**
     * @return array
     *
     * @see runPhpFile
     */
    public function provideExampleFiles()
    {
        $path = __DIR__ . '/../../../examples';

        return $this->addFiles(array(), $path, '~^(?!xmlreader-iterators)[^.]+\.php$~');
    }
}
