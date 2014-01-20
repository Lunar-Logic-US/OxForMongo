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
 * This is the startup for Ox.  This is call from the app/webroot/index.php
 */


/**
 * @contant DEBUG_BOOT boolean Turns on the debugging for this file (mainentry.php)
 */
if (!defined('DEBUG_BOOT')) {
    define('DEBUG_BOOT',FALSE);
}

/**
 * These are constants that can be  originally set in the app/config/framework.php
 */

/**
 * @constant DIR_FRAMEWORK Framework lib directory
 */
if (!defined('DIR_FRAMELIB')) {
    define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
}

/**
 * @constant DIR_APPCONFIG This is the location
 */
if (!defined('DIR_APPCONFIG')) {
    define ("DIR_APPCONFIG", DIR_APP . 'config' . DIRECTORY_SEPARATOR);
}

/**
 * @constant DIR_APPLIB The location of the Application's library directory (app/lib)
 */

if (!defined('DIR_APPLIB')) {
    define ("DIR_APPLIB", DIR_APP . 'lib' . DIRECTORY_SEPARATOR);
}

/**
 * @constant DIR_CONSTRUCT Application's construct directory (app/constructs)
 */
if (!defined('DIR_CONSTRUCT')) {
    define('DIR_CONSTRUCT',DIR_APP . 'constructs' . DIRECTORY_SEPARATOR);
}

/**
 * @constant DIR_COMMON Application's common (app/constructs/_common) directory
 */
if (!defined('DIR_COMMON')) {
    define('DIR_COMMON',DIR_CONSTRUCT . '_common' . DIRECTORY_SEPARATOR);
}

/**
 * @constant DIR_LAYOUTS Application's layout s(app/constructs/_common/layouts) directory
 */
if (!defined('DIR_LAYOUTS')) {
    define('DIR_LAYOUTS',DIR_COMMON . 'layouts' . DIRECTORY_SEPARATOR);
}

/**
 * @constant OX_FRAMEINTERFACE Framework interface locations
 */
if (!defined('OX_FRAMEINTERFACE')) {
    define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);
}

/**
 * @constant OX_FRAME_DEFAULT Framework default user object location
 */
if (!defined('OX_FRAME_DEFAULT')) {
    define ('OX_FRAME_DEFAULT', DIR_FRAMEWORK . 'default' . DIRECTORY_SEPARATOR);
}
if (!defined('OX_FRAME_EXCEPTIONS')) {
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

//The config_parser must be first to get all of the settings for
//this application for all other objects in the system.
Ox_LibraryLoader::load('config_parser','Ox_ConfigPHPParser',FALSE);
Ox_LibraryLoader::load('widget_handler','Ox_WidgetHandler');
if (DEBUG_BOOT) Ox_Logger::logDebug("*****************Loading Page: " .$_SERVER['REQUEST_URI']);
Ox_LibraryLoader::load('session','Ox_Session');
Ox_LibraryLoader::load('db','Ox_MongoSource',FALSE);
Ox_LibraryLoader::load('security','Ox_SecurityMongoCollection',FALSE);
Ox_LibraryLoader::load('dispatch','Ox_Dispatch',FALSE);
Ox_LibraryLoader::load('hook','Ox_Hook',FALSE);
//Router uses the Ox_Dispatch::CONFIG_WEB_BASE_NAME, must be after OxDispatch
Ox_LibraryLoader::load('router','Ox_Router',FALSE);

//asset must load after router...is used buildURL
Ox_LibraryLoader::load('assets_helper','LocalAsset',FALSE);

if (file_exists(DIR_APPCONFIG . 'global.php')) {
    include_once (DIR_APPCONFIG . 'global.php');
}

//---------------------------
// Done loading defines and libraries. Pass off control to the dispatcher
//---------------------------
Ox_LibraryLoader::load('dispatch','Ox_Dispatch',FALSE);
Ox_Dispatch::run();

