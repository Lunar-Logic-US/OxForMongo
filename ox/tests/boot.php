<?php

define ('DIR_APP',dirname(__FILE__) . '/tmp/');
define ('DIR_CONFIG',DIR_APP . 'config/');
file_put_contents(DIR_CONFIG . 'app.php',$appConfigFile);
define ('DIR_FRAMEWORK',dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define ('DIR_FRAMELIB',DIR_FRAMEWORK . 'lib/');
require_once(DIR_FRAMELIB . 'Ox_Dispatch.php');
Ox_Dispatch::skipRun();

require_once(DIR_FRAMEWORK . 'mainentry.php');
