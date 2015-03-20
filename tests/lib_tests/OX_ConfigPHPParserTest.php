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

class Ox_ConfigPHPParserTest extends PHPUnit_Framework_TestCase {
    private $app_config_file = <<<APP_CONFIG_FILE
<?php
\$log_dir = '/var/log/lbf';
?>
APP_CONFIG_FILE;
    private $framework_config_file = <<<FRAMEWORK_CONFIG_FILE
<?php
\$max_timeout = 10000;
?>
FRAMEWORK_CONFIG_FILE;
    private $config_parser;
    public function setUp() {
        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        define ('OX_FRAMEACTIONS', DIR_FRAMELIB . 'actions' . DIRECTORY_SEPARATOR);
        define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);
        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);

        require_once(DIR_FRAMELIB.'/Ox_ConfigPHPParser.php');

        $fp = fopen(DIR_APPCONFIG . 'app.php', 'w');
        fwrite($fp, $this->app_config_file);
        $fp = fopen(DIR_APPCONFIG .'/framework.php', 'w');
        fwrite($fp, $this->framework_config_file);
        $this->config_parser = new Ox_ConfigPHPParser(DIR_APPCONFIG);
    }
    
    public function tearDown() {
        unlink(DIR_APPCONFIG.'app.php');
        unlink(DIR_APPCONFIG.'framework.php');
    }
    public function testGetValueValid() {
        $this->assertEquals('/var/log/lbf', $this->config_parser->getValue('app', 'log_dir'));
    }
    public function testGetValueInvalid() {
        $this->assertNull($this->config_parser->getValue('framework', 'log_dir'));
    }
    public function testGetAppConfigValueValidKey() {
        $this->assertEquals('/var/log/lbf', $this->config_parser->getAppConfigValue('log_dir'));
    }

    /*  No longer valid as framework.php is no longer parsed.
    public function testGetFrameworkConfigValueValidKey() {
        $this->assertEquals(10000, $this->config_parser->getFrameworkConfigValue('max_timeout'));
    }
    */
    public function testGetAppConfigValueInvalidKey() {
        $this->assertNull(($this->config_parser->getAppConfigValue('log')));
    }
    public function testGetFrameworkConfigInvalueValidKey() {
        $this->assertNull(($this->config_parser->getFrameworkConfigValue('timeout')));
    }
    
}
?>