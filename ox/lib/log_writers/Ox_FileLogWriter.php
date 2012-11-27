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
 * Write log messages to a file (default)
 *
 * Default location: /tmp/log/ox.log
 * 
 */
class Ox_FileLogWriter implements Ox_LogWriter
{
    /**
     * The log file where messages can be recorded.
     * Web server permission must be granted for this file or you'll see nasty errors.
     */
    private $log_file;
    
    /**
     * Constructor initializes the file location.
     */
    public function __construct($file)
    {
        $this->log_file = $file;
        //just test to see if we can access the file.
        if ($fp = fopen($this->log_file, 'a+')) {
            fclose($fp);
        } else {
            print <<<HTML
<div style="background-color: #f08080;font-weight: bold;font-size:34px;border: 2px solid black;">
Can not write to the log file ({$this->log_file}). Please check file and directory permissions and that the web service
has permission to write.
</div>
HTML;
            throw new Exception( "Logger: Error opening log file: " . $this->log_file );
        }
    }

    /**
     * Writes a message to the file.
     */
    public function log($p_message)
    {
        if ($fp = fopen($this->log_file, 'a+')) {
            fwrite($fp, $p_message."\n");
            fclose($fp);
        } else {
            throw new Exception( "Error opening log file: " . $this->log_file );
        }
    }
}
