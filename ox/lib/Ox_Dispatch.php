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


//TODO: refactor so these defines and require are better handled
/**
 * RegEx for the web root.
 */
define('WEB_ROOT','/^\/?$/');
/**
 * Name for the assembler file.
 */
define('ASSEMBLER_NAME','assembler.php');
require_once(DIR_FRAMELIB . 'constructs/Ox_AssemblerConstruct.php');

/**
 * Sets up the information needed for the router to run.
 *
 * @package Ox_Boot
 */
class Ox_Dispatch
{
    const DEBUG = FALSE;
    /**
     * Flag to disable dispatch.
     */
    private static $_skipRun = FALSE;
    
    /**
     * Flag to indicate initialization status
     */
    private static $_initialized = FALSE;
    
    /**
     * The path of the default actions.
     */
    private static $_dirDefaultActions;
    
    /**
     * The path to the application defined actions.
     */
    private static $_dirAppActions;
    
    /**
     * The path to application routes.
     * @var array
     */
    private static $_appRoutes;

    /**
     * The base of the URL to strip off if the app is in a sub directory.
     * @var string
     */
    private static $_appWebBase='';

    const CONFIG_WEB_BASE_NAME = 'web_base_dir';


    /**
     * Set the skip flag to allow the dispatch to run.  This is for unit testing.
     * @static
     */
    public static function allowRun()
    {
        self::$_skipRun=FALSE;
    }
    /**
     * Set the skip flag to allow the stop dispatch from running.  This is for unit testing.
     * @static
     */
    public static function skipRun()
    {
        self::$_skipRun=TRUE;
    }

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
     * Loads all actions and routes in preparation for dispatch.
     */
    public static function loadRoutes()
    {
        if (!self::$_initialized) {
            self::_init();
        }
        //TODO: change this so that actions are only loaded when needed.
        //Load the default Ox actions
        Ox_LibraryLoader::loadAll(self::$_dirDefaultActions);

        // TODO: change to make easier
        // This should be changed to make it easier for applications to override default actions.  For example,
        // these routes could go into a separate routing array that only gets checked if all user patterns fall
        // through.  Otherwise in order to override these patterns, the application must know the exact regex
        // string to use to override it, and this can lead to needing two or more router->add lines per regex --
        // one to override the default and another to catch the other patterns that the default regex would not
        // catch.
        Ox_Router::add(WEB_ROOT, new Ox_FlatAction());

        Ox_Router::add('/^\/assets\/([\w -]*\.\w{0,5})$/', new Ox_AssetAction());
        Ox_Router::add('/^\/assets\/(\w*)\/(\d*)\/(\d*)$/', new Ox_AssemblerAction(DIR_CONSTRUCT . 'asm/assets_asm/',
            'AssetsAssembler'));
        Ox_Router::add('/^\/assets(\/\w*)?$/', new Ox_AssemblerAction(DIR_CONSTRUCT . 'asm/assets_asm/',
            'AssetsAssembler'));
        Ox_Router::add('/^(\w+)\/(\w+)$/', new Ox_AssemblerAction());

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
     * Executes the dispatch by routing to a page in your web app.
     */
    public static function run()
    {
        if (self::$_skipRun) {
            return false;
        }
        if (!self::$_initialized) {
            self::_init();
        }

        // Do timeout checking--if timeout exceeded, end session, else, update
        // last activity time.
        global $session;
        $session->update();
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_Dispatch: session after update: " . print_r($_SESSION,1));
        }

        //self::_loadRoutes();
        //decode the URL
        $url_info = parse_url($_SERVER['REQUEST_URI']);
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_Dispatch: Before Trim: " . $url_info['path'] . " Trim string: " . self::$_appWebBase);
        }

        // Strip off part of the url as needed
        $url = $url_info['path'];
        if (substr($url, 0, strlen(self::$_appWebBase)) == self::$_appWebBase) {
            $url = substr($url, strlen(self::$_appWebBase), strlen($url) );
        }

        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_Dispatch: Dispatching path (After Trim): " . $url);
        }
        Ox_Router::route($url);
    }
}