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
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Mongo
 */

/**
 * Facade to the PHP MongoDB Collection and released Ox_Schema
 *
 * This adds validation and ID utility functions
 * @package Ox_Mongo
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
     * @param MongoCollection $mongoCollection
     */
    public function __construct(MongoCollection $mongoCollection)
    {
        $this->_mongoCollection = $mongoCollection;
        $this->_collectionName = $mongoCollection->getName();
        $this->_schemaDirectory = DIR_APP . 'schemas' . DIRECTORY_SEPARATOR;
        $this->_loadSchema();
    }

    /**
     * Take a string, array, MongoId return proper MongoId.
     *
     * @param string|MongoId|array $idIn
     * @return MongoId
     */
    public static function normalizeId($idIn)
    {
        //General case  if idIn is a mongoId or if it is just a string
        $normalizedId = $idIn;

        if (is_array($idIn) && isset($idIn['_id'])) {
            //We have been given a whole record and
            $normalizedId = $idIn['_id'];
        } elseif (is_string($idIn) && preg_match('/^[0-9A-F]{24}\z/i',$idIn)) {
            //we have the string representation of a MongoId, make a mongoID
            //We do the regex and the try because we do not know which version of the
            //PHP Mongo driver that we will be dealing with.
            try {
                $normalizedId = new MongoId($idIn);
            } catch (Exception $e) {
                //do nothing it must be a string _id
                //Really we should not get here, because of the regex.
            }
        }

        return $normalizedId;
    }

    // ------------------------------------
    // MongoCollection Facade
    // ------------------------------------


    /**
     * Find doc with id
     *
     * @param string|MongoId|array $id
     * @param array $fieldsToRetrieve
     * @return array|null
     */
    public function findById($id,array $fieldsToRetrieve=array())
    {
        $mongoId = self::normalizeId($id);
        return $this->_mongoCollection->findOne(array('_id' => $mongoId),$fieldsToRetrieve);
    }

    /**
     * Update a doc with id
     *
     * @param string | MongoID $id
     * @param array $updateArray
     * @param array $options
     * @return mixed
     */
    public function updateById($id,array $updateArray,array $options=array())
    {
        $mongoId = self::normalizeId($id);
        $criteria = array('_id'=>$mongoId);
        return $this->_mongoCollection->update($criteria,$updateArray,$options);
    }

    /**
     * Passes all non-defined calls to the MongoCollection Object
     * @param $name string
     * @param $params array
     * @return MongoCollection
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
     * Return if the $doc is valid based on this schema.
     *
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
