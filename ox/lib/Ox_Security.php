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
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Security
 */


require_once(DIR_FRAMELIB.'Ox_User.php');

/**
 * Security class to control access to resources.
 *
 * This class also serves as a user manager. This class interacts with
 * Ox_Session and Ox_User classes to achieve user management functions.
 * <br><br>
 * This object uses the User system resource for all of it checks against the user in the session. For most
 * authenication systems, you only need to setup a new User object.  This can be done using the class override system
 * in the Ox_LibraryLoader
 * <br><br>
 * This object expect roles by be a array of roles name like:
 * <pre><code>
 *    $security = Ox_LibraryLoader::Security();
 *    $neededRoles = array('su','user','admin');
 *    $security->checkPermission($neededRoles); //Checks the current user for the supplied roles.
 * </code></pre>
 * <br><br>
 * <em>EXAMPLE LOGIN CONSTRUCT:</em><br>
 * <pre><code>
 *    $security = Ox_LibraryLoader::getResource('security');
 *    $security->login($_POST['username'],$_POST['password']);
 * </code></pre>
 *
 * <em>EXAMPLE LOGIN CONSTRUCT (With additional authentication criteria):</em><br>
 * <pre><code>
 *    $security = Ox_LibraryLoader::getResource('security');
 *    $security->setAuthenticationCriteria('subDomain',$subDomain);
 *    $security->login($_POST['username'],$_POST['password']);
 * </code></pre>
 *
 * @package Ox_Security
 */
class Ox_Security
{
    /** Enable/Disable Debugging for this object. */
    const DEBUG = FALSE;
    
    /** User object for the current session. */
    protected $user;
    
    /** The session */
    protected $session;

    /**
     * Load the system user object.
     *
     * This initalizes the sytem
     */
    function __construct(){
        Ox_LibraryLoader::load('user','Ox_UserMongo',false);
        $this->user =  Ox_LibraryLoader::User();
        $this->session = Ox_LibraryLoader::Session();
    }

    /**
     * Check if a given password is equivalent from a hashed password.
     * 
     * @param $passwordFromDatabase
     * @param $password
     * 
     * @return boolean
     */
    public function authenticateUser($passwordFromDatabase,$password)
    {
        if (is_array($passwordFromDatabase) ) {
            //backward compatibility
            $passwordFromDatabase = $passwordFromDatabase['password'];
        }
        $salt = substr($passwordFromDatabase, 0, 6);
        if($salt . sha1($salt . $password) == $passwordFromDatabase) {
            return true;
        }
        return false;
    }

    /**
     * Logs the user into the system and redirects them to the page they tried to navigate to originally by
     * default.
     *
     * Logging in is made up of several steps:
     * 1. Set the username and password properties on the user object.
     *    Additional criteria can be added using $security->setAuthenticationCriteria().
     * 2. Load the user from the database.
     * 3. When user found in DB, log the user in.
     * 4. Redirect.
     *
     * @param $username
     * @param $password
     * @param bool $redirect
     * @return bool
     */
    public function login($username, $password, $redirect=true)
    {
        $name_field = Ox_LibraryLoader::config_parser()->getAppConfigValue('security_username_field');
        if(strlen($name_field)) {
            $this->user->set($name_field,$username);
        } else {
            $this->user->set('username',$username);
        }

        if ($this->user->load()) {
            if($this->authenticateUser($this->user->getPassword(),$password)){
                $this->loginUser();
                if ($redirect) {
                    if(isset($_GET['r'])) {
                        $redirect = urldecode($_GET['r']);
                        if (self::DEBUG) Ox_Logger::logDebug('Ox_Security::login - Redirect: login -> ' . $redirect);
                        Ox_Router::redirect($redirect);
                    }
                }
                return true;
            }
            
        }
        $this->user->unload();
        return false;
    }

    /**
     * Gets an array of Roles that are valid for this user.
     *
     * @return array
     */
    public function getUserRoles()
    {
        if ($this->user->getRoles())  {
            return $this->user->getRoles();
        }
        return array();
    }

    /**
     * Adds to the user filter that will be used as criteria in the Ox_User->load() function.
     *
     * @param $varName
     * @param $value
     */
    public function setAuthenticationCriteria($varName,$value)
    {
        $this->user->set($varName,$value);
    }

    /**
     * Tests a user has permission based on the given role list.  Takes care of the anonymous and su cases.
     *
     * @param $needed_roles
     * @param null $user_roles
     * @return bool
     */
    public function checkPermission($needed_roles, $user_roles=null)
    {
        if(!is_array($needed_roles)) {
            $needed_roles =  array($needed_roles);
        }
        if ($user_roles===null || !is_array($user_roles)){
            $user_roles = $this->getUserRoles();
        }
        // Corner case
        if($this->isPublic($needed_roles)) {
            return true;
        }

        // Corner case
        if(in_array('su', $user_roles)) {
            return true;
        }

        // General case
        $intersect = array_intersect($user_roles, $needed_roles);
        if(!empty($intersect)) {
            return true;
        }

        return false;
    }

    /**
     * Logs in a user to the system by setting the user_id in the session.
     *
     * @param null $user
     */
    public function loginUser($user=null)
    {
        $this->session->start();
        //$this->session->set('user_id', $this->user->getIdString());
        if ($user===null) {
            //@TODO: Make sure this doesn't break backwards compatibility otherwise change implementation to use string.
            //$session->set('user_id', $user['_id']->__toString());
            $this->session->set('user_id', $this->user->getIdString());
        } else {
            $this->session->set('user_id', $user['_id']->__toString());
        }
        if (self::DEBUG) Ox_Logger::logMessage("Ox_Security::loginUser - adding to session " . $this->user->getIdString());
        if (self::DEBUG) Ox_Logger::logMessage("Ox_Security::loginUser - Session " . print_r($_SESSION,1));
    }

    /**
     * Tests if a the current user is logged into the system.
     * @return mixed
     */
    public function loggedIn()
    {
        $user_id = $this->session->get('user_id');
        if (self::DEBUG) Ox_Logger::logDebug('Ox_Security::loggedIn - Logging in:' .$user_id);
        //if (self::DEBUG) Ox_Logger::logDebug('Ox_Security::loggedIn - Call Trace:' . print_r(debug_backtrace(),1));
        return $user_id;
    }

    /**
     * Logs out the current user
     */
    public function logout()
    {
        $user_id = $this->session->get('user_id');
        if (self::DEBUG) Ox_Logger::logMessage("Ox_Security::logout - Logged user $user_id out");
        $this->session->stop();
    }

    /**
     * Creates a hashed string that can be saved as the password for a user.
     *
     * @param $plainString
     * @return string
     */
    public static function hashAndSaltString($plainString)
    {
        $salt = substr(sha1(uniqid(rand(), true)), 0, 6);
        $hashed = $salt . sha1($salt . $plainString);
        return $hashed;
    }

    /**
     * Test if the current resource is public.
     *
     * @param $needed_roles
     * @return bool
     */
    public function isPublic($needed_roles)
    {
        // Corner case
        if(is_array($needed_roles)) {
            if(in_array('anonymous', $needed_roles)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if the user has access to the resource and sets a redirect string.
     *
     * @param string $path
     * @param array  $required_roles
     * @param string $method
     * @param array  $user
     * @return bool
     */
    public function secureResource($path, $required_roles, $method,$user=null)
    {
        if ($user === null) {
            $roles = $this->getUserRoles();
        } else {
            $roles = $user['roles'];
        }
        // See if this method requires login
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_Security::secureResource - Path : $path  ");
            Ox_Logger::logDebug("Ox_Security::secureResource - Required Roles: " . print_r($required_roles,true));
            Ox_Logger::logDebug("Ox_Security::secureResource - Method: $method");
            Ox_Logger::logDebug("Ox_Security::secureResource - User : " . print_r($this->user,true));
        }
        if($this->isPublic($required_roles)) {
            if (self::DEBUG) Ox_Logger::logMessage('Ox_Security::secureResource -  found public resource. ');
            return true;
        } else if(!$this->isPublic($required_roles) && !$this->loggedIn()) {
            // We don't want to deep link people to immediately logout
            if($method == 'logout') {
                if (self::DEBUG) Ox_Logger::logDebug('Ox_Security::secureResource - Redirect logout: ' . $path . ' -> /');
                Ox_Router::redirect('/');
            } else {
                $url = '/users/login';
                $config_parser = Ox_LibraryLoader::Config_Parser();
                if (isset($config_parser)) {
                    $login_url = $config_parser->getAppConfigValue('login_url');
                    if ($login_url) {
                        $url = $login_url;
                    }
                }
                $fromUrl = array('r'=>Ox_Router::buildURL($path, $_GET));
                if (self::DEBUG) Ox_Logger::logMessage('Ox_Security::secureResource -  Not Public/Not Logged in Redirect: ' . $path . ' -> ' . print_r($fromUrl,1));
                Ox_Router::redirect($url, $fromUrl,array("HTML/1.1 401 Authorization Required"));
            }
        } else if($this->checkPermission($required_roles,$roles)) {
            if (self::DEBUG) Ox_Logger::logMessage('Ox_Security - secureResource Redirect:tested permissions');
            return true;
        }
        return false;
    }

    /**
     * Provide some debugging information if you are working on the security or session objects
     */
    public function debug()
    {
        $session = Ox_LibraryLoader::getResource('session');
        if($user_id = $this->loggedIn()) {
            print '<br />logged in: '.$user_id.'<br />';
            $roles = $this->getUserRoles();
            print 'roles: ' . implode(', ',$roles);
            print 'session time remaining: <span id="session_time">' . $session->getSessionTimeRemaining() . '</span>';
        } else {
            print '<br />logged in: NO<br />';
        }
        if($_GET['DEBUG']) {
            print $session->debug();
        }
    }

}
