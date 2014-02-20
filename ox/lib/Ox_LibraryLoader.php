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
 * @package Ox_CodeLoading
 */

require_once (OX_FRAME_EXCEPTIONS . 'Ox_Exception.php');

/**
 * Code loading system.
 *
 * This class is used to load the various library components of the system.  It can be used dynamically to load parts
 * of the system inside functions for methods. Much of this has been designed to only load code when it is needed.
 * <br><br>
 * There it can also load object that are expected to be used throughout the system.  These object are given a name so
 * that the object can be easily overridden in the application setup. These are generally the user and security objects.
 * <br><br>
 * To load a system object you would use:<br>
 * <pre><code>Ox_LibraryLoader::load('session','Ox_Session');
 * </code></pre>
 * <br><br>
 * That object can then be access anywhere in Ox by doing:<br>
 * <pre><code>$session = Ox_LibraryLoader::Session();
 * // or call the method you need directly
 * Ox_LibraryLoader::Session()->get('some session var');
 * </code></pre>
 * <br><br>
 * This give an application a high degree of flexibility because it can override the session object by creating a new
 * session object with the same interfaces.  So you could replace the standard Session object which uses PHP to a Mongo
 * session object.
 * <br><br>
 * In order perform this you need have created the replacement class in app/lib and the filename would be the same as your
 * class.  Then you would add to your app.php:<br>
 * <pre><code>$class_overload = array (
 *                         varName => YourOverridingClassName,
 *                         'session' => 'TestSession',
 * );
 * </code></pre>
 *
 * @package Ox_CodeLoading
 * @method static Ox_Session Session() Returns session resource.
 * @method static Ox_MongoSource Db() Returns database resource.
 * @method static Ox_WidgetHandler Widget_Handler() Returns widget handler resource
 * @method static Ox_ConfigPHPParser Config_Parser() Returns configuration parser resource
 * @method static Ox_Security Security() Returns security resource
 * @method static Ox_Hook Hook() Returns hook resource
 * @method static Ox_Router Router() Returns router resource
 * @method static LocalAsset Assets_Helper() Returns asset resource
 * @method static Ox_Dispatch Dispatch() Returns dispatch resource
 * @method static Ox_User User() Returns user resource
 */
class Ox_LibraryLoader
{
    /** Variable name to use in app.php. */
    const CONFIG_OVERLOAD_NAME = 'class_overload';
    /** Display debug message or not. */
    const DEBUG = FALSE;


    /** @var array List of system objects. */
    private static $_resources = array();

    /** @var Ox_ConfigPHPParser Pointer to the config parser. */
    private static $_configParser = null;
    
    /** @var array List of overloaded system objects {@See Ox_LibraryLoader::load()}. */
    private static $_overloadList = array();
    
    /** @var array Default search path, note: loads FRAMEWORK FIRST */
    private static $_classPath = array(DIR_FRAMELIB,DIR_APPLIB);


    /**
     * Add a path to the classPath.
     *
     * @static
     * @param string $path Path to add to the general class path.
     */
    public static function addPath($path)
    {
        self::$_classPath[] = $path;
    }

    /**
     * Get the current class path.
     *
     * @static
     * @return array Array of directory paths
     */
    public static function getPath()
    {
        return self::$_classPath;
    }

    /**
     * Clear the config information TESTING ONLY.
     *
     * @static
     */
    public static function clearConfig()
    {
        self::$_configParser = null;
        self::$_overloadList = null;
    }

    /**
     * Is the configParser available.
     *
     * If the configParser is available return true and also load the class overload settings.  This solves
     * the chicken and egg problem between needing the config parser, but it may not be loaded yet.
     *
     * @static
     * @return bool
     * @throws Ox_Exception
     */
    private static function _getParser()
    {
        global $config_parser;
        //we already have everything...
        if (self::$_configParser!= null) {
            return true;
        }
        //can we get it all setup?
        if (isset($config_parser) && $config_parser != null) {
            self::$_configParser = $config_parser;
            self::$_overloadList = self::$_configParser->getAppConfigValue(self::CONFIG_OVERLOAD_NAME);
            if (!empty(self::$_overloadList) && !is_array(self::$_overloadList)) {
                throw new Ox_Exception("Error:  " . self::CONFIG_OVERLOAD_NAME . " is not an array.",'Ox_Loader::BadOverload');
            }
            return true;

        }
        return false;
    }

    /**
     * Loads all of the php code in the path.
     *
     * @param String $path Directory path to load all of the PHP files.
     */
    public static function loadAll($path)
    {
        //is the last character a /, if not add it
        if (substr($path,-1)!='/') {
            $path .= '/';
        }
        foreach (glob($path . '*.php') as $fileName) {
            require_once($fileName);
        }
    }

    /**
     * Load code in page.
     *
     * This acts like an include but you can dynamically set the path to try and load the code.  The code is loaded from
     * the first location it finds. You also have the option have an Ox_Exception thrown if the file can not be found.
     *
     * @param string $name Name of the file to load without ".php"
     * @param array $path Array of directory paths to look for the file
     * @param bool $throw Should we throw an error if the file is not found.
     * @throws Ox_Exception
     */
    public static function loadCode($name,$path=null,$throw=true)
    {
        $fileToLoad = '';

        if (empty($path) || !is_array($path)) {
            $searchPath = self::$_classPath;
        } else {
            $searchPath = $path;
        }

        $name .= '.php';
        foreach ($searchPath as $path) {
            $fileNameToTest = $path . $name;
            if (is_file($fileNameToTest) ) {
                $fileToLoad = $fileNameToTest;
                break;
            }
            $fileNameToTest = $path . strtolower($name);
            if (is_file($fileNameToTest)) {
                $fileToLoad = $fileNameToTest;
                break;
            }
        }

        if (empty($fileToLoad) && $throw) {
            throw new Ox_Exception("Error: Can not find library {$name} in path: " . implode(';',$searchPath),'Ox_Loader::NotFound');
        }
        //Try/Catch does not work on require or includes.
        if (!empty($fileToLoad))
        {
            require_once($fileToLoad);
        }
    }

    /**
     * Load a system resource.
     *
     * Loads a class for the system objects from the locations in the classPath.  Then it instantiates
     * the class in a global variable of varName.  The class being loaded can be overwritten by setting
     * a variable in the app.php config file.
     * <br><br>
     * It would look like:
     * <pre><code>$class_overload = array (
     *                         varName => YourOverridingClassName,
     *                         'session' => 'TestSession',
     * );</code></pre>
     * <br><br>
     * Notes:
     * <ul>
     * <li>The file should have the same name as the class and should be in the app/lib directory.</li>
     * <li>Application classes must be unique.</li>
     * <li>In order to be a singleton, the class must have method getInstance() to initialize it.</li>
     * </ul>
     *
     * @static
     * @param string $varName Name of the system resource
     * @param string $defaultClassName Class name to load
     * @param bool $singleton Is this a singleton.
     * @throws Ox_Exception
     */
    public static function load($varName,$defaultClassName,$singleton=true)
    {
        global $$varName; //currently setup for backward compatibility
        $class = $defaultClassName;

        //If the ConfigParse is up, see if this class is being overloaded
        if (self::_getParser()) {
            if (isset(self::$_overloadList[$varName])) {
                if (self::DEBUG) {
                    Ox_Logger::logDebug("Overriding var $varName to " . self::$_overloadList[$varName]);
                }
                $class = self::$_overloadList[$varName];
                self::loadCode($class);
            }
        }
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_LibraryLoader - Loading Class: $class");
        }

        //There was no override.
        if ($class == $defaultClassName) {
            //this the code in memory to use the class?
            if (!class_exists($class,false)) {
                self::loadCode($defaultClassName);
            }
        }

        if (!class_exists($class,false)) {
            throw new Ox_Exception("After loading library ({$class}) can not find class: " . $class,'Ox_Loader::NotFound');
        }

        //actually load the class
        if ($singleton===true) {
            $$varName = $class::getInstance();
        } else {
            $$varName = new $class();
        }
        //Save this as a resource
        self::$_resources[$varName] = $$varName;

    }

    /**
     * Get a saved resource.
     *
     * This returns the associated object that has been loaded with Ox_LibraryLoader::load
     *
     * @see Ox_LibraryLoader::load
     * @static
     * @param string $name
     * @return null|mixed The resource object
     */
    public static function getResource($name)
    {
        if (self::DEBUG) Ox_Logger::logDebug("Ox_LibraryLoader - Resources: " . print_r(self::$_resources,1));
        if (array_key_exists($name,self::$_resources)) {
            if (self::DEBUG) Ox_Logger::logDebug("Ox_LibraryLoader - Getting resource: $name");
            return self::$_resources[$name];
        }  elseif (array_key_exists(strtolower($name),self::$_resources)) {
            if (self::DEBUG) Ox_Logger::logDebug("Ox_LibraryLoader - Getting resource: $name");
            $name = strtolower($name);
            return self::$_resources[$name];
        }else {
            return null;
        }
    }

    /**
     * Get system resource.
     *
     * This uses the PHP magic function to access static methods with arguments.  This allows a clean syntax to get a
     * system resource.
     * <br><br>
     * The following loads the session resource into the variable $session.
     * <br>
     * <pre><code>
     * $session = Ox_LibraryLoader::Session();
     * </code></pre>
     *
     * @param string $method_name Name of the missing method being call.
     * @param array $arguments Parameters passed to the method
     * @return mixed
     */
    public static function __callStatic($method_name,$arguments)
    {
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_LibraryLoader - Getting resource of static: $method_name");
        }
        return self::getResource($method_name);
    }

    /**
     * Hook for for PHP auto load.
     *
     * PHP will call this function when PHP can not file the find in the PHP path.
     *
     * @param string $className Class name.
     */
    public static function autoLoad($className) {
        $path = array(
            DIR_APP . 'validators' . DIRECTORY_SEPARATOR,
            OX_FRAME_DEFAULT . 'validators' . DIRECTORY_SEPARATOR,
            OX_FRAME_DEFAULT . 'exceptions' . DIRECTORY_SEPARATOR,
            OX_FRAME_EXCEPTIONS
        );
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_LibraryLoader - AutoLoad: $className");
        }
        Ox_LibraryLoader::loadCode($className,$path,false);
    }
}

spl_autoload_register(array('Ox_LibraryLoader','autoLoad'),false);
