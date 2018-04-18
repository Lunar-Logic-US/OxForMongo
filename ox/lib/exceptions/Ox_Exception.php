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
 * @package Ox_Exceptions
 */

/**
 * A base level Ox Error
 * @package Ox_Exceptions
 */
class Ox_Exception extends Exception {
    /**
     * Saved String Code
     * @var string
     */
    public static $DEBUG=false;
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
     * @param string $message
     * @param string $code
     * @param Exception $previous
     */
    public function __construct($message=null, $code = '', Exception $previous = null) {
        $this->code = $code;
        if (class_exists('Ox_Logger',false)){
            // We only want to log objects and other non-strings if DEBUG is true.
            // If DEBUG is false and we are given a non-string, log the fact that some information is omitted
            if (self::$DEBUG) {
                $message = var_export($message, true);
            }
            else {
                if (! is_string($message)) {
                    $message = 'Dump not shown because Ox_Exception::DEBUG is false';
                }
            }
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

