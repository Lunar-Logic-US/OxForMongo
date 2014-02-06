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
 * Validator that uses the provided RegEx to check against.
 */
class Ox_RegExValidator extends  Ox_Validator
{
    /**
     * The RegEx to validate against.
     * 
     * @var string
     */
    protected $regEx;

    /**
     * Message to return for a failure.
     * 
     * @var string
     */
    protected $failMessage;

    /**
     * Default constructor.
     * 
     * @param null|string $testParams
     * @param null|string $failMessage
     */
    public function __construct($testParams=null,$failMessage=null)
    {
        if ($testParams===null) {
            $this->regEx = '/.*/';
        } else {
            $this->regEx = $testParams;

        }
        if ($failMessage===null) {
            $this->failMessage = 'Failed RegEx:' . $this->regEx;
        } else {
            $this->failMessage = $failMessage;
        }
    }

    /**
     * See if the value passes the RegEx
     * 
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $test = preg_match($this->regEx,$value);
        return ($test===1)?true:false;

    }

    /**
     * Return the created error.
     * 
     * @return string
     */
    public function getError(){
        return $this->failMessage;
    }

    public function sanitize($value)
    {
        return $value;
    }

}