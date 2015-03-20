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
$appConfigFile = <<<APP_CONFIG_FILE
<?php
\$log_dir = '/tmp/';
\$mongo_config = array(
    'set_string_id' => TRUE,
    'persistent' => TRUE,
    'host'       => 'localhost',
    'database'   => 'test',
    'port'       => '27017',
    'login'         => '',
    'password'      => '',
    'replicaset'    => '',
);

?>
APP_CONFIG_FILE;



require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '/boot.php');
/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RouterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        //define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        //define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        //define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);

        //require_once(OX_FRAMEINTERFACE . '/Ox_Routable.php');
        //require_once(DIR_FRAMELIB . '/Ox_Router.php');
        //require_once(DIR_FRAMELIB . '/Ox_Logger.php');
    }

    public function tearDown()
    {
    }
    
    public function testBuildURLMultipleParams()
    {
        $this->assertEquals('/home/add_page?r=%2Fusers%2Flogin&value=Ren%27s+page', Ox_Router::buildURL('/home/add_page', array('r'=>'/users/login','value'=>'Ren\'s page')));
    }
    
    public function testBuildURLSingleParam()
    {
        $this->assertEquals('/home/add_page?r=%2Fusers%2Flogin', Ox_Router::buildURL('/home/add_page', array('r'=>'/users/login')));
    }
    
    public function testBuildURLEmptyParams()
    {
        $this->assertEquals('/home/add_page', Ox_Router::buildURL('/home/add_page', array()));
    }

    public function testBuildURLNoParams()
    {
        $this->assertEquals('/home/add_page', Ox_Router::buildURL('/home/add_page'));
    }

    public function testAdd()
    {
        Ox_Router::add("/testing/",null);
        $routeResult = Ox_Router::getAll();
        $this->assertTrue(array_key_exists('/testing/',$routeResult));
    }
    /*
    public function testRedirect()
    {
        print "got here";
        Ox_Router::redirect('testlocation');
        $results = xdebug_get_headers();
        //$results = ob_get_flush();
        var_dump($results);
        $this->assertTrue(false);
    }*/
    public function testRouteSuccess()
    {
        //define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');


        $testClass = 'TestAction';
        $fileData=<<<PHP
<?php
class $testClass implements Ox_Routable
{
    public function go(\$args)
    {
        define('ROUTE_SUCCESS',implode('|',\$args));
    }

}
PHP;
        $fileName = DIR_APP."{$testClass}.php";
        file_put_contents($fileName,$fileData);

        require_once($fileName);
        Ox_Router::add('/^testroute$/',new TestAction());
        Ox_Router::route('testroute');
        $this->assertTrue(defined('ROUTE_SUCCESS'));

        unlink($fileName);
    }

    public function testRouteWithArguments()
    {
        $testClass = 'TestAction';
        $fileData=<<<PHP
<?php
class $testClass implements Ox_Routable
{
    public function go(\$args)
    {
        define('ROUTE_SUCCESS',implode('|',\$args));
    }

}
PHP;
        $fileName = DIR_APP."/lib/{$testClass}.php";
        file_put_contents($fileName,$fileData);
        require_once($fileName);

        Ox_Router::addTop('/^testroute(\d*)\/?(\d*)$/',new TestAction());
        Ox_Router::route('testroute555/666');
        $this->assertTrue(defined('ROUTE_SUCCESS'));
        $args = explode('|',ROUTE_SUCCESS);
        $this->assertEquals($args[1],555);
        $this->assertEquals($args[2],666);

        unlink($fileName);
    }


    public function testRouteMissing()
    {
        $testClass = 'TestAction';
        $fileData=<<<PHP
<?php
class $testClass implements Ox_Routable
{
    public function go(\$args)
    {
        define('ROUTE_SUCCESS',implode('|',\$args));
    }

}
PHP;
        $fileName = DIR_APP."{$testClass}.php";
        file_put_contents($fileName,$fileData);

        require_once($fileName);
        Ox_Router::add('/^testroutecantfind$/',new TestAction());
        Ox_Router::route('testroute');
        $this->assertTrue(!defined('ROUTE_SUCCESS'));

        unlink($fileName);
    }

    public function testRemove()
    {
        $testRoute = '/newroute/';
        //make sure newroute is not in the list
        $routeList = Ox_Router::getAll();
        $this->assertFalse(array_key_exists($testRoute,$routeList));

        //Add it to the list.
        Ox_Router::add($testRoute,new Ox_AssetAction());
        $routeList = Ox_Router::getAll();
        $this->assertTrue(array_key_exists($testRoute,$routeList));

        //remove it from the list.
        Ox_Router::remove($testRoute);
        $routeList = Ox_Router::getAll();
        $this->assertFalse(array_key_exists($testRoute,$routeList));
    }

    public function testRemoveDefaults()
    {
        //Get the current list and make sure they are there
        $routeList = Ox_Router::getAll();
        $this->assertTrue(array_key_exists(WEB_ROOT,$routeList));
        $this->assertTrue(array_key_exists(WEB_ASSET_ROUTE,$routeList));
        //Remove all of the default routes.
        Ox_Router::remove(WEB_ROOT);
        Ox_Router::remove(WEB_ASSET_ROUTE);
        //routerList should now be empty.
        $routeList = Ox_Router::getAll();
        $this->assertFalse(array_key_exists(WEB_ROOT,$routeList));
        $this->assertFalse(array_key_exists(WEB_ASSET_ROUTE,$routeList));
    }

    public function testAddTop()
    {
        $testRoute = '/newroute/';
        $routeList = Ox_Router::getAll();
        //make sure the first element is not our test route.
        reset($routeList);
        $first_key = key($routeList);
        $this->assertNotEquals($testRoute,$first_key);

        //Add the testroute to the top
        Ox_Router::addTop($testRoute,new Ox_AssetAction());
        $routeList = Ox_Router::getAll();
        reset($routeList);
        $first_key = key($routeList);
        $this->assertEquals($testRoute,$first_key);
    }
}
