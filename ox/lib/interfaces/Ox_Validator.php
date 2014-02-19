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
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Schema_Validators
 */

/**
 * Defines a test that can be used with an Ox_Schema.
 * @package Ox_Schema_Validators
 */
abstract class Ox_Validator {

    /**
     * This is the default subdirectory name to use in the default lib directories.
     */
    const DIR_NAME = 'validators/';

    /**
     * This is a list of paths to search for validators.
     *
     * @var array
     */
    private static $_path = array();

    /**
     * Save the setting and setup what is need for the isValid call.
     *
     * @param null $testParams parameters for this version of the test (like string length, or regex)
     * @param null $failMessage
     */
    public function __construct($testParams=null,$failMessage=null){}

    /**
     * Loads the given file/class from the default directories or any pushed on directory.
     *
     * @param $className
     * @param null|array $testParams
     * @param null|string $failMessage
     * @return Ox_Validator
     */
    public static function load($className,$testParams=null,$failMessage=null)
    {
        //These are added last so they are searched last.
        //The default path for the app vaidators
        self::addLibPath(DIR_APPLIB . self::DIR_NAME);
        //The default path for Ox validators
        self::addLibPath(OX_FRAME_DEFAULT . self::DIR_NAME);

        Ox_LibraryLoader::loadCode($className,self::$_path);
        $state = new $className($testParams,$failMessage);
        return $state;
    }

    /**
     * Adds a new directory on the directory path for the locations of the validators
     * @param $path
     */
    public static function addLibPath($path)
    {
        array_push(self::$_path,$path);
    }


    /**
     * Returns whether the value given is valid.
     *
     * @param $value
     * @return boolean
     */
    abstract public function isValid($value);

    /**
     * Return the error message.
     *
     * @return string
     */
    abstract public function getError();

    /**
     * Returns a cleaned version of the value that can be saved to the database
     *
     * @param $value
     * @return mixed
     */
    abstract public function sanitize($value);
}