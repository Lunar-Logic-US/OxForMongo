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
 * @package Ox_Boot
 */

/**
 * Helper functions to be used with Ox widgets
 */
class WidgetHelper
{

    /**
     * Append an md5 hash to the end of a file in webroot.
     *
     * @param string $path Path of the file relative to webroot
     * @return string $path Path of the file relative to webroot with its md5 hash appended
     * @internal string $full_system_path Path of the file from the root of the application
     * @internal string $md5 Md5 hash of the file
     */
    public static function addCacheBuster($path)
    {
        $full_system_path = DIR_APP . 'webroot' . $path;
        try {
            $path = ltrim($path, '/');
            $md5 = md5_file($full_system_path);
            if (!$md5) {
                throw new Exception('Could not calculate the md5 hash for ' . $full_system_path);
            }
            return sprintf('/%s?v=%s', $path, $md5);
        } catch(Exception $e) {
            Ox_Logger::logError(__CLASS__ . '-' . __FUNCTION__ . ': ' . $e->getMessage());
            return $path;
        }
    }

}
