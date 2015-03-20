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
class Ox_WidgetHandlerTest extends PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);
        define ('OX_FRAME_DEFAULT', DIR_FRAMEWORK . 'default' . DIRECTORY_SEPARATOR);
        define ('OX_FRAME_EXCEPTIONS', DIR_FRAMELIB . 'exceptions' . DIRECTORY_SEPARATOR);

        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ('DIR_APPLIB', DIR_APP . 'lib'. DIRECTORY_SEPARATOR);
        define('DIR_CONSTRUCT',DIR_APP . 'constructs' . DIRECTORY_SEPARATOR);
        define('DIR_COMMON',DIR_CONSTRUCT . '_common' . DIRECTORY_SEPARATOR);


        require_once(DIR_FRAMELIB . 'Ox_LibraryLoader.php');
        require_once(DIR_FRAMELIB . 'Ox_WidgetHandler.php');
    }

    protected function tearDown()
    {
    }

    /**
     * @covers Ox_WidgetHandler::getInstance
     */
    public function testGetInstance()
    {
        Ox_WidgetHandler::getInstance();
        $this->assertTrue(interface_exists('Ox_Widget'));
    }

    /**
     * @covers Ox_WidgetHandler::__get
     */
    public function test__get()
    {
        $handler = Ox_WidgetHandler::getInstance();
        $widget = $handler->HtmlTitle;
        $this->assertTrue($widget instanceof HtmlTitle);
    }
    /**
     * @covers Ox_WidgetHandler::getWidget
     */
    public function testGetWidget()
    {
        Ox_WidgetHandler::getInstance();
        $widget = Ox_WidgetHandler::getWidget('JS');
        $this->assertTrue($widget instanceof JS);
    }
    /**
     * @covers Ox_WidgetHandler::getWidget
     */
    public function testRenderWidget()
    {
        Ox_WidgetHandler::getInstance();
        $this->expectOutputString('<title>Ox Framework</title>');
        Ox_WidgetHandler::renderWidget('HtmlTitle');
    }

    public function testRenderWidgetMissing()
    {
        Ox_WidgetHandler::getInstance();
        $this->expectOutputString('BROKEN Widget HtmlTitleMissing: Could not load or call the render function.');
        Ox_WidgetHandler::renderWidget('HtmlTitleMissing');
    }

    /**
     * @covers Ox_WidgetHandler::reset
     * @todo   Implement testReset().
     */
    public function testReset()
    {
        Ox_WidgetHandler::getInstance();
        Ox_WidgetHandler::getWidget('HtmlTitle')->set('Will Reset This Title');
        $this->expectOutputString('<title>Ox Framework</title>');
        Ox_WidgetHandler::reset('HtmlTitle');
        Ox_WidgetHandler::renderWidget('HtmlTitle');
    }
    public function testResetMissing()
    {
        Ox_WidgetHandler::getInstance();
        $this->expectOutputString('BROKEN Widget HtmlTitleMissing: Could not load or call the display function.');
        Ox_WidgetHandler::reset('HtmlTitleMissing');
    }

    /**
     * @covers Ox_WidgetHandler::__callStatic
     * @todo   Implement test__callStatic().
     */
    public function test__callStatic()
    {
        Ox_WidgetHandler::getInstance();
        $this->expectOutputString('<title>Ox Framework</title>');
        Ox_WidgetHandler::HtmlTitle();
    }
    public function test__callStaticMissing()
    {
        Ox_WidgetHandler::getInstance();
        $this->expectOutputString('BROKEN Widget HtmlTitleMissing: Could not load or call the render function.');
        Ox_WidgetHandler::HtmlTitleMissing();
    }
}
