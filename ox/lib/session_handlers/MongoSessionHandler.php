<?php

namespace ox\lib\session_handlers;

class MongoSessionHandler implements \ox\lib\interfaces\KeyValueStore
{
    use \ox\lib\traits\Singleton;

    const COLLECTION_NAME = 'ox_session';
    const SESSION_TIMESTAMP_KEY = 'timestamp';
    const SESSION_VARIABLES_KEY = 'variables';
    const GC_ID = 'garbage_collection';
    const GC_TIMESTAMP_KEY = 'last_performed';

    private $collection;
    private $gc_max_session_age = 86400; // 24 hours
    private $gc_period = 3600; // 1 hour
    private $session_id; // MongoId
    private $session_name;

    public function __construct()
    {
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
            'w' => 1
        ];
        $result = $this->collection->remove($criteria, $options);

        if (isset($result['ok']) && $result['ok']) {
            \Ox_Logger::logDebug('MongoSessionHandler: successfully closed session');

            \ox\lib\http\CookieManager::delete($this->session_name, '/');
            $this->session_id = null;
            $this->collection = null;

            return true;
        } else {
            \Ox_Logger::logDebug('MongoSessionHandler: failed to close session');
            return false;
        }
    }

    /**
     * @param string $save_path
     * @param string $session_name
     * @return bool
     */
    public function open($session_name)
    {
        \Ox_Logger::logDebug('MongoSessionHandler: session opened');

        $this->session_name = $session_name;

        // Establish a database connection
        $db = \Ox_LibraryLoader::db();

        // Save a reference to the session collection
        $this->collection = $db->getCollection(self::COLLECTION_NAME);

        // Check for an existing session ID (received in a cookie)
        $id = \ox\lib\http\CookieManager::getCookieValue($session_name);
        if (isset($id)) {
            \Ox_Logger::logDebug("MongoSessionHandler: received existing cookie");

            if (\MongoId::isValid($id)) {
                $this->session_id = new \MongoId($id);
            } else {
                \Ox_Logger::logDebug(
                    sprintf(
                        "MongoSessionHandler: cookie's existing session id is not valid: '%s'",
                        $id
                    )
                );
            }
        } else {
            \Ox_Logger::logDebug("MongoSessionHandler: no cookie was received");
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
        \ox\lib\http\CookieManager::set($cookie);

        $this->updateTimestamp();
        $this->checkGarbage();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->throwIfUnopened();

        \Ox_Logger::logDebug(
            sprintf(
                'MongoSessionHandler: get "%s"',
                $key
            )
        );
        \Ox_Logger::logDebug(
            sprintf(
                'MongoSessionHandler: session_id: "%s"',
                $this->session_id
            )
        );

        if (self::keyIsValid($key)) {
            $query = [
                '_id' => new \MongoId($this->session_id)
            ];
            $fields = [
                self::SESSION_VARIABLES_KEY . '.' . $key => true
            ];

            $doc = $this->collection->findOne($query, $fields);

            if (isset($doc[self::SESSION_VARIABLES_KEY][$key])) {
                return $doc[self::SESSION_VARIABLES_KEY][$key];
            } else {
                return null;
            }
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set($key, $value)
    {
        $this->throwIfUnopened();

        \Ox_Logger::logDebug(
            sprintf(
                'MongoSessionHandler: set "%s" = "%s"',
                $key,
                $value
            )
        );
        \Ox_Logger::logDebug(
            sprintf(
                'MongoSessionHandler: session_id: "%s"',
                $this->session_id
            )
        );

        if (self::keyIsValid($key)) {
            $criteria = [
                '_id' => new \MongoId($this->session_id)
            ];

            $new_object = [
                '$set' => [
                    self::SESSION_VARIABLES_KEY . '.' . $key => $value
                ]
            ];

            $options = [
                'upsert' => true
            ];

            $this->collection->update($criteria, $new_object, $options);
        } else {
            throw new ox\lib\exceptions\SessionException(
                sprintf(
                    'Key contains invalid characters: "%s"',
                    (string) $key
                )
            );
        }
    }


    /*************************************************************************/
    // Private Functions
    /*************************************************************************/

    /**
     * Determine whether a given key is a valid key name for storage in Mongo
     * as a session variable.
     *
     * @return bool True if the key is valid
     */
    private function keyIsValid($key)
    {
        if (is_string($key)
            && strpos($key, '.') === false
            && strpos($key, '$') === false
            && strpos($key, ' ') === false) {
            return true;
        } else {
            return false;
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
     * Perform garbage collection for expired sessions.
     *
     * @return void
     */
    private function collectGarbage()
    {
        \Ox_Logger::logDebug('MongoSessionHandler: collecting garbage');

        // Remove sessions older than gc_max_session_age
        $cutoff = time() - $this->gc_max_session_age;
        $criteria = [
            self::SESSION_TIMESTAMP_KEY => ['$lte' => new \MongoDate($cutoff)]
        ];
        $this->collection->remove($criteria);

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
            throw new \ox\lib\exceptions\SessionException(
                'MongoSessionHandler: Session has not been opened yet'
            );
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
