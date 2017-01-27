<?php

namespace ox\lib\session_handlers;

require_once dirname(dirname(dirname(dirname(__FILE__))))
    . '/vendor/autoload.php';

use \Ox_ConfigParser;
use \Ox_LibraryLoader;
use \Ox_Logger;
use \ox\lib\exceptions\SessionException;
use \ox\lib\http\CookieManager;

class MongoSessionHandler extends \ox\lib\abstract_classes\SessionHandler
{
    use \ox\lib\traits\Singleton;

    const LOG_PREFIX = 'MongoSessionHandler: ';

    const DB_COLLECTION_NAME = 'ox_session';
    const GC_ID = 'garbage_collection';
    const GC_TIMESTAMP_KEY = 'last_performed';
    const SESSION_CREATED_KEY = 'created';
    const SESSION_ID_BYTE_LENGTH = 32;
    const SESSION_ID_HASH_ALGORITHM = 'sha256';
    const SESSION_LAST_REQUEST_KEY = 'last_request';
    const SESSION_VARIABLES_KEY = 'variables';
    const TOKEN_HMAC_ALGORITHM = 'sha256';
    const TOKEN_HMAC_BYTE_LENGTH = 32; // tied to TOKEN_HMAC_ALGORITHM

    const INVALID_KEY_EXCEPTION_MESSAGE = 'Key contains invalid characters';
    const INVALID_TOKEN_HMAC_MESSAGE = 'Session token HMAC is invalid';
    const NO_RANDOM_BYTES_EXCEPTION_MESSAGE =
        'PRNG failure; no session ID can be generated';
    const UNOPENED_EXCEPTION_MESSAGE = 'A session has not been opened yet';
    const OPEN_EXCEPTION_MESSAGE = 'A session is already open';
    const MISSING_TOKEN_HMAC_KEY_EXCEPTION_MESSAGE =
        'Session token HMAC key is not set (in app config)';

    const CONFIG_GC_INTERVAL_NAME = 'session_gc_interval';
    const CONFIG_MAX_SESSION_AGE_NAME = 'max_session_age';
    const CONFIG_MAX_SESSION_IDLE_NAME = 'max_session_idle';
    const CONFIG_TOKEN_HMAC_KEY_NAME = 'session_token_hmac_key';

    // These settings can be overridden in the app config using the names above
    const GC_INTERVAL_DEFAULT = 3600; // 1 hour
    const MAX_SESSION_AGE_DEFAULT = 3600; // 1 hour
    const MAX_SESSION_IDLE_DEFAULT = 900; // 15 minutes

    private $collection;
    private $gc_interval;
    private $max_session_age;
    private $max_session_idle;

    /** @var string The token HMAC key (loaded from app config) */
    private $token_hmac_key;

    /** @var string The unique identifier of the session */
    private $session_id;

    /** @var string The name of the cookie which stores the session token */
    private $session_name;

    /**
     * @var Ox_ConfigParser Used for all ConfigParser calls, to facilitate
     *                      mocks in unit tests
     */
    private $configParser;

    /**
     * @var CookieManager Used for all CookieManager calls, to facilitate mocks
     *                    in unit tests
     */
    private $cookieManager;

    /**
     * @var Ox_MongoSource Used for all Mongo calls, to facilitate mocks in
     *                     unit tests
     */
    private $mongoSource;

    /**
     * Accessor method for configParser.  This is here to facilitate unit
     * testing.
     */
    public function setConfigParser($configParser)
    {
        $this->configParser = $configParser;
    }

    /**
     * Accessor method for cookieManager.  This is here to facilitate unit
     * testing.
     */
    public function setCookieManager($cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    /**
     * Accessor method for mongoSource.  This is here to facilitate unit
     * testing.
     */
    public function setMongoSource($mongoSource)
    {
        $this->mongoSource = $mongoSource;
    }

    public function __construct()
    {
        // Use the default ConfigParser singleton
        $this->configParser = Ox_LibraryLoader::Config_Parser();

        // Use an instance of the default CookieManager class
        $this->setCookieManager(new CookieManager());

        // Use the default MongoSource singleton
        $this->setMongoSource(Ox_LibraryLoader::db());
    }

    /**
     * Close the session, destroying it.
     *
     * @return bool True if the session was successfully destroyed
     */
    public function destroy()
    {
        $this->throwIfUnopened();

        $criteria = ['_id' => $this->session_id_hash];
        $options = [
            'justOne' => true,
            'w' => 1 // Acknowledged write
        ];
        $result = $this->collection->remove($criteria, $options);

        if (isset($result['ok']) && $result['ok']) {
            $this->cookieManager->delete($this->session_name, '/');
            $this->session_id = null;
            $this->session_id_hash = null;
            $this->collection = null;

            return true;
        } else {
            Ox_Logger::logError(self::LOG_PREFIX . 'failed to close session');
            return false;
        }
    }

    /**
     * Open the session.  This must be called before calling any other methods.
     *
     * @param string $save_path
     * @param string $session_name
     * @return bool
     */
    public function open($session_name)
    {
        $this->throwIfOpen();

        $this->session_name = $session_name;

        // Apply settings defined in app config
        $this->applyAppConfig();

        // Save a reference to the session collection
        $this->collection = $this->mongoSource->getCollection(
            self::DB_COLLECTION_NAME
        );

        $id = $this->getSessionIdFromToken();

        // If an existing session ID was received in a token
        if (isset($id)) {
            // Use the existing session ID
            $this->session_id = $id;

            // Cache the hashed version of the session ID
            $this->session_id_hash = $this->hashSessionId($this->session_id);

            $this->updateLastRequestTimestamp();
        } else {
            // Generate a new session ID
            $this->session_id = self::generateSessionId();
            $this->insertSession(time());
        }

        // Create and set a cookie to be sent in the response
        $cookie = new \ox\lib\http\Cookie(
            $session_name,
            $this->generateToken(),
            0,
            '/'
        );
        $this->cookieManager->set($cookie);

        $this->checkGarbage();
    }

    /**
     * Get the value of a session variable.
     *
     * @param string $key The key of the session variable for which to query
     * @return mixed The value of the session variable, or null if the given
     *               key was not found
     */
    public function get($key)
    {
        $this->throwIfUnopened();
        self::throwOnInvalidKey($key);

        $query = [
            '_id' => $this->session_id_hash
        ];
        $fields = [
            self::buildSessionVariableKey($key) => true
        ];

        $doc = $this->collection->findOne($query, $fields);

        if (isset($doc[self::SESSION_VARIABLES_KEY][$key])) {
            return $doc[self::SESSION_VARIABLES_KEY][$key];
        } else {
            return null;
        }
    }

    /**
     * Set the value of a session variable.
     *
     * @param string $key
     * @param mixed $value
     * @return bool True if the write succeeded
     */
    public function set($key, $value)
    {
        $this->throwIfUnopened();
        self::throwOnInvalidKey($key);

        $criteria = [
            '_id' => $this->session_id_hash
        ];
        $new_object = [
            '$set' => [
                self::buildSessionVariableKey($key) => $value
            ]
        ];
        $options = [
            'upsert' => true,
            'w' => 1 // Acknowledged write
        ];

        $result = $this->collection->update($criteria, $new_object, $options);

        if (isset($result['ok']) && $result['ok']) {
            return true;
        } else {
            return false;
        }
    }


    /*************************************************************************/
    // Private Functions
    /*************************************************************************/

    /**
     * Return the properly formatted key to use for a session variable
     *
     * @return string
     */
    private static function buildSessionVariableKey($key)
    {
        return sprintf(
            '%s.%s',
            self::SESSION_VARIABLES_KEY,
            $key
        );
    }

    /**
     * Determine whether a given key is a valid key name for storage in Mongo
     * as a session variable.
     *
     * @return bool True if the key is valid
     */
    private static function keyIsValid($key)
    {
        if (is_string($key)
            && strpos($key, '.') === false
            && strpos($key, '$') === false
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return void
     * @throws SessionException
     */
    private static function throwOnInvalidKey($key)
    {
        if (!self::keyIsValid($key)) {
            throw new SessionException(self::INVALID_KEY_EXCEPTION_MESSAGE);
        }
    }

    /**
     * Check if it's time to perform garbage collection, and perform it if
     * necessary.
     *
     * @return bool True if garbage was collected
     */
    private function checkGarbage()
    {
        $cutoff = time() - $this->gc_interval;

        $query = ['_id' => self::GC_ID];
        $doc = $this->collection->findOne($query);

        if (isset($doc[self::GC_TIMESTAMP_KEY])) {
            $lastPerformed = $doc[self::GC_TIMESTAMP_KEY]->sec;
        } else {
            $lastPerformed = null;
        }

        // If there is no record of garbage collection ever happening before,
        // or if garbage collection has not been performed recently enough
        if (!isset($lastPerformed) || $lastPerformed <= $cutoff) {
            $this->collectGarbage();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Perform garbage collection for expired sessions.  The expired sessions
     * are deleted using an unacknowledged write concern, because in this case
     * performance is more important than receiving confirmation that the
     * removal(s) took place.
     *
     * @return void
     */
    private function collectGarbage()
    {
        Ox_Logger::logDebug(self::LOG_PREFIX . 'collecting garbage');

        // Record that we last collected garbage right now
        $now = time();
        $criteria = ['_id' => self::GC_ID];
        $new_object = [
            '$set' => [
                self::GC_TIMESTAMP_KEY => new \MongoDate($now)
            ]
        ];
        $options = ['upsert' => true];
        $this->collection->update($criteria, $new_object, $options);

        // Find sessions which were either created too long ago, or last used
        // too long ago
        $createdCutoff = time() - $this->max_session_age;
        $lastRequestCutoff = time() - $this->max_session_idle;

        $criteria = [
            self::SESSION_CREATED_KEY => [
                '$lt' => new \MongoDate($createdCutoff)
            ],
            self::SESSION_LAST_REQUEST_KEY => [
                '$lt' => new \MongoDate($lastRequestCutoff)
            ]
        ];
        $options = [
            'w' => 0 // Unacknowledged write
        ];
        $this->collection->remove($criteria, $options);
    }

    /**
     * Throw if a session is currently open.
     *
     * @return void
     * @throws SessionException
     */
    private function throwIfOpen()
    {
        if (isset($this->session_id)) {
            throw new SessionException(self::OPEN_EXCEPTION_MESSAGE);
        }
    }

    /**
     * Throw if no session is currently open.
     *
     * @return void
     * @throws SessionException
     */
    private function throwIfUnopened()
    {
        if (!isset($this->session_id)) {
            throw new SessionException(self::UNOPENED_EXCEPTION_MESSAGE);
        }
    }

    /**
     * @return string
     */
    private function hashSessionId($session_id)
    {
        return hash(self::SESSION_ID_HASH_ALGORITHM, $session_id);
    }

    /**
     * Insert a new session into the database.
     *
     * @param int $created Unix timestamp of the time of creation
     * @return bool True if the session was inserted successfully
     */
    private function insertSession($created)
    {
        // Cache the hashed version of the session ID
        $this->session_id_hash = $this->hashSessionId($this->session_id);

        $new_doc = [
            '_id' => $this->session_id_hash,
            self::SESSION_CREATED_KEY => new \MongoDate($created),
            self::SESSION_LAST_REQUEST_KEY => new \MongoDate($created)
        ];
        $options = [
            'w' => 1 // Acknowledged write
        ];

        $result = $this->collection->insert($new_doc, $options);

        if (isset($result['ok']) && $result['ok']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update the last_request timestamp for the current session.  The
     * timestamp is one of two used to find expired sessions when performing
     * garbage collection.  A session becomes idle if this timestamp does not
     * get updated for a certain length of time.
     *
     * @return void
     */
    private function updateLastRequestTimestamp()
    {
        $now = time();
        $criteria = ['_id' => $this->session_id_hash];
        $new_object = [
            '$set' => [
                self::SESSION_LAST_REQUEST_KEY => new \MongoDate($now)
            ]
        ];
        $options = ['upsert' => true];

        $this->collection->update($criteria, $new_object, $options);
    }

    /**
     * @return string Token ID if a valid existing token was received,
     *                otherwise null
     */
    private function getSessionIdFromToken()
    {
        // Check for an existing session token (received in a cookie)
        $rawToken = $this->cookieManager->getCookieValue($this->session_name);

        // If an existing token was received
        if (isset($rawToken)) {
            try {
                $token = new SessionTokenParser($rawToken);
            } catch (SessionException $exception) {
                // Log the exception
                Ox_Logger::logWarning(
                    self::LOG_PREFIX . $exception->getMessage()
                );

                // Do not use the existing token, but continue serving the
                // request
                return null;
            }

            // If the token signature is valid
            if ($this->validateTokenHmac($token)) {
                // If this session ID still exists in the database, and is not
                // expired
                if ($this->sessionExistsAndIsNotExpired($token->getSessionId())) {
                    return $token->getSessionId();
                }
            } else {
                // The HMAC token is invalid; log this and do not use the
                // existing token, but continue serving the request
                Ox_Logger::logWarning(
                    self::LOG_PREFIX . self::INVALID_TOKEN_HMAC_MESSAGE
                );
                return null;
            }
        }

        return null;
    }

    /**
     * @param string $session_id
     * @return bool True if the session ID exists and is not expired
     */
    private function sessionExistsAndIsNotExpired($session_id)
    {
        $now = time();
        $createdCutoff = $now - $this->max_session_age;
        $lastRequestCutoff = $now - $this->max_session_idle;

        $query = [
            '_id' => $this->hashSessionId($session_id),
            self::SESSION_CREATED_KEY => [
                '$gte' => new \MongoDate($createdCutoff)
            ],
            self::SESSION_LAST_REQUEST_KEY => [
                '$gte' => new \MongoDate($lastRequestCutoff)
            ],
        ];

        // Use find with limit instead of findOne, to improve performance since
        // we do not need to iterate through the cursor (we are only checking
        // that the record exists)
        $cursor = $this->collection->find($query)->limit(1);
        $count = $cursor->count();

        // If there were any results
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param SessionTokenParser $token
     * @return bool True if the token HMAC is valid, false otherwise
     */
    private function validateTokenHmac($token)
    {
        $freshHmac = $this->generateHmac($token->getSessionId());

        return self::compareHashes($token->getHmac(), $freshHmac);
    }

    /**
     * @return string
     */
    private static function generateSessionId()
    {
        return bin2hex(random_bytes(self::SESSION_ID_BYTE_LENGTH));
    }

    /**
     * @return string
     */
    private function generateHmac($tokenData)
    {
        return hash_hmac(
            self::TOKEN_HMAC_ALGORITHM,
            $tokenData,
            $this->token_hmac_key
        );
    }

    /**
     * Return a token which is the session ID, followed by a dot, followed by
     * an HMAC.
     *
     * @return string
     */
    private function generateToken()
    {
        $data = $this->session_id;
        $hmac = $this->generateHmac($data);

        return sprintf(
            '%s%s%s',
            $data,
            SessionTokenParser::DELIMITER,
            $hmac
        );
    }

    /**
     * Compare two hashes, using a constant-time comparison to protect against
     * timing attacks, i.e. all characters in the string are compared, without
     * stopping early in the case of a non-matching character.
     *
     * @param string $hash1
     * @param string $hash2
     * @return bool True if the hashes match, false otherwise
     */
    private function compareHashes($hash1, $hash2)
    {
        if (!is_string($hash1) || !is_string($hash2)) {
            return false;
        }

        // Store the length for use below in multiple places
        $len = strlen($hash1);

        if ($len != strlen($hash2)) {
            return false;
        }

        $difference = 0;
        for ($i = 0; $i < $len; $i++) {
            $difference |= ord($hash1[$i]) ^ ord($hash2[$i]);
        }

        return ($difference === 0);
    }

    /**
     * @return void
     */
    private function applyAppConfig()
    {
        $this->gc_interval = $this->getConfigValue(
            self::CONFIG_GC_INTERVAL_NAME,
            self::GC_INTERVAL_DEFAULT
        );

        $this->max_session_age = $this->getConfigValue(
            self::CONFIG_MAX_SESSION_AGE_NAME,
            self::MAX_SESSION_AGE_DEFAULT
        );

        $this->max_session_idle = $this->getConfigValue(
            self::CONFIG_MAX_SESSION_IDLE_NAME,
            self::MAX_SESSION_IDLE_DEFAULT
        );

        $this->token_hmac_key = $this->getConfigValue(
            self::CONFIG_TOKEN_HMAC_KEY_NAME,
            null
        );

        // If the token HMAC key is not set, it is not safe to continue, so
        // throw an exception
        if (!isset($this->token_hmac_key)) {
            throw new SessionException(
                self::MISSING_TOKEN_HMAC_KEY_EXCEPTION_MESSAGE
            );
        }
    }

    /**
     * @param string $configName
     * @param mixed $defaultValue
     * @return mixed The value corresponding to $configName if set in app
     *               config; otherwise $defaultValue
     */
    private function getConfigValue($configName, $defaultValue)
    {
        $value = $this->configParser->getAppConfigValue($configName);
        if (isset($value)) {
            return $value;
        } else {
            return $defaultValue;
        }
    }
}
