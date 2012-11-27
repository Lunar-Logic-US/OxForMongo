<?php
/**
 * This MongoDB session handler is intended to store any data you see fit.
 * One interesting optimization to note is the setting of the active flag
 * to 0 when a session has expired. The intended purpose of this garbage
 * collection is to allow you to create a batch process for removal of
 * all expired sessions. This should most likely be implemented as a cronjob
 * script.
 *
 * @author	Corey Ballou
 * @copyright	Corey Ballou (2010)
 *
 * 
 */

/**
 * Class to use a MongoDB database session.
 */
class Ox_MongoSessionManager
{
    /**
     * default config with support for multiple servers
     * (helpful for sharding and replication setups)
     */
    protected $_config = array(
        // cookie related vars
        //'cookie_path'   => '/',
        //'cookie_domain' => '.mydomain.com', // .mydomain.com
        // session related vars
        'lifetime'      => 3600,        // session lifetime in seconds
        'database'      => 'session',   // name of MongoDB database
        'collection'    => 'session',   // name of MongoDB collection

		// persistent related vars
		'persistent' 	=> false, 			// persistent connection to DB?
        'persistentId' 	=> 'MongoSession', 	// name of persistent connection
		
		// whether we're supporting replicaSet
		'replicaSet'		=> false,

		// array of mongo db servers
        'servers'   	=> array(
            array(
                'host'          => Mongo::DEFAULT_HOST,
                'port'          => Mongo::DEFAULT_PORT,
                'username'      => null,
                'password'      => null
            )
        )
    );

    /**
     * Stores the connection.
     */
    protected $_connection;

    /**
     * Stores the mongo db.
     */
    protected $_mongo;

    /**
     * Stores session data results.
     */
    protected $_session = FALSE;
    
    /**
     * Flag to indicate if the session has been stopped
     */
    protected $_stopped = FALSE;
    
    /**
     * Locks the session for writing.
     */
    protected  $_do_not_write = FALSE;
    
    /**
     * Default constructor.
     *
     * @access  public
     * @param   array   $config
     */
    public function __construct($config = array())
    {
        // initialize the database
        $this->_init(empty($config) ? $this->_config : $config);

        // set object as the save handler
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );

        // set some important session vars
        ini_set('session.auto_start',               0);
        ini_set('session.gc_probability',           1);
        ini_set('session.gc_divisor',               100);
        ini_set('session.gc_maxlifetime',           $this->_config['lifetime']);
        ini_set('session.referer_check',            '');
        ini_set('session.entropy_file',             '/dev/urandom');
        ini_set('session.entropy_length',           16);
        ini_set('session.use_cookies',              1);
        ini_set('session.use_only_cookies',         1);
        ini_set('session.use_trans_sid',            0);
        ini_set('session.hash_function',            1);
        ini_set('session.hash_bits_per_character',  5);

        // disable client/proxy caching
        session_cache_limiter('nocache');
        
        // set the cookie parameters
        $domain=null;
        if (isset($this->_config['cookie_domain'])) {
            $domain=$this->_config['cookie_domain'];
        }
        $path = null;
        if (isset($this->_config['cookie_path'])) {
            $path=$this->_config['cookie_path'];
        } else {
            $this->_config['cookie_path'] = '/';
            $path='/';
        }

        session_set_cookie_params(
			$this->_config['lifetime'],
			$path,
			$domain
		);

        $this->session_name =  'mongo_sess';
        // name the session
        session_name($this->session_name);
        register_shutdown_function('session_write_close');

        // start it up
        session_start();
    }

    /**
     * Initialize MongoDB. There is currently no support for persistent
     * connections.  It would be very easy to implement, I just didn't need it.
     *
     * @access  private
     * @param   array   $config
     */
    private function _init($config)
    {
        // ensure they supplied a database
        if (empty($config['database'])) {
            throw new Exception('You must specify a MongoDB database to use for session storage.');
        }
        
        if (empty($config['collection'])) {
            throw new Exception('You must specify a MongoDB collection to use for session storage.');
        }
        
        // update config
        $this->_config = $config;
        
        // generate server connection strings
        $connections = array();
        if (!empty($this->_config['servers'])) {
            foreach ($this->_config['servers'] as $server) {
                $str = '';
                if (!empty($server['username']) && !empty($server['password'])) {
                    $str .= $server['username'] . ':' . $server['password'] . '@';
                }
				$str .= !empty($server['host']) ? $server['host'] : Mongo::DEFAULT_HOST;
                $str .= ':' . (!empty($server['port']) ? (int) $server['port'] : Mongo::DEFAULT_PORT);
                array_push($connections, $str);
            }
        } else {
            // use default connection settings
            array_push($connections, Mongo::DEFAULT_HOST . ':' . Mongo::DEFAULT_PORT);
        }
        
		// add immediate connection
		$opts = array('connect' => true);
		
		// support persistent connections
		if ($this->_config['persistent'] && !empty($this->_config['persistentId'])) {
            $opts['persist'] = $this->_config['persistentId'];
        }

	// support replica sets
	if ($this->_config['replicaSet']) {
	    $opts['replicaSet'] = true;
	}
	
	// load mongo server connection
	try {
	    $this->_connection = new Mongo('mongodb://' . implode(',', $connections), $opts);
	} catch (Exception $e) {
	    throw new Exception('Can\'t connect to the MongoDB server.');
	}
        
        // load the db
        try {
            $mongo = $this->_connection->selectDB($this->_config['database']);
        } catch (InvalidArgumentException $e) {
            throw new Exception('The MongoDB database specified in the config does not exist.');
        }
        
        // load collection
        try {
            $this->_mongo = $mongo->selectCollection($this->_config['collection']);
        } catch(Exception $e) {
            throw new Exception('The MongoDB collection specified in the config does not exist.');
        }
        
        // proper indexing on the expiration
        $this->_mongo->ensureIndex(
		array('expiry' => 1),
		array('name' => 'expiry',
				'unique' => false,
				'dropDups' => true,
				'safe' => true,
				'sparse' => true,
		)
	);
		
	// proper indexing of session id and lock
	$this->_mongo->ensureIndex(
		array('session_id' => 1, 'lock' => 1),
		array('name' => 'session_id',
			  'unique' => true,
			  'dropDups' => true,
			  'safe' => true
		)
	);
    }

    /**
     * Open does absolutely nothing as we already have an open connection.
     *
     * @access  public
     * @return	bool
     *
     * @param	string	$save_path
     * @param	string	$session_name
     */
    public function open($save_path, $session_name)
    {
        return true;
    }

    /**
     * Close does absolutely nothing as we can assume __destruct handles
     * things just fine.
     *
     * @access  public
     * @return	bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Read the session data.
     *
     * @access	public
     * @param	string	$id
     * @return	string
     */
    public function read($id)
    {
	// obtain a read lock on the data, or subsequently wait for
	// the lock to be released
	if ($this->_lock($id)) {
            // exclude results that are inactive or expired
            $result = $this->_mongo->findOne(
                array(
                    'session_id'	=> $id,
                    'expiry'    	=> array('$gte' => time()),
                    'active'    	=> 1
                )
            );

            if (isset($result['data'])) {
                $this->_session = $result;
                return $result['data'];
            }
        }
        return '';
   }

    /**
     * Atomically write data to the session, ensuring we remove any
     * read locks.
     *
     * @access  public
     * @param   string  $id
     * @param   mixed   $data
     * @return	bool
     */
    public function write($id, $data)
    {
        if ($this->_do_not_write) {
            return TRUE;
        }
        //print "<hr>session writting!!!<hr>";
        // create expires
        $expiry = time() + $this->_config['lifetime'];

        // create new session data
        $new_obj = array(
            'data'	=> $data,
	    'lock'	=> 0,
            'active'	=> 1,
            'expiry'	=> $expiry
        );
        
        // check for existing session for merge
        if (!empty($this->_session)) {
            $obj = (array) $this->_session;
            unset ($obj['_id']);
            $new_obj = array_merge($obj, $new_obj);
        }

        // atomic update
	$query = array('session_id' => $id);
		
	// update options
	$options = array(
	    'upsert' 	=> TRUE,
	    'safe'		=> TRUE,
	    'fsync'		=> FALSE
	);
  
	// perform the update or insert
        try {
	    $result = $this->_mongo->update($query, array('$set' => $new_obj), $options);
	    return $result['ok'] == 1;
	} catch (Exception $e) {
	    throw $e;
	    return false;
	}	
        return true;
    }

    /**
     * Destroys the session by removing the document with
     * matching session_id.
     *
     * @access  public
     * @param   string  $id
     * @return  bool
     */
    public function destroy($id)
    {
        $this->_mongo->remove(array('session_id' => $id), true);
        return true;
    }

    /**
     * Garbage collection. Remove all expired entries atomically.
     *
     * @access  public
     * @return	bool
     */
    public function gc()
    {
		// define the query
		$query = array('expiry' => array('$lt' => time()));
		
		// specify the update vars
		$update = array('$set' => array('active' => 0));
		
		// update options
		$options = array(
			'multiple'	=> TRUE,
			'safe'		=> TRUE,
			'fsync'		=> FALSE
		);
		
		// update expired elements and set to inactive
		$this->_mongo->update($query, $update, $options);

		return true;
    }
	
    /**
     * Solves issues with write() and close() throwing exceptions.
     *
     * @access	public
     * @return	void
     */
    public function __destruct()
    {
	//print "session destruction!!!";
	if (!$this->_stopped) {
		    session_write_close();
	}
    }
    
    /**
     * Return the expiry value for the current session.
     *
     * @return int
     */
    public function get_expiry()
    {
        //print "session destruction!!!";
        if ($this->_session !==FALSE) {
            return $this->_session['expiry'];
        }
        return 0;
    }

    /*
     * Creates a new session ID.
     *
     * @return boolean
     */
    public function regenerate_id()
    {
        global $logger;
        $old_id = session_id();
        $success = session_regenerate_id();
        if ($success) {
            $new_id = session_id();
            $where = array('session_id' => $new_id);
            $document = $this->_mongo->findOne($where);
            $this->_session['_id'] = $document['_id'];
            $this->_session['session_id'] = $new_id;
            Ox_Logger::logMessage('Regnerating Session ID to: ' . $new_id);
            $this->_mongo->remove(array('session_id' => $old_id), true);
        }
        return $success;
    }
    
    /*
     * Stops the current session, destroying it.
     *
     * @return boolean
     */
    public function stop()
    {
        $this->_stopped = TRUE;
        $success = session_destroy();
        return $success;
    }

    /**
    * Create a global lock for the specified document.
    *
    * @author	Benson Wong (mostlygeek@gmail.com)
    * @access	private
    * @param	string	$id
    */
    private function _lock($id)
    {
	global $logger;
	global $router;
	$remaining = 30000000;
	$timeout = 5000;
    
	// Check for a row.
	$result = $this->_mongo->findOne(
	     array(
		'session_id'  => $id,
		'expiry'      => array('$gte' => time()),
		'active'      => 1
	    )
	);
    
	Ox_Logger::logMessage('SESSION - _lock - Trying to lock:'. $id );
	if (!empty($result)) {
	    do {
	
		try {
	
		    $query = array('session_id' => $id, 'lock' => 0);
		    $update = array('$set' => array('lock' => 1));
		    $options = array('safe' => true, 'upsert' => true);
		    $result = $this->_mongo->update($query, $update, $options);
		    if ($result['ok'] == 1) {
			return true;
		    }
	
		} catch (MongoCursorException $e) {
		    if (substr($e->getMessage(), 0, 26) != 'E11000 duplicate key error') {
			throw $e; // not a dup key?
		    }
		}
	
		// force delay in microseconds
		usleep($timeout);
		$remaining -= $timeout;
	
		// backoff on timeout, save a tree. max wait 1 second
		$timeout = ($timeout < 1000000) ? $timeout * 2 : 1000000;
	
	    } while ($remaining > 0);
	    // session is still locked.... This should not happen, but if it does clear the session.
	    Ox_Logger::logMessage('SESSIOM - _lock - Could not obtain a session lock. Clearing the session !!!!!!!!!!!!!');
	    unset($query['lock']);
	    $this->_mongo->remove($query);
	    unset ($_COOKIE[$this->session_name]);
	    setcookie($this->session_name,null,-1,$this->_config['cookie_path']);
	    $this->_do_not_write = TRUE;
	    //throw new Exception('Could not obtain a session lock.');
	}
	return FALSE;
    }
}
