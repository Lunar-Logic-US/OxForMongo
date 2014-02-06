<?php
/**
 * Created by PhpStorm.
 * User: m2calabr
 * Date: 2/5/14
 * Time: 6:36 PM
 */

class MongoIdStringValidator extends Ox_Validator {

    const REG_EX = '/^([0123456789abcdef]){24}\z/';

    private $_error = null;
    private $_allowNull = true;

    /**
     * Sets up the parameters for this validator.
     *
     * @param null $testParams['allowNull] true if nulls are allowed.
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
     *
     *          1         2
     * 123456789012345678901234
     * 52f2dd274dd0da5418000001
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