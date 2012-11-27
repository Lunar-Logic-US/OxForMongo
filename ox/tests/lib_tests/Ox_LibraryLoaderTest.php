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
class OxLibraryLoaderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);
        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ('DIR_APPLIB', DIR_APP . 'lib'. DIRECTORY_SEPARATOR);
        define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);
        define ('OX_FRAME_EXCEPTIONS', DIR_FRAMELIB . 'exceptions' . DIRECTORY_SEPARATOR);
        define ('OX_FRAME_DEFAULT', DIR_FRAMEWORK . 'default' . DIRECTORY_SEPARATOR);

        require_once(DIR_FRAMELIB.'Ox_Logger.php');
        require_once(DIR_FRAMELIB.'Ox_LibraryLoader.php');
    }

    public function tearDown()
    {
    }

    // ------------
    // loadCode Tests
    // ------------
    public function testLoadCodeOxLibrary()
    {
        $testClass = 'Ox_Logger';
        Ox_LibraryLoader::loadCode($testClass);
        $this->assertTrue(class_exists($testClass,FALSE));
    }

    public function testLoadCodeAppLibrary()
    {
        $testClass = 'TestClass';
        $fileData=<<<PHP
<?php
class $testClass
{
    function __construct()
    {
    }

}
PHP;
        $fileName = DIR_APPLIB."{$testClass}.php";
        $fp = fopen($fileName, 'w');
        fwrite($fp, $fileData);
        fclose($fp);

        Ox_LibraryLoader::loadCode($testClass);
        $this->assertTrue(class_exists($testClass,FALSE));
        unlink($fileName);
    }

    public function testLoadCodeAppLibraryLowercase()
    {
        $testClass = 'LowerClass';
        $fileData=<<<PHP
<?php
class $testClass
{
    function __construct()
    {
    }

}
PHP;
        $fileName = DIR_APPLIB. strtolower($testClass) .".php";
        $fp = fopen($fileName, 'w');
        fwrite($fp, $fileData);
        fclose($fp);

        Ox_LibraryLoader::loadCode($testClass);
        $this->assertTrue(class_exists($testClass,FALSE));
        unlink($fileName);
    }

    public function testLoadCodeAppLibraryWithPath()
    {
        $testClass = 'LowerClass';
        $fileData=<<<PHP
<?php
class $testClass
{
    function __construct()
    {
    }

}
PHP;
        $fileName = DIR_APPLIB. strtolower($testClass) .".php";
        $fp = fopen($fileName, 'w');
        fwrite($fp, $fileData);
        fclose($fp);

        Ox_LibraryLoader::loadCode($testClass,array(DIR_APPLIB));
        $this->assertTrue(class_exists($testClass,FALSE));
        unlink($fileName);
    }

    public function testLoadCodeNotFound()
    {
        $this->setExpectedException('Ox_Exception');
        Ox_LibraryLoader::loadCode("WillNotFind.php",array(DIR_APPLIB));
    }

    // ------------
    // Load Tests
    // ------------
    public function testLoadNormalClass()
    {
        $testClass = 'TestClass';
        $fileData=<<<PHP
<?php
class $testClass
{
    function $testClass()
    {
    }

}
PHP;
        $fileName = DIR_APPLIB."{$testClass}.php";
        $fp = fopen($fileName, 'w');
        fwrite($fp, $fileData);
        fclose($fp);

        Ox_LibraryLoader::load('testGlobal',$testClass,FALSE);
        global $testGlobal;
        $this->assertTrue(isset($testGlobal));
        $this->assertTrue($testGlobal instanceof $testClass);
        unlink($fileName);
    }

    public function testLoadSingletonClass()
    {
        $testClass = 'TestSingletonClass';
        $fileData=<<<PHP
<?php
class $testClass
{
    private static \$_instance = NULL;


    public static function getInstance()
    {
        if (is_null(self::\$_instance)) {
            self::\$_instance = new self();
        }
        return self::\$_instance;
    }
    private function __construct()
    {
    }

}
PHP;
        $fileName = DIR_APPLIB."{$testClass}.php";
        $fp = fopen($fileName, 'w');
        fwrite($fp, $fileData);
        fclose($fp);

        Ox_LibraryLoader::load('testGlobal',$testClass);
        global $testGlobal;
        $this->assertTrue(isset($testGlobal));
        $this->assertTrue($testGlobal instanceof $testClass);
        unlink($fileName);
    }

    public function testLoadOverload()
    {
        $testClass = 'TestOverload';
        $fileData=<<<PHP
<?php
class $testClass
{
    private static \$_instance = NULL;


    public static function getInstance()
    {
        if (is_null(self::\$_instance)) {
            self::\$_instance = new self();
        }
        return self::\$_instance;
    }
    private function __construct()
    {
    }

}
PHP;
        $fileName = DIR_APPLIB."{$testClass}.php";
        $fp = fopen($fileName, 'w');
        fwrite($fp, $fileData);
        fclose($fp);

        $app_config_file =<<<APP_CONFIG_FILE
<?php
   \$log_dir = DIR_APP.'/';
   \$class_overload = array('session'=>'$testClass');
APP_CONFIG_FILE;
        $fileNameConfig = DIR_APPCONFIG."app.php";
        $fp = fopen($fileNameConfig, 'w');
        fwrite($fp, $app_config_file);
        fclose($fp);

        Ox_LibraryLoader::load('config_parser','Ox_ConfigPHPParser',FALSE);
        Ox_LibraryLoader::load('session',$testClass);
        global $session;
        $this->assertTrue(isset($session));
        $this->assertTrue($session instanceof $testClass);

        unlink($fileName);
        unlink($fileNameConfig);
    }

    public function testLoadOverloadNotArray()
    {
        Ox_LibraryLoader::clearConfig();
        $app_config_file =<<<APP_CONFIG_FILE
<?php
   \$log_dir = DIR_APP.'/';
   \$class_overload = 'This is a string';
APP_CONFIG_FILE;
        $fileNameConfig = DIR_APP."config/app.php";
        $fp = fopen($fileNameConfig, 'w');
        fwrite($fp, $app_config_file);
        fclose($fp);

        Ox_LibraryLoader::load('config_parser','Ox_ConfigPHPParser',FALSE);

        $this->setExpectedException('Ox_Exception');
        Ox_LibraryLoader::load('session','some class');

        unlink($fileNameConfig);
    }

                 /*
    public function testLoadMissingClass()
    {
        $testClass = 'TestMissing';
        $fileData=<<<PHP
<?php
PHP;
        $fileName = DIR_APPLIB."{$testClass}.php";
        $fp = fopen($fileName, 'w');
        fwrite($fp, $fileData);
        fclose($fp);

        $app_config_file =<<<APP_CONFIG_FILE
<?php
   \$log_dir = DIR_APP.'/';
   //\$class_overload = array('session'=>'$testClass');
APP_CONFIG_FILE;
        $fileNameConfig = DIR_APP."config/app.php";
        $fp = fopen($fileNameConfig, 'w');
        fwrite($fp, $app_config_file);
        fclose($fp);

        Ox_LibraryLoader::load('config_parser','Ox_ConfigPHPParser',FALSE);
        Ox_LibraryLoader::load('session',$testClass);
        global $session;
        $this->assertTrue(isset($session));
        $this->assertTrue($session instanceof $testClass);

        unlink($fileName);
        unlink($fileNameConfig);
    }
                 */

    // ------------
    // Path Tests
    // ------------
    public function testGetDefaultPath()
    {
        $defaultPath = Ox_LibraryLoader::getPath();
        $expectedPath = array(DIR_FRAMELIB,DIR_APPLIB);
        $this->assertEquals($defaultPath,$expectedPath);
    }
    public function testAddPath()
    {
        $pathToAdd='NewPath';
        Ox_LibraryLoader::addPath($pathToAdd);
        $newPath = Ox_LibraryLoader::getPath();
        $this->assertTrue(in_array($pathToAdd,$newPath));
        //$this->assertEquals($defaultPath,$expectedPath);
    }


    public function testLoadAll()
    {
        $testClass = 'Test1Class';
        $fileData=<<<PHP
<?php
class $testClass
{
    function $testClass()
    {
    }

}
PHP;
        $fileName1 = DIR_APPLIB."{$testClass}.php";
        file_put_contents($fileName1,$fileData);
        $testClass = 'Test2Class';
        $fileData=<<<PHP
<?php
class $testClass
{
    function $testClass()
    {
    }

}
PHP;
        $fileName2 = DIR_APPLIB."{$testClass}.php";
        file_put_contents($fileName2,$fileData);

        $testClass = 'NotIncludedClass';
        $fileData=<<<PHP
<?php
class $testClass
{
    function $testClass()
    {
    }

}
PHP;
        $fileName3 = DIR_APPLIB."{$testClass}.txt";
        file_put_contents($fileName3,$fileData);


        Ox_LibraryLoader::loadAll(DIR_APPLIB);
        $this->assertTrue(class_exists('Test1Class',false));
        $this->assertTrue(class_exists('Test2Class',false));
        $this->assertFalse(class_exists('NotIncludedClass',false));
        unlink($fileName1);
        unlink($fileName2);
        unlink($fileName3);
    }


}
