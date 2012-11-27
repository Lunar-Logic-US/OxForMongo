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



class BootstrapTest extends PHPUnit_Framework_TestCase {
    private $app_config_file = <<<APP_CONFIG_FILE
<?php
   \$log_dir = DIR_APP.'/';
   \$class_overload = array('session'=>'TestSession');
?>
APP_CONFIG_FILE;
    private $framework_config_file = <<<FRAMEWORK_CONFIG_FILE
<?php

?>
FRAMEWORK_CONFIG_FILE;
    private $global_config_file = <<<GLOBAL_CONFIG_FILE
<?php

?>
GLOBAL_CONFIG_FILE;

    public function setUp()
    {
        define ('DIR_APP',dirname(__FILE__) . '/tmp/');
        define ('DIR_FRAMEWORK',dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );

        define ('DIR_APPLIB', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib'. DIRECTORY_SEPARATOR);
        define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);

        define ('OX_FRAME_EXCEPTIONS', DIR_FRAMELIB . 'exceptions' . DIRECTORY_SEPARATOR);
        require_once (OX_FRAME_EXCEPTIONS . 'Ox_Exception.php');
        require_once(DIR_FRAMELIB . 'Ox_Dispatch.php');
        Ox_Dispatch::skipRun();


        $fp = fopen(DIR_APPCONFIG.'app.php', 'w');
        fwrite($fp, $this->app_config_file);

        $fp = fopen(DIR_APPCONFIG.'framework.php', 'w');
        fwrite($fp, $this->framework_config_file);

        $fp = fopen(DIR_APPCONFIG.'global.php', 'w');
        fwrite($fp, $this->global_config_file);

    }
    public function tearDown()
    {
        unlink(DIR_APPCONFIG.'app.php');
        unlink(DIR_APPCONFIG.'framework.php');
        unlink(DIR_APPCONFIG.'global.php');
    }
    
    public function testConfigParserSet()
    {
        require(DIR_FRAMEWORK.'mainentry.php');
        global $config_parser;
        $this->assertTrue(isset($config_parser),'$config_parser does not exist.');
        $this->assertTrue($config_parser instanceof Ox_ConfigPHPParser,'$config_parser is not of type ConfigPHPParser.');
    }

    public function testLoggerSet()
    {
        require(DIR_FRAMEWORK.'mainentry.php');
        $this->assertTrue(class_exists('Ox_Logger'),'Ox_Logger Class does not exist.');
    }

    /**
     * This also tests the class override for a singleton
     */
    public function testSessionSet()
    {
        global $session;
        require(DIR_FRAMEWORK.'mainentry.php');
        $this->assertTrue(isset($session),'$session does not exist.');
        $this->assertTrue($session instanceof Ox_Session);
    }

    public function testDBSet()
    {
        global $db;
        require(DIR_FRAMEWORK.'mainentry.php');
        $this->assertTrue(isset($db),'$db does not exist.');
        $this->assertTrue($db instanceof Ox_MongoSource);
    }

    public function testSecuritySet()
    {
        global $security;
        require(DIR_FRAMEWORK.'mainentry.php');
        $this->assertTrue(isset($security),'$security does not exist.');
        $this->assertTrue($security instanceof Ox_Security);
    }

    public function testRouterSet()
    {
        global $router;
        require(DIR_FRAMEWORK.'mainentry.php');
        $this->assertTrue(isset($router),'$router does not exist.');
        $this->assertTrue($router instanceof Ox_Router);
    }
    public function testAssetHelperSet()
    {
        global $assets_helper;
        require(DIR_FRAMEWORK.'mainentry.php');
        $this->assertTrue(isset($assets_helper));
        $this->assertTrue($assets_helper instanceof LocalAsset);
    }
    public function testWidgetHandlerSet()
    {
        global $widget_handler;
        require(DIR_FRAMEWORK.'mainentry.php');
        $this->assertTrue(isset($widget_handler));
        $this->assertTrue($widget_handler instanceof Ox_WidgetHandler);
    }
}

?>