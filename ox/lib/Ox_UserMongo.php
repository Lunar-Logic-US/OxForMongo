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
 * This is the user class for user using the system.  This is a default MongoDB user setup for Ox
 *
 * Inherits magic get() and set() functions from the abstract class Ox_User. The Ox_User
 * has an array of properties that are intended to represent the user object stored in MongoDB.
 * The flexibility provided by maintaining a property array as opposed to classical members increases
 * the potential reuse of this class...
 *
 * Of course you can override this class should you have extensively complex requirements for
 * your authenticated user. If, however, you simply want to add something to make your security
 * more robust it's simple to add a property to the user using the security object from your
 * application instance of Ox.
 *
 * @package Ox_Security
 */
class Ox_UserMongo extends Ox_User
{
    /**
     * Loads a user using the session.
     *
     * The users is loaded from the DB into the user property, if there is a good value in $_SESSION['user_id'].
     * This is called as part of the start of Ox and is call from Ox_Security->__construct.
     *
     * @see Ox_Security::__construct
     */
    public function __construct() {
        if (self::DEBUG) Ox_Logger::logMessage("User::__construct Starting");

        $config = Ox_LibraryLoader::getResource('config_parser');
        $session = Ox_LibraryLoader::getResource('session');
        $collection = $config->getAppConfigValue('user_collection');
        if ($collection) {
            $this->userCollection = $collection;
        }

        $user_id = $session->get('user_id');
        if ($user_id) {
            $db = Ox_LibraryLoader::getResource('db');
            $collection = $this->userCollection;
            $this->user = $db->$collection->findOne(array('_id'=> new MongoId($user_id)));
        }
    }

    /**
     * Load a user.
     *
     * The user is loaded from the database into the user property from the settings in the user property.
     * NOTE: All properties will be used int he where clause to pull the user.
     *
     * make sure $this->user is not empty or null or you will
     * get the first from all users!!
     */
    public function load() {
        if (self::DEBUG) Ox_Logger::logMessage("User::load");
        $db = Ox_LibraryLoader::getResource('db');
        if (isset($this->user) && !empty($this->user)) {

            // case insensitive user match
            $user = $this->user['email'];
            $filter = array('email' => array('$regex' => new MongoRegex("/^$user/i")));
            $this->user = $db->$collection->findOne($filter);
        }
        if (isset($this->user) && !empty($this->user)) {
	        return true;
	    }
	    return false;
    }

	/**
	 * Unload a user
	 */
	public function unload() {
		$this->user = array();
	}

    /**
     * Returns an array of the user's roles.
     *
     * @return string[]
     */
    public function getRoles() {
        return $this->get('roles');
    }

    /**
     * Returns the user's saved password.
     *
     * It is presumably salted and hashed.
     *
     * @return string
     */
    public function getPassword() {
        return $this->get('password');
    }

    /**
     * Returns the MongoId object for this user.
     */
    public function getId() {
        return $this->get('_id');
    }

    /**
     * Returns the user's id as a string.
     *
     * It must be suitable to put in the session var.  This id is what the
     * constructor will need to load the current user.
     *
     * @return string
     */
    public function getIdString() {
        return $this->get('_id')->__toString();
    }
}
