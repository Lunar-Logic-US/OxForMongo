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
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ExceptionTest extends PHPUnit_Framework_TestCase {

    private $expectMessage = 'Dump not shown because Ox_Exception::DEBUG is false'; 
    private $appConfFile = <<<APP_CONFIG_FILE
<?php
    \$log_dir = '/tmp/';
    \$class_overload = array('session'=>'TestSession');
?>
APP_CONFIG_FILE;

    public function setUp()
    {
        define ('DIR_APP',dirname(dirname(dirname(__FILE__))) . '/tmp/');
        define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);
        file_put_contents(DIR_APPCONFIG . 'app.php',$this->appConfFile);

        define ('DIR_FRAMEWORK',dirname(dirname(dirname(dirname(__FILE__)))) . '/');
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        copy (DIR_FRAMEWORK . 'tests/lib/TestSession.php', DIR_APP . 'lib/TestSession.php');
        require_once(DIR_FRAMELIB . '/Ox_Dispatch.php');
        Ox_Dispatch::skipRun();
        require_once(DIR_FRAMEWORK . 'mainentry.php');
    }
    public function tearDown()
    {
    }

    // DEBUG is expected to be false by default
    public function testDebugFalseDefault()
    {
        $this->assertFalse(Ox_Exception::$DEBUG);
    }

    /**
     * @expectedException Ox_Exception
     */
    public function testDebugTrueObject()
    {
        Ox_Exception::$DEBUG = true;
        $testMessage = (object) array('key' => 'foo', 'key2' => 'bar');
        $this->expectExceptionMessage(var_export($testMessage, true));
        throw new Ox_Exception($testMessage);
    } 
    
    /**
     * @expectedException Ox_Exception
     */
    public function testDebugFalseObject()
    {
        Ox_Exception::$DEBUG = false;
        $testMessage = (object) array('key' => 'foo', 'key2' => 'bar');
        $this->expectExceptionMessage($this->expectMessage);
        throw new Ox_Exception($testMessage);
    } 

    /**
     * @expectedException Ox_Exception
     */
    public function testDebugTrueString()
    {
        Ox_Exception::$DEBUG = true;
        $testMessage = "somestr";
        $this->expectExceptionMessage($testMessage);
        throw new Ox_Exception($testMessage);
    } 

    /**
     * @expectedException Ox_Exception
     */
    public function testDebugFalseString()
    {
        Ox_Exception::$DEBUG = false;

        $testMessage = "somestr";
        $this->expectExceptionMessage = $testMessage;
        throw new Ox_Exception($testMessage);
    } 

    /**
     * @expectedException Ox_Exception
     */
    public function testDebugTrueArray()
    {
        Ox_Exception::$DEBUG = true;

        $testMessage = [7, 3, "42"];
        $this->expectExceptionMessage(var_export($testMessage, true));
        throw new Ox_Exception($testMessage);
    } 

    /**
     * @expectedException Ox_Exception
     */
    public function testDebugFalseArray()
    {
        Ox_Exception::$DEBUG = false;
        $testMessage = [7, 3, "42"];
        $this->expectExceptionMessage($this->expectMessage);
        throw new Ox_Exception($testMessage);
    } 

}  
?>
