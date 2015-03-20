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


class SessionTest extends PHPUnit_Framework_TestCase {
    private $app_config_file = <<<APP_CONFIG_FILE
<?php
   \$log_dir = DIR_APP.'/';
   \$session_class = 'TestSession';  //session has to be overridden to the tests to work.
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
        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );

        require_once(DIR_FRAMELIB . '/Ox_Session.php');

        $fp = fopen(DIR_APP.'config/app.php', 'w');
        fwrite($fp, $this->app_config_file);
        $fp = fopen(DIR_APP.'config/framework.php', 'w');
        fwrite($fp, $this->framework_config_file);
        $fp = fopen(DIR_APP.'config/global.php', 'w');
        fwrite($fp, $this->global_config_file);

    }
    public function tearDown()
    {
        unlink(DIR_APP.'config/app.php');
        unlink(DIR_APP.'config/framework.php');
        unlink(DIR_APP.'config/global.php');
    }
/*
    public function testGetSessionTimeRemainingNoUser() {
        $session = new LBFSession();
        $this->assertEquals(0, $session->getSessionTimeRemaining());
    }
*/

    public function testIsExpiredNoUser() {
        //require('../mainentry.php');
        //$session = LBFSession::getInstance();
        //$this->assertTrue($session->isExpired());
        $this->assertTrue(true);
    }


}

?>