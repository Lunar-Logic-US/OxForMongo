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
     *
     * Example Hook:
        "hooks"{
            "menu":[
                {
                    "file":"/home/jesse/web/ivy/cart/hook.php",
                    "class":"cartHook",
                    "function":"scripts",
                },
                {
                    "file":"/home/jesse/web/ivy/cart/hook.php",
                    "class":"ordersHook",
                    "function":"scripts",
                }
            ],
            "orders":[
                {
                    "file":"/home/jesse/web/ivy/cart/Orders/hook.php",
                    "class":"ordersHook",
                    "function":"scripts",
                }
            ],
            "home":[
                {
                    "file":"/home/jesse/web/ivy/site/hook.php",
                    "class":"siteHook",
                    "function":"sidebar",
                }
            ]
        }
     * 
     * @param $name - The name of the hook, to be invoked in the execute function.
     * @param $construct - The plugin where this hook is implemented
     * @param $function - The function to call in the above mentioned construct
     * @param bool $replace - Add an additional hook if false or replace the existing hook(s) if true.
     */
    public static function register($name, $file, $class, $function, $replace=false)
    {
        $update = array('$addToSet'=>array(
            'hooks.'.$name=>array("file"=>$file, "class"=>$class, "function"=>$function)
        ));
        if($replace){
            $update = array('$set'=>array(
                'hooks.'.$name=>array(array("file"=>$file, "class"=>$class, "function"=>$function))
            ));
        }
        $db = Ox_LibraryLoader::db();
        if(!empty($update)){
            $db->settings->update(
                array(),
                $update
            );
        }
        
    }

    /**
     * Execute a registered a hook to be used.
     *
     * $name - The name of the hook to invoke.
     */
    public static function execute($name, $arguments=array())
    {
        $db = Ox_LibraryLoader::db();
        $settings = $db->settings->findOne();
        $output='';
        if(isset($settings['hooks'][$name]) && is_array($settings['hooks'][$name])){
            foreach($settings['hooks'][$name] as $hook){
                if(file_exists($hook['file'])){
                    require_once($hook['file']);
                    if(class_exists($hook['class'])){
                        call_user_func_array(array($hook['class'],$hook['function']),$arguments);
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
