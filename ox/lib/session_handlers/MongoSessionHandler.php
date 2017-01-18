<?php

namespace ox\lib\session_handlers;

class MongoSessionHandler implements
    \SessionHandlerInterface,
    \ox\lib\interfaces\KeyValueStore
{
    use \ox\lib\traits\Singleton;

    const COLLECTION_NAME = 'session';

    private $collection;
    private $session_id;

    /**
     * @return bool
     */
    public function close()
    {
    }

    /**
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
    }

    /**
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
    }

    /**
     * @param string $save_path
     * @param string $session_name
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        // Establish a database connection
        $db = \Ox_LibraryLoader::db();

        // Save a reference to the session collection
        $this->collection = $db->${COLLECTION_NAME};

        // TODO: set session ID
        // TODO: set cookie
        $this->$session_id = new MongoId();

        // Check for an existing session cookie
        $cookie = null;
        $cookie = ox\lib\Cookie::get($session_name);

        // If there is an existing session cookie
        if (isset($cookie)) {
            $this->session_id = $cookie->getValue();
        } else {
            // If there is no existing session cookie, generate a new one
            $this->session_id = new MongoId();

            $cookie = new ox\lib\Cookie(
                $session_name,
                $this->session_id
            );
        }

        // Send the cookie for this response
        $cookie->send();
    }

    /**
     * @param string $session_id
     * @return string
     */
    public function read($session_id)
    {
        //$query = [
        //    '_id' => $session_id,
        //];

        //$this->collection->findOne($query);
    }

    /**
     * @param string $session_id
     * @param string $session_data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $query = [
            '_id' => $this->session_id,
        ];
        $fields = [$key => true];

        $this->db->findOne($query, $fields);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set($key, $value)
    {
        if (keyIsValid($key)) {
            $query = [
                '_id' => $this->session_id,
                $key
            ];

            $this->collection->upsert($query);
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
     * @return bool
     */
    private function keyIsValid($key)
    {
        if (is_string($key)
            && strpos($key, '.') === false
            && strpos($key, '$') === false) {
            return true;
        } else {
            return false;
        }
    }

    private function setCookie()
    {
        //$_COOKIE[$this->session_id];

        if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
            throw new ox\lib\exceptions\SessionException(
                sprintf(
                    'Session cannot be opened because cookies were already '
                    . 'sent in file "%s" on line $line',
                    $file,
                    $line
                )
            );
        }

        setcookie($cookie);
    }
}