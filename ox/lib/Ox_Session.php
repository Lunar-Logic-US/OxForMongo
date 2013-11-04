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
 * A thin wrapper around PHP's sessions.
 *
 * This class handles session expiration after a time period of inactivity
 * specified per user role in the config app config file. If no limit is
 * specified, the default timeout time is set to 15 minutes.
 */

require_once(DIR_FRAMELIB . 'session_managers/Ox_MongoSessionManager.php');

class Ox_Session
{
    const DEBUG = FALSE;

    /**
     * MongoDB session
     */
    private $_mongo_session = FALSE;
    
    /**
     * The instance of the session
     */
    private static $_instance = null;
    
    /**
     * Flag stipulates MongoDB or not
     */
    private $_uses_mongo = FALSE;
    
    /**
     * Flag to allow/ignore time outs.
     */
    private $_user_time_outs = FALSE;
    
    /**
     * Sets the session timeout (in seconds).
     */
    private $_max_timeout = 900;
    
    /**
     * Singleton instantiation of the session.
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Private constructor for Singleton pattern.
     */
    private function __construct()
    {
        $config_parser = Ox_LibraryLoader::getResource('config_parser');

        if (self::DEBUG) Ox_Logger::logDebug('Session: Setup Start----------------');

        $session_config=$config_parser->getAppConfigValue('session');
        $this->_user_time_outs=$config_parser->getAppConfigValue('timeouts');

        if (empty($session_config) || !isset($session_config['mongo_enabled']) || $session_config['mongo_enabled']===FALSE) {
            if (self::DEBUG) Ox_Logger::logDebug('Session: PHP')
            ;
            $this->_uses_mongo = FALSE;
            ini_set('session.use_cookies',              1);
            ini_set('session.cookie_httponly',          1);

            $runSet = false;
            $domain = null;
            if (isset($session_config['cookie_domain'])) {
                $domain=$session_config['cookie_domain'];
                $runSet = true;
            }

            $path = '/';
            if (isset($session_config['cookie_path'])) {
                $path=$session_config['cookie_path'];
                $runSet = true;
            }

            $lifetime = 3600;
            if (isset($session_config['lifetime'])) {
                $path=$this->_config['cookie_path'];
                $runSet = true;
            }

            //Only run if there is a change.  otherwise just use system defaults.
            if ($runSet) {
                session_set_cookie_params(
                    $lifetime,
                    $path,
                    $domain
                );
            }
            if (isset($session_config['session_name'])) {
                session_name($session_config['session_name']);
            }


            session_start();
        }  else {
            $mongo_config=$config_parser->getAppConfigValue('mongo_config');
            if (self::DEBUG) Ox_Logger::logDebug('Session: MONGO');
                $this->_uses_mongo = TRUE;
                $calculated_config = array(
                // session related vars
                //This is database lifetime.  We track the cookie timeout in get time
                    // we can not set it here because we can not tell what role the user is
                    // until much later in the boot process.
                'lifetime'      => $this->_max_timeout,        // session lifetime in seconds
                'database'      => $mongo_config['database'],   // name of MongoDB database
                'collection'    => $session_config['mongo_collection'],   // name of MongoDB collection

                // persistent related vars
                'persistent' 	=> false, 			// persistent connection to DB?
                'persistentId' 	=> 'MongoSession', 	// name of persistent connection

                // whether we're supporting replicaSet
                'replicaSet'		=> false,

                // array of mongo db servers
                'servers'   	=> array(
                    array(
                        'host'          => $mongo_config['host'],
                        'port'          => $mongo_config['port'],
                        'username'      => $mongo_config['login'],
                        'password'      => $mongo_config['password'],
                    )
                )
            );
            if (isset($session_config['cookie_domain'])) {
                $calculated_config['cookie_domain']=$session_config['cookie_domain'];
            }
            if (isset($session_config['cookie_path'])) {
                $calculated_config['cookie_path']=$session_config['cookie_path'];
            }
            $this->_mongo_session = new Ox_MongoSessionManager($calculated_config);
        }
        if (self::DEBUG) Ox_Logger::logDebug('Session Setup END----------------');
    }

    /**
     * Debugging function.
     */
    public function debug()
    {
        $debug = <<<DEBUG
<script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        updateTimeout();
    });

    function updateTimeout() {
        $.ajax({
            url:'http://'+window.location.hostname+'/ajax/session_timeout?STATUS=1',
            success: function(data) {
                obj = $.parseJSON(data)
                $('#session_time').text(obj.rsp);
                setTimeout(updateTimeout, 1000);
            },
            error: function(error) {
                alert(error);
            }
        });
    }
</script>

DEBUG;
        return $debug;
    }

    /**
     * Starts the session based on predefined initialized status.
     */
    public function start()
    {
        if ($this->_uses_mongo) {
            if (self::DEBUG) Ox_Logger::logMessage('Doing Mongo regenerate id');
            $this->_mongo_session->regenerate_id();
        } else {
            if (self::DEBUG) Ox_Logger::logMessage('Doing PHP regenerate id');
            session_regenerate_id();
        }
        $_SESSION['last_request_time'] = time();
    }

    /**
     * Stops the session.
     */
    public function stop()
    {
        $_SESSION = array(); //destroy all of the session variables
        if ($this->_uses_mongo) {
            $this->_mongo_session->stop();
        } else {
            session_destroy();
        }
    }

    /**
     * Setter for session vars.
     */
    function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Getter for session vars.
     *
     * @param string $key
     * @return mixed
     */
    function get($key)
    {
        if (self::DEBUG) Ox_Logger::logDebug('SESSION - sessino state :' . print_r($_SESSION,1));
        if (self::DEBUG) Ox_Logger::logDebug('SESSION - get:' . $key);
        if(isset($_SESSION[$key])) {
            if (self::DEBUG) Ox_Logger::logDebug('SESSION - value:' . $_SESSION[$key]);
            return $_SESSION[$key];
        }
        return null;
    }

    /**
     * Check to see if the session has expired.
     *
     * @return boolean
     */
    public function isExpired()
    {
        return $this->getSessionTimeRemaining() <= 0;
    }

    /**
     * Get the time left on the current session.
     *
     * @return int
     */
    public function getSessionTimeRemaining()
    {
        $security = Ox_LibraryLoader::getResource('security');
        $default_timeout = 900;
        $time_left = 0;
        if (self::DEBUG) Ox_Logger::logDebug('SESSION - getSessionTimeRemaining user roles: '.print_r($security->getUserRoles(),true));
        if($security->loggedIn() && isset($_SESSION['last_request_time'])) {
            // If a user has multiple roles, simply take the one with the longest timeout.
            $roles = $security->getUserRoles();
            if(in_array('su', $roles)) {
                // check for the presence of an app-defined SU timeout.  If not, set to 8 hours.
                if(in_array('su', $this->_user_time_outs)) {
                    $default_timeout = $this->_user_time_outs['su'];
                } else {
                    $default_timeout = 60 * 60 * 8;
                }
            } else {
                foreach($roles as $role) {
                    if(isset($this->_user_time_outs[$role]) && $this->_user_time_outs[$role] > $default_timeout) {
                        $default_timeout = $this->_user_time_outs[$role];
                    }
                }
            }
            $time_left = $default_timeout - (time() - $_SESSION['last_request_time']);
        }
        if (self::DEBUG) Ox_Logger::logDebug('SESSION - getSessionTimeRemaining : Session Time left: ' . $time_left);
        return $time_left;

    }

    /**
     * Refresh the current session (resetting the time remaining)
     */
    public function update()
    {
        $security = Ox_LibraryLoader::getResource('security');
        if(isset($_GET['STATUS'])) {
            return;
        }
        if($security->loggedIn()) {
            
            if($this->isExpired()) {
                if (self::DEBUG) Ox_Logger::logDebug('SESSION - update : Session expired');
                $this->stop();
            } else {
                if (self::DEBUG) Ox_Logger::logDebug('SESSION - update : Updated Last Request Time');
                $_SESSION['last_request_time'] = time();
            }
        }
    }

    /**
     * Return the current session if it has been started.
     */
    public static function getSessionId()
    {
        if (is_null(self::$_instance)) {
            return null;
        }
        return session_id();
    }
}