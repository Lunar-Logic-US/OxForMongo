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
 *    Security class to control access to resources.
 *
 *    This class also serves as a user manager. This class interacts with
 *    'LBFSession' class to achieve user management functions.
 *
 *    EXAMPLE LOGIN CONSTRUCT
 *    $security = Ox_LibraryLoader::getResource('security');
 *    $security->login($_POST['username'],$_POST['password']);
 *    ------------------------------------------------------------
 *    EXAMPLE LOGIN CONSTRUCT (With additional authentication criteria).
 *    $security = Ox_LibraryLoader::getResource('security');
 *    $security->setAuthenicationCriteria('subdomain',$subdomain);
 *    $security->login($_POST['username'],$_POST['password']);
 */

require_once(DIR_FRAMELIB . 'Ox_Security.php');

class Ox_SecurityMongoCollection extends Ox_Security
{
    /*
    public function __construct() {
        global $user;
        $user_id=$this->loggedIn();
        if($user_id) {
            $user = $this->getUser(array('_id'=>new MongoId($user_id)));
            if (self::DEBUG) {
                Ox_Logger::logDebug('User:' . print_r($user,true));
            }
        }
    }
    */
    
    /**
     * Returns a user by given filter and collection.
     *
     * @param   string    $filter       The where criteria for user selection.
     * @param   string    $collection   The collection where the user is stored.
     */
    public function getUser($filter, $collection='users')
    {
        global $db;
        $users = $db->getCollection($collection);
        return $users->findOne($filter);
    }
    
    /*
    public function authenticateUser($user, $password)
    {
        $salt = substr($user['password'], 0, 6);
        if($salt . sha1($salt . $password) == $user['password']) {
            return true;
        }
        return false;
        
    }
    */
}
