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
 * Widget HtmlTitle
 * This widget Allows you to set title tag from anywhere in your code and then have it displayed
 * in a layout (or anywhere)
 *
 * To set:
 * global $widget_handler;
 * $widget_handler->HtmlTitle->set("SewOn");
 *
 * To display:
 * <?php WidgetHandler::HtmlTitle(); ?>
 */

class HtmlTitle implements Ox_Widget {
    /**
     * The title of the webpage.
     */
    private $_title;

    /**
     * Load a default title from the config or set a default title
     */
    public function __construct(){
        global $config_parser;
        if (isset($config_parser)) {
            $this->_title=$config_parser->getAppConfigValue('default_title');
        }
        if (empty($this->_title)) {
            $this->_title = 'Ox Framework';
        }
    }

    /**
     * Output or return the title tag
     * @param bool $return_string
     * @return string
     */
    public function render($return_string = FALSE) {
        $output =  "<title>{$this->_title}</title>";
        if ($return_string === FALSE) {
            print $output;
            return TRUE;
        } else {
            return $output;
        }
    }

    /**
     * Set the title to be used in the title tag.
     *
     * @param $title
     * @internal param bool $return_string
     * @return string
     */
    public function set($title) {
        $this->_title = $title;
    }
}