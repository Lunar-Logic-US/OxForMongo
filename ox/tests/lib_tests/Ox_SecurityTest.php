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
class SecurityTest extends PHPUnit_Framework_TestCase {

    private $appConfFile = <<<APP_CONFIG_FILE
<?php
    \$log_dir = '/tmp/';
    \$class_overload = array('session'=>'TestSession');
?>
APP_CONFIG_FILE;

    public function setUp()
    {
        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);
        file_put_contents(DIR_APPCONFIG . 'app.php',$this->appConfFile);

        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . '/');
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        copy (DIR_FRAMEWORK . 'tests/lib/TestSession.php', DIR_APP . 'lib/TestSession.php');
        require_once(DIR_FRAMELIB . '/Ox_Dispatch.php');
        Ox_Dispatch::skipRun();
        require_once(DIR_FRAMEWORK . 'mainentry.php');
    }
    public function tearDown()
    {
    }

    public function testCheckPermAllowed()
    {
        $security = new Ox_Security();
        $required = array('admin', 'manager');
        $user_roles = array('admin');
        $this->assertTrue($security->checkPermission($required, $user_roles));
    }
    public function testCheckPermDenied() {
        $security = new Ox_SecurityMongoCollection();
        $required = array('admin');
        $user_roles = array('user');
        $this->assertFalse($security->checkPermission($required, $user_roles));
    }
    public function testCheckPermSu() {
        $security = new Ox_SecurityMongoCollection();
        $required = array('admin');
        $user_roles = array('su');
        $this->assertTrue($security->checkPermission($required, $user_roles));
    }

    public function testAuthenticateUserSuccess() {
        $security = new Ox_Security();
        $user = array(
            '_id' => new MongoId('4ef03b9256610cf6050649b4'),
            'username' => 'devsu',
            'password' => 'b8423bb75536db36c065e4ec805d48c6d2db3b1abc5b1d',
        );
        $password = $security->hashAndSaltString('devsu');
        $user['password'] = $password;

         
        $security = new Ox_SecurityMongoCollection();
        $this->assertTrue($security->authenticateUser($user, 'devsu'));
        
    }
    public function testAuthenticateUserFailure() {
        $user = array(
            '_id' => new MongoId('4ef03b9256610cf6050649b4'),
            'username' => 'devsu',
            'password' => '38f176a7518768fc02a395741a5e65bd9df78b1db84763',
        );
         
        $security = new Ox_SecurityMongoCollection();
        $this->assertFalse($security->authenticateUser($user, 'admin'));
    }
    
    public function testIsPublicPass() {
        $security = new Ox_SecurityMongoCollection();
        $required = array('anonymous');
        $user_roles = null;
        $this->assertTrue($security->checkPermission($required, $user_roles));
    }
    
    public function testIsPublicFail() {
        $security = new Ox_SecurityMongoCollection();
        $required = array();
        $user_roles = null;
        $this->assertFalse($security->checkPermission($required, $user_roles));
    }
    public function testLoginUser() {
        $security = new Ox_SecurityMongoCollection();
        $security->loginUser(array('_id'=>new MongoId('1111111111')));
        global $session;
        $user_id = $session->get('user_id');

        $this->assertFalse($security->checkPermission($user_id, '1111111111'));
    }
    public function testLogout(){
        global $session;
        $session->start();
        $security = new Ox_SecurityMongoCollection();
        $security->logout();

        $this->assertFalse($session->started);

    }

    /*
    public function testIsPublicWildcardOnly() {
        $security = new Security();
        $public_methods = array('*');
        $method = 'index';
        $this->assertTrue($security->isPublic($public_methods, $method));
    }   
    public function testIsPublicWildcard() {
        $security = new Security();
        $public_methods = array('index','*','add');
        $method = 'run';
        $this->assertTrue($security->isPublic($public_methods, $method));
    }
    */
    public function testLoggedInTrue() {
        global $session;
        $security = new Ox_SecurityMongoCollection();
        $session->set('user_id', '1111111111');
        $this->assertEquals($security->loggedIn(),'1111111111');
    }
    public function testLoggedInFalse() {
        $security = new Ox_SecurityMongoCollection();
        $this->assertEquals($security->loggedIn(),null);
    }

    public function testSecureResourceAnonymous() {
        $security = new Ox_SecurityMongoCollection();
        $required = array('anonymous');
        $user = array('roles'=>array());
        $this->assertTrue($security->secureResource('/',$required,'index', $user));
    }
    public function testSecureResourceAnonymousSU() {
        $security = new Ox_SecurityMongoCollection();
        $required = array('anonymous');
        $user = array('roles'=>array('su'));
        $this->assertTrue($security->secureResource('/',$required,'index', $user));
    }
    public function testSecureResourceAdminSULoggedIn() {
        $security = new Ox_Security();

        //mark as logged in
        $session = Ox_LibraryLoader::Session();
        $session->set('user_id', '1111111111');

        $required = array('admin');
        $user = array('roles'=>array('su'));
        print "call just before\n";
        $this->assertTrue($security->secureResource('/',$required,'index', $user));
    }
    public function testSecureResourceAdminUserLoggedIn() {
        $security = new Ox_SecurityMongoCollection();
        global $session;
        //mark as logged in
        $session->set('user_id', '1111111111');
        $required = array('admin');
        $user = array('roles'=>array('user'));
        $this->assertFalse($security->secureResource('/',$required,'index', $user));
    }
    private function _containedInArrayValue($needle,$haystack) {
        foreach ($haystack as $value) {
            if (strpos($value,$needle)!==false) {
                return true; //FOUND!!!
            }
        }
        return false;
    }
    public function testSecureResourceAdminSULoggedOut() {
        //ob_start();
        $security = new Ox_SecurityMongoCollection();
        $required = array('admin');
        $user = array('roles'=>array('su'));
        //$this->expectOutputString('<title>Ox Framework</title>');
        $security->secureResource('/',$required,'index', $user);

        //test what headers are set.
        $headers_list = xdebug_get_headers();
        $this->assertTrue($this->_containedInArrayValue('401',$headers_list));
    }

    /*
    public function testLogout() {
        
    }
    */
}  
?>