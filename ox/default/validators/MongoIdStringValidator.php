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
 * @package Ox_Schema_Validators
 */

/**
 * A Validator for MongoIdStrings
 * @package Ox_Schema_Validators
 */
class MongoIdStringValidator extends Ox_Validator {

    /**
     * RegEx used to validate Mongo Id String
     */
    const REG_EX = '/^([0123456789abcdef]){24}\z/';

    /**
     * @var string Error if one was generated.
     */
    private $_error = null;
    /**
     * @var bool Do we allow null strings
     */
    private $_allowNull = true;

    /**
     * Sets up the parameters for this validator.
     *
     * @param null $testParams true if nulls are allowed.
     * @param null $failMessage
     */
    function __construct($testParams=null,$failMessage=null)
    {
        if (isset($testParams['allowNull'])) {
            //Notice I am only allowing the value of true, all else is false
            $this->_allowNull = ($testParams['allowNull']===true);
        }
    }

    /**
     * Returns whether the value given is valid.
     *
     * @param $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (strlen($value)<24) {
            $this->_error  = 'Mongo Id is too short.';
            return false;
        }
        if (strlen($value)>24) {
            $this->_error  = 'Mongo Id is too long.';
            return false;
        }
        //Yes I know that I could just use the regex, but I get better error messages this way.
        $test = preg_match(self::REG_EX,$value);
        if ($test===1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return the error message.
     *
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns a cleaned version of the value that can be saved to the database
     *
     * @param $value
     * @return mixed
     */
    public function sanitize($value)
    {
        if ($value === null) {
            return null;
        }
        return new MongoId($value);
    }


} 