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
 * @package Ox_Hook
 */

/**
 * Manages the hooks for Ox.
 *
 * This is hook system that allows you to inject code into an existing code base.  This is not
 * meant for Ox but those applications built on Ox, For example e-commerce, shipping, payment systems, etc.
 * @package Ox_Hook
 */
class Ox_Hook
{
    /** Enable/Disable Debugging for this object. */
    const DEBUG = FALSE;

    /** Name of the setting ID for the settings collection. */
    const SETTING_ID = 'Ox_Hook';

    /** @var null|Ox_Hook Singleton instance */
    private static $_instance = NULL;

    /** @var array List of the hooks that have been registered. */
    private static $_hook_list = array();

    /**
     * Instantiate the singleton.
     *
     * @return null|Ox_Hook
     */
    public static function getInstance()
    {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Runs the init for a for "module" construct.
     *
     * This looks for the given construct, then the file inside that construct called hook.  Inside hook, there
     * should be an object called <construct>Hook which has a static init function.  That is the method that will
     * be called by this method.
     *
     * This is intended to give an easy way to spin up modules in the modules.php configuration file. For example:
     * <code>
     * Ox_Hook::initializeModuleConstruct('cms');
     * Ox_Hook::initializeModuleConstruct('cart');
     * </code>
     * @param string $construct
     */
    public function initializeModuleConstruct($construct)
    {
        require_once(DIR_CONSTRUCT. $construct . DIRECTORY_SEPARATOR . 'hook.php');
        $hookObjectName = $construct . 'Hook';
        $hookObjectName::init();
    }

    /**
     * Register a function on a hook.  Hooks are registered on Ox execution.
     *
     * @param string $name - The name of the hook, to be invoked in the execute function.
     * @param string $file - The physical address of the file that includes the hook class and method
     * @param string $class - The name of the class that has the hook method
     * @param string $method - The method to call in given class
     */
    public static function register($name, $file, $class, $method)
    {
        if(self::DEBUG) {
            Ox_Logger::logDebug(__CLASS__ .'-'. __FUNCTION__ .": $name - file: {$file} | class: {$class} | function: $method.");
        }
        if(!isset(self::$_hook_list[$name])) {
            self::$_hook_list[$name] = array();
        }
        self::$_hook_list[$name][] = array(
            "file" => $file,
            "class" => $class,
            "function" => $method
        );
    }

    /**
     * Execute a registered a hook to be used.
     *
     * @param string $name - The name of the hook to invoke.
     * @param array $arguments
     * @param boolean $multipleReturns If true gathers the return values from each hook and returns them as an unkeyed array.  If false returns the value from the lasst hook called.  Defaults to false.
     * @return mixed Returns whatever the called method gives us, either as an array if $multipleReturns, or a single value from the last hook called.  Returns null if no hooks were registered or if no return values were ever created.
     */
    public static function execute($name, $arguments = array(), $multipleReturns = false)
    {
        if(self::DEBUG) {
            Ox_Logger::logDebug(__CLASS__ . '-' .  __FUNCTION__ . ": Executing Hook: " . $name);
        }
        $output = array();

        if(!count(self::$_hook_list)) {
            if(self::DEBUG) {
                Ox_Logger::logDebug('Ox_Hook: Returning null due to empty self::$_hook_list');
            }
            return null;
        }
        if(!array_key_exists($name, self::$_hook_list)) {
            if(self::DEBUG) {
                Ox_Logger::logDebug('Ox_Hook: Returning null, can\'t find hook "' . $name . '" in self::$_hook_list');
            }
            return null;
        }

        foreach(self::$_hook_list[$name] as $hook) {
            if(file_exists($hook['file'])) {
                require_once($hook['file']);
                if(class_exists($hook['class'])) {
                    if(self::DEBUG) {
                        Ox_Logger::logDebug("Ox_Hook: Firing hook $name - file: {$hook['file']} | class: {$hook['function']} | name: $name.");
                    }
                    $output[] = call_user_func_array(array($hook['class'], $hook['function']), array($arguments));
                } else {
                    Ox_Logger::logError("Hook class ({$hook['class']}) not found for $name.");
                }
            } else {
                Ox_Logger::logError("Hook file ({$hook['file']}) not found for $name.");
            }
        }

        if($multipleReturns) {
            return $output;
        }

        return array_pop($output);
    }
}
