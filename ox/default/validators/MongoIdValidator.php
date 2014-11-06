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
 * A Validator for MongoId Objects
 * @package Ox_Schema_Validators
 */
class MongoIdValidator extends Ox_Validator {

    /**
     * Sets up the validator
     *
     * @param null $testParams
     * @param null $failMessage
     */
    function __construct($testParams=null,$failMessage=null)
    {
    }

    /**
     * Returns whether the value given is valid.
     *
     * @param $value
     * @return boolean
     */
    public function isValid($value)
    {
        // TODO: Implement isValid() method.
    }

    /**
     * Return the error message.
     *
     * @return string
     */
    public function getError($fieldName)
    {
        // TODO: Implement getError() method.
    }

    /**
     * Returns a cleaned version of the value that can be saved to the database
     *
     * @param $value
     * @return mixed
     */
    public function sanitize($value)
    {
        // TODO: Implement sanitize() method.
        return $value;
    }



} 