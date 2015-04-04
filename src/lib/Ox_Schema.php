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
 * @package Ox_Schema
 */


/**
 * Abstract class for creating schemas.
 *
 * <pre><code>
 * class NewSchema extends Ox_Schema
 * {
 *     public function __construct() {
 *          $this->_schema = array(
 *              <schema definitions>,
 *          );
 *      }
 * }
 * </code></pre>
 * What I want:
 *
 * To test for required
 * To test for valid - is is only if the field exists in the array, NOT if the value is empty.
 * To "fill in" defaults
 *
 * Scheme definition is one of:
 *      field => array ( '__validator => <Must from interface Ox_Validoator> , '__required' => <true | false>, '__default' => <simple type> )
 *      object => array( '__required', 'blah')
 *      array =>  array( '__required', _fields)
 *
 * Examples of use:
 * <code>
 *     $schema->isFieldValid('field',$value);
 *     $schema->sanitize($wholeDocument);
 *     $schema->sanitizeField('fieldName',$value);
 * </code>
 * @package Ox_Schema
 */
abstract class Ox_Schema
{
    /**
     * Enable/Disable Debugging for this object.
     */
    const DEBUG = TRUE;
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';

    /**
     * Holds the schema definition.
     * @var array
     */
    protected $_schema;

    // ---- Flags ----

    /**
     * Flag -- Replace invalid entries with the default.
     * @var bool
     */
    protected $_replaceOnInvalid=false;

    /**
     * Flag -- Replace missing entries with the default.
     * @var bool
     */
    protected $_injectOnMissing=false;

    /**
     * Flag -- Replace missing entries with the default.
     * @var bool
     */
    protected $_sanitize=false;


    /**
     * Flag -- Replace missing entries with the default.
     * @var bool
     */
    protected $_onInsert = false;

    /**
     * Flag -- Must exactly match the schema
     *
     * No missing, no additions
     * @var bool
     */
    protected $_strict = FALSE;


    // ---- Internal ----
    /**
     * Internal - Set if we have hit a required field
     * @var
     */
    protected $_required;

    /**
     * Internal - List of errors found.
     * @var array
     */
    protected $_errors = array();

    /**
     * Type this schema represents.  array | object
     * @var string
     */
    protected $_type = self::TYPE_OBJECT; // object | array

    /**
     * Schema must be defined in the construct
     */
    abstract function __construct();


    /**
     * Checks the document to make sure all required fields are present.
     *
     * @param $doc array
     * @return bool
     */
    public function checkRequired(&$doc)
    {
        $this->_required = false;
        //are we missing any required
        foreach ($this->_schema as $fieldName => $fieldValue) {

            //is there are required field?
            if (isset($this->_schema[$fieldName]['__required'])
                && $this->_schema[$fieldName]['__required'] === true)
            {
                $this->_required = true;
            }

            //Does this field pass?
            if (isset($this->_schema[$fieldName]['__required'])
                && $this->_schema[$fieldName]['__required'] === true
                && !isset($doc[$fieldName])
            ) {
                if ($this->_injectOnMissing && isset($this->_schema[$fieldName]['__default'])) {
                    $doc[$fieldName] = $this->_schema[$fieldName]['__default'];
                } else {
                    $this->_errors[] = 'Missing Required Field: ' .$fieldName;
                }

            }

            //Are there subfields
            if (isset($this->_schema[$fieldName]['__schema'])) {
                $s = $this->_schema[$fieldName]['__schema'];
                $value = isset($doc[$fieldName]) ? $doc[$fieldName] : null;
                $s->checkRequired($value);
                $this->_errors = array_merge($this->_errors,$s->getErrors());
                if ($s->isRequired()) {
                    $this->_required = true;
                }

            }
        }
        if (empty($this->_errors)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the whole document is valid based on the given scheme in the constructor.
     *
     * @param array $doc
     * @return bool
     */
    public function isValid(array &$doc)
    {
        $this->_errors = array(); //reset
        $this->tested = array();

        //test for validity
        foreach ($doc as $fieldName => $fieldValue) {
            if (is_array($fieldValue) && Ox_MongoSource::isMongoArray($fieldValue)) {
                //must test each element of the array (as an object)
                if ($this->_schema[$fieldName]['__schema']->_type!= self::TYPE_ARRAY) {
                    $this->_errors[] = "Expected an array, but got an object in field: $fieldName";
                }
                foreach ($fieldValue as $arrayObject) {
                    $this->isFieldValid($fieldName,$arrayObject,$doc);
                }
            } else {
                $this->isFieldValid($fieldName,$fieldValue,$doc);
            }
        }

        $this->checkRequired($doc);

        if (empty($this->_errors)){
            return true;
        } else {
            return false;
        }

    }

    /**
     * Checks if a particular field is valid.
     *
     * @param string $fieldName
     * @param mixed $fieldValue
     * @param array $doc
     * @return bool
     */
    public function isFieldValid($fieldName,$fieldValue,array &$doc)
    {
        $passes = true;
        if (isset($this->_schema[$fieldName]['__validator']) ) {
            $v = $this->_schema[$fieldName]['__validator'];
            $passes = $v->isValid($fieldValue);
            if(!$passes) {
                $vError = $v->getError($fieldName);
                $this->_errors[] = $vError ? $vError : "Field $fieldName failed validation. $vError Value: " . print_r($fieldValue,1);    
            }
        }
        if (isset($this->_schema[$fieldName]['__schema']) ) {
            //sub object or array
            $s = $this->_schema[$fieldName]['__schema'];
            $passes = $s->isValid($fieldValue);
            if (!$passes ) {
                $newErrors = $s->getErrors();
                $this->_errors = array_merge($this->_errors,$newErrors);
            }
        }
        if (!$passes) {
            if ($this->_replaceOnInvalid && isset($this->_schema[$fieldName]['__default'])) {
                $doc[$fieldName] = $this->_schema[$fieldName]['__default'];
            } else {
                $passes = false;
            }
        }
        return $passes;
    }

    //-------------------------------
    //Getters
    //-------------------------------

    /**
     * Return the collected errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Returns true if any fields we have gone through have been required
     *
     * @return boolean
     */
    private function isRequired()
    {
        return $this->_required;
    }


    //-------------------------------
    //Setters
    //-------------------------------
    /**
     * Sets if the schema should insert default values if variable is missing.
     *
     * @param boolean $injectOnMissing
     */
    public function setInjectOnMissing($injectOnMissing)
    {
        $this->_injectOnMissing = $injectOnMissing;
    }

    /**
     * Sets if the schema should replace to a default value if the value is invalid.
     *
     * @param boolean $replaceOnInvalid
     */
    public function setReplaceOnInvalid($replaceOnInvalid)
    {
        $this->_replaceOnInvalid = $replaceOnInvalid;
    }

    /**
     * Reset the collected errors
     */
    public function resetErrors()
    {
        $this->_errors = array();
    }

    /**
     * Manually set the fillDefault behavior.
     *
     * @param array $doc
     * @param bool $onMissing
     * @param bool $onInvalid
     * @return array
     */
    public function fillWithDefaults(array $doc=array(),$onMissing=true,$onInvalid=true)
    {
        $savedInjectOnMissing = $this->_injectOnMissing;
        $savedReplaceOnInvalid = $this->_replaceOnInvalid;

        $this->_injectOnMissing = $onMissing;
        $this->_replaceOnInvalid = $onInvalid;
        $this->isValid($doc);


        $this->_injectOnMissing = $savedInjectOnMissing;
        $this->_replaceOnInvalid = $savedReplaceOnInvalid;
        return $doc;
    }


    /**
     * Return private onInsert
     *
     * @return bool
     */
    public function checkOnInsert()
    {
       return $this->_onInsert;
    }

    /**
     * Finds the $fieldName in the schema and returns the sanitized version of $fieldValue
     *
     * @param $fieldName
     * @param $fieldValue
     * @return null|mixed A sanitized version of the $fieldValue
     */
    public function sanitizeField($fieldName,$fieldValue) {
        if (isset($this->_schema[$fieldName]['__validator']) ) {
            $v = $this->_schema[$fieldName]['__validator'];
            return $v->sanitize($fieldValue);
        }

        if (isset($this->_schema[$fieldName]['__schema']) ) {
            //sub object or array
            $s = $this->_schema[$fieldName]['__schema'];
            return $s->sanitize($fieldValue);
        }
        //TODO should error here instead of quietly returning unsanitized value maybe
        return $fieldValue;
    }

    /**
     * Checks if the whole document is valid.
     *
     * @param array $doc Document to be sanitized.
     * @return bool
     */
    public function sanitize(array &$doc)
    {
        $this->_errors = array(); //reset
        foreach ($doc as $fieldName => $fieldValue) {
            if (is_array($fieldValue) && Ox_MongoSource::isMongoArray($fieldValue)) {
                //must test each element of the array (as an object)
                if ($this->_schema[$fieldName]['__schema']->_type!= self::TYPE_ARRAY) {
                    $this->_errors[] = "Expected and array, but got an object in field: $fieldName";
                }
                foreach ($fieldValue as $arrayObject) {
                    $doc[$fieldName] = $this->sanitizeField($fieldName,$arrayObject,$doc);
                }
            } else {
                $doc[$fieldName] = $this->sanitizeField($fieldName,$fieldValue,$doc);
            }
        }
    }

}
