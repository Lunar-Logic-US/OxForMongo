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

class BootstrapTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once(
            dirname(__FILE__) . DIRECTORY_SEPARATOR .
            'boot' . DIRECTORY_SEPARATOR .
            'boot.php'
        );
    }
    
    public function testConfigParserSet()
    {
        require(DIR_FRAMEWORK . 'mainentry.php');
        global $config_parser;
        $this->assertTrue(
            isset($config_parser),
            '$config_parser does not exist.'
        );
        $this->assertTrue(
            $config_parser instanceof Ox_ConfigPHPParser,
            '$config_parser is not of type ConfigPHPParser.'
        );
    }

    public function testLoggerSet()
    {
        require(DIR_FRAMEWORK . 'mainentry.php');
        $this->assertTrue(
            class_exists('Ox_Logger'),
            'Ox_Logger Class does not exist.'
        );
    }

    /**
     * This also tests the class override for a singleton
     */
    public function testSessionSet()
    {
        global $session;
        require(DIR_FRAMEWORK . 'mainentry.php');
        $this->assertTrue(isset($session), '$session does not exist.');
        $this->assertTrue($session instanceof Ox_Session);
    }

    public function testDBSet()
    {
        global $db;
        require(DIR_FRAMEWORK . 'mainentry.php');
        $this->assertTrue(isset($db), '$db does not exist.');
        $this->assertTrue($db instanceof Ox_MongoSource);
    }

    public function testSecuritySet()
    {
        global $security;
        require(DIR_FRAMEWORK . 'mainentry.php');
        $this->assertTrue(isset($security), '$security does not exist.');
        $this->assertTrue($security instanceof Ox_Security);
    }

    public function testRouterSet()
    {
        global $router;
        require(DIR_FRAMEWORK . 'mainentry.php');
        $this->assertTrue(isset($router), '$router does not exist.');
        $this->assertTrue($router instanceof Ox_Router);
    }

    public function testAssetHelperSet()
    {
        global $assets_helper;
        require(DIR_FRAMEWORK . 'mainentry.php');
        $this->assertTrue(isset($assets_helper));
        $this->assertTrue($assets_helper instanceof LocalAsset);
    }

    public function testWidgetHandlerSet()
    {
        global $widget_handler;
        require(DIR_FRAMEWORK . 'mainentry.php');
        $this->assertTrue(isset($widget_handler));
        $this->assertTrue($widget_handler instanceof Ox_WidgetHandler);
    }
}
