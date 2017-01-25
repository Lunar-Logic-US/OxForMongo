<?php
//------------------------------------------------
// Simple OX BOOTSTRAP for Ox object and libraries.
//------------------------------------------------
define('DS', DIRECTORY_SEPARATOR);
define('DIR_APP', dirname(__FILE__) . DS);
define('DIR_FRAMEWORK', dirname(dirname(dirname(__FILE__))) . DS . 'ox' . DS);
define('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DS);

// Keep dispatch from actually dispatching.
require_once(DIR_FRAMELIB . 'Ox_Dispatch.php');
Ox_Dispatch::skipRun();

require_once(DIR_FRAMEWORK . 'mainentry.php');

//------------------------------------------------
// OX BOOTSTRAP DONE.
//----------------------------------------------------
