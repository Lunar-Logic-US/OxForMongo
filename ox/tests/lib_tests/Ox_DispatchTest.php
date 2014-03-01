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
class Ox_DispatchTest extends PHPUnit_Framework_TestCase
{
    private $app_config_file = <<<APP_CONFIG_FILE
<?php
\$log_dir = DIR_APP . '';
\$log_file = 'ox.log';
?>
APP_CONFIG_FILE;


    protected function setUp()
    {
        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        //define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);

        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);
        //define ('DIR_APPLIB', DIR_APP . 'lib'. DIRECTORY_SEPARATOR);
        //define('DIR_CONSTRUCT',DIR_APP . 'constructs' . DIRECTORY_SEPARATOR);
        //define('DIR_COMMON',DIR_CONSTRUCT . '_common' . DIRECTORY_SEPARATOR);


        //require_once(DIR_FRAMELIB . 'Ox_LibraryLoader.php');
        require_once(DIR_FRAMELIB . 'Ox_Dispatch.php');
        //$this->object = Ox_WidgetHandler::getInstance();
        //$this->object = Ox_WidgetHandler::getInstance();
    }

    protected function tearDown()
    {
    }

    /**
     */
    public function testRun()
    {
        file_put_contents(DIR_APPCONFIG . 'app.php',$this->app_config_file);

        $_SERVER['REQUEST_URI']='/test';

        Ox_Dispatch::skipRun();
        require_once(DIR_FRAMEWORK . 'mainentry.php');
        Ox_Dispatch::allowRun();
        $this->expectOutputRegex('/class="error404"/');
        Ox_Dispatch::run();

    }
}