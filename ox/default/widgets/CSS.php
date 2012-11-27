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
 * Widget CSS
 * This widget Allows you to add CSS files from anywhere in your code and then have it added
 * in a layout (or anywhere)
 *
 * To set:
 * global $widget_handler;
 * $widget_handler->CSS->add("960.css"); // add a simple css file from /css/960.js
 *
 * To display:
 * <?php WidgetHandler::CSS(); ?>
 */

class CSS implements Ox_Widget {
    /**
     * Storage var for the list of CSS filenames.
     */
    private $_css_file_list = array();

    /**
     * Create link tags for the list of css files.
     *
     * @param bool $return_string
     * @return string
     */
    public function render($return_string = FALSE) {
        $output = '';

        foreach ($this->_css_file_list as $css_file => $css_options) {
            $media = '';
            if (isset($css_options['media']) && $css_options['media']!==FALSE) {
                $media = " media=\"{$css_options['media']}\" ";
            }
            $file = $css_file;
            $directory = $css_options['directory'];
            $output .= "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"{$directory}{$file}\" />\n";
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
}