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
    /**
     * Enable/Disable Debugging for this object.
     */
    const DEBUG = FALSE;
    /**
     * Name of the setting ID for the settings collection.
     */
    const SETTING_ID = 'Ox_Hook';

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
     * Runs the init for a for "module" construct.
     *
     * This looks for the given construct, then the file inside that construct called hook.  Inside hook, there
     * should be an obejct called <construct>Hook which has a static init function.  That is the method that will
     * be called by this method.
     *
     * This is intended to give an easy way to spin up modules in the modules.php configuration file. For example:
     * <code>
     * Ox_Hook::initializeModuleConstruct('cms');
     * Ox_Hook::initializeModuleConstruct('cart');
     * </code>
     * @param string $construct
     */
    public static function initializeModuleConstruct($construct)
    {
        require_once(DIR_CONSTRUCT. $construct . DIRECTORY_SEPARATOR . 'hook.php');
        $hookObjectName = $construct . 'Hook';
        $hookObjectName::init();
    }


    /**
     * Register a function on a hook.
     *
     * Example hook document:
     * <code>
     *  "hooks"{
     *       "menu":[
     *          {
     *              "file":"/home/jesse/web/ivy/cart/hook.php",
     *              "class":"cartHook",
     *              "function":"scripts",
     *          },
     *          {
     *              "file":"/home/jesse/web/ivy/cart/hook.php",
     *              "class":"ordersHook",
     *              "function":"scripts",
     *          }
     *      ],
     *      "orders":[
     *          {
     *              "file":"/home/jesse/web/ivy/cart/Orders/hook.php",
     *              "class":"ordersHook",
     *              "function":"scripts",
     *          }
     *      ]
     *  }
     * </code>
     *
     * @param $name - The name of the hook, to be invoked in the execute function.
     * @param $file - The physical address of the file that includes the hook class and method
     * @param $class - The name of the class that has the hook method
     * @param $function - The function to call in the above mentioned construct
     * @param bool $replace - Add an additional hook if false or replace the existing hook(s) if true.
     */
    public static function register($name, $file, $class, $function, $replace=false)
    {
        if (self::DEBUG) Ox_Logger::logDebug(__CLASS__ .'-'. __FUNCTION__ .": $name - file: {$file} | class: {$class} | function: $function.");
        $update = array('$addToSet'=>array(
            $name=>array("file"=>$file, "class"=>$class, "function"=>$function)
        ));
        /*
        if ($replace) {
            $update = array('$set'=>array(
                $name=>array(array("file"=>$file, "class"=>$class, "function"=>$function))
            ));
        }
        */
        if (!empty($update)) {
            if (self::DEBUG) Ox_Logger::logDebug(__CLASS__ .'-'. __FUNCTION__ .": Updating DB: " . print_r($update,1));
            $db = Ox_LibraryLoader::Db();
            //$status = $db->settings->insert(array('_id'=>'Ox_Hook',$name=>array()));

            $status = $db->settings->update(
                array('_id'=>self::SETTING_ID),
                $update,
                array('upsert'=>true)
            );

            if (self::DEBUG) Ox_Logger::logDebug(__CLASS__ .'-'. __FUNCTION__ .": Update Status: " . print_r($status,1));
        }
        
    }

    /**
     * Execute a registered a hook to be used.
     *
     * @param string $name - The name of the hook to invoke.
     * @param array $arguments
     * @return mixed
     */
    public static function execute($name, $arguments=array())
    {
        if (self::DEBUG) Ox_Logger::logDebug(__CLASS__ .'-'. __FUNCTION__ .": Executing Hook: " .$name);
        $db = Ox_LibraryLoader::Db();
        $settings = $db->settings->findById(self::SETTING_ID );
        $output='';
        if(isset($settings[$name]) && is_array($settings[$name])){
            foreach($settings[$name] as $hook){
                if(file_exists($hook['file'])){
                    require_once($hook['file']);
                    if(class_exists($hook['class'])){
                        if (self::DEBUG) Ox_Logger::logDebug("Ox_Hook: Firing hook $name - file: {$hook['file']} | class: {$hook['function']} | name: $name.");
                        return call_user_func_array(array($hook['class'],$hook['function']),$arguments);
                    } else {
                        Ox_Logger::logError("Hook class ({$hook['class']}) not found for $name.");
                    }
                } else {
                    Ox_Logger::logError("Hook file ({$hook['file']}) not found for $name.");
                }
            }
        }
    }
}
