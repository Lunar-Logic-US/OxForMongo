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

/**
 * This is the abstract class for user using the system.
 *
 * This allows user information to be stored in various ways
 * and the security object would still be able to work. Please note: There are several
 * abstract methods which you must implement should you wish to override the default Ox_UserMongo class.
 * Before overriding the Ox_UserMongo, you may want to review the features to see if the additional
 * features you need can't be provided using the existing default classes.
 * @package Ox_Security
 */
abstract class Ox_User {
    /**
     * Turns on object debug logging.
     */
    const DEBUG = FALSE;

    /**
     * Both the filter for the load and then the loaded user.
     * @var array
     */
    protected $user = array();

    /**
     * The default user collection.
     * @var string
     */
    protected $userCollection = 'users';

    /**
     * Loads a user using the session.
     *
     * The users is loaded from the DB into the user property, if there is a good value in $_SESSION['user_id'].
     * This is called as part of the start of Ox and is call from Ox_Security->__construct.
     *
     * @see Ox_Security::__construct
     */
    abstract public function __construct();

    /**
     * Load a user.
     *
     * The user is loaded from the dababase into the user property from the settings in the user property.
     */
    abstract public function load();

    /**
     * Returns an array of the user's roles.
     *
     * @return string[]
     */
    abstract public function getRoles();

    /**
     * Returns the user's saved password.
     *
     * It is presumably salted and hashed.
     *
     * @return string
     */
    abstract public function getPassword();


    /**
     * Returns the user's id as a string.
     *
     * It must be suitable to put in the session var.  This id is what the
     * constructor will need to load the current user.
     *
     * @return string
     */
    abstract public function getIdString();

    /**
     * Set the properties on the loaded user.
     *
     * @param string $varName
     * @param mixed $value
     */
    public function set($varName,$value)
    {
        if (!isset($this->user)) {
            $this->user = array();
        }
        $this->user[$varName] = $value;
    }
    
    /**
     * Returns properties from the loaded user.
     *
     * @param string $varName
     * @return mixed
     */
    public function get($varName)
    {
        if (array_key_exists($varName, $this->user)) {
            return $this->user[$varName];
        } else {
            return null;
        }
    }

    /**
     * Returns the user doc that came from the database.
     *
     * @return array
     */
    public function getUserDoc(){
        return $this->user;
    }
}
