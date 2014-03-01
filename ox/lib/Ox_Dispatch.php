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

/** RegEx for the web root. */
define('WEB_ROOT','/^\/?$/');
/** RegEx for the assests. */
define('WEB_ASSET_ROUTE','/^\/assets\/([\w -]*\.\w{0,5})$/');
/** RegEx Default. */
define('WEB_DEFAULT_ROUTE','/^\/(\w*)(\/\w*)?(\/[\w.\-_]*)?(\/[\w.\-_]*)?(\/[\w.\-_]*)?(\/\w*)?(\/\d*)?$/');

/** Name for the assembler file. */
define('ASSEMBLER_NAME','assembler.php');
require_once(DIR_FRAMELIB . 'constructs/Ox_AssemblerConstruct.php');

/**
 * Set the environment for routing.
 *
 * This does basic url handling and make sure all the routes are setup.  Run is called when as the next step
 * in the boot process.  It is called from mainentry.php.
 * <br><br>
 * If you are unit testing you can skip the dispatch by calling the Ox_Dispatch::skipRun() method.
 *
 * @package Ox_Boot
 */
class Ox_Dispatch
{
    /** Enable/Disable Debugging for this object. */
    const DEBUG = FALSE;

    /** Variable name in app.php for web base directory ie: $web_base_dir = "/ox_app" */
    const CONFIG_WEB_BASE_NAME = 'web_base_dir';

    /** @var boolean Flag to disable routing. */
    private static $_skipRun = FALSE;
    
    /** @var boolean Initialization status */
    private static $_initialized = FALSE;
    
    /** @var string Path of the default actions. */
    private static $_dirDefaultActions;
    
    /** @var string Path to the application defined actions. */
    private static $_dirAppActions;
    
    /** @var array Full path and name to route file. */
    private static $_appRoutes;

    /** @var string Base of the URL to strip off if the app is in a sub directory. */
    private static $_appWebBase='';

    /**
     * Allow the dispatch to run.  For unit testing.
     * @static
     */
    public static function allowRun() { self::$_skipRun=FALSE; }

    /**
     * Stop dispatch from running.  For unit testing.
     * @static
     */
    public static function skipRun() { self::$_skipRun=TRUE; }

    /**
     * Initialization routine sets local static vars.
     */
    private static function _init()
    {
        self::$_initialized = TRUE;
        self::$_dirDefaultActions = OX_FRAME_DEFAULT . 'actions' . DIRECTORY_SEPARATOR;
        self::$_dirAppActions = DIR_APP . 'actions' . DIRECTORY_SEPARATOR;
        self::$_appRoutes = DIR_APP . 'config' . DIRECTORY_SEPARATOR . 'routes.php';
        self::$_appWebBase = Ox_LibraryLoader::Config_Parser()->getAppConfigValue(self::CONFIG_WEB_BASE_NAME);
    }

    /**
     * Loads all actions and routes.
     *
     * This loads all of the need actions and routes in preparation for dispatching.  The route.php is loaded here.
     */
    public static function loadRoutes()
    {
        if (!self::$_initialized) {
            self::_init();
        }
        //TODO: change this so that actions are only loaded when needed.
        //Load the default Ox actions
        Ox_LibraryLoader::loadAll(self::$_dirDefaultActions);

        Ox_Router::add(WEB_ROOT, new Ox_FlatAction());
        Ox_Router::add(WEB_ASSET_ROUTE, new Ox_AssetAction());
        Ox_Router::add(WEB_DEFAULT_ROUTE, new Ox_AssemblerAction());

        //Load all app actions in the app actions directory
        if (is_dir(self::$_dirAppActions)) {
            Ox_LibraryLoader::loadAll(self::$_dirAppActions);
        }

        //Add application routes
        if (file_exists(self::$_appRoutes)) {
            require_once(self::$_appRoutes);
        }
    }

    /**
     * Gets URL Path
     *
     * Get the URL Path modified as need to be able to route our web call.
     *
     * @uses $_SERVER['REQUEST_URI']
     * @return string URL path
     */
    public static function getURLPath()
    {
        //Decode the URL
        $url_info = parse_url($_SERVER['REQUEST_URI']);
        if (self::DEBUG) {
            Ox_Logger::logDebug(__CLASS__ . '-' . __FUNCTION__ . ": Path from URL: " . $url_info['path'] . " | App web Base: " . self::$_appWebBase);
        }

        //Strip off part of the url as needed
        $url = $url_info['path'];
        if (substr($url, 0, strlen(self::$_appWebBase)) == self::$_appWebBase) {
            $url = substr($url, strlen(self::$_appWebBase), strlen($url) );
        }
        return $url;
    }

    /**
     * Dispatch the request.
     *
     * Get the path part of the URL and removed the web base as needed.  Then send the path request to be routed.
     */
    public static function run()
    {
        if (self::$_skipRun) { return false; }
        if (!self::$_initialized) { self::_init(); }

        // Do timeout checking--if timeout exceeded, end session, else, update last activity time.
        Ox_LibraryLoader::Session()->update();
        if (self::DEBUG) {
            Ox_Logger::logDebug(__CLASS__ . '-' . __FUNCTION__ . ": session after update: " . print_r($_SESSION,1));
        }

        $urlPath = self::getURLPath();

        if (self::DEBUG) {
            Ox_Logger::logDebug(__CLASS__ . '-' . __FUNCTION__ . ": Url Path Before Routing: " . $urlPath);
        }
        Ox_Router::route($urlPath);
    }
}