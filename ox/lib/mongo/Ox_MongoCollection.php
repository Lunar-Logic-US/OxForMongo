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
 * Facade to the PHP MongoDB Collection and released Ox_Schema
 *
 * This adds validation and ID utility functions
 */
class Ox_MongoCollection
{
    /**
     * The underlying MongoCollection object.
     *
     * @var MongoCollection
     */
    public $_mongoCollection;

    /**
     * Directory where schemas are stored
     * @var string
     */
    private $_schemaDirectory;

    /**
     * Schema for this collecting, if exists
     * @var Ox_Schema
     */
    private $_schema = null;

    /**
     * Name of this collection
     * @var string
     */
    private $_collectionName = null;

    /**
     * Setup the collection and load the schema
     * @param $p_mongoCollection
     */
    public function __construct($p_mongoCollection)
    {
        $this->_mongoCollection = $p_mongoCollection;
        $this->_collectionName = $p_mongoCollection->getName();
        $this->_schemaDirectory = DIR_APP . 'schemas' . DIRECTORY_SEPARATOR;
        $this->_loadSchema();
    }

    // ------------------------------------
    // MongoCollection Facade
    // ------------------------------------

    /**
     * Find doc with _id of string|MongoId of p_id
     * @param $id string|MongoId
     * @return array|null
     */
    public function findById($id)
    {
        if ($id instanceof MongoId) {
            $mongoId = $id;
        } elseif ($id instanceof string && preg_match('/^[0-9A-F]{24}\z/i',$id)) {
            $mongoId = new MongoId($id);
        } else {
            $mongoId = mongoId(idString($id));
        }
        
        return $this->_mongoCollection->findOne(array('_id' => $mongoId));
    }

    /**
     * Passes all non-defined calls to the MongoCollection Object
     * @param $name string
     * @param $params array
     * @return mixed
     */
    public function __call($name, $params)
    {
        return call_user_func_array(array($this->_mongoCollection, $name), $params);
    }

    /**
     * This is essentially a SQL GROUP BY command.
     *
     * @param $filter array         standard Mongo filter array for the collection.
     * @param $groupByField string  field to group on.
     * @param $resultName string    array key for all the documents matching the filter.
     * @return mixed
     */
    public function groupBy( $filter, $groupByField, $resultName )
    {
        $keys = array( $groupByField => 1 );
        $initial = array( $resultName => array());
        $criteria = array(
            'condition' => $filter,
        );
        $reduce = "function (obj, prev) { prev.$resultName.push(obj); }";

        $result = $this->_mongoCollection->group( $keys, $initial, $reduce, $criteria );

        return $result['retval'];
    }

    /**
     * Overrides the Mongo insert to allow for validation.
     *
     * @param array $a
     * @param array $options
     * @return array|bool
     */
    public function insert (array $a, array $options = array())
    {

        if ($this->_schema !== null && $this->_schema->checkOnInsert()) {
            $this->_schema->isValid($a);
        }
        return $this->_mongoCollection->insert($a,$options);
    }

    // ------------------------------------
    // Schema Facade
    // ------------------------------------

    /**
     * Load the schema class if it available.
     */
    private function _loadSchema()
    {
        if (!$this->_schema) {
            $schemaClass = $this->_collectionName . 'Schema';
            Ox_LibraryLoader::loadCode($schemaClass,array($this->_schemaDirectory),false);
            if (class_exists($schemaClass)) {
                $this->_schema = new $schemaClass();
            } else {
                $this->_schema = null;
            }
        }
    }

    /**
     * @param array $doc
     * @return boolean
     */
    public function isValid(array $doc)
    {
        if ($this->_schema !== null) {
            return $this->_schema->isValid($doc);
        } else {
            return true;
        }
    }

    /**
     * Check and fill the give doc with defaults.
     *
     * @param array $doc
     * @param bool $onMissing
     * @param bool $onInvalid
     * @return mixed
     */
    public function fillWithDefaults(array $doc=array(),$onMissing=true,$onInvalid=true)
    {
        if ($this->_schema!==null) {
            return $this->_schema->fillWithDefaults($doc, $onMissing, $onInvalid);
        } else {
            return $doc;
        }
    }

    /**
     * Return collected error on the schema
     * @return array
     */
    public function getErrors()
    {
        if ($this->_schema!== null) {
            return $this->_schema->getErrors();
        } else {
            return array();
        }
    }
    /*
    public function getSchema()
    {
        $this->_loadSchema();
        return $this->_schema;
    }

    */

}
