<?php

namespace ox\lib\session_handlers;

use \ox\lib\http\CookieManager;
use \ox\lib\exceptions\SessionException;
use \Ox_Logger;
use \Ox_LibraryLoader;

class MongoSessionHandler implements
    \ox\lib\interfaces\SessionHandler,
    \ox\lib\interfaces\KeyValueStore
{
    use \ox\lib\traits\Singleton;

    const COLLECTION_NAME = 'ox_session';
    const SESSION_TIMESTAMP_KEY = 'last_updated';
    const SESSION_VARIABLES_KEY = 'variables';
    const GC_ID = 'garbage_collection';
    const GC_TIMESTAMP_KEY = 'last_performed';

    const UNOPENED_EXCEPTION_MESSAGE = 'Session has not been opened yet';
    const INVALID_KEY_EXCEPTION_MESSAGE = 'Key contains invalid characters';

    private $collection;
    private $gc_max_session_age = 86400; // 24 hours
    private $gc_period = 3600; // 1 hour
    private $session_id; // MongoId
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

        $criteria = ['_id' => new \MongoId($this->session_id)];
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
            self::COLLECTION_NAME
        );

        // Check for an existing session ID (received in a cookie)
        $id = $this->cookieManager->getCookieValue($session_name);
        if (isset($id)) {
            Ox_Logger::logDebug("MongoSessionHandler: received existing cookie");

            if (\MongoId::isValid($id)) {
                $this->session_id = new \MongoId($id);
            } else {
                Ox_Logger::logDebug(
                    sprintf(
                        "MongoSessionHandler: cookie's existing session id is not valid: '%s'",
                        $id
                    )
                );
            }
        } else {
            Ox_Logger::logDebug("MongoSessionHandler: no cookie was received");
        }

        // If there is no existing session ID, generate a new one
        if (!isset($this->session_id)) {
            $this->session_id = new \MongoId();
        }

        // Create and set a cookie to be sent in the response
        $cookie = new \ox\lib\http\Cookie(
            $session_name,
            $this->session_id->__toString(),
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
            '_id' => new \MongoId($this->session_id)
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
            '_id' => new \MongoId($this->session_id)
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
        $criteria = ['_id' => new \MongoId($this->session_id)];
        $new_object = [
            '$set' => [
                self::SESSION_TIMESTAMP_KEY => new \MongoDate($now)
            ]
        ];
        $options = ['upsert' => true];

        $this->collection->update($criteria, $new_object, $options);
    }
}
