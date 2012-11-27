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
 * Defines a test that can be used with an Ox_Schema.
 */
interface Ox_Validator {
    /**
     * Save the setting and setup what is need for the isValid call.
     * @param null $testParams
     * @param null $failMessage
     */
    public function __construct($testParams=null,$failMessage=null);

    /**
     * Returns whether the value given is valid.
     * @param $value
     * @return boolean
     */
    public function isValid($value);

    /**
     * Return the error message.
     * @return string
     */
    public function getError();
}