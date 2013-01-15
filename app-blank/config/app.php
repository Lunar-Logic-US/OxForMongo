<?php
/**
 * Application Variables needed throughout the application.  They are accessible through
 * the config parser.
 *
 * $mongoConfig = array(
 *      'set_string_id' => true,
 *      'persistent' => true,
 *      'host'       => 'localhost',
 *      'database'   => 'test',
 *      'port'       => '27017',
 *      'login'         => '',
 *      'password'      => '',
 *      'replicaset'    => '',
 *  );
 */

/**
 * @var array
 */
$class_overload = array();


/**
 * If the app is in a subdirectory the you need to set webdir_base to the directory that the app is in. Note no trailing /
 * @var string
 */
//$web_base_dir = '/appdir';



/**
 * MongoDB connection settings
 */
$mongo_config = array(
    'set_string_id' => TRUE,
    'persistent' => TRUE,
    'host'       => 'localhost',
    'database'   => 'blank',
    'port'       => '27017',
    'login'         => '',
    'password'      => '',
    'replicaset'    => '',
);

/**
 * This is the url to the login page (default is /usr/login)
 */
$login_url = '/user/login';

/**
 * Session handling configuration
 */
$session = array(
    'mongo_enabled' => FALSE,            //if FALSE reverts to php sessions
    'mongo_collection' =>'sessions',
    //'cookie_path' => '/',             //defaults to /
    //'cookie_domain' => 'example.com', //defaults to the current domain
);

/**
 * Log file location
 */
$log_dir = DIR_APP . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'log';

/**
 * LBG role defines
 * TODO: Move into a table
 */
$roles = array(
    //'su', We don't need this since it is hard coded to allow. Also, we don't
    //      want any chance of this getting loose anywhere.
    'role1',
    'role2',
    'role3',
    'user',
);

$timeouts = array(
    'chain_admin'=>30*60,
    'location_admin'=>30*60,
    'manager'=>60*60,
    'user/staff'=>5*60
);


