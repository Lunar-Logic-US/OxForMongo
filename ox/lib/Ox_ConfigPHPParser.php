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
 * Interface and implementation for loading of app and framework config values.
 */

require_once(OX_FRAMEINTERFACE  . 'Ox_ConfigParser.php');

class Ox_ConfigPHPParser implements Ox_ConfigParser
{
    /**
     * The file to parse.
     */
    private $file;
    
    /**
     * Configuration directory path.
     */
    private $config_dir;
    
    /**
     * The configuration array.
     */
    private $config = array();
    
    /**
     * Default constructor
     *
     * @param $config_dir
     */
    public function __construct($config_dir=DIR_APPCONFIG)
    {
        $this->config_dir = $config_dir;
        $files = scandir($config_dir);
        foreach($files as $file) {
            if(preg_match('/^.*\.php$/', $file)) {
                if(!in_array($file, array('routes.php', 'global.php', 'framework.php'))) {
                    $this->file = $file;
                    $this->inspectFile();
                }
            }
        }
    }
    
    /**
     * Get a value from the file by section and key.
     *
     * @param string $section
     * @param string $key
     */
    public function getValue($section,$key)
    {
        if(isset($this->config[$section]) && isset($this->config[$section][$key])) {
            return $this->config[$section][$key];
        } else {
            return null;
        }
    }
    
    /**
     * Returns a configuration value from the app array.
     *
     * @return string
     */
    public function getAppConfigValue($key)
    {
        return $this->getValue('app', $key);
    }
    
    /**
     * Returns a configuration value from the framework array.
     */
    public function getFrameworkConfigValue($key)
    {
        return $this->getValue('framework', $key);
    }
    
    /**
     * parses the file, adding values to the config array.
     */
    private function inspectFile()
    {
        include($this->config_dir . DIRECTORY_SEPARATOR . $this->file);
        foreach(get_defined_vars() as $key => $value) {
            $this->config[preg_replace('/.php$/', '', $this->file)][$key] = $value;
        }
    }
}

?>