<?php
/**
 *    Copyright (c) 2012 Lunar Logic LLC
 *
 *    This program is free software: you can redistribute it and/or  modify
 *    it under the terms of the GNU Affero General Public License, version 3,
 *    as published by the Free Software Foundation.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */



class LBFLoggerTest extends PHPUnit_Framework_TestCase {
    private $filename;
    private $logger;
    public function setUp() {
        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ('DIR_APPLIB', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib'. DIRECTORY_SEPARATOR);

        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);
        define ('OX_FRAME_EXCEPTIONS', DIR_FRAMELIB . 'exceptions' . DIRECTORY_SEPARATOR);

        require_once(DIR_FRAMELIB . 'Ox_LibraryLoader.php');
        require_once(DIR_FRAMELIB . 'Ox_Logger.php');

        $this->filename = DIR_APP . 'testlog.txt';
        $this->logger = new Ox_Logger($this->filename, Ox_Logger::allLogLevels());
    }

    public function tearDown() {
        unlink($this->filename);
    }

    // Test for the 4 log level logger functions
    public function testWrapperLogLevelMESSAGE() {
        Ox_Logger::logMessage("test message level");
        $fp = fopen($this->filename, 'r');
        $this->assertContains('MESSAGE test message level', fgets($fp));
        fclose($fp);
    }
    public function testWrapperLogLevelWARNING() {
        Ox_Logger::logWarning("test warning level");
        $fp = fopen($this->filename, 'r');
        $this->assertContains('WARNING test warning level', fgets($fp));
        fclose($fp);
    }
    public function testWrapperLogLevelERROR() {
        Ox_Logger::logError("test error level");
        $fp = fopen($this->filename, 'r');
        $this->assertContains('ERROR test error level', fgets($fp));
        fclose($fp);
    }
    public function testWrapperLogLevelDEBUG() {
        Ox_Logger::logDebug("test debug level");
        $fp = fopen($this->filename, 'r');
        $this->assertContains('DEBUG test debug level', fgets($fp));
        fclose($fp);
    }
    
}

?>