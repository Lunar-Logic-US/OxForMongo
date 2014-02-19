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
 * @package Ox_Logging
 */


//search replace: $this->logger->log with Ox_Logger::log
//search replace: $logger->log with Ox_Logger::log

//Making this available for all user code
require_once(OX_FRAMEINTERFACE  . 'Ox_LogWriter.php');

/**
 * Class to facilitate logging.
 *
 * There are three log levels available: MESSAGE, WARNING, ERROR and DEBUG. The
 * logger can be configured to react to one, all, or any combination of the
 * available logging levels.
 *
 * In addition, one, or many logging levels can be associated with a specific
 * logger that implements the 'Logger' interface. The default is to set all
 * to 'FileLogger'.
 *
 * This class is setup to currently run both as a pseudo singleton and a regular class.  This was
 * done for backward compatibility
 *
 * TODO: Add config setting to set which logWriter is going to be used.
 * @package Ox_Logging
 */
class Ox_Logger
{
    const NONE    = 0;
    const MESSAGE = 1;
    const WARNING = 2;
    const ERROR   = 4;
    const DEBUG   = 8;
    const DEFAULT_FILENAME = 'ox.log';
    const LOG_WRITER = 'Ox_FileLogWriter';

    /**
     * Specify the directory of the log writers
     */
    private static $_lib;
    
    /**
     * Specifies the level of the entry (e.g. notice, warning, error) see above.
     */
    private static $_logLevel;
    
    /**
     * The format of to use for the timestamp.
     */
    private static $datetime_format = "\[d\/M\/Y:H:i:s\]";
    
    /**
     * An include path.
     */
    private static $_classPath;

    /**
     * Track usage by marking initialization flag.
     */
    private static $_initialized=FALSE;
    
    /**
     * Error level handlers.
     */
    private static $_handlers = array(
        Ox_Logger::MESSAGE => null,
        Ox_Logger::WARNING => null,
        Ox_Logger::ERROR => null,
        Ox_Logger::DEBUG => null
    );

    /**
     * Constructor initializes the logger using optionally passed in parameters.
     *
     * @param String $log_file
     * @param int $level
     */
    public function __construct($log_file=null, $level = 0)
    {
        self::_init($log_file,$level);
    }

    /**
     * Returns the setting for all of the log levels.
     *
     * @return int
     */
    public static function allLogLevels()
    {
        return Ox_Logger::MESSAGE | Ox_Logger::WARNING | Ox_Logger::ERROR | Ox_Logger::DEBUG;
    }

    /**
     * Initialize  this object
     *
     * This can be run at any time that we need to send a message.  This means that you can just log a message
     * and this object will initialize itself.
     *
     * @param null $logFile Name of the log file
     * @param null $level
     * @return bool
     * @throws Exception
     */
    private static function _init($logFile=null, $level = null) {
        if (!self::$_initialized) {
            global $config_parser;
            if (!isset($config_parser) && $logFile===null) {
                return false;
            }
            self::$_lib = DIR_FRAMELIB . 'log_writers/';
            self::$_classPath = array(self::$_lib,DIR_APPLIB);

            self::$_initialized = TRUE;
            global $config_parser;
            if (empty($logFile)) {
                $logPath = $config_parser->getAppConfigValue('log_dir');
                if (empty($logPath)) {
                    throw new Exception( "Ox_Logger: No log file directory given.");
                }
                //check for last slash, must check for either DOS or Unix
                if (substr($logPath,-1)!== '/' && substr($logPath,-1)!== '\\')    {
                    $logPath .= DIRECTORY_SEPARATOR;
                }

                $logName = $config_parser->getAppConfigValue('log_filename');
                if (empty($logName)) {
                    $logName = self::DEFAULT_FILENAME;
                }

                $logFile = $logPath . $logName;
            }
            if ($level === null) {
                $level = (int) $config_parser->getAppConfigValue('log_level');
            }
            self::$_logLevel = $level;
            $writer = self::LOG_WRITER;
            foreach(self::$_handlers as $key => $value) {
                Ox_LibraryLoader::loadCode($writer,self::$_classPath);
                self::$_handlers[$key] = new $writer($logFile);
            }
        }
        return true;
    }

    /**
     * Set the logger for a log message type
     *
     * @param $type
     * @param $logger
     */
    public static function setLogger($type, $logger)
    {
        self::$_handlers[$type] = $logger;
    }

    /**
     * Set the current log level
     * @param $level
     */
    public static function setLevel($level)
    {
        self::$_logLevel = $level;
    }

    /**
     * Send the message to the correct logWriter
     *
     * @param $level
     * @param $p_message
     */
    private static function _logIt($level, $p_message)
    {
        //only run if we are initialized
        if (self::_init()) {
            $message = print_r( $p_message, 1 );
            if($level == Ox_Logger::MESSAGE) {
                self::$_handlers[Ox_Logger::MESSAGE]->log(self::_getFormattedTimestamp().' MESSAGE '.$message);
            } elseif($level == Ox_Logger::WARNING) {
                self::$_handlers[Ox_Logger::WARNING]->log(self::_getFormattedTimestamp().' WARNING '.$message);
            } elseif($level == Ox_Logger::ERROR) {
                self::$_handlers[Ox_Logger::ERROR]->log(self::_getFormattedTimestamp().' ERROR '.$message);
            } elseif($level == Ox_Logger::DEBUG) {
                self::$_handlers[Ox_Logger::DEBUG]->log(self::_getFormattedTimestamp().' DEBUG '.$message);
            }
        }
    }
    
    // Wrapper functions for the 4 log levels
    /**
     * Send a message of type MESSAGE
     * @param $message
     */
    public static function logMessage($message)
    {
        self::_logIt(Ox_Logger::MESSAGE, $message);
    }

    /**
     * Send a message of type WARNING
     * @param $message
     */
    public static function logWarning($message)
    {
        self::_logIt(Ox_Logger::WARNING, $message);
    }

    /**
     * Send a message of type ERROR
     * @param $message
     */
    public static function logError($message)
    {
        self::_logIt(Ox_Logger::ERROR, $message);
    }

    /**
     * Send a message of type DEBUG
     * @param $message
     */
    public static function logDebug($message)
    {
        self::_logIt(Ox_Logger::DEBUG, $message);
    }


    /**
     * Returns a formatted timestamp to use in the log entry
     *
     * @return string
     */
    private static function _getFormattedTimestamp() {
        $dateTime = new DateTime( "now", new DateTimeZone( 'UTC' ) );
        $dateTime->setTimezone(new DateTimeZone("America/Los_Angeles"));
        return $dateTime->format(self::$datetime_format);
    }

}