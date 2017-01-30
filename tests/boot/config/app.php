<?php
/*
This is a default app.php that is used for running tests.  To override these
settings for a test, please do not overwrite this file (as some tests have done
in the past); instead, mock an Ox_ConfigParser and hand that to the class being
tested.  You may need to refactor the class to use an Ox_ConfigParser object if
handed one, instead of always using the singleton from Ox_LibraryLoader.
 */

$log_dir = DIR_APP . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'log';

$session_token_hmac_key = 'test-key';
