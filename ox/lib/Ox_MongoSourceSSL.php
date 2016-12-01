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
require_once(DIR_FRAMELIB . 'Ox_MongoSource.php');

/**
 * Wrapper around the Mongo class from the PHP driver
 *
 * Allow us to add helper functions and other wrapper classes as needed.
 */
class Ox_MongoSourceSSL extends Ox_MongoSource
{
    /**
     * Enable/Disable Debugging for this object.
     */
    const DEBUG = false;

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
            Ox_Logger::logError("MongoDB SSL driver requires connection information; please specify in app.php");
            throw new Ox_MongoSourceException("MongoDB SSL driver requires connection information; please specify in app.php");
        }
        try{
            $host = $this->createConnectionName($this->_config, $this->_driverVersion);

            if (isset($this->_config['replicaset']) && count($this->_config['replicaset']) === 2) {
                $this->_connection = new Mongo($this->_config['replicaset']['host'], $this->_config['replicaset']['options']);
            } else if ($this->_driverVersion >= '1.3.0') {
                $context_information = array(
                    "ssl" => array(
                        /* Disable self signed certificates */
                        "allow_self_signed" => false,

                        /* Verify the peer certificate against our provided Certificate Authority root certificate */
                        "verify_peer"       => true, /* Default to false pre PHP 5.6 */

                        /* Verify the peer name (e.g. hostname validation) */
                        /* Will use the hostname used to connec to the node */
                        "verify_peer_name"  => true,

                    /* Verify the server certificate has not expired */
                        "verify_expiry"     => true, /* Only available in the MongoDB PHP Driver */
                ));
                if(empty($this->_config['private_key_file']) || !file_exists($this->_config['private_key_file'])) {
                    Ox_Logger::logError("SSL certificate file " . $this->_config['private_key_file'] . " does not exist.");
                    throw new Ox_MongoSourceException("SSL certificate file " . $this->_config['private_key_file'] . " does not exist.");
                    return false;
                }

                /* Certificate Authority the remote server certificate must be signed by */
                $context_information['ssl']['cafile'] = $this->_config['private_key_file'];
                $ctx = stream_context_create($context_information);

                $this->_connection = new MongoClient($host, array('ssl'=>true), array('context'=> $ctx));
            } else if ($this->_driverVersion >= '1.2.0') {
                //TODO: Write support for this driver version.
                Ox_Logger::logError("MongoDB SSL is not supported on this version of the MongoDB driver");
                throw new Ox_MongoSourceException("MongoDB SSL is not supported on this version of the MongoDB driver");
                return false;
            } else {
                //TODO: Write support for this driver version.
                Ox_Logger::logError("MongoDB SSL is not supported on this version of the MongoDB driver");
                throw new Ox_MongoSourceException("MongoDB SSL is not supported on this version of the MongoDB driver");
                return false;
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
}
