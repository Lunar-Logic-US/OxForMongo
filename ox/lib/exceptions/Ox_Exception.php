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
 * A base level Ox Error
 */
class Ox_Exception extends Exception {
    /**
     * Saved String Code
     * @var string
     */
    protected $code = '';

    /**
     * String to prepend on the error message for the log.
     * @var string
     */
    protected static $_logHeader='Ox_Exception:';

    /**
     * Create the exception.
     *
     * Save the string code.
     * @param null $message
     * @param string $code
     * @param Exception $previous
     */
    public function __construct($message=null, $code = '', Exception $previous = null) {
        $this->code = $code;
        if (class_exists('Ox_Logger',false)){
            Ox_Logger::logDebug(self::$_logHeader . $message);
        }
        parent::__construct($message, 0, $previous);

    }

    /**
     * Return the String code
     * @return string
     */
    public function getStringCode() {
        return $this->code;
    }
}