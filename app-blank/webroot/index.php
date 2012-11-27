<?php
/**
 * Site root.
 *
 * This sets up the most basic constants, then hands off process to the
 * framework. This should be the only place PHP code is directly called.
 *
 * This file/directory should be marked read-only.
 */

//ini_set('session.gc_maxlifetime',5);
//session_start();

/**
 * @contant DIR_APP Application directory
 */
define ("DIR_APP", dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

/**
 * @contant DIR_FRAMEWORK Framework directory
 */
require_once (DIR_APP . 'config/framework.php');
if (!defined('DIR_FRAMEWORK')) {
    define ("DIR_FRAMEWORK",dirname(DIR_APP) . DIRECTORY_SEPARATOR . 'ox' . DIRECTORY_SEPARATOR );
}

/**
 * @constant DIR_UPLOAD uploads directory
 */
define ("DIR_UPLOAD", dirname(DIR_APP) . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR);

//hand-off to the framework .. try catch will not work here.
if (file_exists(DIR_FRAMEWORK . 'mainentry.php')) {
    require_once(DIR_FRAMEWORK . 'mainentry.php');
} else {
    print <<<HTML
        <div style="background-color: #f08080;font-weight: bold;font-size:34px;border: 2px solid black;">
        Can not find the Ox Framework.  Please check your settings in config/framework.php.
        </div>
HTML;
    throw new Exception ('Can not find ox/mainentry.php.  Check config/framework.php');
}

