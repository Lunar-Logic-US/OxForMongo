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

require_once(DIR_FRAMELIB . 'Ox_Security.php');

/**
 * Mongo Security class
 *
 * @package Ox_Security
 */
class Ox_SecurityMongoCollection extends Ox_Security
{
    /**
     * Returns a user by given filter and collection.
     *
     * @param   string    $filter       The where criteria for user selection.
     * @param   string    $collection   The collection where the user is stored.
     */
    public function getUser($filter, $collection='users')
    {
        $db= Ox_LibraryLoader::Db();        
        $users = $db->getCollection($collection);
        
        return $users->findOne($filter);
    }
    
}
