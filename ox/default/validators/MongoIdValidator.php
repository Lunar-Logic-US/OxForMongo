<?php
/**
 * Created by PhpStorm.
 * User: m2calabr
 * Date: 2/5/14
 * Time: 6:32 PM
 */

class MongoIdValidator extends Ox_Validator {
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
    public function getError()
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