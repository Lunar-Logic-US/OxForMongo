<?php
/**
 * Framework Settings
 * Here are the defines to setup how the framework will access parts of this application.
 * It is loaded before the framework's main entry.
 */

/**
 * This would be the absolute path to get to the Ox directory.
 *
 * By default this is not required. It is only needed IF
 * this application is in it own directory structure separate from Ox.
 *
 * If set it give the directory of the "ox" directory in Ox.
 * If it is not set or commented out, then the framework assumes
 * that that application is part of the Ox tree at the same
 * level as the "ox" directory.
 *
 * Please note the trailing slash.  It is required.
 *
 * @contant DIR_FRAMEWORK Framework directory
 */
/*
if (!defined('DIR_FRAMEWORK')) {
    define ("DIR_FRAMEWORK",'/someplace/ox//');
}
*/