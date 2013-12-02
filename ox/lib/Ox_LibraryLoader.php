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
 * This class is used to load the various library components of the system.  It can be used dynamically to load parts
 * of the system inside functions for methods.
 */

require_once (OX_FRAME_EXCEPTIONS . 'Ox_Exception.php');

class Ox_LibraryLoader
{
    const CONFIG_OVERLOAD_NAME = 'class_overload';
    const DEBUG = FALSE;

    /**
     * @var null  This holds a pointer to the config parser
     */
    private static $_resources = array();

    /**
     * @var null  This holds a pointer to the config parser
     */
    private static $_configParser = null;
    
    /**
     * @var array list of ox object names (index) and the class name that will be used (value)
     * See Ox_LibraryLoader::load()
     */
    private static $_overloadList = array();
    
    /**
     * @var array This is the default search path to look for the php code.
     * NOTE: The default loads the FRAMEWORK FIRST!!! See Ox_LibraryLoader::load().
     */
    private static $_classPath = array(DIR_FRAMELIB,DIR_APPLIB);


    /**
     * Add to the class path
     * @static
     * @param $path
     */
    public static function addPath($path)
    {
        self::$_classPath[] = $path;
    }

    /**
     * Get the current class path
     * @static
     * @return array
     */
    public static function getPath()
    {
        return self::$_classPath;
    }

    /**
     * TESTING ONLY: clear the config information
     *
     * @static
     * @return array
     */
    public static function clearConfig()
    {
        self::$_configParser = null;
        self::$_overloadList = null;
    }

    /**
     * See if the configParser has been set or is available.
     * If it is available, load the class overload settings.
     *
     * @static
     * @return bool
     * @throws Exception $e
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
     * This call loads all of the php code in a directory.
     *
     * This is useful for the default actions directories.
     *
     * @param String $path
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
     * This just loads a library from the default areas in the Ox/app tree or if
     * specified the path given.
     *
     * @param $name
     * @param null $path
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
     * This function loads a class for the system objects from the locations in the classPath.  Then it instantiates
     * the class in a global variable of varName.  The class being loaded can be overwritten by setting
     * a variable in the app.php config file.
     *
     * It would look like:
     * $class_overload = array (
     *                         <varName> => <YourOveridingClassName>,
     *                         'session' => 'TestSession',
     * );
     *
     * Notes:
     *    The file should have the same name as the class and should be in the app/lib directory.
     *    Application classes must not have the same name as the Ox classes for all classes in the Ox/lib directory
     *
     * TODO: Add a system to save all "system" objects in an array so you can request those objects from this loader.
     *
     * @static
     * @param $varName
     * @param $defaultClassName
     * @param bool $singleton
     * @throws Exception
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
     * @static
     * @param $name
     * @return null
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
     * Magic function to access static methods with arguments.
     */
    public static function __callStatic($method_name,$arguments)
    {
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_LibraryLoader - Getting resource of static: $method_name");
        }
        return self::getResource($method_name);
    }

    /**
     * Loads the class into memory.
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
