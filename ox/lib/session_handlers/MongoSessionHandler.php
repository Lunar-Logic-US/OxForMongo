<?php

namespace ox\lib\session_handlers;

use \ox\lib\http\CookieManager;
use \ox\lib\exceptions\SessionException;
use \Ox_Logger;
use \Ox_LibraryLoader;

class MongoSessionHandler extends \ox\lib\abstract_classes\SessionHandler
{
    use \ox\lib\traits\Singleton;

    const DB_COLLECTION_NAME = 'ox_session';
    const SESSION_TIMESTAMP_KEY = 'last_updated';
    const SESSION_VARIABLES_KEY = 'variables';
    const GC_ID = 'garbage_collection';
    const GC_TIMESTAMP_KEY = 'last_performed';

    const UNOPENED_EXCEPTION_MESSAGE = 'Session has not been opened yet';
    const INVALID_KEY_EXCEPTION_MESSAGE = 'Key contains invalid characters';
    const INVALID_TOKEN_HMAC_EXCEPTION_MESSAGE =
        'Session token HMAC is invalid';

    private $collection;
    private $gc_max_session_age = 86400; // 24 hours
    private $gc_period = 3600; // 1 hour

    /** @var string The unique identifier of the session */
    private $session_id;

    /** @var string The name of the cookie which stores the session token */
    private $session_name;

    /**
     * @var CookieManager Used for all CookieManager calls, to facilitate mocks
     *                    in unit tests
     */
    private $cookieManager;

    /**
     * @var Ox_MongoSource Used for all Mongo calls, to facilitate mocks in
     *                     unit tests
     */
    private $mongo;

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
        // Use the default MongoSource
        $this->setMongoSource(Ox_LibraryLoader::db());

        // Use the default cookie manager
        $this->setCookieManager(new CookieManager());
    }

    /**
     * Close the session, destroying it.
     *
     * @return bool True if the session was successfully destroyed
     */
    public function close()
    {
        $this->throwIfUnopened();

        $criteria = ['_id' => $this->session_id];
        $options = [
            'justOne' => true,
            'w' => 1 // Acknowledged write
        ];
        $result = $this->collection->remove($criteria, $options);

        if (isset($result['ok']) && $result['ok']) {
            Ox_Logger::logDebug('MongoSessionHandler: successfully closed session');

            $this->cookieManager->delete($this->session_name, '/');
            $this->session_id = null;
            $this->collection = null;

            return true;
        } else {
            Ox_Logger::logDebug('MongoSessionHandler: failed to close session');
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
        Ox_Logger::logDebug('MongoSessionHandler: session opened');

        $this->session_name = $session_name;

        // Save a reference to the session collection
        $this->collection = $this->mongoSource->getCollection(
            self::DB_COLLECTION_NAME
        );

        $id = $this->getSessionIdFromToken();

        // If an existing session ID was received in a token
        if (isset($id)) {
            // Use the existing session ID
            $this->session_id = $id;
        } else {
            // Generate a new session ID
            $this->session_id = self::generateSessionId();
        }

        // Create and set a cookie to be sent in the response
        $cookie = new \ox\lib\http\Cookie(
            $session_name,
            $this->generateToken(),
            0,
            '/'
        );
        $this->cookieManager->set($cookie);

        $this->updateTimestamp();
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
            '_id' => $this->session_id
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
            '_id' => $this->session_id
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
        $cutoff = time() - $this->gc_period;

        $query = ['_id' => self::GC_ID];
        $doc = $this->collection->findOne($query);

        if (isset($doc[self::GC_TIMESTAMP_KEY])) {
            $lastPerformed = $doc[self::GC_TIMESTAMP_KEY]->sec;
        } else {
            $lastPerformed = 0;
        }

        // If there is no document, or if garbage collection has not been
        // performed in a long time
        if ($lastPerformed <= $cutoff) {
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
        Ox_Logger::logDebug('MongoSessionHandler: collecting garbage');

        // Remove sessions older than gc_max_session_age
        $cutoff = time() - $this->gc_max_session_age;
        $criteria = [
            self::SESSION_TIMESTAMP_KEY => ['$lte' => new \MongoDate($cutoff)]
        ];
        $options = [
            'w' => 0 // Unacknowledged write
        ];
        $this->collection->remove($criteria, $options);

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
    }

    /**
     * Throw if the session has not been opened.
     *
     * @throws SessionException
     */
    private function throwIfUnopened()
    {
        if (!isset($this->session_id)) {
            throw new SessionException(self::UNOPENED_EXCEPTION_MESSAGE);
        }
    }

    /**
     * Update the timestamp for the current session.  The timestamp is used to
     * find expired sessions when performing garbage collection.
     *
     * @return void
     */
    private function updateTimestamp()
    {
        $now = time();
        $criteria = ['_id' => $this->session_id];
        $new_object = [
            '$set' => [
                self::SESSION_TIMESTAMP_KEY => new \MongoDate($now)
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
        $token = new SessionTokenParser($rawToken);

        // If the token signature is valid
        if ($this->validateTokenHmac($token)) {
            // If this token still exists in the database
            if (!$this->tokenExists($token->getSessionId())) {
                // If the token is not expired
                if (!$this->tokenIsExpired($token->getSessionId())) {
                    // TODO: return the ID part of the token
                    return $token->getSessionId();
                }
            }
        } else {
            throw new SessionException(
                INVALID_TOKEN_HMAC_EXCEPTION_MESSAGE
            );
        }

        return null;
    }


    /**
     * @param SessionTokenParser $token
     * @return bool True if the token HMAC is valid
     */
    private function validateTokenHmac($token)
    {
        $token->getHmac();
    }

    /**
     * @return string
     * @todo
     */
    private static function generateSessionId()
    {
        // TODO; use a PRNG to generate a pseudo-random number which is less
        // predictable than a MongoID.  A MongoID is used here for prototype
        // purposes only.
        $mongoId = new \MongoId();
        return $mongoId->__toString();
    }

    /**
     * Return a token which is the session ID, followed by a dot, followed by
     * an HMAC.
     *
     * @return string
     */
    private function generateToken()
    {
        // TODO: get secret from app.php
        $secret = '*whisperwhisper*';

        $data = $this->session_id;
        $hmac = hash_hmac('sha256', $data, $secret);

        return sprintf(
            '%s.%s',
            $data,
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
        $len = strllen($hash1);

        if ($len != strlen($hash2)) {
            return false;
        }

        $difference = 0;
        for ($i = 0; $i < $len; $i++) {
            $difference |= ord($hash1[$i]) ^ ord($hash2[$i]);
        }

        return ($difference === 0);
    }
}
