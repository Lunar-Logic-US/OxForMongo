<?php 

class Ox_MongoDelayedCursor
{
    public $_collection;
    
    private $_filter;
    
    private $_options = array();
    
    public function __construct($collection, $filter)
    {
        $this->_collection = $collection;
        $this->_filter = $filter;
    }
    
    public function execute()
    {
        return $this->_collection->find($this->_filter, $this->_options);
    }
    
    public function __call($name, $params)
    {
        $this->_options[$name] = $params[0];
        return $this;
    }
}

?>