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
 * @package Ox_Configuration
 */

/**
 * Interface for the system configuration parser.
 *
 * @package Ox_Configuration
 */
interface Ox_ConfigParser
{
    /**
     * Returns the config value from app.php
     *
     * This gets a configuration value from the apps.php in the applications configuration directory.
     * @param string $key
     * @return mixed
     */
    public function getAppConfigValue($key);
    /**
     * Returns the config value from frameword.php
     *
     * @param string $key
     * @return mixed
     * @depreciated
     */
    public function getFrameworkConfigValue($key);
}