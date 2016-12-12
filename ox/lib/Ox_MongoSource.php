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
 * This is a convenience layer to the PECL object.
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
require_once(DIR_FRAMELIB . 'mongo/Ox_MongoCollection.php');
require_once(DIR_FRAMELIB . 'Ox_Schema.php');

/**
 * Wrapper around the Mongo class from the PHP driver
 *
 * Allow us to add helper functions and other wrapper classes as needed.
 */
class Ox_MongoSource
{
    /**
     * Enable/Disable Debugging for this object.
     */
    const DEBUG = false;

    /**
     * The configure we use to setup the connection
     * @var array
     */
    protected $_config;

    /**
     * Connect to Mongo
     * @var Mongo
     */
    protected $_connection;

    /**
     * Database Instance
     *
     * @var MongoDB
     * @access protected
     */
    protected $_db = false;

    /**
     * Mongo Driver Version
     *
     * @var string
     * @access protected
     */
    protected $_driverVersion = Mongo::VERSION;

    /**
     * List of created collections.
     *
     * Need to maintain collection state.
     * @var array
     */
    protected $_collectionCache = array();

    /**
     * Create connection URI.
     *
     * @param array $config
     * @param string $version  version of MongoDriver
     * @return string
     */
    public function createConnectionName($config, $version)
    {
        $host = '';
        if ($version >= '1.0.2') {
            $host = "mongodb://";
        }

        $hostname = $config['host'] . ':' . $config['port'];
        if(empty($config['login'])){
            $host .= $hostname;
        } else {
            $host .= $config['login'] .':'. $config['password'] . '@' . $hostname . '/'. $config['database'];
        }

        return $host;
    }

    /**
     * Connect to the database
     *
     * If using 1.0.2 or above use the mongodb:// format to connect
     * The connect syntax changed in version 1.0.2 - so check for that too
     *
     * If authentication information in present then authenticate the connection
     *
     * TODO: Need to be updated to use MongoClient when the new driver comes out.
     *
     * @throws Ox_MongoSourceException
     * @return boolean Connected
     * @access public
     */
    public function connect()
    {
        global $config_parser;

        if ($this->_db !== false) {
            return true;
        }
        if($mongo_config=$config_parser->getAppConfigValue('mongo_config')) {
            $this->_config = $mongo_config;
        } else {
            // Default config
            $this->_config = array(
                'set_string_id' => true,
                'persistent' => true,
                'host'       => 'localhost',
                'database'   => 'test',
                'port'       => '27017',
                'login'         => '',
                'password'      => '',
                'replicaset'    => '',
            );
        }
        try{
            $host = $this->createConnectionName($this->_config, $this->_driverVersion);

            if (isset($this->_config['replicaset']) && count($this->_config['replicaset']) === 2) {
                $this->_connection = new Mongo($this->_config['replicaset']['host'], $this->_config['replicaset']['options']);
            } else if ($this->_driverVersion >= '1.3.0') {
                $this->_connection = new MongoClient($host);
            } else if ($this->_driverVersion >= '1.2.0') {
                $this->_connection = new Mongo($host);
            } else {
                $this->_connection = new Mongo($host, true, $this->_config['persistent']);
            }

            if (isset($this->_config['slaveok'])) {
                $this->_connection->setSlaveOkay($this->_config['slaveok']);
            }

            if ($this->_db = $this->_connection->selectDB($this->_config['database'])) {
                if (!empty($this->_config['login']) && $this->_driverVersion < '1.2.0') {
                    $return = $this->_db->authenticate($this->_config['login'], $this->_config['password']);
                        if (!$return || !$return['ok']) {
                            Ox_Logger::logError('MongodbSource::connect ' . $return['errmsg']);
                            throw new Ox_MongoSourceException('MongodbSource::connect ' . $return['errmsg'],'ConnectFailed');
                            return false;
                        }
                }
            }

        } catch(MongoException $e) {
            $this->error = $e->getMessage();
            Ox_Logger::logError($this->error);
            throw new Ox_MongoSourceException('MongodbSource::connect ' . $this->error,'ConnectFailed');
        }
        return true;
    }

    /**
     * @param string $db
     */
    public function selectDB($db){
        $this->connect();
        //var_dump($this->_connection);
        $this->_db = $this->_connection->selectDB($db);
        //TODO: add the authentication
    }

    /**
     * Shortcut function for runCommand
     *
     * @param $command
     * @return null
     * @throws Ox_MongoSourceException
     */
    public function run($command)
    {
        if (is_string($command)) {
            $command_object = json_decode($command);
            if ($command_object === null) {
                $this->error = 'Bad JSON: ' . $command;
                Ox_Logger::logError($this->error);
                throw new Ox_MongoSourceException('MongodbSource::run ' . $this->error,'RunFailed');
                return null;
            }
            $command = $command_object;
        }
        if (self::DEBUG) Ox_Logger::logDebug('MongoSource::run: ' . print_r($command,1));
        $this->connect();
        return $this->_db->command($command);
    }

    /**
     * Wrapper for the Mongo execute method
     *
     * @param $query
     * @param array $args
     * @return mixed
     */
    public function execute($query, $args = array())
    {
       if (self::DEBUG) Ox_Logger::logMessage('MongoSource::execute: ' . $query . ' ' . serialize($args));
        $this->connect();
        return $this->_db->execute($query);
    }

    /**
     * Return the REAL mongoDB object.
     *
     * @return bool|MongoDB
     */
    public function getDB()
    {
        $this->connect();
        return $this->_db;
    }

    /**
     * Return the MongoGridFS Object
     *
     * @return mixed
     */
    public function getGridFS()
    {
        $this->connect();
        return $this->_db->getGridFS();
    }


    /**
     * Wrapper for collections.
     *
     * Setting a collections wrapper to add functionality to some collections calls.
     * @param $collection
     * @return Ox_MongoCollection
     */
    public function getCollection($collection)
    {
        $this->connect();
        if (!isset($this->_collectionCache[$collection])) {
            $this->_collectionCache[$collection] = new Ox_MongoCollection( $this->_db->$collection );
        }
        return $this->_collectionCache[$collection];
    }

    /**
     * This setup this object o be able to return collections in the form $var->collection_name.
     *
     * @param $name
     * @return Ox_MongoCollection
     */
    public  function __get($name)
    {
        //Do the assignment to make sure the pointer is returned correctly
        $collection = $this->getCollection($name);
        return $collection;
    }

    /**
     * Check if it is a Mongo array
     *
     * From experimental checking must test all of the keys.  If one of them is not a number or is not in
     * numeric order then we have a Mongo Object.
     *
     * @param array $array
     * @return bool
     */
    public static function isMongoArray(array $array)
    {
        $index = 0;
        foreach ($array as $k => $v) {
            if (is_int($k) && $k == $index) {
                $index++;
            } else {
                return false;
            }
        }
        return true;
    }

}
