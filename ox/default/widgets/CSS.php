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
 * @package Ox_Widgets
 */

/**
 * Widget CSS
 * This widget Allows you to add CSS files from anywhere in your code and then have it added
 * in a layout (or anywhere)
 *
 * To set:
 * <code>
 * $widget_handler=Ox_LibraryLoader::Widget_Handler();
 * $widget_handler->CSS->add("960.css"); // add a simple css file from /css/960.js
 * </code>
 *
 * To display:
 * <code>
 * WidgetHandler::CSS();
 * </code>
 * @package Ox_Widgets
 */

class CSS implements Ox_Widget {
    /**
     * Storage var for the list of CSS filenames.
     */
    private $_css_file_list = array();
    /*
     * Added for site overrides.
     */
    private $_css_override_list = array();

    /**
     * Create link tags for the list of css files.  Files specified in the override
     * list will be added after files specified using the normal add functions.
     *
     * @param bool $return_string
     * @return string
     */
    public function render($return_string = FALSE) {
        $output = '';

        $appWebBase = Ox_LibraryLoader::Config_Parser()->getAppConfigValue(Ox_Dispatch::CONFIG_WEB_BASE_NAME);
        foreach ($this->_css_file_list as $css_file => $css_options) {
            $media = '';
            if (isset($css_options['media']) && $css_options['media']!==FALSE) {
                $media = " media=\"{$css_options['media']}\" ";
            }
            $file = $css_file;

            $directory = $appWebBase . $css_options['directory'];
            $url = Ox_Router::buildURL($css_options['directory'].$file);
            if (strpos($url, '://') == false) {
                Ox_LibraryLoader::loadCode('WidgetHelper', array(__DIR__.DIRECTORY_SEPARATOR));
                $url = WidgetHelper::addCacheBuster($url);
            }
            $output .= "<link rel=\"stylesheet\" type=\"text/css\"{$media}href=\"{$url}\" />\n";
        }

        foreach ($this->_css_override_list as $css_file => $css_options) {
            $media = '';
            if (isset($css_options['media']) && $css_options['media']!==FALSE) {
                $media = " media=\"{$css_options['media']}\" ";
            }
            $file = $css_file;

            $directory = $appWebBase . $css_options['directory'];
            $url = Ox_Router::buildURL($css_options['directory'].$file);
            if (strpos($url, '://') == false) {
                Ox_LibraryLoader::loadCode('WidgetHelper', array(__DIR__.DIRECTORY_SEPARATOR));
                $url = WidgetHelper::addCacheBuster($url);
            }
            $output .= "<link rel=\"stylesheet\" type=\"text/css\"{$media}href=\"{$url}\" />\n";
        }

        if ($return_string === FALSE) {
            print $output;
        } else {
            return $output;
        }
    }

    /**
     * Add file to the top of the CSS list.
     *
     * @param $file
     * @param bool $media
     * @param string $directory
     */
    public function add_to_top($file,$media=FALSE,$directory='/css/') {
        $options = array('media'=>$media,'directory'=>$directory);
        if (isset($this->_css_file_list[$file])) {
            $this->_css_file_list[$file] = $options;
        } else {
            $new = array($file => $options);
            $this->_css_file_list = array_merge($new,$this->_css_file_list);
        }
    }

    /**
     * Add file to the bottom of the CSS list.
     *
     * @param $file
     * @param bool $media
     * @param string $directory
     */
    public function add_to_bottom($file,$media=FALSE,$directory='/css/') {
        $new = array($file => array('media'=>$media,'directory'=>$directory));
        //This will overwrite if the same file is used twice.
        $this->_css_file_list = array_merge($this->_css_file_list,$new);
    }

    /**
     * Add file to the bottom of the CSS list.
     *
     * @param $file
     * @param bool $media
     * @param string $directory
     */
    public function add($file,$media=FALSE,$directory='/css/'){
        $this->add_to_bottom($file,$media,$directory);
    }

    /**
     * Add override file to the bottom of the override list.
     *
     * @param $file
     * @param bool $media
     * @param string $directory
     */
    public function add_override($file,$media=FALSE,$directory='/css/') {
        $new = array($file => array('media'=>$media,'directory'=>$directory));
        //This will overwrite if the same file is used twice.
        $this->_css_override_list = array_merge($this->_css_override_list,$new);
    }
}
