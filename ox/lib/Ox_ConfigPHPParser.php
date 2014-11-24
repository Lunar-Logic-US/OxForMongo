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
 * @package Ox_Configuration
 */


require_once(OX_FRAMEINTERFACE  . 'Ox_ConfigParser.php');

/**
 * Interface and implementation for loading of app configurations values.
 *
 * @package Ox_Configuration
 */
class Ox_ConfigPHPParser implements Ox_ConfigParser
{
    /** @var string The file to parse. */
    private $file;
    
    /** @var string Configuration directory path. */
    private $config_dir;
    
    /** @var array Configuration array. */
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
                if(!in_array($file, array('routes.php', 'global.php', 'framework.php','modules.php', 'hook.php'))) {
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
     * @return mixed
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
     * @param string $key
     * @return mixed
     */
    public function getAppConfigValue($key)
    {
        return $this->getValue('app', $key);
    }

    /**
     * Returns a configuration value from the framework array.
     * @param string $key
     * @return mixed
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
