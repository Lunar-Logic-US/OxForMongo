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
 * NOT COMPLETE - To allow for arbitrary hooks in code that can be use in plugins.
 *
 * This is intended for build plugin system that will need to hook into an existing code base.  This is not
 * meant for Ox but those applications built on Ox, For example e-commerce, shipping, payment systems, etc.
 *
 */
class Ox_Hook
{
    /**
     * Singleton instance
     *
     * @var null
     */
    private static $_instance = NULL;

    /**
     * List of the hooks that have been registered.
     *
     * @var array
     */
    private $_hook_list = array();

    /**
     * Instantiate the singleton.
     *
     * @return null|Ox_Hook
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Register a function on a hook.
     * @param $hook_area
     * @param $action
     * @param $function_to_use
     * @param bool $replace
     */
    public function register_to_execute($hook_area,$action,$function_to_use,$replace=FALSE)
    {

    }

    /**
     * Register a hook to be used.
     */
    public function execute()
    {

    }
}
