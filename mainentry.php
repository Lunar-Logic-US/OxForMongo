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
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Boot
 */

/**
 * This is the startup for Ox.  This is call from the app/webroot/index.php
 */


if (!defined('DEBUG_BOOT')) {
    /** Turns on the debugging for this file (mainentry.php) */
    define('DEBUG_BOOT',FALSE);
}

//These are constants that can be originally set in the app/config/framework.php
if (!defined('DIR_FRAMELIB')) {
    /** Framework library directory, can be set in framework.php */
    define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
}

if (!defined('DIR_APPCONFIG')) {
    /** Directory for the app configuration files, can be set in framework.php */
    define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);
}


if (!defined('DIR_APPLIB')) {
    /** Application's library directory (app/lib), can be set in framework.php */
    define ("DIR_APPLIB", DIR_APP . 'lib' . DIRECTORY_SEPARATOR);
}

if (!defined('DIR_CONSTRUCT')) {
    /** Application's construct directory (app/constructs), can be set in framework.php */
    define('DIR_CONSTRUCT',DIR_APP . 'constructs' . DIRECTORY_SEPARATOR);
}

if (!defined('DIR_COMMON')) {
    /** Application's common (app/constructs/_common) directory, can be set in framework.php */
    define('DIR_COMMON',DIR_CONSTRUCT . '_common' . DIRECTORY_SEPARATOR);
}

if (!defined('DIR_LAYOUTS')) {
    /** Application's layout s(app/constructs/_common/layouts) directory, can be set in framework.php */
    define('DIR_LAYOUTS',DIR_COMMON . 'layouts' . DIRECTORY_SEPARATOR);
}

if (!defined('OX_FRAMEINTERFACE')) {
    /**
     * Framework interface locations, can be set in framework.php
     */
    define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);
}

if (!defined('OX_FRAME_DEFAULT')) {
    /**
     * Framework default user object location
     */
    define ('OX_FRAME_DEFAULT', DIR_FRAMEWORK . 'default' . DIRECTORY_SEPARATOR);
}
if (!defined('OX_FRAME_EXCEPTIONS')) {
    /**
     * Framework default exceptions
     */
    define ('OX_FRAME_EXCEPTIONS', DIR_FRAMELIB . 'exceptions' . DIRECTORY_SEPARATOR);
}

//---------------------------
//Initialize Base Objects
//---------------------------
require_once(OX_FRAMEINTERFACE  . 'Ox_Routable.php');
require_once(DIR_FRAMELIB . 'Ox_LibraryLoader.php');
require_once(DIR_FRAMELIB . 'Ox_Logger.php');
Ox_LibraryLoader::loadAll(OX_FRAME_EXCEPTIONS);
Ox_LibraryLoader::loadAll(OX_FRAMEINTERFACE);

//TODO:To be moved out of here.
require_once(DIR_FRAMELIB . 'Ox_Asset.php');
//require_once(OX_FRAMEINTERFACE  . 'Ox_Widget.php');

//---------------------------
// Initialize the user object
//---------------------------
//TODO: make this passable to that all errors can be displayed at once.
global $user;  //added for unit test which this needs to be forced in the global scope.

//The config_parser must be first to get all of the settings for this application for all other objects in the system.
//app.php is read when we load the config_parser
Ox_LibraryLoader::load('config_parser','Ox_ConfigPHPParser',FALSE);
Ox_LibraryLoader::load('widget_handler','Ox_WidgetHandler');
if (DEBUG_BOOT) Ox_Logger::logDebug("*****************Loading Page: " .$_SERVER['REQUEST_URI']);
Ox_LibraryLoader::load('session','Ox_Session');
Ox_LibraryLoader::load('db','Ox_MongoSource',FALSE);
Ox_LibraryLoader::load('security','Ox_SecurityMongoCollection',FALSE);
Ox_LibraryLoader::load('dispatch','Ox_Dispatch',FALSE);
Ox_LibraryLoader::load('hook','Ox_Hook',TRUE);
Ox_LibraryLoader::load('mailer','Ox_Mail',FALSE);

//Router uses the Ox_Dispatch::CONFIG_WEB_BASE_NAME, must be after OxDispatch
Ox_LibraryLoader::load('router','Ox_Router',FALSE);
//Load after router, uses Oc_Router::buildURL
Ox_LibraryLoader::load('assets_helper','LocalAsset',FALSE);

//Loading routes.php in loadRoutes.  Loaded here to allow modules to overwrite the default routes.
//Loaded after the Ox_Router as Actions will use Ox_Router::buildURL
Ox_Dispatch::loadRoutes();

// Load default project configuration files if they exist
if(file_exists(DIR_APPCONFIG . 'modules.php')) {
    if (DEBUG_BOOT) Ox_Logger::logDebug("Main Page loading modules.php.");
    require_once (DIR_APPCONFIG . 'modules.php');
}
if(file_exists(DIR_APPCONFIG . 'hook.php')) {
    if(DEBUG_BOOT) Ox_Logger::logDebug("Main Page loading hook.php.");
    require_once(DIR_APPCONFIG . 'hook.php');
}

if(file_exists(DIR_APPCONFIG . 'global.php')) {
    if (DEBUG_BOOT) Ox_Logger::logDebug("Main Page loading global.php.");
    require_once (DIR_APPCONFIG . 'global.php');
}
//---------------------------
// Done loading defines and libraries. Pass off control to the dispatcher
//---------------------------
Ox_Dispatch::run();

