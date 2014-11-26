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

//Making this available for all user code
require_once(OX_FRAMEINTERFACE  . 'Ox_LogWriter.php');

/**
 * Class to facilitate logging.
 *
 * There are three log levels available: MESSAGE, WARNING, ERROR and DEBUG. The
 * logger can be configured to react to one, all, or any combination of the
 * available logging levels.
 * <br><br>
 * In addition, one, or many logging levels can be associated with a specific
 * logger that implements the 'Logger' interface. The default is to set all
 * to 'FileLogger'.
 * <br><br>
 * This class is setup to currently run both as a pseudo singleton and a regular class.  This was
 * done for backward compatibility.
 * <br><br>
 * Do log in your code:<pre><code>
 * Ox_Logger::logDebug("Server URI: " .$_SERVER['REQUEST_URI']); //just a plain debug message
 * Ox_Logger::logDebug($_SERVER); //print_r the variable into the log
 * Ox_Logger::logError("Error in Request URI: " .$_SERVER['REQUEST_URI']); //log an error message
 * </code></pre>
 * TODO: Add config setting to set which logWriter is going to be used.
 * @package Ox_Logging
 */
class Ox_Logger
{
    //These are bit flags and types for setting what is written to the log
    /** Log level None - Nothing is logged */
    const NONE    = 0;
    /** Log level Message */
    const MESSAGE = 1;
    /** Log level Warning */
    const WARNING = 2;
    /** Log Level Error */
    const ERROR   = 4;
    /** Log Level Debug */
    const DEBUG   = 8;

    /** Default name for the ox log */
    const DEFAULT_FILENAME = 'ox.log';

    /** Default log writer object */
    const LOG_WRITER = 'Ox_FileLogWriter';

    /** Directory of the log writers */
    private static $_lib;
    
    /** Specifies the level of the entry (e.g. notice, warning, error) see above. */
    private static $_logLevel;
    
    /** Format of the timestamp. */
    private static $datetime_format = "\[d\/M\/Y:H:i:s\]";
    
    /** An include path. */
    private static $_classPath;

    /** Track usage by marking initialization flag. */
    private static $_initialized=FALSE;
    
    /** Error level handlers. */
    private static $_handlers = array(
        Ox_Logger::MESSAGE => null,
        Ox_Logger::WARNING => null,
        Ox_Logger::ERROR => null,
        Ox_Logger::DEBUG => null
    );

    /**
     * Constructor.
     *
     * Initializes the logger using optionally passed in parameters.
     *
     * @param string $log_file
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
     * @param null|string $logFile Name of the log file
     * @param int $level
     * @return bool
     * @throws Exception
     */
    private static function _init($logFile=null, $level = null) {
        if (!self::$_initialized) {
            $config_parser = Ox_LibraryLoader::Config_Parser();
            if (!isset($config_parser) && $logFile===null) {
                return false;
            }
            self::$_lib = DIR_FRAMELIB . 'log_writers/';
            self::$_classPath = array(self::$_lib,DIR_APPLIB);

            self::$_initialized = TRUE;
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
                $level = $config_parser->getAppConfigValue('log_level');
                // No log level set? Default to all levels
                if($level === null) {
                    $level = self::allLogLevels();
                } else {
                    $level = (int) $level;
                }
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
     * Set the logger for a log message type.
     *
     * @param int $type
     * @param Ox_LogWriter $logger
     */
    public static function setLogger($type, $logger)
    {
        self::$_handlers[$type] = $logger;
    }

    /**
     * Set the current log level.
     * @param int $level
     */
    public static function setLevel($level)
    {
        self::$_logLevel = $level;
    }

    /**
     * Send the message to the correct logWriter
     *
     * @param int $level
     * @param string $p_message
     */
    private static function _logIt($level, $p_message)
    {
        //only run if we are initialized
        if (self::_init()) {
            $message = print_r( $p_message, 1 );
            if(($level & self::$_logLevel) == Ox_Logger::MESSAGE) {
                self::$_handlers[Ox_Logger::MESSAGE]->log(self::_getFormattedTimestamp().' MESSAGE '.$message);
            } elseif(($level & self::$_logLevel) == Ox_Logger::WARNING) {
                self::$_handlers[Ox_Logger::WARNING]->log(self::_getFormattedTimestamp().' WARNING '.$message);
            } elseif(($level & self::$_logLevel) == Ox_Logger::ERROR) {
                self::$_handlers[Ox_Logger::ERROR]->log(self::_getFormattedTimestamp().' ERROR '.$message);
            } elseif(($level & self::$_logLevel) == Ox_Logger::DEBUG) {
                self::$_handlers[Ox_Logger::DEBUG]->log(self::_getFormattedTimestamp().' DEBUG '.$message);
            }
        }
    }
    
    // Wrapper functions for the 4 log levels
    /**
     * Send a message of type MESSAGE.
     * @param string $message
     */
    public static function logMessage($message)
    {
        self::_logIt(Ox_Logger::MESSAGE, $message);
    }

    /**
     * Send a message of type WARNING
     * @param string $message
     */
    public static function logWarning($message)
    {
        self::_logIt(Ox_Logger::WARNING, $message);
    }

    /**
     * Send a message of type ERROR
     * @param string $message
     */
    public static function logError($message)
    {
        self::_logIt(Ox_Logger::ERROR, $message);
    }

    /**
     * Send a message of type DEBUG
     * @param string $message
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